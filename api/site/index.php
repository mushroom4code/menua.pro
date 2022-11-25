<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

include 'functions.php';
header("Content-type: application/json; charset=utf8");
sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => json_encode(($_JSON ?? "NO CONTENT"), 288 + 128)]);
$OUT = ['success' => true, 'currenttime' => date("Y-m-d H:i:s")];
if (getBearerToken() !== '7PgvB1xnpBF7TjiV8Jup2Cjn') {
	header("HTTP/1.1 401 Unauthorized");
	die(json_encode(['success' => false, 'error' => ['id' => '401', 'text' => 'Unauthorized'], 'currenttime' => date("Y-m-d H:i:s")]));
}

if (!($_JSON ?? false)) {
	ob_start();
	header("HTTP/1.1 204 NO CONTENT");
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
	header("Pragma: no-cache"); // HTTP 1.0.
	header("Expires: 0"); // Proxies.
	ob_end_flush(); //now the headers are sent
	die(json_encode(['success' => false, 'error' => ['id' => '204', 'text' => 'no content'], 'currenttime' => date("Y-m-d H:i:s")]));
}

$code = mfa(mysqlQuery(""
				. " SELECT *, 'warehouse' AS `database` FROM `warehouse`.`clientsFeedback` WHERE `clientsFeedbackCode` = BINARY '" . mres($_JSON['code']) . "'"
				. " UNION ALL "
				. " SELECT *, 'vita' AS `database` FROM `vita`.`clientsFeedback` WHERE `clientsFeedbackCode` = '" . mres($_JSON['code']) . "'"
				. ""));
if (!$code) {
	die(json_encode(['success' => false, 'error' => ['id' => '404', 'text' => 'code not found'], 'currenttime' => date("Y-m-d H:i:s")]));
}

mysqlQuery("UPDATE `" . $code['database'] . "`.`clientsFeedback` SET `clientsFeedbackOpened`=`clientsFeedbackOpened`+1 WHERE `idclientsFeedback` = " . $code['idclientsFeedback'] . "");
$OUT['date'] = $code['clientsFeedbackDate'];
$client = mfa(mysqlQuery("SELECT `idclients` AS `id`, `clientsLName` as `lastName`,`clientsFName` as `firstName`,`clientsMName` as `middleName` FROM `" . $code['database'] . "`.`clients` WHERE `idclients` = " . $code['clientsFeedbackClient'] . " "));

if (!$client) {
	die(json_encode(['success' => false, 'error' => ['id' => '404', 'text' => 'client not found'], 'currenttime' => date("Y-m-d H:i:s")]));
}
$OUT['client'] = $client;

$servicesApplied = query2array(mysqlQuery("SELECT *"
				. " FROM `" . $code['database'] . "`.`servicesApplied`"
				. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
				. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
				. " WHERE `servicesAppliedDate` = '" . $code['clientsFeedbackDate'] . "'"
				. " AND `servicesAppliedClient`=" . $code['clientsFeedbackClient'] . ""
				. " AND NOT isnull(`servicesAppliedService`)"
				. " AND isnull(`servicesAppliedDeleted`)"
				. ""));
if (!$servicesApplied) {
	die(json_encode(['success' => false, 'error' => ['id' => '404', 'text' => 'services not found'], 'currenttime' => date("Y-m-d H:i:s")]));
}
$personnel = [];
foreach ($servicesApplied as $serviceApplied) {
	$personnel[$serviceApplied['idusers']]['id'] = $serviceApplied['idusers'];
	$personnel[$serviceApplied['idusers']]['lastName'] = $serviceApplied['usersLastName'];
	$personnel[$serviceApplied['idusers']]['firstName'] = $serviceApplied['usersFirstName'];
	$personnel[$serviceApplied['idusers']]['middleName'] = $serviceApplied['usersMiddleName'];
//	$personnel[$serviceApplied['idusers']]['services'][] = $serviceApplied['servicesName'];
//	$personnel[$serviceApplied['idusers']]['services'] = [];
}

foreach (($_JSON['personnel'] ?? []) AS $user_json) {
	$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers`='" . mres($user_json['id']) . "'"));
	if ($user) {
		mysqlQuery("INSERT INTO `" . $code['database'] . "`.`clientsFeedbackRating` SET "
				. "`clientsFeedbackRatingDate` = '" . $code['clientsFeedbackDate'] . "',"
				. "`clientsFeedbackRatingClient` =" . $client['id'] . ","
				. "`clientsFeedbackRatingPersonnel` = " . $user['idusers'] . ","
				. "`clientsFeedbackRatingRating` = " . sqlVON($user_json['rating']) . ","
				. "`clientsFeedbackRatingComment` = " . sqlVON($user_json['comment']) . ""
				. "ON DUPLICATE KEY UPDATE "
				. "`clientsFeedbackRatingDate` = '" . $code['clientsFeedbackDate'] . "',"
				. "`clientsFeedbackRatingClient` =" . $client['id'] . ","
				. "`clientsFeedbackRatingPersonnel` = " . $user['idusers'] . ","
				. "`clientsFeedbackRatingRating` = " . sqlVON($user_json['rating']) . ","
				. "`clientsFeedbackRatingComment` = " . sqlVON($user_json['comment']) . ","
				. "`clientsFeedbackRatingTime` = NOW()"
				. "");
	}
	$OUT['saved'] = true; 
}


foreach ($personnel as $index => $user) {
	$personnel[$index]['positions'] = array_column(query2array(mysqlQuery("SELECT `positionsName` FROM `" . $code['database'] . "`.`usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser` = " . $index . "")), 'positionsName');
	$rating = mfa(mysqlQuery("SELECT `clientsFeedbackRatingRating`,`clientsFeedbackRatingComment` FROM `" . $code['database'] . "`.`clientsFeedbackRating`"
					. " WHERE `clientsFeedbackRatingDate` = '" . $code['clientsFeedbackDate'] . "'"
					. " AND `clientsFeedbackRatingClient` = " . $client['id'] . ""
					. " AND `clientsFeedbackRatingPersonnel` = " . $index . " "));
	if ($rating) {
		$personnel[$index]['rating'] = $rating['clientsFeedbackRatingRating'];
		$personnel[$index]['comment'] = $rating['clientsFeedbackRatingComment'];
	}
}

$OUT['personnel'] = array_values($personnel);
//$OUT['servicesApplied'] = $servicesApplied;

exit(json_encode($OUT, 288));
