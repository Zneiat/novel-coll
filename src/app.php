<?php
/**
 * Created by PhpStorm.
 * User: Zneiat
 * Date: 2018/7/13
 * Time: 上午 10:55
 */

error_reporting(E_ALL^E_NOTICE);
date_default_timezone_set('Asia/Shanghai');

define('APP_NAME', 'QwQ Coll');
define('APP_ROOT', __DIR__);
define('APP_CONF', require APP_ROOT . '/../config.php');
define('APP_ACTION_MAP', require APP_ROOT . '/action-map.php');

require APP_ROOT . '/vendor/autoload.php';

top:
gotoAction();
passthru('pause');
clearScreen();
goto top;
