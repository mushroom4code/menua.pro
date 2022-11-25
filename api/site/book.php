<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
include 'functions.php';
header("Content-type: application/json; charset=utf8");
sendTelegram('sendMessage', ['chat_id' => '-527765235', 'text' => json_encode(($_JSON ?? "NO CONTENT"), 288 + 128)]);

if (!($_JSON ?? false)) {
	ob_start();
	header("HTTP/1.1 204 NO CONTENT");
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
	header("Pragma: no-cache"); // HTTP 1.0.
	header("Expires: 0"); // Proxies.
	ob_end_flush(); //now the headers are sent
	die();
}

if (getBearerToken() !== 'AIuZLsEgEShFbCNuwzko') {
	header("HTTP/1.1 401 Unauthorized");
	die();
}
//$_USER['id']=707;

$phoneNumber = preg_replace("/[^0-9]/", "", $_JSON['client']['mobile_phone'] ?? '');
if (strlen($phoneNumber) == 11) {
	$phoneNumber[0] = '8';
} elseif (strlen($phoneNumber) == 10) {
	$phoneNumber = '8' . $phoneNumber;
}

if ($_JSON['appointment_source'] == 'Prodoctorov') {
	$idclientsSources = 19;
	$idclientsSourcesName = "ПроДокторов";
} elseif ($_JSON['appointment_source'] == 'infiniti-clinic.ru') {
	$idclientsSources = 20;
	$idclientsSourcesName = "Сайт"; 
} else {
	$idclientsSources = null;
}
$clients = query2array(mysqlQuery("SELECT *"
				. " FROM `clients`"
				. " LEFT JOIN `clientsPhones` ON (`clientsPhonesClient` = `idclients`)"
				. " WHERE `clientsPhonesPhone`='" . mres($phoneNumber) . "'"
				. " AND isnull(`clientsPhonesDeleted`)"));

if (count($clients) > 1) {
//try to filter by name
	$clients = array_values(array_filter($clients, function ($client)use ($_JSON) {
				return $client['clientsLName'] == ($_JSON['client']['last_name'] ?? '') && $client['clientsFName'] == trim($_JSON['client']['first_name'] ?? '');
			}));
	if (count($clients) > 1) {
		telegramSendByRights([159], "🚨🚨🚨При записи через " . $idclientsSourcesName . " найдено больше 1го клиента с номером телефона\n" . mres($phoneNumber) . "\nКлиент сайта: " . ($_JSON['client']['last_name'] ?? '') . ' ' . trim($_JSON['client']['first_name'] ?? '') . "\nСрочно принять меры!");

		print json_encode(["status_code" => 416, "detail" => "Slot doesn't exist"]);
		die();
	} elseif (count($clients) == 1) {
		$client = $clients[0];
	} else {
		telegramSendByRights([159], "🚨🚨🚨При записи через " . $idclientsSourcesName . " найдено больше 1го клиента с номером телефона\n" . mres($phoneNumber) . ", но ни одного с данными которые ввёл клиент (" . trim($_JSON['client']['last_name'] ?? '') . ' ' . trim($_JSON['client']['first_name'] ?? '') . ")\nСрочно принять меры!");

		print json_encode(["status_code" => 416, "detail" => "Slot doesn't exist"]);
		die();
	}
} elseif (count($clients) == 1) {

	if ($clients[0]['clientsLName'] == ($_JSON['client']['last_name'] ?? '') && $clients[0]['clientsFName'] == trim($_JSON['client']['first_name'] ?? '')) {
		$client = $clients[0];
	} else {
		telegramSendByRights([159], "🚨🚨🚨При записи через " . $idclientsSourcesName . " возникла ошибка! Записывается " . trim($_JSON['client']['last_name'] ?? '') . ' ' . trim($_JSON['client']['first_name'] ?? '') . ' ' . trim($_JSON['client']['second_name'] ?? '') . " c телефонным номером $phoneNumber, а в базе данных на с этим же номером записан клиент " . $clients[0]['clientsLName'] . ' ' . $clients[0]['clientsFName'] . "\nhttps://" . SUBDOMEN . "menua.pro/pages/offlinecall/schedule.php?client=" . $clients[0]['idclients']);
		print json_encode(["status_code" => 416, "detail" => "Slot doesn't exist"]);
		die();
	}
} else {
//ADD NEW CLIENT



	mysqlQuery("INSERT INTO `clients` SET "
			. " `clientsLName` = '" . mres(trim($_JSON['client']['last_name'] ?? '')) . "', "
			. " `clientsFName` = '" . mres(trim($_JSON['client']['first_name'] ?? '')) . "', "
			. " `clientsMName` = '" . mres(trim($_JSON['client']['second_name'] ?? '')) . "', "
			. (validateDate(trim($_JSON['client']['birthday'] ?? '')) ? (" `clientsBDay` = '" . mres(trim($_JSON['client']['birthday'] ?? '')) . "',") : "")
//			. " `clientsAddedBy`='707', "
			. " `clientsSource`=" . sqlVON($idclientsSources) . "");
	$idclient = mysqli_insert_id($link);
	$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . $idclient . "'"));

	mysqlQuery("INSERT INTO `clientsPhones` SET `clientsPhonesClient` = '" . $idclient . "', `clientsPhonesPhone` = '" . mres($phoneNumber) . "'");

	telegramSendByRights([158], "✅ Добавлен новый клиент через " . $idclientsSourcesName . "\n" . trim($_JSON['client']['last_name'] ?? '') . ' ' . trim($_JSON['client']['first_name'] ?? '') . "\nhttps://" . SUBDOMEN . "menua.pro/pages/offlinecall/schedule.php?client=" . $idclient);
}

$appointment = $_JSON['appointment'];
$appointment['dt_start'] = $appointment['dt_start'] . ':00';
$appointment['dt_end'] = $appointment['dt_end'] . ':00';
$appointment['personnel'] = $_JSON['doctor']['id'];
//Проверить расписание врача в этот интервал времени
$usersSchedule = mfa(mysqlQuery("SELECT *"
				. " FROM `usersSchedule`"
				. " WHERE `usersScheduleUser` = '" . mres($appointment['personnel']) . "'"
				. " AND `usersScheduleDate` = '" . date("Y-m-d", strtotime($appointment['dt_start'])) . "'"
				. " AND `usersScheduleFrom`<='" . mres($appointment['dt_start']) . "'"
				. " AND `usersScheduleTo`>='" . mres($appointment['dt_end']) . "'"
				. ""));

if (!$usersSchedule) {
	print json_encode(["status_code" => 416, "detail" => "Slot doesn't exist"]);
	die();
}
$_JSON['$usersSchedule'] = $usersSchedule;

//Проверить нет ли у варача записей на это время
$servicesApplied = query2array(mysqlQuery("SELECT *"
				. " FROM `servicesApplied`"
				. " WHERE `servicesAppliedDate` = '" . date("Y-m-d", strtotime($appointment['dt_start'])) . "'"
				. " AND `servicesAppliedPersonal` =  '" . mres($appointment['personnel']) . "'"
				. " AND isnull(`servicesAppliedDeleted`)"
				. ""));

$servicesAppliedFiltered = array_filter($servicesApplied, function ($serviceApplied) use ($appointment) {

	if ($serviceApplied['servicesAppliedTimeBegin'] <= $appointment['dt_start'] && $appointment['dt_start'] < $serviceApplied['servicesAppliedTimeEnd']) {
		//начало записи попало в интервал уже установленной процедуры
		return true;
	}
	if ($serviceApplied['servicesAppliedTimeBegin'] < $appointment['dt_end'] && $appointment['dt_end'] <= $serviceApplied['servicesAppliedTimeEnd']) {
		//конец записи попало в интервал уже установленной процедуры
		return true;
	}
	if (
			$appointment['dt_start'] <= $serviceApplied['servicesAppliedTimeBegin'] &&
			$appointment['dt_end'] >= $serviceApplied['servicesAppliedTimeEnd']) {
		//запись полностью перекрывает интервал уже установленной процедуры
		return true;
	}
	return false;
});

if ($servicesAppliedFiltered) {
	print json_encode(["status_code" => 423, "detail" => "Slot is busy"]);
	die();
}
//Осуществляем запись:

mysqlQuery("INSERT INTO `servicesApplied` SET "
		. " `servicesAppliedService` = '361', "
		. " `servicesAppliedQty` = '1', "
		. " `servicesAppliedClient` = '" . $client['idclients'] . "', "
		. " `servicesAppliedBy` = '707', "
		. " `servicesAppliedPrice` = '" . mres($appointment['price']) . "', "
		. " `servicesAppliedPersonal`='" . mres($appointment['personnel']) . "',"
		. " `servicesAppliedDate` = '" . date("Y-m-d", strtotime($appointment['dt_start'])) . "',"
		. " `servicesAppliedTimeBegin`='" . $appointment['dt_start'] . "', "
		. " `servicesAppliedTimeEnd`='" . $appointment['dt_end'] . "'"
		. "");
$idservicesApplied = mysqli_insert_id($link);
if (!$idservicesApplied) {
	die(json_encode(["status_code" => 425, "detail" => "can't set service applied"]));
}

if ($appointment['comment'] ?? false) {
	mysqlQuery("INSERT INTO `servicesAppliedComments` SET `servicesAppliedCommentsSA` = '" . $idservicesApplied . "', `servicesAppliedCommentText`='" . mres($appointment['comment']) . "'");
	$comment = "\n❗️Комментарий: " . $appointment['comment'];
}


telegramSendByRights([158], "✅ Новая запись через " . $idclientsSourcesName . "\n" . trim($_JSON['client']['last_name'] ?? '') . ' ' . trim($_JSON['client']['first_name'] ?? '') . "\nhttps://" . SUBDOMEN . "menua.pro/pages/offlinecall/schedule.php?client=" . $client['idclients'] . '&date=' . date("Y-m-d", strtotime($appointment['dt_start'])) . ($comment ?? ''));

print json_encode(["status_code" => 204, "claim_id" => $idservicesApplied]);
die();

//print json_encode($_JSON);

