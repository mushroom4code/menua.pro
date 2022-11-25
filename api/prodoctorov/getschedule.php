<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include $_ROOTPATH . '/sync/includes/setupLight.php';
include 'functions.php';
header("Content-type: application/json; charset=utf8");
//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔷 Расписание запрошено сайтом' . "\n"]); // . json_encode(($_JSON ?? []), 288 + 128)
if (getBearerToken() !== 'AIuZLsEgEShFbCNuwzko') {
	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔷 Не пройдена авторизация']);
	header("HTTP/1.1 401 Unauthorized");
	die();
}
//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔷 Доступ разрешен']);
$NAME = ['1' => 'ООО «Инфинити» Московские ворота', '2' => 'ООО «Инфинити» Чкаловская'];
$NAMEshort = ['1' => 'МВ', '2' => 'ЧК'];
$DATABASE = ['1' => 'warehouse', '2' => 'vita'];
$clinics = [];
for ($n = 1; $n <= 2; $n++) {
	$schedule = [];
	$schedule[$n] = $NAME[$n];
	$schedule['data'][$n] = [];
	$personnelSQL = '';
	if ($_JSON['clinics'] ?? false) {
		$clinic = array_filter($_JSON['clinics'], function ($clinic) use ($n) {
			return $clinic['id'] == $n;
		});
		sort($clinic);
//		printr($clinic);
//		die();
		if ($clinic[0] ?? false) {
			$usersStrings = [];
			foreach ($clinic[0]['personnel'] as $personnel) {
				$personnelStrings = [];
				if ($personnel['lname']) {
					$personnelStrings[] = " usersLastName = " . sqlVON($personnel['lname']) . "";
				}

				if ($personnel['fname']) {
					$personnelStrings[] = " usersFirstName = " . sqlVON($personnel['fname']) . "";
				}
				if ($personnel['mname']) {
					$personnelStrings[] = " usersMiddleName = " . sqlVON($personnel['mname']) . "";
				}
				if ($personnelStrings) {
					$usersStrings[] = implode(' AND ', $personnelStrings);
				}
			}
			if ($usersStrings) {
				$personnelSQL = " AND (" . implode(') OR (', $usersStrings) . ")";
				$users = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `" . $DATABASE[$n] . "`.`usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions`  " . " FROM `" . $DATABASE[$n] . "`.`users` WHERE isnull(`usersDeleted`) " . $personnelSQL));
				foreach ($users as $user) {
					telegramSendByRights(['183'], '🔷 ' . $user['usersLastName'] . " " . $user['usersFirstName'] . " " . $user['usersMiddleName'] . " (" . $user['positions'] . ") | " . $NAMEshort[$n] . "\n");
				}
			}
		}
	}



	$usersSchedule = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `" . $DATABASE[$n] . "`.`usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
					. " FROM `" . $DATABASE[$n] . "`.`users`"
					. " LEFT JOIN `" . $DATABASE[$n] . "`.`usersSchedule`  ON (`idusers` = `usersScheduleUser`)"
					. " WHERE `usersScheduleDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 13 DAY)"
					. $personnelSQL
					. " AND `usersScheduleUser` IN (SELECT `usersPositionsUser` FROM `" . $DATABASE[$n] . "`.`usersPositions` WHERE `usersPositionsPosition` IN ("
					. " 1,3,6,7,8,9,10,11,12,13,27,31,34,36,39,42,43,48,50,51,54,55,56,57,58,59,61,62,63,65,74,75"
					. "))"
					. " AND `usersScheduleHalfs` IN ('11','10','01')"
					. " ORDER BY `usersScheduleDate`,`usersScheduleUser`"));
	$servicesApplied = query2array(mysqlQuery("SELECT *"
					. " FROM `" . $DATABASE[$n] . "`.`servicesApplied`"
					. " WHERE `servicesAppliedDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 13 DAY)"
					. " AND NOT isnull(`servicesAppliedPersonal`)"
					. " AND isnull(`servicesAppliedDeleted`)"));

//printr($usersSchedule);
	foreach ($usersSchedule as $day) {
		$schedule['data'][$n][$day['usersScheduleUser']]['efio'] = $day['usersLastName'] . ' ' . $day['usersFirstName'] . ' ' . $day['usersMiddleName'];
		$schedule['data'][$n][$day['usersScheduleUser']]['espec'] = $day['positions'];
		$schedule['data'][$n][$day['usersScheduleUser']]['servicesApplied'] = array_filter($servicesApplied, function ($serviceApplied) use ($day) {
			return
			$serviceApplied['servicesAppliedDate'] == $day['usersScheduleDate'] &&
			$serviceApplied['servicesAppliedPersonal'] == $day['usersScheduleUser']
			;
		});
		for ($time = mystrtotime($day['usersScheduleDate'] . " 10:00:00"); $time < mystrtotime($day['usersScheduleDate'] . " 20:00:00"); $time += 30 * 60) {
//print $time;
			$available = !count(array_filter($schedule['data'][$n][$day['usersScheduleUser']]['servicesApplied'], function ($serviceApplied) use ($time) {
								return ($time >= strtotime($serviceApplied['servicesAppliedTimeBegin']) && $time < strtotime($serviceApplied['servicesAppliedTimeEnd']));
							})) && $time >= mystrtotime($day['usersScheduleFrom']) && $time < mystrtotime($day['usersScheduleTo']);

			$schedule['data'][$n][$day['usersScheduleUser']]['cells'][] = [
				"dt" => $day['usersScheduleDate'],
				"time_start" => date("H:i", $time),
				"time_end" => date("H:i", $time + 30 * 60),
				"free" => $available,
				"room" => ""
			];
		}
		unset($schedule['data'][$n][$day['usersScheduleUser']]['servicesApplied']);
	}
	$clinics[] = $schedule;
}


exit(json_encode($clinics, JSON_UNESCAPED_UNICODE));
