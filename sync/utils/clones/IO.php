<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (($_JSON['source'] ?? 0) && ($_JSON['target'] ?? 0)) {






	if (($_JSON['source']['table'] ?? false) && ($_JSON['source']['source'] ?? false ) !== ($_JSON['target']['target'] ?? null)) {
		//tables	
		if ($_JSON['source']['table'] == 'clientsComments') {
			if (mysqlQuery("UPDATE `clientsComments` SET `clientsCommentsClient`='" . $_JSON['target']['target'] . "' WHERE `clientsCommentsClient` = '" . $_JSON['source']['source'] . "'")) {
				print json_encode(['success' => true], 288);
			}
		}
		if ($_JSON['source']['table'] == 'clientsPassports') {
			if (mysqlQuery("UPDATE `clientsPassports` SET `clientsPassportsClient`='" . $_JSON['target']['target'] . "' WHERE `clientsPassportsClient` = '" . $_JSON['source']['source'] . "'")) {
				print json_encode(['success' => true], 288);
			}
		}
		if ($_JSON['source']['table'] == 'clientsPhones') {
			if (mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesClient`='" . $_JSON['target']['target'] . "' WHERE `clientsPhonesClient` = '" . $_JSON['source']['source'] . "'")) {
				print json_encode(['success' => true], 288);
			}
		}

		if ($_JSON['source']['table'] == 'clientsVisits') {
			if (mysqlQuery("UPDATE IGNORE `clientsVisits` SET `clientsVisitsClient`='" . $_JSON['target']['target'] . "' WHERE `clientsVisitsClient` = '" . $_JSON['source']['source'] . "'") &&
					mysqlQuery("DELETE FROM `clientsVisits` WHERE `clientsVisitsClient` = '" . $_JSON['source']['source'] . "'")
			) {
				print json_encode(['success' => true], 288); //clientsVisitsDate
			}
		}

		if ($_JSON['source']['table'] == 'f_sales') {
			if (mysqlQuery("UPDATE `f_sales` SET `f_salesClient`='" . $_JSON['target']['target'] . "' WHERE `f_salesClient` = '" . $_JSON['source']['source'] . "'")) {
				print json_encode(['success' => true], 288);
			}
		}

		if ($_JSON['source']['table'] == 'servicesApplied') {
			if (mysqlQuery("UPDATE `servicesApplied` SET `servicesAppliedClient`='" . $_JSON['target']['target'] . "' WHERE `servicesAppliedClient` = '" . $_JSON['source']['source'] . "'")) {
				print json_encode(['success' => true], 288);
			}
		}
		if ($_JSON['source']['table'] == 'clients') {
			$to = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`= '" . $_JSON['source']['source'] . "' "));

			if (mysqlQuery("UPDATE `clients` SET `" . $_JSON['source']['column'] . "`='" . $to[$_JSON['source']['column']] . "' WHERE `idclients` = '" . $_JSON['target']['target'] . "'")) {
				print json_encode(['success' => true], 288);
			}
		}
	} else {
		
	}
}