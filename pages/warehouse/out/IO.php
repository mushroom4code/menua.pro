<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (isset($_JSON['params']['deleteWithdrawEntry'])) {
	if (R(23)) {
		$good = mfa(mysqlQuery("SELECT * FROM `WH_goodsOut` "
						. " WHERE `idWH_goodsOut` = '" . FSI($_JSON['params']['deleteWithdrawEntry']) . "'"));
		mysqlQuery("UPDATE `WH_goodsOut` SET `WH_goodsOutDeleted` = CURRENT_TIMESTAMP,"
				. "`WH_goodsOutDeletedBy` = " . $_USER['id'] . " "
				. " WHERE `idWH_goodsOut` = '" . FSI($_JSON['params']['deleteWithdrawEntry']) . "'");
		sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => "‼️‼️‼️Отмена списания товара!\n" . json_encode($good, 288 + 128) . "\n" . $_USER['lname'] . ' ' . $_USER['fname']]);
	} else {
		$_JSON['msgs'][] = 'У Вас нет доступа к этой функции';
	}
}





if (isset($_JSON['action']) && $_JSON['action'] === 'getWithdrawal') {
//	{"action":"getWithdrawal","user":"176","date":"2020-01-09"}
	if (R(6)) {
		if (isset($_JSON['date']) && $_JSON['date'] === '') {
			$_JSON['date'] = null;
		}
		if (!isset($_JSON['date']) || $_JSON['date'] === null) {
			$_JSON['date'] = date("Y-m-d");
		}
		//Если передана только дата, значит надо сформировать отчёт о тех, кто в этот день что-то брал. Фио + количество позиций
		if ((!isset($_JSON['user']) || $_JSON['user'] === null)) {

			$_JSON['summary'] = query2array(mysqlQuery(""
							. "SELECT "
							. "SUM(`WH_goodsOutQty`) AS `qty`,"
							. "CONCAT_WS(' ',`usersLastName`,`usersFirstName`) as `name`,"
							. "`idusers` as `user`"
							. ""
							. " FROM "
							. "`WH_goodsOut`"
							. "LEFT JOIN `users` ON (`idusers`=`WH_goodsOutUser`) "
							. " WHERE "
							. "`WH_goodsOutDate` BETWEEN '" . $_JSON['date'] . " 00:00:00' AND '" . $_JSON['date'] . " 23:59:59'"
							. " AND isnull(`WH_goodsOutDeleted`)"
							. " GROUP BY `idusers`"
			));
		}
		//Если передан только пользователь, значит формируем отчёт за сегодня по этому пользователю Наименование + соответствующее количество
		if (isset($_JSON['user']) && $_JSON['user'] !== null && (!isset($_JSON['date']) || $_JSON['date'] === null)) {
			$_JSON['withdrawal'] = query2array(mysqlQuery("SELECT *, DATE(`WH_goodsOutDate`) AS `outDate` FROM "
							. "`WH_goodsOut`"
							. "LEFT JOIN `WH_goods` ON (`idWH_goods`=`WH_goodsOutItem`) "
							. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature`=`WH_goodsNomenclature`) "
							. "LEFT JOIN `units` ON (`idunits` = `WH_nomenclatureUnits`)"
							. " WHERE "
							. "`WH_goodsOutUser`='" . FSS($_JSON['user']) . "'"
							. "AND `WH_goodsOutDate` BETWEEN '" . $_JSON['date'] . " 00:00:00' AND '" . $_JSON['date'] . " 23:59:59' AND isnull(`WH_goodsOutDeleted`)"
			));
		}
//Если передана дата и пользователь - формируем отчёт по дню и пользователю  Наименование + соответствующее количество
		if (isset($_JSON['user']) && $_JSON['user'] !== null && isset($_JSON['date']) && $_JSON['date'] !== null) {
			$_JSON['withdrawal'] = query2array(mysqlQuery("SELECT "
							. "`idWH_goodsOut`,"
							. "DATE(`WH_goodsOutDate`) AS `outDate`, "
							. "`WH_goodsName`,"
							. "`WH_goodsOutQty`,"
							. "`unitsName`,"
							. "`idWH_nomenclature`"
							. "FROM "
							. "`WH_goodsOut`"
							. "LEFT JOIN `WH_goods` ON (`idWH_goods`=`WH_goodsOutItem`) "
							. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature`=`WH_goodsNomenclature`) "
							. "LEFT JOIN `units` ON (`idunits` = `WH_nomenclatureUnits`)"
							. " WHERE "
							. "`WH_goodsOutUser`='" . FSS($_JSON['user']) . "'"
							. "AND `WH_goodsOutDate` BETWEEN '" . $_JSON['date'] . " 00:00:00' AND '" . $_JSON['date'] . " 23:59:59' AND isnull(`WH_goodsOutDeleted`)"
//							. "GROUP BY `WH_goodsOutItem`"
			));
		}
	} else {
		$_JSON['msgs'][] = 'У Вас нет доступа к этой функции';
	}
}



if (isset($_JSON['action']) && $_JSON['action'] === 'makeWithdraw') {
//	action	"makeWithdraw"
//date	"2020-05-08"
//item	"1051"
//qty	"1"
//user	176

	if (R(12)) {
		$date = $_JSON['date'] == date("Y-m-d") ? date("Y-m-d H:i:s") : FSS($_JSON['date']) . ' 12:00:00';

		if (preg_match("/^(?:SET)(\d+)$/", FSS($_JSON['item']), $matches)) {//isSet
			//{"action":"makeWithdraw","date":"2020-05-15","user":176,"item":"SET1300","qty":2}
			$items = query2array(mysqlQuery("SELECT * FROM `WH_goodsSetsContent` WHERE `WH_goodsSetsContentSet`='" . $matches[1] . "'"));
			$_JSON['items'] = $items;
			$VALUES = [];
			foreach ($items as $item) {
				$VALUES[] = "( "
						. $item['WH_goodsSetsContentGood'] . ","
						. $item['WH_goodsSetsContentUnits'] . ","
						. (floatval($item['WH_goodsSetsContentQty']) * $_JSON['qty']) . ",'"
						. FSI($_JSON['user']) . "','"
						. $date . "')";
			}


			if (count($VALUES) && mysqlQuery("INSERT INTO `WH_goodsOut` ( `WH_goodsOutItem`, `WH_goodsOutUnits`, `WH_goodsOutQty`,`WH_goodsOutUser`,`WH_goodsOutDate`) VALUES " . implode(",", $VALUES) . ";
")) {
				$_JSON['success'] = true;
			} else {
				$_JSON['success'] = false;
			}
		} else {//isNotSet
			$units = mfa(mysqlQuery("SELECT `WH_nomenclatureUnits` "
									. "FROM `WH_goods` "
									. "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature`=`WH_goodsNomenclature`)"
									. "  WHERE `idWH_goods` = '" . $_JSON['item'] . "'"))['WH_nomenclatureUnits'] ?? 'null';
			//idWH_goodsOut, WH_goodsOutItem, WH_goodsOutUnits, WH_goodsOutQty, WH_goodsOutUser, WH_goodsOutDate, WH_goodsOutDeleted, WH_goodsOutTime
			if (mysqlQuery("INSERT INTO `WH_goodsOut` SET "
							. "`WH_goodsOutItem` = '" . FSS($_JSON['item']) . "',"
							. "`WH_goodsOutUser` = '" . FSI($_JSON['user']) . "',"
							. "`WH_goodsOutDate` = '" . $date . "',"
							. "`WH_goodsOutUnits` = " . $units . ","
							. "`WH_goodsOutQty` = '" . FSS($_JSON['qty']) . "'")) {
				$_JSON['success'] = true;
			}
		}
	} else {
		$_JSON['msgs'][] = 'У Вас нет доступа к этой функции';
	}
}


//{"action":"checkBC","BC":"9000803834121738"}
if (isset($_JSON['action']) && $_JSON['action'] === 'checkBC') {
	if (R(12)) {
//		preg_match($_JSON, $subject, $matches)
		if (preg_match("/^(?:SET)(\d+)$/", FSS($_JSON['BC']), $matches)) {
			$_JSON['SET'] = mfa(mysqlQuery("SELECT * FROM `WH_nomenclature` WHERE `idWH_nomenclature`='" . $matches[1] . "'"));
		} else {
			$_JSON['personal'] = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersBarcode`='" . mres($_JSON['BC']) . "'"));

			if (empty($_JSON['personal'])) {
				$_JSON['item'] = mfa(mysqlQuery("SELECT
    *,
    (IFNULL((SELECT
                    `WH_stocktakingQty`
                FROM
                    `WH_stocktaking`
                WHERE
                    `idWH_stocktaking` = (SELECT
                            MAX(`idWH_stocktaking`)
                        FROM
                            `WH_stocktaking`
                        WHERE
                            `WH_stocktakingDate` = (SELECT
                                    MAX(`WH_stocktakingDate`)
                                FROM
                                    `WH_stocktaking`
                                WHERE
                                    `WH_stocktakingGoods` = `idWH_goods`))),
            0) - (IFNULL((SELECT
                    SUM(`WH_goodsOutQty`)
                FROM
                    `WH_goodsOut`
                WHERE
                    `WH_goodsOutItem` = `idWH_goods`
					AND isnull(`WH_goodsOutDeleted`)
                        AND `WH_goodsOutDate` >= (IFNULL((SELECT
                                    MAX(`WH_stocktakingDate`)
                                FROM
                                    `WH_stocktaking`
                                WHERE
                                    `WH_stocktakingGoods` = `idWH_goods`),
                            '0000-01-01 00:02:02'))),
            0)) + (IFNULL((SELECT
                    SUM(`WH_goodsInQty`)
                FROM
                    `WH_goodsIn`
                WHERE
                    `WH_goodsInGoodsId` = `idWH_goods`
                        AND `WH_goodsInDate` >= (IFNULL((SELECT
                                    MAX(`WH_stocktakingDate`)
                                FROM
                                    `WH_stocktaking`
                                WHERE
                                    `WH_stocktakingGoods` = `idWH_goods`),
                            '0000-01-01 00:02:02'))),
            0))) AS `balance`
FROM
    `WH_goods`
	LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `WH_goodsNomenclature`)
	LEFT JOIN `units` ON (`idunits` = `WH_nomenclatureUnits`)
WHERE
    `WH_goodBarCode` = '" . mres($_JSON['BC']) . "'"));
			}
		}
	} else {
		$_JSON['msgs'][] = 'У Вас нет доступа к этой функции';
	}
}







print json_encode($_JSON, 288);

