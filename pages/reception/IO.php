<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
//mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (isset($_JSON['action']) && $_JSON['action'] === 'getDeleteReasons') {
	print json_encode(query2array(mysqlQuery("SELECT iddaleteReasons as id, daleteReasonsName as name FROM `daleteReasons` WHERE isnull(`daleteReasonsDeleted`)")), 288);
}





if (isset($_JSON['action']) && $_JSON['action'] === 'moveTheDate' && count($_JSON['servicesApplied'] ?? [])) {
	$servicesApplied = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `idservicesApplied` IN (" . implode(',', $_JSON['servicesApplied']) . ")"));
	
//	{"action":"moveTheDate","moveFrom":"2020-05-22","moveTo":"2020-05-22","servicesApplied":[17,23,48,49]}
	if (mysqlQuery("UPDATE `servicesApplied` SET "
					. "`servicesAppliedDate` = '" . $_JSON['moveTo'] . "', "
					. "`servicesAppliedTimeBegin` = null, "
					. "`servicesAppliedTimeEnd` = null "
					. " WHERE `idservicesApplied` IN (" . implode(',', $_JSON['servicesApplied']) . ")")) {
		print json_encode(['success' => true], 288);
	} else {
		print json_encode(['success' => false, 'error' => urlencode(mysqli_error($link))], 288);
	}
	die();
//
}


if (isset($_JSON['action']) && $_JSON['action'] === 'saveTime') {
//	action "saveTime"
//	column "to"
//	id 20
//	value "11:11"
	$column = '';
	if ($_JSON['column'] == 'from') {
		$column = 'servicesAppliedTimeBegin';
	}
	if ($_JSON['column'] == 'to') {
		$column = 'servicesAppliedTimeEnd';
	}
	$time = 'null';
	if ($_JSON['value']) {
		$time = "'" . date("Y-m-d H:i:s", strtotime(date("Y-m-d") . ' ' . FSS($_JSON['value']) . ':00')) . "'";
	}
	if ($column) {
//		print ;
		mysqlQuery("UPDATE `servicesApplied` SET `$column` = " . $time . " WHERE `idservicesApplied` = '" . FSI($_JSON['id']) . "' ");
	}
}



if (isset($_JSON['action']) && $_JSON['action'] === 'setPersonal') {

//action	"setPersonal"
//id	14
//state	"143"
	mysqlQuery("UPDATE `servicesApplied` SET `servicesAppliedPersonal` = " . ($_JSON['personal'] ? $_JSON['personal'] : 'null') . " WHERE `idservicesApplied` = '" . FSI($_JSON['id']) . "' ");
}

if (isset($_JSON['action']) && $_JSON['action'] === 'setGift') {
//	action "setGift"
//	id 8
//	state true
//	mysqlQuery("UPDATE `servicesApplied` SET `servicesAppliedIsFree` = " . ($_JSON['state'] ? '1' : 'null') . " WHERE `idservicesApplied` = '" . FSI($_JSON['id']) . "' ");
}





if (isset($_JSON['action']) && $_JSON['action'] === 'saveAppliedServices') {
	$client = FSI($_JSON['client']);
	$services = $_JSON['services'];
	$date = date("Y-m-d");
	mysqlQuery("DELETE FROM `servicesApplied` "
			. "WHERE "
			. "`servicesAppliedDate` = '" . $date . "'"
			. " AND `servicesAppliedClient` = '" . $client . "'");
//idservicesApplied, servicesAppliedService, servicesAppliedDate, servicesAppliedTimeBegin, servicesAppliedTimeEnd, servicesAppliedClient, servicesAppliedIsFree, servicesAppliedBy, servicesAppliedDeleted, servicesAppliedAt
	foreach ($services as $service) {
		$cleanedArray[] = [
			$service['id'] ?? 'null',
			$service['service']['idservices'],
			"'" . $date . "'",
			"'" . date("Y-m-d H:i:s", strtotime($date . " " . $service['time']['from'] . ':00')) . "'",
			"'" . date("Y-m-d H:i:s", strtotime($date . " " . $service['time']['to'] . ':00')) . "'",
			$client,
//			$service['isFree'] ? '1' : 'null',
			$_USER['id'],
			$_USER['id']
		];
	}


	$insertSQL = "INSERT INTO `servicesApplied`
(`idservicesApplied`,
`servicesAppliedService`,
`servicesAppliedDate`,
`servicesAppliedTimeBegin`,
`servicesAppliedTimeEnd`,
`servicesAppliedClient`,
`servicesAppliedBy`,
`servicesAppliedByReal`
) VALUES 
" . batchInsert($cleanedArray);

	mysqlQuery($insertSQL);

//VALUES
//(<{idservicesApplied: }>,
// <{servicesAppliedService: }>,
// <{servicesAppliedDate: }>,
// <{servicesAppliedTimeBegin: }>,
// <{servicesAppliedTimeEnd: }>,
// <{servicesAppliedClient: }>,
// <{servicesAppliedIsFree: }>,
// <{servicesAppliedBy: }>,
// <{servicesAppliedDeleted: }>,
// <{servicesAppliedAt: CURRENT_TIMESTAMP}>);





	printr($insertSQL);
}




if (isset($_JSON['action']) && $_JSON['action'] === 'addNewClient') {


//	[action] => addNewClient
//	[lastname] => фывфыв
//	[firstname] => цуывмы
//	[middlename] => цпмчсм
//	[acardnumber] => 121212121
//	[birthday] => 1212-12-12
//	[clientsPhone] => 1212121212
//	[isNew] => 1
//	[callerID] =>
//	[callerName] => Новый Оператор
//	[callerAdmin] => 264acardnumber	"134234"


	if (empty($_JSON['callerID']) && !empty($_JSON['callerName'])) {
		
	}






//	die();
	$SET = [];
	$GET = [];
	if (FSS(trim($_JSON['firstname'])) !== '') {
		$SET[] = "`clientsFName` = '" . FSS(trim($_JSON['firstname'])) . "'";
		$GET[] = "`clientsFName` = '" . FSS(trim($_JSON['firstname'])) . "'";
	}


	if (FSS(trim($_JSON['clientsSource'])) !== '') {
		$SET[] = "`clientsSource` = '" . FSS(trim($_JSON['clientsSource'])) . "'";
	}


	if (FSS(trim($_JSON['lastname'])) !== '') {
		$SET[] = "`clientsLName` = '" . FSS(trim($_JSON['lastname'])) . "'";
		$GET[] = "`clientsLName` = '" . FSS(trim($_JSON['lastname'])) . "'";
	}
	if (FSS(trim($_JSON['middlename'])) !== '') {
		$SET[] = "`clientsMName` = '" . FSS(trim($_JSON['middlename'])) . "'";
		$GET[] = "`clientsMName` = '" . FSS(trim($_JSON['middlename'])) . "'";
	}
	if (FSS(trim($_JSON['clientsPhone'])) !== '') {
		$GET[] = "`clientsPhonesPhone` = '" . FSS(trim($_JSON['clientsPhone'])) . "'";
	}



	if (validateDate(FSS(trim($_JSON['birthday'])))) {
		$SET[] = "`clientsBDay` = '" . FSS(trim($_JSON['birthday'])) . "'";
		$GET[] = "`clientsBDay` = '" . FSS(trim($_JSON['birthday'])) . "'";
	}



	if (count($SET)) {
		$selectSQL = "SELECT * FROM `clients` LEFT JOIN `clientsPhones` ON (`clientsPhonesClient` = `idclients`) WHERE "
				. implode(" AND ", $GET);
	}



	if ($client = mfa(mysqlQuery($selectSQL))) {
		die(json_encode(['success' => false, 'msgs' => ['Клиент с такими данными уже есть']], 288));
	}



	$SET[] = "`clientsAddedBy` = '" . $_USER['id'] . "'";

	if (isset($_JSON['isNew']) && $_JSON['isNew'] == true) {
//		$SET[] = "`clientsIsNew` = '1'";
	}
	if (isset($_JSON['callerID']) && $_JSON['callerID']) {
		$SET[] = "`clientsCallerId` = '" . FSI($_JSON['callerID']) . "'";
	}
	if (isset($_JSON['callerAdmin']) && $_JSON['callerAdmin']) {
		$SET[] = "`clientsCallerAdmin` = '" . FSI($_JSON['callerAdmin']) . "'";
	}
	if (isset($_JSON['gender']) && $_JSON['gender'] != '') {
		$SET[] = "`clientsGender` = '" . FSI($_JSON['gender']) . "'";
	}



	if (FSS(trim($_JSON['acardnumber'])) !== '') {
		$SET[] = "`clientsAKNum` = '" . FSS(trim($_JSON['acardnumber'])) . "'";
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

			print json_encode(['success' => true, 'client' => $insertid], 288);
		} else {
			print json_encode(['success' => false, 'msgs' => ['Ошибка вставки в базу данных']], 288);
		}
	}
}