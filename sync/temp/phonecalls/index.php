<?php

ini_set('memory_limit', '1024M');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!($file = file('pc1.csv'))) {
	die('cannot open file');
};

//3 phone
//5 time

function exportCSV($rows = false) {
	if (!empty($rows)) {
		$name = date("YmdHis") . ".csv";
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . 'codes1.csv');
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

$clientsPhones = query2array(mysqlQuery("SELECT `clientsPhonesPhone` FROM `clientsPhones` "), 'clientsPhonesPhone');
$RCC_phones = query2array(mysqlQuery("SELECT `RCC_phonesNumber` FROM `RCC_phones` "), 'RCC_phonesNumber');
//print count($clientsPhones);
$n = 0;
$result = [];
$isclient = [
	'I' => 0,
	'II' => 0,
	'other' => 0
];
foreach ($file as $row) {
	if ($n > 0) {
		$columns = explode(";", $row);

		$number = '8' . str_replace('"', '', $columns[3]);

		$code = $columns[3][1] . $columns[3][2] . $columns[3][3];
		$time = floatval(str_replace(',', '.', $columns[5]));
//		print $code . '|';
//		print $columns[5] . '|' . $time;
		if (($clientsPhones[$number] ?? false)) {
			$isclient['II'] += $time;
		} elseif (($RCC_phones[$number] ?? false)) {
			$isclient['I'] += $time;
		} else {
			$isclient['other'] += $time;
		}
		$result[$code] = round(($result[$code] ?? 0) + $time, 2);
		if ($n > 200) {

//			die();
		}
	}
	$n++;
}
print_r($isclient);
$rows = [];
ksort($result);
foreach ($result as $code => $dration) {
	$rows[] = [$code, str_replace('.', ',', $dration)];
}
//exportCSV($rows);
//print_r($result);
