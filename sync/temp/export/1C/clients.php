<?php

ini_set('memory_limit', '1G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

mysqlQuery("UPDATE vita.clients set clientsUUID = (SELECT uuid()) where isnull(clientsUUID);");

$DATABASE = 'vita';
//`$DATABASE`.
$sales = query2array(mysqlQuery("SELECT "
				. "clientsUUID, clientsLName, clientsFName, clientsMName, clientsBDay, clientsAKNum, clientsAddedAt, clientsGender, clientsSource, clientsOldSince, clientsTIN, clientsContractDate"
				. " FROM "
				. " `$DATABASE`.`clients` "
				. ""));

//idclients, GUID, 




header('Content-disposition: attachment; filename=clients_export ' . date("y-m-d H-i-s") . '.json');
header('Content-type: application/json');

print(json_encode([
			'sales' => $sales,
				], 288 + 128));
