<?php

function var_dump_ret($mixed = null) {
	ob_start();
	var_dump($mixed);
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
//{
//	"action":"checkBC", "BC":"LLN2GS07192502190121"
//}

if (isset($_JSON['action']) && $_JSON['action'] === 'checkBC') {
	$OUT = [];
	if (R(7)) {
		$result = query2array(mysqlQuery("SELECT * FROM "
						. "`WH_goods`"
						. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `WH_goodsNomenclature`) "
						. " WHERE `WH_goodBarCode` ='" . FSS($_JSON['BC']) . "'"));
		$OUT = $result;
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}

	print json_encode($OUT, 288);
}



if (isset($_JSON['action']) && $_JSON['action'] === 'addNewItem') {
	$OUT = [];
//	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => json_encode($_JSON, 288)]);
	if (R(7)) {

		if (!empty($_JSON['idWH_goods']) && !empty($_JSON['idnomenclature']) && empty($_JSON['WH_goodsNomenclature'])) {
			mysqlQuery("UPDATE `WH_goods` SET `WH_goodsNomenclature` = '" . FSI($_JSON['idnomenclature']) . "' WHERE `idWH_goods`='" . FSI($_JSON['idWH_goods']) . "'");
//			sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'Номенклатура привязана к товару']);
		}
		if (!empty($_JSON['idWH_goods']) && !empty($_JSON['price'])) {



			///старая цена это цена последней закупки данной номенклатуры
			$WH_goodLast = mfa(mysqlQuery("SELECT * FROM `WH_goodsIn` LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`) WHERE `idWH_goodsIn` = (SELECT  MAX(`idWH_goodsIn`) FROM `WH_goodsIn` LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`) WHERE `WH_goodsNomenclature` = '" . ( $_JSON['idnomenclature'] ?? $_JSON['WH_goodsNomenclature']) . "');"));
			if ($WH_goodLast) {
				$priceDelta = round(($_JSON['price'] ?? 0), 2) - ($WH_goodLast['WH_goodsPrice'] ?? 0);
			} else {
				$priceDelta = 0;
			}

			$WH_goodsNomenclature = ( $_JSON['idnomenclature'] ?? $_JSON['WH_goodsNomenclature'] ?? false);

			if ($WH_goodsNomenclature && round($priceDelta, 2) != 0) {
//				sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => '$WH_goodsNomenclature && round($priceDelta, 2) !== 0' . var_dump_ret([round($priceDelta, 2), 0])]);

				$servicesPrimecosts = query2array(mysqlQuery("SELECT "
								. " sum(`servicesPrimecostNomenclatureQty`) as `nomenclatureQty`, "
								. " `servicesPrimecostService` "
								. " FROM  `servicesPrimecost` WHERE `servicesPrimecostNomenclature` = $WH_goodsNomenclature GROUP BY `servicesPrimecostService`"));

				if (array_column($servicesPrimecosts, 'servicesPrimecostService')) {

					$servicesPrimecostServices = query2array(mysqlQuery("SELECT * FROM `services` WHERE `idservices` in (" . implode(',', array_column($servicesPrimecosts, 'servicesPrimecostService')) . ")"), 'idservices');
//					sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "SELECT * FROM `services` WHERE `idservices` in (" . implode(',', array_unique(array_column($servicesPrimecosts, 'servicesPrimecostService'))) . ")"]);
					$priceDeltaChange = [];
					foreach ($servicesPrimecosts as $servicesPrimecost) {
						$priceDeltaChange[$servicesPrimecost['servicesPrimecostService']]['delta'] = $priceDelta * $servicesPrimecost['nomenclatureQty'];
						$priceDeltaChange[$servicesPrimecost['servicesPrimecostService']]['servicesName'] = $servicesPrimecostServices[$servicesPrimecost['servicesPrimecostService']]['servicesName'];
						$insertSQL = "INSERT INTO `servicesPrices` "
								. "(servicesPricesService, servicesPricesPrice, servicesPricesType, servicesPricesSetBy,servicesPricesSetByDatabase) "
								. "SELECT "
								. "" . $servicesPrimecost['servicesPrimecostService'] . " AS `servicesPricesService`,"
								. " (select GREATEST(0,ifnull(`servicesPricesPrice`,0) + (" . round($priceDelta * $servicesPrimecost['nomenclatureQty']) . ")) from servicesPrices WHERE  `idservicesPrices` = ifnull((SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesService`= " . $servicesPrimecost['servicesPrimecostService'] . " AND `servicesPricesType`=5),(SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesService`= " . $servicesPrimecost['servicesPrimecostService'] . " AND `servicesPricesType`=1))) AS `servicesPricesPrice`,"
								. " 5 AS `servicesPricesType`,"
								. "" . $_USER['id'] . " AS `servicesPricesSetBy`,"
								. "'" . DBNAME . "' as `servicesPricesSetByDatabase`";
						mysqlQuery($insertSQL);

						$insertedPrice = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices`=" . mysqli_insert_id($link)));
						if (0) {
							sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => $insertSQL . "\n\n" . "INSERT INTO `servicesPrices` SET"
								. " `servicesPricesService` = " . $insertedPrice['servicesPricesService'] . ", "
								. " `servicesPricesPrice` = " . round($insertedPrice['servicesPricesPrice'], -2) . ", "
								. " `servicesPricesType` = 1,"
								. " `servicesPricesSetBy` = " . $_USER['id']]);
						}


						if (0) {
							mysqlQuery("INSERT INTO `servicesPrices` SET"
									. " `servicesPricesService` = " . $insertedPrice['servicesPricesService'] . ", "
									. " `servicesPricesPrice` = " . round($insertedPrice['servicesPricesPrice'], -2) . ", "
									. " `servicesPricesType` = 1,"
									. " `servicesPricesSetBy` = " . $_USER['id']);
							mysqlQuery("INSERT INTO `servicesPrices` SET"
									. " `servicesPricesService` = " . $insertedPrice['servicesPricesService'] . ", "
									. " `servicesPricesPrice` = " . round($insertedPrice['servicesPricesPrice'] * 1.3, -2) . ", "
									. " `servicesPricesType` = 2,"
									. " `servicesPricesSetBy` = " . $_USER['id']);
						}


						if (mysqli_error($link)) {

						}
//
					}
					if (0) {
						sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => $insertSQL . "\n\n" . "INSERT INTO `servicesPrices` SET"
							. " `servicesPricesService` = " . $insertedPrice['servicesPricesService'] . ", "
							. " `servicesPricesPrice` = " . round($insertedPrice['servicesPricesPrice'], -2) . ", "
							. " `servicesPricesType` = 1,"
							. " `servicesPricesSetBy` = " . $_USER['id']]);
					}

//					sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "foreach, done"]);
				} else {
//					sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'array_column($servicesPrimecosts,\'servicesPrimecostService\')']);
				}



//				sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'Приход. Изменение цены ' . $WH_goodLast['WH_goodsName'] . ': Было: ' . ($WH_goodLast['WH_goodsPrice'] ?? 0) . ', стало: ' . ($_JSON['price'] ?? 0) . ', разница: ' . (($priceDelta ?? false) && $priceDelta > 0 ? '+' : '') . ($priceDelta ?? 'Не найдено') . "\nЗатронуты услуги: \n" . mb_substr(print_r(($priceDeltaChange ?? 'НЕТ УСЛУГ'), 1), 0, 5000)]);
			} else {
//				sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'Цена не поменялась.']);
			}

			mysqlQuery("UPDATE `WH_goods` SET `WH_goodsPrice` = '" . FSS($_JSON['price']) . "' WHERE `idWH_goods`='" . FSI($_JSON['idWH_goods']) . "'");
		}

		$units = mfa(mysqlQuery("SELECT `WH_nomenclatureUnits` FROM `WH_nomenclature` WHERE `idWH_nomenclature`='" . FSI($_JSON['idnomenclature']) . "'"))['WH_nomenclatureUnits'];
//		printr($units);
		if (mysqlQuery("INSERT INTO `WH_goodsIn` SET "
						. "`WH_goodsInGoodsId` = '" . FSI($_JSON['idWH_goods']) . "',"
						. " `WH_goodsInQty` = '" . FSS($_JSON['qty']) . "',"
						. " `WH_goodsInPrice` = '" . round($_JSON['price'], 3) . "',"
						. " `WH_goodsInSupplier` = " . (FSS($_JSON['idsuppliers']) ? FSS($_JSON['idsuppliers']) : "null") . ","
						//
						//
						. " `WH_goodsInUnits` = " . ($units ?? "null") . ","
						//
						//
						. " `WH_goodsInDate` = " . (FSS($_JSON['date']) == date("Y-m-d") ? ' CURRENT_TIMESTAMP' : (" '" . FSS($_JSON['date']) . " 00:00:00'"))
						. "")) {
			$OUT['success'] = true;
		} else {
			$OUT['success'] = false;
			$OUT['errors'][] = "Произошла ошибка <br><br>" . mysqli_error($link);
		}
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}


	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}


if (isset($_JSON['action']) && $_JSON['action'] === 'getIn') {
	$OUT = [];
	if (R(7)) {
		$OUT['entries'] = [];
		$result = mysqlQuery(""
				. "SELECT *, "
				. "`WH_goodsName` as `name`,"
				. "`WH_goodsInQty` as `qty`"
				. " FROM `WH_goodsIn`"
				. "LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`)"
				. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `WH_goodsNomenclature`)"
				. "LEFT JOIN `units` ON (`idunits` = `WH_goodsInUnits`)"
				. "WHERE `WH_goodsInDate` BETWEEN '" . FSS($_JSON['date']) . " 00:00:00' AND '" . FSS($_JSON['date']) . " 23:59:59'");
		while ($row = mfa($result)) {
			$OUT['entries'][] = $row;
		}
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}



if (isset($_JSON['action']) && $_JSON['action'] === 'stocktaking') {
	$OUT = [];
	if (R(7)) {
		$OUT['entries'] = [];
		$result = mysqlQuery(""
				. "SELECT "
				. "`idgoods`,"
				. "`goodsName` as `name`,"
				. "`unitsName`,"
				. "SUM(`stocktakingQty`) as `qty`"
				. " FROM `stocktaking`"
				. "LEFT JOIN `goods` ON (`idgoods` = `stocktakingItem`)"
				. "LEFT JOIN `units` ON (`idunits` = `goodsUnit`)"
				. "WHERE `stocktakingDate` BETWEEN '" . FSS($_JSON['date']) . " 00:00:00' AND'" . FSS($_JSON['date']) . " 23:59:59'"
				. "GROUP BY `stocktakingItem`");
		while ($row = mfa($result)) {
			$OUT['entries'][] = $row;
		}
		$OUT['stocktaking'] = true;
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}

if (isset($_JSON['action']) && $_JSON['action'] === 'saveConsignmentNote') {
	$OUT = [];
	$errors = [];
	if (R(7)) {
		if ($_USER['id'] == 176) {
			printr($_JSON);
			die();
//			Array
//(
//    [action] => saveConsignmentNote
//    [date] => 2020-05-04
//    [consignmentNote] => Array
//          (
//            [CNnum] => 111
//            [CNdate] => 2020-04-27
//            [idsuppliers] => 52
//            [suppliersKPP] => 31
//            [company] => 1
//            [items] => Array
//                (
//                    [0] => Array
//                        (
//                            [idgoods] =>
//                            [name] => coca cola 0.33л ж.б.
//                            [barcode] => 5449000131805
//                            [type] => 1
//                            [units] => 13
//                            [qty] => 1
//                            [nomenclatureID] =>
//                            [nomenclatureName] =>
//                            [nomenclatureQty] => 1
//                            [nomenclatureUnits] => 13
//                            [WHunits] => 13
//                            [WHqty] => 1
//                            [vatSumm] => 0
//                            [summIncVat] => 39
//                            [supplier] => 52
//                            [summExVat] => 39
//                            [newVatPerc] => 0
//                        )
//                )
//        )
//
//)
		}



		if (
				isset($_JSON['consignmentNote']) &&
				isset($_JSON['consignmentNote']['items']) &&
				is_array($_JSON['consignmentNote']['items']) &&
				count($_JSON['consignmentNote']['items'])
		) {

			$insertFields = [];
			if (isset($_JSON['consignmentNote']['CNnum']) && !empty(FSS($_JSON['consignmentNote']['CNnum']))) {
				$insertFields[] = "`WH_consignmentNotesNumber` = '" . FSS($_JSON['consignmentNote']['CNnum']) . "'";
			}

			if (isset($_JSON['consignmentNote']['CNdate']) && !empty(FSS($_JSON['consignmentNote']['CNdate']))) {
				$insertFields[] = "`WH_consignmentNotesDate` = '" . FSS($_JSON['consignmentNote']['CNdate']) . "'";
			} else {
				if (isset($_JSON['date'])) {
					if (FSS($_JSON['date']) == date("Y-m-d")) {
						$insertdateSQL = ' CURRENT_TIMESTAMP';
					} else {
						$insertdateSQL = (" '" . FSS($_JSON['date']) . " 00:00:00'");
					}
				} else {
					$insertdateSQL = ' CURRENT_TIMESTAMP';
				}
				$insertFields[] = "`WH_consignmentNotesDate` = $insertdateSQL";
			}

			if (isset($_JSON['consignmentNote']['idsuppliers']) && !empty(FSS($_JSON['consignmentNote']['idsuppliers']))) {
				$insertFields[] = "`WH_consignmentNotesSupplier` = '" . FSI($_JSON['consignmentNote']['idsuppliers']) . "'";
			}

			if (isset($_JSON['consignmentNote']['suppliersKPP']) && !empty(FSS($_JSON['consignmentNote']['suppliersKPP']))) {
				$insertFields[] = "`WH_consignmentNotesKPP` = '" . FSI($_JSON['consignmentNote']['suppliersKPP']) . "'";
			}
			if (isset($_JSON['consignmentNote']['company']) && !empty(FSS($_JSON['consignmentNote']['company']))) {
				$insertFields[] = "`WH_consignmentNotesCompany` = '" . FSI($_JSON['consignmentNote']['company']) . "'";
			}
			$insertFields[] = "`WH_consignmentNotesUser` = '" . $_USER['id'] . "'";

			if (mysqlQuery("INSERT INTO `WH_consignmentNotes` SET " . implode(",", $insertFields)) && $idconsignmentNotes = mysqli_insert_id($link)) {
				foreach ($_JSON['consignmentNote']['items'] as $item) {
					if (!($item['nomenclatureID'] ?? 0)) {
						if (mysqlQuery("INSERT INTO `WH_nomenclature` SET "
										. " `WH_nomenclatureName`='" . FSS($item['nomenclatureName']) . "',"
										. " `WH_nomenclatureUnits`='" . FSS($item['nomenclatureUnits']) . "', "
										. " `WH_nomenclatureEntryType`='2', "
										. " `WH_nomenclatureType`='" . FSS($item['type']) . "'"
								)) {
							$item['nomenclatureID'] = mysqli_insert_id($link);
						} else {
							$errors[] = 'Error while inserting NEW Nomenclature';
						}
					}
					if (!($item['idgoods'] ?? 0)) {
						if (mysqlQuery("INSERT INTO `WH_goods` SET "
										. "`WH_goodsName` = '" . FSS($item['name']) . "',"
										. "`WH_goodBarCode` = '" . FSS($item['barcode']) . "',"
										. "`WH_goodsUnits` = '" . FSS($item['units']) . "',"
										//
										. "`WH_goodsNomenclature` = '" . FSI($item['nomenclatureID']) . "', "
										. "`WH_goodsNomenclatureQty` = '" . FSS($item['nomenclatureQty']) . "',"
										//
										. "`WH_goodsWHUnits` = '" . FSS($item['WHunits']) . "',"
										. "`WH_goodsWHQty` = '" . FSI($item['WHqty']) . "' "
								)) {
							$item['idgoods'] = mysqli_insert_id($link);
						} else {
							$errors[] = 'Error while inserting NEW Item';
						}
					} else {
						if (mysqlQuery("UPDATE `WH_goods` SET "
										. "`WH_goodsName` = '" . FSS($item['name']) . "',"
										. "`WH_goodBarCode` = '" . FSS($item['barcode']) . "',"
										. "`WH_goodsUnits` = '" . FSS($item['units']) . "',"
										//
										. "`WH_goodsNomenclature` = '" . FSI($item['nomenclatureID']) . "', "
										. "`WH_goodsNomenclatureQty` = '" . FSS($item['nomenclatureQty']) . "',"
										//
										. "`WH_goodsWHUnits` = '" . FSS($item['WHunits']) . "',"
										. "`WH_goodsWHQty` = '" . FSI($item['WHqty']) . "' "
										. "WHERE `idWH_goods` = '" . FSI($item['idgoods']) . "'"
								)) {

						} else {
							$errors[] = 'Error while UPDATING Item';
						}
					}

					if (mysqlQuery("INSERT INTO `WH_goodsIn` SET "
									. " `WH_goodsInGoodsId` = '" . FSI($item['idgoods']) . "',"
									. " `WH_goodsInQty` = '" . $item['qty'] . "',"
									. " `WH_goodsInPrice` = '" . round($item['summIncVat'] / $item['qty'], 3) . "',"
									. " `WH_goodsInSummIncVat` = '" . ($item['summIncVat']) . "',"
									. " `WH_goodsInVatSumm` = " . ($item['vatSumm'] ?? 'null') . ","
									. " `WH_goodsInUnits` = " . ($item['WHunits'] ?? 'null') . ","
									. " `WH_goodsInCN` = '" . ($idconsignmentNotes) . "',"
									. " `WH_goodsInUser` = '" . ($_USER['id']) . "',"
									. " `WH_goodsInSupplier` = " . (FSS($_JSON['consignmentNote']['idsuppliers']) ? FSS($_JSON['consignmentNote']['idsuppliers']) : "null") . ","
									. " `WH_goodsInDate` = " . (FSS($_JSON['date']) == date("Y-m-d") ? ' CURRENT_TIMESTAMP' : (" '" . FSS($_JSON['date']) . " 10:00:00'"))
									. "")) {

					} else {
						$errors[] = 'Error while inserting Item';
					}
				}
			} else {
				$errors[] = 'Error while inserting CN';
			}
		} else {
			$errors[] = 'Insufficient data';
		}


		if (!count($errors)) {
			$OUT['success'] = true;
		} else {
			$OUT['success'] = false;
			$OUT['errors'] = $errors;
		}
	} else {
		$OUT['msgs'][] = 'У Вас нет прав доступа к данной функции';
	}
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}


