<?php

$schedule['data']['1'] = [];
$usersSchedule = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `warehouse`.`usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
				. " FROM `warehouse`.`usersSchedule` "
				. " LEFT JOIN `warehouse`.`users` ON (`idusers` = `usersScheduleUser`)"
				. " WHERE `usersScheduleDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 13 DAY)"
				. " AND `usersScheduleUser` IN (SELECT `usersPositionsUser` FROM `warehouse`.`usersPositions` WHERE `usersPositionsPosition` IN ("
				. "1,3,6,7,8,9,10,11,12,13,27,34,36,39,42,43,50,51,54,57,58,61,62"
				. "))"
				. " AND `usersScheduleHalfs` IN ('11','10','01')"
				. " ORDER BY `usersScheduleDate`,`usersScheduleUser`"));
$servicesApplied = query2array(mysqlQuery("SELECT *"
				. " FROM `warehouse`.`servicesApplied`"
				. " WHERE `servicesAppliedDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 13 DAY)"
				. " AND NOT isnull(`servicesAppliedPersonal`)"
				. " AND isnull(`servicesAppliedDeleted`)"));

//printr($usersSchedule);
foreach ($usersSchedule as $day) {
	$schedule['data']['1'][$day['usersScheduleUser']]['efio'] = $day['usersLastName'] . ' ' . $day['usersFirstName'] . ' ' . $day['usersMiddleName'];
	$schedule['data']['1'][$day['usersScheduleUser']]['espec'] = $day['positions'];
	$schedule['data']['1'][$day['usersScheduleUser']]['servicesApplied'] = array_filter($servicesApplied, function ($serviceApplied) use ($day) {
		return
		$serviceApplied['servicesAppliedDate'] == $day['usersScheduleDate'] &&
		$serviceApplied['servicesAppliedPersonal'] == $day['usersScheduleUser']
		;
	});
	for ($time = mystrtotime($day['usersScheduleDate'] . " 10:00:00"); $time < mystrtotime($day['usersScheduleDate'] . " 20:00:00"); $time += 30 * 60) {
//print $time;
		$available = !count(array_filter($schedule['data']['1'][$day['usersScheduleUser']]['servicesApplied'], function ($serviceApplied) use ($time) {
							return ($time >= strtotime($serviceApplied['servicesAppliedTimeBegin']) && $time < strtotime($serviceApplied['servicesAppliedTimeEnd']));
						})) && $time >= mystrtotime($day['usersScheduleFrom']) && $time < mystrtotime($day['usersScheduleTo']);

		$schedule['data']['1'][$day['usersScheduleUser']]['cells'][] = [
			"dt" => $day['usersScheduleDate'],
			"time_start" => date("H:i", $time),
			"time_end" => date("H:i", $time + 30 * 60),
			"free" => $available,
			"room" => ""
		];
	}
	unset($schedule['data']['1'][$day['usersScheduleUser']]['servicesApplied']);
}

