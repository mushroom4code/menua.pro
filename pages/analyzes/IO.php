<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
//{"action":"addNewTPS_CatalogEntry","itemType":2,"itemName":"123","itemParent":3}

if (R(46)) {

	if (isset($_JSON['action']) && $_JSON['action'] == 'addNewTPS_CatalogEntry') {
		if (mysqlQuery("INSERT INTO `TPS_Catalog` SET "
						. "`TPS_CatalogParent`=" . ($_JSON['itemParent'] ? FSI($_JSON['itemParent']) : 'null') . ", "
						. "`TPS_CatalogName`='" . FSS($_JSON['itemName']) . "', "
						. "`TPS_CatalogEntryType`='" . FSI($_JSON['itemType']) . "'")) {
			$OUT['success'] = true;
		} else {
			$OUT['success'] = false;
			$OUT['error'] = urlencode(mysqli_error($link));
		}
	}
	print json_encode($OUT, 288);
}
