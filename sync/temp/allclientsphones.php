<?

ini_set('memory_limit', '1024M');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
$phones = query2array(mysqlQuery("SELECT DISTINCT `phone` FROM ("
				. " SELECT `clientsPhonesPhone` AS `phone` FROM `clientsPhones`"
				. " UNION ALL "
				. " SELECT `RCC_phonesNumber` AS `phone` FROM `RCC_phones` WHERE NOT isnull(`RCC_phonesClaimedBy`)"
				. ") AS `phones` WHERE LENGTH(`phone`)>=7 AND `phone`>1000000"
		));

function exportCSV($rows = false) {
	if (!empty($rows)) {
		$name = date("YmdHis") . ".csv";
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $name);
		$output = fopen('php://output', 'w');
		fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
		foreach ($rows as $idrow => $row) {
			if (is_array($row)) {
				foreach ($row as $idcolumn => $column) {
					$row[$idcolumn] = strip_tags($row[$idcolumn]);
				}
			}
			if (!is_array($row)) {
				$row = [$row];
			}
			fputcsv($output, $row, ';');
		}
		exit();
	}
	return false;
}

exportCSV($phones);
printr(count($phones ?? []));

