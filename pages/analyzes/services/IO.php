<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
//{"action":"addNewTPS_CatalogEntry","itemType":2,"itemName":"123","itemParent":3}

if (R(46)) {
	$OUT = [];
	if (isset($_JSON['action']) && $_JSON['action'] == 'TPS_costsValue') {
		if (mysqlQuery("INSERT INTO `TPS_costs` SET "
						. "`TPS_costsService`='" . FSS($_JSON['TPservice']) . "',"
						. "`TPS_costsValue`='" . FSS($_JSON['summValue']) . "'"
				)) {
			$OUT['success'] = true;
			$OUT['newValue'] = nf($_JSON['summValue']);
		} else {
			$OUT['success'] = false;
			$OUT['error'] = urlencode(mysqli_error($link));
		}
	}
	if (isset($_JSON['action']) && $_JSON['action'] == 'TPS_pricesValue') {
		if (mysqlQuery("INSERT INTO `TPS_prices` SET "
						. "`TPS_pricesService`='" . FSS($_JSON['TPservice']) . "',"
						. "`TPS_pricesValue`='" . FSS($_JSON['summValue']) . "'"
				)) {
			$OUT['success'] = true;
			$OUT['newValue'] = nf($_JSON['summValue']);
		} else {
			$OUT['success'] = false;
			$OUT['error'] = urlencode(mysqli_error($link));
		}
	}



	print json_encode($OUT, 288);
}
