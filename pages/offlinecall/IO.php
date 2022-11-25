<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (R(109) && isset($_JSON['idclientStatus'])) {
    if (($_JSON['client'] ?? false)) {
        $clientSQL = "SELECT * FROM "
                . " `clients` LEFT JOIN (SELECT * FROM `clientStatus` WHERE `idclientStatus` = (SELECT MAX(`idclientStatus`)  FROM `clientStatus` WHERE `clientStatusClient` = '" . FSI($_JSON['client']) . "')) as `status` ON (`clientStatusClient` = `idclients`)"
                . " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`) "
                . " WHERE `idclients`='" . FSI($_JSON['client']) . "'";
        $client = mfa(mysqlQuery($clientSQL));
        if (($_JSON['idclientStatus'] ?? '') !== ($client['clientStatusStatus'] ?? '')) {
            $newstatus = mysqli_real_escape_string($link, ($_JSON['idclientStatus'] ?? ''));
            $newstatusSQL = "INSERT INTO `clientStatus` SET "
                    . "`clientStatusClient` = '" . $client['idclients'] . "', "
                    . "`clientStatusStatus` = " . ($newstatus ? ("'" . $newstatus . "'") : 'null') . ","
                    . "`clientStatusAppliedBy` = '" . $_USER['id'] . "'";
//			print $newstatusSQL;
            mysqlQuery($newstatusSQL);
        } else {
//			print 'oops';
        }
    }
    die();
}

//


if ((R(93)) && ($_JSON['params']['moveSA'] ?? false)) {
    if (($_JSON['params']['TOcontract'] ?? false)) {


        $service = mfa(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `idservicesApplied` = '" . $_JSON['params']['moveSA'] . "'"))['servicesAppliedService'];

        $remains = getRemainsByClient($_JSON['client']);
        $filterData = ['contract' => $_JSON['params']['TOcontract'], 'service' => $service];
        $remainsContracts = array_filter($remains, function ($remainsRow) use ($filterData) {
            return $remainsRow['f_salesContentService'] == $filterData['service'] &&
            $remainsRow['f_subscriptionsContract'] == $filterData['contract'] &&
            $remainsRow['f_salesContentQty'] > 0
            ;
        });
        if (count($remainsContracts)) {

            foreach ($remainsContracts as $key => $remainsContract) {
                $newPrice = $remainsContracts[$key]['f_salesContentPrice'];
            }



            mysqlQuery("UPDATE `servicesApplied` SET"
                    . " `servicesAppliedContract` = '" . mysqli_real_escape_string($link, $_JSON['params']['TOcontract']) . "', "
                    . " `servicesAppliedPrice` = '" . $newPrice . "'"
                    . " WHERE	`idservicesApplied` = '" . mysqli_real_escape_string($link, $_JSON['params']['moveSA']) . "'");
        }
    } else {
        mysqlQuery("UPDATE `servicesApplied` SET"
                . " `servicesAppliedContract` = NULL,"
                . "`servicesAppliedPrice` = NULL"
                . " WHERE	`idservicesApplied` = '" . mysqli_real_escape_string($link, $_JSON['params']['moveSA']) . "'");
    }
}



if ((R(187) || $_USER['id'] == 176) && ($_JSON['action'] ?? '') === 'changesource' && ($_JSON['idclients'] ?? false) && ($_JSON['source'] ?? false)) {
    $client = mfa(mysqlQuery("SELECT * FROM `clients` LEFT JOIN `users` ON (`idusers` = `clientsAddedBy`) WHERE `idclients` = " . mres($_JSON['idclients'])));
    $sources = query2array(mysqlQuery("SELECT * FROM `clientsSources`"), 'idclientsSources');
    if (mysqlQuery("UPDATE `clients` SET `clientsSource` = " . mres($_JSON['source']) . " WHERE `idclients` = " . $client['idclients'])) {

        if ($client['usersTG'] ?? false) {
            sendTelegram('sendMessage', ['chat_id' => $client['usersTG'], 'text' =>
                ""
                . "‼️" . $_USER['lname'] . " " . $_USER['fname'] . " меняет источник клиента "
                . $client['clientsLName'] . " " . $client['clientsFName'] . " (" . $client['idclients'] . ")"
                . " с \"" . $sources[$client['clientsSource']]['clientsSourcesName']
                . "\" на \"" . $sources[$_JSON['source']]['clientsSourcesName'] . "\""
            ]);
        }


        exit(json_encode(['success' => true], 288));
    } else {
        exit(json_encode(['success' => false, 'error' => mysqli_error($link)], 288));
    }
}
if (R(47) && isset($_JSON['action']) && $_JSON['action'] === 'doreturn') {
//{"action":"doreturn","data":[{"f_salesContentQty":7,"f_salesContentService":203,"servicesName":"Маникюр с покрытием гель-лаком","f_salesContentPrice":1600,"f_subscriptionsContract":19174}]}
    foreach ($_JSON['data'] as $service) {
        mysqlQuery("DELETE FROM `f_salesReplacementsCoordinator` WHERE `f_salesReplacementsCoordinatorDate` = CURDATE() AND `f_salesReplacementsCoordinatorContract` = " . FSI($service['f_subscriptionsContract']) . " ");
        mysqlQuery("INSERT INTO `f_salesReplacementsCoordinator` SET"
                . " `f_salesReplacementsCoordinatorCurator`='" . $_USER['id'] . "', "
                . " `f_salesReplacementsCoordinatorDate`= CURDATE(), "
                . " `f_salesReplacementsCoordinatorContract`='" . FSI($service['f_subscriptionsContract']) . "' "
                . "");

        if ($service['f_salesContentQty'] ?? 0) {
            mysqlQuery("INSERT INTO `f_subscriptions` SET "
                    . "`f_subscriptionsContract` = " . FSI($service['f_subscriptionsContract']) . ","
                    . "`f_salesContentService`=" . FSI($service['f_salesContentService']) . ","
                    . "`f_salesContentPrice`=" . intval($service['f_salesContentPrice']) . ","
                    . "`f_salesContentQty`= -" . FSI($service['f_salesContentQty']) . ","
                    . "`f_subscriptionsUser`=" . $_USER['id'] . "");
        }
    }
    print json_encode(['success' => true], 288);
}




if (R(47) && isset($_JSON['action']) && $_JSON['action'] === 'moveTheDate') {
//{"action":"moveTheDate","moveFrom":"2020-08-24","moveTo":"2020-08-25","servicesAppliedClient":112}
    $source = query2array(mysqlQuery("SELECT * FROM  `servicesApplied` WHERE `servicesAppliedDate` = '" . $_JSON['moveFrom'] . "' AND `servicesAppliedClient` = '" . intval($_JSON['servicesAppliedClient']) . "'"));
    foreach ($source as $service) {
        if (
                !$service['servicesAppliedFineshed'] &&
                !$service['servicesAppliedDeleted']
        ) {

            mysqlQuery("UPDATE `servicesApplied` SET "
                    . "`servicesAppliedDate` = '" . $_JSON['moveTo'] . "',"
                    . "`servicesAppliedTimeBegin` = '" . ($_JSON['moveTo'] . ' ' . date("H:i:s", strtotime($service['servicesAppliedTimeBegin']))) . "',"
                    . "`servicesAppliedTimeEnd` = '" . ($_JSON['moveTo'] . ' ' . date("H:i:s", strtotime($service['servicesAppliedTimeEnd']))) . "',"
                    . "`servicesAppliedPersonal` = null,"
                    . "`servicesAppliedStarted` = null,"
                    . "`servicesAppliedAt` = CURRENT_TIMESTAMP "
//					. ",`servicesAppliedBy` = '" . $_USER['id'] . "'"
                    . " WHERE `idservicesApplied`='" . $service['idservicesApplied'] . "'");
        }
    }
    print json_encode(['success' => true], 288);
    die();
}



if (R(47) && isset($_JSON['action']) && $_JSON['action'] === 'getServices') {
    $_search = explode(' ', preg_replace('/\s+/', ' ', trim($_JSON['serviceName'])));
    $_searchRow = [];
    foreach ($_search as $_searchElement) {
        $_searchRow[] = "`servicesName` LIKE '%" . mres($_searchElement) . "%' ";
    }
    $services = query2array(mysqlQuery("SELECT"
                    . " `idequipment`, "
                    . " `equipmentQty`, "
                    . " `servicesDuration`, "
                    . "`idservices` as `idservices`, "
                    . "if(`servicesParent`,CONCAT('нов.ном. ',`servicesName`),`servicesName`) as `name`,"
                    . "`servicesTypesName` as `typeName`,"
                    . "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `servicesPrice`, "
                    . "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`)) as `priceMin`,"
                    . "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<=NOW() AND `servicesPricesType`='2' AND `servicesPricesService` = `idservices`)) as `priceMax`,"
                    . " (if((SELECT `serviceMotivationService` FROM `serviceMotivation` WHERE `serviceMotivationService`=`idservices` AND `serviceMotivationMotivation`='5'),true,false)) AS `canBeDiagnostic`,"
                    . "(SELECT `servicesDescriptionsDescription` FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM servicesDescriptions WHERE`servicesDescriptionsService` = `idservices`)) as `serviceDescription`"
                    . " FROM `services` "
                    . " LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) "
                    . " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`)"
                    . " WHERE isnull(`servicesDeleted`)"
//					. " AND NOT isnull(`servicesParent`)"
                    . " AND (" . implode(' AND ', $_searchRow) . ")"));
    print json_encode(['services' => $services], 288);
    die();
}

if (R(47) && isset($_JSON['action']) && $_JSON['action'] === 'getRecentServices') {


    $recents = query2array(mysqlQuery("SELECT count(1) as `qty`,`servicesAppliedService`"
                    . " FROM `servicesApplied`"
                    . "WHERE `servicesAppliedBy` = '" . $_USER['id'] . "'"
                    . "AND isnull(`servicesAppliedContract`)"
                    . "AND `servicesAppliedAt`>=DATE_SUB(curdate(), interval 5 day) GROUP BY `servicesAppliedService` ORDER BY `qty` DESC LIMIT 10"));
    if (!count($recents)) {
        $recents = query2array(mysqlQuery("SELECT count(1) as `qty`,`servicesAppliedService`"
                        . " FROM `servicesApplied`"
                        . "WHERE "
                        . " isnull(`servicesAppliedContract`) AND"
                        . " isnull(`servicesAppliedDeleted`)"
                        . "AND `servicesAppliedAt`>=DATE_SUB(curdate(), interval 5 day) GROUP BY `servicesAppliedService` ORDER BY `qty` DESC LIMIT 10"));
    }

    if (count($recents)) {
        $services = query2array(mysqlQuery("SELECT"
                        . " `idequipment`, "
                        . " `equipmentQty`, "
                        . " `servicesDuration`, "
                        . "`idservices` as `idservices`, "
                        . "`servicesName` as `name`,"
                        . "`servicesTypesName` as `typeName`,"
                        . "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `servicesPrice`, "
                        . "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`)) as `priceMin`,"
                        . "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<=NOW() AND `servicesPricesType`='2' AND `servicesPricesService` = `idservices`)) as `priceMax`,"
                        . " (if((SELECT `serviceMotivationService` FROM `serviceMotivation` WHERE `serviceMotivationService`=`idservices` AND `serviceMotivationMotivation`='5'),true,false)) AS `canBeDiagnostic`,"
                        . "(SELECT `servicesDescriptionsDescription` FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM servicesDescriptions WHERE`servicesDescriptionsService` = `idservices`)) as `serviceDescription` "
                        . "FROM `services` "
                        . "LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) "
                        . " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`)"
                        . "WHERE isnull(`servicesDeleted`) "
                        . "AND `idservices` in (" . implode(',', array_column($recents, 'servicesAppliedService')) . ")"));
    } else {
        $services = [];
    }

    print json_encode(['services' => $services], 288);
}

if (R(47) && isset($_JSON['action']) && $_JSON['action'] === 'placePill') {
    $SQL = [];

    $consults = [172, 354, 361, 1574, 1961, 1962, 1963, 1964, 1965, 1966, 1967, 1968, 1969, 1970, 1971, 1972, 1973, 1974, 1975, 1976, 1977, 1978, 1979, 1980, 1981, 1982, 1996, 1997, 2090, 2091, 2092, 2093, 2094, 2095, 2096, 2097, 2952, 4343, 4344, 4478, 4480, 4481, 4482, 4483, 4484, 4485, 4486, 4487, 4488];

    if (in_array(($_JSON['idservices'] ?? false), $consults) && $_JSON['comment']) {


        $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . mres($_JSON['idclients']) . "'"));
        $service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($_JSON['idservices']) . "'"));
        foreach (getUsersByRights([156]) as $user) {
            if ($user['usersTG']) {
                sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' =>
                    ""
                    . "‼️" . $service['servicesName'] . "\r\n"
                    . "Комментарий: " . $_JSON['comment'] . "\r\n"
                    . "Клиент: " . $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'] . "\r\n"
                    . "Дата: " . date("d.m.Y", strtotime($_JSON['date'])) . "\r\n"
                    . "Записал: " . $_USER['lname'] . ' ' . $_USER['fname'] . "\r\n"
                    . "https://" . SUBDOMEN . "menua.pro/pages/offlinecall/schedule.php?client=" . $client['idclients'] . "&date=" . date("Y-m-d", strtotime($_JSON['date']))
                ]);
            }
        }
    }

//action	"placePill"
//comment	null
//date	"2020-09-04T13:30:00.000Z"
//deleteReason	null
//duration	"60"
//idclients	112
//idf_subscriptions	42384
//idservices	267
//idservicesApplied	null
//idusers	null
//locked	false
//qty	"1"
//servicesAppliedPrice	"2000"
//time	"16:50"
    if (($_JSON['time'] ?? '')) {
        $_JSON['date'] = date("Y-m-d " . $_JSON['time'] . ":s", strtotime($_JSON['date']));
    }
//	die();
    if (($_JSON['idservicesApplied'] ?? false) && ($_JSON['deleteReason'] ?? false)) {//удаление процедуры
        if ($_JSON['deleteReason'] == 'notFree') {
            mysqlQuery("UPDATE `servicesApplied` SET "
                    . " `servicesAppliedIsFree` = NULL, "
                    . " `servicesAppliedPrice` = NULL "
                    . " WHERE `idservicesApplied` = '" . FSI($_JSON['idservicesApplied']) . "' AND `servicesAppliedDate`>'" . EDGEDATE . "'");
        } else {
            mysqlQuery("UPDATE `servicesApplied` SET "
                    . " `servicesAppliedDeleted`= NOW()"
                    . ", `servicesAppliedDeleteReason`='" . mres($_JSON['deleteReason']) . "'"
                    . ", `servicesAppliedDeletedBy`='" . $_USER['id'] . "'"
                    . " WHERE `idservicesApplied` = '" . FSI($_JSON['idservicesApplied']) . "' AND `servicesAppliedDate`>'" . EDGEDATE . "'");
            foreach (getUsersByRights([133]) as $user) {
                if ($user['usersTG'] ?? false) {
                    $procedure = mfa(mysqlQuery("SELECT * "
                                    . "FROM `servicesApplied`"
                                    . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                                    . " LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`)"
                                    . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                                    . " WHERE `idservicesApplied`='" . mres($_JSON['idservicesApplied']) . "'"));
                    sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => '🗑️ ' . ($_USER['lname'] ?? '') . ' ' . ($_USER['fname'] ?? '') . ": удалена процедура \"" . ($procedure['servicesName'] ?? '??') . "\".\r\n" . ($procedure['servicesAppliedDate'] == date("Y-m-d") ? 'На сегодня' : ('на ' . date("d.m.Y", strtotime($procedure['servicesAppliedDate'])))) . "\r\nКлиент: " . (($procedure['clientsLName'] ?? '??') ) . ' ' . (($procedure['clientsFName'] ?? '??') ) . ' ' . (($procedure['clientsLMame'] ?? '??') ) . ' ' . "\r\nПричина: " . ($procedure['daleteReasonsName'] ?? '??') . "\n" . 'http://' . SUBDOMEN . 'menua.pro/pages/offlinecall/schedule.php?client=' . $procedure['idclients']]);
                }
            }
        }
    } elseif ($_JSON['idservicesApplied'] ?? false) {//обновление данных процедуры
        $servicesApplied = mfa(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `idservicesApplied` = '" . FSI($_JSON['idservicesApplied']) . "'"));

        if ($servicesApplied) {
            mysqlQuery("UPDATE `servicesApplied` SET "
                    . " `servicesAppliedPersonal`=" . ($_JSON['idusers'] ? FSI($_JSON['idusers']) : 'null') . ""
                    . ", `servicesAppliedTimeBegin`='" . date("Y-m-d H:i:s", strtotime($_JSON['date'])) . "'"
                    . ", `servicesAppliedTimeEnd`='" . date("Y-m-d H:i:s", (FSI($_JSON['duration']) * 60) + strtotime($_JSON['date'])) . "'"
                    . ", `servicesAppliedIsDiagnostic`=" . sqlVON($_JSON['diagnostic']) . " "
                    . " WHERE `idservicesApplied` = '" . FSI($_JSON['idservicesApplied']) . "'");
            mysqlQuery("DELETE FROM `servicesAppliedComments` WHERE `servicesAppliedCommentsSA` = '" . $servicesApplied['idservicesApplied'] . "'");
            if ($_JSON['comment'] ?? false && $_JSON['comment']) {
                mysqlQuery("INSERT INTO `servicesAppliedComments` SET `servicesAppliedCommentsSA` = '" . $servicesApplied['idservicesApplied'] . "', `servicesAppliedCommentText`='" . mysqli_real_escape_string($link, $_JSON['comment']) . "'");
            }
        }
    } elseif ($_JSON['idf_subscriptions'] ?? false) {// новая процедура из абонемента
        $subscription = mfa(mysqlQuery("SELECT * FROM"
                        . " `f_subscriptions` "
                        . " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
                        . " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
                        . " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
                        . " WHERE `idf_subscriptions` = '" . FSI($_JSON['idf_subscriptions'] ?? '') . "'"));

        //Проверим, оплачен ли абонемент
        $contractInfo = contractInfo($subscription['idf_sales']);

        $servicesAppliedSum = mfa(mysqlQuery("SELECT SUM(`servicesAppliedPrice`*`servicesAppliedQty`) AS `summ` FROM `servicesApplied` WHERE `servicesAppliedContract`='" . $subscription['idf_sales'] . "' AND isnull(`servicesAppliedDeleted`)"))['summ'] ?? 0;
        $servicesAppliedAppend = ($subscription['f_salesContentPrice'] ?? 0) * ($_JSON['qty'] ?? 1);
        if (!R(139) &&
                $contractInfo['paymentsSumm'] < $contractInfo['f_salesSumm'] && $contractInfo['paymentsSumm'] < $servicesAppliedSum + $servicesAppliedAppend
        ) {
            print json_encode(['success' => true, 'msgs' => [
                    'Абонемент оплачен не полностью.<br>'
                    . 'Пройдено процедур на сумму:' . nf($servicesAppliedSum) . 'р.<br>'
                    . 'Остаток средств: ' . nf($contractInfo['paymentsSumm'] - $servicesAppliedSum) . 'р.<br>'
                    . 'Добавляемая процедура: ' . nf($servicesAppliedAppend) . 'р.<br>']], 288);
            foreach (getUsersByRights([119]) as $user) {
                if ($user['usersICQ'] ?? false) {
                    ICQ_messagesSend_SYNC($user['usersICQ'], '🚫️ ' . $_USER['lname'] . ' ' . $_USER['fname'] . ' пытается добавить процедуру (' . $subscription['servicesName'] . ' ' . number_format($subscription['f_salesContentPrice'], 0, '.', ' ') . 'р.' . ') из неоплаченного абонемента [' . $subscription['idf_sales'] . '].' . "\r\n" . '(Процедур пройдено на ' . number_format($servicesAppliedSum, 0, '.', ' ') . 'р.)',
                            [
                                [
                                    [
                                        "text" => '🧍 Клиент: ' . $subscription['clientsLName'] . ' ' . $subscription['clientsFName'] . ' ' . $subscription['clientsMName'],
                                        "url" => "https://" . SUBDOMEN . "menua.pro/pages/offlinecall/schedule.php?client=" . $subscription['idclients'],
                                        "style" => "primary"
                                    ]
                                ], [
                                    [
                                        "text" => '📄 Договор от: ' . date("d.m.Y", strtotime($subscription['f_salesDate'])) . "\r\n"
                                        . '(Оплачено ' . number_format($contractInfo['paymentsSumm'], 0, '.', ' ') . 'р. из ' . number_format($contractInfo['f_salesSumm'], 0, '.', ' ') . 'р.)',
                                        "url" => "https://" . SUBDOMEN . "menua.pro/pages//checkout/payments.php?client=" . $subscription['idclients'] . "&contract=" . $subscription['idf_sales'],
                                        "style" => "primary"
                                    ]
                                ]
                            ]
                    );
                }
            }
            die();
        }



        if ($subscription && ($_JSON['duration'] ?? false) && ($_JSON['date'] ?? false)) {

            $inserSQL = "INSERT INTO `servicesApplied` SET "
                    . " `servicesAppliedClient`=" . ($subscription['f_salesClient'] ?? "null") . ""
                    . ((!($_JSON['noservice'] ?? false)) ? (", `servicesAppliedService`=" . ($subscription['f_salesContentService'] ?? "null")) : '')
                    . ((!($_JSON['noservice'] ?? false)) ? (", `servicesAppliedQty`='" . FSI($_JSON['qty']) . "'") : ", `servicesAppliedQty`='0'")
                    . ((!($_JSON['noservice'] ?? false)) ? (", `servicesAppliedPrice`=" . ($subscription['f_salesContentPrice'] ?? "null") . "") : '')
                    . ", `servicesAppliedContract`= " . ($subscription['idf_sales'] ?? "null") . " "
                    . ", `servicesAppliedBy`='" . $_USER['id'] . "'"
                    . ", `servicesAppliedByReal`='" . $_USER['id'] . "'"
                    . ", `servicesAppliedIsDiagnostic`=" . sqlVON($_JSON['diagnostic']) . " "
                    . ", `servicesAppliedLocked`=" . (($_JSON['locked'] ?? false) ? '1' : 'null') . ""
                    . ", `servicesAppliedPersonal`=" . ($_JSON['idusers'] ? FSI($_JSON['idusers']) : 'null') . ""
                    . ", `servicesAppliedDate`='" . date("Y-m-d", strtotime($_JSON['date'])) . "'"
                    . ", `servicesAppliedTimeBegin`='" . date("Y-m-d H:i:s", strtotime($_JSON['date'])) . "'"
                    //	. ", `servicesAppliedIsFree`=" . (intval($subscription['f_salesContentPrice'] ?? 0) ? 'null' : '1') . ""
                    . ", `servicesAppliedTimeEnd`='" . date("Y-m-d H:i:s", (FSI($_JSON['duration']) * 60) + strtotime($_JSON['date'])) . "'"
                    . "";
            mysqlQuery($inserSQL);

            if ($_JSON['comment'] ?? false && $_JSON['comment']) {
                mysqlQuery("INSERT INTO `servicesAppliedComments` SET `servicesAppliedCommentsSA` = '" . mysqli_insert_id($link) . "', `servicesAppliedCommentText`='" . mysqli_real_escape_string($link, $_JSON['comment']) . "'");
            }
//		 , , , , , , servicesAppliedIsFree, , , , , , , , servicesAppliedPrice, , ,
        }
    } elseif (// новая процедура из общего списка
            ($_JSON['date'] ?? false) &&
            ($_JSON['duration'] ?? false) &&
            ($_JSON['idclients'] ?? false) &&
            ($_JSON['idservices'] ?? false) &&
            (isset($_JSON['servicesAppliedPrice'])) &&
            ($_JSON['qty'] ?? false)
    ) {
//		action "placePill"
//		date "2020-07-24T08:30:00.000Z"
//		deleteReason null
//		duration "60"
//		idclients "112"
//		idf_subscriptions null
//		idservicesApplied null
//		idusers null
//		qty "1"

        $inserSQL = "INSERT INTO `servicesApplied` SET "
                . "  `servicesAppliedService`=" . FSI($_JSON['idservices']) . ""
                . ", `servicesAppliedClient`=" . FSI($_JSON['idclients']) . ""
                . ", `servicesAppliedBy`='" . ($_JSON['idusersSA'] ?? $_USER['id']) . "'"
                . ", `servicesAppliedByReal`='" . $_USER['id'] . "'"
                . ", `servicesAppliedPersonal`=" . ($_JSON['idusers'] ? FSI($_JSON['idusers']) : 'null') . ""
                . ", `servicesAppliedDate`='" . date("Y-m-d", strtotime($_JSON['date'])) . "'"
                . ", `servicesAppliedTimeBegin`='" . date("Y-m-d H:i:s", strtotime($_JSON['date'])) . "'"
                . ", `servicesAppliedQty`='" . FSI($_JSON['qty']) . "'"
                . ", `servicesAppliedIsDiagnostic`=" . sqlVON($_JSON['diagnostic']) . " "
                . ", `servicesAppliedPrice`='" . intval($_JSON['servicesAppliedPrice'] ?? 0) . "'"
                //	. ", `servicesAppliedIsFree`=" . (intval($_JSON['servicesAppliedPrice'] ?? 0) ? 'null' : '1') . ""
                . ", `servicesAppliedTimeEnd`='" . date("Y-m-d H:i:s", (FSI($_JSON['duration']) * 60) + strtotime($_JSON['date'])) . "'"
                . "";
//		ICQ_messagesSend_SYNC('sashnone', $inserSQL);
        mysqlQuery($inserSQL);

        $idservicesApplied = mysqli_insert_id($link);
        if ($_JSON['comment'] ?? false && $_JSON['comment']) {
            mysqlQuery("INSERT INTO `servicesAppliedComments` SET `servicesAppliedCommentsSA` = '" . $idservicesApplied . "', `servicesAppliedCommentText`='" . mysqli_real_escape_string($link, $_JSON['comment']) . "'");
        }

        if ($_JSON['idusersSA'] ?? false) {
            //	mysqlQuery("INSERT INTO `CCO2SA` SET `CCO2SACCO` = '" . intval($_JSON['idusersSA']) . "', `CCO2SASA` = '" . $idservicesApplied . "'");
        }
    }






    print json_encode(['success' => true, 'json' => $_JSON, 'date' => date("Y-m-d H:i:s", strtotime($_JSON['date'])), '$inserSQL' => $SQL, '$contractInfo' => ($contractInfo ?? null)], 288);
}
if (R(47) && ($_JSON['action'] ?? false) === 'loadSchedule' && validateDate($_JSON['date'])) {
//date	"2020-07-14"
//client	8283
//action	"loadSchedule"

    $schedule = query2array(mysqlQuery("SELECT *"
                    . ", (if((SELECT `serviceMotivationService` FROM `serviceMotivation` WHERE `serviceMotivationService`=`idservices` AND `serviceMotivationMotivation`='5'),true,false)) AS `canBeDiagnostic`"
                    . " FROM "
                    . " `servicesApplied`"
                    . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                    . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
                    . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                    . " LEFT JOIN `f_sales` ON (`idf_sales` = `servicesAppliedContract`)"
                    . " LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA` = `idservicesApplied`)"
                    . " WHERE `servicesAppliedDate` = '" . $_JSON['date'] . "' AND `servicesAppliedClient` = '" . FSI($_JSON['client']) . "' AND isnull(`servicesAppliedDeleted`)"));
    $getValid = [];
    foreach ($schedule as &$schedule2) {
        if (!$schedule2['servicesAppliedContract']) {
            $schedule2['valid'] = true;
        } else {
            $getValidSQL = "SELECT SUM(`f_salesContentQty`) AS `sum` FROM `f_subscriptions` WHERE "
                    . " `f_subscriptionsContract` = '" . $schedule2['servicesAppliedContract'] . "'"
                    . " AND `f_salesContentService` = '" . $schedule2['servicesAppliedService'] . "'"
                    . " AND `f_salesContentPrice` = " . (in_array($schedule2['servicesAppliedPrice'], ['', null]) ? 'null' : round($schedule2['servicesAppliedPrice'], 2)) . "";
            $getValid[] = $getValidSQL;
            if (
                    mfa(mysqlQuery($getValidSQL))['sum'] > 0) {
                $schedule2['valid'] = true;
            } else {
                $schedule2['valid'] = false;
            }
        }
        $schedule2['deleteable'] = false;

        if ((R(141) || $schedule2['servicesAppliedBy'] == $_USER['id']) && (R(168) || strtotime($schedule2['servicesAppliedDate']) >= strtotime(date("Y-m-d")))) {
            $schedule2['deleteable'] = true;
        }
    }
    print json_encode(['success' => true, 'json' => $_JSON, 'getValid' => ($getValid ?? ''), 'schedule' => $schedule], 288);
}



if (R(47) && isset($_JSON['action']) && $_JSON['action'] === 'toggleAlert') {
    $alert = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales` = '" . intval($_JSON['contract']) . "'"));
    if ($alert['f_salesAlert']) {
        mysqlQuery("UPDATE `f_sales` SET `f_salesAlert` = null, `f_salesAlertBy` = null WHERE `idf_sales` = '" . intval($_JSON['contract']) . "'");
    } else {
        mysqlQuery("UPDATE `f_sales` SET `f_salesAlert` = CURRENT_TIMESTAMP, `f_salesAlertBy` = '" . $_USER['id'] . "' WHERE `idf_sales` = '" . intval($_JSON['contract']) . "'");

        foreach (getUsersByRights([88]) as $user) {
            if ($user['usersICQ'] ?? false) {
                ICQ_messagesSend_SYNC($user['usersICQ'], '⚠️ ' . $_USER['lname'] . ' ' . $_USER['fname'] . ': найден ошибочный абонемент [' . $_JSON['contract'] . ']');
            }
        }
    }
}



if ((R(47) || R(184) || R(176)) && isset($_JSON['action']) && $_JSON['action'] === 'getContracts' && ($_JSON['client'] ?? false)) {

    $contracts = query2array(mysqlQuery("SELECT "
                    . "`idf_sales`,"
                    . "`f_salesSumm`,"
                    . "`f_salesDate`,"
                    . "`f_salesCancellationDate`,"
                    . "`f_salesAlert`,"
                    . "`f_salesAlertBy`,"
                    . "`f_salesNumber`"
                    . " FROM `f_sales` WHERE `f_salesClient` = '" . FSI($_JSON['client']) . "'"));

    $servicesApplied = query2array(mysqlQuery(""
                    . " SELECT"
                    . " `services`.*,"
                    . " `servicesApplied`.*,"
                    . " `daleteReasons`.*,"
                    . " `users`.*,"
                    . " `usersDelete`.`idusers` AS `idusersDelete`,"
                    . " `usersDelete`.`usersLastName` AS `usersLastNameDelete`,"
                    . " `usersDelete`.`usersFirstName` AS `usersFirstNameDelete`,"
//					. " `f_subscriptions`.*,"
                    . " `equipment`.*,"
                    . " `users2`.`usersLastName` as `operatorLastName` FROM `servicesApplied`"
                    . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                    . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
                    . " LEFT JOIN `users` AS `usersDelete` ON (`usersDelete`.`idusers` = `servicesAppliedDeletedBy`)"
                    . " LEFT JOIN `users` as `users2` ON (`users2`.`idusers` = `servicesAppliedBy`)"
                    . " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`) "
                    . " LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`) "
//					. " LEFT JOIN `f_subscriptions` ON (`f_subscriptionsContract`=`servicesAppliedContract` AND `f_salesContentService`=`servicesAppliedService` AND `f_salesContentPrice`=`servicesAppliedPrice`)"
                    . " WHERE `servicesAppliedClient` = '" . FSI($_JSON['client']) . "'"
                    . ((!R(166)) ? " AND isnull(`servicesAppliedDeleted`)" : "")
    ));

    foreach ($servicesApplied as $servicesAppliedIndex => $serviceApplied2) {
        $servicesApplied[$servicesAppliedIndex]['servicesAppliedDateHR'] = date('d.m.Y', strtotime($serviceApplied2['servicesAppliedDate']));
        $servicesApplied[$servicesAppliedIndex]['medrecords'] = query2array(mysqlQuery("SELECT * FROM `medrecords` WHERE `medrecordsServiceApplied` = '" . $serviceApplied2['idservicesApplied'] . "'"));
    }


    $unsorted = obj2array(array_filter($servicesApplied, function ($element) {
                global $subscription, $_JSON;
                if (($_JSON['unsortedFilter'] ?? '') == 'diagnostics') {
                    return ($element['servicesAppliedContract'] === null) && ($element['servicesAppliedService'] == '362');
                }
                if (($_JSON['unsortedFilter'] ?? '') == 'nocontract') {
                    return ($element['servicesAppliedContract'] === null);
                }
                return 1;
            }));

    function subscriptionsSumm($subscriptions) {
        usort($subscriptions, function ($a, $b) {
            return strtotime($a['f_subscriptionsDate']) <=> strtotime($b['f_subscriptionsDate']);
        });
        $OUT = [];
        foreach ($subscriptions as $subscription3) {
            $found = false;
            foreach ($OUT as &$OUTelem) {
                if (
                        $OUTelem['f_salesContentService'] == $subscription3['f_salesContentService'] &&
                        $OUTelem['f_salesContentPrice'] == $subscription3['f_salesContentPrice']
                ) {
                    $found = true;
                    $OUTelem['f_salesContentQty'] += $subscription3['f_salesContentQty'];
                }
            }
            if (!$found) {
                $OUT[] = $subscription3;
            }
        }
        $filtered = array_filter($OUT, function ($el) {
            return $el['f_salesContentQty'] > 0;
        });
        return $filtered;
    }

    foreach ($contracts as &$contract2) {
        $subscriptionsArray = query2array(mysqlQuery("SELECT "
                        . "*,"
                        . "`idservices`,"
                        . "`idf_subscriptions`,"
                        . "`servicesName`,"
                        . "`f_salesContentPrice`,"
                        . "`f_salesContentQty`"
                        . " FROM `f_subscriptions`"
                        . " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
                        . " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`)"
                        . " LEFT JOIN `f_sales` ON (`idf_sales`=`f_subscriptionsContract`)"
                        . " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
                        . " WHERE `f_subscriptionsContract` = '" . $contract2['idf_sales'] . "'"));

//printr($subscriptions);


        $subscriptions = subscriptionsSumm($subscriptionsArray);

//		printr($subscriptions);
        foreach ($subscriptions as $subscriptionsIndex => $subscription) {


            $subscription['comments'] = query2array(mysqlQuery("SELECT * FROM f_subscriptionsComments WHERE f_subscriptionsCommentsSubscription = '" . $subscription['idf_subscriptions'] . "' "));

            $doneArr = array_filter($servicesApplied, function ($element) {
                global $subscription;
                if ($element['servicesAppliedDeleted']) {
                    return false;
                }
                return (
                $element['servicesAppliedContract'] === $subscription['f_subscriptionsContract'] &&
                $element['servicesAppliedService'] === $subscription['f_salesContentService'] &&
                $element['servicesAppliedPrice'] === $subscription['f_salesContentPrice'] &&
                $element['servicesAppliedFineshed']);
            });
            $done = array_sum(array_column($doneArr, 'servicesAppliedQty'));
            $reservedArr = array_filter($servicesApplied, function ($element) {
                global $subscription;
                if ($element['servicesAppliedDeleted']) {
                    return false;
                }
                return (
                $element['servicesAppliedContract'] === $subscription['f_subscriptionsContract'] &&
                $element['servicesAppliedService'] === $subscription['f_salesContentService'] &&
                $element['servicesAppliedPrice'] === $subscription['f_salesContentPrice'] &&
                !$element['servicesAppliedFineshed']);
            });
            $reserved = array_sum(array_column($reservedArr, 'servicesAppliedQty'));

//			$remains =


            $contract2['subscriptions'][] = [
                'info' => $subscription,
                'remains' => $subscription['f_salesContentQty'] - $reserved - $done,
                'reserved' => $reserved,
                'done' => $done,
                'reservedArr' => obj2array($reservedArr),
                'doneArr' => obj2array($doneArr)
            ];
        }
        $contract2['f_salesNumber'] = $contract2['f_salesNumber'] ?? 'Без номера';
        $contract2['f_salesDateHuman'] = date("d.m.Y", strtotime($contract2['f_salesDate']));

        if (count($contract2['subscriptions'] ?? [])) {
            usort($contract2['subscriptions'], function ($a, $b) {
                return mb_strtolower($a['info']['servicesName']) <=> mb_strtolower($b['info']['servicesName']);
            });
        }
        $contract2['personnelOld'] = query2array(mysqlQuery(""
                        . " SELECT `users`.* FROM `f_salesToPersonal` LEFT JOIN `users` ON (`idusers`=`f_salesToPersonalUser`) WHERE `f_salesToPersonalSalesID` ='" . $contract2['idf_sales'] . "'"
                        . " UNION ALL"
                        . " SELECT `users`.* FROM `f_salesToCoord` LEFT JOIN `users` ON (`idusers`=`f_salesToCoordCoord`) WHERE `f_salesToCoordSalesID` ='" . $contract2['idf_sales'] . "'"));

        $contract2['personnel'] = query2array(mysqlQuery("SELECT * FROM `f_salesRoles`"
                        . " LEFT JOIN `f_roles` ON (`idf_roles` = `f_salesRolesRole`)"
                        . " LEFT JOIN `users` ON (`idusers`=`f_salesRolesUser`) WHERE `f_salesRolesSale` = '" . $contract2['idf_sales'] . "'"));

        $contract2['paymentsArray'] = query2array(mysqlQuery(""
                        . "SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` ='" . $contract2['idf_sales'] . "'"
                        . "UNION ALL "
                        . "SELECT `idf_balance`, `f_balanceSalesID`, 3 as `f_paymentsType`, `f_balanceAmount`, `f_balanceTime`, `f_balanceUser`,null as `f_paymentsComment`, `f_balanceClient` FROM `f_balance` WHERE `f_balanceSalesID` ='" . $contract2['idf_sales'] . "'"));
        $contract2['creditsArray'] = query2array(mysqlQuery("SELECT *  FROM `f_credits` LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`) WHERE `f_creditsSalesID` ='" . $contract2['idf_sales'] . "'"));

        $contract2['payments'] = $payments = mfa(mysqlQuery("SELECT SUM(`f_paymentsAmount`) as `summ` FROM (SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` ='" . $contract2['idf_sales'] . "'"
                                . "UNION ALL "
                                . "SELECT `idf_balance`, `f_balanceSalesID`, 3 as `f_paymentsType`, `f_balanceAmount`, `f_balanceTime`, `f_balanceUser`,null as `f_paymentsComment`, `f_balanceClient` FROM `f_balance` WHERE `f_balanceSalesID` ='" . $contract2['idf_sales'] . "') as `payments`"))['summ'] ?? 0;
        $contract2['credits'] = $credits = mfa(mysqlQuery("SELECT SUM(`f_creditsSumm`) as `summ` FROM `f_credits` WHERE `f_creditsSalesID` ='" . $contract2['idf_sales'] . "'"))['summ'] ?? 0;
    }
    usort($contracts, function ($a, $b) {
        return $a['f_salesDate'] <=> $b['f_salesDate'];
    });
    print json_encode(['success' => true, 'contracts' => $contracts, 'unsorted' => $unsorted], 288);
}///getContracts




if (isset($_JSON['action']) && $_JSON['action'] === 'getAvailablePersonnel') {


    $personnelSQL = "SELECT `idusers`, `usersLastName`, `usersMiddleName`, `usersFirstName`,`usersDeleted`,`usersScheduleFrom`,`usersScheduleTo`,`usersScheduleDuty` "
            . " FROM `users` "
            . " LEFT JOIN `usersPositions` ON (`idusers` = `usersPositionsUser`) "
            . " LEFT JOIN  `positions2services` ON (`usersPositionsPosition` = `positions2servicesPosition`) "
            . " LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers` AND `usersScheduleDate` = '" . $_JSON['date'] . "')"
//			. " LEFT JOIN `users2services` ON (`users2servicesUser` = `idusers`)"
            . " WHERE "
            . " (isnull(`usersDeleted`) OR (`usersDeleted`>'" . $_JSON['date'] . " 23:59:59'))"
            . (isset($_JSON['idservices']) ? ("AND "
            . ""
            . "("
            . "`positions2servicesService` = '" . $_JSON['idservices'] . "'  "
            . "OR (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = '" . $_JSON['idservices'] . "' AND `users2servicesUser` = `idusers`)>0"
            . ")"
            . " AND (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesExclude` = '" . $_JSON['idservices'] . "' AND `users2servicesUser` = `idusers`) = 0"
            . "") : '')
            . " AND NOT isnull(`idusers`) "
            . ""
            . " AND `usersGroup` IN (1,2,3,4,5,6,7,10,11)"
            . " AND NOT isnull(`usersScheduleFrom`) "
            . " AND NOT isnull(`usersScheduleTo`) "
            . " GROUP BY `idusers`,`idusersSchedule`";

    $personnel = query2array(mysqlQuery($personnelSQL));

    foreach ($personnel as &$emploee) {
        if ($_USER['id'] == 176) {
            
        }
        $userpositions = query2array(mysqlQuery("SELECT * FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser` = '" . $emploee['idusers'] . "'"));
        $emploee['positions'] = implode('<br>', array_column($userpositions, 'positionsName'));
    }

//idequipment

    if (count($personnel)) {
        $servicesApplied = query2array(mysqlQuery("SELECT *,"
                        . " (SELECT COUNT(1) FROM OCC_calls LEFT JOIN OCC_callsConfirm ON (OCC_callsConfirmCall= idOCC_calls) WHERE `OCC_callsClient` = `idclients` AND `OCC_callsType` = '8' AND `OCC_callsConfirmDate` = `servicesAppliedDate`) as `confirmed`,"
                        . "(SELECT `usersGroupsName` FROM `users` LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`) WHERE `idusers` = `servicesAppliedBy`) as `recordSource` "
                        . "FROM "
                        . " `servicesApplied` "
                        . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                        . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                        . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
                        . " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`)"
                        . " LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA` = `idservicesApplied`)"
                        . " WHERE `servicesAppliedPersonal` IN (" . implode(',', array_column($personnel, 'idusers')) . ")"
                        . " AND `servicesAppliedDate` = '" . $_JSON['date'] . "'"
                        . " AND isnull(`servicesAppliedDeleted`)"));

        foreach ($servicesApplied as &$serviceApplied9) {
            if (!($serviceApplied9['clientsOldSince'] ?? false) || $serviceApplied9['clientsOldSince'] == $serviceApplied9['servicesAppliedDate']) {
                $serviceApplied9['clientsIsNew'] = true;
            } else {
                $serviceApplied9['clientsIsNew'] = false;
            }
        }//clientsOldSince

        if ($_JSON['idequipment'] ?? false) {
            $equipment['idequipment'] = $_JSON['idequipment'];
            $start = null;
            $finish = null;
            $lastState = false;
            for ($time = strtotime($_JSON['date'] . ' 08:00:00'); $time <= strtotime($_JSON['date'] . ' 22:00:00'); $time += 60 * 5) {

                $nowused = count(obj2array(array_filter($servicesApplied, function ($element) {
                                    global $time, $_JSON;
                                    if ($_JSON['idequipment'] == $element['idequipment']) {
                                        if ($time >= strtotime($element['servicesAppliedTimeBegin']) && $time < strtotime($element['servicesAppliedTimeEnd'])) {
                                            return true;
                                        }
                                    }
                                    return false;
                                })));
                $state = $nowused >= $_JSON['equipmentQty'];
                if (!$lastState && $state) {
                    $lastState = $state;
                    $start = $time;
                }
                if ($lastState && !$state) {
                    $lastState = $state;
                    $finish = $time;
                    $equipment['time'][] = [
                        'from' => date("Y-m-d H:i:s", $start),
                        'to' => date("Y-m-d H:i:s", $time),
                    ];
                    $start = null;
                    $finish = null;
                }
            }
        }




        foreach ($personnel as &$person2) {
            $person2['services'] = obj2array(array_filter($servicesApplied, function ($element) {
                        global $person2;
                        return $element['servicesAppliedPersonal'] == $person2['idusers'];
                    }));
        }
        $info[] = $personnelSQL;
    } else {
        $info[] = 'personnel count is null';
        $info[] = $personnelSQL;
    }


    usort($personnel, function ($a, $b) {
        return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
    });

//	$_JSON['f_subscriptionsContract']?strtotime($_JSON['date']) > strtotime(EDGEDATE):true;
    print json_encode([
        'dateValid' => R(198) ? true : (strtotime($_JSON['date']) > strtotime(EDGEDATE)),
        'personnel' => $personnel,
        'equipment' => $equipment ?? [],
//		'sql' => $personnelSQL,
        'info' => $info ?? [],
        'success' => true], 288);
}




if (isset($_JSON['action']) && $_JSON['action'] === 'callerSuggestions') {

    $callers = query2array(mysqlQuery("SELECT * FROM `users` WHERE `usersLastName` LIKE '%" . mysqli_real_escape_string($link, $_JSON['lastname']) . "%'"));

    if (1) {
        print json_encode(['callers' => $callers, 'success' => true], 288);
    } else {
        print json_encode(['success' => false], 288);
    }
}

if (isset($_JSON['clientsByPhone'])) {

    $start = microtime(1);
    $clientsII = query2array(mysqlQuery("SELECT `idclients`,`clientsLName`,`clientsFName`,`clientsMName` FROM `clients` LEFT JOIN `clientsPhones` ON (`idclients`=`clientsPhonesClient`) WHERE `clientsPhonesPhone` = '" . $_JSON['clientsByPhone'] . "' AND isnull(`clientsPhonesDeleted`) GROUP BY `idclients`"));

    if (count($clientsII)) {
        $clients = $clientsII;
    } else {
        $clients = query2array(mysqlQuery("SELECT * FROM `RCC_phones` WHERE `RCC_phonesNumber` = '" . $_JSON['clientsByPhone'] . "'"));

        foreach ($clients as &$client) {
            $clientNames = explode(' ', trim(preg_replace('/\s+/', ' ', $client['RCC_phonesLName'])));
            $client['clientsLName'] = $clientNames[0] ?? '';
            $client['clientsFName'] = $client['RCC_phonesFName'] ?? $clientNames[1] ?? '';
            $client['clientsMName'] = $client['​​RCC_phonesMName'] ?? $clientNames[2] ?? '';

            $client['clientsLName'] = mb_ucfirst($client['clientsLName']);
            $client['clientsFName'] = mb_ucfirst($client['clientsFName']);
            $client['clientsMName'] = mb_ucfirst($client['clientsMName']);
        }
    }



    print json_encode(['executionTime' => (microtime(1) - $start), 'clients' => $clients], 288);
}


if (isset($_JSON['action']) && $_JSON['action'] === 'addNewClient') {


//action	"addNewClient"
//birthday	""
//clientsPhone	"89219847682"
//comment	"sdfsdfsdfsdfsdf"
//firstname	"Любовь"
//gender	1
//lastname	"Мосягина"
//middlename	"Юрьевна"

    $SET = [];
    if (FSS(trim($_JSON['firstname'])) !== '') {
        $SET[] = "`clientsFName` = '" . FSS(trim($_JSON['firstname'])) . "'";
    }
    if (FSS(trim($_JSON['lastname'])) !== '') {
        $SET[] = "`clientsLName` = '" . FSS(trim($_JSON['lastname'])) . "'";
    }
    if (FSS(trim($_JSON['middlename'])) !== '') {
        $SET[] = "`clientsMName` = '" . FSS(trim($_JSON['middlename'])) . "'";
    }

    if (validateDate(FSS(trim($_JSON['birthday'])))) {
        $SET[] = "`clientsBDay` = '" . FSS(trim($_JSON['birthday'])) . "'";
    }



    $GET = $SET;

    if (FSS(trim($_JSON['clientsPhone'])) !== '') {
        $GET[] = "`clientsPhonesPhone` = '" . FSS(trim($_JSON['clientsPhone'])) . "'";
    }

    if (count($GET)) {
        $selectSQL = "SELECT * FROM `clients` LEFT JOIN `clientsPhones` ON (`clientsPhonesClient` = `idclients`) WHERE "
                . implode(" AND ", $GET);
    }

    if ($client = mfa(mysqlQuery($selectSQL))) {
        die(json_encode(['success' => false, 'msgs' => ['Клиент с такими данными уже есть']], 288));
    }

    $SET[] = "`clientsAddedBy` = '" . $_USER['id'] . "'";

    if (isset($_JSON['gender']) && $_JSON['gender'] != '') {
        $SET[] = "`clientsGender` = '" . FSI($_JSON['gender']) . "'";
    }

    if (isset($_JSON['idclientsSources']) && $_JSON['idclientsSources'] != '') {
        $SET[] = "`clientsSource` = '" . FSI($_JSON['idclientsSources']) . "'";
    }


    if (count($SET)) {

        $insertSQL = "INSERT INTO `clients` SET " . implode(",", $SET);
//		die();
        if (mysqlQuery($insertSQL) && $insertid = mysqli_insert_id($link)) {


            if (isset($_JSON['clientsPhone']) && $_JSON['clientsPhone'] != '') {
                mysqlQuery("INSERT INTO `clientsPhones` SET"
                        . " `clientsPhonesClient` = '" . $insertid . "',"
                        . " `clientsPhonesPhone` = '" . FSI($_JSON['clientsPhone']) . "'"
                        . "");
            }

            if (isset($_JSON['comment']) && $_JSON['comment'] != '') {
                mysqlQuery("INSERT INTO `clientsComments` SET "
                        . " `clientsCommentsClient` = '" . $insertid . "',"
                        . "`clientsCommentsAddedBy` = '" . $_USER['id'] . "',"
                        . " `clientsCommentsText` = '" . mysqli_real_escape_string($link, $_JSON['comment']) . "'"
                        . "");
            }

            print json_encode(['success' => true, 'client' => $insertid], 288);
        } else {
            print json_encode(['success' => false, 'msgs' => ['Ошибка вставки в базу данных']], 288);
        }
    }
}


