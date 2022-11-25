<?php
$pageTitle = 'Оформление договора';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_GET['delete'] ?? false) {
	$url = "https://dclubs.ru/evotor/orders/api/3rdparty/v2/order/" . $_GET['delete'];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	$headers = array(
		"Authorization: Bearer ".EVOTORBearer,
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);

	header("Location: " . GR2(['delete' => null]));
	die();
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!1) {
	?>E403R26<?
} else {
	?>


	<div style="padding: 10px;">
		<div style=" display: inline-block;">
			<div class="box neutral">
				<div class="box-body">
					<h2>Касса</h2>
					<?php
					$url = "https://dclubs.ru/evotor/orders/api/3rdparty/v2/order";

					$curl = curl_init($url);
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

					$headers = array(
						"Accept: application/json",
						"Authorization: Bearer ".EVOTORBearer,
					);
					curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//for debug only!
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

					$resp = json_decode(curl_exec($curl), 1);
					curl_close($curl);
					?>
					<div style="display: inline-block;">
						<div class="lightGrid" style="display: grid; grid-template-columns: repeat(11,auto);">
							<div style="display: contents;">
								<div class="C B">N</div>
								<div class="C B">Дата</div>
								<div class="C B">Клиент</div>
								<div class="C B">Абонемент</div>
								<div class="C B">Стоимость<br>абонемента</div>
								<div class="C B">Сумма процедур</div>
								<div class="C B">Стоимость<br>по кассе</div>
								<div class="C B">Сумма процедур</div>
								<div class="C B">Оплачено<br>касса</div>
								<div class="C B">Оплачено<br>црм</div>
								<div class="C B">X</div>
							</div>
							<?
							$n = 0;
							uasort($resp, function ($a, $b) {
								return $a['period'] <=> $b['period'];
							});
							foreach (($resp ?? []) as $index => $receipt) {
								$resp[$index]['receipt_json'] = json_decode($resp[$index]['receipt_json'], 1);
//								$resp[$index]['receipt'] = json_decode($resp[$index]['receipt'], 1);
								$n++;

								if ($receipt['number'] ?? false) {
									$client = mfa(mysqlQuery("SELECT * FROM `clients` LEFT JOIN `f_sales` ON (`f_salesClient` = `idclients`) WHERE `idf_sales`='" . $receipt['number'] . "'"));
									$fsale = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales` = '" . $receipt['number'] . "'"));

									if ($fsale) {
										$subscriptions = mfa(mysqlQuery("SELECT SUM(`f_salesContentPrice`*`f_salesContentQty`) as `summ` FROM `f_subscriptions` WHERE `f_subscriptionsContract` = '" . $fsale['idf_sales'] . "' GROUP BY `f_subscriptionsContract`"));

										$f_salesSumm = round($fsale['f_salesSumm'], 2);
										$subscriptionssumm = round($subscriptions['summ'], 2);
										$f_payments = mfa(mysqlQuery("SELECT sum(`f_paymentsAmount`) AS `summ` FROM `f_payments` WHERE `f_paymentsSalesID`='" . $fsale['idf_sales'] . "'"))['summ'];
									} else {
										$f_salesSumm = 'N/A';
										$subscriptionssumm = 'N/A';
									}
								}





								$receipttotal = round($receipt['total'], 2);
								$receiptpositionssumm = round(array_sum(array_map(function ($position) {
													return $position['price'] * $position['quantity'];
												}, $receipt['positions'])), 2);
								$payed = $resp[$index]['receipt_json']['data']['totalAmount'] ??
										array_sum(array_column($receipt['receipt']['body']['payments'] ?? [], 'sum')) ?? 0;
								?><?
								$bgcolor = (count(array_count_values([
											(int) $f_payments,
											(int) $payed,
											(int) $receiptpositionssumm,
											(int) $receipttotal,
											(int) $subscriptionssumm,
											(int) $f_salesSumm
										])) == 1) ? 'initial' : 'pink';
								?>
								<div style="display: contents;">
									<div class="R" style="background-color: <?= $bgcolor; ?>"><?= $n; ?></div>
									<div class="R" style="background-color: <?= $bgcolor; ?>"><?= date("d.m.Y H:i", $receipt['period']); ?></div>
									<div class="" style="background-color: <?= $bgcolor; ?>"><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients'] ?? ''; ?>"><?= $receipt['client'] ?? 'ОТСУТСТВУЕТ'; ?></a></div>
									<div class="" style="background-color: <?= $bgcolor; ?>"><a target="_blank" href="/pages/checkout/payments.php?client=<?= $client['idclients'] ?? ''; ?>&contract=<?= $receipt['number'] ?? ''; ?>"><?= $receipt['number'] ?? 'ОТСУТСТВУЕТ'; ?></a></div>
									<div style="background-color: <?= $bgcolor; ?>"><?= $f_salesSumm; ?></div>
									<div style="background-color: <?= $bgcolor; ?>"><?= $subscriptionssumm; ?></div>
									<div style="background-color: <?= $bgcolor; ?>"><?= $receipttotal; ?></div>
									<div style="background-color: <?= $bgcolor; ?>"><?= $receiptpositionssumm; ?></div>
									<div style="background-color: <?= $bgcolor; ?>"><?= $payed; ?></div>
									<div style="background-color: <?= $bgcolor; ?>"><?= $f_payments; ?></div>
									<div style="background-color: <?= $bgcolor; ?>"><?
										if (1 || !$payed) {
											?>
											<a href = "/pages/checkout/evotor/?delete=<?= $receipt['uuid'] ?? ''; ?>">X</a>
											<?
										}
										?></div>



								</div>
								<?
							}
							?>
						</div>
					</div>
					<? //printr($resp, 1);   ?>
				</div>
			</div>
		</div>
	</div>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
