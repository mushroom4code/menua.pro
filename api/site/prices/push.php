<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

include 'functions.php';
//header("Content-type: application/json; charset=utf8");

$services = query2array(mysqlQuery("SELECT `idservices`,`servicesName`, (SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`)) as `price`"
				. " FROM `services`"
				. " WHERE isnull(`servicesDeleted`);"));

$OUT['services'] = $services;

//exit(json_encode($OUT, 288));

$key = 'h47roy4r7hr87ry4ry7y7yosfuffuy';
$url = 'https://infiniti-clinic.ru/in.php';
// 
$data['key'] = $key;
$data['data']['services'] = $services;

$payload = json_encode($data, 288);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

print 'Ответ сервера: ' . $result . 'Конец ответа сервера.';
