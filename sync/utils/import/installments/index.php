<?php
$pageTitle = 'Импорт клиентов';
header('Content-Encoding: none;');
die();
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {

	$json = json_decode(file_get_contents("sales.json"), true);
	$allsales = $json['Договоры'];
//	printr($sales[1]);
//	die('not now');
	$BANKGUIDS = (query2array(mysqlQuery("SELECT `idRS_banks`,`RS_banksGUID` FROM `RS_banks`"), 'RS_banksGUID'));
	$f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID');
	?>

	<style>

		.sq {
			display: inline-block;
			width: 8px;
			height: 8px;
			margin: 2px;
		}
		.ok {
			background-color: green;
		}
		.err {
			background-color: red;
		}
		.warn {
			background-color: orange;
		}

	</style>
	<div class="box neutral">
		<div class="box-body">
			<h2>Рассрочки</h2>
			<div style="line-height: 10px;">
				<?
				$start = microtime(1);
				$insert = [];
				$head = "INSERT INTO `f_installments` (`f_installmentsSalesID`,`f_installmentsSumm`,`f_installmentsPeriod`) VALUES ";
				$clients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'GUID');

				foreach ($allsales as $client) {
					if ($client['GUIDКлиента']) {
						$clientSQL = $clients[$client['GUIDКлиента']] ?? null;
						if (!($clientSQL['idclients'] ?? false)) {
							?><div class="sq err" title="Нет id клиента по GUID <?= $client['GUIDКлиента'] ?? 'Да и гуида нет...'; ?>" onclick="alert('Нет id клиента по GUID <?= $client['GUIDКлиента'] ?? 'Да и гуида нет...'; ?>');"></div><?
						}
						$sales = $client['Договоры'];
						foreach ($sales as $sale) {
							if (($sale['Схема оплаты'] ?? '') == 'В рассрочку') {
								$f_installments['f_installmentsSalesID'] = $f_sales[$sale['GUIDДоговора']]['idf_sales'] ?? null;
								$f_installments['f_installmentsSumm'] = $sale["Сумма договора"] - mfa(
												mysqlQuery("SELECT SUM(`f_paymentsAmount`) as `summ` FROM `f_payments` WHERE `f_paymentsSalesID`='" . $f_installments['f_installmentsSalesID'] . "'")
										)['summ'] ?? 0;
								$f_installments['f_installmentsPeriod'] = 1;

								if ($f_installments['f_installmentsSalesID'] ?? false) {
									$insert[] = "('" . $f_installments['f_installmentsSalesID'] . "', "
											. " '" . $f_installments['f_installmentsSumm'] . "', "
											. " '" . $f_installments['f_installmentsPeriod'] . "')";
								} else {
									?><div class="sq warn" onclick="alert('Нет продажи по GUID <?= $sale['GUIDДоговора'] ?? 'Да и гуида нет...'; ?>');" title="Нет продажи по GUID <?= $sale['GUIDДоговора'] ?? 'Да и гуида нет...'; ?>"></div><?
									}

									if (count($insert) > 25) {
										if (mysqlQuery($head . implode(',', $insert))) {
											print '<div class="sq ok" title="25"></div>';
										} else {
											print '<div class="sq warn" title="mysqlError"></div>';
										}
										$insert = [];
										for ($n = 0; $n <= 10; $n++) {
											print '<!--                                                                                                    -->';
										}
										ob_flush();
										flush();
									}
								}
							}
						}
					}
					if (count($insert)) {
						mysqlQuery($head . implode(',', $insert));
						$insert = [];
					}
					?>
			</div>
			<h3>Завершено за: <?= round((microtime(1) - $start), 2); ?></h3>
		</div>
	</div>

<? }
?>

<?
print "PGT:" . microtime(1) - $start;
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
