<?php
$load['title'] = $pageTitle = 'Поступления денежных средств';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!R(118)) {
	?>E403R118<?
} else {
	$from = $_GET['from'] ?? date("Y-m-d");
	$to = $_GET['to'] ?? $_GET['from'] ?? date("Y-m-d");
	$f_payments = query2array(mysqlQuery("SELECT * FROM `f_payments` "
					. "left join `f_sales` on (`idf_sales`= `f_paymentsSalesID`)"
					. "left join `clients` on (`idclients` = `f_salesClient`)"
					. " WHERE `f_paymentsDate`>='" . $from . " 00:00:00' AND `f_paymentsDate`<='" . $to . " 23:59:59' ;"));

	$f_credits = query2array(mysqlQuery("SELECT * FROM `f_credits` left join `f_sales` on (`idf_sales`= `f_creditsSalesID`)"
					. "left join `clients` on (`idclients` = `f_salesClient`)"
					. " WHERE `f_salesDate`>='" . $from . "' AND  `f_salesDate`<='" . $to . "';"));

	$payments = [];

	foreach ($f_payments as $f_payment) {
		$payments[] = [
			'date' => date("d.m.Y", strtotime($f_payment['f_paymentsDate'])),
			'name' => $f_payment['clientsLName'] . ' ' . $f_payment['clientsFName'] . ' ' . $f_payment['clientsMName'],
			'summ' => $f_payment['f_paymentsAmount'],
			'type' => ['1' => 'кэш', '2' => 'экв'][$f_payment['f_paymentsType']],
		];
	}

	foreach ($f_credits as $f_credit) {
		$payments[] = [
			'date' => date("d.m.Y", strtotime($f_credit['f_salesDate'])),
			'name' => $f_credit['clientsLName'] . ' ' . $f_credit['clientsFName'] . ' ' . $f_credit['clientsMName'],
			'summ' => $f_credit['f_creditsSumm'],
			'type' => 'банк',
		];
	}

	include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
	usort($payments, function ($a, $b) {
		if ($a['date'] <=> $b['date']) {
			return $a['date'] <=> $b['date'];
		}
		return $a['name'] <=> $b['name'];
	});
//	printr($f_payments);
//	printr($f_credits);
	printr($payments);

	$f_paymentsSumm = array_sum(array_column($f_payments, 'f_paymentsAmount'));
	$f_creditsSumm = array_sum(array_column($f_credits, 'f_creditsSumm'));
	?>
	<div class="box neutral">
		<div class="box-body">
			<h3>
				<input type="date" autocomplete="off" onchange="GETreloc('from', this.value);" value="<?= $from; ?>"><br>
				<input type="date" autocomplete="off" onchange="GETreloc('to', this.value);" value="<?= $to; ?>">

			</h3>
			Кэш+экваиринг: <?= $f_paymentsSumm; ?><br>
			Кредиты: <?= $f_creditsSumm; ?><br>
			Итого:<?= nf($f_paymentsSumm + $f_creditsSumm); ?>
			<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto;">

				<div style="display: contents; font-weight: bolder; text-align: center;">
					<div>Клиент</div>
					<div>Абонемент №</div>
					<div>Сумма платежа</div>
					<div>Тип платежа</div>
				</div>

				<?
				foreach (($payments ?? []) as $payment) {
					?>
					<div style="display: contents;">
						<div><?= $payment['name'] ?></div>
						<div></div>
						<div><?= $payment['summ'] ?? '??'; ?></div>
						<div><?= $payment['type'] ?? '??'; ?></div>
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
