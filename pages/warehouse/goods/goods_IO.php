<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include 'working.php';




if (!empty($_JSON['action']) && $_JSON['action'] === 'orderConfirm' && R(24)) {
	mysqlQuery("UPDATE `orders` SET `ordersDone` = CURRENT_TIMESTAMP WHERE `idorders` = '" . FSI($_JSON['order']) . "'");
}




if (!empty($_JSON['action']) && $_JSON['action'] === 'getVAT' && R(9)) {
	/*
	  action	"getVAT"
	  item	20
	  supplier	"26"
	 */
	$result = mfa(mysqlQuery("SELECT `vatsAmount` FROM `vats` WHERE `vatsGoods` = '" . FSI($_JSON['item']) . "'  AND  `vatsSupplier` = '" . FSI($_JSON['supplier']) . "'"))['vatsAmount'];
	print json_encode(['vatsAmount' => $result], 288);
}



if (!empty($_JSON['action']) && $_JSON['action'] === 'searchItem' && R(9)) {
//{"action":"searchItem","search":"пара"}
	$query = "SELECT *"
//			. "`parents`.`idgoods` AS `idparents`,"
//			. "`parents`.`goodsName` AS `parentsName`,"
//			. "`items`.`idgoods` AS `iditems`,"
//			. "`items`.`goodsName` AS `itemsName`,"
//			. "`items`.`goodsUnit` AS `goodsUnit`,"
//			. "`items`.`goodsSupplierUnit` AS `goodsSupplierUnit`"
			. " FROM `WH_goods` AS `items` "
			. " LEFT JOIN `WH_nomenclature` AS `parents` ON (`parents`.`idWH_nomenclature` = `items`.`WH_goodsNomenclature`) "
			. " WHERE "
			. " `items`.`WH_goodsName` LIKE '%" . FSS($_JSON['search']) . "%'"
			. " AND NOT isnull(`idWH_nomenclature`)"
			. " LIMIT 10 ";

	$items = query2array(mysqlQuery($query));
	print json_encode(['items' => $items], 288);
}

//1027 	Лаеннек р-р д/ин. амп. 2 мл №10 	4987480010100 	упаковка 	1уп. = 20мл. 	1амп. = 2мл.
//goodsBarCode	"4987480010100"
//goodsName	"Лаеннек р-р д/ин. амп. 2 мл №10"
//id	"1027"
//itemUnits	"8"
//wh_goodsnomenclatureqty	"20"
//wh_goodswhqty	"2"
//WH_goodsWHUnits	"10"

if (!empty($_JSON['action']) && $_JSON['action'] === 'saveGoodsToSet' && R(9)) {
//	{"action":"saveGoodsToSet","item":{"id":"1027","idWH_nomenclature":"1218","qty":"20"}}

	$units = mfa(mysqlQuery("SELECT `WH_nomenclatureUnits` FROM `WH_goods` LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `WH_goodsNomenclature`) WHERE `idWH_goods` = '" . FSI($_JSON['item']['id']) . "'"))['WH_nomenclatureUnits'];
//	idWH_goodsSetsContent, WH_goodsSetsContentSet, WH_goodsSetsContentGood, WH_goodsSetsContentQty, WH_goodsSetsContentUnits
	if (mysqlQuery("INSERT INTO `WH_goodsSetsContent` SET "
					. "`WH_goodsSetsContentSet` = '" . FSI($_JSON['item']['idWH_nomenclature']) . "', "
					. "`WH_goodsSetsContentGood` = '" . FSI($_JSON['item']['id']) . "', "
					. "`WH_goodsSetsContentQty` = '" . FSS($_JSON['item']['qty']) . "', "
					. "`WH_goodsSetsContentUnits` = " . $units . "")) {
		$OUT['success'] = true;
	} else {
		$OUT['success'] = false;
	}
	print json_encode($OUT, 288);
	die();
}
if (!empty($_JSON['action']) && $_JSON['action'] === 'deleteFromGoodsToSet' && R(9)) {
	if (mysqlQuery("DELETE FROM `WH_goodsSetsContent`  "
					. "WHERE `idWH_goodsSetsContent` = '" . FSI($_JSON['item']) . "'")) {
		$OUT['success'] = true;
	} else {
		$OUT['success'] = false;
	}
	print json_encode($OUT, 288);
	die();
}


if (!empty($_JSON['action']) && $_JSON['action'] === 'saveGoodsToNomenclature' && R(9)) {
	$OUT = [];
	if (!empty($_JSON['item']['id'] ?? '')) {//update
		$OUT['action'] = 'update';
		$insertFields = [];

		if (trim($_JSON['item']['goodsName'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsName`='" . FSS(trim($_JSON['item']['goodsName'])) . "'";
		}
		if (trim($_JSON['item']['goodsBarCode'] ?? '') !== '') {
			$insertFields[] = "`WH_goodBarCode`='" . FSS(trim($_JSON['item']['goodsBarCode'])) . "'";
		}
		if (trim($_JSON['item']['itemUnits'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsUnits`='" . FSI(trim($_JSON['item']['itemUnits'])) . "'";
		}
		if (trim($_JSON['item']['idWH_nomenclature'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsNomenclature`='" . FSI(trim($_JSON['item']['idWH_nomenclature'])) . "'";
		}
		if (trim($_JSON['item']['wh_goodsnomenclatureqty'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsNomenclatureQty`='" . FSS(trim($_JSON['item']['wh_goodsnomenclatureqty'])) . "'";
		}
		if (trim($_JSON['item']['WH_goodsWHUnits'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsWHUnits`='" . FSI(trim($_JSON['item']['WH_goodsWHUnits'])) . "'";
		}
		if (trim($_JSON['item']['wh_goodswhqty'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsWHQty`='" . FSS(trim($_JSON['item']['wh_goodswhqty'])) . "'";
		}



		if (mysqlQuery("UPDATE `WH_goods` SET "
						. implode(",", $insertFields)
						. " WHERE `idWH_goods`='" . FSI($_JSON['item']['id']) . "'")) {
			if (isset($_JSON['item']['ballance']) && trim($_JSON['item']['ballance']) !== '') {
				$units = mfa(mysqlQuery("SELECT `WH_nomenclatureUnits` "
										. "FROM `WH_goods` "
										. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature`=`WH_goodsNomenclature`)"
										. "  WHERE `idWH_goods` = '" . FSI($_JSON['item']['id']) . "'"))['WH_nomenclatureUnits'] ?? 'null';
				mysqlQuery("INSERT INTO `WH_stocktaking` SET"
						. " `WH_stocktakingGoods`='" . $_JSON['item']['id'] . "',"
						. " `WH_stocktakingUnits`=" . $units . ","
						. " `WH_stocktakingQty`='" . floatval($_JSON['item']['ballance']) . "'"
						. "");
			}

			$OUT['success'] = true;
		} else {
			$OUT['success'] = false;
			$OUT['error'] = mysqli_error($link);
		}


		//$_JSON['item']['id']
		//
	} else {//insert new
		$OUT['action'] = 'insert';
		$insertFields = [];

		if (trim($_JSON['item']['goodsName'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsName`='" . FSS(trim($_JSON['item']['goodsName'])) . "'";
		}
		if (trim($_JSON['item']['goodsBarCode'] ?? '') !== '') {
			$insertFields[] = "`WH_goodBarCode`='" . FSS(trim($_JSON['item']['goodsBarCode'])) . "'";
		}
		if (trim($_JSON['item']['itemUnits'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsUnits`='" . FSI(trim($_JSON['item']['itemUnits'])) . "'";
		}
		if (trim($_JSON['item']['idWH_nomenclature'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsNomenclature`='" . FSI(trim($_JSON['item']['idWH_nomenclature'])) . "'";
		}
		if (trim($_JSON['item']['wh_goodsnomenclatureqty'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsNomenclatureQty`='" . FSS(trim($_JSON['item']['wh_goodsnomenclatureqty'])) . "'";
		}
		if (trim($_JSON['item']['WH_goodsWHUnits'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsWHUnits`='" . FSI(trim($_JSON['item']['WH_goodsWHUnits'])) . "'";
		}
		if (trim($_JSON['item']['wh_goodswhqty'] ?? '') !== '') {
			$insertFields[] = "`WH_goodsWHQty`='" . FSS(trim($_JSON['item']['wh_goodswhqty'])) . "'";
		}
		if (mysqlQuery("INSERT INTO `WH_goods` SET "
						. implode(",", $insertFields)
				)) {
			$_JSON['item']['id'] = mysqli_insert_id($link);

			if (isset($_JSON['item']['ballance']) && trim($_JSON['item']['ballance']) !== '') {
				$units = mfa(mysqlQuery("SELECT `WH_nomenclatureUnits` "
										. "FROM `WH_goods` "
										. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature`=`WH_goodsNomenclature`)"
										. "  WHERE `idWH_goods` = '" . FSI($_JSON['item']['id']) . "'"))['WH_nomenclatureUnits'] ?? 'null';

				mysqlQuery("INSERT INTO `WH_stocktaking` SET"
						. " `WH_stocktakingGoods`='" . $_JSON['item']['id'] . "',"
						. " `WH_stocktakingUnits`=" . $units . ","
						. " `WH_stocktakingQty`='" . floatval($_JSON['item']['ballance']) . "'");
			}
			$OUT['success'] = true;
		} else {
			$OUT['success'] = false;
			$OUT['error'] = mysqli_error($link);
		}



		//
	}
	print json_encode($OUT, 288);
}



if (!empty($_JSON['action']) && $_JSON['action'] === 'searchNomenclature' && R(9)) {
//{"action":"searchItem","search":"пара"}
	$query = "SELECT *"
			. " FROM `WH_nomenclature`"
			. " WHERE "
			. "`WH_nomenclatureName` LIKE '%" . FSS($_JSON['search']) . "%'"
			. "LIMIT 10 ";

	$items = query2array(mysqlQuery($query));
	print json_encode(['items' => $items], 288);
}

if (!empty($_JSON['action']) && $_JSON['action'] === 'searchGoods' && R(9)) {
//{"action":"searchItem","search":"пара"}
	$query = "SELECT *"
			. " FROM `WH_goods` "
			. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `WH_goodsNomenclature`) "
			. " WHERE "
			. "`WH_goodsName` LIKE '%" . FSS($_JSON['search']) . "%'"
			. "LIMIT 10 ";

	$items = query2array(mysqlQuery($query));
	print json_encode(['items' => $items], 288);
}



if (!empty($_JSON['action']) && $_JSON['action'] === 'searchGoodsBC' && R(9)) {
//{"action":"searchItem","search":"пара"}
	$query = "SELECT *"
			. " FROM `WH_goods` "
			. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `WH_goodsNomenclature`)"
			. " WHERE "
			. "`WH_goodBarCode` ='" . FSS($_JSON['search']) . "'"
			. "LIMIT 1";

	$items = query2array(mysqlQuery($query));
	print json_encode(['items' => $items], 288);
}






if (!empty($_JSON['action']) && $_JSON['action'] === 'confirmItem' && R(24)) {


//{"action":"confirmItem","item":157,"quantity":"2"}	
	$item = mfa(mysqlQuery("SELECT * "
					. "FROM `orderedItems`"
					. "LEFT JOIN `goods` ON (`idgoods` = `orderedItemsItem`)  "
					. "WHERE `idorderedItems` = '" . FSI($_JSON['item']) . "'"));

	mysqlQuery("INSERT INTO `in` SET "
			. "`inGoodsId` = '" . $item['idgoods'] . "',"
			. " `inQty` = '" . (($item['goodsSupplierUnit'] !== null && $item['goodsSupplierUnit'] !== $item['goodsUnit']) ? ($_JSON['quantity'] * ($item['goodsUSUratio'] ?? 1)) : ($_JSON['quantity'])) . "',"
			. " `inSupplier` = " . $item['orderedItemsSupplier'] . ","
			. " `inDate` = CURRENT_TIMESTAMP"
			. "");
	mysqlQuery("UPDATE `orderedItems` SET `orderedItemsQty`='" . FSS($_JSON['quantity']) . "', `orderedItemsChecked` = CURRENT_TIMESTAMP WHERE `idorderedItems` = '" . FSI($_JSON['item']) . "'");
}



if (!empty($_JSON['action']) && $_JSON['action'] === 'sendOrder' && R(24)) {
	if (!empty($_JSON['items']) && count($_JSON['items'])) {
		$supplier = mfa(mysqlQuery("SELECT * FROM `suppliers` WHERE `idsuppliers` ='" . FSI($_JSON['supplier']) . "' "));
		$items = query2array(mysqlQuery("SELECT "
						. "`idgoods`,"
						. "`goodsName`,"
						. "`WHU`.`unitsName` as `WHunitsName`,"
						. "`SU`.`unitsName` as `SunitsName`,"
						. "`WHU`.`idunits` as `idWHunits`,"
						. "`SU`.`idunits` as `idSunits`"
						. "FROM `goods`"
						. "LEFT JOIN `units` AS `SU` ON (`SU`.`idunits` = `goodsSupplierUnit`)"
						. "LEFT JOIN `units` AS `WHU` ON (`WHU`.`idunits` = `goodsUnit`)"
						. " where `idgoods` IN (" . implode(",", array_column($_JSON['items'], 'id')) . ")"), 'idgoods');
	}

	mysqlQuery("INSERT INTO `orders` SET `ordersSupplier` = '" . $supplier['idsuppliers'] . "',`ordersDate` = CURRENT_TIMESTAMP");
	$orderid = mysqli_insert_id($link);

	$strings = [];
	foreach ($_JSON['items'] as $item) {
		$strings[] = "($orderid, " . $item['id'] . ", " . $item['qty'] . ", " . ($items[$item['id']]['idSunits'] ?? $items[$item['id']]['idWHunits'] ?? 'null') . ", " . $supplier['idsuppliers'] . ")";
	}
	mysqlQuery("INSERT INTO `orderedItems` (`orderedItemsOrder`, `orderedItemsItem`, `orderedItemsQty`,`orderedItemsUnit`,`orderedItemsSupplier`) VALUES " . implode(",", $strings));

	if (!$supplier['suppliersEmailIsVoid']) {


		$entity = mfa(mysqlQuery("SELECT *  FROM `entities` WHERE `identities` = '" . FSI($_JSON['emailFrom']) . "'"));

		$message = '
	<!DOCTYPE html>
	<html>
		<head>
			<title>Заявка</title>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<style>
				* {
					font-family: Verdana;
					font-size: 9pt;
					line-height: 20pt;
				}
				h2 {
					font-size: 20pt;
					text-align: center;
					font-style: italic;
					font-weight: normal;
				}	
				h3 {
					font-size: 16pt;
					font-weight: normal;
				}	
				.devider {
					height: 10px;
					background-color: black;
				}
				table {
					font-size: 12pt;
					border-collapse: collapse;
					line-height: 16pt;
				}
				td,th {
					padding: 0px 20px;
				}
			</style>
		</head>
		<body>
		<div style="display: inline-block;">
			<h2>' . $entity['entitiesName'] . '</h2>';

		$entitiesData = explode("\n", $entity['entitiesData']);

		foreach ($entitiesData as $entitiesDataRow) {
			$message .= '<div>' . $entitiesDataRow . '</div>';
		}

//			<div>Адрес: 196084, г. Санкт-Петербург, Московский пр-кт, д. 111, лит. А, пом.2Н</div>
//			<div>ИНН 7810730632 КПП 781001001 ОГРН 1187847142724 дата присвоения 22.05.2018</div>
//			<div>р/с 4070281060070021247 в ПАО БАНК "АЛЕКСАНДРОВСКИЙ" БИК 044030755 к/с 30101810600000000755</div>
//			<div>тел. 8(812) 4544407</div>


		$message .= '<div class="devider"></div>
			<h3>Здравствуйте! Оформите заказ:</h3>
			<table border="1">
				<tr>
					<th>№</th>
					<th>Наименование изделия</th>
					<th>Кол-во</th>
				</tr>';
		$n = 0;
		foreach ($_JSON['items'] as &$item) {
			$n++;
			$message .= '<tr><td>' . $n . '</td><td>' . $items[$item['id']]['goodsName'] . '</td><td>' . ($item['qty'] . ' ' . ($items[$item['id']]['SunitsName'] ?? $items[$item['id']]['WHunitsName'] ?? '')) . '</td></tr>';
			$item['u'] = ($items[$item['id']]['SunitsName'] ?? $items[$item['id']]['WHunitsName'] ?? '');
//[supplier] => 26
			//[id] => 124
			//[qty] => 4
		}

		$message .= '
			</table>
			<br><br><br><br>
	<div>C уважением,</div>
    <div>"<b>' . $entity['entitiesName'] . '</b>"</div>
    <div>Михневич Виктория Владимировна</div>
    <div>моб.тел.: <a href="tel:+79009364902">+7 (900) 936-49-02</a></div>
    <div>эл.почта: <a href="mailto:sklad_m111@mail.ru?subject=Вопрос по заказу №' . substr($orderid, -2) . " от " . date("d.m.Y") . '">sklad_m111@mail.ru</a></div>
		</div>
		</body>
	</html>
';
		$subject = $entity['entitiesName'] . ". Заказ №" . (substr($orderid, -2)) . " от " . date("d.m.Y");




		smtpmail($entity['entitiesName'], $supplier['suppliersName'], $supplier['suppliersEmail'], $subject, $message);
		smtpmail($entity['entitiesName'], 'Коваленко Елена Юрьевна', 'sklad_m111@mail.ru', $subject, $message);
		smtpmail($entity['entitiesName'], 'Александр', 'sashnone@mail.ru', $subject, $message);

		print json_encode(['success' => true, 'items' => $_JSON['items'], 'msgs' => [['type' => 'success', 'text' => 'ok', 'autoDismiss' => 1000]]], 288);
	} else {
		print json_encode(['msgs' => [['type' => 'success', 'text' => 'ok', 'autoDismiss' => 1000]]], 288);
	}
}	