<?php
/**
 * Created by PhpStorm.
 * User: Zneiat
 * Date: 2018/7/13
 * Time: 上午 10:55
 */

error_reporting(E_ALL^E_NOTICE);
date_default_timezone_set('Asia/Shanghai');
define("APP_ROOT", __DIR__);

require APP_ROOT . '/../vendor/autoload.php';

$_config = require APP_ROOT . '/../config.php';
$_supports = require APP_ROOT . '/supports.php';

require APP_ROOT . '/libs.php';

welcome();
