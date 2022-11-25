<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

include 'functions.php';
header("Content-type: application/json; charset=utf8");
sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => json_encode(($_JSON ?? "NO CONTENT"), 288 + 128)]);
$OUT = ['success' => true, 'currenttime' => date("Y-m-d H:i:s")];
if (getBearerToken() !== '7PgvB1xnpBF7TjiV8Jup2Cjn') {
	header("HTTP/1.1 401 Unauthorized");
	die(json_encode(['success' => false, 'error' => ['id' => '401', 'text' => 'Unauthorized'], 'currenttime' => date("Y-m-d H:i:s")]));
}

if (!($_JSON ?? false)) {
	ob_start();
	header("HTTP/1.1 204 NO CONTENT");
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
	header("Pragma: no-cache"); // HTTP 1.0.
	header("Expires: 0"); // Proxies.
	ob_end_flush(); //now the headers are sent
	die(json_encode(['success' => false, 'error' => ['id' => '204', 'text' => 'no content'], 'currenttime' => date("Y-m-d H:i:s")]));
}

$services = query2array(mysqlQuery("SELECT `idservices`,`servicesName`,"
				. "ifnull((SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<=NOW() AND `servicesPricesType`='2' AND `servicesPricesService` = `idservices`)),(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`))) as `price` FROM `services` WHERE `servicesURL`='" . mres($_JSON['url']) . "';"));
$OUT['url'] = $_JSON['url'];
$OUT['services'] = $services;

exit(json_encode($OUT, 288));
