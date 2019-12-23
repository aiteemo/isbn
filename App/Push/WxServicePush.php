<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/11
 * Time: 17:28
 */
namespace App\Push;

use App\WechatClass;
use App\Common;

class WxServicePush
{
    public $push_limit = 50;

    function push($list)
    {
        if(!$list)     {
            return;
        }

        // 数据格式化
        foreach ($list as $list_k=>$list_v) {
            $list[$list_k]                  +=  json_decode($list_v['push_data'],1);
            $list[$list_k]['template_id']   =   $list[$list_k]['wechat_template_id'];
        }

        // 推送
        $wechat     =   new WechatClass();
        $res        =   $wechat->multiPush($list);


        // 推送结果处理
        $this->disposePushResult($res,$list);


        return $res;
    }

    /*
     * 推送中
     * */
    public function pushing($list) {

        if(!$list) return;
        $where = 'id in ('.implode(',',$list).')';
        return M()->update( 'wx_public_account_push_list_new', array('is_push'=>1,'push_time'=>time()), $where );
    }

    /*
     * 记录全部推送完成逻辑
     * */
    public function pushFinish() {

        $un_finish  =   $this->getPush('is_push=1');
        if(!$un_finish) return;

        $changeId   =   array();
        foreach ($un_finish as $k=>$v) {
            $res    =   $this->getPushList('push_id='.$v['id'].' and is_push in(0,1)',1);
            if(!$res) $changeId[] = $v['id'];
        }
        if($changeId) $this->updatePush( array('is_push'=>2,'finish_time'=>time()), $where='id in ('.implode(',',$changeId).')' );
    }

    /*
     * 推送失败
     * */
    public function pushFail() {

        $list = $this->getPushList('is_push=1 and push_time<'.(time()-180),$size=100000,$order='id asc',$fields='id');
        if($list) {
            foreach ($list as $k=>$v) {
                $changeId[] = $v['id'];
            }
            if($changeId) $this->updatePushList( array('is_push'=>0), $where='id in ('.implode(',',$changeId).')' );
        }
    }

    /*处理发送完成的逻辑*/
    private function disposePushResult($res=array(),$list) {

        $fail_ary = $err_ary = $success_ary = $quit_ary = $del_ary = array();

        if(!$res) {
            foreach ($list as $l_v) $fail_ary[] = $l_v['id'];
        } else {
            foreach ($res as $k=>$v) {
                $tmp = json_decode($v, 1);

                if(!isset($tmp['errcode'])) {
                    $fail_ary[] = $k;
                    continue;
                }

                if ($tmp['errcode'] == 0) {
                    $success_ary[] = $k;
                } elseif ($tmp['errcode'] == '43004') {//取消推送：43004需要接收者关注/
                    $quit_ary[] = $k;
                } elseif ($tmp['errcode'] == '40003' or $tmp['errcode'] == '44002') {
                    $del_ary[] = $k;
                } elseif ($tmp['errcode'] == '40001') {// token过期
                    $fail_ary[] = $k;
                }else{
                    $err_ary[] = $k;
                }
            }
        }

        $m      =   M();
        $table  =   'wx_public_account_push_list_new';

        // 如果有错则修改状态记录
        if($err_ary) {
            $where = 'id in ('.implode(',',$err_ary).')';
            $m->update( $table, array('is_push'=>5), $where );
        }
        // 失败则重新发送
        if($fail_ary) {
            // 批量添加
            R()->lPushList(PUSH_KEY,$fail_ary);
            //$where = 'id in ('.implode(',',$fail_ary).')';
            //$m->update( $table, array('is_push'=>0), $where );
        }
        // 推送成功修改状态//是否推送 0未推送 1推送中 2.已完成  3.取消推送4.已删除
        if($success_ary) {
            $where = 'id in ('.implode(',',$success_ary).')';
            $m->update( $table, array('is_push'=>2), $where );
        }
        // 取消推送：43004需要接收者关注/
        if($quit_ary) {
            // 更改发送状态
            $where = 'id in ('.implode(',',$quit_ary).')';
            $m->update( $table, array('is_push'=>3), $where );
            // 更改用户订阅状态
            //$m->update( "wx_public_account_fans",array('subscribe'=>0),'openid=');
        }
        // 删除推送：40003	不合法的 OpenID
        if($del_ary) {
            $where = 'id in ('.implode(',',$del_ary).')';
            $m->update( $table, array('is_push'=>4), $where );
        }

        if(!$success_ary) {
            echo date('Y-m-d H:i:s')."SEND FAIL...\n";
            echo "SLEEP...3\n";
            sleep(1);
            echo "SLEEP...2\n";
            sleep(1);
            echo "SLEEP...1\n";
            echo "RESTART...\n";
            sleep(1);
        }
    }


    /*
     * 获取待发送列表
     * */
    public function getUnPushList() {

        // 获取待发送或者发送中的任务
        $redis = R();
        $idary = $redis->rPopList(PUSH_KEY,$this->push_limit);

        if(!$idary) return;

        // 获取发送列表
        $list = $this->getPushList('id in('.implode(',',$idary).') and is_push=1 and push_time<'.time(),$this->push_limit);
        return $list;
    }

    /*
     * 获取待发送列表
     * */
    public function getUnPushListIdAry($size=150000) {

        // 获取待发送或者发送中的任务

        // 获取发送列表
        $list = $this->getPushList('is_push=0 and push_time<'.time(),$size,$order='id asc',$fields='id');
        return $list;
    }


    /*
     * 获取总发送列表
     * */
    private function getPush($where='',$size='',$order='id asc') {

        $m               =  M();
        $sql             =  'select * from wx_public_account_push';
        if($where)  $sql =  $sql.' WHERE '.$where;
        if($order)  $sql =  $sql.' ORDER BY '.$order;
        if($size)   $sql =  $sql.' LIMIT '.$size;
        $list            = $m->query($sql);
        return $list;
    }

    /*
     * 获取发送列表
     * */
    private function getPushList($where='',$size='',$order='id asc',$fields='') {

        $m               =  M();
        $fields          =  $fields ? : 'id,openid,accountid,push_id,is_push,push_data';
        $sql             =  'select '.$fields.' from wx_public_account_push_list_new';
        if($where)  $sql =  $sql.' WHERE '.$where;
        if($order)  $sql =  $sql.' ORDER BY '.$order;
        if($size)   $sql =  $sql.' LIMIT '.$size;
        $list            = $m->query($sql.' for update');
        return $list;
    }

    /*
     * 更改发送列表状态
     * */
    function updatePushList($data,$where) {

        if(!$data or !$where) {
            return;
        }
        $res = M()->update('wx_public_account_push_list_new', $data, $where, $debug = false);
        return $res;
    }

    /*
     * 更改发送总状态
     * */
    function updatePush($data,$where) {

        if(!$data or !$where) {
            return;
        }
        $res = M()->update('wx_public_account_push', $data, $where, $debug = false);
        return $res;
    }

}