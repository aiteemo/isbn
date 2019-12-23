<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/11
 * Time: 17:30
 */

namespace App;


class WechatClass
{
    public $config = array();
    public $token;
    function __construct($config=array())
    {
        if($config) {
            $this->config = $config;
            $this->token = $this->getToken();
        }
    }

    private function getToken()
    {
        $key    =   '';
        $token  =   '';
        if(!$token) {
            $accountInfo = $this->getAccountInfo($this->config['accountid']);
            $token       = $accountInfo['access_token'];
        }
        return $token;
    }

    /*
     * 获取公众号详情
     * */
    private function getAccountInfo($accountid) {

        $m      =   M();
        $redis  =   R();
        $key    = 'push_push_wechat_getAccountInfo_'.$accountid;
        $json   =   $redis->get($key);
        if(!$json or  !$accountInfo=json_decode($json,1)) {
            $accountInfo = $m->query('select appid,secret,expires_time,access_token from wx_public_account where accountid='.$accountid,'Row');
            $redis->setex($key,60,json_encode($accountInfo));
        }
        return $accountInfo;
    }

    public function multiPush($data)
    {
        // 最终推送内容
        $push_all_data = array();

        // 数据格式化
        foreach ($data as $data_v) {
            $push_data = array();
            $this->config['accountid']      =   $data_v['accountid'];
            $push_data['data']              =   $this->disposePushData($data_v);
            $push_data['curl_url']          =   PUSH_RECEIVE_URL .'?token='. $this->getToken();
            $push_all_data[$data_v['id']]   =   $push_data;
        }

        //推送
        return multiHttpd($push_all_data);
    }

    /*
     * 推送字段组装
     * */
    private function disposePushData($data) {

        $data_tmp  = array(
            'touser'       => $data['openid'],
            'template_id'  => $data['template_id'],
        );

        /*
         * 推送小程序链接
         * miniprogram'  => array(
         * 'appid'    => $data['appid'],
         * 'pagepath' => $data['pagepath'],
         * ),
         * */
        if($data['miniprogram']) {
            $data_tmp['miniprogram'] = $data['miniprogram'];
        }

        if($data['url']) {
            $data_tmp['url'] = $data['url'];
        }

        $data_tmp_data = array();

        /*
         * 头部字段，下面字段同
         * first'  => array(
         * 'value'    => VALUE,
         * 'color'    => COLOR,
         * ),
         * */
        if($data['first']) {
            $data_tmp['data']['first']      =   $data['first'];
        }
        if($data['keyword1']) {
            $data_tmp['data']['keyword1']   =   $data['keyword1'];
        }
        if($data['keyword2']) {
            $data_tmp['data']['keyword2']   =   $data['keyword2'];
        }
        if($data['keyword3']) {
            $data_tmp['data']['keyword3']   =   $data['keyword3'];
        }
        if($data['keyword4']) {
            $data_tmp['data']['keyword4']   =   $data['keyword4'];
        }
        if($data['keyword5']) {
            $data_tmp['data']['keyword5']   =   $data['keyword5'];
        }
        if($data['remark']) {
            $data_tmp['data']['remark']     =   $data['remark'];
        }

        return $data_tmp;
    }
    public function push($data)
    {

        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $this->getToken;

        //数据格式化
        $data = array(
            'touser'       => $data['openid'],
            'template_id'  => $data['template_id'],
            'miniprogram'  => array(
                'appid'    => $data['appid'],
                'pagepath' => $data['pagepath'],
            ),
            'data' => array(
                'first' => array(
                    'value' => $data['value1'],
                    'color' => $data['color1'],
                ),
                'keyword1' => array(
                    'value' => $data['value2'],
                    'color' => $data['color2'],
                ),
                'keyword2' => array(
                    'value' => $data['value3'],
                    'color' => $data['color3'],
                ),
                'keyword3' => array(
                    'value' => $data['value4'],
                    'color' => $data['color4'],
                ),
                'keyword4' => array(
                    'value' => $data['value5'],
                    'color' => $data['color5'],
                ),
                'remark' => array(
                    'value' => $data['value6'],
                    'color' => $data['color6'],
                )
            )
        );
        
        return $data;

        //selfPrint($data,1);

        //推送
        return http_post($url,$data);
    }
}