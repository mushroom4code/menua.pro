<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (!($_JSON['search'] ?? false)) {
    die(json_encode(['success' => false, 'msgs' => [['type' => 'error', 'text' => 'Пустой запрос']]], 288));
}
if (mb_strlen(trim($_JSON['search'])) < 3) {
    die(json_encode(['success' => false, 'msgs' => [['type' => 'error', 'text' => 'Слишком короткий запрос. Дополните.']]], 288));
}

$n = 0;
if (0 && ctype_digit((string) trim($_JSON['search']))) {
    $search = (string) trim($_JSON['search']);
    $parent = 1;
    while (strlen($search) > 1 && $n < 10) {
        $n++;
        $servicesCode = $search[0] . $search[1];
        $search = substr($search, 2, strlen($search) - 2);
        $sql = "SELECT "
                . "`idservices`,"
                . "`servicesParent`,"
                . "`servicesCode`,"
                . "`servicesName`,"
                . "`serviceNameShort`,"
                . "`servicesDuration`,"
                . "`servicesVat`,"
                . "`servicescolN804`,"
                . "(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
(PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
 SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 1) as `price` , "
                . "(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
(PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
 SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 1) as `priceMin`,  "
                . "(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
(PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
 SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 2) as `priceMax`,"
                . "(SELECT `servicesDescriptionsDescription` FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM servicesDescriptions WHERE`servicesDescriptionsService` = `idservices`)) as `serviceDescription`   "
                . " FROM `services` "
                . "WHERE isnull(`servicesDeleted`) "
                . "AND `servicesParent` = '" . $parent . "'"
                . " AND `servicesCode` = '" . $servicesCode . "'";
        $tempResult = query2array(mysqlQuery($sql));
        if ($tempResult) {
            $parent = $tempResult[0]['idservices'];
            $searchResult = $tempResult;
        }
    }
} else {


    $_search = explode(' ', preg_replace('/\s+/', ' ', preg_replace('/[^\w ]/u', ' ', trim($_JSON['search']))));
    $_searchRow = [];
    foreach ($_search as $_searchElement) {
        $_searchRow[] = "`servicesName` LIKE '%" . mres($_searchElement) . "%' ";
    }
    $_searchRowShort = [];
    foreach ($_search as $_searchElement) {
        $_searchRowShort[] = "`serviceNameShort` LIKE '%" . mres($_searchElement) . "%' ";
    }

    $sql = "SELECT *,"
//            . " ifnull((SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='2') AND `servicesPricesType`='2'  AND `servicesPricesService` = `idservices`)),(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`))) as `price`, "
            ."(SELECT `servicesDescriptionsDescription` FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM `servicesDescriptions` WHERE`servicesDescriptionsService` = `idservices`)) as `serviceDescription`
            ,(SELECT COUNT(1) FROM `servicesPrimecost` WHERE `servicesPrimecostService` = `idservices`) as `PCqty`"
            . " , GREATEST((SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`),(SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = `idservices`)) AS `personal`"
            . " ,(SELECT COUNT(1) FROM `servicesApplied` WHERE `servicesAppliedService` = `idservices`) as `servicesApplied`"
            . " ,(SELECT COUNT(1) FROM `f_subscriptions` WHERE `f_salesContentService` = `idservices`) as `f_subscriptions`"
            . " ,(SELECT COUNT(1) FROM `servicesEquipment` WHERE `servicesEquipmentService` = `idservices`) as `servicesEquipmentQty`"
            . " ,(SELECT COUNT(1) FROM `servicesGUIDs` WHERE `servicesGUIDsService` = `idservices`) as `GUIDsQty`
    ,(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
   (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 1) as `priceMin`"
         . ",(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
   (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 2) as `priceMax`"
         . ",(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
   (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 3) as `minCost`"
         . ",(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
   (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 4) as `maxCost`,"
            . "(SELECT `servicesDescriptionsDescription` FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM servicesDescriptions WHERE`servicesDescriptionsService` = `idservices`)) as `serviceDescription`   "
            . " FROM  `services`"
            . " WHERE isnull(`servicesDeleted`)"
//            . " AND (isnull(`servicesEntryType`) OR `servicesEntryType` IN (2,3,4))"
            . " AND ("
//            . " `servicescolN804` LIKE '%" . mres($_JSON['search']) . "%' "
//            . " OR `servicesSupplierCode` LIKE '%" . mres($_JSON['search']) . "%'"
            . "  (" . implode(' AND ', $_searchRow) . ")"
            . " OR (" . implode(' AND ', $_searchRowShort) . ")"
            . ")"
            . (($_JSON['newonly'] ?? false) ? " AND NOT isnull(`servicesParent`) " : "");
    $searchResults = query2array(mysqlQuery($sql));
}

if (!($searchResults ?? [])) {
    die(json_encode(['success' => false, 'msgs' => [['type' => 'error', 'text' => 'Поиск не дал результатов. Измените запрос.', 'sql' => ($sql ?? 'Пустой запрос')]]], 288));
}

foreach (($searchResults ?? []) as $searchResultsIndex => $searchResult) {
    $searchResults[$searchResultsIndex]['price'] = $searchResult['priceMax'] ?? $searchResult['priceMin'];
}


foreach ($searchResults as &$searchResultEntry) {
//    $n = 0;
//    while ($searchResultEntry['servicesParent'] !== '1' && $n < 10) {
//        $n++;
//        $parent = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . $searchResultEntry['servicesParent'] . "'"));
//        if ($parent) {
//            $searchResultEntry['servicesParent'] = $parent['servicesParent'];
//            $searchResultEntry['servicesCode'] = (string) $parent['servicesCode'] . (string) $searchResultEntry['servicesCode'];
//        } else {
//            break;
//        }
//    }
//
    if ($_search ?? false) {
        $searchResultEntry['servicesNameHighlighted'] = preg_replace('/' . implode('|', $_search) . '/iu', '<span style="color:red;"><b>$0</b></span>', $searchResultEntry['servicesName']);
    } else {
        $searchResultEntry['servicesNameHighlighted'] = $searchResultEntry['servicesName'];
    }
}
exit(json_encode(['success' => true, 'sql' => $sql, 'services' => $searchResults, 'example' => "WITH `prices` AS ("
    . "SELECT *, ROW_NUMBER() OVER (PARTITION BY `itemPricesGroup`,`itemPricesSize` ORDER BY `iditemPrices` DESC) AS `rowNumber`"
    . "  FROM `itemPrices`"
    . " WHERE `itemPricesAddedTime` <= '" . date("Y-m-d") . "'"
    . " AND `itemPricesItem`=''"
    . ")"
    . " SELECT * FROM `prices` "
    . " LEFT JOIN `itemsSizes` ON (`iditemsSizes` = `itemPricesSize`)"
    . " LEFT JOIN `itemsPricesGroups` ON (`iditemsPricesGroups` = `itemPricesGroup`)"
    . " WHERE `itemPricesItem`=''"
    . " AND isnull(`itemsPricesGroupsDeletedTime`) "
    . " AND `rowNumber` = 1;"], JSON_UNESCAPED_UNICODE));
