<?php

ini_set('memory_limit', '1G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$DATABASE = 'vita';

//$DATABASE = 'warehouse';

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

$clients = query2array(mysqlQuery("SELECT "
				. " `clientsLName`,"
				. " `clientsFName`,"
				. " `clientsMName`,"
				. " `clientsBDay`,"
				. " (SELECT GROUP_CONCAT(DISTINCT `clientsPhonesPhone` SEPARATOR ', ')  FROM $DATABASE.`clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`"
				. ""
				. ""
				. " FROM $DATABASE.`clients` order by clientsLName,clientsFName,clientsMName,clientsBDay"));

$rows = [
	[
		'ФИО',
		'Дата рождения',
		'телефон(ы)',
		'Примечание',
	]
];

foreach ($clients as $client) {
	if (mb_strlen($client['clientsLName'] . $client['clientsFName'] . $client['clientsMName']) < 13) {
		continue;
	}
	$rows[] = [
		$client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'],
		$client['clientsBDay'] ? date("d.m.Y", strtotime($client['clientsBDay'])) : '',
		$client['phones'],
		''
	];
}
exportCSV($rows);
