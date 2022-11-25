<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");
include $_ROOTPATH . '/sync/includes/setupLight.php';

ICQ_messagesSend_SYNC('sashnone', '☎️' . "\r\n" . json_encode($_POST, 288));
sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => '☎️' . "\r\n" . json_encode($_POST, 288)]);
print json_encode($_POST, 288);
