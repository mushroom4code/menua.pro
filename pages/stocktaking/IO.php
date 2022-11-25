<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
//{
//	"action":"checkBC", "BC":"LLN2GS07192502190121"
//}

if (isset($_JSON['action']) && $_JSON['action'] === 'checkBC') {

	$result = mysqlQuery("SELECT * FROM "
			. "`barcodes`"
			. " LEFT JOIN `goods` ON (`idgoods` = `barcodesItem`)"
			. " LEFT JOIN `units` ON (`idunits` = `goodsUnit`)"
			. " WHERE `barcodesCode` ='" . FSS($_JSON['BC']) . "'");
	$OUT = [];
	while ($row = mfa($result)) {
		$OUT[] = [
			'id' => $row['idgoods'],
			'name' => $row['goodsName'],
			'barcode' => $_JSON['BC'],
			'qty' => 0,
			'unit' => [
				'id' => $row['idunits'],
				'name' => $row['unitsName'],
				'fname' => $row['unitsFullName']
			]
		];
	}
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}



if (isset($_JSON['action']) && $_JSON['action'] === 'addNewItem') {

	if (isset($_JSON['stocktaking']) && $_JSON['stocktaking'] === true) {

		if (mysqlQuery("INSERT INTO `stocktaking` SET "
						. "`stocktakingItem` = '" . FSI($_JSON['id']) . "',"
						. " `stocktakingQty` = '" . FSS($_JSON['qty']) . "',"
						. " `stocktakingDate` = '" . date("Y-m-d") . "'"
						. " ")) {
			$OUT['success'] = true;
			$OUT['stocktaking'] = true;
		} else {
			$OUT['success'] = false;
			$OUT['stocktaking'] = true;
			$OUT['errors'][] = "Произошла ошибка <br><br>" . mysqli_error($link);
		}
	} else {
		if (mysqlQuery("INSERT INTO `in` SET "
						. "`inGoodsId` = '" . FSI($_JSON['id']) . "',"
						. " `inQty` = '" . FSS($_JSON['qty']) . "',"
						. " `inDate` = '" . FSS($_JSON['date']) . "'"
						. " ")) {
			$OUT['success'] = true;
		} else {
			$OUT['success'] = false;
			$OUT['errors'][] = "Произошла ошибка <br><br>" . mysqli_error($link);
		}
	}




	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}


if (isset($_JSON['action']) && $_JSON['action'] === 'getIn') {
	$OUT['entries'] = [];
	$result = mysqlQuery(""
			. "SELECT *, "
			. "`goodsName` as `name`,"
			. "`inQty` as `qty`"
			. " FROM `in`"
			. "LEFT JOIN `goods` ON (`idgoods` = `inGoodsId`)"
			. "LEFT JOIN `units` ON (`idunits` = `goodsUnit`)"
			. "WHERE `inDate` = '" . FSS($_JSON['date']) . "'");
	while ($row = mfa($result)) {
		$OUT['entries'][] = $row;
	}
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}



if (isset($_JSON['action']) && $_JSON['action'] === 'stocktaking') {
	$OUT['entries'] = [];
	$result = mysqlQuery(""
			. "SELECT "
			. "`goodsName` as `name`,"
			. "`unitsName`,"
			. "SUM(`stocktakingQty`) as `qty`"
			. " FROM `stocktaking`"
			. "LEFT JOIN `goods` ON (`idgoods` = `stocktakingItem`)"
			. "LEFT JOIN `units` ON (`idunits` = `goodsUnit`)"
			. "WHERE `stocktakingDate` = '" . FSS($_JSON['date']) . "'"
			. "GROUP BY `stocktakingItem`");
	while ($row = mfa($result)) {
		$OUT['entries'][] = $row;
	}
	$OUT['stocktaking'] = true;
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}