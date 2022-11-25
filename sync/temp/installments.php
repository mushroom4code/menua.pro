<?php
$pageTitle = 'Рассрочки';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (0) {
	?>E403R27<? } else {
	?>

	<div class="box neutral">
		<div class="box-body" style="padding: 10px;">
			<?
			$clients = array_unique(array_column(query2array(mysqlQuery("SELECT `f_salesClient` FROM `f_installments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_installmentsSalesID`) where  `f_salesDate`>'2020-06-01 00:00:00'" . " AND NOT isnull(`f_salesNumber`)")), 'f_salesClient'));
			$contracts = query2array(mysqlQuery("SELECT"
							. " * "
							. "FROM `f_sales` "
							. "LEFT JOIN `clients` ON (`idclients` = `f_salesClient`) "
							. "LEFT JOIN `f_installments` ON (`f_installmentsSalesID` = `idf_sales`) "
							. "WHERE `f_salesClient` IN (" . implode(',', $clients) . ")"
							. "AND `f_salesDate`>'2020-06-01 00:00:00'"
							. "AND NOT isnull(`f_salesNumber`)"));

//			printr($contracts);

			$clientsContracts = [];
			foreach ($contracts as $contract) {
				$clientsContracts[$contract['f_salesClient']]['contracts'][] = $contract;
			}
//			printr($clientContracts);


			$start_date = '2020-06-01';
			$end_date = '2020-07-31';
			?>
			<div style="display: grid; grid-template-columns: auto auto; grid-gap: 0px 10px;">
				<?
				foreach ($clientsContracts as $clientContracts) {
//					printr($clientContracts);
					?>

					<div style="display: contents;">
						<div><a target="blank" href="https://menua.pro/pages/checkout/payments.php?client=<?= $clientContracts['contracts'][0]['idclients']; ?>">
								<?= $clientContracts['contracts'][0]['idclients']; ?>] <?= $clientContracts['contracts'][0]['clientsLName']; ?> <?= $clientContracts['contracts'][0]['clientsFName']; ?>
							</a>
						</div>
						<?
						usort($clientContracts['contracts'], function($a, $b) {
							return $a['f_salesTime'] <=> $b['f_salesTime'];
						});
//							printr($clientContracts);
						?>
						<div><?
							foreach ($clientContracts['contracts'] as $clientContract) {

								$payments = (mfa(mysqlQuery("SELECT sum(f_paymentsAmount) as `summ` FROM `f_payments` WHERE `f_paymentsSalesID` = " . $clientContract['idf_sales'] . ""))['summ'] ?? 0);
								$credits = (mfa(mysqlQuery("SELECT `f_creditsSumm` FROM `f_credits` WHERE `f_creditsSalesID` = " . $clientContract['idf_sales'] . ""))['f_creditsSumm'] ?? 0);

								$border = 'border: 1px solid gray;';

								$background = 'background-color: silver;';
								if ($clientContract['idf_installments'] ?? false) {
									$border = 'border: 2px solid red;';
								}

								if ($payments + $credits == 0) {
									$background = 'background-color: pink;';
								}
								if ($payments + $credits < $clientContract['f_salesSumm']) {
									$background = 'background-color: yellow;';
								}
								if ($payments + $credits >= $clientContract['f_salesSumm']) {
									$background = 'background-color: lightgreen;';
								}
//								$clientContract['f_salesSumm'];
								?>
							<a target="_blank" style="padding: 0px;" href="https://menua.pro/pages/checkout/payments.php?client=<?= $clientContract['idclients']; ?>&contract=<?= $clientContract['idf_sales']; ?>"><b style="width: 20px; height: 20px; display: inline-block; <?= $background; ?> <?= $border; ?>" title="<?= $clientContract['idf_sales']; ?>] <?= ($payments + $credits); ?> из <?= $clientContract['f_salesSumm']; ?>р. <?= $clientContract['f_salesDate']; ?>"></b></a>
									<?
//								printr($payments);
//								printr($credits);
								}
								?>
						</div>
					</div>

					<?
				}
				?>
			</div>
		</div>
	</div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
