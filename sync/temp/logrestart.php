<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {

	die("\$_ROOTPATH is undefined");
}
include $_ROOTPATH . '/sync/includes/setupLight.php';

$start = microtime(1);
$lastSend = 0;
$filename = '/var/www/html/public/logs/PGT-access.log';
$lastFilesize = 0;
sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'start loggong']);
while (1) {
	clearstatcache();
	$filesize = filesize($filename);

	if (1) {
		$handle = fopen($filename, "rb");
		$rows = file($filename);
		fclose($handle);
		while (!($handle = fopen($filename, "wb"))) {
			usleep(10000);
		}
		fwrite($handle, '');
		fclose($handle);
		$stop = false;
		foreach ($rows as $row) {
			$row = explode("\t", $row);

			if ($row[3] === '-') {
				sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => '❗️' . (json_encode($row))]);
				$stop = true;
			}
		}
		if ($stop ?? false) {
			usleep(30000);
			sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'RESTART']);
//			exec("sudo systemctl restart php8.0-fpm.service");
		}
	}

	usleep(100000);
}
sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'exit']);
