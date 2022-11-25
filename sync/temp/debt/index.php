<?php
$pageTitle = '';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$debt = file('debt.csv');
foreach ($debt as &$row) {
	$row = iconv("cp1251", "utf-8", $row);
}
$sales = [];
foreach ($debt as $row) {
	$columns = explode(';', $row);
	$dateArr = explode(".", $columns[0]);
	$date = $dateArr[2] . "-" . $dateArr[1] . "-" . $dateArr[0];
	$nameArr = explode(" ", preg_replace('/\s+/', ' ', $columns[1]));
	$lname = $nameArr[0];
	$fname = $nameArr[1];

	$sale = query2array(mysqlQuery("SELECT * FROM `f_sales` LEFT JOIN `clients` ON (`idclients` = `f_salesClient`) WHERE `f_salesDate`='$date' AND `clientsLName` like '%$lname%' AND `clientsFName` LIKE '%$fname%'"));
	if (count($sale) !== 1) {
		print count($sale) . ' ' . $columns[0] . ' ' . $columns[1] . '<br>';
	} else {

		$sales[] = $sale[0]['idf_sales'];
	}
}

$installments = query2array(mysqlQuery("SELECT *,(SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID`=`idf_sales`) AS `payments` FROM `f_installments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_installmentsSalesID`) WHERE `f_salesDate`<='2020-06-03' AND `f_installmentsSalesID` NOT IN (" . implode(',', $sales) . ") AND `f_salesSumm` > (SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID`=`idf_sales`)"));

foreach ($installments as $installment) {
	mysqlQuery("INSERT INTO `f_payments` SET "
			. " `f_paymentsSalesID`='" . $installment['idf_sales'] . "',"
			. " `f_paymentsType`='1',"
			. " `f_paymentsAmount` = '" . (($installment['f_salesSumm'] ?? 0) - ($installment['payments'] ?? 0)) . "',"
			. " `f_paymentsDate`='2021-04-22 00:00:00',"
			. " `f_paymentsUser` = 176");
}

printr($installments);
print count($installments);
?>
<div class="box neutral">
	<div class="box-body">

		<? printr($sales); ?>
		<? printr($debt); ?>

	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
