<?php

$pageTitle = '1';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$CDR_link = mysqli_init();
if (!$CDR_link) {
	die('mysqli_init failed');
}

if (!mysqli_options($CDR_link, MYSQLI_OPT_CONNECT_TIMEOUT, 1)) {
	die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
}

if (!mysqli_real_connect($CDR_link, '192.168.128.100', 'cdruser', '0lwddbjSLgRyXvpN', 'asterisk')) {
	print ('not connected');
	$CDR_link = null;
}

$calls = query2array(mysqlQuery("select * from `asterisk`.`cdr` WHERE  `calldate` BETWEEN '2021-04-14 11:00:00' AND '2021-09-14 13:00:00' 
and (`dst` like '%21362361%' OR `src` like '%21362361%')
 order by id desc;", $CDR_link));

printr($calls);
foreach ($calls as $call) {

	$ch = curl_init('http://192.168.128.100/ivr_stat/audio/' . $call['uniqueid'] . '.mp3');
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// $retcode >= 400 -> not found, $retcode = 200, found.
	curl_close($ch);

	if ($retcode == 200) {
		print '<a target="_blank" href="http://192.168.128.100/ivr_stat/audio/' . $call['uniqueid'] . '.mp3' . '">' . 'http://192.168.128.100/ivr_stat/audio/' . $call['uniqueid'] . '.mp3' . '</a><br>';
	} else {
		print 'not a file: ' . 'http://192.168.128.100/ivr_stat/audio/' . $call['uniqueid'] . '.mp3' . '<br>';
	}
}
?>


<?

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
