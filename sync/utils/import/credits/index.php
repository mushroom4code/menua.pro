<?php
$pageTitle = 'Импорт клиентов';
header('Content-Encoding: none;');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176) {
	
}
die();
$start = microtime(1);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {

	$json = json_decode(file_get_contents("sales.json"), true);
	$allsales = $json['Договоры'];
//	printr($sales[1]);
//	die('not now');
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
			<h2>Кредиты</h2>
			<div style="line-height: 10px;">
				<?
				$start = microtime(1);
				$BANKGUIDS = (query2array(mysqlQuery("SELECT `idRS_banks`,`RS_banksGUID` FROM `RS_banks`"), 'RS_banksGUID'));
				$clients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'GUID');
				$f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID');
				$insert = [];
				$head = "INSERT INTO `f_credits` (`f_creditsBankAgreementNumber`,`f_creditsSumm`,`f_creditsSummIncInterest`,`f_creditsMonthes`,`f_creditsSalesID`,`f_creditsBankID`) VALUES ";
				foreach ($allsales as $client) {
					if ($client['GUIDКлиента']) {
						$clientSQL = $clients[$client['GUIDКлиента']] ?? null;
						$sales = $client['Договоры'];
						foreach ($sales as $sale) {
							$idf_sale = $f_sales[$sale['GUIDДоговора']]['idf_sales'] ?? false;
							if (!$idf_sale) {
								?><div class="sq err" title="Нет договора по GUID<?= $sale['GUIDДоговора'] ?>" onclick="alert('Нет договора по GUID\r\n<?= $sale['GUIDДоговора'] ?>');"></div><?
								continue;
							}
							if (($sale['Схема оплаты'] ?? '') == 'Кредит') {
								if (!($BANKGUIDS[$sale["GUID Банка"] ?? '']['idRS_banks'] ?? false)) {
									mysqlQuery("INSERT INTO `RS_banks` SET `RS_banksName`='" . mres($sale["Банк"]) . "', `RS_banksShort`='" . mres($sale["Банк"]) . "', `RS_banksGUID`='" . $sale["GUID Банка"] . "'");
									$idbank = mysqli_insert_id($link);
									$BANKGUIDS[$sale["GUID Банка"]] = ['idRS_banks' => $idbank, 'RS_banksGUID' => $sale["GUID Банка"]];
								} else {
									$idbank = $BANKGUIDS[$sale["GUID Банка"] ?? '']['idRS_banks'];
								}
								$f_credits['f_creditsBankAgreementNumber'] = ( $sale['Номер Кредитного Договора'] ?? '');
								$f_credits['f_creditsSumm'] = floatval($sale['Сумма кредита'] ?? 0);
								$f_credits['f_creditsSummIncInterest'] = floatval($sale['Сумма договора'] ?? 0);
								$f_credits['f_creditsMonthes'] = 24;
								$f_credits['f_creditsSalesID'] = $idf_sale;
								$f_credits['f_creditsBankID'] = $BANKGUIDS[$sale['GUID Банка'] ?? '']['idRS_banks'];

								$insert[] = "('" . $f_credits['f_creditsBankAgreementNumber'] . "', "
										. " '" . $f_credits['f_creditsSumm'] . "', "
										. " '" . $f_credits['f_creditsSummIncInterest'] . "', "
										. " '" . $f_credits['f_creditsMonthes'] . "', "
										. " '" . $f_credits['f_creditsSalesID'] . "', "
										. " '" . $f_credits['f_creditsBankID'] . "')";
								if (count($insert) > 25) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="25"></div>';
									} else {
										print '<div class="sq warn" title="mysqlErr"></div>';
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
