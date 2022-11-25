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

print $_ROOTPATH;

var_dump($CDR_link); 
print "\r\nfunction_exists('sendTelegram'):" . function_exists('sendTelegram');
