<?php

set_time_limit(120);
$start = microtime(1);
header('Content-Encoding: none;');

include 'constants.php';

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

function DOCDOC_userSchedule($user, $clinicN) {
	global $alias;
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
	return $data;
}

/*
  $username = 'xxxxx';
  $api_onfleet = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
  $url_onfleet = "https://onfleet.com/api/v2/tasks";

  curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  $request =  $url.'api/mail.send.json';

  // Generate curl request
  $session = curl_init($request);
  // Tell curl to use HTTP POST
  curl_setopt ($session, CURLOPT_POST, true);
  // Tell curl that this is the body of the POST
  curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
  // Tell curl not to return headers, but do return the response
  curl_setopt($session, CURLOPT_HEADER, false);
  curl_setopt($session, CURLOPT_RETURNTRANSFER, true);


  // Post the Pickup task to Onfleet
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url_onfleet);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_USERPWD, $api_onfleet);
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($ch, CURLOPT_ENCODING, "");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, '{"destination":{"address":{"unparsed":"'.$pickup_address.'"},"notes":"'.$comments.'"},"recipients":[{"name":"'.$name.'","phone":"+61'.$phone.'","notes":"Number of riders: '.$riders.'"}],"completeBefore":'.$timestamp.',"pickupTask":"yes","autoAssign":{"mode":"distance"}}');
  $mh = curl_multi_init();

  curl_multi_add_handle($mh,$ch);




 */




$mh = curl_multi_init();

foreach ($users as $user) {
	$data = DOCDOC_userSchedule($user, $user['clinic_id']);
	$currentChannel = curl_init();
	curl_multi_add_handle($mh, $currentChannel);

	curl_setopt($currentChannel, CURLOPT_URL, $URL);
	curl_setopt($currentChannel, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($currentChannel, CURLOPT_HEADER, FALSE);
	curl_setopt($currentChannel, CURLOPT_POST, TRUE);
	curl_setopt($currentChannel, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

	curl_setopt($currentChannel, CURLOPT_HTTPHEADER, array(
		"Content-Type: application/json",
		"Authorization: Bearer " . $bearer
	));

//	$response = curl_exec($currentChannel);

	$channels[] = $currentChannel;
	print count($channels) . ', ';
//	usleep(150000);
	for ($n = 0; $n <= 100; $n++) {
		print '<!--                                                                                                                                                                                                                                                                                                            -->';
	}
	print microtime(1) - $start;
	flush();
//	sleep(1);
}

$active = null;
//execute the handles
do {
	$mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
	if (curl_multi_select($mh) != -1) {
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	}
}


//close the handles
foreach ($channels as $channel) {
	$info = curl_multi_getcontent($channel);
	print '<iframe>'
			. print_r($info, 1)
			. '</iframe>';
	curl_multi_remove_handle($mh, $channel);
	curl_close($channel);
}


curl_multi_close($mh);
