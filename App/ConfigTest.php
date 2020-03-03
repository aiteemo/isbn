<?php
/**
 * Created by PhpStorm.
 * User: lkk
 * Date: 2018/11/12
 * Time: 6:22 PM
 */
const DB_HOST = '49.234.37.188';
const DB_PORT = '3306';
const DB_NAME = 'isbn';
const DB_USER = 'teemoisbn';
const DB_PASS = 'teemobabyisbn201923081039';
const DB_CHAR = 'utf8mb4';

const REDIS_HOST       = '127.0.0.1';
const REDIS_PORT       = '6379';
const PUSH_KEY         = 'push_pushlist_isbn';
const PUSH_RECEIVE_URL = 'http://123.206.67.116:20011/receive.php';

if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 'off');
}