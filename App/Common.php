<?php
include_once BASEDIR . '/App/RedisClass.php';
include_once BASEDIR . '/App/DAOPDO.php';
require_once BASEDIR . '/vendor/autoload.php';
use PHPHtmlParser\Dom;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

/**
 * Created by PhpStorm.
 * User: lkk
 * Date: 2018/11/12
 * Time: 6:44 PM
 */

/*
 * 格式化输出打印
 * */
function selfPrint($data, $die = '')
{
    echo "<pre><meta charset='UTF-8'>";
    print_r($data);
    echo "</pre>" . PHP_EOL;
    if ($die)
        die;
}

/*
 * debug输出打印
 * */
function debugPrint($data, $die = '')
{

    if ($_GET['debug']) {
        selfPrint($data, $die = '');
    }
}

/*
 * 睡眠输出打印
 * */
function sleepPrint($data, $time = 3)
{
    echo date('Y-m-d H:i:s') . "：sleepPrint..." . PHP_EOL;
    print_r($data);
    echo PHP_EOL;
    while ($time > 0) {
        echo "SLEEP..." . $time . PHP_EOL;
        sleep(1);
        $time--;
    }
    echo "RESTART..." . PHP_EOL;
}

/*
 * 初始化数据库连接
 * */
function M()
{
    $pdo = DAOPDO::getInstance(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_CHAR);
    if ($pdo)
        return $pdo;
}

/*
 * 初始化数据库连接
 * */
function R($config = [ ])
{
    $config = $config ? : [ 'host' => REDIS_HOST, 'port' => REDIS_PORT ];
    $redis  = RedisClass::getInstance($config);
    if ($redis)
        return $redis;
}


/*
 * 记录日志
 * */
function startLog($data = [ ])
{
    $data               = is_array($data) && $data ? $data : [ ];
    $data['start_time'] = microtime(true);

    return M()->insert('wx_public_account_push_log', $data);
}

/*
 * 记录日志
 * */
function endLog($log_id = '', $data = [ ])
{

    if (!$log_id)
        return;
    $data             = is_array($data) && $data ? $data : [ ];
    $data['end_time'] = microtime(true);
    $info             = M()->query('select * from wx_public_account_push_log where id=' . $log_id, 'Row');
    $data['use_time'] = round($data['end_time'] - $info['start_time'], 4);
    M()->update('wx_public_account_push_log', $data, 'id=' . $log_id);
}

/*
 * 删除日志
 * */
function deleteLog($log_id = '')
{
    if (!$log_id)
        return;
    M()->delete('wx_public_account_push_log', 'id=' . $log_id);
}

/*
 * 记录日志
 * */
function writeLog($content = '', $come_from = 0, $unique_str = '')
{

    $m                       = M();
    $save_data               = [ ];
    $save_data['data']       = !is_array($content) ? : json_encode($content);
    $save_data['add_time']   = time();
    $save_data['come_from']  = $come_from;
    $save_data['unique_str'] = $unique_str;
    $m->insert('work_log', $save_data, $debug = false);
}

/*
 * 并发推送
 * */
function multiHttpd($data, $url = '')
{

    $chArr = $result = [ ];
    $mh    = curl_multi_init();
    if (!$data)
        return $result;

    foreach ($data as $k => $v) {

        // 请求地址
        $url = isset($v['curl_url']) && $v['curl_url'] ? $v['curl_url'] : $url;
        if (!$url)
            continue;

        $chArr[ $k ] = curl_init();
        curl_setopt($chArr[ $k ], CURLOPT_URL, $url);
        curl_setopt($chArr[ $k ], CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chArr[ $k ], CURLOPT_SSL_VERIFYHOST, false);
        if (isset($v['data']) and $v['data']) {
            curl_setopt($chArr[ $k ], CURLOPT_POSTFIELDS, json_encode($v['data']));
            curl_setopt($chArr[ $k ], CURLOPT_POST, 1);
        }
        curl_setopt($chArr[ $k ], CURLOPT_HEADER, 0);
        curl_setopt($chArr[ $k ], CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chArr[ $k ], CURLOPT_TIMEOUT, 3);
    }

    foreach ($chArr as $k => $ch) {
        $res = curl_multi_add_handle($mh, $ch); //2 增加句柄
    }

    $active = null;

    do {

        while (($mrc = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM)
            ;

        if ($mrc != CURLM_OK)
            break;

        while ($done = curl_multi_info_read($mh)) {
            $info                           = curl_getinfo($done['handle']);
            $error                          = curl_error($done['handle']);
            $result[ (int)$done['handle'] ] = curl_multi_getcontent($done['handle']);
            curl_multi_remove_handle($mh, $done['handle']);
            curl_close($done['handle']);
        }

        if ($active > 0)
            curl_multi_select($mh);

    } while ($active);

    $res = [ ];

    foreach ($chArr as $chArr_k => $chArr_v) {
        if (isset($result[ (int)$chArr_v ])) {
            $res[ $chArr_k ] = $result[ (int)$chArr_v ];
        }
    }

    return $res;
}

/**
 * GET 请求
 *
 * @param string $url
 */
function http_get($url)
{
    $oCurl = curl_init();
    if (stripos($url, "https://") !== false) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    $sContent = curl_exec($oCurl);
    $aStatus  = curl_getinfo($oCurl);
    curl_close($oCurl);
    if (intval($aStatus["http_code"]) == 200) {
        return $sContent;
    } else {
        return false;
    }
}

/**
 * POST 请求
 *
 * @param string $url
 * @param array  $param
 *
 * @return string content
 */
function http_post($url, $param)
{

    $oCurl = curl_init();
    if (stripos($url, "https://") !== false) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
    }
    if (is_string($param)) {
        $strPOST = $param;
    } else {
        $aPOST = [ ];
        foreach ($param as $key => $val) {
            $aPOST[] = $key . "=" . urlencode((string)$val);
        }
        $strPOST = join("&", $aPOST);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($oCurl, CURLOPT_POST, true);
    curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
    $sContent = curl_exec($oCurl);
    $aStatus  = curl_getinfo($oCurl);
    curl_close($oCurl);
    if (intval($aStatus["http_code"]) == 200) {
        return $sContent;
    } else {
        return false;
    }
}

function to10($isbn)
{
    $isbn = str_replace('-', '', $isbn);
    if (!preg_match('/^\d+x?$/i', $isbn)) {
        return null;
    }

    if (strlen($isbn) == 10) {
        return $isbn;
    }

    $sum = 0;
    $num = substr($isbn, 3, 9);
    for ($i = 10, $p = 0; $i > 1; $i--, $p++) {
        $sum += $i * intval($num[ $p ]);
    }

    $m = $sum % 11;

    $check = 11 - $m;

    if ($check == 10) {
        $check = 'x';
    }

    if ($check == 11) {
        $check = '0';
    }

    return $num . $check;
}

function to13($isbn)
{
    $isbn = str_replace('-', '', $isbn);
    if (!preg_match('/^\d+x?$/i', $isbn)) {
        return null;
    }

    if (strlen($isbn) == 13) {
        return $isbn;
    }

    $sum = 0;
    $num = '978' . substr($isbn, 0, 9);

    for ($i = 0; $i < 12; $i++) {
        $n = $num[ $i ];
        if (($i + 1) % 2 == 0) {
            $sum += $n * 3;
        } else {
            $sum += $n;
        }
    }

    $m     = $sum % 10;
    $check = 10 - $m;

    return $num . $check;
}

function getISBN($isbn)
{
    $url    = 'http://isbn.szmesoft.com/isbn/query?isbn=';
    $res    = file_get_contents($url . $isbn);
    $search = json_decode($res, true);
    if ($search && isset($search['ID'])) {
        $arr['photo_url']  = "http://isbn.szmesoft.com/ISBN/GetBookPhoto?ID=" . $search['PhotoUrl'];
        $arr['isbn']       = $search['ISBN'];
        $arr['book_name']  = $search['BookName'];
        $arr['author']     = $search['Author'];
        $arr['publishing'] = $search['Publishing'];
        $arr['asin']       = $search['ASIN'];
        $arr['weight']     = $search['Weight'];
        $arr['size']       = $search['Size'];
        $arr['pages']      = $search['Pages'];
        $arr['price']      = $search['Price'];

        return $arr;
    }

    return [ ];
}

function getISBNfromDouban($isbn)
{
    $surl    = 'https://book.douban.com/isbn/' . $isbn . '/';
    $headers = json_encode(get_headers($surl), true);
    $headers = json_encode($headers, true);
    $surl    = cut($headers, 'Location: ', '"');
    $surl    = str_replace('\\', '', $surl);//302地址
    $data    = get($surl);

    $dom = new Dom;
    $dom->load($data);
    $res                       = [ ];
    $res['description']        = strip_tags($dom->find('.intro')[0]);
    $res['author_description'] = strip_tags($dom->find('.intro')[1]);
    $bq                        = $dom->find('#db-tags-section');
    $res['tags']               = strip_tags($bq->find('.indent'));
    if ($res['tags']) {
        $res['tags'] = str_replace(" ", "", $res['tags']);
    }

    $data_1 = cut($data, 'application/ld+json">', '</script>');
    if (!$data_1) {
        return [ ];
    }
    $data_1 = json_decode($data_1, true);
    if (!$data_1['isbn']) {
        return [ ];
    }
    $res['book_name'] = $data_1['name'];//书名
    $res['isbn']      = $data_1['isbn'];//书名
    $author           = $data_1['author'];
    if ($author[0] == '') {
        $author[0]['name'] = '未知';
    }
    $res['author']    = $author[0]['name'];//作者
    $res['photo_url'] = cut($data, 'data-pic="', '"');//图标
    $publisher        = cut($data, '出版社:</span>', '<br/>');
    if ($publisher == '') {
        $publisher = '未知';
    }
    $res['publishing'] = $publisher;//出版社
    $published         = cut($data, '出版年:</span>', '<br/>');
    if ($published == '') {
        $published = '未知';
    }
    $res['published'] = $published;//出版年
    $page             = cut($data, '页数:</span>', '<br/>');
    if ($page == '') {
        $page = '未知';
    }
    $res['pages'] = $page;//页数
    $price        = cut($data, '定价:</span>', '<br/>');
    if ($price == '') {
        $price = '未知';
    }
    $res['price'] = $price;//定价
    return $res;
}

//剪切
function cut($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1])) {
        $r = explode($end, $r[1]);

        return $r[0];
    }

    return '';
}

//模拟get请求
function get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.7 (KHTML, like Gecko) Chrome/20.0.1099.0 Safari/536.7 QQBrowser/6.14.15493.201');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function uploadsRemoteFile($fileUrl, $prefix, $key = '')
{
    $tmp_key = md5($fileUrl . time() . rand(0, 9));
    $res     = file_put_contents('/tmp/qiniu_' . $tmp_key, file_get_contents($fileUrl));
    if (!$res) {
        return false;
    }
    $key = $key ? $key : $tmp_key;

    return uploadsFile('/tmp/qiniu_' . $tmp_key, $prefix, $key);
}

function uploadsFile($filePath, $prefix, $key = '')
{
    $tmp_key = md5($filePath . time() . rand(0, 9));

    // 用于签名的公钥和私钥
    $accessKey = 'nJC3e3JjO5k-3kea7Ayc_f_lR_xuT4km837SYEkb';
    $secretKey = 'g9lxbs3zRvy2d2KNhdgheQgFBOE20FEfkmtrU6J5';
    // 初始化签权对象
    $auth    = new Auth($accessKey, $secretKey);
    $upToken = $auth->uploadToken('aiteemo');

    /*// 要上传文件的本地路径
    $filePath = '/Users/lkk/Downloads/1.png';
    // 上传到七牛后保存的文件名
    $key = 'ls_1.png';*/

    // 初始化 UploadManager 对象并进行文件的上传。
    $uploadMgr = new UploadManager();
    // 调用 UploadManager 的 putFile 方法进行文件的上传。
    $key = $key ? $key : $tmp_key;
    list($ret, $err) = $uploadMgr->putFile($upToken, $prefix . '_' . $key, $filePath);
    if ($err !== null) {
        return false;
    }

    return 'http://oss.aiteemo.com/' . $prefix . '_' . $key;
}

function getQiniuToken()
{
    // 用于签名的公钥和私钥
    $accessKey = 'nJC3e3JjO5k-3kea7Ayc_f_lR_xuT4km837SYEkb';
    $secretKey = 'g9lxbs3zRvy2d2KNhdgheQgFBOE20FEfkmtrU6J5';
    // 初始化签权对象
    $auth    = new Auth($accessKey, $secretKey);
    $upToken = $auth->uploadToken('aiteemo');
    return $upToken;
}