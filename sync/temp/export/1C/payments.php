<?php

ini_set('memory_limit', '3G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$DATABASE = 'vita';
//`$DATABASE`.
//mysqlQuery("UPDATE `$DATABASE`.`services` set servicesUUID = (SELECT uuid()) where isnull(servicesUUID);");
mysqlQuery("UPDATE `$DATABASE`.`f_sales` set f_salesUUID = (SELECT uuid()) where isnull(f_salesUUID);");
mysqlQuery("UPDATE `$DATABASE`.`users` set usersUUID = (SELECT uuid()) where isnull(usersUUID);");
//mysqlQuery("UPDATE `$DATABASE`.`clients` set clientsUUID = (SELECT uuid()) where isnull(clientsUUID);");

$output = query2array(mysqlQuery("SELECT "
				. "(SELECT `f_salesUUID` FROM `$DATABASE`.`f_sales` WHERE `idf_sales` = `f_paymentsSalesID`) as `f_paymentsSalesID`,"
				. " (SELECT `f_paymentsTypesName` FROM `f_paymentsTypes` WHERE `idf_paymentsTypes` = `f_paymentsType`)`f_paymentsType`,"
				. "`f_paymentsAmount`,"
				. "`f_paymentsDate`,"
				. "(SELECT `usersUUID` FROM `$DATABASE`.`users` WHERE `idusers` = `f_paymentsUser`) as `f_paymentsUser`,"
				. "`f_paymentsComment`"
				. " FROM "
				. " `$DATABASE`.`f_payments`"
				. ""));
if (0) {
	printr($output[0], 1);
	print ". \"`" . implode("`,\"<br>. \"`", array_keys($output[0])) . "`\"";
	die();
}
//idservicesApplied, , servicesAppliedQty, servicesAppliedClient, servicesAppliedBy, servicesAppliedPersonal, servicesAppliedDate, servicesAppliedIsFree2, servicesAppliedIsNew, servicesAppliedAt, servicesAppliedTimeBegin, servicesAppliedStarted, servicesAppliedStartedBy, servicesAppliedTimeEnd, servicesAppliedFineshed, servicesAppliedFinishedBy, , servicesAppliedSubscription, servicesAppliedPrice, servicesAppliedDeleted, servicesAppliedDeletedBy, servicesAppliedDeleteReason, servicesAppliedLocked, servicesAppliedByReal, servicesAppliedIsDiagnostic, servicesAppliedAdvancePayment
header('Content-disposition: attachment; filename=payments_export ' . date("y-m-d H-i-s") . '.json');
header('Content-type: application/json');

print(json_encode([
			'sales' => $output,
				], 288 + 128));
