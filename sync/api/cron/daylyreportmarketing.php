<?php

die();
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';

//Прибавление:
$yesterday = date("Y-m-d", strtotime("yesterday")); //Если сегодня понедельник, берем сегодняшнюю дату, если нет, то дату последнего (прошедшего) понедельника
$weekStart = date("Y-m-d", strtotime("last monday")); //date("N") == 1 ? date("Y-m-d") : Если сегодня понедельник, берем сегодняшнюю дату, если нет, то дату последнего (прошедшего) понедельника
$monthStart = date("Y-m-01");
//printr($monthStart);


$appliedYesterday = query2array(mysqlQuery("SELECT count(1) as `cnt`,`clientsSource`,`clientsSourcesName` FROM `clients` LEFT JOIN `clientsSources` on (`idclientsSources` = `clientsSource`) WHERE `idclients`  in (SELECT `servicesAppliedClient` as `idclient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=12 AND `servicesAppliedDate`='$yesterday'  GROUP BY `servicesAppliedClient`) GROUP BY `clientsSource`"));
$appliedWeek = query2array(mysqlQuery("SELECT count(1) as `cnt`,`clientsSource`,`clientsSourcesName` FROM `clients` LEFT JOIN `clientsSources` on (`idclientsSources` = `clientsSource`) WHERE `idclients`  in (SELECT `servicesAppliedClient` as `idclient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=12 AND `servicesAppliedDate`>='$weekStart' and `servicesAppliedDate`<=CURDATE() GROUP BY `servicesAppliedClient`) GROUP BY `clientsSource`"));
$appliedMonth = query2array(mysqlQuery("SELECT count(1) as `cnt`,`clientsSource`,`clientsSourcesName` FROM `clients` LEFT JOIN `clientsSources` on (`idclientsSources` = `clientsSource`) WHERE `idclients`  in (SELECT `servicesAppliedClient` as `idclient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=12 AND `servicesAppliedDate`>='$monthStart' and `servicesAppliedDate`<=CURDATE() GROUP BY `servicesAppliedClient`) GROUP BY `clientsSource`"));

$visitsYesterday = query2array(mysqlQuery("SELECT count(1) as `cnt`,`clientsSource`,`clientsSourcesName` FROM `clients` LEFT JOIN `clientsSources` on (`idclientsSources` = `clientsSource`) WHERE `idclients`  in (SELECT `servicesAppliedClient` as `idclient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=12 AND `servicesAppliedDate`='$yesterday' AND (SELECT COUNT(1) FROM `score` WHERE `scoreClient`=`servicesAppliedClient` AND `scoreMarket`='1' AND `scoreDate`='$yesterday')>0 GROUP BY `servicesAppliedClient`)  GROUP BY `clientsSource`"));
$visitsWeek = query2array(mysqlQuery("SELECT count(1) as `cnt`,`clientsSource`,`clientsSourcesName` FROM `clients` LEFT JOIN `clientsSources` on (`idclientsSources` = `clientsSource`) WHERE `idclients`  in (SELECT `servicesAppliedClient` as `idclient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=12 AND `servicesAppliedDate`>='$weekStart' and `servicesAppliedDate`<=CURDATE() AND (SELECT COUNT(1) FROM `score` WHERE `scoreClient`=`servicesAppliedClient` AND `scoreMarket`='1' AND `scoreDate`>='$weekStart' and `scoreDate`<=CURDATE())>0 GROUP BY `servicesAppliedClient`)  GROUP BY `clientsSource`"));
$visitsMonth = query2array(mysqlQuery("SELECT count(1) as `cnt`,`clientsSource`,`clientsSourcesName` FROM `clients` LEFT JOIN `clientsSources` on (`idclientsSources` = `clientsSource`) WHERE `idclients`  in (SELECT `servicesAppliedClient` as `idclient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=12 AND `servicesAppliedDate`>='$monthStart' and `servicesAppliedDate`<=CURDATE() AND (SELECT COUNT(1) FROM `score` WHERE `scoreClient`=`servicesAppliedClient` AND `scoreMarket`='1' AND `scoreDate`>='$monthStart' and `scoreDate`<=CURDATE())>0 GROUP BY `servicesAppliedClient`)  GROUP BY `clientsSource`"));

$query = "SELECT COUNT(1) AS `cnt`, SUM(f_salesSumm) as `f_salesSumm`, clientsSource, clientsSourcesName FROM (SELECT 
           *
        FROM
            `f_sales`
            left join `clients` on (`idclients`=`f_salesClient`)
        WHERE
            `f_salesClient` IN (SELECT 
                    `servicesAppliedClient` AS `idclient`
                FROM
                    `servicesApplied`
                        LEFT JOIN
                    `users` ON (`idusers` = `servicesAppliedBy`)
                WHERE
                    `usersGroup` = 12
                        AND `servicesAppliedDate` = '$yesterday'
                        AND (SELECT 
                            COUNT(1)
                        FROM
                            `f_sales`
                        WHERE
                            `f_salesClient` = `servicesAppliedClient`
                                AND `f_salesDate` = '$yesterday') > 0
               )
                AND `f_salesDate` = '$yesterday') as `sales`
                LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)
                group by clientsSource;";
//print $query;

$salesYesterday = query2array(mysqlQuery($query));

$salesWeek = query2array(mysqlQuery("SELECT COUNT(1) AS `cnt`, SUM(f_salesSumm) as `f_salesSumm`, clientsSource, clientsSourcesName FROM (SELECT 
           *
        FROM
            `f_sales`
            left join `clients` on (`idclients`=`f_salesClient`)
        WHERE
            `f_salesClient` IN (SELECT 
                    `servicesAppliedClient` AS `idclient`
                FROM
                    `servicesApplied`
                        LEFT JOIN
                    `users` ON (`idusers` = `servicesAppliedBy`)
                WHERE
                    `usersGroup` = 12
                        AND `servicesAppliedDate` >= '$weekStart'
                        AND (SELECT 
                            COUNT(1)
                        FROM
                            `f_sales`
                        WHERE
                            `f_salesClient` = `servicesAppliedClient`
                                AND `f_salesDate` >= '$weekStart' AND `f_salesDate`<=CURDATE()) > 0
               )
                AND `f_salesDate` >= '$weekStart' AND `f_salesDate`<=CURDATE()) as `sales`
                LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)
                group by clientsSource;"));

//$weekStart
$salesMonth = query2array(mysqlQuery("SELECT COUNT(1) AS `cnt`, SUM(f_salesSumm) as `f_salesSumm`, clientsSource, clientsSourcesName FROM (SELECT 
           *
        FROM
            `f_sales`
            left join `clients` on (`idclients`=`f_salesClient`)
        WHERE
            `f_salesClient` IN (SELECT 
                    `servicesAppliedClient` AS `idclient`
                FROM
                    `servicesApplied`
                        LEFT JOIN
                    `users` ON (`idusers` = `servicesAppliedBy`)
                WHERE
                    `usersGroup` = 12
                        AND `servicesAppliedDate` >= '$monthStart'
                        AND (SELECT 
                            COUNT(1)
                        FROM
                            `f_sales`
                        WHERE
                            `f_salesClient` = `servicesAppliedClient`
                                AND `f_salesDate` >= '$monthStart' AND `f_salesDate`<=CURDATE()) > 0
               )
                AND `f_salesDate` >= '$monthStart' AND `f_salesDate`<=CURDATE()) as `sales`
                LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)
                group by clientsSource;"));

print mysqli_error($link);
printr($salesYesterday);
//printr($salesMonth);

$report = [];

foreach ($appliedYesterday as $row) {
	$report['yesterday'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['yesterday'][$row['clientsSource']]['applied'] = $row['cnt'];
}
foreach ($visitsYesterday as $row) {
	$report['yesterday'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['yesterday'][$row['clientsSource']]['visits'] = $row['cnt'];
}
foreach ($salesYesterday as $row) {
	$report['yesterday'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['yesterday'][$row['clientsSource']]['sales'] = $row['cnt'];
	$report['yesterday'][$row['clientsSource']]['salesSumm'] = $row['f_salesSumm'];
}



foreach ($appliedWeek as $row) {
	$report['week'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['week'][$row['clientsSource']]['applied'] = $row['cnt'];
}
foreach ($visitsWeek as $row) {
	$report['week'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['week'][$row['clientsSource']]['visits'] = $row['cnt'];
}
foreach ($salesWeek as $row) {
	$report['week'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['week'][$row['clientsSource']]['sales'] = $row['cnt'];
	$report['week'][$row['clientsSource']]['salesSumm'] = $row['f_salesSumm'];
}







foreach ($appliedMonth as $row) {
	$report['month'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['month'][$row['clientsSource']]['applied'] = $row['cnt'];
}
foreach ($visitsMonth as $row) {
	$report['month'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['month'][$row['clientsSource']]['visits'] = $row['cnt'];
}
foreach ($salesMonth as $row) {
	$report['month'][$row['clientsSource']]['name'] = $row['clientsSourcesName'];
	$report['month'][$row['clientsSource']]['sales'] = $row['cnt'];
	$report['month'][$row['clientsSource']]['salesSumm'] = $row['f_salesSumm'];
}


//printr($report, 1);
//printr($appliedWeek);
//printr($visitsWeek);
//printr($appliedMonth);
//printr($visitsMonth);
//printr($remainsDoneWeek);
//printr($remainsMonth);
//printr($appliedMonth);


$text = "ℹ️ МАРКЕТИНГ\n\n"
		. "<b>Вчера</b>:\n";

foreach ($report['yesterday'] as $source) {
	$text .= $source['name'] . " - "
			. ($source['applied'] ?? 0) . "/"
			. ($source['visits'] ?? 0) . " ("
			. ($source['sales'] ?? 0) . "/"
			. number_format($source['salesSumm'] ?? 0, 0, ',', ' ') . ")" . "\n";
}
$text .= "<b>Итого: "
		. array_sum(array_column($report['yesterday'], 'applied')) . "/"
		. array_sum(array_column($report['yesterday'], 'visits')) . " ("
		. array_sum(array_column($report['yesterday'], 'sales')) . "/"
		. number_format(array_sum(array_column($report['yesterday'], 'salesSumm')), 0, ',', ' ') . ")"
		. "</b>\n";

$text .= "\n<b>Неделя:</b>\n";
foreach ($report['week'] as $source) {
	$text .= $source['name'] . " - "
			. ($source['applied'] ?? 0) . "/"
			. ($source['visits'] ?? 0) . " ("
			. ($source['sales'] ?? 0) . "/"
			. number_format($source['salesSumm'] ?? 0, 0, ',', ' ') . ")" . "\n";
}



$text .= "<b>Итого: "
		. array_sum(array_column($report['week'], 'applied')) . "/"
		. array_sum(array_column($report['week'], 'visits')) . " ("
		. array_sum(array_column($report['week'], 'sales')) . "/"
		. number_format(array_sum(array_column($report['week'], 'salesSumm')), 0, ',', ' ') . ")"
		. "</b>\n";

$text .= "\n"
		. "<b>Месяц:</b>\n"
		. "";

foreach ($report['month'] as $source) {
	$text .= $source['name'] . " - "
			. ($source['applied'] ?? 0) . "/"
			. ($source['visits'] ?? 0) . "("
			. ($source['sales'] ?? 0) . "/"
			. number_format($source['salesSumm'] ?? 0, 0, ',', ' ') . ")" . "\n";
}
$text .= "<b>Итого: "
		. array_sum(array_column($report['month'], 'applied')) . "/"
		. array_sum(array_column($report['month'], 'visits')) . " ("
		. array_sum(array_column($report['month'], 'sales')) . "/"
		. number_format(array_sum(array_column($report['month'], 'salesSumm')), 0, ',', ' ') . ")"
		. "</b>\n";
$text .= "\n<i>Клиенты записанные отделом маркетинга.\n"
		. "*Назначено/Пришло(человек купило/сумма продаж)</i>";

//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => $text]);
if ($_GET['root'] ?? false) {
	telegramSendByRights([192], $text);
}

printr($text, 1);
