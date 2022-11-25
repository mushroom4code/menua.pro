<?php

function date2monthFromTo($date) {
	$timestamp = mystrtotime($date);
	return [
		'Ym' => date("Ym", $timestamp),
		'Y-m' => date("Y-m", $timestamp),
		'from' => date("Y-m-01", $timestamp),
		'to' => date("Y-m-t", $timestamp),
	];
}

function getUserSales($user, $from, $to) {
	return query2array(mysqlQuery("SELECT * FROM"
					. " `f_salesRoles` "
					. " LEFT JOIN `f_roles` ON (`idf_roles` = `f_salesRolesRole`)"
					. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_salesRolesSale`) WHERE `f_salesRolesUser` = '" . mres($user) . "' AND `f_salesDate`>='" . mres($from) . "' AND `f_salesDate`<='" . mres($to) . "'"));
}

function getPayments($sales) {
	if (count($sales)) {
		$payments = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` IN (" . implode(',', array_column($sales, 'idf_sales')) . ")"));
//			printr($payments);
		foreach ($payments as $payment) {
			$index = array_search($payment['f_paymentsSalesID'], array_column($sales, 'idf_sales'));
			$sales[$index]['payments'][] = $payment;
			$sales[$index]['paymentsTotal'] = ($sales[$index]['paymentsTotal'] ?? 0) + $payment['f_paymentsAmount'];
		}

		$credits = query2array(mysqlQuery("SELECT * FROM `f_credits` WHERE `f_creditsSalesID` IN (" . implode(',', array_column($sales, 'idf_sales')) . ")"));

		foreach ($credits as $credit) {
			$index = array_search($credit['f_creditsSalesID'], array_column($sales, 'idf_sales'));
			$sales[$index]['credits'][] = $credit;
			$sales[$index]['paymentsTotal'] = ($sales[$index]['paymentsTotal'] ?? 0) + $credit['f_creditsSumm'];
		}
		foreach ($sales as $index => $sale) {
			$sales[$index]['payed'] = $sale['paymentsTotal'] >= $sale['f_salesSumm'];
			$sales[$index]['summIncReturns'] = $sale['f_salesSumm'] - $sale['f_salesCancellationSumm'];
		}
	}
	return $sales;
}
