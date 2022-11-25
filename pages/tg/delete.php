<?php
if (isset($argv)) {
	print_r($argv);
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include ($_ROOTPATH . "/sync/includes/setupLight.php");
ignore_user_abort(true);
set_time_limit(0);


$params['token'] = ICQAPIKEY;
$params['msgId'] = $_GET['msgId'];
$params['chatId'] = $_GET['chatId'];

$url = "https://api.icq.net/bot/v1/messages/deleteMessages" . '?' . http_build_query($params);
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$json = curl_exec($curl);
//printr($json);
curl_close($curl);
$response = json_decode($json, true);



printr($response);


$messages = query2array(mysqlQuery("SELECT * FROM ICQmessagesSent where `ICQmessagesSentTime` > '2021-03-01 00:00:00' AND `ICQmessagesSentChatId`='AoLFoYsIXwfaLddCcgc';"));

foreach ($messages as $message) {
	?>
	<a href="<?= GR2(['msgId' => $message['ICQmessagesSentMsgId'], 'chatId' => $message['ICQmessagesSentChatId']]); ?>"><?= $message['ICQmessagesSentText']; ?></a><br>
	<?
}
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

