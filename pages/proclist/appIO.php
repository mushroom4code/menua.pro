<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (($_JSON['action'] ?? '') === 'deleteServiceApplied' && isset($_JSON['idservicesApplied'])) {
	mysqlQuery("UPDATE `servicesApplied`"
			. " SET `servicesAppliedDeleted` = NOW(),"
			. " `servicesAppliedDeleteReason` = '19',"
			. " `servicesAppliedDeletedBy`='" . $_USER['id'] . "'"
			. " WHERE `idservicesApplied` = '" . mres($_JSON['idservicesApplied']) . "'"
			. " AND `servicesAppliedBy`='" . $_USER['id'] . "'"
			. "");
}
if (($_JSON['action'] ?? '') === 'getServicesApplied' && isset($_JSON['idclients'])) {
	$servicesApplied = query2array(mysqlQuery("SELECT *, (`servicesAppliedBy` = '" . $_USER['id'] . "') AS `deleteable` FROM `servicesApplied`"
					. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) "
					. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
					. " WHERE `servicesAppliedClient`='" . mres($_JSON['idclients']) . "'"
					. " AND `servicesAppliedDate`>=CURDATE()"
					. " AND isnull(`servicesAppliedDeleted`)"
					. ""));
	exit(json_encode(['success' => true, 'servicesApplied' => $servicesApplied], 288));
}


if (($_JSON['action'] ?? '') === 'makeAnAppointment') {

	$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($_JSON['service']) . "'"));
	if (!$service) {
		exit(json_encode(['success' => false, 'msg' => 'Несуществующая услуга'], 288));
	}
	mysqlQuery("INSERT INTO `servicesApplied` SET 
`servicesAppliedService` = " . ((($_JSON['options'] ?? '') === 'noservice') ? 'NULL' : ("'" . $service['idservices'] . "'")) . ",
`servicesAppliedQty` = '" . mres($_JSON['qty']) . "',
`servicesAppliedClient` = '" . mres($_JSON['client']) . "',
`servicesAppliedBy` = '" . $_USER['id'] . "',
`servicesAppliedPersonal` = '" . mres($_JSON['personal']) . "',
`servicesAppliedDate` = '" . date("Y-m-d", $_JSON['timeBegin']) . "',
`servicesAppliedTimeBegin` = '" . date("Y-m-d H:i:s", $_JSON['timeBegin']) . "',
`servicesAppliedTimeEnd` = '" . date("Y-m-d H:i:s", $_JSON['timeBegin'] + ($service['servicesDuration'] ?? 60) * 60) . "',
`servicesAppliedContract` = '" . mres($_JSON['contract']) . "',
`servicesAppliedPrice` = " . sqlVON($_JSON['price']) . ",
`servicesAppliedByReal` = '" . $_USER['id'] . "'");
	if (($error = mysqli_error($link))) {
		die(json_encode(['success' => false, 'msg' => $error], 288));
	}
	exit(json_encode(['success' => true], 288));
}






die(json_encode(['success' => false, 'error' => 'EOF'], 288));
