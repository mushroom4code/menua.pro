<?php

ini_set('memory_limit', '1G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

function exportCSV($rows = false) {
	global $DATABASE;
	if (!empty($rows)) {
		$name = 'clients_' . $DATABASE . '_' . date("YmdHis") . ".csv";
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $name);
		$output = fopen('php://output', 'w');
		fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
		foreach ($rows as $row) {
			if (!is_array($row)) {
				$row = [$row];
			}
			fputcsv($output, $row, ';');
		}
		exit();
	}
	return false;
}

$DATABASE = 'vita';
//`$DATABASE`.
$sales = query2array(mysqlQuery("SELECT "
//				. "f_sales.*,"
//				. "`subscriptions`.*,"
				. " `idf_sales`,"
				. " `f_salesNumber`,"
				. " `f_salesDate`,"
				. " `f_salesSumm`,"
				. " `idclients`,"
				. " `clientsLName`,"
				. " `clientsFName`,"
				. " `clientsMName`, "
				. " `clientsBDay`, "
				. " (SELECT COUNT(1) FROM `$DATABASE`.`f_payments` WHERE `f_paymentsSalesID` = `idf_sales` AND `f_paymentsType`=1) as `cash`, "
				. " (SELECT COUNT(1) FROM `$DATABASE`.`f_payments` WHERE `f_paymentsSalesID` = `idf_sales` AND `f_paymentsType`=2) as `card`, "
				. " (SELECT COUNT(1) FROM `$DATABASE`.`f_credits` WHERE `f_creditsSalesID` = `idf_sales`) as `bank`, "
				. " (SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ')  FROM `$DATABASE`.`clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`"
				. " FROM "
				. " `$DATABASE`.`clients` "
				. " LEFT JOIN `$DATABASE`.`f_sales` ON (`f_salesClient` = `idclients`)"
//				. " LEFT JOIN ("
//				. "(SELECT"
//				. " SUM(`f_salesContentQty`) as `f_salesContentQty`,"
//				. " `f_salesContentService`,"
//				. " `f_subscriptionsContract`,"
//				. " `f_salesContentPrice`"
//				. " FROM `$DATABASE`.`f_subscriptions` GROUP BY `f_subscriptionsContract`,`f_salesContentService`,`f_salesContentPrice`)"
//				. ""
//				. ") AS `subscriptions` ON (`f_subscriptionsContract` = `idf_sales`)"
				. " WHERE NOT isnull(`idf_sales`)"
//		. " AND `idclients` = 957"
				. "ORDER BY `idclients`,`idf_sales`"
				. ""
				. ""), 'idf_sales');
$f_subscriptions = mysqlQuery("SELECT * FROM `$DATABASE`.`f_subscriptions`");
$servicesApplied = mysqlQuery("SELECT * FROM `$DATABASE`.`servicesApplied` WHERE"
		. " NOT isnull(`servicesAppliedService`)"
		. " AND NOT isnull(`servicesAppliedFineshed`)"
		. " AND NOT isnull(`servicesAppliedContract`)"
		. " AND isnull(`servicesAppliedDeleted`)"
		. "");
$services = query2array(mysqlQuery("SELECT * FROM `$DATABASE`.`services`"), 'idservices');

while ($f_subscription = mfa($f_subscriptions)) {
	if ($f_subscription['f_salesContentQty'] != 0) {
		$sales[(string) $f_subscription['f_subscriptionsContract']]['services'][$f_subscription['f_salesContentService']]['name'] = $services[$f_subscription['f_salesContentService']]['servicesName'];
		$sales[(string) $f_subscription['f_subscriptionsContract']]['services'][$f_subscription['f_salesContentService']]['prices'][$f_subscription['f_salesContentPrice']]['subscriptions'] = ($sales[$f_subscription['f_subscriptionsContract']]['services'][$f_subscription['f_salesContentService']]['prices'][$f_subscription['f_salesContentPrice']]['subscriptions'] ?? 0) + $f_subscription['f_salesContentQty'];
	}
}
while ($serviceApplied = mfa($servicesApplied)) {
	if ($serviceApplied['servicesAppliedQty'] != 0) {
		$sales[(string) $serviceApplied['servicesAppliedContract']]['services'][$serviceApplied['servicesAppliedService']]['name'] = $services[$serviceApplied['servicesAppliedService']]['servicesName'];
		$sales[(string) $serviceApplied['servicesAppliedContract']]['services'][$serviceApplied['servicesAppliedService']]['prices'][$serviceApplied['servicesAppliedPrice']]['servicesApplied'] = (
				$sales[$serviceApplied['servicesAppliedContract']]['services'][$serviceApplied['servicesAppliedService']]['prices'][$serviceApplied['servicesAppliedPrice']]['servicesApplied'] ?? 0) + $serviceApplied['servicesAppliedQty'];
	}
}

$rows = [
	['№',
		'Фамилия',
		'Имя',
		'Отчество',
		'дата рождения',
		'Телефон',
		'Договор (номер, дата)',
		'Сумма по договору',
		'налич, р/сч, эквайринг',
		'наименование процедуры (услуги)',
		'количество',
		'цена',
		'сумма',
		'наименование процедуры (услуги)',
		'количество',
		'цена',
		'сумма',
		'наименование процедуры (услуги)',
		'количество',
		'цена',
		'сумма']
];
$cnt = 0;
$idclients = null;
foreach ($sales as $index => $sale) {
	if ($idclients != $sale['idclients']) {
		$idclients = $sale['idclients'];
		$cnt++;
	}
	foreach (($sale['services'] ?? []) as $service) {
		foreach ($service['prices'] as $price => $servicePrice) {
			$rows[] = [
				$cnt, //№
				$sale['clientsLName'], //Фамилия
				$sale['clientsFName'], //Имя
				$sale['clientsMName'], //Отчество
				$sale['clientsBDay'], //дата рождения
				$sale['phones'], //Телефон
				'№' . ($sale['f_salesNumber'] ?? ('id' . $sale['idf_sales'])) . ' от ' . date("d.m.Y", strtotime($sale['f_salesDate'])), //Договор (номер, дата)
				round($sale['f_salesSumm']), //Сумма по договору
				implode(', ', array_filter(
								[
									($sale['cash'] ? 'налич' : null),
									($sale['card'] ? 'эквайринг' : null),
									($sale['bank'] ? 'р/сч' : null)
								]
						)
				),
				$service['name'], //наименование процедуры (услуги)
				($servicePrice['subscriptions'] ?? 0), //количество
				round($price), //цена
				$price * ($servicePrice['subscriptions'] ?? 0), //сумма
				$service['name'], //наименование процедуры (услуги)
				($servicePrice['servicesApplied'] ?? 0), //количество
				round($price), //цена
				$price * ($servicePrice['servicesApplied'] ?? 0), //сумма
				$service['name'], //наименование процедуры (услуги)
				($servicePrice['subscriptions'] ?? 0) - ($servicePrice['servicesApplied'] ?? 0), //количество
				round($price), //цена
				$price * (($servicePrice['subscriptions'] ?? 0) - ($servicePrice['servicesApplied'] ?? 0) ), //сумма
			];
		}
	}
}
exportCSV($rows);
