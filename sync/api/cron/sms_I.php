<?php

//В файлах выполняемых на сервере не должно быть разврывов типа <?
//print date("H:i:s");
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {

	die("\$_ROOTPATH is undefined");
}
//
include $_ROOTPATH . '/sync/includes/setupLight.php';
error_reporting(E_ALL); // выводим все ошибки и предупреждения

$date = date("Y-m-d");

$clientsSQL = "SELECT * FROM `clientsVisits` LEFT JOIN `clients` ON (`idclients`=`clientsVisitsClient`) WHERE `clientsOldSince`=`clientsVisitsDate` AND `clientsVisitsDate`='" . $date . "'";// AND `idclients`=112
$clients = query2array(mysqlQuery($clientsSQL));

foreach ($clients as $n => $client) {
	$clients[$n]['phones'] = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`)"));

	if ($clients[$n]['phones'] ?? false) {
		$smsText = smsTemplate("Здравствуйте, #clientsFName #clientsMName! Недавно Вы посетили медицинский центр «Инфинити». Мы будем очень признательны, если Вы поделитесь своим мнением о работе нашего медцентра. Оцените нас, пожалуйста, в этой форме: https://reviewss.me/infiniti-clinic Спасибо, что помогаете нам становиться лучше!",
				[
					'clientsFName' => $client['clientsFName'],
					'clientsMName' => $client['clientsMName']
		]);
		foreach ($clients[$n]['phones'] as $phone) {

			$sendResult = sendSms(($phone['clientsPhonesPhone'] ?? null), $smsText);

			$success = (($sendResult['status'] ?? '') === 'ok');
			if ($success) {
				$uid = preg_replace("/message-id-/", '', $sendResult['result']['uid']);
				mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesSmsTotal` = `clientsPhonesSmsTotal`+1 WHERE `idclientsPhones` = '" . $phone['idclientsPhones'] . "'");
				mysqlQuery("INSERT INTO `sms` SET "
						. "`smsHash` = '" . $uid . "', "
						. "`smsClient` = '" . $client['idclients'] . "', "
						. "`smsText` = '" . mres($smsText) . "', "
						. "`smsPhone` = '" . $phone['idclientsPhones'] . "'");
			} else {
				print 'Не удалось отправить SMS idphones=' . $phone['idclientsPhones'];
			}
		}
	}
}
