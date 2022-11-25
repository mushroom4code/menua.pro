<?
header('Content-Encoding: none;');
?>
<style>
	div {
		width: 10px; height: 10px;
		display: inline-block;
		border: 1px solid silver;
	}
	div:hover {
		border: 1px solid white;
	}
	.red{
		background-color: red;
	}
	.pink{
		background-color: pink;
	}
	.yellow{
		background-color: yellow;
	}
	.green{
		background-color: green;
	}
	.darkgreen{
		background-color: darkcyan;
	}

	.blue{
		background-color: blue;
	}
</style>
<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

function formatContract($contractInfo) {
	$out = [];
	$out [0] = iconv("utf-8", "cp1251", ($contractInfo['contract']['clientsLName'] ?? '') . ' ' .
			($contractInfo['contract']['clientsFName'] ?? '') . ' ' .
			($contractInfo['contract']['clientsMName'] ?? ''));
//		
	$out[1] = ($contractInfo['contract']['idf_sales'] ?? '');
//		
	$out[2] = ($contractInfo['contract']['f_salesDate'] ?? '');
//		
	$out[3] = ($contractInfo['contract']['f_salesSumm'] ?? 0);
//	
	$out[4] = ($contractInfo['calculatedSumm'] ?? 0);
//		
	$out[5] = ($contractInfo['paymentsSumm']);



	return $out;
}

//function contractInfo($idf_sale, $date = null) {
//	$contract = mfa(mysqlQuery("SELECT * FROM `f_sales` LEFT JOIN `clients` ON (`idclients` = `f_salesClient`) WHERE `idf_sales`='" . intval($idf_sale) . "'"));
//
//	$f_credits = mfa(mysqlQuery("SELECT * FROM `f_credits` WHERE `f_creditsSalesID`='" . $contract['idf_sales'] . "'"));
//	$f_installments = mfa(mysqlQuery("SELECT * FROM `f_installments` WHERE `f_installmentsSalesID`='" . $contract['idf_sales'] . "'"));
//	$f_payments = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID`='" . $contract['idf_sales'] . "'"));
//	$f_subscriptions = query2array(mysqlQuery("SELECT * FROM `f_subscriptions` WHERE `f_subscriptionsContract`='" . $contract['idf_sales'] . "'"));
//	$servicesApplied = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `servicesAppliedContract`='" . $contract['idf_sales'] . "'"));
//
//	$remains = [];
//	$calculatedSumm = 0;
//	foreach ($f_subscriptions as $f_subscription) {
//		$calculatedSumm += ($f_subscription['f_salesContentQty'] ?? 0) * ($f_subscription['f_salesContentPrice'] ?? 0);
//	}
//	$paymentsSumm = 0;
//	if (is_array($f_payments)) {
//		$paymentsSumm += array_sum(array_column($f_payments, 'f_paymentsAmount'));
//	}
//	if (is_array($f_credits)) {
//		$paymentsSumm += $f_credits['f_creditsSumm'];
//	}
//
////	;
//
//
//	$output = [
//		'contract' => $contract,
//		'calculatedSumm' => $calculatedSumm,
//		'f_salesSumm' => $contract['f_salesSumm'],
//		'paymentsSumm' => $paymentsSumm,
//		'paymentsOK' => ($contract['f_salesSumm'] > 0 && $paymentsSumm == $calculatedSumm && ($calculatedSumm ?? 0) > 0),
//		'paymentsDebt' => $calculatedSumm - $paymentsSumm,
//		'f_payments' => $f_payments,
//		'f_credits' => $f_credits,
//		'f_installments' => $f_installments,
//		'f_subscriptions' => $f_subscriptions,
//		'servicesApplied' => $servicesApplied,
//	];
////	printr($output);
//	return $output;
//}

if ($_GET['contract'] ?? false) {
	$contr = contractInfo(($_GET['contract'] ?? 16459));
	?><a target="_blank" href="/pages/checkout/payments.php?client=<?= $contr['contract']['f_salesClient']; ?>&contract=<?= $contr['contract']['idf_sales']; ?>">Кредитный</a><?
	printr($contr);
} else {
	$contractsResult = mysqlQuery("SELECT `idf_sales` FROM `f_sales` WHERE  isnull(`f_salesCancellationDate`) ORDER BY `f_salesDate`");
	print mysqli_num_rows($contractsResult) . '<br>';
	$green = $darkgreen = $blue = $yellow = $pink = $red = 0;
	$greenDB = $darkgreenDB = $blueDB = $yellowDB = $pinkDB = $redDB = [
		[iconv("utf-8", "cp1251", 'Клиент'),
			iconv("utf-8", "cp1251", 'Номер аб.'),
			iconv("utf-8", "cp1251", 'Дата продажи'),
			iconv("utf-8", "cp1251", 'Стоимость аб.'),
			iconv("utf-8", "cp1251", 'Стоимость услуг'),
			iconv("utf-8", "cp1251", 'Платежи')]
	];



	while ($contract = mfa($contractsResult)) {
		$contractinfo = contractInfo($contract['idf_sales']);
//		ob_end_flush();
		flush();
//		printr();
//		printr($contractinfo);
//		die();
		?><a target="_blank" href="?contract=<?= $contract['idf_sales'] ?>"><?
			if ($contractinfo['paymentsOK']) {
				?><div class="green"></div><?
				$green++;
				$greenDB[] = formatContract($contractinfo);
			} else {
				if ($contractinfo['f_salesSumm'] > 0 && $contractinfo['paymentsSumm'] == $contractinfo['f_salesSumm']) {
					?><div class="darkgreen"></div><?
					$darkgreen++;
					$darkgreenDB[] = formatContract($contractinfo);
				} elseif ($contractinfo['paymentsSumm'] > $contractinfo['calculatedSumm']) {
					?><div class="blue"></div><?
						$blue++;
						$blueDB[] = formatContract($contractinfo);
					} elseif (is_array($contractinfo['f_installments'])) {
						if (time() - strtotime($contractinfo['contract']['f_salesDate']) > 30 * 24 * 60 * 60) {
							?><div class="pink"></div><?
							$pink++;
							$pinkDB[] = formatContract($contractinfo);
						} else {
							?><div class="yellow"></div><?
							$yellow++;
							$yellowDB[] = formatContract($contractinfo);
						}
					} else {
						$redDB[] = formatContract($contractinfo);
						$red++;
						?><div class="red"></div><?
					}
				}
				?></a><?
		}
		?><br><br>
	<div class="red"></div> (<?= $red; ?>) - Проблема с платежами<br>
	<div class="yellow"></div> (<?= $yellow; ?>) - Незакрытая рассрочка<br>
	<div class="pink"></div> (<?= $pink; ?>) - Просроченная рассрочка<br>
	<div class="green"></div> (<?= $green; ?>) - Сумма платежей равна Сумме стоимостей процедур<br>
	<div class="darkgreen"></div> (<?= $darkgreen; ?>) - Сумма платежей равна стоимости абонемента (проставлена вручную), но не соотвествует Сумме стоимостей процедур.<br>
	<div class="blue"></div> (<?= $blue; ?>) - Сумма платежей Больше Суммы стоимостей процедур. (скорее всего ошибки с ценами на процедуры)<br>
	<?
	$fp = fopen('csv/red.csv', 'w');
	foreach ($redDB as $fields) {
		fputcsv($fp, $fields, ';');
	}
	fclose($fp);


	$fp = fopen('csv/pink.csv', 'w');
	foreach ($pinkDB as $fields) {
		fputcsv($fp, $fields, ';');
	}
	fclose($fp);


	$fp = fopen('csv/yellow.csv', 'w');
	foreach ($yellowDB as $fields) {
		fputcsv($fp, $fields, ';');
	}
	fclose($fp);



	$fp = fopen('csv/blue.csv', 'w');
	foreach ($blueDB as $fields) {
		fputcsv($fp, $fields, ';');
	}
	fclose($fp);


	$fp = fopen('csv/darkgreen.csv', 'w');
	foreach ($darkgreenDB as $fields) {
		fputcsv($fp, $fields, ';');
	}
	fclose($fp);
	$fp = fopen('csv/green.csv', 'w');
	foreach ($greenDB as $fields) {
		fputcsv($fp, $fields, ';');
	}
	fclose($fp);
}






 




