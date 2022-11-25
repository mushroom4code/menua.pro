<?php
header('Content-Encoding: none;');
$pageTitle = 'Импорт платежей';
die();
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {
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
	<?= $f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID'); ?>


	<div class="box neutral">
		<div class="box-body">
			<h2>Оплаты нал/безнал</h2>
			<div style="line-height: 10px;">
				<?
				$json = json_decode(file_get_contents("payments.json"), true);
				$payments = $json['Оплаты'];
				$start = microtime(1);
				$insert = [];
				$head = "INSERT INTO `f_payments` (`f_paymentsSalesID`,`f_paymentsType`,`f_paymentsAmount`,`f_paymentsDate`,`f_paymentsUser`) VALUES ";

				foreach ($payments as $payment) {

//						printr($payment);
					if ($payment['GUIDДоговора'] ?? false) {


						$f_payments['f_paymentsSalesID'] = $f_sales[$payment['GUIDДоговора']]['idf_sales'] ?? false;

						if (!$f_payments['f_paymentsSalesID']) {
							continue;
						}
						//, , , , , 
						if ($payment['Источник'] == 'Касса') {
							$f_payments['f_paymentsType'] = 1;
						}
						if ($payment['Источник'] == 'Оплата картой') {
							$f_payments['f_paymentsType'] = 2;
						}
						if ($payment['Источник'] == 'Банк') {
							$f_payments['f_paymentsType'] = 2;
						}
						$f_payments['f_paymentsAmount'] = $payment['Сумма'];
						$f_payments['f_paymentsDate'] = date("Y-m-d H:i:s", strtotime($payment['Дата платежа']));
						$f_payments['f_paymentsUser'] = 176;

						$insert[] = "("
								. "'" . $f_payments['f_paymentsSalesID'] . "',"
								. "'" . $f_payments['f_paymentsType'] . "',"
								. "'" . $f_payments['f_paymentsAmount'] . "',"
								. "'" . $f_payments['f_paymentsDate'] . "',"
								. "'" . $f_payments['f_paymentsUser'] . "')";
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
					} else {
						print 'NO GUIDДоговора';
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
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
