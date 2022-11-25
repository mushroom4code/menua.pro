<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setup.php';
//ICQ_messagesSend_SYNC('AoLF0rcsY9MXT89Io2U', ($_USER['lname'] ?? 'NOLNAME') . ' ' . ($_USER['fname']) . "\r\nJavaScript Error\r\n" . print_r($_JSON, true));
sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => ($_USER['lname'] ?? 'NOLNAME') . ' ' . ($_USER['fname']) . "\r\n" . date("H:i:s") . "\r\n" . "JavaScript Error\r\n" . print_r($_JSON, true)]);
