<?php

function getPaymentsValues($userPaymentsValues, $userPaymentsValuesDate) {
  if (!$userPaymentsValues || !is_array($userPaymentsValues)) {
	 return null;
  }


  usort($userPaymentsValues, function ($a, $b) {
	 if ($a['iduserPaymentsTypes'] <=> $b['iduserPaymentsTypes']) {
		return intval($a['iduserPaymentsTypes']) <=> intval($b['iduserPaymentsTypes']);
	 }
	 if ($a['userPaymentsValuesDate'] <=> $b['userPaymentsValuesDate']) {
		return $b['userPaymentsValuesDate'] <=> $a['userPaymentsValuesDate'];
	 }
	 if ($a['iduserPaymentsValues'] <=> $b['iduserPaymentsValues']) {
		return $b['iduserPaymentsValues'] <=> $a['iduserPaymentsValues'];
	 }
  });

  $typePayments = (array_filter($userPaymentsValues, function ($userPaymentsValue) use ($userPaymentsValuesDate) {
				return mystrtotime($userPaymentsValue['userPaymentsValuesDate']) <= mystrtotime($userPaymentsValuesDate);
			 }));

  $out = [];
  foreach ($typePayments as $typePayment) {
	 if (!($out[$typePayment['userPaymentsValuesType']] ?? false)) {
		$out[$typePayment['userPaymentsValuesType']] = $typePayment;
	 }
  }
  foreach ($out as $outIndex => $outValue) {//удаляем нулевые параметры зп, чтобы они не маячили лишний раз
	 if (!$outValue['userPaymentsValuesValue']) {
		unset($out[$outIndex]);
	 }
  }

  if (count($out)) {
	 return $out;
  } else {
	 return null;
  }
}

function getInviter2($servicesApplied) {// Возвращает идентификатор сотрудника сделавшего последнюю запись.
  //$params = ['idclients' => null, 'date' => null]
//			$servicesApplied = array_filter($servicesAppliedArray, function ($serviceApplied) {
//				return in_array($serviceApplied['usersGroup'], ['13']);
//			});
  usort($servicesApplied, function ($a, $b) {
	 return $b['idservicesApplied'] <=> $a['idservicesApplied'];
  });
  if (!count($servicesApplied ?? [])) {
	 return null;
  }
//		printr($servicesApplied);
  return $servicesApplied[0]['servicesAppliedBy'];
}

function getInviter3($servicesApplied) {// Возвращает идентификатор сотрудника сделавшего последнюю запись.
  if (count($servicesAppliedNotDeleted = array_filter(($servicesApplied ?? []), function ($serviceApplied) {
				return !$serviceApplied['servicesAppliedDeleted'];
			 }))) {
	 $out = $servicesAppliedNotDeleted;
  } else {
	 $out = $servicesApplied;
  }

  usort($out, function ($a, $b) {
	 return $b['idservicesApplied'] <=> $a['idservicesApplied'];
  });
  if (!count($out ?? [])) {
	 return null;
  }
  return $out[0]['servicesAppliedBy'];
}

function getPayments($iduser, $dates) {
  global $_USER, $visitsServicesApplied, $rewards56;
  $payments = [];

//	printr($user);
//	printr($dates);

  $userPaymentsValues = query2array(mysqlQuery("SELECT *"
						. " FROM `userPaymentsValues`"
						. " LEFT JOIN `userPaymentsTypes` ON (`iduserPaymentsTypes` = `userPaymentsValuesType`)"
						. " WHERE `userPaymentsValuesUser`=" . mres($iduser) . ""
						. " AND `userPaymentsValuesDate` <='" . mres(max($dates)) . "'"
						. " AND `userPaymentsTypesType` <> 'grid'"
						. " AND (isnull(`userPaymentsTypesDeleted`) OR DATE(`userPaymentsTypesDeleted`)>='" . mres(min($dates)) . "')"
						. " ORDER BY `userPaymentsValuesDate` DESC, `iduserPaymentsValues` DESC"));

  $LTs = query2array(mysqlQuery("SELECT * FROM `LT` LEFT JOIN `userPaymentsTypes` ON (`iduserPaymentsTypes` = `LTid`)"
						. " WHERE `LTuser` = '" . mres($iduser) . "'"
						. " AND `LTdate`<='" . mres(max($dates)) . "'"
						. " AND (isnull(`userPaymentsTypesDeleted`) OR DATE(`userPaymentsTypesDeleted`)>='" . mres(min($dates)) . "')"
						. ""));
//	print '$LTs';
//	printr($LTs);
  $ltgridsAll = [];
  foreach ($LTs as $LTdataRow) {
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsTypesType'] = 'ltgrid';
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsValuesDate'] = $LTdataRow['LTdate'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['iduserPaymentsValues'] = $LTdataRow['idLT'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsValuesType'] = $LTdataRow['LTid'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsValuesUser'] = $LTdataRow['LTuser'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsTypesName'] = $LTdataRow['userPaymentsTypesName'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsValuesSetBy'] = $LTdataRow['LTsetBy'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsValuesSetTime'] = $LTdataRow['LTset'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsTypesSort'] = $LTdataRow['userPaymentsTypesSort'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsTypesType'] = $LTdataRow['userPaymentsTypesType'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['userPaymentsTypesDeleted'] = $LTdataRow['userPaymentsTypesDeleted'];
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['type'] = $LTdataRow['LTtype'] ?? '-';
	 $ltgridsAll[$LTdataRow['LTid']][$LTdataRow['LTdate']]['data'][] = [
		  'from' => $LTdataRow['LTfrom'],
		  'to' => $LTdataRow['LTto'],
		  'result' => $LTdataRow['LTresult'],
	 ];
  }
//	printr($ltgridsAll);
  foreach ($ltgridsAll as $ltgrids) {
	 foreach ($ltgrids as $date => $ltgrid) {
//			print 'LTGRID';
//			printr($ltgrid);
		if (count($ltgrid['data'] ?? [])) {
		  $userPaymentsValues[] = [
				"iduserPaymentsValues" => $ltgrid['iduserPaymentsValues'],
				"userPaymentsValuesType" => $ltgrid['userPaymentsValuesType'],
				"userPaymentsValuesUser" => $ltgrid['userPaymentsValuesUser'],
				"userPaymentsValuesDate" => $ltgrid['userPaymentsValuesDate'],
				"userPaymentsValuesValue" => [$date => $ltgrid], //$ltgrid['userPaymentsValuesValue']
				"userPaymentsValuesSetBy" => $ltgrid['userPaymentsValuesSetBy'],
				"userPaymentsValuesSetTime" => $ltgrid['userPaymentsValuesSetTime'],
				"iduserPaymentsTypes" => $ltgrid['userPaymentsValuesType'],
				"userPaymentsTypesName" => $ltgrid['userPaymentsTypesName'],
				"userPaymentsTypesSort" => $ltgrid['userPaymentsTypesSort'],
				"userPaymentsTypesType" => $ltgrid['userPaymentsTypesType'],
				"userPaymentsTypesDeleted" => $ltgrid['userPaymentsTypesDeleted']
		  ];
		}
	 }
  }

//	print'$userPaymentsValues';
//	printr($userPaymentsValues);
  $paymentTypes = array_unique(array_column($userPaymentsValues, 'userPaymentsValuesType'));

//	printr($paymentTypes);
//			idclients
//        "": "Тарасова",
//        "": "Людмила",
//        "": "Николаевна",
//    printr($paymentTypes);

  if (
			 in_array(56, $paymentTypes) ||
			 in_array(43, $paymentTypes) ||
//			 in_array(44, $paymentTypes) ||
			 in_array(45, $paymentTypes) ||
			 in_array(46, $paymentTypes)
  ) {


	 $rewards56 = $rewards56 ?? query2array(mysqlQuery("SELECT * FROM `clientsSourcesRewards` WHERE `clientsSourcesRewardsDate`<='" . mres(max($dates)) . "'"));
	 usort($rewards56, function ($a, $b) {
		if ($b['clientsSourcesRewardsDate'] <=> $a['clientsSourcesRewardsDate']) {
		  return $b['clientsSourcesRewardsDate'] <=> $a['clientsSourcesRewardsDate'];
		}

		if ($a['idclientsSourcesRewards'] <=> $b['idclientsSourcesRewards']) {
		  return $a['idclientsSourcesRewards'] <=> $b['idclientsSourcesRewards'];
		}
	 });
	 if ($_USER['id'] == 176) {
//			printr($rewards56);
	 }

	 //Получить всех клиентов на дату(ы)
	 $query = "SELECT "
//						. " *,"
				. " `idclients`,"
				. " `clientsLName`,"
				. " `clientsFName`,"
				. " `clientsMName`,"
				. " `clientsSource`,"
				. " `clientsSourcesLabel`,"
				. " `clientsVisitsDate`,"
				. " `idservicesApplied`,"
				. " `servicesAppliedDeleted`,"
				. " `scoreMarket`,"
				. " `clientsOldSince`,"
				. " `scoreDescription`,"
				. " ifnull((SELECT sum(`f_salesSumm`) FROM `f_sales` WHERE `f_salesClient`=`CV`.`clientsVisitsClient` AND `f_salesDate`<`CV`.`clientsVisitsDate`),0) as `salesSumm`,"
				. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient`=`CV`.`clientsVisitsClient` AND `f_salesDate`<`CV`.`clientsVisitsDate` AND `f_salesType` IN(1,2)) as `salesQty`,"
				. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient`=`CV`.`clientsVisitsClient` AND `f_salesDate`=`CV`.`clientsVisitsDate` AND `f_salesType` IN(1,2) AND `f_salesSumm`>=35000 AND (SELECT 
            SUM(`paymentsAmount`)
        FROM
            (SELECT 
                SUM(`f_creditsSumm`) AS `paymentsAmount`
            FROM
                `f_credits`
            WHERE
                `f_creditsSalesID` = `idf_sales`  AND date(`f_creditsAdded`) = date(`f_salesDate`) UNION ALL SELECT 
                SUM(`f_paymentsAmount`) AS `paymentsAmount`
            FROM
                `f_payments`
            WHERE
                `f_paymentsSalesID` = `idf_sales` AND  date(`f_paymentsDate`) = date(`f_salesDate`) ) AS `paymentsAll`) >=35000) as `todaysSalesQty`,"
				. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient`=`CV`.`clientsVisitsClient` AND `f_salesDate`<`CV`.`clientsVisitsDate` AND `f_salesType` NOT IN(1,2)) as `not_salesQty`,"
				. " TIMESTAMPDIFF(MONTH, ("
//                . "SELECT MAX(`clientsVisitsDate`) FROM `clientsVisits` as `CVL`  WHERE `CVL`.`clientsVisitsDate`<`CV`.`clientsVisitsDate` AND `CVL`.`clientsVisitsClient`=`CV`.`clientsVisitsClient`"
				. " SELECT `scoreDate` FROM `score` WHERE `idscore` = (SELECT `idscore` FROM `score` "
				. " WHERE `scoreDate`<`CV`.`clientsVisitsDate` ORDER BY `idscore` DESC LIMIT 1)"
				. " AND `scoreClient`=`CV`.`clientsVisitsClient`"
				. " AND `scoreMarket` = 1"
				. ""
				. "), `clientsVisitsDate`) as `lastVizitMonthes`,"
				. " (SELECT COUNT(1) FROM `clientsVisits` as `PV` LEFT JOIN `score` ON (`idscore` = (SELECT `idscore` FROM `score` WHERE `scoreDate`=`PV`.`clientsVisitsDate` AND `scoreClient`=`idclients` ORDER BY `idscore` DESC LIMIT 1)) WHERE "
				. " `PV`.`clientsVisitsClient`=`idclients` AND (ISNULL(`scoreMarket`) OR `scoreMarket` <> 0) AND `PV`.`clientsVisitsDate`>DATE_SUB(`CV`.`clientsVisitsDate`, INTERVAL 3 MONTH) AND `PV`.`clientsVisitsDate`<`CV`.`clientsVisitsDate`) as `previsit`,"
				. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients` AND `f_salesDate`< `CV`.`clientsVisitsDate`) as `sales`, "
				. " `servicesAppliedBy`"
				. " FROM `clientsVisits` AS `CV`"
				. " LEFT JOIN `clients` ON (`idclients`=`clientsVisitsClient`)"
				. " LEFT JOIN `clientsSources` ON (`idclientsSources`=`clientsSource`)"
				. " LEFT JOIN `score` ON (`idscore`= (SELECT `idscore` FROM `score` WHERE `scoreClient` = `idclients` AND `scoreDate` = `clientsVisitsDate` ORDER BY `idscore` DESC LIMIT 1))"
				. " LEFT JOIN `servicesApplied` ON (`servicesAppliedClient` = `clientsVisitsClient` AND `servicesAppliedDate`=`clientsVisitsDate`)"
				. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
				. " WHERE `clientsVisitsDate`>='" . min($dates) . "'"
				. " AND `clientsVisitsDate`<='" . max($dates) . "'"
				. " AND `usersGroup`='12'" //Маркетинг
				. " AND NOT isnull(`idservicesApplied`)"
				. " AND NOT isnull(`servicesAppliedBy`)"
//						. " AND `idclientsVisits` = (SELECT MIN(`idclientsVisits`) FROM `clientsVisits` WHERE `clientsVisitsClient`=`idclients`)"
				. " HAVING `previsit`=0"
				. " ORDER BY `idservicesApplied`"
				. "";
//        printr($query);
	 $visitsServicesApplied = $visitsServicesApplied ?? query2array(mysqlQuery($query));
	 $clientsVisits = [];

	 foreach ($visitsServicesApplied as $visit) {
//			if (!($clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['idclients'] ?? false)) {
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['idclients'] = $visit['idclients'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['clientsLName'] = $visit['clientsLName'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['clientsFName'] = $visit['clientsFName'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['clientsMName'] = $visit['clientsMName'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['clientsSource'] = $visit['clientsSource'];

		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['lastVizitMonthes'] = $visit['lastVizitMonthes'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['salesSumm'] = $visit['salesSumm'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['todaysSalesQty'] = $visit['todaysSalesQty'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['salesQty'] = $visit['salesQty'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['not_salesQty'] = $visit['not_salesQty'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['clientsOldSince'] = $visit['clientsOldSince'];

		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['scoreDescription'] = $visit['scoreDescription'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['scoreMarket'] = $visit['scoreMarket'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['clientsSourcesLabel'] = $visit['clientsSourcesLabel'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['clientsVisitsDate'] = $visit['clientsVisitsDate'];
		$clientsVisits[$visit['clientsVisitsDate']][$visit['idclients']]['servicesApplied'][] = $visit;
//			}
	 }
//		printr($clientsVisits);

	 foreach ($clientsVisits as $date => $clients) {
//			printr($clients);
		foreach ($clients as $client) {
		  if (getInviter3($client['servicesApplied']) == $iduser) {

			 if ($client['salesQty'] == 0 && $client['todaysSalesQty'] > 0) {
				$payments['dates'][$date]['50']['data'][] = $client;
			 }

			 $payments['dates'][$date]['56']['data'][] = $client;

			 if (in_array($client['clientsSource'], [13, 31, 34, 35, 36, 37]) && $client['scoreMarket'] && $client['salesSumm'] == 0 && ($client['lastVizitMonthes'] === null || $client['lastVizitMonthes'] >= 6)) {
				$payments['dates'][$date]['43']['data'][] = $client;
			 }



			 if (($client['salesSumm'] > 0 && $client['salesSumm'] < 5000 && $client['lastVizitMonthes'] >= 6) && $client['scoreMarket']) {
				$payments['dates'][$date]['46']['data'][] = $client;
			 }



			 if (
						!(($client['salesSumm'] > 0 && $client['salesSumm'] < 5000 && $client['lastVizitMonthes'] >= 6) && $client['scoreMarket']) && //46
						!(in_array($client['clientsSource'], [13, 31, 34, 35, 36, 37]) && $client['scoreMarket'] && $client['salesSumm'] == 0 && ($client['lastVizitMonthes'] === null || $client['lastVizitMonthes'] >= 6))//43
			 ) {
				$payments['dates'][$date]['45']['data'][] = $client;
			 }



//			 if (
//						($client['clientsOldSince'] != null && $client['clientsOldSince'] < $date) &&
//						$client['lastVizitMonthes'] >= 6 &&
//						$client['salesQty'] == 0) {
//				$payments['dates'][$date]['46']['data'][] = $client;
//			 }
//					printr($payments['dates'][$date]['43']['data']);
		  }
		}
	 }
  }
  $intersect = array_intersect([11, 39, 40, 49, 51, 52, 53, 57, 58, 60, 63, 64], $paymentTypes);
  if ($intersect) {//Если есть процент от продаж, надо бы получить все платежи
//	if (in_array(39, $paymentTypes) || in_array(51, $paymentTypes) || (in_array(11, $paymentTypes) && count(array_filter($userPaymentsValues, function ($rule) {
//						return $rule['userPaymentsValuesType'] == 11 && is_array($rule['userPaymentsValuesValue']);
//					})))) {//Если есть процент от продаж, надо бы получить все платежи
//			
//		printr($intersect);
	 $f_paymentsSQL = "SELECT"
//						. " *,"
				. " `idclients`,"
				. " `payment`,"
				//prepayments  
				. " ifnull("
				. "(SELECT SUM(`summ`) FROM ("
				. " SELECT sum(`f_paymentsAmount`) as `summ` FROM `f_payments` as `sP` WHERE `sP`.`f_paymentsSalesID`= `idf_sales` AND `sP`.`f_paymentsDate`<`paymentDate`"
				. " UNION ALL "
				. " SELECT sum(`f_creditsSumm`) as `summ` FROM `f_credits` as `sC` WHERE `sC`.`f_creditsSalesID`= `idf_sales` AND `sC`.`f_creditsAdded`<`paymentDate`"
				. ") AS `prepayments`) "
				. ",0)"
				. " as `prePaymentsSumm`,"
				//
//                . " `paymentType`,"
				. " `paymentDate`,"
//                . " `paymentTime`,"
				. " `f_salesDate`,"
				. " `idf_sales`,"
				. " `f_salesType`,"
				. " `f_salesSumm`, "
				. " `clientsLName`, "
				. " `clientsFName`, "
				. " `clientsMName`,"
//                . " `idf_payments`,"
				. " `clientsOldSince`,"
				. " if((SELECT COUNT(1) FROM `f_salesRoles` WHERE `f_salesRolesSale`=`idf_sales` AND `f_salesRolesUser` = '$iduser'),1,0) as `mySale`, "
				. " (SELECT `f_salesRolesRole` FROM `f_salesRoles` WHERE `f_salesRolesSale`=`idf_sales` AND `f_salesRolesUser` = '$iduser') as `myRole`, "
				. " (SELECT `usersScheduleDuty` FROM `usersSchedule` WHERE `usersScheduleUser` = " . $iduser . " AND `usersScheduleDate` = `f_salesDate`) as `usersSalesScheduleDuty`, "
				. " (SELECT COUNT(1) FROM `f_salesRoles` WHERE `f_salesRolesSale` = `saleid` AND `f_salesRolesRole` IN (1,2,3)) AS `saleParticipants`"
				//  Payments  
				. " FROM (SELECT saleid, sum(payment) as payment, paymentDate FROM(SELECT "
//                . " `idf_payments`,"
				. " `f_paymentsSalesID` as `saleid`,"
				. " `f_paymentsAmount` as `payment`,"
				. " `f_paymentsType` as `paymentType`,"
				. " DATE(`f_paymentsDate`) AS `paymentDate`"
//                . " `f_paymentsDate` AS `paymentTime`"
				. " FROM `f_payments` WHERE "
				. " `f_paymentsDate` >= '" . min($dates) . " 00:00:00' "
				. " AND  `f_paymentsDate` <= '" . max($dates) . " 23:59:59'"
				. " AND (SELECT COUNT(1) FROM `f_salesRoles` "
				. " WHERE `f_salesRolesUser` = " . $iduser . " AND `f_salesRolesSale`=`f_paymentsSalesID`)>0 "
				. ""
				. " UNION ALL "
				. ""
				. " SELECT"
//                . " null as `idf_payments`,"
				. " `f_creditsSalesID` as `saleid`,"
				. " `f_creditsSumm` as `payment`,"
				. " '0' as `paymentType`,"
				. " `f_salesDate` AS `paymentDate`"
//                . " `f_creditsAdded`  AS `paymentTime`"
				. " FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
				. " WHERE"
				. " `f_salesDate` >= '" . min($dates) . "' "
				. " AND  `f_salesDate` <= '" . max($dates) . "'"
				. " AND (SELECT COUNT(1) FROM `f_salesRoles` "
				. "      WHERE `f_salesRolesUser` = " . $iduser . " AND `f_salesRolesSale`=`f_creditsSalesID`)>0 "
				. ""
				. " UNION ALL "
				. " SELECT"
//                . " null as `idf_payments`,"
				. " `idf_sales` as `saleid`,"
				. " -`f_salesCancellationSumm` as `payment`,"
				. " null as `paymentType`,"
				. " `f_salesCancellationDate` AS `paymentDate`"
//                . " TIMESTAMP(`f_salesCancellationDate`)  AS `paymentTime`"
				. " FROM `f_sales` "
				. " WHERE"
				. " `f_salesCancellationDate` >= '" . min($dates) . "' "
				. " AND  `f_salesCancellationDate` <= '" . max($dates) . "'"
				. " AND (SELECT COUNT(1) FROM `f_salesRoles` "
				. "      WHERE `f_salesRolesUser` = " . $iduser . " AND `f_salesRolesSale`=`idf_sales`)>0 "
				. ""
				. ") AS `paymentsALL` GROUP BY saleid, paymentDate) AS `payments`"
				//
				. " LEFT JOIN `f_sales` ON (`idf_sales` = `saleid`)"
				. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
				. " HAVING `saleParticipants`>0"
				. "";
	 $f_payments = query2array(mysqlQuery($f_paymentsSQL));
//        printr($f_paymentsSQL);
  }
//    printr($paymentTypes);
  if (in_array(11, $paymentTypes) && count(array_filter($userPaymentsValues, function ($rule) {
						  return $rule['userPaymentsValuesType'] == 11 && is_array($rule['userPaymentsValuesValue']);
						}))
  ) {
//		printr($userPaymentsValues);
	 foreach ($f_payments as $f_payment) {
		$payments['dates'][$f_payment['paymentDate']]['11']['data'][] = $f_payment;
		if ($f_payment['saleParticipants'] ?? false) {
		  $payments['types']['11']['total'] = ($payments['types']['11']['total'] ?? 0) + $f_payment['payment'] / $f_payment['saleParticipants'];
		}
	 }
  }

  if (in_array(40, $paymentTypes) || in_array(49, $paymentTypes)
  ) {

	 foreach ($f_payments as $f_payment) {
		if (in_array(40, $paymentTypes)) {
		  $payments['dates'][$f_payment['paymentDate']]['40']['data'][] = $f_payment;
		}

		if (in_array(49, $paymentTypes) && $f_payment['mySale']) {
//              $payments['dates'][$f_payment['paymentDate']]['40']['data'][] = $f_payment;
		  $payments['dates'][$f_payment['paymentDate']]['49']['data'][] = $f_payment;
		}
	 }
  }



  if (in_array(52, $paymentTypes) && count(array_filter($userPaymentsValues, function ($rule) {
						  return $rule['userPaymentsValuesType'] == 52 && is_array($rule['userPaymentsValuesValue']);
						}))
  ) {
	 //% от доли абонемента вторичного пациента
	 foreach ($f_payments as $f_payment) {

		if ($f_payment['f_salesType'] == 2) { // отсекаем всех без перехода во вторичку и тех, у кого совпадает дата первого визита с датой продажи
//                printr($f_payment);
		  if ($f_payment['f_salesDate'] < '2022-08-01') {
			 $payments['dates'][$f_payment['paymentDate']]['52']['data'][] = $f_payment;
			 if ($f_payment['saleParticipants'] ?? false) {
				$payments['types']['52']['total'] = ($payments['types']['52']['total'] ?? 0) + $f_payment['payment'] / $f_payment['saleParticipants'];
			 }
		  } else {
			 if (($f_payment['prePaymentsSumm'] ?? 0) > 15000) {
				$payments['dates'][$f_payment['paymentDate']]['52']['data'][] = $f_payment;
				if ($f_payment['saleParticipants'] ?? false) {
				  $payments['types']['52']['total'] = ($payments['types']['52']['total'] ?? 0) + $f_payment['payment'] / $f_payment['saleParticipants'];
				}
			 } else {//prePaymentsSumm<15000
				if (($f_payment['prePaymentsSumm'] ?? 0) + ($f_payment['payment'] ?? 0) > 15000) {
				  $payments['dates'][$f_payment['paymentDate']]['52']['data'][] = $f_payment;
				  if ($f_payment['saleParticipants'] ?? false) {
					 $payments['types']['52']['total'] = ($payments['types']['52']['total'] ?? 0) + ($f_payment['payment'] + $f_payment['prePaymentsSumm']) / $f_payment['saleParticipants'];
				  }
				} else {
				  $payments['dates'][$f_payment['paymentDate']]['52']['data'][] = $f_payment;
				}
			 }
		  }
		}
	 }
  }



  if (in_array(59, $paymentTypes) && count(array_filter($userPaymentsValues, function ($rule) {
						  return $rule['userPaymentsValuesType'] == 59 && is_array($rule['userPaymentsValuesValue']);
						}))
  ) {

//% от всего платежа по первичной продаже в которой сотрудник указан как ПМ
	 foreach ($f_payments as $f_payment) {

		if ($f_payment['f_salesType'] == 1 && $f_payment['myRole'] == 1) { // отсекаем всех без перехода во вторичку и тех, у кого совпадает дата первого визита с датой продажи
//                printr($f_payment);
		  if ($f_payment['f_salesDate'] < '2022-08-01') {
			 $payments['dates'][$f_payment['paymentDate']]['59']['data'][] = $f_payment;
			 if ($f_payment['saleParticipants'] ?? false) {
				$payments['types']['59']['total'] = ($payments['types']['59']['total'] ?? 0) + $f_payment['payment'];
			 }
		  } else {
			 if (($f_payment['prePaymentsSumm'] ?? 0) > 15000) {
				$payments['dates'][$f_payment['paymentDate']]['59']['data'][] = $f_payment;
				if ($f_payment['saleParticipants'] ?? false) {
				  $payments['types']['59']['total'] = ($payments['types']['59']['total'] ?? 0) + $f_payment['payment'];
				}
			 } else {//prePaymentsSumm<15000
				if (($f_payment['prePaymentsSumm'] ?? 0) + ($f_payment['payment'] ?? 0) > 15000) {
				  $payments['dates'][$f_payment['paymentDate']]['59']['data'][] = $f_payment;
				  if ($f_payment['saleParticipants'] ?? false) {
					 $payments['types']['59']['total'] = ($payments['types']['59']['total'] ?? 0) + ($f_payment['payment'] + $f_payment['prePaymentsSumm']);
				  }
				} else {
				  $payments['dates'][$f_payment['paymentDate']]['59']['data'][] = $f_payment;
				}
			 }
		  }
		}
	 }
  }



  if (in_array(61, $paymentTypes) && count(array_filter($userPaymentsValues, function ($rule) {
						  return $rule['userPaymentsValuesType'] == 61 && is_array($rule['userPaymentsValuesValue']);
						}))
  ) {
	 // доли от платежа по вторичной продаже в которой сотрудник указан как ПМ
	 foreach ($f_payments as $f_payment) {

		if ($f_payment['f_salesType'] == 2 &&
				  $f_payment['myRole'] == 1
		) { // отсекаем всех без перехода во вторичку и тех, у кого совпадает дата первого визита с датой продажи
//                printr($f_payment);
		  if ($f_payment['f_salesDate'] < '2022-08-01') {
			 $payments['dates'][$f_payment['paymentDate']]['61']['data'][] = $f_payment;
			 if ($f_payment['saleParticipants'] ?? false) {
				$payments['types']['61']['total'] = ($payments['types']['61']['total'] ?? 0) + $f_payment['payment'] / $f_payment['saleParticipants'];
			 }
		  } else {
			 if (($f_payment['prePaymentsSumm'] ?? 0) > 15000) {
				$payments['dates'][$f_payment['paymentDate']]['61']['data'][] = $f_payment;
				if ($f_payment['saleParticipants'] ?? false) {
				  $payments['types']['61']['total'] = ($payments['types']['61']['total'] ?? 0) + $f_payment['payment'] / $f_payment['saleParticipants'];
				}
			 } else {//prePaymentsSumm<15000
				if (($f_payment['prePaymentsSumm'] ?? 0) + ($f_payment['payment'] ?? 0) > 15000) {
				  $payments['dates'][$f_payment['paymentDate']]['61']['data'][] = $f_payment;
				  if ($f_payment['saleParticipants'] ?? false) {
					 $payments['types']['61']['total'] = ($payments['types']['61']['total'] ?? 0) + ($f_payment['payment'] + $f_payment['prePaymentsSumm']) / $f_payment['saleParticipants'];
				  }
				} else {
				  $payments['dates'][$f_payment['paymentDate']]['61']['data'][] = $f_payment;
				}
			 }
		  }
		}
	 }
  }









  ////54 new
  if (in_array(54, $paymentTypes) && count(array_filter($userPaymentsValues, function ($rule) {
						  return $rule['userPaymentsValuesType'] == 54 && is_array($rule['userPaymentsValuesValue']);
						}))
  ) {
	 //% от всего абонемента первичного пациента
	 foreach ($f_payments as $f_payment) {

		if ($f_payment['f_salesType'] == 1) { // отсекаем всех без перехода во вторичку и тех, у кого совпадает дата первого визита с датой продажи
//                printr($f_payment);
		  if ($f_payment['f_salesDate'] < '2022-08-01') {
			 $payments['dates'][$f_payment['paymentDate']]['54']['data'][] = $f_payment;
			 if ($f_payment['saleParticipants'] ?? false) {
				$payments['types']['54']['total'] = ($payments['types']['54']['total'] ?? 0) + $f_payment['payment'];
			 }
		  } else {

			 if (($f_payment['prePaymentsSumm'] ?? 0) > 15000) {
				$payments['dates'][$f_payment['paymentDate']]['54']['data'][] = $f_payment;
				if ($f_payment['saleParticipants'] ?? false) {
				  $payments['types']['54']['total'] = ($payments['types']['54']['total'] ?? 0) + $f_payment['payment'];
				}
			 } else {//prePaymentsSumm<15000
				if (($f_payment['prePaymentsSumm'] ?? 0) + ($f_payment['payment'] ?? 0) > 15000) {
				  $payments['dates'][$f_payment['paymentDate']]['54']['data'][] = $f_payment;
				  if ($f_payment['saleParticipants'] ?? false) {
					 $payments['types']['54']['total'] = ($payments['types']['54']['total'] ?? 0) + ($f_payment['payment'] + $f_payment['prePaymentsSumm']);
				  }
				} else {
				  $payments['dates'][$f_payment['paymentDate']]['54']['data'][] = $f_payment;
				}
			 }
		  }
		}
	 }
  }


  ////60 new
  if (in_array(60, $paymentTypes)) {
	 //% от всего платежа по первичной продаже в которой сотрудник указан как СПЛ
	 foreach (($f_payments ?? []) as $f_payment) {
//            printr($f_payment);
		if (
				  $f_payment['f_salesType'] == 1 &&
				  $f_payment['myRole'] == 2 //СПЛ
		) { // отсекаем всех без перехода во вторичку и тех, у кого совпадает дата первого визита с датой продажи
//                printr($f_payment);
		  $payments['dates'][$f_payment['paymentDate']]['60']['data'][] = $f_payment;
		}
	 }
  }


  ////62 new
  if (in_array(62, $paymentTypes)) {
	 //% доли от платежа по вторичной продаже в которой сотрудник указан как СПЛ
	 foreach (($f_payments ?? []) as $f_payment) {
//            printr($f_payment);
		if (
				  $f_payment['f_salesType'] == 2 &&
				  $f_payment['myRole'] == 2 //СПЛ
		) { // отсекаем всех без перехода во вторичку и тех, у кого совпадает дата первого визита с датой продажи
//                printr($f_payment);
		  $payments['dates'][$f_payment['paymentDate']]['62']['data'][] = $f_payment;
		}
	 }
  }












//    if (in_array(54, $paymentTypes) && count(array_filter($userPaymentsValues, function ($rule) {
//                        return $rule['userPaymentsValuesType'] == 54 && is_array($rule['userPaymentsValuesValue']);
//                    }))
//    ) {
//        //% от всего абонемента первичного пациента
//        foreach ($f_payments as $f_payment) {
//
//            if ($f_payment['f_salesType'] == 1) { // отсекаем всех без перехода во вторичку и тех, у кого совпадает дата первого визита с датой продажи
////                printr($f_payment);
//                $payments['dates'][$f_payment['paymentDate']]['54']['data'][] = $f_payment;
//                $payments['types']['54']['total'] = ($payments['types']['54']['total'] ?? 0) + $f_payment['payment'];
//            }
//        }
//    }


  $servicesApplied = query2array(mysqlQuery("SELECT"
//					. " *, "
						. "`servicesAppliedQty`,"
						. "`servicesAppliedDate`,"
						. "`servicesAppliedContract`,"
						. "`servicesAppliedPrice`,"
						. "`servicesAppliedIsDiagnostic`,"
						. "`idservices`,"
						. "`servicesName`,"
						. "`idclients`,"
						. "`clientsLName`,"
						. "`clientsFName`,"
						. "`clientsMName`,"
						. "`usersServicesPaymentsSumm`,"
						. "`usersServicesPaymentsSummFree`,"
						. " (SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = "
						. "	(SELECT MAX(idservicesPrices) FROM `servicesPrices` WHERE date(`servicesPricesDate`)<=`servicesAppliedDate` AND `servicesPricesService` = `idservices` AND `servicesPricesType`=3)"
						. ") as `minWage`,"
						. " (SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = "
						. "	(SELECT MAX(idservicesPrices) FROM `servicesPrices` WHERE date(`servicesPricesDate`)<=`servicesAppliedDate` AND `servicesPricesService` = `idservices` AND `servicesPricesType`=4)"
						. ") as `maxWage`"
						. ""
						. " FROM `servicesApplied`"
						. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
						. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
						. " LEFT JOIN `usersServicesPayments` ON (`idusersServicesPayments` = (SELECT MAX(`idusersServicesPayments`) FROM `usersServicesPayments` WHERE `usersServicesPaymentsDate`<=`servicesAppliedDate` AND `usersServicesPaymentsService` = `servicesAppliedService` AND `usersServicesPaymentsUser` = $iduser))"
						. " WHERE `servicesAppliedPersonal`=$iduser"
						. " AND `servicesAppliedDate`>='" . min($dates) . "'"
						. " AND `servicesAppliedDate`<='" . max($dates) . "'"
						. " AND NOT isnull(`servicesAppliedFineshed`)"
						. " AND NOT isnull(`servicesAppliedService`)"
						. " AND isnull(`servicesAppliedDeleted`)"
						. ""));

  ///////////////////////////////////////////////////////////////////////
  ///////////////////////////////////////////////////////////////////////
  $userShifts = getUserShifts($iduser, $dates);

  if (in_array(3, $paymentTypes)) {
	 $creditFsales = query2array(mysqlQuery("SELECT"
//						. " *,"
						  . " `idf_sales`, "
						  . " `clientsLName`, "
						  . " `clientsFName`, "
						  . " `clientsMName`, "
						  . " `f_salesType`, "
						  . " `f_salesIsSmall`, "
						  . " `f_salesTypesName`, "
						  . " `f_salesSumm`, "
						  . " `f_salesDate` "
						  . " FROM `f_sales`"
						  . " LEFT JOIN `f_salesRoles` ON (`idf_sales`=`f_salesRolesSale`)"
						  . " LEFT JOIN `clients` ON (`idclients`=`f_salesClient`)"
						  . " LEFT JOIN `f_salesTypes` ON (`idf_salesTypes` = `f_salesType`)"
						  . ""
						  . " WHERE `f_salesRolesUser` = $iduser"
						  . " AND `f_salesRolesRole` = 5"
						  . " AND `f_salesDate`>='" . min($dates) . "'"
						  . " AND `f_salesDate`<='" . max($dates) . "'"
						  . ""));
	 foreach ($creditFsales as $creditFsale) {
		$payments['dates'][$creditFsale['f_salesDate']]['3']['data'][] = $creditFsale;
	 }
//		printr($creditFsales);
  }

  if (in_array(50, $paymentTypes)) {


	 $payments50 = query2array(mysqlQuery("SELECT *, (SELECT `f_salesSumm` FROM `f_sales` WHERE `idf_sales` = `saleid`) as `f_saleSumm` "
						  . " FROM (SELECT "
						  . " `f_paymentsSalesID` as `saleid`,"
						  . " `f_paymentsAmount` as `payment`,"
						  . " `f_paymentsType` as `paymentType`,"
						  . " DATE(`f_paymentsDate`) AS `paymentDate`"
						  . " FROM `f_payments` WHERE "
						  . " `f_paymentsDate` >= '" . min($dates) . " 00:00:00' "
						  . " AND  `f_paymentsDate` <= '" . max($dates) . " 23:59:59'"
						  . "UNION ALL "
						  . ""
						  . " SELECT"
						  . " `f_creditsSalesID` as `saleid`,"
						  . " `f_creditsSumm` as `payment`,"
						  . " '0' as `paymentType`,"
						  . " `f_salesDate` AS `paymentDate`"
						  . " FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
						  . " WHERE"
						  . " `f_salesDate` >= '" . min($dates) . "' "
						  . " AND  `f_salesDate` <= '" . max($dates) . "'"
						  . ") AS `payments`"
						  . ""));

//		printr($payments50, 1);
  }

//////////////////////////////////////////58//////////////////////////////////////////////////////////////////////
  if (in_array(58, $paymentTypes)) {
	 //% от всего абонемента первичного пациента
	 foreach ($f_payments as $f_payment) {
//            printr($f_payment);
//            continue;
		if ($f_payment['saleParticipants'] > 1) { // 
//                printr($f_payment);
		  $payments['dates'][$f_payment['paymentDate']]['58']['data'][] = $f_payment;
		}
	 }
  }

///////////////////////////////////////////////////\\58/////////////////////////////////////////////////////////////
//////////////////////////////////////////57//////////////////////////////////////////////////////////////////////
  if (in_array(57, $paymentTypes)) {
	 //% от всего абонемента первичного пациента
	 foreach ($f_payments as $f_payment) {
//            printr($f_payment);
//            continue;
		if ($f_payment['saleParticipants'] == 1) { // 
//                printr($f_payment);
		  $payments['dates'][$f_payment['paymentDate']]['57']['data'][] = $f_payment;
		}
	 }
  }

///////////////////////////////////////////////////\\57/////////////////////////////////////////////////////////////
//////////////////////////////////////////57//////////////////////////////////////////////////////////////////////
  if (in_array(63, $paymentTypes) || in_array(64, $paymentTypes)) {
	 //% от всего абонемента первичного пациента
	 foreach ($f_payments as $f_payment) {
//            printr($f_payment);
//            continue;
		$f_payment['saleParticipants'] = mfa(mysqlQuery("SELECT COUNT(1) as `saleParticipants` FROM `f_salesRoles` WHERE `f_salesRolesSale` = '" . $f_payment['idf_sales'] . "' AND `f_salesRolesRole` IN (2,3)"))['saleParticipants'];
		if ($f_payment['saleParticipants'] == 1) { // 
//                printr($f_payment);
		  $payments['dates'][$f_payment['paymentDate']]['63']['data'][] = $f_payment;
		} elseif ($f_payment['saleParticipants'] > 1) {
		  $payments['dates'][$f_payment['paymentDate']]['64']['data'][] = $f_payment;
		}
	 }
  }

///////////////////////////////////////////////////\\57/////////////////////////////////////////////////////////////
//	//	$output = [];
//	foreach (dates($dates['from'], $dates['to']) as $date) {
//		$output[$date]['paymentsValues'] = getPaymentsValues($userPaymentsValues, $date);
//		$output[$date]['userShifts'] = $userShifts[$date] ?? null;
//	}
//	printr($userPaymentsValues, 1);

  foreach (($servicesApplied ?? []) as $serviceApplied) {
//		printr($serviceApplied);
	 $payments['dates'][$serviceApplied['servicesAppliedDate']]['dops']['data'][] = $serviceApplied;
  }




  foreach (dates($dates['from'], $dates['to']) as $date) {
	 $paymentsValues = getPaymentsValues($userPaymentsValues, $date);

	 include 'payments/module_1.php'; // 1] Оклад за полную смену
	 include 'payments/module_3.php'; // Кредитные
	 include 'payments/module_6.php'; // Оклад за месяц
	 include 'payments/module_9.php'; // 9] Почасовая
	 include 'payments/module_11.php'; // 11] ПМП % от прдаж
	 include 'payments/module_33.php'; // 33] % от стоимоисти оказанных услуг
	 include 'payments/module_39.php'; // 39] % от продажи делить на всех участников
	 include 'payments/module_48.php'; // 48] % от ДЕЖУРНЫЕ СМЕНЫ продажи делить на всех участников
	 include 'payments/module_40.php'; // 40] % оборота
	 include 'payments/module_41.php'; // 41] Оклад за 1/2 смены
	 include 'payments/module_42.php'; // Премия за первичный визит
	 include 'payments/module_43.php'; // Маркетинг. Премия за визит клиента (источник 13 Лидогенерация МСК)
	 include 'payments/module_44.php'; // Маркетинг. Премия за визит клиента (источник 28 Актель ВХОД)
	 include 'payments/module_45.php'; // Маркетинг. Премия за визит клиента (источник не 13 и не 28)
	 include 'payments/module_46.php'; // Маркетинг. Вторичка без абонов
	 include 'payments/module_47.php'; // Сверхурочные
	 include 'payments/module_49.php'; // 40] % оборота в рабочие дни
	 include 'payments/module_50.php'; // 50] Маркетинг бонус за купленные абонементы
	 include 'payments/module_51.php'; // 51] % от всего абонемента первичного пациента
	 include 'payments/module_52.php'; // 52] % от доли абонемента вторичного пациента (СЕТКА)
	 include 'payments/module_53.php'; // 53] % от доли абонемента вторичного пациента
	 include 'payments/module_54.php'; // 54] % от всего абонемента первичного пациента (СЕТКА)
	 include 'payments/module_56.php'; // Маркетинг приход по источнику
	 include 'payments/module_57.php'; // % от личной продажи
	 include 'payments/module_58.php'; // % от групповой продажи
	 include 'payments/module_59.php'; // % от всего платежа по первичной продаже в которой сотрудник указан как ПМ
	 include 'payments/module_60.php'; // % от всего платежа по первичной продаже в которой сотрудник указан как СПЛ
	 include 'payments/module_61.php'; // % доли от платежа по вторичной продаже в которой сотрудник указан как ПМ
	 include 'payments/module_62.php'; // % доли от платежа по вторичной продаже в которой сотрудник указан как СПЛ
	 include 'payments/module_63.php'; // % от одиночной продажи без ПМП
	 include 'payments/module_64.php'; // % от групповой продажи без ПМП
	 include 'payments/module_dops.php'; // Допы
  }

  return $payments;
}

function getUserShifts($iduser, $dates) {
//	printr([$iduser, $dates]);
  $fingerlog = query2array(mysqlQuery("SELECT"
						. " `fingerLogDate`,"
						. " min(`fingerLogTime`) as `fingerFrom`,"
						. " max(`fingerLogTime`) as `fingerTo`,"
						. " (UNIX_TIMESTAMP(max(`fingerLogTime`))-UNIX_TIMESTAMP(min(`fingerLogTime`))) AS `fingerDuration` "
						. " FROM `fingerLog`"
						. " WHERE `fingerlogUser` = $iduser"
						. " AND `fingerLogDate`>='" . min($dates) . "'"
						. " AND `fingerLogDate`<='" . max($dates) . "'"
						. " GROUP BY `fingerLogDate`"), 'fingerLogDate');

  $usersSchedule = query2array(mysqlQuery("SELECT * FROM `usersSchedule`"
						. " WHERE `usersScheduleUser` = $iduser"
						. " AND `usersScheduleDate`>='" . min($dates) . "'"
						. " AND `usersScheduleDate`<='" . max($dates) . "'"
						. ""), 'usersScheduleDate');
  foreach ($usersSchedule as $shift) {
	 $fingerlog[$shift['usersScheduleDate']]['scheduleFrom'] = $shift['usersScheduleFrom'];
	 $fingerlog[$shift['usersScheduleDate']]['scheduleTo'] = $shift['usersScheduleTo'];
	 $fingerlog[$shift['usersScheduleDate']]['usersScheduleDuty'] = !!$shift['usersScheduleDuty'];
	 $fingerlog[$shift['usersScheduleDate']]['scheduleHalfs'] = $shift['usersScheduleHalfs'];
	 $fingerlog[$shift['usersScheduleDate']]['scheduleDuration'] = (($shift['usersScheduleFrom'] ?? false) && ($shift['usersScheduleTo'] ?? false)) ? (strtotime($shift['usersScheduleTo']) - strtotime($shift['usersScheduleFrom'])) : 0;
  }
  foreach ($fingerlog as $date => $data) {
	 $fingerlog[$date]['WHpercent'] = (($data['fingerDuration'] ?? false) && ($data['scheduleDuration'] ?? false)) ? ($data['fingerDuration'] / $data['scheduleDuration']) : 0;
	 $fingerlog[$date]['WHpercentLimit_1'] = min($fingerlog[$date]['WHpercent'], 1);
	 if (($data['fingerDuration'] ?? false) && ($data['scheduleDuration'] ?? false)) {


		$fingerlog[$date]['WHpercentLimit_shift'] = (min(strtotime($data['scheduleTo']), strtotime($data['fingerTo'])) - max(strtotime($data['fingerFrom']), strtotime($data['scheduleFrom']))) / $data['scheduleDuration'];
	 } else {
		$fingerlog[$date]['WHpercentLimit_shift'] = 0;
	 }
	 if (($fingerlog[$date]['scheduleHalfs'] ?? false) == '11') {
		$fingerlog[$date]['scheduleSize'] = 1;
	 } elseif (in_array(($fingerlog[$date]['scheduleHalfs'] ?? false), ['01', '10'])) {
		$fingerlog[$date]['scheduleSize'] = 0.5;
	 } else {
		$fingerlog[$date]['scheduleSize'] = 0;
	 }
  }

  return $fingerlog ?? [];
}
