<?php

ini_set('memory_limit', '1G');
header("Content-type: text/plain; charset=utf8");
//header("Content-Type: ");
//header('Content-type: plain/text');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$services = query2array(mysqlQuery("SELECT *,"
				. " (SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(idservicesPrices) FROM `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`=1)) as `price`"
				. " FROM `vita`.`services`"
				. "LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"));

foreach ($services as $service) {
	print
			$service['idservices'] . "\t"
			. $service['servicesName'] . "\t"
			. ($service['serviceNameShort'] ?? $service['servicesName']) . "\t"
			. ($service['servicesTypesName'] ?? '') . "\t"
			. ($service['price'] ?? '') . "\t"
			. "процедура\t"
			. (secondsToTimeShort($service['servicesDuration'] * 60) ) . "\t"
			. "нет\t"
			. "\t"
			. (($service['servicesVat'] ?? 'не указан') ? ($service['servicesVat'] ?? 'не указан') : "НДС не облагается" ) . "\t"
			. "\n";
}