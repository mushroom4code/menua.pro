<?php

include 'constants.php';
$positionsList = '1,3,6,7,8,9,10,11,12,13,27,31,34,36,39,42,43,48,50,51,54,55,56,57,58,59,61,62,63,65,74,75';
$users = query2array(mysqlQuery("SELECT "
				. " 100000+`idusers` as `external_id`, "
				. " '" . $alias . "_1' as `external_clinic_id`,"
				. " CONCAT_WS(' ', `usersLastName`, `usersFirstName`, `usersMiddleName`) AS `name`, "
				. " 'doctor' as `type`"
				. " FROM `warehouse`.`users`"
				. " WHERE `idusers`  IN (SELECT `usersPositionsUser` FROM `warehouse`.`usersPositions` WHERE `usersPositionsPosition` IN ("
				. $positionsList
				. ")) AND isnull(`usersDeleted`)"
				. " UNION ALL"
				. " SELECT "
				. " 200000+`idusers` as `external_id`, "
				. " '" . $alias . "_2' as `external_clinic_id`,"
				. " CONCAT_WS(' ', `usersLastName`, `usersFirstName`, `usersMiddleName`) AS `name`, "
				. " 'doctor' as `type`"
				. " FROM `vita`.`users`"
				. " WHERE `idusers`  IN (SELECT `usersPositionsUser` FROM `warehouse`.`usersPositions` WHERE `usersPositionsPosition` IN ("
				. $positionsList
				. ")) AND isnull(`usersDeleted`)"));
//print count($users);
//"idusers": 796,
// "": "Воронина",
// "": "Екатерина",
// "": "Александровна",
//$alias . '_1';
//printr($users, 1);
//die();

$data = [
	"jsonrpc" => "2.0",
	"method" => "addResources",
	"params" => [
		"resources" => $users
	],
	"id" => microtime(1)
];
//printr($data);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	"Content-Type: application/json",
	"Authorization: Bearer " . $bearer
));

$response = curl_exec($ch);
curl_close($ch);

printr(json_decode($response), 1);

