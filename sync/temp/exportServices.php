<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

function exportCSV($rows = false) {
	if (!empty($rows)) {
		$name = date("YmdHis") . ".csv";
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

$sql = "SELECT"
		. " CONCAT_WS('-','infinity','service',LPAD(`idservices`, 6, 0)) AS `uuid`,"
		. " ifnull(`serviceNameShort`,`servicesName`) as `name`,"
		. " if(`servicesEntryType`=1,1,0) as `isGroup`,"
		. " `idservices`,"
		. " `servicesParent`,"
		. " 'шт' as `units`,"
		. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `price`,"
		. " '' as `cost`,"
		. " '' as `remains`,"
		. " '' as `code`,"
		. " `servicesEntryTypesName`,"
		. " `servicesVat`,"
		. " LPAD(`idservices`, 6, 0) as `barcode`,"
		. " '' as `description`,"
		. " '' as `alcoproductioncode`,"
		. " '' as `alcospirits`,"
		. " '' as `alcovolume`,"
		. " '' as `alcocode`,"
		. " if(`servicesEntryType` IN (2,3,4),1,0) as `tosale`"
		. " FROM `services` LEFT JOIN `servicesEntryTypes` ON (`idservicesEntryTypes` = `servicesEntryType`)";
$rows = query2array(mysqlQuery($sql));
exportCSV($rows);
/*    {
        "idservices": 2,
        "servicesParent": null,
        "servicesCode": null,
        "servicesName": "Дарсонваль - укрепление волос",
        "serviceNameShort": null,
        "servicesType": 3,
        "servicesDeleted": "2020-04-17 13:49:04",
        "servicesEquipment": null,
        "servicesDuration": 30,
        "servicesURL": null,
        "servicesAdded": null,
        "servicesEquipped": null,
        "servicescolN804": null,
        "servicesSupplierCode": null,
        "servicesEntryType": 2,
        "servicesNewPlan": null,
        "servicesVat": null
    }*/