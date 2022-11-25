<?php

//print "delayed message";
if (isset($argv)) {
	var_dump($argv);
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
usleep(1000);
$params = [];
foreach ($_GET as $key => $arg) {
	$_GET[$key] = urldecode($arg);
}
if ($_GET['delay'] > 0) {
	ICQ_actionSend_SYNC($_GET['delay'], 'typing');
}
usleep($_GET['delay'] * 1000);
if ($_GET['delay'] > 0) {
	ICQ_actionSend_SYNC($_GET['delay'], '');
}
ICQ_messagesSend_SYNC($_GET['peerid'], $_GET['message']);
