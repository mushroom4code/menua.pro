<?php

ini_set('memory_limit', '1G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$DATABASE = 'vita';

$DATABASE = 'warehouse';



function exportCSV($rows = false) {
	global $DATABASE;
	if (!empty($rows)) {
		$name = 'sales_' . $DATABASE . '_' . date("YmdHis") . ".csv";
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

//mysqlQuery("UPDATE vita.f_sales set f_salesUUID = (SELECT uuid()) where isnull(f_salesUUID);");


/*            "GUID": "dc7fb01f-e3c7-11e7-aaa0-2c768a5d8193",
  "": "Грязнова",
  "": "Людмила",
  "": "Николаевна",
  "": "1943-09-14",
  "": null,
  "clientsAddedBy": 176,
  "clientsAddedAt": "2021-02-05 07:18:43",
  "": null,
  "clientsCallerId": null,
  "clientsCallerAdmin": null,
  "clientsSource": 4,
  "clientsOldSince": "2017-12-18",
  "clientsControl": null,
  "clientsPassedAway": null,
  "clientsPassedAwayBy": null,
  "clientsHash": null,
  "clientsTIN": null,
  "clientsDatabase": null,
  "clientsContractDate": null,
  "clientscQR": null,
  "clientscQRset": null,
  "clientsTG": null,
  "clientsUUID": "36a53e1a-c61f-11ec-a5a3-a4bb6dd075fa" */

//mysqlQuery("UPDATE $DATABASE.clients set clientsUUID = (SELECT uuid()) where isnull(clientsUUID);");

$sales = query2array(mysqlQuery("SELECT * FROM"
				. " `$DATABASE`.`f_sales`"
				. " ORDER BY `f_salesDate`"));
$rows = [
	[
		'Идентификатор договора',
		'Номер договора',
		'дата заключения',
		'Примечание',
	]
];
foreach ($sales as $sale) {

	$rows[] = [
		$sale['idf_sales'],
		($sale['f_salesIsAppendix'] ? 'Приложение ' : '') . $sale['f_salesNumber'],
		$sale['f_salesDate'] ? date("d.m.Y", strtotime($sale['f_salesDate'])) : ''
	];
}

exportCSV($rows);
//header('Content-disposition: attachment; filename=sales_export ' . date("y-m-d H-i-s") . '.json');
//header('Content-type: application/json');

//print(json_encode([
//			'clients' => $clients,
//			'sales' => $sales,
//				], 288 + 128));
