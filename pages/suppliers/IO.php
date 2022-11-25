<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';


if (!R(10)) {
	die('[]');
}

//{"action":"editField","key":"suppliersName","value":"ООО \"Северная Каролина Фарма\"2"}
if (isset($_JSON['action']) && $_JSON['action'] === 'editField') {





	if ($_JSON['field'] === 'vatsAmount') {
		$result = false;
//		goods 670
//		supplier 26
//		value "10"
		if (FSI($_JSON['value'])) {
			if (mysqlQuery("INSERT INTO  `vats` SET "
							. "`vatsSupplier` = '" . FSI($_JSON['supplier']) . "', "
							. "`vatsGoods` = '" . FSI($_JSON['goods']) . "', "
							. "`vatsAmount` = '" . FSI($_JSON['value']) . "'"
							. "ON DUPLICATE KEY UPDATE `vatsAmount` = '" . FSI($_JSON['value']) . "'")) {
				$result = true;
			}
		} else {
			if (mysqlQuery("DELETE FROM  `vats` WHERE"
							. "`vatsSupplier` = '" . FSI($_JSON['supplier']) . "' AND "
							. "`vatsGoods` = '" . FSI($_JSON['goods']) . "' "
							. "")) {
				$result = true;
			}
		}
		if ($result) {
			$OUT['success'] = true;
		}
	}



	if ($_JSON['field'] === 'newManager') {

		mysqlQuery("INSERT INTO `suppliersManagers` SET "
				. "`suppliersManagersName` ='" . FSS($_JSON['value']) . "',"
				. "`suppliersManagersSupplier` = '" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}



	if ($_JSON['field'] === 'newPhone') {
		mysqlQuery("INSERT INTO `suppliersManagersPhones` SET "
				. "`suppliersManagersPhonesPhone` ='" . FSS($_JSON['value']) . "',"
				. "`suppliersManagersPhonesManager` = '" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}

	if ($_JSON['field'] === 'newKPP') {
		mysqlQuery("INSERT INTO `kpps` SET "
				. "`kppsSupplier` ='" . FSS($_JSON['key']) . "',"
				. "`kppsKpp` = '" . FSI($_JSON['value']) . "'");
		$OUT['success'] = true;
	}


	if ($_JSON['field'] === 'managerPhoneComment') {
		mysqlQuery("UPDATE `suppliersManagersPhones` SET `suppliersManagersPhonesComment` ='" . FSS($_JSON['value']) . "' WHERE `idsuppliersManagersPhones` ='" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}

	if ($_JSON['field'] === 'managerPhoneNumber') {
		mysqlQuery("UPDATE `suppliersManagersPhones` SET `suppliersManagersPhonesPhone` ='" . FSS($_JSON['value']) . "' WHERE `idsuppliersManagersPhones` ='" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}

	if ($_JSON['field'] === 'suppliersName') {
		mysqlQuery("UPDATE `suppliers` SET `suppliersName` ='" . FSS($_JSON['value']) . "' WHERE `idsuppliers` ='" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}
	if ($_JSON['field'] === 'suppliersINN') {
		mysqlQuery("UPDATE `suppliers` SET `suppliersINN` ='" . FSS($_JSON['value']) . "' WHERE `idsuppliers` ='" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}
	if ($_JSON['field'] === 'suppliersPhone') {
		mysqlQuery("UPDATE `suppliers` SET `suppliersPhone` ='" . FSS($_JSON['value']) . "' WHERE `idsuppliers` ='" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}
	if ($_JSON['field'] === 'suppliersEmail') {
		mysqlQuery("UPDATE `suppliers` SET `suppliersEmail` ='" . FSS($_JSON['value']) . "' WHERE `idsuppliers` ='" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}

	if ($_JSON['field'] === 'suppliersCode') {
		mysqlQuery("UPDATE `suppliers` SET `suppliersCode` ='" . FSS($_JSON['value']) . "' WHERE `idsuppliers` ='" . FSI($_JSON['key']) . "'");
		$OUT['success'] = true;
	}
}


if (isset($_JSON['action']) && $_JSON['action'] === 'getManager') {
	$managersRaw = query2array(mysqlQuery("SELECT * "
					. "FROM `suppliersManagers`"
					. "LEFT JOIN `suppliersManagersPhones` ON (`suppliersManagersPhonesManager` = `idsuppliersManagers`)"
					. " WHERE `idsuppliersManagers` = '" . FSI($_JSON['manager']) . "'"));
	$managers = [];
	foreach ($managersRaw as $manager) {
		$managers[$manager['idsuppliersManagers']]['id'] = $manager['idsuppliersManagers'];
		$managers[$manager['idsuppliersManagers']]['name'] = $manager['suppliersManagersName'];
		$managers[$manager['idsuppliersManagers']]['phones'][] = [
			'id' => $manager['idsuppliersManagersPhones'],
			'number' => $manager['suppliersManagersPhonesPhone'],
			'comment' => $manager['suppliersManagersPhonesComment'] ?? '----'
		];
	}

	$managers = obj2array($managers);
	if (count($managers) == 1) {
		$OUT['manager'] = ($managers[0]);
	}
}

//{"action":"voidEmail","supplier":47,"":true}
if (isset($_JSON['action']) && $_JSON['action'] === 'voidEmail') {
	mysqlQuery("UPDATE `suppliers` SET `suppliersEmailIsVoid` = " . ($_JSON['state'] ? '1' : 'null') . " WHERE `idsuppliers` = " . FSI($_JSON['supplier']) . " ");
}

//action: "addNewSupplier"
//supplierBarcode: "234234fds"
//supplierName: "gfds sdf sdf"

if (isset($_JSON['action']) && $_JSON['action'] === 'addNewSupplier') {
	if (mysqli_num_rows(mysqlQuery("SELECT * FROM `suppliers` WHERE  `suppliersCode` = '" . FSS($_JSON['supplierBarcode']) . "'"))) {
		$OUT['msgs'][] = 'Поставщик с таким штрих-кодом<br>уже есть. Сгенерируй пожалуйста другой штрих-код';
	} else {
		if (mysqlQuery("INSERT INTO `suppliers` SET "
						. "`suppliersName` = '" . FSS($_JSON['supplierName']) . "',"
						. " `suppliersCode` = '" . FSS($_JSON['supplierBarcode']) . "'"
						. "")) {
			$OUT['msgs'][] = ['type' => 'success', 'text' => rt(['Теперь я в курсе про', 'Запомнил', 'Замечательно. В базе теперь есть', 'Ок. Записал.', 'Сохранено', 'Сохранил', 'Так. Ок.']) . '<br>' . FSS($_JSON['supplierName']), 'autoDismiss' => 2000];
			$OUT['success'] = true;
		}
	}
}




$OUT['suppliers'] = query2array(mysqlQuery("SELECT "
				. "idsuppliers as id, "
				. "suppliersName as name, "
				. "suppliersCode as code"
				. " FROM `suppliers`"));



print json_encode($OUT, JSON_UNESCAPED_UNICODE);
