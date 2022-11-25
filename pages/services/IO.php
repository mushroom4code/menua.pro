<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (isset($_JSON['action']) && $_JSON['action'] === 'makeOptional') {
	$getConsumablesSQL = "UPDATE `servicesPrimecost` SET `servicesPrimecostIsOptional` = " . (intval($_JSON['val']) == 1 ? '1' : 'null') . " WHERE `idservicesPrimecost` = '" . mres($_JSON['id']) . "'";
	$result = mysqlQuery($getConsumablesSQL);
	if ($result ?? false) {
		print json_encode(['msgs' => [['type' => 'success', 'text' => 'ok', 'autoDismiss' => 500]], 'sql' => $getConsumablesSQL ?? '',], 288);
	} else {
		print json_encode(['msgs' => [['type' => 'error', 'text' => mysqli_error($link)]], 'sql' => $getConsumablesSQL ?? '',], 288);
	}
}



if (isset($_JSON['action']) && $_JSON['action'] === 'makeMultiply') {
	$getConsumablesSQL = "UPDATE `servicesPrimecost` SET `servicesPrimecostMultiply` = " . (intval($_JSON['val']) == 1 ? '1' : 'null') . " WHERE `idservicesPrimecost` = '" . mres($_JSON['id']) . "'";
	$result = mysqlQuery($getConsumablesSQL);
	if ($result ?? false) {
		print json_encode(['msgs' => [['type' => 'success', 'text' => 'ok', 'autoDismiss' => 500]], 'sql' => $getConsumablesSQL ?? '',], 288);
	} else {
		print json_encode(['msgs' => [['type' => 'error', 'text' => mysqli_error($link)]], 'sql' => $getConsumablesSQL ?? '',], 288);
	}
}




if (isset($_JSON['action']) && $_JSON['action'] === 'makeVariable') {
	$getConsumablesSQL = "UPDATE `servicesPrimecost` SET `servicesPrimecostVariable` = " . (intval($_JSON['val']) == 1 ? '1' : 'null') . " WHERE `idservicesPrimecost` = '" . mres($_JSON['id']) . "'";
	$result = mysqlQuery($getConsumablesSQL);
	if ($result ?? false) {
		print json_encode(['msgs' => [['type' => 'success', 'text' => 'ok', 'autoDismiss' => 500]], 'sql' => $getConsumablesSQL ?? '',], 288);
	} else {
		print json_encode(['msgs' => [['type' => 'error', 'text' => mysqli_error($link)]], 'sql' => $getConsumablesSQL ?? '',], 288);
	}
}





if (isset($_JSON['action']) && $_JSON['action'] === 'consumablesSuggestions') {

	if (mb_strlen(trim($_JSON['name'])) >= 3) {

		$nameArr = explode(' ', trim($_JSON['name']));
		foreach ($nameArr as &$nameArrPt) {
			$nameArrPt = " `WH_nomenclatureName` LIKE '%" . mysqli_real_escape_string($link, $nameArrPt) . "%' ";
		}
		$nameSQL = implode(' AND ', $nameArr);
		$getConsumablesSQL = "SELECT * FROM `WH_nomenclature`  LEFT JOIN `units` ON (`idunits` = `WH_nomenclatureUnits`) WHERE ($nameSQL) LIMIT 10";
		$consumables = query2array(mysqlQuery($getConsumablesSQL));

		foreach ($consumables as &$consumable) {
			$item = mfa(mysqlQuery("SELECT * FROM    `WH_goodsIn` AS `a`        INNER JOIN    (SELECT         MAX(`idWH_goodsIn`) AS `idWH_goodsInMAX`    FROM        `WH_goodsIn` LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`)    GROUP BY `WH_goodsNomenclature`) AS `b` ON (`a`.`idWH_goodsIn` = `b`.`idWH_goodsInMAX`) LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`) WHERE
    `WH_goodsNomenclature` = '" . $consumable['idWH_nomenclature'] . "'        AND `WH_goodsInDate` <= '2020-11-02'"));
			$consumable['item'] = $item;
		}
	}




	//	print $pricesSQL = "SELECT * FROM `servicesPrices` AS `a` INNER JOIN (SELECT MAX(`idservicesPrices`) AS `idservicesPricesMAX` FROM `servicesPrices` GROUP BY `servicesPricesService`,`servicesPricesType`) AS `b` ON (`a`.`idservicesPrices` = `b`.`idservicesPricesMAX`) LEFT JOIN servicesPricesTypes ON (idservicesPricesTypes = servicesPricesType) WHERE `WH_goodsNomenclature` = '" . $serviceApplied['servicesAppliedService'] . "'  AND `servicesPricesDate`<='2020-11-02'";


	print json_encode(['success' => false, 'consumables' => $consumables ?? [], 'sql' => $getConsumablesSQL ?? '', 'count' => count($consumables ?? [])], 288);
}




if (($_JSON['action'] ?? '') === 'saveprice') {
	$errors = [];
	$output = [];
	if (is_array($_JSON['p'] ?? false)) {
		foreach ($_JSON['p'] as $type => $price) {
			$oldprice = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE "
							. " `idservicesPrices` = ("
							. "							SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM `servicesPrices` WHERE `servicesPricesService` = " . sqlVON($_JSON['service']) . ""
							. " AND `servicesPricesType` = " . sqlVON($type) . " ) AND `servicesPricesService` = " . sqlVON($_JSON['service']) . ""
							. " AND `servicesPricesType` = " . sqlVON($type) . "  )"
							. " "));

//			$output['msgs'][] = round(($oldprice['servicesPricesPrice'] ?? 0), 2) . ' = ' . round($price, 2);

			if (round(($oldprice['servicesPricesPrice'] ?? 0), 2) != round($price, 2)) {
				if (!mysqlQuery("INSERT INTO `servicesPrices` SET "
								. " `servicesPricesService` = " . sqlVON($_JSON['service']) . ","
								. " `servicesPricesPrice` = " . sqlVON($price, 1) . ","
								. " `servicesPricesType`=" . sqlVON($type) . ","
								. " `servicesPricesSetBy` = " . $_USER['id'] . " "
						)) {
					$errors[] = mysqli_errno($link);
				}
			}
		}//foreach
		if (!count($errors)) {
			$output['success'] = true;
			$output['msgs'][] = ['type' => 'success', 'text' => 'ok', 'autoDismiss' => 500];

			print json_encode($output, 288);
		} else {
			print json_encode(['success' => false, 'errors' => $errors, 'msgs' => [['type' => 'error', 'text' => mysqli_error($link)]]], 288);
		}
	}
}