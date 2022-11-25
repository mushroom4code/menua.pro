<?php

if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    $_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
    $_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
    $_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
$today = date("Y-m-d");
$tomorrow = date("Y-m-d", strtotime("+1 day"));
$weekStart = date("Y-m-d", strtotime("last monday")); //date("N") == 1 ? date("Y-m-d") :
// Если сегодня понедельник, берем сегодняшнюю дату,
//  если нет, то дату последнего (прошедшего) понедельника
$monthStart = date("Y-m-01");

$appliedTomorrow = query2array(mysqlQuery(""
                . " SELECT `clients`.*,`servicesAppliedDate`, (SELECT COUNT(1) FROM `clientsVisits` WHERE"
                . " `clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = `servicesAppliedDate`) as `clientVisit`"
                . " FROM `servicesApplied`"
                . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
                . " WHERE "
                . " NOT isnull(`clientsOldSince`) "
                . " AND `clientsOldSince`<'$tomorrow'"
                . " AND `servicesAppliedDate`='$tomorrow'"
                . " AND isnull(servicesAppliedDeleted)"
                . " GROUP BY `servicesAppliedClient`,`servicesAppliedDate`"
                . " ORDER BY `clientsLName`,`clientsFName`,`clientsMName`"));

$appliedToday = query2array(mysqlQuery(""
                . " SELECT `clients`.*,`servicesAppliedDate`, (SELECT COUNT(1) FROM `clientsVisits` WHERE"
                . " `clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = `servicesAppliedDate`) as `clientVisit` FROM `servicesApplied`"
                . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
                . " WHERE"
                . " `servicesAppliedDate`='$today'"
                . " AND (isnull(servicesAppliedDeleted) OR servicesAppliedDeleted<'$today')"
                . " AND NOT isnull(`clientsOldSince`) "
                . " AND `clientsOldSince`<'$today'"
                . " GROUP BY `servicesAppliedClient`,`servicesAppliedDate`"
                . " ORDER BY `clientsLName`,`clientsFName`,`clientsMName`"));

$visitsToday = array_filter($appliedToday, function ($client) {
    return $client['clientVisit'] > 0;
});
$appliedMonth = query2array(mysqlQuery(""
                . " SELECT `clients`.*,`servicesAppliedDate`, (SELECT COUNT(1) FROM `clientsVisits` WHERE"
                . " `clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = `servicesAppliedDate`) as `clientVisit` FROM `servicesApplied`"
                . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup` IN (8,9) AND `servicesAppliedDate`>='$monthStart'"
                . " and `servicesAppliedDate`<=CURDATE() GROUP BY `servicesAppliedClient`,`servicesAppliedDate`"
                . " ORDER BY `clientsLName`,`clientsFName`,`clientsMName`"));

$visitsMonth = array_filter($appliedMonth, function ($client) {
    return $client['clientVisit'] > 0;
});

$remains = query2array(mysqlQuery("select 
	`sale`, `service`, `price`, `qty`, `clients`.* ,
	(SELECT MAX(`servicesAppliedDate`) FROM `servicesApplied` WHERE `servicesAppliedClient` = `idclients` AND not isnull(`servicesAppliedContract`)) as `lastServicesApplied`
from (select `sale`, `service`, `price`, sum(`qty`) as `qty` FROM (
SELECT 
`f_subscriptionsContract` as `sale`,
`f_salesContentService` as `service`,
`f_salesContentPrice` as `price`,
SUM(`f_salesContentQty`) as `qty`
 FROM `f_subscriptions` 
group by `f_subscriptionsContract`,`f_salesContentService`,`f_salesContentPrice`
union all
select 
`servicesAppliedContract` as `sale`,
`servicesAppliedService` as `service`,
`servicesAppliedPrice` as `price`,
 sum(-`servicesAppliedQty`) as `qty`

 from `servicesApplied`
where isnull(`servicesAppliedDeleted`) and not isnull(`servicesAppliedContract`) GROUP BY 
`servicesAppliedContract`,`servicesAppliedPrice`,`servicesAppliedService`
) as `subsNsa`  GROUP BY  `sale`, `service`, `price`) as `remains`
 LEFT JOIN `f_sales` ON (`idf_sales` = `sale`)
 LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)
 
WHERE isnull(`f_salesCancellationDate`) 
AND (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesSumm`>15000 AND `f_salesClient` = `idclients`)>0

"));

$clients = [];

foreach ($remains as $remain) {
    $clients[$remain['idclients']]['sales'][$remain['sale']]['remains'] = ($clients[$remain['idclients']]['sales'][$remain['sale']]['remains'] ?? 0) + max(0, $remain['qty']);
    $clients[$remain['idclients']]['lastServicesApplied'] = $remain['lastServicesApplied'];
}
foreach ($clients as $clientsIndex => $client) {
    $clients[$clientsIndex]['notEmptySales'] = count(array_filter($client['sales'], function ($sale) {
                return $sale['remains'] > 0;
            }));
}

$clientsW2orless = array_filter($clients, function ($client) {
    return $client['notEmptySales'] > 0 && $client['notEmptySales'] <= 2;
});
$clientsW3_5sales = array_filter($clients, function ($client) {
    return $client['notEmptySales'] >= 2 && $client['notEmptySales'] <= 5;
});
$clientsWmore5sales = array_filter($clients, function ($client) {
    return $client['notEmptySales'] > 5;
});

$clientsWnoRemains = array_filter($clients, function ($client) {
    return $client['notEmptySales'] == 0;
});

$monthes = [
    '1_3' => [],
    '3_6' => [],
    '6_12' => [],
    '12+' => [],
];

//__________________I____________________________________V______________________________________________________
//                  ++++++++++ +1month +++++++++ I
//                  ++++++++++++++++++++++++++++++++++++++++++++++++++++++ +3month +++++++++ I






foreach ($clientsWnoRemains as $clientsWnoRemainsIndex => $clientWnoRemains) {


    if (
            strtotime($clientWnoRemains['lastServicesApplied'] . " +1 month") <= time() &&
            time() < strtotime($clientWnoRemains['lastServicesApplied'] . " +3 month")
    ) {
        $monthes['1_3'][] = $clientWnoRemains;
    } elseif (
            strtotime($clientWnoRemains['lastServicesApplied'] . " +3 month") <= time() &&
            time() < strtotime($clientWnoRemains['lastServicesApplied'] . " +6 month")
    ) {
        $monthes['3_6'][] = $clientWnoRemains;
    } elseif (
            strtotime($clientWnoRemains['lastServicesApplied'] . " +6 month") <= time() &&
            time() < strtotime($clientWnoRemains['lastServicesApplied'] . " +12 month")
    ) {
        $monthes['6_12'][] = $clientWnoRemains;
    } elseif (
            strtotime($clientWnoRemains['lastServicesApplied'] . " +12 month") < time()
    ) {
        $monthes['12+'][] = $clientWnoRemains;
    }
}

$text = "1. Назначено на сегодня " . count($appliedToday) . "
   из них пришло " . count($visitsToday) . "
2. На месяц было назначено " . count($appliedMonth) . "
   из них пришло " . count($visitsMonth) . "
3. Назначено на завтра  " . count($appliedTomorrow) . "
Сортировка клиентов по абонементам
	до 2х абонементов (с остатками) " . count($clientsW2orless) . "
	от 3 до 5 абонементов " . count($clientsW3_5sales) . "
	от 5 и более " . count($clientsWmore5sales) . "
            
         без остатков " . count($clientsWnoRemains) . "   
       от 1го до 3х месяцев " . count($monthes['1_3']) . "      
       от 3го до 6х месяцев " . count($monthes['3_6']) . "      
       от 6го до 12х месяцев " . count($monthes['6_12']) . "      
       от 12го и более " . count($monthes['12+']) . "      
             "; //(с 1го числа до даты отчёта)
//printr($clientsWnoRemains, 1);
if ($_GET['root'] ?? false) {
    telegramSendByRights([192], $text);
}
printr($text ?? 'no text', 1);
