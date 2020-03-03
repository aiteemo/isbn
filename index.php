<?php
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('PRC');
}
define('BASEDIR', __DIR__);
define('DEBUG', true);
if (file_exists('/tmp/teemo/push.conf.lock')) {
    include_once BASEDIR . '/App/Config.php';
} else {
    include_once BASEDIR . '/App/ConfigTest.php';
}
include BASEDIR . '/App/Loader.php';
include BASEDIR . '/App/Common.php';
spl_autoload_register('\\Imooc\\Loader::autoload');
require_once __DIR__ . '/vendor/autoload.php';

$m = M();

$err = '';
if ($_GET && isset($_GET['isbn'])) {
    // 是否存在
    $search = $m->query("select * from isbn WHERE state=1 AND isbn='" . $_GET['isbn'] . "'");
    $arr    = $search ? $search[0] : [ ];

    // 不存在则查询
    if (!$arr) {
        // 查询豆瓣
        $arr = getISBNfromDouban($_GET['isbn']);
        if (!$arr) {
            $arr = getISBN($_GET['isbn']);
        }
        if ($arr && isset($arr['isbn'])) {
            if ($photo_url = uploadsRemoteFile($arr['photo_url'], 'isbn_photo_url')) {
                $arr['photo_url'] = $photo_url;
            }
            $m->insert("isbn", $arr);
        }
    }
    $arr['tags'] = $arr['tags'] ? array_filter(explode("&nbsp;", $arr['tags'])) : '';
    if (isset($_GET['api']) && $_GET['api'] == 'true') {
        echo json_encode($arr);
        die;
    }
    if (!$arr or !isset($arr['isbn'])) {
        $err = "暂未找到!可通过提交ISBN告诉我们,以更好的完善信息库";
    } else {
        session_start();
        $_SESSION['info'] = $arr;
        header("Location:info.php");
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>AiTeemo ISBN</title>
    <meta name="keywords" content="isbn,isbn查询 isbn api,开源isbn">
    <meta name="description" content="致力于提供开源的ISBN查询服务"/>
</head>
<body>
<div class="container">
    <div class="py-5 text-center">
        <!--<img class="d-block mx-auto mb-4" src="https://getbootstrap.com/assets/brand/bootstrap-solid.svg" alt=""
             width="72" height="72">-->
        <h2>AiTeemo ISBN</h2>
        <br/>
        <p class="lead">成立这个项目,希望提供开源的ISBN查询服务,也希望得到更多的人帮忙完善ISBN信息</p>
    </div>

    <div class="row">
        <div class="col-md-12 order-md-1">
            <h4 class="mb-3">ISBN 图书编码查询</h4>
            <form class="needs-validation" method="get">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <input type="text" class="form-control" id="ISBN" placeholder="<?php echo $err; ?>" value=""
                               name="isbn" required>
                        <div class="invalid-feedback">
                            Valid ISBN is required.
                        </div>
                    </div>
                </div>
                <hr class="mb-4">
                <button class="btn btn-primary btn-lg btn-block" type="submit">查询</button>
            </form>
        </div>
    </div>
    <?php require "footer.php"; ?>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.slim.min.js"
            integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
            crossorigin="anonymous"></script>
    <script src="https://cdn.bootcss.com/popper.js/1.12.9/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
            crossorigin="anonymous"></script>
    <script src="https://cdn.bootcss.com/bootstrap/4.0.0/js/bootstrap.min.js"
            integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
            crossorigin="anonymous"></script>
</body>
</html>