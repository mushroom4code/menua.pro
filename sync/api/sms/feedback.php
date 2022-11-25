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
$uid = preg_replace("/message-id-/", '', $_GET['id'] ?? '');

//$_JSON:{"result":"ok"}$_GET: {"id":"message-id-mw4ZQfV2JTFc","parts":"2","status":"delivered"}
//$id = $_GET['id']
//ICQ_messagesSend_SYNC('sashnone', 'СМС ФИДБЕК ' . json_encode($_GET, 288 + 128));
$phone = mfa(mysqlQuery("SELECT `smsPhone`, `clientsPhonesPhone` FROM `sms` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `smsPhone`)  WHERE `smsHash` = '" . mres($uid) . "'"));

//ICQ_messagesSend_SYNC('sashnone', '$phone' . json_encode($phone, 288 + 128));

if ($phone && ($_GET['status'] ?? '') == 'delivered') {
	mysqlQuery("UPDATE `sms` SET `smsDelivered` = NOW() WHERE `smsHash` = '" . mysqli_real_escape_string($link, $uid) . "'");
	mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesSmsSuccess` = `clientsPhonesSmsSuccess`+1 WHERE `idclientsPhones` = '" . $phone['smsPhone'] . "'");
}

 
if ($phone && in_array(($_GET['status'] ?? ''), ['', 'insufficient_balance', 'failed', 'rejected'])) {
	$message = '🆘 (' . $uid . ') Ошибка отправки SMS на номер ' . $phone['clientsPhonesPhone'] . ', причина: ' . (['' => 'неизвестная ошибка', 'insufficient_balance' => 'закончился пакет SMS', 'failed' => 'невозможно доставить', 'rejected' => 'Отклонено провайдером!'][$_GET['status']]) . '.';
	if ($_USER['usersTG'] ?? false) {
		sendTelegram('sendMessage', ['chat_id' => $_USER['usersTG'], 'text' => $message]);
	}
	foreach (getUsersByRights([132]) as $user) {
		if ($user['usersTG'] ?? false) {
			sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => $message]);
		}
	}
}
if ($phone) {
	mysqlQuery("UPDATE `sms` SET `smsState` = '" . $_GET['status'] . "' WHERE `smsHash` = '" . mysqli_real_escape_string($link, $uid) . "'");
}

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */