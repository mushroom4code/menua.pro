<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (isset($_JSON['action']) && $_JSON['action'] === 'cfTypeColor') {
	$OUT = [];
	if (R(146)) {
		mysqlQuery("UPDATE `cashFlowTypes` SET `cashFlowTypesColor` = '" . FSS($_JSON['cfTypeColor']) . "' WHERE `idcashFlowType` = '" . FSI($_JSON['id']) . "'");
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}

	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}
if (isset($_JSON['getDDSS']) && $_JSON['getDDSS']) {
	$OUT = [];
	if (R(146)) {
		$OUT = query2array(mysqlQuery("SELECT * FROM `cashFlowTypes`"));
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}

	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}
if (isset($_JSON['action']) && $_JSON['action'] == 'editCF') {
	/*
	  action: "editCF"
	  cftype: "7"
	  comment: "ТЕСТОВАЯ ЗАПИСЬ"
	  key: 346
	  summ: "777"
	 */

	$OUT = [];
	if (R(146)) {

		$oldCF = mfa(mysqlQuery("SELECT * FROM `cashFlow` WHERE `idcashFlow`='" . $_JSON['key'] . "' AND isnull(`cashFlowDeleted`)"));

		$sets = [];

		if ($oldCF['cashFlowSumm'] != FSI($_JSON['summ'])) {
			$sets[] = "`cashFlowSumm`='" . FSI($_JSON['summ']) . "'";
		}
		if ($oldCF['cashFlowComment'] != FSS($_JSON['comment'])) {
			$sets[] = "`cashFlowComment`='" . FSS($_JSON['comment'] ?? '') . "'";
		}
		if ($oldCF['cashFlowType'] != FSI($_JSON['cftype'])) {
			$sets[] = "`cashFlowType`=" . ($_JSON['cftype'] ?? 'null') . "";
		}
		if (date("Y-m-d", strtotime($oldCF['cashFlowDate'])) != FSI($_JSON['date'])) {
			$sets[] = "`cashFlowDate`='" . ($_JSON['date'] . ' 12:00:00') . "'";
		}



		if (count($sets) && mysqlQuery("UPDATE `cashFlow` SET "
						. implode(",", $sets)
						. " WHERE "
						. "`idcashFlow` = '" . FSI($_JSON['key']) . "'")) {
			$OUT['success'] = true;
		} else {
			$OUT['msgs'][] = 'Ничего не поменялось.';
			$OUT['success'] = true;
		}
	} else {
		$OUT['success'] = false;
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}

	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}
if (isset($_JSON['action']) && $_JSON['action'] == 'editCFT') {
	/*
	  action: "editCFT"
	  ​cftype: "банк"
	  key: 11
	 */

//	printr($_JSON);
	$OUT = [];
	if (R(146)) {

		if (FSS($_JSON['key']) === 'new') {
			if (mysqlQuery("INSERT INTO `cashFlowTypes` SET"
							. " `cashFlowTypeName`='" . FSS($_JSON['cftype']) . "'")) {
				$OUT['success'] = true;
			}
		} else {
			if (mysqlQuery("UPDATE `cashFlowTypes` SET"
							. " `cashFlowTypeName`='" . FSS($_JSON['cftype']) . "'"
							. " WHERE "
							. "`idcashFlowType` = '" . FSI($_JSON['key']) . "'")) {
				$OUT['success'] = true;
			}
		}
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}

	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}