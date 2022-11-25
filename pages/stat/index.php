<?php
$pageTitle = $load['title'] = 'Стат';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(134)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(134)) {
	?>E403R134<?
} else {
	$from = $_GET['from'] ?? date("Y-m-01");
	$to = $_GET['to'] ?? date("Y-m-d");
	?>
	<div class="box neutral">
		<div class="box-body">
			<h2>Стат</h2>
			<div style="display: grid; grid-template-columns: auto auto ; grid-gap: 10px; margin: 10px;">
				<input type="date" onchange="GR({'from': this.value});" value="<?= $from; ?>">
				<input type="date" onchange="GR({'to': this.value});" value="<?= $to; ?>">	
			</div>

			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(2, auto);">
				<div style="display: contents;" class="C B">
					<div>Дата</div>
					<div>Итого</div>

				</div> 

				<?
				$col1 = 0;
				$col2 = 0;
				for ($time = strtotime($from); $time <= strtotime($to); $time += 60 * 60 * 24) {

					$credits = query2array(mysqlQuery("SELECT * FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`) WHERE `f_salesDate` = '" . date("Y-m-d", $time) . "'"));
					$paymentsI_II = query2array(mysqlQuery("SELECT * FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE `f_salesDate` = '" . date("Y-m-d", $time) . "' AND `f_salesType` IN (1,2);"));
					$paymentsI_II_III = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsDate` >= '" . date("Y-m-d", $time) . " 00:00:00' AND  `f_paymentsDate` <= '" . date("Y-m-d", $time) . " 23:59:59';"));

					$total = array_sum(array_column($credits, 'f_creditsSumm')) + array_sum(array_column($paymentsI_II_III, 'f_paymentsAmount'));
					$col1 += $total;
					?>
					<div style="display: contents;">
						<div><?= date("d.m.Y", $time); ?></div>
						<div class="R"><?= ($total); ?></div> 

					</div>
					<?
				}
				?>
				<div style="display: contents;">
					<div class="B R">Итого:</div>
					<div class="B R"><?= ($col1); ?></div> 

				</div>
			</div>


			<h3 style="padding: 20px;">Остатки по банкам</h3>	

			<?
			$unpayedCreditsSQL = "SELECT * "
					. "FROM `f_credits` "
					. "LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`) "
					. "LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
					. "WHERE isnull(`f_creditsPayed`)"
					. " AND not isnull(`idRS_banks`)"
					. " AND `f_salesDate` >= '2022-01-01'"
					. " AND `f_salesDate` <= '" . $to . "'"
					. "";
			$unpayedCredits = query2array(mysqlQuery($unpayedCreditsSQL));
			$banks = [];
			foreach ($unpayedCredits as &$unpayedCredit2) {
				$unpayedCredit2['transactions'] = query2array(mysqlQuery("SELECT * FROM `f_creditsTransactions` WHERE `f_creditsTransactionsCredit` = '" . $unpayedCredit2['idf_credits'] . "' AND `f_creditsTransactionsDate`<='$to'"));

				$banks[$unpayedCredit2['idRS_banks']]['info'] = [
					'name' => ($unpayedCredit2['RS_banksShort'] ?? $unpayedCredit2['idRS_banks'])
				];
				$banks[$unpayedCredit2['idRS_banks']]['credits'][] = $unpayedCredit2['f_creditsSumm'];
				$banks[$unpayedCredit2['idRS_banks']]['payments'][] = array_sum(array_column($unpayedCredit2['transactions'], 'f_creditsTransactionsValue'));
			}
			?>

			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(2, auto);">
				<div style="display: contents;" class="C B">
					<div>Банк</div>
					<div>Сумма</div>

				</div> 
				<?
				$total = 0;
				foreach ($banks as $bank) {
					$summ = array_sum($bank['credits']) - array_sum($bank['payments']);
					$total += $summ;
					?>
					<div style="display: contents;">
						<div><?= $bank['info']['name']; ?></div>
						<div class="R"><?= $summ ?></div>

					</div> 

					<?
				}
				?>
				<div style="display: contents;">
					<div class="B R">Итого:</div>
					<div class="B R"><?= $total; ?></div>

				</div> 
			</div> 

			<?
//			printr($banks);
//			printr($unpayedCredits);
			?>
			<h3 style="padding: 20px;">С/С проданных процедур</h3>	
			<?

			function getPrimeCost($isservice) {//`idservicesApplied`, `servicesAppliedService`
				$consumables = query2array(mysqlQuery(""
								. "SELECT * FROM `servicesPrimecost` "
								. "LEFT JOIN (SELECT * FROM    `WH_goodsIn` AS `a`        INNER JOIN    (SELECT         MAX(`idWH_goodsIn`) AS `idWH_goodsInMAX`    FROM        `WH_goodsIn` LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`)    GROUP BY `WH_goodsNomenclature`) AS `b` ON (`a`.`idWH_goodsIn` = `b`.`idWH_goodsInMAX`) LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`)) as `PC` ON (`servicesPrimecostNomenclature`=`WH_goodsNomenclature`)"
								. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `servicesPrimecostNomenclature`)"
								. "LEFT JOIN `units` ON (`idunits` = `WH_nomenclatureUnits`)"
								. "WHERE `servicesPrimecostService` = '" . $isservice . "'"
								. ""));

				$totalPC = 0;
				foreach ($consumables as $consumable) {


					if ($consumable['WH_goodsPrice'] == null) {
						$consumable['WH_goodsPrice'] = mfa(mysqlQuery("SELECT `WH_goodsPrice` FROM `WH_goods` WHERE `WH_goodsNomenclature` = '" . $consumable['servicesPrimecostNomenclature'] . "' ORDER BY `idWH_goods` DESC LIMIT 1"))['WH_goodsPrice'] ?? null;
					}

					$totalPC += ($consumable['WH_goodsPrice'] && $consumable['servicesPrimecostNomenclatureQty']) ? ($consumable['WH_goodsPrice'] * $consumable['servicesPrimecostNomenclatureQty']) : 0;
				}
				return $totalPC;
			}

			$services = query2array(mysqlQuery("SELECT SUM(f_salesContentQty) as `f_salesContentQty`,f_salesContentService FROM f_sales LEFT JOIN f_subscriptions ON (`f_subscriptionsContract` = `idf_sales`) where f_salesDate>='$from' AND f_salesDate<='$to' group by f_salesContentService;"));
//			printr($services, 1);
			$totalPC = 0;
			$zeroServices = 0;
			foreach ($services as $servicesIndex => $service) {
				$pc = getPrimeCost($service['f_salesContentService']);
				if ($pc) {
					$totalPC += $service['f_salesContentQty'] * $pc;
				} else {
					$zeroServices++;
				}
			}
			?>
			Общая себестоимость проданных процедур: <?= nf($totalPC); ?>р.<br>
			Процедур с неуказанной себестоимостью: <?= $zeroServices; ?>

		</div>
	</div>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
