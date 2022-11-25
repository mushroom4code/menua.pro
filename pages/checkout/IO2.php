<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf8");
mb_internal_encoding("UTF-8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$sortSQL = " ORDER BY `clientsLName`,`clientsFName`,`clientsMName`";
$NOW = date("Y-m-d H:i:s");
/*
 */

function getClientsData($clients) {
    global $_USER;
    foreach (($clients ?? []) as $index => $client) {
        $clients[$index]['passport'] = mfa(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "')")) ?? [];

        $clients[$index]['balance'] = mfa(mysqlQuery("SELECT SUM(-`f_balanceAmount`) as `balance` FROM `f_balance` WHERE `f_balanceClient` = '" . $client['idclients'] . "'"))['balance'] ?? 0;

        $clients[$index]['phones'] = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`)"));
        $clients[$index]['servicesApplied'] = query2array(mysqlQuery("SELECT"
                        . " `idservicesApplied`,"
                        . " `idservices`,"
                        . " `servicesAppliedQty` as `qty`,"
                        . " `servicesAppliedFineshed`,"
                        . " TRUE as `required`,"
                        . " ifnull(`servicesAppliedPrice`,0) AS `price`,"
                        . " `servicesName` "
                        . " FROM `servicesApplied`"
                        . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                        . " WHERE `servicesAppliedClient` = '" . $client['idclients'] . "'"
                        . " AND isnull(`servicesAppliedDeleted`)"
                        . " AND isnull(`servicesAppliedContract`)"
                        . (($_USER['id'] != 176) ? " AND `servicesAppliedDate` = CURDATE()" : "")
        ));
        $clients[$index]['f_salesDraft'] = query2array(mysqlQuery(""
                        . " SELECT "
                        . " `idf_salesDraft`,"
                        . " UNIX_TIMESTAMP(`f_salesDraftDate`) AS `f_salesDraftDate`,"
                        . " `f_salesDraftAuthor`,"
                        . " `f_salesDraftNumber`,"
                        . " `usersLastName`,"
                        . " `usersFirstName`,"
                        . " `usersMiddleName`"
                        . " FROM `f_salesDraft` "
                        . " LEFT JOIN `users` ON (`idusers` = `f_salesDraftAuthor`) WHERE `f_salesDraftClient`='" . $client['idclients'] . "'"));

        foreach ($clients[$index]['f_salesDraft'] as $f_salesDraftIndex => $f_saleDraft) {
            $clients[$index]['f_salesDraft'][$f_salesDraftIndex]['f_subscriptionsDraft'] = query2array(mysqlQuery("SELECT"
                            . " `idf_subscriptionsDraft`,"
                            . " `idservices`, "
                            . " `servicesName`,"
                            . " `servicesVat`,"
                            . " (SELECT SUM(`f_subscriptionsDraftSalesQty`) FROM `f_subscriptionsDraftSales` WHERE `f_subscriptionsDraftSalesDraft` = `idf_subscriptionsDraft`) AS `f_subscriptionsDraftSalesQty`,"
//							. " `f_subscriptionsDraftSalesSale`,"
                            . " `f_subscriptionsDraftQty` AS `qty`, "
                            . " `f_subscriptionsDraftPrice` AS `price` "
                            . " FROM `f_subscriptionsDraft`"
//							. " LEFT JOIN `f_subscriptionsDraftSales` ON (`f_subscriptionsDraftSalesDraft` = `idf_subscriptionsDraft`) "
                            . " LEFT JOIN `services` ON (`idservices` = `f_subscriptionsDraftService`) WHERE `f_subscriptionsDraftSaleDraft`='" . $f_saleDraft['idf_salesDraft'] . "'"));
        }
    }
    return $clients;
}

//{"action":"getSale","idf_sale":19893}
if (($_JSON['action'] ?? '') == 'getSale') {//Загружаем абонемент
    $f_sale = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales` = '" . mres($_JSON['idf_sale']) . "'"));
    if (!$f_sale) {
        die(json_encode(['success' => false, 'error' => 'sale not found'], 288));
    }
    $sale = [];

    $f_salesRoles = query2array(mysqlQuery("SELECT * FROM `f_salesRoles` LEFT JOIN `users` ON (`idusers` = `f_salesRolesUser`) WHERE `f_salesRolesSale` = '" . $f_sale['idf_sales'] . "'"));
    foreach ($f_salesRoles as $f_salesRole) {
        $sale['personnel'][$f_salesRole['f_salesRolesRole']]['users'][] = [
            'idusers' => $f_salesRole['idusers'],
            'usersLastName' => $f_salesRole['usersLastName'],
            'usersFirstName' => $f_salesRole['usersFirstName'],
            'usersMiddleName' => $f_salesRole['usersMiddleName'],
        ];
    }

    $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . $f_sale['f_salesClient'] . "'"));

    $sale['client'] = [
        "id" => $client['idclients'],
        "aknum" => $client['clientsAKNum'],
        "phones" => query2array(mysqlQuery("SELECT `idclientsPhones` as `id`, `clientsPhonesPhone` as `number` FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient` = '" . $client['idclients'] . "'")),
        "lname" => $client['clientsLName'],
        "fname" => $client['clientsFName'],
        "mname" => $client['clientsMName'],
        "gender" => $client['clientsGender'],
        "bday" => $client['clientsBDay'],
        "passport" => mfa(mysqlQuery("SELECT "
                        . "`clientsPassportsBirthPlace` AS `bplace`,"
                        . "`clientsPassportNumber` AS `number`,"
                        . "`clientsPassportsDate` AS `date`,"
                        . "`clientsPassportsCode` AS `code`,"
                        . "`clientsPassportsDepartment` AS `department`,"
                        . "`clientsPassportsRegistration` AS `registration`,"
                        . "`clientsPassportsResidence` AS `residence`"
                        . " FROM `clientsPassports` WHERE `idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "')")),
        "servicesApplied" => []
    ];

    $sale['sale'] = [
        "id" => $f_sale['idf_sales'],
        "type" => $f_sale['f_salesType'],
        "entity" => $f_sale['f_salesEntity'],
        "date" => $f_sale['f_salesDate']
    ];

//	"idf_salesDraft": 448,
//	"idf_subscriptionsDraft": 13592,
    $sale['sale']['subscriptions'] = query2array(mysqlQuery("SELECT "
                    . " `f_salesContentService` AS `idservices`,"
                    . " `servicesName` AS `servicesName`,"
                    . " `f_salesContentPrice` AS `price`,"
                    . " `f_salesContentQty` AS `qty`,"
                    . " `f_subscriptionsExpDate` AS `validBefore`"
                    . ""
                    . " FROM `f_subscriptions`"
                    . " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
                    . " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
                    . " WHERE `f_subscriptionsContract` = '" . $f_sale['idf_sales'] . "'"
                    . " AND (isnull(`f_subscriptionsDate`) OR `f_subscriptionsDate`=`f_salesDate`)"
                    . ""));

    $f_payments = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` = '" . $f_sale['idf_sales'] . "' AND `f_paymentsDate` = '" . $f_sale['f_salesTime'] . "'"));

    $payments = [];
    foreach ($f_payments as $f_payment) {
        if ($f_payment['f_paymentsType'] == '1') {
            $payments['cash'] = [
                'enabled' => true,
                'value' => $f_payment['f_paymentsAmount']
            ];
        }
        if ($f_payment['f_paymentsType'] == '2') {
            $payments['card'] = [
                'enabled' => true,
                'value' => $f_payment['f_paymentsAmount']
            ];
        }
    }
    $f_installment = mfa(mysqlQuery("SELECT * FROM `f_installments` WHERE `f_installmentsSalesID` = '" . $f_sale['idf_sales'] . "'"));

    if ($f_installment) {
        $payments['installment'] = [
            'enabled' => true,
            'value' => $f_installment['f_installmentsSumm']
        ];
    }

    $f_credits = query2array(mysqlQuery("SELECT * FROM `f_credits` WHERE `f_creditsSalesID` =  '" . $f_sale['idf_sales'] . "'"));

    foreach ($f_credits as $f_credit) {
        $payments['banks'][] = [
            "enabled" => true,
            "idbank" => $f_credit['f_creditsBankID'],
            "value" => $f_credit['f_creditsSumm'],
            "agreementNumber" => $f_credit['f_creditsBankAgreementNumber'],
            "creditsMonthes" => $f_credit['f_creditsMonthes']
        ];
    }


    $sale['payments'] = $payments;
//  "payments": {
//    "card": {
//      "enabled": true,
//      "value": "11000"
//    },
//    "cash": {
//      "enabled": true,
//      "value": "12000"
//    },
//    "banks": [
//      {
//        
//      }
//    ],
//    "installment": {
//      "enabled": true,
//      "value": "10000"
//    }
//  }



    exit(json_encode(['success' => true, 'sale' => $sale], 288));
}



if (($_JSON['searchby'] ?? false) === 'idclients') {
    $clients = getClientsData(query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`clientsBDay`) AS `clientsBDayTS` "
                            . " FROM `clients` "
                            . " WHERE `idclients` = '" . mres($_JSON['idclients']) . "'"
    )));
    exit(json_encode(['success' => true, 'clients' => $clients], 288));
}

if (($_JSON['searchby'] ?? false) === 'clientsAKNum') {
    $clients = getClientsData(query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`clientsBDay`) AS `clientsBDayTS` "
                            . " FROM `clients` "
                            . " WHERE `clientsAKNum` = '" . mres($_JSON['aknum']) . "'"
                            . " $sortSQL LIMIT 25")));
    exit(json_encode(['success' => true, 'clients' => $clients], 288));
}

if (($_JSON['searchby'] ?? false) === 'clientsPhone') {
    $clients = getClientsData(query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`clientsBDay`) AS `clientsBDayTS` FROM `clients` "
                            . " WHERE `idclients` IN (SELECT `clientsPhonesClient` FROM `clientsPhones` WHERE `clientsPhonesPhone` = '" . mres($_JSON['phone']) . "' AND isnull(`clientsPhonesDeleted`))  "
                            . " $sortSQL"
                            . " LIMIT 25")));
    exit(json_encode(['success' => true, 'clients' => $clients], 288));
}

if (($_JSON['searchby'] ?? false) === 'name') {

    $searchBy = [];
    if (trim($_JSON['lname'] ?? '')) {
        $searchBy[] = " `clientsLName` LIKE '%" . trim($_JSON['lname']) . "%'";
    }
    if (trim($_JSON['fname'] ?? '')) {
        $searchBy[] = " `clientsFName` LIKE '%" . trim($_JSON['fname']) . "%'";
    }
    if (trim($_JSON['mname'] ?? '')) {
        $searchBy[] = " `clientsMName` LIKE '%" . trim($_JSON['mname']) . "%'";
    }
    if ($searchBy) {
        $clients = getClientsData(query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`clientsBDay`) AS `clientsBDayTS` FROM `clients` "
                                . " WHERE "
                                . implode(" AND ", $searchBy)
                                . " $sortSQL"
                                . " LIMIT 25")));
    } else {
        die(json_encode(['success' => false, 'clients' => []], 288));
    }

    exit(json_encode(['success' => true, 'clients' => $clients], 288));
}


if (
        is_array($_JSON['client']) &&
        is_array($_JSON['sale']) &&
        is_array($_JSON['payments']) &&
        is_array($_JSON['personnel'])
) {
    /* CLIENT VALIDATEION */
    $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`= '" . mres($_JSON['client']['id']) . "'"));
    if (!$client) {
        die(json_encode(['success' => false, 'msgs' => ['Ошибка в данных клиента']]));
    }

//	"idf_sales, f_salesNumber, f_salesCreditManager, f_salesClient, f_salesSumm, f_salesComment, f_salesTime, f_salesDate, f_salesType, f_salesCancellationDate, f_salesCancellationSumm, f_salesEntity, f_salesAlert, f_salesAlertBy, import, f_salesGUID, f_salesIsAppendix";
//Добавляем продажу
    mysqlQuery("INSERT INTO `f_sales` SET "
            . "`f_salesNumber` = (SELECT * FROM (SELECT IF(isnull((SELECT MAX(CAST(`f_salesNumber` as SIGNED)) FROM `f_sales` WHERE `f_salesClient`='" . $client['idclients'] . "' AND `f_salesEntity`='" . intval($_JSON['sale']['entity']) . "' AND NOT isnull(`f_salesIsAppendix`))),2,(SELECT MAX(CAST(`f_salesNumber` as SIGNED)) FROM `f_sales` WHERE `f_salesClient`='" . $client['idclients'] . "' AND NOT isnull(`f_salesIsAppendix`))+1)) as `tmp`),"
            . "`f_salesCreditManager` = " . $_USER['id'] . ","
            . "`f_salesClient` = '" . $client['idclients'] . "',"
            . "`f_salesType` = '" . intval($_JSON['sale']['type']) . "',"
            . "`f_salesIsSmall` = " . sqlVON($_JSON['sale']['issmall'] ? '1' : null) . ","
            . "`f_salesEntity` = '" . intval($_JSON['sale']['entity']) . "',"
            . "`f_salesSumm` = '" . round(array_sum(array_map(function ($subscription) {
                                return $subscription['price'] * $subscription['qty'];
                            }, $_JSON['sale']['subscriptions'])), 2) . "',"
            . "`f_salesDate` = '" . mres($_JSON['sale']['date'] ?? date("Y-m-d")) . "',"
            . "`f_salesIsAppendix` = '1',"
            . "`f_salesAdvancePayment` = " . sqlVON(($_JSON['payments']['advancePayment'] ?? false) ? '1' : null, 1) . ","
            . "`f_salesTime` = '" . $NOW . "'"
    );

    $f_sale = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales` = '" . mysqli_insert_id($link) . "'"));
    if (!$f_sale) {
        die(json_encode(['success' => false, 'msgs' => ['Ошибка при внесении продажи']]));
    }

    $entity = mfa(mysqlQuery("SELECT * FROM `entities` LEFT JOIN `entitiesSBISkeys` ON (`identitiesSBISkeys` = (SELECT MAX(`identitiesSBISkeys`) FROM `entitiesSBISkeys` WHERE `entitiesSBISEntity` = `identities`)) WHERE `identities` = '" . mres($f_sale['f_salesEntity']) . "'"));
    $client['phones'] = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`);"));
//Продажа добавлена
    $SBIS["vatSum0"] = null;
    $SBIS["vatNone"] = null;
    $SBIS["vatSum10"] = null;
    $SBIS["vatSum110"] = null;
    $SBIS["vatSum20"] = null;
    $SBIS["vatSum120"] = null;
//Процедурки
    $evotorpositions = [];
    if (isset($_JSON['sale']['subscriptions']) && count($_JSON['sale']['subscriptions'])) {
        foreach ($_JSON['sale']['subscriptions'] as $service) {
            mysqlQuery("INSERT INTO `f_subscriptions` SET "
                    . "	`f_subscriptionsContract`='" . $f_sale['idf_sales'] . "',"
                    . " `f_subscriptionsUser`= '" . $_USER['id'] . "',"
                    . " `f_salesContentService`= '" . mres($service['idservices']) . "',"
                    . " `f_salesContentPrice`= '" . mres(($service['price'] ?? 0)) . "',"
                    . (validateDate($service['validBefore'] ?? '') ? (" `f_subscriptionsExpDate`='" . validateDate($service['validBefore']) . "',") : '')
                    . " `f_salesContentQty`= '" . mres($service['qty']) . "' ");
            $idf_subscriptions = mysqli_insert_id($link);
            if ($service['comment']) {
                mysqlQuery("INSERT INTO `f_subscriptionsComments` SET"
                        . " `f_subscriptionsCommentsSubscription` = '" . $idf_subscriptions . "',"
                        . " `f_subscriptionsCommentsComment` = '" . mres($service['comment']) . "',"
                        . " `f_subscriptionsCommentsAddedBy` = '" . $_USER['id'] . "'"
                        . "");
            }

            if ($service['idf_subscriptionsDraft'] ?? false) {
//биндим черновики если они есть.
                mysqlQuery("INSERT IGNORE INTO  `f_subscriptionsDraftSales` SET"
                        . " `f_subscriptionsDraftSalesSale` = '" . $f_sale['idf_sales'] . "',"
                        . " `f_subscriptionsDraftSalesQty` = '" . mres($service['qty']) . "',"
                        . " `f_subscriptionsDraftSalesDraft` = '" . mres($service['idf_subscriptionsDraft']) . "'");
            }
            if ($service['idservicesApplied'] ?? false) {
//биндим выполненные процедуры если они есть.
                mysqlQuery("UPDATE `servicesApplied` SET "
                        . " `servicesAppliedContract`= '" . $f_sale['idf_sales'] . "',"
                        . " `servicesAppliedPrice`='" . intval($service['price']) . "'"
                        . " WHERE `idservicesApplied` = '" . $service['idservicesApplied'] . "'");
            }


            if ($service['price'] ?? 0) {//подготавливаем данные для эвотора, наверное уже лишнее, но пускай пока будет. Может по аналогии надо будет со СБИС делать.
                $serviceDB = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($service['idservices']) . "'"));
                $evotorpositions[] = [
                    "code" => $serviceDB['idservices'],
                    "name" => $serviceDB['serviceNameShort'] ?? $serviceDB['servicesName'] ?? 'НЕИЗВЕСТНАЯ ПОЗИЦИЯ',
                    "productType" => "NORMAL",
                    "price" => ($service['price'] ?? 0),
                    "quantity" => $service['qty'],
                    "priceWithDiscount" => ($service['price'] ?? 0),
                    "vat" => [null => 'NO_VAT', '0' => 'NO_VAT', '20' => 'VAT_18'][$serviceDB['servicesVat']]
                ];
                if (in_array($f_sale['f_salesType'], [1, 2])) {
                    if ($serviceDB['servicesVat']) {
                        $taxRateNomenclature = (string) "1" . $serviceDB['servicesVat'];
                    } else {
                        $taxRateNomenclature = null;
                    }
                } else {
                    if ($serviceDB['servicesVat']) {
                        $taxRateNomenclature = (string) $serviceDB['servicesVat'];
                    } else {
                        $taxRateNomenclature = null;
                    }
                }

                $SBIS['nomenclatures'][] = [
                    "nameNomenclature" => $serviceDB['serviceNameShort'] ?? $serviceDB['servicesName'] ?? 'НЕИЗВЕСТНАЯ ПОЗИЦИЯ',
                    "barcodeNomenclature" => null,
                    "priceNomenclature" => (string) ($service['price'] ?? 0),
                    "quantityNomenclature" => (string) ($service['qty'] ?? 0),
                    "measureNomenclature" => "шт",
                    "kindNomenclature" => "у",
                    "totalPriceNomenclature" => (string) (($service['price'] ?? 0) * ($service['qty'] ?? 0)),
                    "taxRateNomenclature" => $taxRateNomenclature,
                    "totalVat" => (string) (($service['price'] ?? 0) * ($service['qty'] ?? 0)),
                ];

                switch (true) {
                    case ($serviceDB['servicesVat'] === null || $serviceDB['servicesVat'] === '0'):
                        $SBIS["vatNone"] = ($SBIS["vatNone"] ?? 0) + ($service['price'] ?? 0) * ($service['qty'] ?? 0);
                        break;
                    case ($taxRateNomenclature === '10'):
                        if (in_array($f_sale['f_salesType'], [1, 2])) {
                            $SBIS["vatSum110"] = ($SBIS["vatSum110"] ?? 0) + ($service['price'] ?? 0) * ($service['qty'] ?? 0);
                        } else {
                            $SBIS["vatSum10"] = ($SBIS["vatSum10"] ?? 0) + ($service['price'] ?? 0) * ($service['qty'] ?? 0);
                        }
                        break;
                    case ($taxRateNomenclature === '20'):
                        if (in_array($f_sale['f_salesType'], [1, 2])) {
                            $SBIS["vatSum120"] = ($SBIS["vatSum120"] ?? 0) + ($service['price'] ?? 0) * ($service['qty'] ?? 0);
                        } else {
                            $SBIS["vatSum20"] = ($SBIS["vatSum20"] ?? 0) + ($service['price'] ?? 0) * ($service['qty'] ?? 0);
                        }
                        break;
                }
            }
        }
    }//процедурки


    $postpaySum = array_sum(array_column($_JSON['payments']['banks'], 'value'));

    $SBIS["companyID"] = $entity['SBIScompanyID'] ?? null;
    $SBIS["kktRegNumber"] = ($_JSON['payments']['kkt']['regId'] ?? null); //
    $SBIS["cashierFIO"] = $_USER['lname'] . ' ' . mb_substr($_USER['fname'], 0, 1) . '.';
    $SBIS["operationType"] = "1"; //
    $SBIS["cashSum"] = $_JSON['payments']['cash']['value'] ? ( $_JSON['payments']['cash']['value']) : null;
    $SBIS["bankSum"] = $_JSON['payments']['card']['value'] ? ( $_JSON['payments']['card']['value']) : null;
    $SBIS["internetSum"] = null;
    $SBIS["accountSum"] = null;
    $SBIS["postpaySum"] = $postpaySum ? ( $postpaySum) : null;
    $SBIS["prepaySum"] = null;
    $SBIS["allowRetailPayed"] = "1";
    $SBIS["customerFIO"] = implode(' ', array_filter([$client['clientsLName'], $client['clientsFName'], $client['clientsMName']])); //;
    $SBIS["customerEmail"] = null;
    $SBIS["customerPhone"] = implode(',', array_column(($client['phones'] ?? []), 'clientsPhonesPhone'));
    $SBIS["customerINN"] = null;
    $SBIS["customerExtId"] = $client['idclients'];
    $SBIS["taxSystem"] = "1";
    $SBIS["sendEmail"] = "sashnone@mail.ru";
    $SBIS["sendPhone"] = ($client['phones'][0]['clientsPhonesPhone'] ?? null);
    $SBIS["propName"] = null;
    $SBIS["propVal"] = null;
    $SBIS["comment"] = "тестовый чек";
    $SBIS["externalId"] = null;
    if (in_array($f_sale['f_salesType'], [1, 2])) {
        $SBIS["payMethod"] = "1";
    } else {
        $SBIS["payMethod"] = "4";
    }
    foreach ($SBIS as $key => $value) {
        if ($value !== null && !is_array($value)) {
            $SBIS[$key] = (string) $value;
        }
    }

    sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'JSON:' . json_encode($_JSON, JSON_UNESCAPED_UNICODE + 128) . '']);
//	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'SBIS:' . json_encode($SBIS, JSON_UNESCAPED_UNICODE + 128) . '']);
    if (($SBIS["companyID"] ?? false) && ($_JSON['payments']['kkt']['model'] ?? '') === 'АТОЛ 30Ф') {
        sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => '✳️✳️✳️✳️✳️✳️✳️ОТПРАВЛЯЮ ОПЛАТУ В СБИС:']);
        $ch = curl_init('https://api.sbis.ru/retail/sale/create');
        curl_setopt_array($ch, array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HEADER => 0,
            CURLOPT_POSTFIELDS => json_encode($SBIS),
            CURLOPT_HTTPHEADER => array(
                'Content-type:  application/json; charset=utf-8',
                'X-SBISAccessToken: ' . $entity['token']
            ),
        ));
        $response = curl_exec($ch);
        sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'ОТВЕТ СБИС:' . json_encode(json_decode($response, 1), 288 + 128)]);
    }


    if (($_JSON['payments']['cash']['value'] ?? 0) || ($_JSON['payments']['card']['value'] ?? 0)) {
        if ($_JSON['sale']['entity']) {
            $url = 'https://dclubs.ru/evotor/orders/api/3rdparty/v2/order/' . EVOTORGUID;
            $dataToSend = [
                "type" => "SELL",
                "number" => $f_sale['idf_sales'],
                "period" => time(),
                "state" => "new",
                "client" => $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'],
                "id" => $f_sale['idf_sales'],
                "positions" => $evotorpositions
            ];

            if (1) {
                $ch = curl_init($url);
                $payload = json_encode($dataToSend);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . EVOTORBearer]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
//				sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'EVOTOR DATA RESULT:' . $result]);
                curl_close($ch);
            }
        }
    }




//банковские дела
    foreach (($_JSON['payments']['banks'] ?? []) as $bank) {
        if ($bank['enabled'] ?? false) {
            mysqlQuery("INSERT INTO `f_credits` SET "
                    . " `f_creditsBankID` = '" . FSI($bank['idbank']) . "',"
                    . " `f_creditsBankAgreementNumber` = '" . mres($bank['agreementNumber']) . "',"
                    . " `f_creditsSumm` = '" . FSI($bank['value']) . "', "
                    . " `f_creditsMonthes` = '" . FSS($bank['creditsMonthes']) . "', "
                    . " `f_creditsSalesID` = '" . $f_sale['idf_sales'] . "'"
                    . "");
        }
    }
//кэш
    if ($_JSON['payments']['cash']['enabled'] ?? false) {
        mysqlQuery("INSERT INTO `f_payments` SET  "
                . "`f_paymentsSalesID` = '" . $f_sale['idf_sales'] . "', "
                . "`f_paymentsType` = '1', "
                . (($_JSON['sale']['date'] && $_JSON['sale']['date'] !== date("Y-m-d")) ? ("`f_paymentsDate` = '" . $_JSON['sale']['date'] . " 04:00:00', ") : ("`f_paymentsDate` = '" . $NOW . "', "))
                . "`f_paymentsUser` = '" . $_USER['id'] . "', "
                . "`f_paymentsClient` = (SELECT `f_salesClient` FROM `f_sales` WHERE `idf_sales` = '" . $f_sale['idf_sales'] . "' ), "
                . "`f_paymentsAmount`='" . mres($_JSON['payments']['cash']['value']) . "'");
    }
// эквайринг
    if ($_JSON['payments']['card']['enabled'] ?? false) {
        mysqlQuery("INSERT INTO `f_payments` SET  "
                . "`f_paymentsSalesID` = '" . $f_sale['idf_sales'] . "', "
                . "`f_paymentsType` = '2', "
                . (($_JSON['sale']['date'] && $_JSON['sale']['date'] !== date("Y-m-d")) ? ("`f_paymentsDate` = '" . $_JSON['sale']['date'] . " 04:00:00', ") : ("`f_paymentsDate` = '" . $NOW . "', "))
                . "`f_paymentsUser` = '" . $_USER['id'] . "', "
                . "`f_paymentsClient` = (SELECT `f_salesClient` FROM `f_sales` WHERE `idf_sales` = '" . $f_sale['idf_sales'] . "' ), "
                . "`f_paymentsAmount`='" . mres($_JSON['payments']['card']['value']) . "'");
    }

// баланс
    if ($_JSON['payments']['balance']['enabled'] ?? false) {
        mysqlQuery("INSERT INTO `f_balance` SET  "
                . "`f_balanceSalesID` = '" . $f_sale['idf_sales'] . "', "
                . (($_JSON['sale']['date'] && $_JSON['sale']['date'] !== date("Y-m-d")) ? ("`f_balanceTime` = '" . $_JSON['sale']['date'] . " 04:00:00', ") : ("`f_balanceTime` = '" . $NOW . "', "))
                . "`f_balanceUser` = '" . $_USER['id'] . "', "
                . "`f_balanceClient` = (SELECT `f_salesClient` FROM `f_sales` WHERE `idf_sales` = '" . $f_sale['idf_sales'] . "' ), "
                . "`f_balanceAmount`='" . mres($_JSON['payments']['balance']['value']) . "'");
    }

//рассрочка
    if ($_JSON['payments']['installment']['enabled'] ?? false) {
        mysqlQuery("INSERT INTO `f_installments` SET  "
                . "`f_installmentsSalesID` = '" . $f_sale['idf_sales'] . "', "
                . "`f_installmentsPeriod` = '1', "
                . "`f_installmentsSumm`='" . mres($_JSON['payments']['installment']['value']) . "'");
    }

// добавляем участников
    foreach ($_JSON['personnel'] as $idf_roles => $role) {
        foreach ($role['users'] as $user) {
            mysqlQuery("INSERT INTO `f_salesRoles` SET"
                    . " `f_salesRolesSale` = '" . $f_sale['idf_sales'] . "',"
                    . " `f_salesRolesUser` = '" . mres($user['idusers']) . "',"
                    . " `f_salesRolesRole` = '" . mres($idf_roles) . "'");
        }
    }
//Участники по старой системе
    foreach (($_JSON['personnel']['4']['users'] ?? []) as $user) {
        mysqlQuery("INSERT INTO `f_salesToCoord` SET"
                . " `f_salesToCoordSalesID` = '" . $f_sale['idf_sales'] . "',"
                . " `f_salesToCoordCoord` = '" . mres($user['idusers']) . "'");
    }
    foreach (['1', '2', '3'] as $role) {
        foreach (($_JSON['personnel'][$role]['users'] ?? []) as $user) {
            mysqlQuery("INSERT INTO `f_salesToPersonal` SET"
                    . " `f_salesToPersonalSalesID` = '" . $f_sale['idf_sales'] . "',"
                    . " `f_salesToPersonalUser` = '" . mres($user['idusers']) . "'");
        }
    }



    if (1) {
        $url = 'https://api.calltouch.ru/lead-service/v1/api/client-order/create';

        $tags = [];

        $saPersonnel = query2array(mysqlQuery("SELECT * "
                        . " FROM `servicesApplied`"
                        . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                        . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
                        . " WHERE `servicesAppliedDate` = CURDATE() "
                        . " AND `servicesAppliedClient` = '" . $client['idclients'] . "'"
                        . " AND isnull(`servicesAppliedDeleted`) "
                        . " AND (isnull(`servicesAppliedContract`) OR `servicesAppliedContract`='" . $f_sale['idf_sales'] . "')"
                        . " AND NOT isnull(`servicesAppliedFineshed`)"
                        . ";"));
        foreach ($saPersonnel as $sa) {
            if ($sa['usersLastName'] && !in_array($sa['usersLastName'] . ' ' . $sa['usersFirstName'], array_column($tags, 'tag'))) {
                $tags[] = ['tag' => $sa['usersLastName'] . ' ' . $sa['usersFirstName']];
            }
            if ($sa['servicesName'] && !in_array(($sa['serviceNameShort'] ?? $sa['servicesName']), array_column($tags, 'tag'))) {
                $tags[] = ['tag' => ($sa['serviceNameShort'] ?? $sa['servicesName'])];
            }
        }

        $dataToSend = [
            "crm" => "menua",
            "orders" => [
                [
                    "matching" => [
                        [
                            "type" => "callContact",
                            "callContactParams" => [
                                "phones" => array_column(query2array(mysqlQuery("SELECT `clientsPhonesPhone` FROM `clientsPhones` WHERE `clientsPhonesClient`='" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`)")), 'clientsPhonesPhone'),
                                "date" => date("d-m-Y H:i:s"),
                                "callTypeToMatch" => "nearest",
                                "searchDepth" => 12000
                            ]
                        ]
                    ],
                    "orderNumber" => SMSNAME . '.' . $f_sale['idf_sales'],
                    "status" => "Абонемент",
                    "statusDate" => date("d-m-Y H:i:s"),
                    "orderDate" => date("d-m-Y H:i:s"),
                    "revenue" => $f_sale['f_salesSumm'],
                    "manager" => SMSNAME,
                    "comment" => [
                        "text" => "https://" . SUBDOMEN . "menua.pro/pages/checkout/payments.php?client=" . $client['idclients'] . "&contract=" . $f_sale['idf_sales']
                    ],
                    "addTags" => $tags
                ]
            ]
        ];
        if (!count($tags)) {
            unset($dataToSend['orders']['0']['addTags']);
        }
        if (1) {
            $ch = curl_init($url);
            $payload = json_encode($dataToSend);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Access-Token: qq6qtZvSv9r9zhsOte2iRLHPG4lNMIoeMqMf3erDAa/AZ',
                'SiteId: 43769']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
//				sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => '📞' . $result]);
            curl_close($ch);
            $result = json_decode($result, 1);
            if ($result['data']['orders'][0]['calltouchOrderId'] ?? false) {
                foreach (getUsersByRights([167]) as $user) {
                    if ($user['usersTG'] ?? false) {
                        sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => '📞' . ' Добавлена продажа и привязана к Calltouch' . "\n" . 'https://my.calltouch.ru/accounts/29140/sites/43769/reports/deals-journal?dealId=' . $result['data']['orders'][0]['calltouchOrderId']]);
                    }
                }
            }
        }
    }



    ///Краткий отчёт о продаже
    if ($f_sale['f_salesType'] == 1 || $f_sale['f_salesType'] == 2) {
        $participants = query2array(mysqlQuery("SELECT * "
                        . " FROM `f_salesRoles`"
                        . " LEFT JOIN `users` ON (`idusers` = `f_salesRolesUser`)"
                        . " WHERE `f_salesRolesSale`=" . $f_sale['idf_sales'] . " AND `f_salesRolesRole` IN (1,2,3)"));
        $participantsStr = 'Без участников';
        if (count($participants)) {
            $participantsStr = implode(" / ", array_column($participants, 'usersLastName'));
        }
        $salesQty = mfa(mysqlQuery("SELECT count(1) as `cnt` FROM `f_sales` WHERE `f_salesDate`=CURDATE() AND f_salesType IN (1,2)"))['cnt'] ?? 0;
        $text = (intval($f_sale['f_salesType']) == 1 ? urldecode("1%EF%B8%8F%E2%83%A3") : '') . '🏆 ' . ($participantsStr) . " (" . ($f_sale['f_salesSumm'] ?? '?2?') . " руб.)\r\nИтого: " . ($salesQty ?? 0) . ' ✅';
        telegramSendByRights([58], $text);
    }

    ///\\\Краткий отчёт о продаже


    exit(json_encode(['success' => true, 'sale' => $f_sale['idf_sales'], 'client' => $client['idclients'], 'msgs' => ['Кажется всё получилось!']]));
//
/////
}

