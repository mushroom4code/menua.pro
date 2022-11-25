<?php

ini_set('memory_limit', '1G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$DATABASE = 'vita';
//`$DATABASE`.

mysqlQuery("UPDATE `$DATABASE`.`services` set servicesUUID = (SELECT uuid()) where isnull(servicesUUID);");
mysqlQuery("UPDATE `$DATABASE`.`f_sales` set f_salesUUID = (SELECT uuid()) where isnull(f_salesUUID);");

$f_subscriptions = query2array(mysqlQuery("SELECT "
				. "
`f_salesUUID`,
`servicesUUID`,
`f_salesContentPrice`,
`f_salesContentQty`,
`f_subscriptionsDate` "
				. " FROM "
				. " `$DATABASE`.`f_subscriptions`"
				. " LEFT JOIN `$DATABASE`.`f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
				. " LEFT JOIN `$DATABASE`.`services` ON (`idservices` = `f_salesContentService`)"
				. ""
				. ""));

//printr($f_subscriptions[0], 1);
//print "`" . implode("`,<br>`", array_keys($f_subscriptions[0])) . "`";
//die();
header('Content-disposition: attachment; filename=sold_services_export ' . date("y-m-d H-i-s") . '.json');
header('Content-type: application/json');

print(json_encode([
			'sales' => $f_subscriptions,
				], 288 + 128));
