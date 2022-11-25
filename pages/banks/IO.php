<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

$OUT = ['success' => false];

if (($_JSON['action'] ?? '') == 'searchContracts' && !empty($_JSON['clientName'])) {
	$_JSON['clientName'] = preg_replace('!\s+!', ' ', trim($_JSON['clientName']));
	$nameArr = explode(" ", $_JSON['clientName']);
	$contracts = query2array(mysqlQuery("SELECT *, (SELECT SUM(f_creditsTransactionsValue) FROM `f_creditsTransactions` WHERE `f_creditsTransactionsCredit` = `idf_credits`) AS `creditsPayedAmount` FROM"
					. " `f_credits`"
					. "LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
					. "LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
					. "LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`)"
					. " WHERE"
					. " `f_salesDate`>='2022-01-01' "
					. " AND isnull(`f_creditsPayed`) "
					. " AND isnull(`f_creditsCanceled`) "
					. " AND NOT isnull(`f_creditsBankID`)"
					. (($nameArr[0] ?? '') !== '' ? ("AND `clientsLName` LIKE '" . mres($nameArr[0]) . "%'") : '')
					. (($nameArr[1] ?? '') !== '' ? ("AND `clientsFName` LIKE '" . mres($nameArr[1]) . "%'") : '')
					. (($nameArr[2] ?? '') !== '' ? ("AND `clientsMName` LIKE '" . mres($nameArr[2]) . "%'") : '')
					. (($_JSON['idbanks'] ?? '') !== '' ? ("AND `idRS_banks` = '" . mres($_JSON['idbanks']) . "'") : '')
					. " LIMIT 100"));
	$OUT = ['contracts' => $contracts, 'success' => true];
}

if (($_JSON['action'] ?? '') == 'cancelCredit' && !empty($_JSON['creditId'])) {
	$OUT['success'] = !!mysqlQuery("UPDATE `f_credits` SET `f_creditsCanceled` = NOW() WHERE `idf_credits`='" . mres($_JSON['creditId']) . "'");
}
if (($_JSON['action'] ?? '') == 'addTransaction' && !empty($_JSON['idcredit']) && !empty($_JSON['date']) && !empty($_JSON['summ'])) {

	$OUT['success'] = !!mysqlQuery("INSERT INTO `f_creditsTransactions` SET "
					. "`f_creditsTransactionsCredit`= '" . mres($_JSON['idcredit']) . "',"
					. "`f_creditsTransactionsDate`= '" . mres($_JSON['date']) . "',"
					. "`f_creditsTransactionsValue`= '" . mres($_JSON['summ']) . "'");  
	mysqlQuery("UPDATE `f_credits` SET `f_creditsBroker` = " . sqlVON($_JSON['broker']) . " WHERE `idf_credits` = '" . mres($_JSON['idcredit']) . "' ");
	$credit = mfa(mysqlQuery("SELECT *,(SELECT SUM(f_creditsTransactionsValue) FROM `f_creditsTransactions` WHERE `f_creditsTransactionsCredit` = `idf_credits`) AS `creditsPayedAmount` FROM `f_credits` WHERE `idf_credits`='" . mres($_JSON['idcredit']) . "'"));
	if (intval($credit['f_creditsSumm']) <= intval($credit['creditsPayedAmount'])) {
		mysqlQuery("UPDATE `f_credits` SET `f_creditsPayed` = NOW() WHERE `idf_credits`='" . mres($_JSON['idcredit']) . "'");
	}

	$OUT['errors'][] = mysqli_error($link);
}



print json_encode($OUT, JSON_UNESCAPED_UNICODE);

die();

//printr();

