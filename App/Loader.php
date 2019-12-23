<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/2
 * Time: 12:22
 */
namespace Imooc;

class Loader{
    static function autoload($class){
        require BASEDIR.'/'.str_replace('\\','/',$class).'.php';
    }
}