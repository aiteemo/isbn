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