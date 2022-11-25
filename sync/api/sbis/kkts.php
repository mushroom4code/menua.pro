<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
//https://api.sbis.ru/ofd/v1/orgs/7810730632/kkts

$identity = 1;



printr(getKKTS(1), 1);
?>
<?= date("Y-m-d H:i:s", filemtime(__FILE__)); ?>