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
$url = 'https://api.prodoctorov.ru/mis/send_cells/';

$login[1] = 'medicinskiy-centr-infiniti-sankt-peterburg-13526';
$password[1] = '22de56880eb708d025fa9f9c3547cb7a';

$login[2] = 'medicinskiy-centr-infiniti-sankt-peterburg-16225';
$password[2] = 'dbecd6bd335cb2bf9e23138e58022aa8';
$NAME = ['1' => 'ООО «Инфинити» Московские ворота', '2' => 'ООО «Инфинити» Чкаловская'];
$DATABASE = ['1' => 'warehouse', '2' => 'vita'];

for ($n = 1; $n <= 1; $n++) {
	$schedule = [];
	$schedule[$n] = $NAME[$n];
	$schedule['data'][$n] = [];
	$usersSchedule = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `" . $DATABASE[$n] . "`.`usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
					. " FROM `" . $DATABASE[$n] . "`.`usersSchedule` "
					. " LEFT JOIN `" . $DATABASE[$n] . "`.`users` ON (`idusers` = `usersScheduleUser`)"
					. " WHERE `usersScheduleDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 13 DAY)"
					. " AND `usersScheduleUser` IN (SELECT `usersPositionsUser` FROM `" . $DATABASE[$n] . "`.`usersPositions` WHERE `usersPositionsPosition` IN ("
					. " 1,3,6,7,8,9,10,11,12,13,27,31,34,36,37,38,39,42,43,48,50,51,54,55,56,57,58,59,61,62,63,65,74,75"
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



	$dateToSend['cells'] = json_encode($schedule);
	$ch = curl_init($url);
	$dateToSend['login'] = $login[$n];
	$dateToSend['password'] = $password[$n];
	$payload = json_encode($dateToSend);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);
	$scheduleAll[$n] = $schedule;
}
exit(json_encode(['$result' => $result, '$schedule' => $scheduleAll]));

