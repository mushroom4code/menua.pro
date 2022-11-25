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

//Прибавление:
$weekStart = date("Y-m-d", strtotime("last monday")); //date("N") == 1 ? date("Y-m-d") : Если сегодня понедельник, берем сегодняшнюю дату, если нет, то дату последнего (прошедшего) понедельника
$monthStart = date("Y-m-01");
//printr($monthStart);

$addWeek = mfa(mysqlQuery("SELECT COUNT(1) as `qty` FROM `clients` WHERE `clientsOldSince`>='$weekStart'"))['qty'];
$addMonth = mfa(mysqlQuery("SELECT COUNT(1) as `qty` FROM `clients` WHERE `clientsOldSince`>='$monthStart'"))['qty'];

$appliedWeek = mfa(mysqlQuery("SELECT COUNT(1) as `clientsCnt` FROM ("
						. "SELECT `servicesAppliedClient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=9 AND `servicesAppliedDate`>='$weekStart' and `servicesAppliedDate`<=CURDATE() GROUP BY `servicesAppliedClient`,`servicesAppliedDate`"
						. ") AS `cnt`"))['clientsCnt'];
$appliedMonth = mfa(mysqlQuery("SELECT COUNT(1) as `clientsCnt` FROM ("
						. "SELECT `servicesAppliedClient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=9 AND `servicesAppliedDate`>='$monthStart' and `servicesAppliedDate`<=CURDATE() GROUP BY `servicesAppliedClient`,`servicesAppliedDate`"
						. ") AS `cnt`"))['clientsCnt'];

$visitsWeek = mfa(mysqlQuery("SELECT COUNT(1) as `visitsCnt` FROM `clientsVisits`"
						. " WHERE `clientsVisitsDate`>='$weekStart' AND `clientsVisitsClient` IN ("
						. "SELECT `servicesAppliedClient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=9 AND `servicesAppliedDate`>='$weekStart' and `servicesAppliedDate`<=CURDATE() GROUP BY `servicesAppliedClient`"
						. ")"
//						. "  GROUP BY `clientsVisitsClient`"
						. ""
						. ""))['visitsCnt']; //готово

$visitsMonth = mfa(mysqlQuery("SELECT COUNT(1) as `visitsCnt` FROM `clientsVisits`"
						. " WHERE `clientsVisitsDate`>='$monthStart' AND `clientsVisitsClient` IN ("
						. "SELECT `servicesAppliedClient` FROM `servicesApplied` LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`) WHERE `usersGroup`=9 AND `servicesAppliedDate`>='$monthStart' and `servicesAppliedDate`<=CURDATE() GROUP BY `servicesAppliedClient`"
						. ")"
//						. "  GROUP BY `clientsVisitsClient`"
//						. ") as `preselect`"
						. ""))['visitsCnt']; //готово

$remainsAddWeek = query2array(mysqlQuery("SELECT
    sum(`f_salesContentQty`) as `cnt`, `servicesTypesName`,ifnull(`servicesType`,0) as `servicesType`
FROM
    `f_subscriptions`
        LEFT JOIN
    `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)
    left join `services` on (`idservices` = `f_salesContentService`)
    LEFT JOIN `servicesTypes` on (`idservicesTypes` = `servicesType`)
    where (
    (isnull(`f_subscriptionsDate`) AND `f_salesDate`>='$weekStart' AND `f_salesDate`<=CURDATE())
    or
    (NOT isnull(`f_subscriptionsDate`) AND `f_subscriptionsDate`>='$weekStart' AND `f_subscriptionsDate`<=CURDATE())
    )
    group by `servicesType` ORDER BY `cnt` desc;"), 'servicesType');

$remainsAddMonth = query2array(mysqlQuery("SELECT
    sum(`f_salesContentQty`) as `cnt`, `servicesTypesName`,ifnull(`servicesType`,0) as `servicesType`
FROM
    `f_subscriptions`
        LEFT JOIN
    `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)
    left join `services` on (`idservices` = `f_salesContentService`)
    LEFT JOIN `servicesTypes` on (`idservicesTypes` = `servicesType`)
    where (
    (isnull(`f_subscriptionsDate`) AND `f_salesDate`>='$monthStart' AND `f_salesDate`<=CURDATE())
    or
    (NOT isnull(`f_subscriptionsDate`) AND `f_subscriptionsDate`>='$monthStart' AND `f_subscriptionsDate`<=CURDATE())
    )
    group by `servicesType` ORDER BY `cnt` desc;"), 'servicesType');

$remainsDoneWeek = query2array(mysqlQuery("SELECT
    SUM(servicesAppliedQty) as `qty`, servicesType, `servicesTypesName`
FROM
    `servicesApplied`
        LEFT JOIN
    `services` ON (idservices = servicesAppliedService)
    left join servicesTypes on (idservicesTypes =servicesType )
WHERE
    `servicesAppliedDate` >= '$weekStart'
        AND `servicesAppliedDate` <= CURDATE()
        AND NOT ISNULL(servicesAppliedFineshed)
		AND NOT ISNULL(servicesAppliedContract)
        AND ISNULL(servicesAppliedDeleted)
GROUP BY servicesType;"), 'servicesType');

$remainsDoneMonth = query2array(mysqlQuery("SELECT
    SUM(servicesAppliedQty) as `qty`, servicesType, `servicesTypesName`
FROM
    `servicesApplied`
        LEFT JOIN
    `services` ON (idservices = servicesAppliedService)
    left join servicesTypes on (idservicesTypes =servicesType )
WHERE
    `servicesAppliedDate` >= '$monthStart'
        AND `servicesAppliedDate` <= CURDATE()
        AND NOT ISNULL(servicesAppliedFineshed)
		AND NOT ISNULL(servicesAppliedContract)
        AND ISNULL(servicesAppliedDeleted)
GROUP BY servicesType;"), 'servicesType');

$remainsWeek = [];
foreach ($remainsAddWeek as $servicesType => $remainsAdd) {
	$remainsWeek[($servicesType ?? 0)]['name'] = ($remainsAdd['servicesTypesName'] ?? 'Не указан раздел');
	$remainsWeek[($servicesType ?? 0)]['add'] = $remainsAdd['cnt'];
}

$remainsMonth = [];
foreach ($remainsAddMonth as $servicesType => $remainsAdd) {
	$remainsMonth[($servicesType ?? 0)]['name'] = ($remainsAdd['servicesTypesName'] ?? 'Не указан раздел');
	$remainsMonth[($servicesType ?? 0)]['add'] = $remainsAdd['cnt'];
}



foreach ($remainsDoneWeek as $remainDoneWeek) {
	$remainsWeek[($remainDoneWeek['servicesType'] ?? 0)]['name'] = ($remainDoneWeek['servicesTypesName'] ?? 'Не указан раздел');
	$remainsWeek[($remainDoneWeek['servicesType'] ?? 0)]['done'] = $remainDoneWeek['qty'];
}

foreach ($remainsDoneMonth as $remainDoneMonth) {
	$remainsMonth[($remainDoneMonth['servicesType'] ?? 0)]['name'] = ($remainDoneMonth['servicesTypesName'] ?? 'Не указан раздел');
	$remainsMonth[($remainDoneMonth['servicesType'] ?? 0)]['done'] = $remainDoneMonth['qty'];
}

$remainsTotal = query2array(mysqlQuery("SELECT SUM(qty) as `qty`, servicesType, servicesTypesName FROM (SELECT
    SUM(-servicesAppliedQty) AS `qty`, servicesType
FROM
    servicesApplied
        LEFT JOIN
    services ON (idservices = servicesAppliedService)
WHERE
    NOT ISNULL(servicesAppliedFineshed)
    AND NOT ISNULL(servicesAppliedContract)
        AND ISNULL(servicesAppliedDeleted)
GROUP BY servicesType
UNION ALL SELECT
    SUM(f_salesContentQty) AS `qty`, servicesType
FROM
    f_subscriptions
        LEFT JOIN
    services ON (idservices = f_salesContentService)
GROUP BY servicesType) as preselect LEFT JOIN servicesTypes ON (idservicesTypes=servicesType) GROUP BY `servicesType` order by `qty` DESC;"));

//printr($remainsDoneWeek);
//printr($remainsMonth);
//printr($appliedMonth);
$text = "ℹ️ СЕРВИС\n"
		. "<b>1. Прибавления:</b>\n"
		. "Неделя: $addWeek\n"
		. "Месяц: $addMonth\n\n"
		. "<b>2. Назначено/Пришло:</b>\n"
		. "Неделя: $appliedWeek/$visitsWeek\n"
		. "Месяц: $appliedMonth/$visitsMonth\n\n"
		. "<b>3. Обязательства: Добавлено/Оказано</b>\n"
//		. "Неделя: $remainsAddWeek\n"
//		. "Месяц: $remainsAddMonth\n\n"
		. "<b>За неделю:</b>\n";

foreach ($remainsWeek as $remainWeek) {
	$text .= $remainWeek['name'] . (($remainWeek['add'] ?? 0) > 0 ? " +" : " ") . ($remainWeek['add'] ?? 0) . " / " . -($remainWeek['done'] ?? 0) . "\n";
}
$text .= "<b>ИТОГО +" . array_sum(array_column($remainsWeek, 'add')) . " / " . -array_sum(array_column($remainsWeek, 'done')) . "</b>\n";

$text .= "\n<b>За месяц:</b>\n";
foreach ($remainsMonth as $remainMonth) {
	$text .= $remainMonth['name'] . (($remainMonth['add'] ?? 0) > 0 ? " +" : " ") . ($remainMonth['add'] ?? 0) . " / " . -($remainMonth['done'] ?? 0) . "\n";
}
$text .= "<b>ИТОГО +" . array_sum(array_column($remainsMonth, 'add')) . " / " . -array_sum(array_column($remainsMonth, 'done')) . "</b>\n";

$text .= "\n<b>4. Итого обязательств на балансе:</b>\n";
foreach ($remainsTotal as $remainTotal) {
	$text .= ($remainTotal['servicesTypesName'] ?? 'Не указан раздел') . " " . $remainTotal['qty'] . "\n";
}
if ($_GET['root'] ?? false) {
	telegramSendByRights([192], $text);
}
printr($text, 1);
