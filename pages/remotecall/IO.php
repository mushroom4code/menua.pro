<?php

//R(172) - звонилка
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");
$databases = [
    '1' => 'warehouse',
    '2' => 'vita'
];

if (isset($_JSON['action']) && $_JSON['action'] == 'getPhone') {
    $OUT = [];
    if ($_JSON['call'] ?? false) {
        $call = mfa(mysqlQuery("SELECT"
                        . " * "
                        . " FROM `OCC_calls`"
                        . " LEFT JOIN `clientsPhones` ON (`idclientsPhones`=`OCC_callsPhone`)"
                        . " LEFT JOIN `clients` ON (`idclients`=`clientsPhonesClient`)"
                        . " WHERE `idOCC_calls` = '" . mres($_JSON['call']) . "'"));

        $OUT['OCC_calls'] = $call['idOCC_calls'];
        $OUT['phoneNumber'] = $call['clientsPhonesPhone'];
        $OUT['lname'] = $call['clientsLName'];
        $OUT['fname'] = $call['clientsFName'];
        $OUT['mname'] = $call['clientsMName'];
    } else {
        $count = mfa(mysqlQuery("select COUNT(*) as `count` FROM `RCC_phones` WHERE isnull(`RCC_phonesClaimedBy`)"))['count'];
        if ($count > 0) {
            $rand = rand(0, $count - 1);
            $clientSQL = "SELECT * FROM `RCC_phones`"
                    . " WHERE "
                    . "isnull(`RCC_phonesClaimedBy`)"
                    . "order by `idRCC_phones`  LIMIT $rand,1";
            $client = mfa(mysqlQuery($clientSQL));

            usleep(rand(50000, 200000));

            $OUT['idRCC_phone'] = $client['idRCC_phones'];
            $OUT['phoneNumber'] = $client['RCC_phonesNumber'];
            $OUT['lname'] = $client['RCC_phonesLName'];
            $OUT['db'] = $client['RCC_phonesBase'];
        }
    }
    print json_encode($OUT, 288);
    die();
}


if (($_JSON['action'] ?? '') == "saveCall") {
    //НОВЫЙ КЛИЕНТ, ЗАПИСЫВАЕМ
//	{
//    "action": "saveCall",
//    "call": {
//        "result": "9",
//        "comment": "",
//        "recallDate": "",
//        "smsTemplate": 5
//    },
//    "client": {
//        "idRCC_phones": 1380775,
//        "clientsPhonesPhone": 89602458381,
//        "clientsLName": "Димченко",
//        "clientsFName": "Лидия",
//        "clientsMName": "Алексеевна"
//    },
//    "appointments": []
//}
    //Если нет айди клиента, но есть телефон и (Фамилия или имя)
    if (!($_JSON['client']['idclients'] ?? false) && ($_JSON['client']['clientsPhonesPhone'] ?? false) && (($_JSON['client']['clientsLName'] ?? false) || ($_JSON['client']['clientsFName'] ?? false))) {
//добавляем клиента
        if (!(mysqlQuery("INSERT INTO `clients` SET "
                        . " `clientsLName` = '" . mres(mb_ucfirst(trim($_JSON['client']['clientsLName']))) . "', "
                        . " `clientsFName` = '" . mres(mb_ucfirst(trim($_JSON['client']['clientsFName']))) . "', "
                        . " `clientsMName` = '" . mres(mb_ucfirst(trim($_JSON['client']['clientsMName']))) . "', "
                        . " `clientsBDay` = " . sqlVON($_JSON['client']['clientsBDay'] ?? null) . ", "
                        . " `clientsSource` = " . sqlVON($_JSON['client']['clientsSource'] ?? null) . ", "
                        . " `clientsAddedBy`='" . $_USER['id'] . "',"
                        . " `clientsSource` = '2'"
                        . ";") && ($idclients = mysqli_insert_id($link)))
        ) {
            die('Ошибка добавления клиента');
        }



        telegramSendByRights([112], "🍀 Маркетингом добавлен новый клиент.\r\n"
                . "Оператор: " . $_USER['lname'] . " " . $_USER['fname'] . "\r\n"
                . "Клиент: " . mres(mb_ucfirst(trim($_JSON['client']['clientsLName']))) . " " . mres(mb_ucfirst(trim($_JSON['client']['clientsFName']))) . " " . mres(mb_ucfirst(trim($_JSON['client']['clientsMName']))) . "\r\n" . 'https://' . SUBDOMEN . 'menua.pro/pages/offlinecall/schedule.php?client=' . $idclients);

//добавляем телефонный номер
        if (!(mysqlQuery("INSERT INTO `clientsPhones` SET `clientsPhonesClient` = '" . $idclients . "', `clientsPhonesPhone`='" . mres($_JSON['client']['clientsPhonesPhone']) . "'") && ($idclientsPhones = mysqli_insert_id($link)))) {
            die('Ошибка добавления телефонного номера');
        }
    }
    /*
      "call": {
      "result": "4",
      "recallDate": "2021-07-06",
      "comment": "комментарий к звонку"
      }, */
    ////КЛИЕНТ, ДОСТАЁМ ИМЕЮЩЕГОСЯ ИЛИ ТОЛЬКО ЧТО ДОБАВЛЕННОГО
    $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = " . sqlVON($_JSON['client']['idclients'] ?? $idclients ?? null) . ""));

    if (
            !(
            ( ($idclientsPhones = ($idclientsPhones ?? $_JSON['client']['idclientsPhones'] ?? false)) && ($callResult = ($_JSON['call']['result'] ?? false))) &&
            mysqlQuery("INSERT INTO `OCC_calls` SET "
                    . " `OCC_callsPhone` = " . sqlVON($idclientsPhones) . ","
                    . " `OCC_callsType` = " . sqlVON($callResult) . ","
                    . " `OCC_callsClient` = '" . $client['idclients'] . "',"
                    . " `OCC_callsUser` = '" . $_USER['id'] . "'") &&
            ($idOCC_calls = mysqli_insert_id($link))
            )
    ) {
        die('Ошибка добавления звонка');
    }

    if (
            $callResult && ($_JSON['call']['comment'] ?? null) && !(
            mysqlQuery("INSERT INTO `OCC_callsComments` SET "
                    . ""
                    . "`OCC_callsCommentsComment`=" . sqlVON($_JSON['call']['comment']) . ""
                    . ", `OCC_callsCommentsCall`='" . $idOCC_calls . "'")
            )
    ) {
        die('Ошибка добавления комментария к звонку');
    }

    if (
            $callResult == 4 && ($_JSON['call']['recallDate'] ?? null) && !(
            mysqlQuery("INSERT INTO `OCC_calls` SET "
                    . " `OCC_callsPhone` = " . sqlVON($idclientsPhones) . ","
                    . " `OCC_callsType` = '7',"
                    . " `OCC_callsClient` = '" . $client['idclients'] . "',"
                    . " `OCC_callsUser` = '" . $_USER['id'] . "'")
            )
    ) {
        die('Ошибка добавления отложенного звонка');
    }





    if ($callResult == 5 && !count($_JSON['appointments'] ?? [])) {
        die('Ошибка добавления процедур клиенту, отсутствуют процедуры.');
    }
//ПРОИЗВОДИМ ЗАПИСЬ
    if ($callResult == 5) {
        $appointmentsText = '';
        $appointmentsByTime = [];
        foreach ($_JSON['appointments'] as $appointment) {
            $service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($appointment['service']['id']) . "'"));

            if (!(mysqlQuery("INSERT INTO `servicesApplied` SET "
                            . "`servicesAppliedService`='" . mres($appointment['service']['id']) . "',"
                            . "`servicesAppliedQty`='1',"
                            . "`servicesAppliedClient` = '" . $client['idclients'] . "',"
                            . "`servicesAppliedBy` = '" . $_USER['id'] . "',"
                            . "`servicesAppliedByReal` = '" . $_USER['id'] . "',"
                            . "`servicesAppliedPersonal` = " . sqlVON($appointment['personnel']) . ", "
                            . "`servicesAppliedDate` = '" . date("Y-m-d", $appointment['time']) . "',"
                            . "`servicesAppliedTimeBegin` = '" . date("Y-m-d H:i:s", $appointment['time']) . "',"
                            . "`servicesAppliedTimeEnd` = '" . date("Y-m-d H:i:s", $appointment['time'] + ($service['servicesDuration'] ?? 30) * 60) . "',"
                            . "`servicesAppliedPrice` = " . sqlVON($appointment['price']) . ""
                            . "") &&
                    ($idservicesApplied = mysqli_insert_id($link))
                    )) {
                die('Ошибка добавления процедуры');
            }
            if (($appointment['comment'] ?? false) && !(mysqlQuery("INSERT INTO `servicesAppliedComments` SET `servicesAppliedCommentsSA` = '" . $idservicesApplied . "', `servicesAppliedCommentText`=" . sqlVON($appointment['comment']) . ""))) {
                die('Ошибка добавления комментария к процедуре');
            }
            $appointmentsText .= date("d.m.Y H:i", $appointment['time']) . ' ' . $appointment['service']['name'] . ' (' . $appointment['price'] . 'p.)' . "\r\n";
            $appointmentsByDate[date("Y-m-d", $appointment['time'])][] = $appointment;
        }

        telegramSendByRights([112], "🍀 Маркетинг осуществил запись клиента:\r\n"
                . "Оператор: " . $_USER['lname'] . " " . $_USER['fname'] . "\r\n"
                . "Клиент: " . mres(mb_ucfirst(trim($_JSON['client']['clientsLName']))) . " " . mres(mb_ucfirst(trim($_JSON['client']['clientsFName']))) . " " . mres(mb_ucfirst(trim($_JSON['client']['clientsMName']))) . "\r\n" . 'https://' . SUBDOMEN . 'menua.pro/pages/offlinecall/schedule.php?client=' . $client['idclients'] . "&date=" . date("Y-m-d", $_JSON['appointments'][0]['time']) . "\n" . $appointmentsText);

// ну по всей видимости всё чудесно записалось.
        if ($_JSON['call']['smsTemplate'] ?? null) {
            if ($_JSON['call']['smsTemplate'] !== '-1') {
                //надо отправить отдельные смски по каждому дню. Для этого пересоберем массив исходя из дат (сделаем это на предыдущем этапе)
                $smsTemplatesText = mfa(mysqlQuery("SELECT * FROM `smsTemplates` WHERE `idsmsTemplates` = '" . mres($_JSON['call']['smsTemplate']) . "'"))['smsTemplatesText'] ?? null;
                foreach ($appointmentsByDate as $date => $appointments) {
                    usort($appointments, function ($a, $b) {
                        return $a['time'] <=> $b['time'];
                    });
                    $smsdata = [
                        'dateone' => date("d.m", $appointments[0]['time']),
                        'timeone' => date("H:i", $appointments[0]['time']),
                    ];
                    $smsText = smsTemplate($smsTemplatesText, $smsdata);

                    $sendResult = sendSms(($_JSON['client']['clientsPhonesPhone'] ?? null), $smsText);
                    $success = (($sendResult['status'] ?? '') === 'ok');
                    if ($success) {
                        $uid = preg_replace("/message-id-/", '', $sendResult['result']['uid']);
                        mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesSmsTotal` = `clientsPhonesSmsTotal`+1 WHERE `idclientsPhones` = '" . $idclientsPhones . "'");
                        mysqlQuery("INSERT INTO `sms` SET "
                                . "`smsHash` = '" . $uid . "', "
                                . "`smsUser` = '" . $_USER['id'] . "', "
                                . "`smsClient` = '" . $client['idclients'] . "', "
                                . "`smsText` = '" . mres($smsText) . "', "
                                . "`smsPhone` = '" . $idclientsPhones . "'");
                    } else {
                        $output['errors'][] = 'Не удалось отправить SMS';
                        die('Не удалось отправить SMS');
                    }
                }
            }
        } else {
            die('Отсутствует шаблон СМС в запросе');
        }
    } else {
//		telegramSendByRights([160], "🚨 Результат звонка ($callResult)\nОператор: " . $_USER['lname'] . ' ' . $_USER['fname']);
    }
    print json_encode(['success' => true], 288);
    die();
}




if (($_JSON['action'] ?? '') == "getAvailableTime") {


//	$database = $databases[$_JSON['database']];

    if (($_JSON['date'] ?? '') !== '' && ($_JSON['service'] ?? '') !== '') {
        $equipment = mfa(mysqlQuery("SELECT * FROM `services` LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`) WHERE `idservices` = '" . $_JSON['service'] . "'"));
        $idequipment = $equipment['servicesEquipment'];
        $equipmentQty = $equipment['equipmentQty'];

        $personnelSQL = "SELECT `idusers`, `usersLastName`, `usersFirstName`,`usersDeleted`,`usersScheduleFrom`,`usersScheduleTo`,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
                . " FROM `users` "
                . " LEFT JOIN `usersPositions` ON (`idusers` = `usersPositionsUser`) "
                . " LEFT JOIN `positions2services` ON (`usersPositionsPosition` = `positions2servicesPosition`) "
                . " LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers` AND `usersScheduleDate` = '" . $_JSON['date'] . "')"
                . " LEFT JOIN `users2services` ON (`users2servicesUser` = `idusers`)"
                . " WHERE "
                . " (isnull(`usersDeleted`) OR (`usersDeleted`>'" . $_JSON['date'] . " 23:59:59'))"
                . (isset($_JSON['service']) ? ("AND "
                . ""
                . "("
                . "`positions2servicesService` = '" . $_JSON['service'] . "'  "
                . "OR (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = '" . $_JSON['service'] . "' AND `users2servicesUser` = `idusers`)>0"
                . ")"
                . " AND (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesExclude` = '" . $_JSON['service'] . "' AND `users2servicesUser` = `idusers`) = 0"
                . "") : '')
                . " AND NOT isnull(`idusers`) "
                . ""
                . " AND `usersGroup` IN (1,2,3,4,5,6,7,10,11)"
                . " AND NOT isnull(`usersScheduleFrom`) "
                . " AND NOT isnull(`usersScheduleTo`) "
                . " GROUP BY `idusers`,`idusersSchedule`";

        $personnelSQL = "SELECT `idusers`,
    `usersLastName`,
    `usersFirstName`,
    `usersDeleted`,
    `usersScheduleFrom`,
    `usersScheduleTo`,
    (SELECT 
            GROUP_CONCAT(`positionsName`
                    SEPARATOR ', ') AS `positions`
        FROM
            `usersPositions`
                LEFT JOIN
            `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`)
        WHERE
            `usersPositionsUser` = `idusers`) AS `positions`
FROM `users` LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers`
        AND `usersScheduleDate` = '" . $_JSON['date'] . "')
        WHERE  NOT ISNULL(`usersScheduleFrom`)
        AND NOT ISNULL(`usersScheduleTo`)
        AND (ISNULL(`usersDeleted`) OR (`usersDeleted` > '" . $_JSON['date'] . " 23:59:59'))
        AND (`idusers` in (SELECT `usersPositionsUser` FROM `usersPositions` WHERE  `usersPositionsPosition` IN(SELECT `positions2servicesPosition` FROM `positions2services` where `positions2servicesService` = '" . $_JSON['service'] . "'))
         OR `idusers` in (SELECT `users2servicesUser` FROM `users2services` WHERE `users2servicesInclude` = '" . $_JSON['service'] . "'))
        AND  `idusers` NOT IN (SELECT `users2servicesUser` FROM `users2services` WHERE `users2servicesExclude` = '" . $_JSON['service'] . "');";

        $start = microtime(1);
        $personnel = query2array(mysqlQuery($personnelSQL));
//        logTG($personnelSQL . "\n\n" . (microtime(1) - $start));
        if (!count($personnel)) {
            $personnelSQL = "SELECT `idusers`, `usersLastName`, `usersFirstName`,`usersDeleted`,`usersScheduleFrom`,`usersScheduleTo`,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
                    . " "
                    . " FROM `users` "
                    . " LEFT JOIN `usersPositions` ON (`idusers` = `usersPositionsUser`) "
                    . " LEFT JOIN `positions2services` ON (`usersPositionsPosition` = `positions2servicesPosition`) "
                    . " LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers` AND `usersScheduleDate` BETWEEN '" . $_JSON['date'] . "' AND DATE_ADD('" . $_JSON['date'] . "', INTERVAL 14 DAY))"
                    . " LEFT JOIN `users2services` ON (`users2servicesUser` = `idusers`)"
                    . " WHERE "
                    . " NOT isnull(`idusers`) "
                    . " AND  (isnull(`usersDeleted`) OR (`usersDeleted`>'" . $_JSON['date'] . " 23:59:59'))"
                    . (isset($_JSON['service']) ? ("AND "
                    . ""
                    . "("
                    . "`positions2servicesService` = '" . $_JSON['service'] . "'  "
                    . "OR (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = '" . $_JSON['service'] . "' AND `users2servicesUser` = `idusers`)>0"
                    . ")"
                    . " AND (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesExclude` = '" . $_JSON['service'] . "' AND `users2servicesUser` = `idusers`) = 0"
                    . "") : '')
                    . " AND NOT isnull(`idusers`) "
                    . ""
                    . " AND `usersGroup` IN (1,2,3,4,5,6,7,10,11)"
                    . " AND NOT isnull(`usersScheduleFrom`) "
                    . " AND NOT isnull(`usersScheduleTo`) "
                    . " GROUP BY `idusers`,`idusersSchedule`";

            $personnel = query2array(mysqlQuery($personnelSQL));
        }


        if (count($personnel)) {
            $servicesApplied = query2array(mysqlQuery("SELECT * FROM "
                            . " `servicesApplied` "
                            . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                            . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                            . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
                            . " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`)"
                            . " WHERE `servicesAppliedPersonal` IN (" . implode(',', array_column($personnel, 'idusers')) . ")"
                            . " AND `servicesAppliedDate` = '" . $_JSON['date'] . "'"
                            . " AND isnull(`servicesAppliedDeleted`)"));

            if ($idequipment) {
                $equipment['idequipment'] = $idequipment;
                $start = null;
                $finish = null;
                $lastState = false;
                for ($time = strtotime($_JSON['date'] . ' 08:00:00'); $time <= strtotime($_JSON['date'] . ' 22:00:00'); $time += 60 * 5) {

                    $nowused = count(obj2array(array_filter($servicesApplied, function ($element) {
                                        global $time, $idequipment;
                                        if ($idequipment == $element['idequipment']) {
                                            if ($time >= strtotime($element['servicesAppliedTimeBegin']) && $time < strtotime($element['servicesAppliedTimeEnd'])) {
                                                return true;
                                            }
                                        }
                                        return false;
                                    })));
                    $state = $nowused >= $equipmentQty;
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
        }






//		$dummipils = [];

        foreach ($personnel as &$user) {
            $start = strtotime($user['usersScheduleFrom']); //  //
            $end = strtotime($user['usersScheduleTo']);
            $user['usersDate'] = date("Y-m-d", mystrtotime($user['usersScheduleFrom']));
            $user['usersTime'] = mystrtotime($user['usersScheduleFrom']);
//			$dummipils = [];

            $user['pills'] = [];
            for ($time = $start; $time < $end; $time += 60 * 15) {
                $t = $time;
                $td = $time + 0;
//				$dummipils[] = ['time' => $time, 'personnel' => null];

                if (
                        !(count(array_filter(($equipment['time'] ?? []), function ($el) {
                                    global $time;
                                    return $time >= strtotime($el['from']) && $time < strtotime($el['to']);
                                })))
                ) {

                    $qty = count(array_filter($servicesApplied, function ($el) {
                                global $time, $user;
                                return ($user['idusers'] == $el['servicesAppliedPersonal'] && $time >= strtotime($el['servicesAppliedTimeBegin']) && $time < strtotime($el['servicesAppliedTimeEnd']));
                            }));
                    $tmp408 = array_values(array_filter($servicesApplied, function ($el) {
                                global $time, $user;
                                return (
                                $user['idusers'] == $el['servicesAppliedPersonal'] &&
                                $time >= strtotime($el['servicesAppliedTimeBegin']) &&
                                $time < strtotime($el['servicesAppliedTimeEnd'])
                                );
                            }));
                    $available = !count(array_filter($servicesApplied, function ($el) {
                                        global $time, $user;
                                        return (
                                        $user['idusers'] == $el['servicesAppliedPersonal'] &&
                                        $time >= strtotime($el['servicesAppliedTimeBegin']) &&
                                        $time < strtotime($el['servicesAppliedTimeEnd']) &&
                                        ($el['servicesAppliedPrice'] || $el['servicesAppliedContract'])
                                        );
                                    }));

                    if (count(array_filter($servicesApplied, function ($el) {
                                        global $time, $user;
                                        return (
                                        $user['idusers'] == $el['servicesAppliedPersonal'] &&
                                        $time >= strtotime($el['servicesAppliedTimeBegin']) &&
                                        $time < strtotime($el['servicesAppliedTimeEnd']) &&
                                        $el['servicesAppliedContract']
                                        );
                                    }))) {
                        $color = 'pink';
                    } elseif (count(array_filter($servicesApplied, function ($el) {
                                        global $time, $user;
                                        return (
                                        $user['idusers'] == $el['servicesAppliedPersonal'] &&
                                        $time >= strtotime($el['servicesAppliedTimeBegin']) &&
                                        $time < strtotime($el['servicesAppliedTimeEnd']) &&
                                        (round($el['servicesAppliedPrice']) > 0 && !$el['servicesAppliedContract'])
                                        );
                                    }))) {
                        $color = 'lemonchiffon'; //papayawhip
                    } else {
                        $color = 'silver';
                    }


                    $tmppill = [
                        'time' => $time,
                        'personnel' => $user['idusers'],
                        'service' => $_JSON['service'],
                        'qty' => $qty,
                        'available' => $available,
                        'color' => $color,
                        'data' => $tmp408
                    ];
                    if ($_JSON['idf_subscriptions'] ?? false) {
                        $tmppill['idf_subscriptions'] = $_JSON['idf_subscriptions'];
                    }
                    $user['pills'][] = $tmppill;
                }
            }
        }

        usort($personnel, function ($a, $b) {
            return count($b['pills']) <=> count($a['pills']);
        });
        if (count($personnel) > 10) {
//			array_unshift($personnel, ['idusers' => null, 'usersLastName' => 'Без', 'usersFirstName' => 'специалиста', 'pills' => $dummipils]);
        }
    }
    print json_encode($personnel ?? [], 288);
    die();
}//getAvailableTime





if (($_JSON['action'] ?? '') == "getPhoneInfo" && isset($_JSON['phone'])) {
    if ($_JSON['phone'] == '') {

        $RCC_phone = mfa(mysqlQuery("SELECT * FROM `RCC_phones` WHERE isnull(`RCC_phonesClaimedBy`) ORDER BY RAND() LIMIT 1;"));
        $name = explode(' ', preg_replace('!\s+!', ' ', trim($RCC_phone['RCC_phonesLName'])));
        $client = [
            "idRCC_phones" => $RCC_phone['idRCC_phones'],
            "clientsPhonesPhone" => $RCC_phone['RCC_phonesNumber'],
            "clientsLName" => $name[0] ?? '',
            "clientsFName" => $name[1] ?? '',
            "clientsMName" => $name[2] ?? ''
        ];
        exit(json_encode(['clients' => [$client]], 288));
    } else {
        $phoneNumber = preg_replace("/[^0-9]/", "", $_JSON['phone'] ?? '');
        if (strlen($phoneNumber) == 11) {
            $phoneNumber[0] = '8';
        } elseif (strlen($phoneNumber) == 10) {
            $phoneNumber = '8' . $phoneNumber;
        }

        $clients = query2array(mysqlQuery("SELECT idclients,idclientsPhones,clientsPhonesPhone,clientsLName,clientsFName,clientsMName,clientsBDay,clientsOldSince"
                        . " FROM `clients`"
                        . " LEFT JOIN `clientsPhones` ON (`clientsPhonesClient` = `idclients`)"
                        . " WHERE `clientsPhonesPhone`='" . mres($phoneNumber) . "'"
                        . " AND isnull(`clientsPhonesDeleted`)"));
        if (count($clients) > 1) {
            telegramSendByRights([159], "🚨🚨🚨При записи через маркетинг найдено больше 1го клиента с номером телефона\n" . mres($phoneNumber) . "\nСрочно принять меры!\n https://" . SUBDOMEN . "menua.pro/sync/utils/clones/index.php?clones=[" . implode(',', array_unique(array_column($clients, 'idclients'))) . "] \nОператор: " . $_USER['lname'] . ' ' . $_USER['fname']);
        }
        exit(json_encode(['clients' => ($clients ?? [])], 288));
    }
}
die(json_encode(['error' => 'wtf', 'json' => $_JSON], 288));
