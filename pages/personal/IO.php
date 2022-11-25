<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

$OUT['success'] = false;

//action	"savePersonalPayments"
//date	"2022-02-01"
//employee	825
//service	10926
//value	""

if (($_JSON['action'] ?? '') === 'savePersonalPayments' &&
		validateDate(($_JSON['date'] ?? '')) &&
		($_JSON['service'] ?? false) && (
		$_JSON['employee'] ?? false)) {
	$OUT['success'] = 'processing';
	$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers`=" . mres($_JSON['employee'])));
	if (!$user) {
		die(json_encode(['success' => false, 'error' => 'No such user'], 288));
	}
	$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices`=" . mres($_JSON['service'])));
	if (!$service) {
		die(json_encode(['success' => false, 'error' => 'No such service'], 288)); 
	}

	if (mysqlQuery("INSERT INTO `usersServicesPayments` SET "
					. " `usersServicesPaymentsUser`=" . $user['idusers'] . ","
					. " `usersServicesPaymentsService`=" . $service['idservices'] . ","
					. " `usersServicesPaymentsSumm` = " . sqlVON($_JSON['value'], 1) . ","
					. " `usersServicesPaymentsSummFree` = " . sqlVON($_JSON['valueFree'], 1) . ","
					. " `usersServicesPaymentsDate` = '" . $_JSON['date'] . "',"
					. " `usersServicesPaymentsSetBy` = " . $_USER['id'])) {
		exit(json_encode(['success' => true], 288));
	} else {
		exit(json_encode(['success' => false, 'error' => mysqli_error($link)], 288));
	}
}
print json_encode($OUT, 288);
