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

$monthStart = date("Y-m-01");

$f_salesToday = query2array(mysqlQuery("SELECT *, ("
					 . "ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales` AND `f_paymentsType` IN (1,2)),0)"
					 . "+"
					 . "ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0)"
					 . ") as `payments`, "
					 . "(SELECT group_concat(`usersLastName` SEPARATOR ' / ') FROM `f_salesRoles` LEFT JOIN `users` ON (`idusers` = `f_salesRolesUser`) WHERE `f_salesRolesSale` = `idf_sales` AND `f_salesRolesRole` IN (1,2,3)) AS `participants`"
					 . " FROM `f_sales` WHERE `f_salesDate` = CURDATE()"));
$f_salesMonth = query2array(mysqlQuery("SELECT *, ("
					 . "ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales` AND DATE(`f_paymentsDate`)=`f_salesDate` AND `f_paymentsType` IN (1,2)),0)"
					 . "+"
					 . "ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0)"
					 . ") as `paymentsDayNDay` "
//				. "(SELECT group_concat(`usersLastName` SEPARATOR ' / ') FROM `f_salesRoles` LEFT JOIN `users` ON (`idusers` = `f_salesRolesUser`) WHERE `f_salesRolesSale` = `idf_sales` AND `f_salesRolesRole` IN (1,2,3)) AS `participants`"
					 . " FROM `f_sales` WHERE `f_salesDate`>='$monthStart' AND `f_salesDate` <= CURDATE()"));
//printr($f_salesToday, 1);



$sales_I = array_filter($f_salesToday, function ($sale) {
  return $sale['f_salesType'] == 1 && $sale['payments'] != 0;
});
$sales_II = array_filter($f_salesToday, function ($sale) {
  return $sale['f_salesType'] == 2 && $sale['payments'] != 0;
});

$text = '<b>ПРОДАЖИ ' . ['warehouse' => 'МВ', 'vita' => 'ЧК'][DBNAME] . '</b>' . "\n" . "\n"
		  . '<b>1. Первичка (' . count($sales_I) . '):</b>' . "\n";
foreach ($sales_I as $sale_I) {
  $text .= $sale_I['participants'] . ' - ' . $sale_I['payments'] . "\n";
}


$text .= "\n" . '<b>2. Вторичка (' . count($sales_II) . '):</b>' . "\n";
foreach ($sales_II as $sale_II) {
  $text .= $sale_II['participants'] . ' - ' . $sale_II['payments'] . "\n";
}

$paymentsDay = (mfa(mysqlQuery("SELECT SUM(`f_paymentsAmount`) as `summ` FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE"
								. " DATE(`f_paymentsDate`)=CURDATE() AND `f_paymentsType` IN (1,2)"
								. "AND DATE(`f_paymentsDate`)<>`f_salesDate`"))['summ'] ?? 0);

$paymentsDay_I = (mfa(mysqlQuery("SELECT 
    SUM(`summ`) AS `summ`
FROM
    (SELECT 
        SUM(`f_paymentsAmount`) AS `summ`
    FROM
        `f_payments`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`)
    WHERE
        DATE(`f_paymentsDate`) = CURDATE()
            AND `f_paymentsType` IN (1 , 2)
            AND `f_salesType` = 1
            AND DATE(`f_paymentsDate`) <> `f_salesDate` UNION ALL SELECT 
        SUM(`f_creditsSumm`) AS `summ`
    FROM
        `f_credits`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)
    WHERE
        DATE(`f_creditsAdded`) = CURDATE()
            AND `f_salesType` = 1
            AND DATE(`f_creditsAdded`) <> `f_salesDate`) AS `payments`"))['summ'] ?? 0);

$paymentsDay_II = (mfa(mysqlQuery("SELECT 
    SUM(`summ`) AS `summ`
FROM
    (SELECT 
        SUM(`f_paymentsAmount`) AS `summ`
    FROM
        `f_payments`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`)
    WHERE
        DATE(`f_paymentsDate`) = CURDATE()
            AND `f_paymentsType` IN (1 , 2)
            AND `f_salesType` = 2
            AND DATE(`f_paymentsDate`) <> `f_salesDate` UNION ALL SELECT 
        SUM(`f_creditsSumm`) AS `summ`
    FROM
        `f_credits`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)
    WHERE
        DATE(`f_creditsAdded`) = CURDATE()
            AND `f_salesType` = 2
            AND DATE(`f_creditsAdded`) <> `f_salesDate`) AS `payments`"))['summ'] ?? 0);

$paymentsMonth = (mfa(mysqlQuery("SELECT SUM(`f_paymentsAmount`) as `summ` FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE"
								. " DATE(`f_paymentsDate`)>='$monthStart'"
								. " AND DATE(`f_paymentsDate`)<=CURDATE()"
								. " AND `f_paymentsType` IN (1,2)"
								. " AND DATE(`f_paymentsDate`)<>`f_salesDate`"))['summ'] ?? 0);

$paymentsMonth_I = (mfa(mysqlQuery("SELECT SUM(`f_paymentsAmount`) as `summ` FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE"
								. " DATE(`f_paymentsDate`)>='$monthStart'"
								. " AND DATE(`f_paymentsDate`)<=CURDATE()"
								. " AND `f_paymentsType` IN (1,2) "
								. " AND `f_salesType` = 1 "
								. " AND DATE(`f_paymentsDate`)<>`f_salesDate`"))['summ'] ?? 0);

$paymentsMonth_I = (mfa(mysqlQuery("SELECT 
    SUM(`summ`) AS `summ`
FROM
    (SELECT 
        SUM(`f_paymentsAmount`) AS `summ`
    FROM
        `f_payments`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`)
    WHERE
         DATE(`f_paymentsDate`)>='$monthStart'
			  AND DATE(`f_paymentsDate`)<=CURDATE()
            AND `f_paymentsType` IN (1 , 2)
            AND `f_salesType` = 1
            AND DATE(`f_paymentsDate`) <> `f_salesDate` UNION ALL SELECT 
        SUM(`f_creditsSumm`) AS `summ`
    FROM
        `f_credits`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)
    WHERE
		           DATE(`f_creditsAdded`)>='$monthStart'
			  AND DATE(`f_creditsAdded`)<=CURDATE()
            AND `f_salesType` = 1
            AND DATE(`f_creditsAdded`) <> `f_salesDate`) AS `payments`"))['summ'] ?? 0);

$paymentsMonth_II = (mfa(mysqlQuery("SELECT 
    SUM(`summ`) AS `summ`
FROM
    (SELECT 
        SUM(`f_paymentsAmount`) AS `summ`
    FROM
        `f_payments`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`)
    WHERE
         DATE(`f_paymentsDate`)>='$monthStart'
			  AND DATE(`f_paymentsDate`)<=CURDATE()
            AND `f_paymentsType` IN (1 , 2)
            AND `f_salesType` = 2
            AND DATE(`f_paymentsDate`) <> `f_salesDate` UNION ALL SELECT 
        SUM(`f_creditsSumm`) AS `summ`
    FROM
        `f_credits`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)
    WHERE
		           DATE(`f_creditsAdded`)>='$monthStart'
			  AND DATE(`f_creditsAdded`)<=CURDATE()
            AND `f_salesType` = 2
            AND DATE(`f_creditsAdded`) <> `f_salesDate`) AS `payments`"))['summ'] ?? 0);
//
$paymentsMonthAll = query2array(mysqlQuery("SELECT `amount`, if(isnull(`clientsOldSince`) OR `clientsOldSince`=`f_salesDate`,1,0) as `is_I` FROM (SELECT `f_paymentsSalesID` as `sale`,`f_paymentsAmount` as `amount`"
					 . " FROM `f_payments` WHERE"
					 . " DATE(`f_paymentsDate`)>='$monthStart'"
					 . " AND DATE(`f_paymentsDate`)<=CURDATE()"
					 . " AND `f_paymentsType` IN (1,2) "
					 . "UNION ALL"
					 . " SELECT  `idf_sales` as `sale`, `f_creditsSumm` as `amount` FROM `f_credits`"
					 . " LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
					 . " WHERE `f_salesDate`>='$monthStart'"
					 . " AND `f_salesDate`<=CURDATE()"
					 . ""
					 . ") AS `payments`"
					 . " LEFT JOIN `f_sales` ON (`idf_sales` = `sale`)"
					 . " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
					 . " "));
//printr($paymentsMonthAll, 1);

$monthSumm_I = array_sum(array_column(array_filter($paymentsMonthAll, function ($payment) {
						return $payment['is_I'] == 1;
					 }), 'amount'));
$monthSumm_II = array_sum(array_column(array_filter($paymentsMonthAll, function ($payment) {
						return $payment['is_I'] == 0;
					 }), 'amount'));

//printr([$monthSumm_I, $monthSumm_II], 1);

$text .= "\n" . '<b>3. За день - ' . ($paymentsDay + array_sum(array_column($f_salesToday, 'payments'))) . ' (' . count($f_salesToday) . '):</b>' . "\n";
$text .= 'Первичка - ' . array_sum(array_column(array_filter($f_salesToday, function ($sale) {
								  return $sale['f_salesType'] == 1;
								}), 'payments')) . "\n";
$text .= 'Вторичка - ' . array_sum(array_column(array_filter($f_salesToday, function ($sale) {
								  return $sale['f_salesType'] == 2;
								}), 'payments')) . "\n";
$text .= 'Разовые - ' . array_sum(array_column(array_filter($f_salesToday, function ($sale) {
								  return $sale['f_salesType'] == 3;
								}), 'payments')) . "\n";

$text .= 'Доплаты первичка - ' . $paymentsDay_I . "\n";
$text .= 'Доплаты вторичка - ' . $paymentsDay_II . "\n";

$text .= "\n" . '<b>4. За месяц - ' . ($paymentsMonth + array_sum(array_column($f_salesMonth, 'paymentsDayNDay'))) . ' (' . count($f_salesMonth) . '):</b>' . "\n";
$text .= 'Первичка* - ' . array_sum(array_column(array_filter($f_salesMonth, function ($sale) {
								  return $sale['f_salesType'] == 1;
								}), 'paymentsDayNDay')) . "\n";
$text .= 'Вторичка* - ' . array_sum(array_column(array_filter($f_salesMonth, function ($sale) {
								  return $sale['f_salesType'] == 2;
								}), 'paymentsDayNDay')) . "\n";
$text .= 'Разовые - ' . array_sum(array_column(array_filter($f_salesMonth, function ($sale) {
								  return $sale['f_salesType'] == 3;
								}), 'paymentsDayNDay')) . "\n";

$text .= 'Доплаты первичка - ' . $paymentsMonth_I . "\n";
$text .= 'Доплаты вторичка - ' . $paymentsMonth_II . "\n";

$cancelationDay = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesCancellationDate` = CURDATE()"));
$cancelationDay_I = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesCancellationDate` = CURDATE() AND `f_salesType` = 1"));
$cancelationDay_II = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesCancellationDate` = CURDATE() AND `f_salesType` = 2"));

$cancelationMonth = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesCancellationDate`>= '$monthStart' AND `f_salesCancellationDate` <= CURDATE()"));
$cancelationMonth_I = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesCancellationDate`>= '$monthStart' AND `f_salesCancellationDate` <= CURDATE() AND `f_salesType` = 1"));
$cancelationMonth_II = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesCancellationDate`>= '$monthStart' AND `f_salesCancellationDate` <= CURDATE() AND `f_salesType` = 2"));

$text .= "\n" . '<b>5. Возвраты</b>' . "\n";
$text .= 'День первичка ' . count($cancelationDay_I) . '/' . array_sum(array_column($cancelationDay_I, 'f_salesCancellationSumm')) . '' . "\n";
$text .= 'День вторичка ' . count($cancelationDay_II) . '/' . round(array_sum(array_column($cancelationDay_II, 'f_salesCancellationSumm'))) . '' . "\n";

$text .= 'Месяц первичка ' . count($cancelationMonth_I) . '/' . array_sum(array_column($cancelationMonth_I, 'f_salesCancellationSumm')) . '' . "\n";
$text .= 'Месяц вторичка ' . count($cancelationMonth_II) . '/' . round(array_sum(array_column($cancelationMonth_II, 'f_salesCancellationSumm'))) . '' . "\n";

$f_plan = mfa(mysqlQuery("SELECT * FROM `f_plan` WHERE `f_planYear`='" . date("Y") . "' AND `f_planMonth`='" . date("n") . "'"));

//$monthRemain_I = (($f_plan['f_planSumm'] ?? 0) ? ($f_plan['f_planSumm'] - $paymentsMonth + array_sum(array_column($cancelationMonth, 'f_salesCancellationSumm')) - array_sum(array_column($f_salesMonth, 'paymentsDayNDay'))) : null);

$text .= "\n" . '<b>6. План месяца</b>' . "\n" . 'по первичке ' . ($f_plan['f_planSumm_I'] ?? 'Не установлен') . '' . "\n";
$text .= "" . 'по вторичке ' . ($f_plan['f_planSumm_II'] ?? 'Не установлен') . '' . "\n";

$text .= "\n<b>7. Всего платежей</b>\n";
$text .= "по первичке**: " . $monthSumm_I . "\n";
$text .= "по вторичке**: " . $monthSumm_II . "\n";
if ($f_plan ?? false) {
  $monthRemain_I = max(0, $f_plan['f_planSumm_I'] - $monthSumm_I);
  $monthRemain_II = max(0, $f_plan['f_planSumm_II'] - $monthSumm_II);
  $text .= "\n<b>8. Остаток по плану к выполнению</b>\n";
  $text .= ($monthRemain_I > 0 ? ('по первичке: ' . ($f_plan['f_planSumm_I'] ? $monthRemain_I : 'не установлен план')) : ('Перевыполнили план на ' . ($f_plan['f_planSumm_I'] ? abs($monthRemain_I) : 'не установлен план'))) . "\n";

  $text .= ($monthRemain_II > 0 ? ('по вторичке: ' . ($f_plan['f_planSumm_II'] ? $monthRemain_II : 'не установлен план')) : ('Перевыполнили план на ' . ($f_plan['f_planSumm_II'] ? abs($monthRemain_II) : 'не установлен план'))) . "\n\n";

  $text .= '<b>9. Ежедневный </b>' . "\n";
  $text .= 'по первичке: ' . ($f_plan['f_planSumm_I'] ? ((date("t") - date("j")) > 0 ? round($monthRemain_I / (date("t") - date("j"))) : ' Это был последний день месяца') : 'Не установлен план') . "\n";
  $text .= 'по вторичке: ' . ($f_plan['f_planSumm_II'] ? ((date("t") - date("j")) > 0 ? round($monthRemain_II / (date("t") - date("j"))) : ' Это был последний день месяца') : 'Не установлен план') . "\n";
} else {
  $text .= "\n<b>План на месяц не установлен</b>";
}
If (date("t") == date("j")) {
  $text .= "\n<b>Необходимо установить план на следующий месяц</b>\n";
}

$text .= "\n* - Данные по типу абонемента\n";
$text .= "** - Данные по клиенту\n";

//printr($f_plan);

if ($_GET['root'] ?? false) {
  telegramSendByRights([192], $text);
}
printr($text, 1);
