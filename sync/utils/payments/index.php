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
	.gray{
		background-color: #333;
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

if ($_GET['contract'] ?? false) {
	$contr = contractInfo(($_GET['contract'] ?? 16459));
	?><a target="_blank" href="/pages/checkout/payments.php?client=<?= $contr['contract']['f_salesClient']; ?>&contract=<?= $contr['contract']['idf_sales']; ?>">Перейти к абонементу</a>
	<br><br><br><br>
	Подробная информация:<br><?
	printr($contr);
} else {
	$contractsResult = mysqlQuery("SELECT `idf_sales` FROM `f_sales` WHERE  isnull(`f_salesCancellationDate`) ORDER BY `f_salesDate`");
	print mysqli_num_rows($contractsResult) . '<br>';
	$gray = $green = $darkgreen = $blue = $yellow = $pink = $red = 0;
	$grayDB = $greenDB = $darkgreenDB = $blueDB = $yellowDB = $pinkDB = $redDB = [
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
				if ($contractinfo['f_salesSumm'] == 0) {
					?><div class="gray"></div><?
					$gray++;
					$grayDB[] = formatContract($contractinfo);
				} elseif ($contractinfo['paymentsSumm'] == $contractinfo['f_salesSumm']) {
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
	<a href="/utils/payments/csv/red.csv"><div class="red"></div></a> (<?= $red; ?>) - Проблема с платежами<br>
	<a><div class="gray"></div></a> (<?= $gray; ?>) - Стоимость абонемента 0 рублей<br>
	<a href="/utils/payments/csv/yellow.csv"><div class="yellow"></div></a> (<?= $yellow; ?>) - Незакрытая рассрочка<br>
	<a href="/utils/payments/csv/pink.csv"><div class="pink"></div></a> (<?= $pink; ?>) - Просроченная рассрочка<br>
	<a href="/utils/payments/csv/green.csv"><div class="green"></div></a> (<?= $green; ?>) - Сумма платежей равна Сумме стоимостей процедур<br>
	<a href="/utils/payments/csv/darkgreen.csv"><div class="darkgreen"></div></a> (<?= $darkgreen; ?>) - Сумма платежей равна стоимости абонемента (проставлена вручную), но не соотвествует Сумме стоимостей процедур.<br>
	<a href="/utils/payments/csv/blue.csv"><div class="blue"></div></a> (<?= $blue; ?>) - Сумма платежей Больше Суммы стоимостей процедур. (скорее всего ошибки с ценами на процедуры)<br>
	<?
	if (1) {
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
}











