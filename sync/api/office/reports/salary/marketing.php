<?php

mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

function getAuthorizationHeader() {
	$headers = null;
	if (isset($_SERVER['Authorization'])) {
		$headers = trim($_SERVER["Authorization"]);
	} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
		$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	} elseif (function_exists('apache_request_headers')) {
		$requestHeaders = apache_request_headers();
		// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
		$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		//print_r($requestHeaders);
		if (isset($requestHeaders['Authorization'])) {
			$headers = trim($requestHeaders['Authorization']);
		}
	}
	return $headers;
}

function getBearerToken() {
	$headers = getAuthorizationHeader();
// HEADER: Get the access token from the header
	if (!empty($headers)) {
		if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
			return $matches[1];
		}
	}
	return null;
}

if (getBearerToken() !== 'zJ09f0EUxBnz89LbZAtyyUgpvix4eTi2') {
//	header("HTTP/1.1 401 Unauthorized");
//	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => "Unauthorized"]);
//	die('unauthorized');
}
// 

$from = $_GET['from'] ?? date("Y-m-01");
$to = $_GET['to'] ?? date("Y-m-d");
$personnel = query2array(mysqlQuery("SELECT * FROM `users` WHERE `usersGroup` = 12 AND isnull(`usersDeleted`) ORDER BY `usersLastName`,`usersFirstName`,`usersMiddleName`"), 'idusers');
$previsitSQL = "SELECT `CV`.*,`clients`.*,`clientsSources`.*, (SELECT COUNT(1) FROM `clientsVisits` as `PV` WHERE `PV`.`clientsVisitsClient`=`CV`.`clientsVisitsClient` AND `PV`.`clientsVisitsDate`>DATE_SUB(`CV`.`clientsVisitsDate`, INTERVAL 3 MONTH) AND `PV`.`clientsVisitsDate`<`CV`.`clientsVisitsDate`) AS `previsit`,"
		. "(SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `CV`.`clientsVisitsClient` AND `f_salesDate`< `CV`.`clientsVisitsDate`) as `sales`"
		. " FROM `clientsVisits` AS `CV`"
		. " LEFT JOIN `clients` ON (`idclients` = `CV`.`clientsVisitsClient`)"
		. " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)"
		. " WHERE"
		. " `CV`.`clientsVisitsDate`>='" . min($from, $to) . "'"
		. " AND  `CV`.`clientsVisitsDate`<='" . max($from, $to) . "'"
		. "";

$visits = array_values(array_filter(query2array(mysqlQuery($previsitSQL)), function ($visit) {
			return !$visit['previsit'] && !$visit['sales'];
		}));

$visits = array_map(function ($visit) {
	$visit['servicesApplied'] = query2array(mysqlQuery("SELECT * "
					. " FROM `servicesApplied`"
					. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
					. " WHERE"
					. " `servicesAppliedDate` = '" . $visit['clientsVisitsDate'] . "'"
					. " AND `servicesAppliedClient`  = '" . $visit['clientsVisitsClient'] . "'"
					. " AND `usersGroup` = 12"
					. " ORDER BY `idservicesApplied` DESC"));

	return $visit;
}, $visits);
//			printr($visits, 1);
foreach ($visits as $index => $visit) {

	if (count($visit['servicesApplied'] ?? [])) {
//					printr($visit);
		$personnel[$visit['servicesApplied'][0]['idusers']]['schedule'][$visit['clientsVisitsDate']]['clients'][] = [
			'idclients' => $visit['clientsVisitsClient'],
			'clientsSource' => $visit['clientsSource'],
			'clientsSourcesLabel' => $visit['clientsSourcesLabel'],
			'clientsLName' => $visit['clientsLName'],
			'clientsFName' => $visit['clientsFName'],
			'clientsMName' => $visit['clientsMName'],
		];
	}
}


$personnel = array_values($personnel);
//			printr($personnel);
foreach ($personnel as $index => $user) {
	if (!($user['idusers'] ?? false)) {
		continue;
	}
	$personnel[$index]['diagnostics'] = query2array(mysqlQuery("SELECT * "
					. " FROM `servicesApplied` "
					. " LEFT JOIN `serviceMotivation` ON (`serviceMotivationService` = `servicesAppliedService`)"
					. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
					. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
					. " WHERE "
					. " `servicesAppliedDate` >= '" . min($from, $to) . "'"
					. " AND `servicesAppliedDate` <= '" . max($from, $to) . "'"
					. " AND `servicesAppliedBy` = '" . $user['idusers'] . "'"
					. " AND `serviceMotivationMotivation` = 5"// - 5 диагностика
					. " AND isnull(`servicesAppliedDeleted`)"
					. " AND NOT isnull(`servicesAppliedFineshed`)"
	));

	$fingerLog = query2array(mysqlQuery("SELECT * "
					. " FROM `fingerLog` "
					. " WHERE "
					. " `fingerLogTime` >= '" . min($from, $to) . " 00:00:00'"
					. " AND `fingerLogTime` <= '" . max($from, $to) . " 23:59:59'"
					. " AND `fingerLogUser` = '" . $user['idusers'] . "'"
	));

	$schedule = query2array(mysqlQuery("SELECT * "
					. " FROM `usersSchedule` "
					. " WHERE "
					. " `usersScheduleDate` >= '" . min($from, $to) . "'"
					. " AND `usersScheduleDate` <= '" . max($from, $to) . "'"
					. " AND `usersScheduleUser` = '" . $user['idusers'] . "'"
					. " AND `usersScheduleHalfs` IN ('11','10')"
	));

	foreach ($fingerLog as $entry) {
//					printr($entry);
		$personnel[$index]['schedule'][date("Y-m-d", strtotime($entry['fingerLogTime']))]['fact'][] = $entry['fingerLogTime'];
	}
	foreach ($schedule as $entry) {
//					printr($entry);
		$personnel[$index]['schedule'][$entry['usersScheduleDate']]['plan'] = [$entry['usersScheduleFrom'], $entry['usersScheduleTo']];
		$personnel[$index]['schedule'][$entry['usersScheduleDate']]['shift'] = (string) $entry['usersScheduleHalfs'];
	}

	foreach (($personnel[$index]['schedule'] ?? []) as $date => $data) {

		if ($personnel[$index]['schedule'][$date]['plan'] ?? false) {
			$plan = strtotime(max($personnel[$index]['schedule'][$date]['plan'])) - strtotime(min($personnel[$index]['schedule'][$date]['plan']));
		} else {
			$plan = null;
		}
		if ($personnel[$index]['schedule'][$date]['fact'] ?? false) {
			$fact = strtotime(max($personnel[$index]['schedule'][$date]['fact'])) - strtotime(min($personnel[$index]['schedule'][$date]['fact']));
		} else {
			$fact = null;
		}
		if (($plan ?? false) && ($fact ?? false)) {
			$personnel[$index]['schedule'][$date]['percent'] = round(max(0, min($fact / $plan, 1)) * 100);
		} else {
			$personnel[$index]['schedule'][$date]['percent'] = 0;
		}
	}
//				printr($personnel[$index]['schedule'] ?? '');
}


$OUTPUT = [];
foreach ($personnel as $user) {
	if (!($user['idusers'] ?? false)) {
		continue;
	}

	$OUTPUT[] = [
		'idusers' => $user['idusers'],
		'usersName' => trim($user['usersLastName'] . ' ' . $user['usersFirstName'] . ' ' . $user['usersMiddleName']),
		'shifts650' => round((array_reduce(($user['schedule'] ?? []), function ($carry, $item) {
//												printr($item);
					if (($item['shift'] ?? false) == '10') {
						$carry += $item['percent'] / 100;
//												print $carry;
					}
					return $carry;
				})), 1),
		'shifts1000' => round((array_reduce(($user['schedule'] ?? []), function ($carry, $item) {
//											printr($item);
					if (($item['shift'] ?? false) == '11') {
						$carry += $item['percent'] / 100;
					}
					return $carry;
				})), 1),
		'clients_I' => round((array_reduce(($user['schedule'] ?? []), function ($carry, $item) {

					$carry += count(array_filter(($item['clients'] ?? []), function ($client) {
								return !in_array($client['clientsSource'], [15, 13, 22]);
							}));
					return $carry;
				})), 1),
		'clients_LG' => round((array_reduce(($user['schedule'] ?? []), function ($carry, $item) {
					$carry += count(array_filter(($item['clients'] ?? []), function ($client) {
								return in_array($client['clientsSource'], [15, 13, 22]);
							}));
					return $carry;
				})), 1)
	];
}

print json_encode($OUTPUT, 288 + 128);

