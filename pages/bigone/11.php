<?php

$affectedSalesSQL = "SELECT `idf_sales` FROM `f_sales`"
		. " WHERE `idf_sales` IN (SELECT `f_paymentsSalesID` FROM `f_payments` WHERE `f_paymentsDate`>='$from 00:00:00' AND `f_paymentsDate`<='$to 23:59:59' GROUP BY `f_paymentsSalesID`)"
		. " OR (`f_salesDate`>='$from' AND `f_salesDate`<='$to')";

//print $affectedSalesSQL;
$affectedSales = query2array(mysqlQuery($affectedSalesSQL));

//printr($affectedSales);

$payments = query2array(mysqlQuery("SELECT *,DATE(`f_paymentsDate`) as `f_paymentsDate` FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE `f_paymentsSalesID` IN (" . implode(',', array_column($affectedSales, 'idf_sales')) . ") "));
$credits = query2array(mysqlQuery("SELECT * FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`) WHERE `f_creditsSalesID` IN (" . implode(',', array_column($affectedSales, 'idf_sales')) . ") "));

$f_salesToPersonal = query2array(mysqlQuery("SELECT * FROM `f_salesToPersonal` WHERE `f_salesToPersonalSalesID`  IN (" . implode(',', array_column($affectedSales, 'idf_sales')) . ")"));

//printr($f_salesToPersonal);
$allaffectedSales = [];

foreach ($payments as $payment) {
	$allaffectedSales[$payment['idf_sales']]['idf_sales'] = $payment['idf_sales'];
	$allaffectedSales[$payment['idf_sales']]['f_salesSumm'] = $payment['f_salesSumm'];
	$allaffectedSales[$payment['idf_sales']]['f_salesDate'] = $payment['f_salesDate'];
	$allaffectedSales[$payment['idf_sales']]['f_salesType'] = $payment['f_salesType'];
	$allaffectedSales[$payment['idf_sales']]['f_salesClient'] = $payment['f_salesClient'];
	$allaffectedSales[$payment['idf_sales']]['payments'][] = [
		'f_paymentsType' => $payment['f_paymentsType'],
		'f_paymentsAmount' => $payment['f_paymentsAmount'],
		'f_paymentsDate' => $payment['f_paymentsDate'],
		'f_paymentsAge' => (mystrtotime($payment['f_paymentsDate']) - mystrtotime($payment['f_salesDate'])) / (60 * 60 * 24)
	];
	usort($allaffectedSales[$payment['idf_sales']]['payments'], function ($a, $b) {
		return $a['f_paymentsDate'] <=> $b['f_paymentsDate'];
	});
}
foreach ($credits as $credit) {
	$allaffectedSales[$credit['idf_sales']]['idf_sales'] = $credit['idf_sales'];
	$allaffectedSales[$credit['idf_sales']]['f_salesSumm'] = $credit['f_salesSumm'];
	$allaffectedSales[$credit['idf_sales']]['f_salesDate'] = $credit['f_salesDate'];
	$allaffectedSales[$credit['idf_sales']]['f_salesType'] = $credit['f_salesType'];
	$allaffectedSales[$credit['idf_sales']]['f_salesClient'] = $credit['f_salesClient'];
	$allaffectedSales[$credit['idf_sales']]['payments'][] = [
		'f_paymentsAmount' => $credit['f_creditsSumm'],
		'f_paymentsDate' => $credit['f_salesDate'],
		'f_paymentsAge' => 0,
	];
}

foreach ($allaffectedSales as &$allaffectedSale2) {
	$allaffectedSale2['personal'] = array_filter($f_salesToPersonal, function ($saleToPersonal) use ($allaffectedSale2) {
		return $saleToPersonal['f_salesToPersonalSalesID'] == $allaffectedSale2['idf_sales'];
	});
	/*
	  [idf_salesToPersonal] => 9295
	  [f_salesToPersonalSalesID] => 23276
	  [f_salesToPersonalUser] => 155
	 */


//	$allaffectedSale2[$allaffectedSale2['idf_sales']]['personal'] = array_filter($f_salesToPersonal, function ($sale)use ($allaffectedSale2) {
//		return $sale['f_salesToPersonalSalesID'] == $allaffectedSale2['idf_sales'];
//	});
	$allaffectedSale2['payed'] = (array_sum(array_column($allaffectedSale2['payments'], 'f_paymentsAmount')) >= $allaffectedSale2['f_salesSumm']);
	$allaffectedSale2['spoiled'] = count(array_filter($allaffectedSale2['payments'], function ($payment) {
						return $payment['f_paymentsAge'] > 31;
					})) > 0;
}
//printr(($allaffectedSales));
//printr(count($allaffectedSales));
//printr(count($payments));
//printr(count($credits));
foreach ($groups as &$group3_11) {
	foreach ($group3_11['users'] as &$user3_11) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
			$mysales = array_filter($allaffectedSales, function ($sale)use ($user3_11, $time) {
				return count($sale['payments'] ?? []) &&
				$sale['payments'][count($sale['payments'] ?? []) - 1]['f_paymentsDate'] == mydates("Y-m-d", $time) &&
				count($sale['personal'] ?? []) && in_array($user3_11['idusers'], array_column($sale['personal'], 'f_salesToPersonalUser'));
			});
			foreach ($mysales as $mysale) {
				if ($mysale['payed'] && !$mysale['spoiled']) {
					$user3_11['wages'][mydates("Y-m-d", $time)][11]['value'] = ($user3_11['wages'][mydates("Y-m-d", $time)][11]['value'] ?? 0) +
							$mysale['f_salesSumm'] * (($user3_11['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][11] ?? 0) / 100) / count($mysale['personal'] ?? []);
				}
			}
			$user3_11['wages'][mydates("Y-m-d", $time)][11]['info'] = [
				'percent' => ($user3_11['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][11] ?? 0) / 100,
				'mysales' => $mysales
			];
		}//$time
	}//users
}//groups