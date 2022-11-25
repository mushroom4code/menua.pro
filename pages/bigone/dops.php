<?php

$qstart = microtime(1);
$servicesApplied = query2array(mysqlQuery("SELECT "
				. " `idservicesApplied`, "
				. " `servicesAppliedQty`, "
				. " `servicesAppliedPersonal`, "
				. " `servicesAppliedDate`, "
				. " `servicesAppliedFineshed`,  "
				. " `servicesAppliedContract`, "
				. " `servicesAppliedPrice`, "
				. " `servicesAppliedDeleted`, "
				. " `idservices`,"
				. " `servicesName`,"
				. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='1' AND `servicesPricesService` = `servicesAppliedService`)) as `priceMin`,"
				. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='2' AND `servicesPricesService` = `servicesAppliedService`)) as `priceMax`,"
				. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='3' AND `servicesPricesService` = `servicesAppliedService`)) as `wageMin`,"
				. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='4' AND `servicesPricesService` = `servicesAppliedService`)) as `wageMax`,"
				. " `usersGroup`"
				. " FROM `servicesApplied`"
				. " LEFT JOIN `services` ON (`idservices`=`servicesAppliedService`)"
				. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
				. " WHERE `servicesAppliedDate`>='$from'"
				. " AND  `servicesAppliedDate`<='$to'"
				. " AND isnull(`servicesAppliedDeleted`)"
				. " AND NOT isnull(`servicesAppliedFineshed`)"
				. " AND NOT isnull(`usersGroup`)"
				. ""));

//print '<br>' . (microtime(1) - $qstart) . '<br>';
//printr($servicesApplied);

foreach ($servicesApplied as $serviceApplied) {
	$serviceApplied['isfree'] = !(($serviceApplied['servicesAppliedContract']) || (!$serviceApplied['servicesAppliedContract'] && intval($serviceApplied['servicesAppliedPrice'])));
	$matrix = [];
	$matrix['w']['min'] = $serviceApplied['wageMin'] ?? $serviceApplied['wageMax'] ?? null;
	$matrix['w']['max'] = $serviceApplied['wageMax'] ?? $serviceApplied['wageMin'] ?? null;
	$matrix['p']['min'] = $serviceApplied['priceMin'] ?? $serviceApplied['priceMax'] ?? null;
	$matrix['p']['max'] = $serviceApplied['priceMax'] ?? $serviceApplied['priceMin'] ?? null;
	if (
			is_null($matrix['w']['min']) ||
			is_null($matrix['w']['max']) ||
			is_null($matrix['p']['min']) ||
			is_null($matrix['p']['max'])
	) {
		$serviceApplied['wage'] = 0;
	} else {
		$matrix['w']['d'] = $matrix['w']['max'] - $matrix['w']['min'];
		$matrix['p']['d'] = $matrix['p']['max'] - $matrix['p']['min'];
		if ($matrix['p']['d']) {
			$matrix['slope'] = $matrix['w']['d'] / $matrix['p']['d'];
		} else {
			$matrix['slope'] = null;
		}
		if (is_null($matrix['slope'])) {
			if ($serviceApplied['servicesAppliedPrice'] <= $matrix['p']['min']) {
				$serviceApplied['wage'] = $matrix['w']['min'];
			} else {
				$serviceApplied['wage'] = $matrix['w']['max'];
			}
		} else {
			if ($serviceApplied['servicesAppliedPrice'] <= $matrix['p']['min']) {
				$serviceApplied['wage'] = $matrix['w']['min'];
			} elseif ($serviceApplied['servicesAppliedPrice'] >= $matrix['p']['max']) {
				$serviceApplied['wage'] = $matrix['w']['max'];
			} else {
				$serviceApplied['wage'] = $matrix['w']['min'] + ($serviceApplied['servicesAppliedPrice'] - $matrix['p']['min']) * $matrix['slope'];
			}
		}
	}



	$SABYGROUP[$serviceApplied['servicesAppliedPersonal']][$serviceApplied['servicesAppliedDate']][] = $serviceApplied;
}

foreach ($groups as &$group3_A) {
	foreach ($group3_A['users'] as &$user3_A) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
			$servicesApplied = ($SABYGROUP[$user3_A['idusers']][mydates("Y-m-d", $time)] ?? []);
			$topay = 0;
			$ignoredops = ($user3_A['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][13] ?? 0);
			$isfreecounts = ($user3_A['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][15] ?? 0);
			foreach ($servicesApplied as &$serviceApplied2) {
				$isfree = ($serviceApplied2['isfree'] ?? false);
				/* ($user3_A['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][9] ?? 0) *
				  ($user3_A['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] ?? 0) *
				  ($user3_A['userSchedule'][mydates("Y-m-d", $time)]['duration'] ?? 0) */

				$serviceApplied2['toPay'] = $ignoredops ? 0 : ((($isfree && $isfreecounts) || (!$isfree)) ? (($serviceApplied2['wage'] ?? 0) * ($serviceApplied2['servicesAppliedQty'] ?? 0)) : 0);
				$topay += $serviceApplied2['toPay'];
			}


			$user3_A['wages'][mydates("Y-m-d", $time)]['A']['value'] = $topay;
			$user3_A['wages'][mydates("Y-m-d", $time)]['A']['info'] = [
				'ignoredops' => ($user3_A['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][13] ?? 0),
				'isfreecounts' => ($user3_A['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][15] ?? 0),
				'servicesApplied' => $servicesApplied
//				'fingerlogPerc' => ($user3_A['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] ?? 0),
//				'userSchedule' => ($user3_A['userSchedule'][mydates("Y-m-d", $time)]['duration'] ?? 0),
//				'wageperhour' => ($user3_A['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][9] ?? 0),
			];
		}//$time
	}//users
}//groups