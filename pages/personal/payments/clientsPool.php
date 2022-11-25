<?php
$clientsPool = []; //данные по клиентам
$personnelPool = []; //данные по работникам. $personnelPool[iduser][date]...

$fingerPool = []; //date->idusers->	logs->[]->time //приходы
//									hours -> duration
$schedulePool = []; //Гафик работы
$rewardsPool = []; //тут мы храним отсортированный массив вознаграждений
$f_salesPool = []; //тут продажи за этот месяц, а так же те, по которым проводились платежи в этом месяце.
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
$start = microtime(1);

foreach (query2array(mysqlQuery("SELECT * FROM `users` WHERE isnull(`usersDeleted`)")) as $user) {
	$personnelPool[$user['idusers']] = [];
}

if (mydates("Ym", mystrtotime($from)) === mydates("Ym", mystrtotime($to))) {//В пределах одного месяца
	$monthStart = mydates("Y-m-01", mystrtotime($from));
	$monthEnd = mydates("Y-m-t", mystrtotime($from));
}
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];

if (1) {//
	$servicesApplied = query2array(mysqlQuery("SELECT "
					. " `idservicesApplied`,"
					. "`servicesAppliedService`,"
					. "`servicesAppliedQty`,"
					. "`servicesAppliedClient`,"
					. "`servicesAppliedBy`,"
					. "`servicesAppliedPersonal`,"
					. "`servicesAppliedDate`,"
					. "`servicesAppliedAt`,"
					. "`servicesAppliedFineshed`,"
					. "`servicesAppliedContract`,"
					. "`servicesAppliedPrice`,"
					. "`servicesAppliedDeleted`,"
					. "`servicesAppliedDeletedBy`,"
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='1' AND `servicesPricesService` = `servicesAppliedService`)) as `priceMin`,"
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='2' AND `servicesPricesService` = `servicesAppliedService`)) as `priceMax`,"
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='3' AND `servicesPricesService` = `servicesAppliedService`)) as `wageMin`,"
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<`servicesAppliedFineshed` AND `servicesPricesType`='4' AND `servicesPricesService` = `servicesAppliedService`)) as `wageMax`,"
					. "`servicesAppliedDeleteReason`"
					. " FROM `servicesApplied` "
					. " WHERE `servicesAppliedDate`>='$monthStart' AND `servicesAppliedDate`<='$monthEnd'"));
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	$affectedSales = query2array(mysqlQuery("SELECT `idf_sales` FROM `f_sales`"
					. " WHERE "
					. "`idf_sales` IN (SELECT `f_paymentsSalesID` FROM `f_payments` WHERE `f_paymentsDate`>='$monthStart 00:00:00' AND `f_paymentsDate`<='$monthEnd 23:59:59' GROUP BY `f_paymentsSalesID`)"
//					. (count($servicesApplied ?? []) ? (" OR `idf_sales` IN (" . implode(',', array_unique(array_filter(array_column($servicesApplied, 'servicesAppliedContract')))) . ")") : "")
					. " OR (`f_salesDate`>='$monthStart' AND `f_salesDate`<='$monthEnd')"), 'idf_sales');
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	if (count($affectedSales)) {
		$payments = query2array(mysqlQuery("SELECT *,DATE(`f_paymentsDate`) as `f_paymentsDate` FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE `f_paymentsSalesID` IN (" . implode(',', array_keys($affectedSales)) . ") "));
		$credits = query2array(mysqlQuery("SELECT * FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`) WHERE `f_creditsSalesID` IN (" . implode(',', array_keys($affectedSales)) . ") "));
		foreach ($payments as $payment) {
			$f_salesPool[$payment['idf_sales']]['idf_sales'] = $payment['idf_sales'];
			$f_salesPool[$payment['idf_sales']]['f_salesSumm'] = $payment['f_salesSumm'];
			$f_salesPool[$payment['idf_sales']]['f_salesDate'] = $payment['f_salesDate'];
			$f_salesPool[$payment['idf_sales']]['f_salesType'] = $payment['f_salesType'];
			$f_salesPool[$payment['idf_sales']]['f_salesClient'] = $payment['f_salesClient'];
			$f_salesPool[$payment['idf_sales']]['f_salesCreditManager'] = $payment['f_salesCreditManager'];
			$f_salesPool[$payment['idf_sales']]['payments'][] = [
				'f_paymentsType' => $payment['f_paymentsType'],
				'f_paymentsAmount' => $payment['f_paymentsAmount'],
				'f_paymentsDate' => $payment['f_paymentsDate'],
				'f_paymentsAge' => (mystrtotime($payment['f_paymentsDate']) - mystrtotime($payment['f_salesDate'])) / (60 * 60 * 24)
			];
		}
		$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
		foreach ($credits as $credit) {
			$f_salesPool[$credit['idf_sales']]['idf_sales'] = $credit['idf_sales'];
			$f_salesPool[$credit['idf_sales']]['f_salesSumm'] = $credit['f_salesSumm'];
			$f_salesPool[$credit['idf_sales']]['f_salesDate'] = $credit['f_salesDate'];
			$f_salesPool[$credit['idf_sales']]['f_salesType'] = $credit['f_salesType'];
			$f_salesPool[$credit['idf_sales']]['f_salesClient'] = $credit['f_salesClient'];
			$f_salesPool[$credit['idf_sales']]['f_salesCreditManager'] = $credit['f_salesCreditManager'];
			$f_salesPool[$credit['idf_sales']]['payments'][] = [
				'f_paymentsAmount' => $credit['f_creditsSumm'],
				'f_paymentsDate' => $credit['f_salesDate'],
				'f_paymentsAge' => 0,
			];
		}
	}



	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	foreach ($f_salesPool as $idf_sale => $f_sale) {
		if (count($f_salesPool[$idf_sale]['payments'] ?? []) > 0) {
			usort($f_salesPool[$idf_sale]['payments'], function ($a, $b) {
				return $b['f_paymentsAge'] <=> $a['f_paymentsAge'];
			});
			$f_salesPool[$idf_sale]['intime'] = $f_salesPool[$idf_sale]['payments'][0]['f_paymentsAge'] <= 31;
		}
		$f_salesPool[$idf_sale]['payed'] = (array_sum(array_column($f_salesPool[$idf_sale]['payments'], 'f_paymentsAmount')) >= $f_salesPool[$idf_sale]['f_salesSumm']);
	}
}




$users = query2array(mysqlQuery("SELECT `idusers`,`usersLastName`,`usersFirstName`,`usersMiddleName`,`usersGroup` FROM `users`"), 'idusers');
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
$usersGroups = query2array(mysqlQuery("SELECT `idusersGroups`,`usersGroupsName` FROM `usersGroups`"), 'idusersGroups');

function getReward($userPaymentsValues, $idusers, $userPaymentsValuesType, $userPaymentsValuesDate) {//опции по оплате, т.к. у нас всё отсортировано, удаляем всё, кроме подходящих опций и удаляем настройки из будущего. и берем последнюю запись.
	$typePayments = obj2array(array_filter($userPaymentsValues, function ($userPaymentsValue) use ($idusers, $userPaymentsValuesType, $userPaymentsValuesDate) {
				return ($userPaymentsValue['userPaymentsValuesUser'] == $idusers) && ($userPaymentsValue['userPaymentsValuesType'] == $userPaymentsValuesType) && mystrtotime($userPaymentsValue['userPaymentsValuesDate']) <= mystrtotime($userPaymentsValuesDate);
			}));
	if (count($typePayments)) {
		return floatval($typePayments[0]['userPaymentsValuesValue'] ?? 0);
	} else {
		return 0;
	}
}

function getRecruitingWage($db, $date, $qty) {
	$filtered1 = array_filter($db, function ($row) use ($date) {
		return mystrtotime($date) >= mystrtotime($row['recruitingValuesDate']);
	});
	$maxDate = max(array_column($filtered1, 'recruitingValuesDate'));

	$filtered2 = array_filter($db, function ($row) use ($maxDate) {
		return mystrtotime($maxDate) == mystrtotime($row['recruitingValuesDate']);
	});
	usort($filtered2, function ($a, $b) {
		return $a['recruitingValuesQty'] <=> $b['recruitingValuesQty'];
	});
	$OUT = null;
	foreach ($filtered2 as $row) {
		if ($qty < $row['recruitingValuesQty']) {
			$OUT = $row['recruitingValuesWage'];
			break;
		}
	}
	return $OUT;
}

$AEs = query2array(mysqlQuery("SELECT * FROM `AEvalues` WHERE `AEvaluesDate`<='$monthEnd'"));

$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start]; //micro time(1) - $start
if (1) {
//	$f_salesPool = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE  (`f_salesDate` BETWEEN '$monthStart' AND '$monthEnd')"), 'idf_sales');

	if (count($f_salesPool)) {
		$f_salesToPersonal = query2array(mysqlQuery("SELECT * FROM `f_salesToPersonal` WHERE `f_salesToPersonalSalesID` IN(" . implode(',', array_keys($f_salesPool)) . ")"));
		foreach ($f_salesToPersonal as $f_saleToPersonal) {
			$f_salesPool[$f_saleToPersonal['f_salesToPersonalSalesID']]['participants'][$f_saleToPersonal['f_salesToPersonalUser']] = $users[$f_saleToPersonal['f_salesToPersonalUser']];
		}
	}
	foreach ($f_salesPool as &$f_salesPool_1) {
		$f_salesPool_1['AE'] = getAEs($AEs, $f_salesPool_1['f_salesSumm'], $f_salesPool_1['f_salesDate']);
//		$f_salesPool_1['f_salesClient'] = $clientsPool[$f_salesPool_1['f_salesClient']];
	}
}




if (1) {//все данные по оплате труда
	$rewardsPool = query2array(mysqlQuery("SELECT * FROM `userPaymentsValues` WHERE `userPaymentsValuesDate`<='$monthEnd'"));
	usort($rewardsPool, function ($a, $b) {
//сортируем по пользователю
		if ($a['userPaymentsValuesUser'] <=> $b['userPaymentsValuesUser']) {
			return $a['userPaymentsValuesUser'] <=> $b['userPaymentsValuesUser'];
		}
		//потом по типу правила
		if ($a['userPaymentsValuesType'] <=> $b['userPaymentsValuesType']) {
			return $a['userPaymentsValuesType'] <=> $b['userPaymentsValuesType'];
		}
		//потом по дате
		if ($a['userPaymentsValuesDate'] <=> $b['userPaymentsValuesDate']) {
			return $b['userPaymentsValuesDate'] <=> $a['userPaymentsValuesDate'];
		}
		//и если прям всё совпало - по айди от последнего к первому.
		return $b['iduserPaymentsValues'] <=> $a['iduserPaymentsValues'];
	});
}


if (1) { //координаторы
	$LT = query2array(mysqlQuery("SELECT * "
					. " FROM `LT` "
					. " WHERE"
					. " `LTtype` = '1'"
	));

	usort($LT, function ($a, $b) {
		if ($a['LTdate'] <=> $b['LTdate']) {
			return $b['LTdate'] <=> $a['LTdate'];
		}

		if ($a['LTvalue'] <=> $b['LTvalue']) {
			return floatval($a['LTvalue']) <=> floatval($b['LTvalue']);
		}
	});

	$coordinatorsSalesFlat = array_filter(query2array(mysqlQuery(""
							. " SELECT `idf_sales`,`f_salesSumm`,`f_salesDate`,`f_salesToCoordCoord` "
							. " FROM `f_sales` "
							. " LEFT JOIN `f_salesToCoord` ON (`f_salesToCoordSalesID`=`idf_sales`)"
							. " WHERE"
							. " (isnull(`f_salesCancellationDate`) OR `f_salesCancellationDate`>'$monthEnd')"
							. " AND `f_salesDate`>='$monthStart' AND `f_salesDate`<='$monthEnd'"
							. " AND (ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales`),0)+"
							. "  ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0))>=`f_salesSumm`"
							. "")), function ($f_sale) use ($AEs) {
				return getAEs($AEs, $f_sale['f_salesSumm'], $f_sale['f_salesDate']) > 0;
			});

	$coordinatorsCanceledSalesFlat = array_filter(query2array(mysqlQuery(""
							. " SELECT  `idf_sales`,`f_salesSumm`,`f_salesDate`,`f_salesToCoordCoord`,`f_salesCancellationDate` "
							. " FROM `f_sales` "
							. " LEFT JOIN `f_salesToCoord` ON (`f_salesToCoordSalesID`=`idf_sales`)"
							. " WHERE"
							. " NOT isnull(`f_salesCancellationDate`)"
							. " AND `f_salesCancellationDate`>='$monthStart' AND `f_salesCancellationDate`<='$monthEnd'"
							. " AND `f_salesDate`<'$monthStart'"
							. " AND (ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales`),0)+"
							. "  ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0))>=`f_salesSumm`"
							. "")), function ($f_sale) use ($AEs) {
				return getAEs($AEs, $f_sale['f_salesSumm'], $f_sale['f_salesDate']) > 0;
			});
}




if (1) {
	$recruitingValues = query2array(mysqlQuery("SELECT * FROM `recruitingValues` WHERE `recruitingValuesDate`<='$monthEnd'"));
	usort($recruitingValues, function ($a, $b) {
		if ($b['recruitingValuesDate'] <=> $a['recruitingValuesDate']) {
			return $b['recruitingValuesDate'] <=> $a['recruitingValuesDate'];
		}
		if ($b['recruitingValuesQty'] <=> $a['recruitingValuesQty']) {
			return $b['recruitingValuesQty'] <=> $a['recruitingValuesQty'];
		}
	});
	$recruitingResults = query2array(mysqlQuery("SELECT * FROM `recruiting` WHERE `recruitingDate`>='$monthStart' AND `recruitingDate`<='$monthEnd'"));
	foreach ($recruitingResults as $recruitingResult) {
		$recruitingDirector[$recruitingResult['recruitingDate']] = ($recruitingDirector[$recruitingResult['recruitingDate']] ?? 0) + $recruitingResult['recruitingQty'];
		$personnelPool[$recruitingResult['recruitingUser']][$recruitingResult['recruitingDate']]['payments']['recrut']['qty'] = $recruitingResult['recruitingQty'];
	}
}
//printr($recruitingDirector);
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];

$scores = query2array(mysqlQuery("SELECT * FROM `score` WHERE (`scoreDate` BETWEEN '$monthStart' AND '$monthEnd') ORDER BY `idscore`"));
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];

if (1) {//Расчёт по сменам
	$usersSchedules = query2array(mysqlQuery("SELECT `usersScheduleDate`,`usersScheduleUser`,`usersScheduleFrom`,`usersScheduleTo`,`usersScheduleDuty` FROM `usersSchedule` WHERE (`usersScheduleDate` BETWEEN '$monthStart' AND '$monthEnd')"));
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];

	foreach ($usersSchedules as $usersSchedule) {
		$personnelPool[$usersSchedule['usersScheduleUser']][$usersSchedule['usersScheduleDate']]['payments']['1']['schedule'] = [
			'from' => $usersSchedule['usersScheduleFrom'],
			'to' => $usersSchedule['usersScheduleTo'],
			'hours' => (mystrtotime($usersSchedule['usersScheduleTo']) - mystrtotime($usersSchedule['usersScheduleFrom'])) / 3600,
			'isDuty' => ($usersSchedule['usersScheduleDuty'] == 1),
		];
	}
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	$fingerLogs = query2array(mysqlQuery("SELECT `fingerLogUser`,`fingerLogTime`,DATE(`fingerLogTime`) as `fingerLogDate` FROM `fingerLog` WHERE `fingerLogTime`>='$monthStart 00:00:00' AND `fingerLogTime`<='$monthEnd 23:59:59'"));
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	usort($fingerLogs, function ($a, $b) {
		return $a['fingerLogTime'] <=> $b['fingerLogTime'];
	});
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	foreach ($fingerLogs as $fingerLog) {
		$personnelPool[$fingerLog['fingerLogUser']][$fingerLog['fingerLogDate']]['payments']['1']['fingerlog']['logs'][] = $fingerLog['fingerLogTime'];
		$seconds = (mystrtotime($personnelPool[$fingerLog['fingerLogUser']][$fingerLog['fingerLogDate']]['payments']['1']['fingerlog']['logs'][count($personnelPool[$fingerLog['fingerLogUser']][$fingerLog['fingerLogDate']]['payments']['1']['fingerlog']['logs']) - 1]) - mystrtotime($personnelPool[$fingerLog['fingerLogUser']][$fingerLog['fingerLogDate']]['payments']['1']['fingerlog']['logs'][0]));
		$personnelPool[$fingerLog['fingerLogUser']][$fingerLog['fingerLogDate']]['payments']['1']['fingerlog']['hours'] = abs(ceil(($seconds - 15 * 60) / 3600)); //Округляем до ближайшего целого вверх после 15 минут работы.
	}
	foreach ($personnelPool as $iduser => $user) {
		foreach ($user as $date => $data) {
			$personnelPool[$iduser][$date]['payments']['1']['marketingLimit'] = getReward($rewardsPool, $iduser, 32, $date);
			$personnelPool[$iduser][$date]['payments']['1']['isDutyCounts'] = (1 == getReward($rewardsPool, $iduser, 16, $date));
			$personnelPool[$iduser][$date]['payments']['1']['scheduleReward'] = getReward($rewardsPool, $iduser, 1, $date);
			$personnelPool[$iduser][$date]['payments']['1']['hoursQtyReward'] = getReward($rewardsPool, $iduser, 10, $date);

			//Проверяем
			$isDutyCounts = ($personnelPool[$iduser][$date]['payments']['1']['isDutyCounts'] ?? false);
			$isDuty = ($personnelPool[$iduser][$date]['payments']['1']['schedule']['isDuty'] ?? false);

			//ведём просчёт только в том случае, если не важно дежурная или нет, или если важно и она дежурная.
			if (($personnelPool[$iduser][$date]['payments']['1']['hoursQtyReward'] ?? 0) > 0 && (!$isDutyCounts || ($isDutyCounts && $isDuty))) {//В том случае если надо учитывать дежурные смены
				$personnelPool[$iduser][$date]['payments']['1']['scheduleHours'] = ($personnelPool[$iduser][$date]['payments']['1']['schedule']['hours'] ?? 0);
				$personnelPool[$iduser][$date]['payments']['1']['fingerlogHours'] = ($personnelPool[$iduser][$date]['payments']['1']['fingerlog']['hours'] ?? 0);
				$personnelPool[$iduser][$date]['payments']['1']['toPayHours'] = min(
						$personnelPool[$iduser][$date]['payments']['1']['scheduleHours'],
						$personnelPool[$iduser][$date]['payments']['1']['fingerlogHours'],
						$personnelPool[$iduser][$date]['payments']['1']['hoursQtyReward']
				);

				$personnelPool[$iduser][$date]['payments']['1']['total'] = $personnelPool[$iduser][$date]['payments']['1']['scheduleReward'] * $personnelPool[$iduser][$date]['payments']['1']['toPayHours'] / $personnelPool[$iduser][$date]['payments']['1']['hoursQtyReward'];
				if ($personnelPool[$iduser][$date]['payments']['1']['marketingLimit'] > 0) {//если установлен коэффициент, значит это маркетинг, тут надо поработать.
					//Проверить на соответствие требованиям, и если они не выполняются - то обнулить значение смены. Иначе - ничего не делать.
				}
			}
		}
	}
}//Расчёт по сменам




$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
//printr($fingerPool);

$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
foreach ($users as &$user) {
	$user['usersGroup'] = $usersGroups[$user['usersGroup']] ?? null;
}
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];

$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
if (count($servicesApplied)) {

	$services = query2array(mysqlQuery("SELECT "
					. " `idservices`,"
					. " `servicesName`"
					. " FROM `services` WHERE `idservices` IN (" . implode(',', array_filter(array_unique(array_column($servicesApplied, 'servicesAppliedService')))) . ")"), 'idservices');
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	$clients = query2array(mysqlQuery("SELECT "
					. "`idclients`, `clientsLName`, `clientsFName`, `clientsMName`,`clientsOldSince`"
					. " FROM `clients` WHERE `idclients` IN (" . implode(',', array_unique(array_column($servicesApplied, 'servicesAppliedClient'))) . ")"), 'idclients');
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	$daleteReasons = query2array(mysqlQuery("SELECT * FROM `daleteReasons`"), 'iddaleteReasons');
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	$clientsVisits = query2array(mysqlQuery("SELECT * FROM `clientsVisits` WHERE `clientsVisitsDate`>='$monthStart' AND `clientsVisitsDate`<='$monthEnd'"));
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
}





//printr(
//		[
//			'$users' => count($users),
//			'$servicesApplied' => count($servicesApplied),
//			'$services' => count($services),
//			'$clients' => count($clients),
//			'$daleteReasons' => count($daleteReasons),
//			'$clientsVisits' => count($clientsVisits),
//		]
//);
$n = 0;
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
foreach ($servicesApplied as $serviceApplied) {
	if ($n++ > 50) {
		//break;
	}
	$serviceApplied['servicesAppliedService'] = $services[$serviceApplied['servicesAppliedService']] ?? null;
	$serviceApplied['servicesAppliedClient'] = $clients[$serviceApplied['servicesAppliedClient']] ?? null;

//допы
	if (1) {
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
			$serviceApplied['dopsRewardPerSA'] = 0;
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
					$serviceApplied['dopsRewardPerSA'] = $matrix['w']['min'];
				} else {
					$serviceApplied['dopsRewardPerSA'] = $matrix['w']['max'];
				}
			} else {
				if ($serviceApplied['servicesAppliedPrice'] <= $matrix['p']['min']) {
					$serviceApplied['dopsRewardPerSA'] = $matrix['w']['min'];
				} elseif ($serviceApplied['servicesAppliedPrice'] >= $matrix['p']['max']) {
					$serviceApplied['dopsRewardPerSA'] = $matrix['w']['max'];
				} else {
					$serviceApplied['dopsRewardPerSA'] = $matrix['w']['min'] + ($serviceApplied['servicesAppliedPrice'] - $matrix['p']['min']) * $matrix['slope'];
				}
			}
		}
		$serviceApplied['dopsRewardTotal'] = $serviceApplied['dopsRewardPerSA'] * $serviceApplied['servicesAppliedQty'];
//-----dops
	}





	if (!($clientsPool[$serviceApplied['servicesAppliedDate']]['clients'][$serviceApplied['servicesAppliedClient']['idclients']] ?? false)) {
		$clientsPool[$serviceApplied['servicesAppliedDate']]['clients'][$serviceApplied['servicesAppliedClient']['idclients']] = $clients[$serviceApplied['servicesAppliedClient']['idclients']];
	}

	$serviceApplied['daleteReasonsName'] = $daleteReasons[$serviceApplied['servicesAppliedDeleteReason']]['daleteReasonsName'] ?? null;
	$serviceApplied['isFree'] = ($serviceApplied['servicesAppliedContract'] == null && ($serviceApplied['servicesAppliedPrice'] == null || round($serviceApplied['servicesAppliedPrice']) == 0));

	$serviceApplied['servicesAppliedBy'] = $users[$serviceApplied['servicesAppliedBy']] ?? null;
	$serviceApplied['servicesAppliedPersonal'] = $users[$serviceApplied['servicesAppliedPersonal']] ?? null;
	$serviceApplied['servicesAppliedDeletedBy'] = $users[$serviceApplied['servicesAppliedDeletedBy']] ?? null;

	$serviceAppliedType = (($serviceApplied['servicesAppliedService']['idservices'] ?? false) == 362) ? 'diagnostics' : 'procedures';
	$clientsPool[$serviceApplied['servicesAppliedDate']]['clients'][$serviceApplied['servicesAppliedClient']['idclients']]['servicesApplied'][$serviceAppliedType][] = $serviceApplied;
	$clientsPool[$serviceApplied['servicesAppliedDate']]['clients'][$serviceApplied['servicesAppliedClient']['idclients']]['visit'] = null;
//// $personnelPool
	if ($serviceApplied['servicesAppliedPersonal']['idusers'] ?? false) {
		$personnelPool[$serviceApplied['servicesAppliedPersonal']['idusers']][$serviceApplied['servicesAppliedDate']]['payments']['dops']['procedures'][] = $serviceApplied;
	}
}

///Сервис маркетинг
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
foreach ($clientsPool as $date => $clientsArray) {
	$clients = $clientsArray['clients'];
	foreach ($clients as $idclient => $client) {//сначала делаем процедуры, потом поверх диагностики.
		//$personnelPool[idusers][$date]['payments']['diagnostics']
	}
}


unset($servicesApplied);
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
foreach ($clientsVisits as $clientsVisit) {
	if ($clientsPool[$clientsVisit['clientsVisitsDate']]['clients'][$clientsVisit['clientsVisitsClient']] ?? false) {
		$clientsPool[$clientsVisit['clientsVisitsDate']]['clients'][$clientsVisit['clientsVisitsClient']]['visit'] = $clientsVisit['clientsVisitsTime'];
	}
}



$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
foreach ($scores as $score) {
	$clientsPool[$score['scoreDate']]['clients'][$score['scoreClient']]['score'] = $score;
}


$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
/////////////////////////////////////////////// распределение операторов
//    Смотрим, есть ли диагностики
foreach ($clientsPool as $date => &$clientsPoolBydate) {
	foreach ($clientsPoolBydate['clients'] as $idclients => &$clientsPoolClient) {
//		usort($clientsPoolClient['servicesApplied']['all'], function ($a, $b) {
//			return $a['servicesAppliedAt'] <=> $b['servicesAppliedAt'];
//		});
		if ($clientsPoolClient['servicesApplied']['diagnostics'] ?? false) {
			usort($clientsPoolClient['servicesApplied']['diagnostics'], function ($a, $b) {
				return $a['servicesAppliedAt'] <=> $b['servicesAppliedAt'];
			});
		}
		if ($clientsPoolClient['servicesApplied']['procedures'] ?? false) {
			usort($clientsPoolClient['servicesApplied']['procedures'], function ($a, $b) {
				return $a['servicesAppliedAt'] <=> $b['servicesAppliedAt'];
			});
		}
		$allprocedures = array_merge(($clientsPoolClient['servicesApplied']['diagnostics'] ?? []), ($clientsPoolClient['servicesApplied']['procedures'] ?? []));

		usort($allprocedures, function ($a, $b) {
			return $a['servicesAppliedAt'] <=> $b['servicesAppliedAt'];
		});

		$clientsPoolClient['operator'] = $allprocedures[0]['servicesAppliedBy'] ?? null;
	}
}
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
//printr($clientsPool);
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];

if (1) {

	foreach ($f_salesPool as $f_salePool) {
//Кредитные специалисты
		if (in_array($f_salePool['f_salesType'], [1, 2]) &&
				mystrtotime($f_salePool['f_salesDate']) >= mystrtotime($monthStart) &&
				mystrtotime($f_salePool['f_salesDate']) <= mystrtotime($monthEnd)
		) {//Переносим в кредитных только продажи с AE>0 и в рамках текущего месяца.
//			printr($f_salePool);
			$personnelPool[$f_salePool['f_salesCreditManager']][$f_salePool['f_salesDate']]['сreditManager'][] = $f_salePool;
			$personnelPool[$f_salePool['f_salesCreditManager']][$f_salePool['f_salesDate']]['payments']['3'] = [
				'count' => count($personnelPool[$f_salePool['f_salesCreditManager']][$f_salePool['f_salesDate']]['сreditManager']),
				'reward' => getReward($rewardsPool, $f_salePool['f_salesCreditManager'], 3, $f_salePool['f_salesDate']),
				'total' => getReward($rewardsPool, $f_salePool['f_salesCreditManager'], 3, $f_salePool['f_salesDate']) * count($personnelPool[$f_salePool['f_salesCreditManager']][$f_salePool['f_salesDate']]['сreditManager']),
			];
		}
// /Кредитные специалисты
//* Процент от продажи */
		if (($participants = count($f_salePool['participants'] ?? []))) {
			foreach ($f_salePool['participants'] as $user) {
				$personnelPool[$user['idusers']][($f_salePool['payments'][0]['f_paymentsDate'] ?? $f_salePool['f_salesDate'])]['payments']['11']['f_sales'][$f_salePool['idf_sales']] = $f_salePool;
				if ($f_salePool['payed'] && $f_salePool['intime']) {
					$personnelPool[$user['idusers']][$f_salePool['payments'][0]['f_paymentsDate']]['payments']['11']['reward'] = ($personnelPool[$user['idusers']][$f_salePool['payments'][0]['f_paymentsDate']]['payments']['11']['reward'] ?? getReward($rewardsPool, $user['idusers'], 11, $f_salePool['f_salesDate']) / 100); //Записываем вознаграждение только если его нет, иначе используем то, что уже записано.
//проверяем на оплаченность и дату последнего платежа
					$personnelPool[$user['idusers']][$f_salePool['payments'][0]['f_paymentsDate']]['payments']['11']['total'] = //
							($personnelPool[$user['idusers']][$f_salePool['payments'][0]['f_paymentsDate']]['payments']['11']['total'] ?? 0) +
							$personnelPool[$user['idusers']][$f_salePool['payments'][0]['f_paymentsDate']]['payments']['11']['reward'] *
							array_sum(array_column($f_salePool['payments'], 'f_paymentsAmount')) / count($f_salePool['participants']);
				}
			}
		}


////* Процент от продажи */
	}
}
$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];

foreach ($personnelPool as $iduser => $user) {
	if (isset($_GET['employee']) && $_GET['employee'] != $iduser) {
		continue;
	}


	for ($time = mystrtotime($monthStart); $time <= mystrtotime($monthEnd); $time += 60 * 60 * 24) {//Пересчитываем каждый день
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['countAllSales'] = getReward($rewardsPool, $iduser, 27, mydates("Y-m-d", $time)) == 1; //считать координаторам все продажи
		foreach ($coordinatorsSalesFlat as $coordinatorsSaleFlat) {
			if (
					(
					($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['countAllSales'] ?? false) || $coordinatorsSaleFlat['f_salesToCoordCoord'] == $iduser) &&
					mydates('Y-m-d', $time) == $coordinatorsSaleFlat['f_salesDate']) {
				//запихиваем в координатора продажу которая подходит ему.
				$personnelPool[$iduser][$coordinatorsSaleFlat['f_salesDate']]['payments']['coords']['sales'][$coordinatorsSaleFlat['idf_sales']] = $coordinatorsSaleFlat;
			}
		}

		foreach ($coordinatorsCanceledSalesFlat as $coordinatorsCanceledSaleFlat) {

			if (
					(
					($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['countAllSales'] ?? false) || $coordinatorsCanceledSaleFlat['f_salesToCoordCoord'] == $iduser) &&
					mydates('Y-m-d', $time) == $coordinatorsCanceledSaleFlat['f_salesCancellationDate']) {
				//запихиваем в координатора продажу которая подходит ему.
				$personnelPool[$iduser][$coordinatorsCanceledSaleFlat['f_salesCancellationDate']]['payments']['coords']['salesCanceled'][$coordinatorsCanceledSaleFlat['idf_sales']] = $coordinatorsCanceledSaleFlat;
			}
		}



		if ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['sales'] ?? false) {
			$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['todaySalesSumm'] = //
					array_sum(array_column(($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['sales'] ?? []), 'f_salesSumm')) -
					array_sum(array_column(($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['salesCanceled'] ?? []), 'f_salesSumm'))
			;
		}
	}
}



$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
foreach ($personnelPool as $iduser => $user) {
	if (isset($_GET['employee']) && $_GET['employee'] != $iduser) {
		continue;
	}


//$totalCoordsSalesSumm = array_sum(array_column(, $serviceApplied))
	$totalrecrutcalls = array_sum(array_column(array_column(array_column($user, 'payments'), 'recrut'), 'qty'));

	for ($time = mystrtotime($monthStart); $time <= mystrtotime($monthEnd); $time += 60 * 60 * 24) {//Пересчитываем каждый день
		$mymarketingClients = ($clientsPool[mydates("Y-m-d", $time)] ?? false) ? array_filter($clientsPool[mydates("Y-m-d", $time)]['clients'], function ($client) use ($iduser) {
					return ($client['operator']['idusers'] ?? false) == $iduser;
				}) : [];
		foreach ($mymarketingClients as $idclient => $client) {
			$mymarketingClients[$idclient]['clientState'] = getClientSatate($idclient, mydates("Y-m-d", $time));
		}
//		printr($mymarketingClients);
//		die();
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['monthReward'] = //
				LT($LT, $iduser, array_sum(array_column(array_column(array_column($personnelPool[$iduser], 'payments'), 'coords'), 'todaySalesSumm')), mydates("Y-m-d", $time)) / 100;
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['total'] = ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['todaySalesSumm'] ?? 0) * $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['coords']['monthReward'];
		//
		if ($totalrecrutcalls) {
			$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['myTotalCalls'] = $totalrecrutcalls;
			$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['rewardPerCall'] = getRecruitingWage($recruitingValues, mydates('Y-m-d', $time), $totalrecrutcalls);
			$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['total'] = $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['rewardPerCall'] * ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['qty'] ?? 0);
		}
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['interviewReward'] = getReward($rewardsPool, $iduser, 26, mydates("Y-m-d", $time)); //
		if ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['interviewReward']) {
			$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['total'] = ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['total'] ?? 0) + ( $recruitingDirector[mydates('Y-m-d', $time)] ?? 0) * $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['recrut']['interviewReward'];
		}
//
//
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['6']['total'] = getReward($rewardsPool, $iduser, 6, mydates("Y-m-d", $time)) / mydates('t', $time); //оклад
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['7']['total'] = getReward($rewardsPool, $iduser, 7, mydates("Y-m-d", $time)) / mydates('t', $time); //официальный оклад
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['9']['reward'] = getReward($rewardsPool, $iduser, 9, mydates("Y-m-d", $time)); //почасовая
		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['1']['hoursQtyReward'] = getReward($rewardsPool, $iduser, 10, mydates("Y-m-d", $time)); //оплата за смену

		if (round($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['9']['reward'] ?? 0) > 0) {//Если установлена почасовая оплата
			$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['9']['hours'] = $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['1']['toPayHours'] ?? 0;
			$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['9']['total'] = $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['9']['reward'] * min(($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['1']['schedule']['hours'] ?? 0), ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['1']['fingerlog']['hours'] ?? 0));
		}

//		$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['9']['hours'] = getReward($rewardsPool, $iduser, 9, mydates("Y-m-d", $time));
		$isFreeCounts = $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['isFreeCounts'] = (1 == getReward($rewardsPool, $iduser, 15, mydates("Y-m-d", $time)));
		$isDopsCounts = $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['isDopsCounts'] = !(getReward($rewardsPool, $iduser, 13, mydates("Y-m-d", $time)));
		$isDutyCounts = $personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['isDutyCounts'] = (1 == getReward($rewardsPool, $iduser, 16, mydates("Y-m-d", $time)));
		$isDuty = ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['1']['schedule']['isDuty'] ?? false);

		if (
				count($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['procedures'] ?? []) && //Если есть процедуры
				$isDopsCounts //И если нужно считать допы
		) {

			foreach ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['procedures'] as $procedure) {
				if (
						$procedure['servicesAppliedFineshed'] && !$procedure['servicesAppliedDeleted']
				) {
					if (
							(($isDutyCounts && !$isDuty) || !$isDutyCounts) &&
							$isDopsCounts &&
							(!$procedure['isFree'] || ($procedure['isFree'] && $isFreeCounts))
					) {
						$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['total'] = ($personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['total'] ?? 0) + $procedure['dopsRewardTotal'];
					}
				}
//				$procedure['dopsRewardTotal'];
//				"dopsRewardPerSA": 0,
//				"servicesAppliedQty": 1,
//				$personnelPool[$iduser][mydates('Y-m-d', $time)]['payments']['dops']['total'] = 0;
			}
		}
	}
}


$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
?>
<div style="color: black;">
	<?
	printr($personnelPool[$_GET['employee']] ?? []); //['2021-04-01']
//	printr($clientsPool['2021-04-01']); //
//	printr($schedulePool); //
//	printr($f_salesPool); //
	$_MEMORY[__LINE__][] = [memory_get_usage(), microtime(1) - $start];
	?>
</div>