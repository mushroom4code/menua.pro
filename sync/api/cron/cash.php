<?php

$DAY = 1;
//В файлах выполняемых на сервере не должно быть разврывов типа <?
//print date("H:i:s");
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
//ICQ_messagesSend_SYNC('sashnone', 'email start');
error_reporting(E_ALL); // выводим все ошибки и предупреждения
//ICQ_messagesSend_SYNC('sashnone', __LINE__);
$date = '2020-12-19';
$now = time();

if ($_GET['date'] ?? false) {
	$dateSQL = " `f_paymentsDate` >= '" . $_GET['date'] . " 00:00:00' "
			. " AND `f_paymentsDate` <= '" . $_GET['date'] . " 23:59:59' ";
} else {
	$dateSQL = " `f_paymentsDate` > '" . date("Y-m-d 00:00:00", time() - $DAY * 24 * 60 * 60) . "' "
			. " AND `f_paymentsDate` < '" . date("Y-m-d 23:59:59", time() - $DAY * 24 * 60 * 60) . "' ";
}

$paymentsSQL = "SELECT * "
		. " FROM `f_payments`"
		. " LEFT JOIN `f_paymentsTypes` ON (`idf_paymentsTypes` = `f_paymentsType`)"
		. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`)"
		. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
		. " LEFT JOIN `entities` ON (`identities` = `f_salesEntity`)"
		. " WHERE "
		. $dateSQL
		. "";
//ICQ_messagesSend_SYNC('sashnone', __LINE__);
$payments = query2array(mysqlQuery($paymentsSQL));
//ICQ_messagesSend_SYNC('sashnone', __LINE__);
$clients = [];
foreach ($payments as $payment) {
	$clients[$payment['identities']][$payment['idclients']]['id'] = $payment['idclients'];
	$clients[$payment['identities']][$payment['idclients']]['name'] = $payment['clientsLName'] . ' ' . $payment['clientsFName'] . ' ' . $payment['clientsMName'];
	$clients[$payment['identities']][$payment['idclients']]['payments'][] = [
		'idf_sales' => $payment['idf_sales'],
		'contractNum' => $payment['f_salesNumber'],
		'contractDate' => date("d.m.Y", strtotime($payment['f_salesDate'])),
		'f_paymentsTypesName' => $payment['f_paymentsTypesName'],
		'f_paymentsAmount' => $payment['f_paymentsAmount'],
		'f_salesSumm' => $payment['f_salesSumm']
	];
}
uasort($clients, function ($a, $b) {
	return mb_strtolower($a['name'] ?? '') <=> mb_strtolower($b['name'] ?? '');
});
//ICQ_messagesSend_SYNC('sashnone', __LINE__);


$body = '<!DOCTYPE html>
<html>
	<head>
		<title>Перечисление денежных средств</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
	.lightGrid {
		border-left: 1px solid silver;
		border-top:  1px solid silver;
		background-color: white;
		border-collapse: collapse;
	}

	.lightGrid tr:hover td {
		background-color: hsl(180, 80%, 95%);;
	}

	.lightGrid td {
		padding: 5px 10px;
		border-right: 1px solid silver;
		border-bottom:  1px solid silver;
	}
	body {
	font-family: Roboto;
	}
</style>
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
	</head>
	<body><div style="display: inline-block;">';

$entities = query2array(mysqlQuery("SELECT * FROM `entities`;"), 'identities');
$entities['']['entitiesName'] = 'Архивный абонемент из 1С без указания исполнителя';
foreach ($clients as $identity => $entity) {
	if (count($entity)) {

		$body .= '<h2 style="margin-top: 20px; margin-bottom: 4px;">' . ($entities[$identity]['entitiesName']) . ' ' . date("d.m.Y", time() - $DAY * 24 * 60 * 60) . '</h2><table class="lightGrid"><tr style="display: contents; font-weight: bolder; text-align: center;">
	<td>Клиент</td>
	<td>Абонемент №</td>
	<td>Сумма платежа</td>
	<td>Тип платежа</td>
	</tr>';
//ICQ_messagesSend_SYNC('sashnone', __LINE__);
		$total = [];
		foreach ($entity as $client) {
			$body .= '<tr>'
					. '<td rowspan="' . count($client['payments']) . '">' . $client['name'] . '</td>';
			$cnt = 0;
			foreach ($client['payments'] as $payment) {
				if ($cnt) {
					$body .= '</tr><tr>';
				}
//		printr($client);
				$total[$payment['f_paymentsTypesName']] = ($total[$payment['f_paymentsTypesName']] ?? 0) + $payment['f_paymentsAmount'];
				$body .= '<td><a href="https://' . SUBDOMEN . 'menua.pro/pages/checkout/payments.php?client=' . $client['id'] . '&contract=' . $payment['idf_sales'] . '" target="_blank">' . $payment['contractNum'] . ' от ' . $payment['contractDate'] . ' на сумму ' . number_format($payment['f_salesSumm'], 2, '.', ' ') . 'р.</a></td>';
				$body .= '<td style="text-align: right;">' . number_format($payment['f_paymentsAmount'], 2, '.', ' ') . 'р.</td>';
				$body .= '<td>' . $payment['f_paymentsTypesName'] . '</td>';
				$cnt++;
			}
			$body .= '</tr>';
		}
//ICQ_messagesSend_SYNC('sashnone', __LINE__);

		$body .= '<tr><td colspan="2" style="text-align: right;">Итого:</td><td style="text-align: right;">' . number_format(array_sum($total), 2, '.', ' ') . 'р.</td><td></td></tr>';
		$body .= '<tr><td rowspan="2" colspan="2" style="text-align: right;">Из них: </td><td style="text-align: right;">' . number_format(($total['Наличные'] ?? 0), 2, '.', ' ') . 'р.</td><td>Наличные</td></tr>';
		$body .= '<tr><td style="text-align: right;">' . number_format(($total['Безналичные'] ?? 0), 2, '.', ' ') . 'р.</td><td>Безналичные</td></tr>';
		$body .= '</table>';
	}
}

$body .= '</div></body></html>';

print $body;
die();

if ($_GET['date'] ?? false) {
	print $body;
	ICQ_messagesSend_SYNC('sashnone', 'cash check');
} else {
	smtpmailu(FIN_EMAIL, FIN_EMAIL_PASSWORD, FIN_EMAIL_NAME, 'Александр', 'sashnone@mail.ru', 'Поступления денежных средств за ' . date("d.m.Y", time() - $DAY * 24 * 60 * 60), $body);
	sleep(1);
	smtpmailu(FIN_EMAIL, FIN_EMAIL_PASSWORD, FIN_EMAIL_NAME, 'Менуа', 'pmenua@inbox.ru', 'Поступления денежных средств за ' . date("d.m.Y", time() - $DAY * 24 * 60 * 60), $body);
	sleep(1);
	smtpmailu(FIN_EMAIL, FIN_EMAIL_PASSWORD, FIN_EMAIL_NAME, 'Марина Владимировна', '9041344@gmail.com', 'Поступления денежных средств за ' . date("d.m.Y", time() - $DAY * 24 * 60 * 60), $body);
	ICQ_messagesSend_SYNC('sashnone', 'email finish');
}

/*

*/