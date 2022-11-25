<?php

ini_set('memory_limit', '3G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$DATABASE = 'vita';
//`$DATABASE`.
//mysqlQuery("UPDATE `$DATABASE`.`services` set servicesUUID = (SELECT uuid()) where isnull(servicesUUID);");
mysqlQuery("UPDATE `$DATABASE`.`f_sales` set f_salesUUID = (SELECT uuid()) where isnull(f_salesUUID);");
//mysqlQuery("UPDATE `$DATABASE`.`users` set usersUUID = (SELECT uuid()) where isnull(usersUUID);");
mysqlQuery("UPDATE `$DATABASE`.`RS_banks` set `RS_banksUUID` = (SELECT uuid()) where isnull(RS_banksUUID);");

$output = query2array(mysqlQuery("SELECT "
				. "`f_creditsBankAgreementNumber`,"
				. "`f_creditsSumm`,"
				. "`f_creditsMonthes`,"
				. "(SELECT `f_salesUUID` FROM `$DATABASE`.`f_sales` WHERE `idf_sales` = `f_creditsSalesID`) as `f_creditsSalesID`,"
				. "(SELECT `RS_banksUUID` FROM `$DATABASE`.`RS_banks` WHERE `idRS_banks` = `f_creditsBankID`) as `f_creditsBankID`"
				. " FROM "
				. " `$DATABASE`.`f_credits`"
				. ""));
if (0) {
	printr($output[0], 1);
	print ". \"`" . implode("`,\"<br>. \"`", array_keys($output[0])) . "`\"";
	die();
}
//idservicesApplied, , servicesAppliedQty, servicesAppliedClient, servicesAppliedBy, servicesAppliedPersonal, servicesAppliedDate, servicesAppliedIsFree2, servicesAppliedIsNew, servicesAppliedAt, servicesAppliedTimeBegin, servicesAppliedStarted, servicesAppliedStartedBy, servicesAppliedTimeEnd, servicesAppliedFineshed, servicesAppliedFinishedBy, , servicesAppliedSubscription, servicesAppliedPrice, servicesAppliedDeleted, servicesAppliedDeletedBy, servicesAppliedDeleteReason, servicesAppliedLocked, servicesAppliedByReal, servicesAppliedIsDiagnostic, servicesAppliedAdvancePayment
header('Content-disposition: attachment; filename=credits_export ' . date("y-m-d H-i-s") . '.json');
header('Content-type: application/json');

print(json_encode([
			'sales' => $output,
				], 256 + 128));
