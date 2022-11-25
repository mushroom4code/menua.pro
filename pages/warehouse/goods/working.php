<?php

if (!empty($_JSON['loadGoods'])) {
	$OUT = [];

	if (isset($_JSON['parent'])) {
		$_JSON['parent'] = $_JSON['parent'] === 'null' ? null : $_JSON['parent'];
		if ($_JSON['parent']) {
			$OUT['parentLVL'] = mysqli_result(mysqlQuery("SELECT `goodsParent` FROM `goods` WHERE `idgoods` = '" . $_JSON['parent'] . "'"), 0) ?? 'null';
		}
	}
	$qtext = "SELECT *, IFNULL((SELECT 
            MAX(`stocktakingDate`)
        FROM
            `stocktaking`
        WHERE
            `stocktakingItem` = `idgoods`),'0000-01-01 00:00:00') AS `lastSTdate`,
    (IFNULL((SELECT 
                    `stocktakingQty`
                FROM
                    `stocktaking`
                WHERE
                    `stocktakingItem` = `idgoods`
                        AND `stocktakingDate` = `lastSTdate`
                LIMIT 1),
            0) + IFNULL((SELECT 
                    SUM(`inQty`)
                FROM
                    `in`
                WHERE
                    `inGoodsId` = `idgoods`
                        AND `inTime` >= `lastSTdate`
                LIMIT 1),
            0) - IFNULL((SELECT 
                    SUM(`outQty`)
                FROM
                    `out`
                WHERE
                    `outItem` = `idgoods`
                        AND `outDate` >= `lastSTdate`
						 AND isnull(`outDeleted`)
                LIMIT 1),
            0)) AS `qty` FROM `goods`"
			. " LEFT JOIN `units` ON (`idunits` = `goodsUnit`) "
			. " LEFT JOIN `barcodes` ON (`barcodesItem` = `idgoods`) "
			. " WHERE " . ($_JSON['parent'] ? ("`goodsParent` = '" . $_JSON['parent'] . "'") : 'isnull(`goodsParent`)') . " ";

	$goods = [];

	$result = mysqlQuery($qtext);

	while ($row = mfa($result)) {
		if (empty($goods[$row['idgoods']])) {
			$goods[$row['idgoods']] = $row;
		}
		$goods[$row['idgoods']]['goodsBarcode'][] = FSS($row['barcodesCode']);
	}
	//printr($goods);
	$goods = obj2array($goods);

//	$OUT['$qtext'] = $qtext;
	$OUT['goods'] = $goods;
	//$tree = adjArr2obj($goodsADJ, $id = 'idgoods', $parent = 'goodsParent', $content = 'content', $debug = false);
	//$OUT = $tree;
//

	print json_encode(array_filter_recursive($OUT), JSON_UNESCAPED_UNICODE);
}

if (!empty($_JSON['action']) && $_JSON['action'] === 'addNewItem') {
	$OUT = [];
	if (R(14)) {
		if (
				!empty($_JSON['itemType']) &&
				!empty($_JSON['itemName'])
		) {
			if (
					mysqlQuery("INSERT INTO `WH_nomenclature` SET "
							. "`WH_nomenclatureParent`=" . (!empty($_JSON['itemParent']) ? ("'" . FSS($_JSON['itemParent']) . "'") : 'null') . ", "
							. "`WH_nomenclatureName` =" . (!empty($_JSON['itemName']) ? ("'" . FSS($_JSON['itemName']) . "'") : 'null') . ", "
							. "`WH_nomenclatureUnits` =" . (!empty($_JSON['itemUnit']) ? ("'" . FSS($_JSON['itemUnit']) . "'") : 'null') . ", "
							. "`WH_nomenclatureType` =" . (!empty($_JSON['goodsType']) ? ("'" . FSS($_JSON['goodsType']) . "'") : 'null') . ", "
							. "`WH_nomenclatureEntryType` = " . (!empty($_JSON['itemType']) ? ("'" . FSS($_JSON['itemType']) . "'") : 'null') . ""
					)
			) {

				$OUT['msgs'][] = array(
					'type' => 'success',
					'autoDismiss' => 700,
					'text' => 'Вы добавили элемент.',
					'data' => true);
				$OUT['success'] = true;
			} else {
				$OUT['msgs'][] = array(
					'text' => 'Возникла ошибка при добавлении элемента<br>' . mysqli_error($link),
					'data' => false);
			}
		}
	} else {
		$OUT['msgs'][] = array(
			'text' => 'У Вас нет доступа к данной функции',
			'data' => false);
	}


	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}

if (!empty($_JSON['getUnits'])) {
	print json_encode(
					[
						'units' => query2array(mysqlQuery("SELECT idunits as id, unitsCode as code, unitsName as name, unitsFullName as fname FROM `units`")),
						'goodsTypes' => query2array(mysqlQuery("SELECT `idgoodsTypes` as `id`, `goodsTypesName` as `name` FROM `goodsTypes`"))
					]
					, 288);
}

if (!empty($_JSON['getDirTree'])) {
	print json_encode(['dirTree' => obj2array(array_filter_recursive(adjArr2obj(query2array(mysqlQuery("SELECT `WH_nomenclatureName`,`idWH_nomenclature`,`WH_nomenclatureParent` FROM `WH_nomenclature` WHERE `WH_nomenclatureEntryType`='1'")), $id = 'idWH_nomenclature', $parent = 'WH_nomenclatureParent', $content = 'childs')))], 288);
}

if (!empty($_JSON['action']) && $_JSON['action'] === 'checkBC') {
	$OUT = ['result' => false];
	if (!empty($_JSON['BC'])) {
		$result = mfa(mysqlQuery("SELECT * FROM `WH_goods`"
						. " WHERE `WH_goodBarCode` = '" . FSS($_JSON['BC']) . "' LIMIT 1"));
		if ($result) {
			$OUT = ['result' => $result];
		} else {
			if (!R(14)) {
				$OUT['msgs'][] = array(
					'text' => 'Товар не найден.',
					'data' => false);
			}
		}
	}
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}

if (!empty($_JSON['action']) && $_JSON['action'] === 'editField') {
	if (R(15)) {


		if ($_JSON['key'] === 'contentQty') {
//			WH_goodsSetsContent
			if (mysqlQuery("UPDATE `WH_goodsSetsContent` SET `WH_goodsSetsContentQty` = '" . round($_JSON['value'], 3) . "' WHERE `idWH_goodsSetsContent`='" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? $_JSON['value'] : '0';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}



		if ($_JSON['key'] === 'ballance') {

			$units = mfa(mysqlQuery("SELECT `WH_nomenclatureUnits` "
									. "FROM `WH_goods` "
									. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature`=`WH_goodsNomenclature`)"
									. "  WHERE `idWH_goods` = '" . $_JSON['item'] . "'"))['WH_nomenclatureUnits'] ?? 'null';
			if (($units ?? 0) && mysqlQuery("INSERT INTO `WH_stocktaking` SET"
							. " `WH_stocktakingGoods`='" . $_JSON['item'] . "',"
							. " `WH_stocktakingUnits`=" . $units . ","
							. " `WH_stocktakingQty`='" . round(floatval($_JSON['value']), 3) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? $_JSON['value'] : 'Не задан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}


		if ($_JSON['key'] === 'price') {


			if (mysqlQuery("UPDATE `WH_goods` SET"
							. " `WH_goodsPrice`='" . round(floatval($_JSON['value']), 3) . "'"
							. "WHERE `idWH_goods`='" . $_JSON['item'] . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? round(floatval($_JSON['value']), 3) : '??';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}





		if ($_JSON['key'] === 'goodsMinLimit') {
			if (mysqlQuery("UPDATE `WH_nomenclature` SET `WH_nomenclatureMin` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idWH_nomenclature` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? $_JSON['value'] : 'Не задан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}

		if ($_JSON['key'] === 'goodsMaxLimit') {
			if (mysqlQuery("UPDATE `WH_nomenclature` SET `WH_nomenclatureMax` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idWH_nomenclature` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? $_JSON['value'] : 'Не задан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}


		if ($_JSON['key'] === 'idunits') {// Ok
			if (mysqlQuery("UPDATE `WH_nomenclature` SET `WH_nomenclatureUnits` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idWH_nomenclature` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? mfa(mysqlQuery("SELECT `unitsFullName` FROM `units` WHERE `idunits` = '" . $_JSON['value'] . "'"))['unitsFullName'] : 'не указана';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}


		if ($_JSON['key'] === 'istps') {// Ok
			if (mysqlQuery("UPDATE `WH_nomenclature` SET `WH_nomenclatureIsTPS` = " . sqlVON(($_JSON['value'] ?? null), 1) . " WHERE `idWH_nomenclature` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? 'Да' : 'Нет';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}





		if ($_JSON['key'] === 'idunitsSupplier') {
			if (mysqlQuery("UPDATE `goods` SET `goodsSupplierUnit` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idgoods` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? mfa(mysqlQuery("SELECT `unitsFullName` FROM `units` WHERE `idunits` = '" . $_JSON['value'] . "'"))['unitsFullName'] : 'не указана';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}

//		{"action":"editField","key":"goodsUSUratio","item":20,"value":"10"}
		if ($_JSON['key'] === 'goodsUSUratio') {
			if (mysqlQuery("UPDATE `goods` SET `goodsUSUratio` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idgoods` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? $_JSON['value'] : '??';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}





		if ($_JSON['key'] === 'itemParent') {
			if (mysqlQuery("UPDATE `WH_nomenclature` SET `WH_nomenclatureParent` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idWH_nomenclature` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? mfa(mysqlQuery("SELECT `WH_nomenclatureName` FROM `WH_nomenclature` WHERE `idWH_nomenclature` = '" . $_JSON['value'] . "'"))['WH_nomenclatureName'] : 'Без раздела';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}



		if ($_JSON['key'] === 'goodsBarcode') {
			if (mysqlQuery("UPDATE `barcodes` SET `barcodesCode` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idbarcodes` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}


		if ($_JSON['key'] === 'addBarcode') {
			if (mysqlQuery("INSERT INTO `barcodes` SET `barcodesCode` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . ",  `barcodesItem` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}

		if ($_JSON['key'] === 'deleteBarcode') {
			if (mysqlQuery("DELETE FROM `barcodes` WHERE `idbarcodes` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}

		if ($_JSON['key'] === 'goodsQty') {
			if (mysqlQuery("INSERT INTO `stocktaking` SET"
							. "`stocktakingItem` = '" . FSI($_JSON['item']) . "',"
							. "`stocktakingQty` = '" . FSS($_JSON['value']) . "',"
							. "`stocktakingDate` = CURRENT_TIMESTAMP()")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}



		if ($_JSON['key'] === 'itemName') {
			if (mysqlQuery("UPDATE `WH_nomenclature` SET `WH_nomenclatureName` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idWH_nomenclature` = '" . FSI($_JSON['item']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указано';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		}
	} else {
		$_JSON['msgs'][] = 'У Вас нет доступа к этой функции';
	}

	//{"action":"editField","key":"goodsMinLimit","item":420,"value":"15"}





	print json_encode($_JSON, JSON_UNESCAPED_UNICODE);
}


