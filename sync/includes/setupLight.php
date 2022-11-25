<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
require_once $_ROOTPATH . '/constants.php';
require_once $_ROOTPATH . '/sync/includes/functions.php';
require_once $_ROOTPATH . '/sync/includes/constants.php';
 
//Подключение к БД
$_DB = array('dbUser' => 'root', 'dbName' => DBNAME, 'dbPass' => 'yflt;ysqgfhjkm');
$link = mysqli_connect('localhost', $_DB['dbUser'], $_DB['dbPass'], $_DB['dbName']);
//mysqli_query($link, "SET character_set_client = utf8");
//mysqli_query($link, "SET collation_connection=utf8_general_ci");
//mysqli_query($link, "SET character_set_results = utf8");

mysqli_query($link, "SET character_set_client = utf8mb4");
mysqli_query($link, "SET character_set_server = utf8mb4");
mysqli_query($link, "SET collation_connection=utf8mb4_unicode_ci");
mysqli_query($link, "SET character_set_results = utf8mb4");

//$redis = new Redis();
//$redis->connect('127.0.0.1', 6379);
//Конвертация $rawPost в $_POST в случае поступления данных из фреймворка
if ($rawPost = file_get_contents('php://input')) {
	$_RP = json_decode($rawPost, true) ? json_decode($rawPost, true) : $_POST;
	foreach ($_RP as $key => $value) {
		$_JSON[$key] = $value;
	}
}

