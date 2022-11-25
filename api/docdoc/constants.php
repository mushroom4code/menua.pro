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
$URL = 'https://bookingtest.sberhealth.ru/api/2.0';
$URL = 'https://booking.docdoc.ru/api/2.0';
$bearer = 'd0f89f0f82f343ef7b795f163d99e6671875917d534f01976e672aaa3de23552';
$alias = 'infinity';

function DOCDOC_updateSchedule($user, $clinicN) {
	global $alias, $bearer, $URL;
	$days_interval = 21;
	$DATABASE = ['1' => 'warehouse', '2' => 'vita'][$clinicN];
	$schedule = [];
	$dd = 0;
	while (($curdate = date("Y-m-d", strtotime('+ ' . $dd . ' days'))) < date("Y-m-d", strtotime('+' . $days_interval . ' days'))) {
		$schedule[$curdate] = [];
		$dd++;
	}
	$slots = 0;
	$userSchedule = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `" . $DATABASE . "`.`usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
					. " FROM `" . $DATABASE . "`.`users`"
					. " LEFT JOIN `" . $DATABASE . "`.`usersSchedule`  ON (`idusers` = `usersScheduleUser`)"
					. " WHERE `usersScheduleDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . $days_interval . " DAY)"
					. " AND `usersScheduleUser` = '" . $user['idusers'] . "'"
					. " AND `usersScheduleHalfs` IN ('11','10','01')"
					. " ORDER BY `usersScheduleDate`,`usersScheduleUser`"));

	$servicesApplied = query2array(mysqlQuery("SELECT * "
					. " FROM `" . $DATABASE . "`.`servicesApplied`"
					. " WHERE `servicesAppliedDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . $days_interval . " DAY)"
					. " AND `servicesAppliedPersonal` = '" . $user['idusers'] . "'"
					. " AND isnull(`servicesAppliedDeleted`)"));

	foreach ($userSchedule as $day) {

		$usersScheduleUserservicesApplied = array_filter($servicesApplied, function ($serviceApplied) use ($day) {
			return
			$serviceApplied['servicesAppliedDate'] == $day['usersScheduleDate'] &&
			$serviceApplied['servicesAppliedPersonal'] == $day['usersScheduleUser']
			;
		});
		for ($time = mystrtotime($day['usersScheduleDate'] . " 10:00:00"); $time < mystrtotime($day['usersScheduleDate'] . " 20:00:00"); $time += 30 * 60) {
//print $time;
			$available = !count(array_filter($usersScheduleUserservicesApplied, function ($serviceApplied) use ($time) {
								return ($time >= strtotime($serviceApplied['servicesAppliedTimeBegin']) && $time < strtotime($serviceApplied['servicesAppliedTimeEnd']));
							})) && $time >= mystrtotime($day['usersScheduleFrom']) && $time < mystrtotime($day['usersScheduleTo']);

			if (!($schedule[$day['usersScheduleDate']] ?? false)) {
				$schedule[$day['usersScheduleDate']] = [];
			}
			if ($available) {
				$schedule[$day['usersScheduleDate']][] = [
					"from" => date("Y-m-d H:i", $time),
					"to" => date("Y-m-d H:i", $time + 30 * 60),
					"interval" => "30"
				];
				$slots++;
			}
		}
		unset($schedule['servicesApplied']);
	}
//	printr($schedule);
//	printr(["days" => count($schedule), "slots" => $slots], 1);

	$data = [
		"jsonrpc" => "2.0",
		"method" => "updateSchedule",
		"params" => [
			"resourceExternal" => [
				"id" => 100000 * $clinicN + $user['idusers'],
				"clinic_id" => $clinicN,
				"prefix" => $alias
			],
			"schedule" => [
				"days" => ($schedule ?? [])
			]
		],
		"id" => microtime(1)
	];
	if (!count($schedule)) {
		printr($data);
	}
	if (1) {
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
//		sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔺 обновление расписания докдок (' . $DATABASE . ' ' . $user['idusers'] . ')']);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	} else {
		printr($data, 1);
		return NULL;
	}
}
