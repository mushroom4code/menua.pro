<?php

ini_set('memory_limit', '1G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$DATABASE = 'vita';
//`$DATABASE`.

mysqlQuery("UPDATE `$DATABASE`.`services` set servicesUUID = (SELECT uuid()) where isnull(servicesUUID);");

$services = query2array(mysqlQuery("SELECT servicesUUID, servicesTypesName, servicesName, serviceNameShort, servicesType, servicesDeleted, servicesDuration, servicesAdded, servicesVat"
				. " FROM "
				. " `$DATABASE`.`services` LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"
				. ""));

//idclients, GUID, 




header('Content-disposition: attachment; filename=services_export ' . date("y-m-d H-i-s") . '.json');
header('Content-type: application/json');

print(json_encode([
			'sales' => $services,
				], 288 + 128));
