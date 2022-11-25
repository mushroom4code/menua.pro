<?php

set_time_limit(120);
$start = microtime(1);
header('Content-Encoding: none;');

include 'constants.php';
//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔺 Запускаю обновление расписания докдок']);

$positionsList = '1,3,6,7,8,9,10,11,12,13,27,31,34,36,39,42,43,48,50,51,54,55,56,57,58,59,61,62,63,65,74,75';
$users = query2array(mysqlQuery("SELECT "
				. " `idusers`, "
				. " '1' as `clinic_id`"
//				. " CONCAT_WS(' ', `usersLastName`, `usersFirstName`, `usersMiddleName`) AS `name`, "
//				. " 'doctor' as `type`"
				. " FROM `warehouse`.`users`"
				. " WHERE `idusers`  IN (SELECT `usersPositionsUser` FROM `warehouse`.`usersPositions` WHERE `usersPositionsPosition` IN ("
				. $positionsList
				. ")) AND isnull(`usersDeleted`)"
				. " UNION ALL"
				. " SELECT "
				. " `idusers`, "
				. " '2' as `clinic_id`"
//				. " CONCAT_WS(' ', `usersLastName`, `usersFirstName`, `usersMiddleName`) AS `name`, "
//				. " 'doctor' as `type`"
				. " FROM `vita`.`users`"
				. " WHERE `idusers`  IN (SELECT `usersPositionsUser` FROM `warehouse`.`usersPositions` WHERE `usersPositionsPosition` IN ("
				. $positionsList
				. ")) AND isnull(`usersDeleted`)"));

//printr($users);
foreach ($users as $user) {
	$response = DOCDOC_updateSchedule($user, $user['clinic_id']);
//	printr(json_decode($response), 1);
//	for ($n = 0; $n <= 100; $n++) {
//		print '<!--                                                                                                    -->';
//	}
//	print microtime(1) - $start;
//	flush();
	usleep(150000);
}
//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔺 обновление расписания докдок завершено за ' . round(microtime(1) - $start) . 'сек.']);
