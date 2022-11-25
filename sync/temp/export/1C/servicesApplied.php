<?php

ini_set('memory_limit', '3G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$DATABASE = 'vita';
//`$DATABASE`.

mysqlQuery("UPDATE `$DATABASE`.`services` set servicesUUID = (SELECT uuid()) where isnull(servicesUUID);");
mysqlQuery("UPDATE `$DATABASE`.`f_sales` set f_salesUUID = (SELECT uuid()) where isnull(f_salesUUID);");
mysqlQuery("UPDATE `$DATABASE`.`users` set usersUUID = (SELECT uuid()) where isnull(usersUUID);");
mysqlQuery("UPDATE `$DATABASE`.`clients` set clientsUUID = (SELECT uuid()) where isnull(clientsUUID);");

$output = query2array(mysqlQuery("SELECT "
				. "`f_salesUUID`,"
				. "`servicesUUID`,"
				. "(SELECT `clientsUUID` FROM `$DATABASE`.`clients` WHERE `idclients` = `servicesAppliedClient`) as `servicesAppliedClient`,"
				. "(SELECT `usersUUID` FROM `$DATABASE`.`users` WHERE `idusers` = `servicesAppliedBy`) as `servicesAppliedBy`,"
				. "(SELECT `usersUUID` FROM `$DATABASE`.`users` WHERE `idusers` = `servicesAppliedPersonal`) as `servicesAppliedPersonnel`,"
				. "`servicesAppliedDate`,"
				. "`servicesAppliedAt`,"
				. "`servicesAppliedTimeBegin`,"
				. "`servicesAppliedStarted`,"
				. "`servicesAppliedTimeEnd`,"
				. "`servicesAppliedFineshed`,"
				. "`servicesAppliedPrice`,"
				. "`servicesAppliedQty`,"
				. "`servicesAppliedDeleted`"
				. " FROM "
				. " `$DATABASE`.`servicesApplied`"
				. " LEFT JOIN `$DATABASE`.`f_sales` ON (`idf_sales` = `servicesAppliedContract`)"
				. " LEFT JOIN `$DATABASE`.`services` ON (`idservices` = `servicesAppliedService`)"
				. ""
				. ""));
if (0) {
	printr($output[0], 1);
	print ". \"`" . implode("`,\"<br>. \"`", array_keys($output[0])) . "`\"";
	die();
}
//idservicesApplied, , servicesAppliedQty, servicesAppliedClient, servicesAppliedBy, servicesAppliedPersonal, servicesAppliedDate, servicesAppliedIsFree2, servicesAppliedIsNew, servicesAppliedAt, servicesAppliedTimeBegin, servicesAppliedStarted, servicesAppliedStartedBy, servicesAppliedTimeEnd, servicesAppliedFineshed, servicesAppliedFinishedBy, , servicesAppliedSubscription, servicesAppliedPrice, servicesAppliedDeleted, servicesAppliedDeletedBy, servicesAppliedDeleteReason, servicesAppliedLocked, servicesAppliedByReal, servicesAppliedIsDiagnostic, servicesAppliedAdvancePayment
header('Content-disposition: attachment; filename=applied_services_export ' . date("y-m-d H-i-s") . '.json');
header('Content-type: application/json');

print(json_encode([
			'sales' => $output,
				], 288 + 128));
