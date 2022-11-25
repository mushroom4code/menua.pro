<?php

//Отображение ошибок
if (1) {
	error_reporting(E_ALL & ~E_NOTICE); //
	ini_set('display_errors', 1);
}
//Настройки сессии и кодировки баз данных
mb_internal_encoding("UTF-8");

//session_set_cookie_params((60 * 60 * 24 * 7));
//ini_set('session.gc_maxlifetime', (60 * 60 * 24 * 7));
//ini_set('session.cookie_lifetime', (60 * 60 * 24 * 7));
//ini_set('session.cache_expire', (60 * 60 * 24 * 7));
//$redis = new Redis();
//$redis->connect('127.0.0.1', 6379);
//Подключение функций
//define('ROOT', $_SERVER['DOCUMENT_ROOT']);
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
require_once $_ROOTPATH . '/constants.php';
require_once $_ROOTPATH . '/sync/includes/constants.php';
require_once $_ROOTPATH . '/sync/includes/functions.php';

//printr($_SESSION);
$_LOG = array();
//Подключение к БД
//
if (1) {//ПОСТАВИТЬ НОЛЬ ЕСЛИ ОТВАЛИВАЕТСЯ АЙПИ ТЕЛЕФОНИЯ
	$CDR_link = mysqli_connect('192.168.128.100', 'cdruser', '0lwddbjSLgRyXvpN', 'asterisk');
} else {
	$CDR_link = null;
}



//$CDR_link = mysqli_init();
//mysqli_options($CDR_link, MYSQLI_OPT_CONNECT_TIMEOUT, 1);
//
//if (!mysqli_real_connect($CDR_link, '192.168.128.100', 'cdruser', '0lwddbjSLgRyXvpN', 'asterisk')) {
//	//-522070992
//
//}



$_DB = array('dbUser' => 'root', 'dbName' => DBNAME, 'dbPass' => 'yflt;ysqgfhjkm');
$link = mysqli_connect('localhost', $_DB['dbUser'], $_DB['dbPass'], $_DB['dbName']);
mysqli_query($link, "SET character_set_client = utf8mb4");
mysqli_query($link, "SET character_set_server = utf8mb4");
mysqli_query($link, "SET collation_connection=utf8mb4_unicode_ci");
mysqli_query($link, "SET character_set_results = utf8mb4");

//Конвертация $rawPost в $_POST в случае поступления данных из фреймворка
if ($rawPost = file_get_contents('php://input')) {
	$_RP = json_decode($rawPost, true) ? json_decode($rawPost, true) : $_POST;
	foreach ($_RP as $key => $value) {
		$_JSON[$key] = $value;
	}
}

$returnGet = $_GET;

include_once $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/login.php';
