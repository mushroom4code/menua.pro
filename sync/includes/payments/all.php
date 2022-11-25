<?php
$pageTitle = $load['title'] = 'ЗП Все';
$times = [];
$start = microtime(1);
$times[__LINE__][] = microtime(1) - $start;
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(121)) {
	?>E403R121<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/reports/menu.php';

	$from = $_GET['from'] ?? date("Y-m-01");
	$to = $_GET['to'] ?? date("Y-m-d");

	function getPaymentsValue($userPaymentsValues, $userPaymentsValuesType, $userPaymentsValuesDate) {//опции по оплате, т.к. у нас всё отсортировано, удаляем всё, кроме подходящих опций и удаляем настройки из будущего. и берем последнюю запись.
		$typePayments = obj2array(array_filter($userPaymentsValues, function ($userPaymentsValue) use ($userPaymentsValuesType, $userPaymentsValuesDate) {
					return ($userPaymentsValue['userPaymentsValuesType'] == $userPaymentsValuesType) && mystrtotime($userPaymentsValue['userPaymentsValuesDate']) <= mystrtotime($userPaymentsValuesDate);
				}));
		if (count($typePayments)) {
			return floatval($typePayments[0]['userPaymentsValuesValue'] ?? 0);
		} else {
			return 0;
		}
	}

	$usersPaymentsValues = query2array(mysqlQuery("SELECT * FROM `userPaymentsValues`"
					. " WHERE `userPaymentsValuesType`"
					. " ORDER BY `userPaymentsValuesDate` DESC, `iduserPaymentsValues` DESC "));
	?>
	<style>
		th {
			padding: 5px 10px;
		}
	</style>
	<div class="box neutral">
		<div class="box-body">

			<?
			///(SELECT `f_salesSumm`/ifnull((SELECT COUNT(1) FROM `f_salesRoles` WHERE `f_salesRolesRole` IN('2') AND `idf_sales`= `f_salesRolesSale`),1))
			$personnel = query2array(mysqlQuery("SELECT"
							. " *"
							. " FROM"
							. " `users` WHERE `usersGroup` NOT IN (12,9) "
							. " AND (isnull(`usersDeleted`) OR `usersDeleted`>'" . min($from, $to) . "')"
							. (($_GET['user'] ?? false) ? (" AND `idusers`= " . mres($_GET['user']) . "") : "")
							. ""), 'idusers');
			$times[__LINE__][] = microtime(1) - $start;
			$personnel = array_values($personnel);
			$times[__LINE__][] = microtime(1) - $start;
//			printr($personnel);
			foreach ($personnel as $index => $user) {
				if (!($user['idusers'] ?? false)) {
					continue;
				}

				$personnel[$index]['userPaymentsValues'] = array_values(array_filter($usersPaymentsValues, function ($paymentvalue) use ($user) {
							return $paymentvalue['userPaymentsValuesUser'] == $user['idusers'];
						}));

//				printr($personnel[$index]['userPaymentsValues'] ?? '');

				$personnel[$index]['userPaymentsValuesLT']['11'] = query2array(mysqlQuery("SELECT * "
								. " FROM `LT` "
								. " WHERE "
								. " `LTuser` = '" . mres($user['idusers']) . "'"
								. " AND `LTid` = '11'"
								. " AND `LTdate`= (SELECT MAX(`LTdate`) FROM `LT` WHERE `LTdate`<='$to' AND `LTuser` = '" . mres($user['idusers']) . "' AND `LTid` = '11')"));
				if ($personnel[$index]['userPaymentsValuesLT']['11']) {
					usort($personnel[$index]['userPaymentsValuesLT']['11'], function ($a, $b) {
						if ($a['LTdate'] <=> $b['LTdate']) {
							return $b['LTdate'] <=> $a['LTdate'];
						}

						if ($a['LTresult'] <=> $b['LTresult']) {
							return floatval($a['LTresult']) <=> floatval($b['LTresult']);
						}
					});
				}


				$times[__LINE__][] = microtime(1) - $start;
				$personnel[$index]['creditpersonnel'] = query2array(mysqlQuery(" "
								. "SELECT * "
								. " FROM `f_salesRoles`"
								. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_salesRolesSale`)"
								. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
								. " WHERE `f_salesRolesUser` = " . $user['idusers']
								. " AND `f_salesRolesRole` = 5"
								. " AND `f_salesDate`>='$from'"
								. " AND `f_salesDate`<='$to'"
								. " AND `f_salesType` IN (1,2)"
								. ""));
				$times[__LINE__][] = microtime(1) - $start;
				$personnel[$index]['aervicesApplied'] = query2array(mysqlQuery("SELECT * "
								. " FROM `servicesApplied` "
								. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
								. " LEFT JOIN `serviceMotivation` ON (`serviceMotivationService` = `idservices`)"
								. " WHERE `servicesAppliedDate`>='" . $from . "'"
								. " AND  `servicesAppliedDate`<='" . $to . "'"
								. " AND  `servicesAppliedPersonal`='" . $user['idusers'] . "'"
								. " AND isnull(`serviceMotivationMotivation`)"
								. " AND NOT isnull(`servicesAppliedPersonal`)"
								. " AND NOT isnull(`servicesAppliedFineshed`)"
								. " AND NOT isnull(`servicesAppliedService`)"
								. " AND isnull(`servicesAppliedDeleted`)"
								. " ORDER BY `servicesAppliedTimeBegin`"));
				$times[__LINE__][] = microtime(1) - $start;

				$personnel[$index]['sales'] = query2array(mysqlQuery("SELECT *, (`f_salesSumm`/ifnull((SELECT COUNT(1) FROM `f_salesRoles` WHERE `f_salesRolesRole` in (1,2,3) AND `idf_sales`= `f_salesRolesSale`),1)) as `f_salesSummPart`, "
								. "(SELECT COUNT(1) FROM `f_salesRoles` WHERE `f_salesRolesRole` in (1,2,3) AND `idf_sales`= `f_salesRolesSale`) as `participants` ,"
								. "("
								. "ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales`),0)"
								. "+"
								. "ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0)"
								. ") as `payments`"
								. " FROM `f_salesRoles`"
								. " LEFT JOIN `f_sales` ON (`idf_sales`= `f_salesRolesSale`)"
								. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
								. " WHERE"
								. " `f_salesRolesUser` = '" . $user['idusers'] . "'"
								. " AND `f_salesRolesRole` in (1,2,3)"
								. (($_GET['I_only'] ?? false) ? " AND  `clientsOldSince`=`f_salesDate`" : "")
								. " AND `f_salesDate`>='$from'"
								. " AND `f_salesDate`<='$to'"
								. " AND `f_salesType` IN (1,2)"
								. " ORDER BY `idf_sales`"));
				$personnel[$index]['salesPayments'] = query2array(mysqlQuery("SELECT
    `sales`.*,
    `f_salesSumm`,
    `f_salesType`,
    `idclients`,
    `clientsLName`,
    `clientsFName`,
    `clientsMName`
FROM
    (SELECT
        `idsale`,
            `date`,
            SUM(`paymentValue`) AS `paymentValue`,
            SUM(`cancellationSumm`) AS `cancellationSumm`,
            (SELECT
                    COUNT(1)
                FROM
                    `f_salesRoles`
                WHERE
                    `f_salesRolesSale` = `idsale`
                        AND `f_salesRolesRole` IN (1 , 2, 3)) AS `participants`,
            (SELECT
                    COUNT(1)
                FROM
                    `f_salesRoles`
                WHERE
                    `f_salesRolesSale` = `idsale`
                        AND `f_salesRolesRole` IN (1 , 2, 3)
                        AND `f_salesRolesUser` = " . $user['idusers'] . ") > 0 AS `mysale`
    FROM
        (SELECT
        `f_paymentsSalesID` AS `idsale`,
            DATE(`f_paymentsDate`) AS `date`,
            `f_paymentsAmount` AS `paymentValue`,
            0 AS `cancellationSumm`
    FROM
        `f_payments`
    WHERE
        `f_paymentsDate` >= '$from 00:00:00'
            AND `f_paymentsDate` <= '$to 23:59:59' UNION ALL SELECT
        `f_creditsSalesID` AS `idsale`,
         `f_salesDate` AS `date`,
            `f_creditsSumm` AS `paymentValue`,
            0 AS `cancellationSumm`
    FROM
        `f_credits`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)
    WHERE
        `f_salesDate` >= '$from'
            AND `f_salesDate` <= '$to' UNION ALL SELECT
        `idf_sales` AS `idsale`,
        `f_salesCancellationDate` AS `date`,
            0 AS `paymentValue`,
            - `f_salesCancellationSumm` AS `cancellationSumm`
    FROM
        `f_sales`
    WHERE
        `f_salesCancellationDate` >= '$from'
            AND `f_salesCancellationDate` <= '$to') AS `payments`
    GROUP BY `idsale`,`date`
    ORDER BY `date`,`idsale`) AS `sales`
        LEFT JOIN
    `f_sales` ON (`idf_sales` = `idsale`)
        LEFT JOIN
    `clients` ON (`idclients` = `f_salesClient`)
WHERE
    `mysale`;  "));

				$times[__LINE__][] = microtime(1) - $start;
				$personnel[$index]['canceled'] = query2array(mysqlQuery("SELECT *, (`f_salesCancellationSumm`/ifnull((SELECT COUNT(1) FROM `f_salesRoles` WHERE `f_salesRolesRole` IN(1,2,3) AND `idf_sales`= `f_salesRolesSale`),1)) as `f_salesCancellationSumm` "
								. " FROM `f_salesRoles`"
								. " LEFT JOIN `f_sales` ON (`idf_sales`= `f_salesRolesSale`)"
								. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
								. " WHERE"
								. " `f_salesRolesUser` = '" . $user['idusers'] . "'"
								. " AND `f_salesRolesRole` IN (1,2,3)"
								. " AND `f_salesCancellationDate`>='$from'"
								. " AND `f_salesCancellationDate`<='$to'"
								. " AND `f_salesType` IN (1,2)"));
				$times[__LINE__][] = microtime(1) - $start;
				$fingerLog = query2array(mysqlQuery("SELECT * "
								. " FROM `fingerLog` "
								. " WHERE "
								. " `fingerLogTime` >= '" . min($from, $to) . " 00:00:00'"
								. " AND `fingerLogTime` <= '" . max($from, $to) . " 23:59:59'"
								. " AND `fingerLogUser` = '" . $user['idusers'] . "'"
				));
				$times[__LINE__][] = microtime(1) - $start;
				$schedule = query2array(mysqlQuery("SELECT * "
								. " FROM `usersSchedule` "
								. " WHERE "
								. " `usersScheduleDate` >= '" . min($from, $to) . "'"
								. " AND `usersScheduleDate` <= '" . max($from, $to) . "'"
								. " AND `usersScheduleUser` = '" . $user['idusers'] . "'"
								. " AND `usersScheduleHalfs` IN ('11','10','01')"
				));
				$times[__LINE__][] = microtime(1) - $start;
				$servicesApplied = query2array(mysqlQuery("SELECT `idservices`,`idservicesApplied`,`servicesAppliedPersonal`,`servicesAppliedService`,`clientsLName`,`clientsFName`,`servicesAppliedClient`,`servicesAppliedContract`,`servicesName`,`servicesAppliedPrice`,`servicesAppliedQty`,`servicesAppliedDate` "
								. " FROM `servicesApplied` "
								. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) "
								. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`) "
								. " WHERE `servicesAppliedDate` >= '" . min($from, $to) . "'"
								. " AND `servicesAppliedDate` <= '" . max($from, $to) . "'"
								. " AND isnull(`servicesAppliedDeleted`)"
								. " AND `servicesAppliedPersonal` = '" . $user['idusers'] . "'"
								//	. " AND (`servicesAppliedPrice`>0 || not isnull(`servicesAppliedContract`))"
								. " AND NOT isnull(`servicesAppliedFineshed`) "
								. "AND "
								. "("
								. "(SELECT COUNT(1) FROM `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType` IN (3,4)) > 0"
								. "|| "
								. "(SELECT COUNT(1) FROM `usersServicesPayments` WHERE `usersServicesPaymentsService` = `idservices` AND `usersServicesPaymentsUser`=`servicesAppliedPersonal`) > 0"
								. ")"
								. ""
								. "ORDER BY `servicesAppliedDate`"));
				$times[__LINE__][] = microtime(1) - $start;
//				printr($servicesApplied);
				foreach ($servicesApplied as $servicesApplied) {
//					print $servicesApplied['idservicesApplied'] . '; ';
					$wage = (getWage($servicesApplied) * $servicesApplied['servicesAppliedQty']) ?? 0;
					if (!getPaymentsValue($personnel[$index]['userPaymentsValues'], 13, $servicesApplied['servicesAppliedDate'])) {
						$personnel[$index]['wage'][] = [
							'id' => $servicesApplied['idservices'],
							'client' => $servicesApplied['servicesAppliedClient'],
							'clientsLName' => $servicesApplied['clientsLName'],
							'clientsFName' => $servicesApplied['clientsFName'],
							'idservicesApplied' => $servicesApplied['idservicesApplied'],
							'contract' => $servicesApplied['servicesAppliedContract'],
							'price' => $servicesApplied['servicesAppliedPrice'],
							'name' => $servicesApplied['servicesName'],
							'date' => $servicesApplied['servicesAppliedDate'],
							'qty' => $servicesApplied['servicesAppliedQty'],
							'wage' => $wage];
					}
				}
//				printr($__personalPrice);
				$times[__LINE__][] = microtime(1) - $start;
				if (count($personnel[$index]['wage'] ?? [])) {
					usort($personnel[$index]['wage'], function ($a, $b) {
						if ($a['date'] <=> $b['date']) {
							return $a['date'] <=> $b['date'];
						}
						return mb_strtolower($a['clientsLName'] . $a['clientsFName']) <=> mb_strtolower($b['clientsLName'] . $b['clientsFName']);
					});
				}
				$times[__LINE__][] = microtime(1) - $start;
//				printr($servicesApplied);
				foreach ($fingerLog as $entry) {
//					printr($entry);
					$personnel[$index]['schedule'][date("Y-m-d", strtotime($entry['fingerLogTime']))]['fact'][] = $entry['fingerLogTime'];
				}
				$times[__LINE__][] = microtime(1) - $start;
				foreach ($schedule as $entry) {
//					printr($entry);
					$personnel[$index]['schedule'][$entry['usersScheduleDate']]['plan'] = [$entry['usersScheduleFrom'], $entry['usersScheduleTo']];
					$personnel[$index]['schedule'][$entry['usersScheduleDate']]['shift'] = (string) $entry['usersScheduleHalfs'];
				}
				$times[__LINE__][] = microtime(1) - $start;
				foreach (($personnel[$index]['schedule'] ?? []) as $date => $data) {

					$personnel[$index]['schedule'][$date]['UPV']['10'] = getPaymentsValue($personnel[$index]['userPaymentsValues'], 10, $date);
					$personnel[$index]['schedule'][$date]['UPV']['9'] = getPaymentsValue($personnel[$index]['userPaymentsValues'], 9, $date);

					if ($personnel[$index]['schedule'][$date]['plan'] ?? false) {
						$plan = strtotime(max($personnel[$index]['schedule'][$date]['plan'])) - strtotime(min($personnel[$index]['schedule'][$date]['plan']));
					} else {
						$plan = null;
					}

					if ($personnel[$index]['schedule'][$date]['fact'] ?? false) {
						$fact = strtotime(max($personnel[$index]['schedule'][$date]['fact'])) - strtotime(min($personnel[$index]['schedule'][$date]['fact']));
						$personnel[$index]['schedule'][$date]['seconds'] = $fact;
					} else {
						$fact = null;
					}

					if (($plan ?? false) && ($fact ?? false)) {
						$personnel[$index]['schedule'][$date]['percent'] = round(max(0, min($fact / $plan, 1)) * 100);
					} else {
						$personnel[$index]['schedule'][$date]['percent'] = 0;
					}
					$personnel[$index]['schedule'][$date]['rate'] = getPaymentsValue($personnel[$index]['userPaymentsValues'], 9, $date) ?? 0;
					$personnel[$index]['schedule'][$date]['rateShift'] = getPaymentsValue($personnel[$index]['userPaymentsValues'], 1, $date) ?? 0;
					$personnel[$index]['schedule'][$date]['totalShift'] = (
							($personnel[$index]['schedule'][$date]['UPV']['10'] ?? false) &&
							!($personnel[$index]['schedule'][$date]['UPV']['9'] ?? false) &&
							count($personnel[$index]['schedule'][$date]['fact'] ?? [])
							) ? round(
									min(
											($personnel[$index]['schedule'][$date]['shift'] ?? false) ? ($personnel[$index]['schedule'][$date]['shift'] == '11' ? 1 : 0.5) : 0,
											((strtotime(max($personnel[$index]['schedule'][$date]['fact'])) - strtotime(min($personnel[$index]['schedule'][$date]['fact']))) / ($personnel[$index]['schedule'][$date]['UPV']['10'] * 60 * 60))
									)
									, 2) : 0;
				}
				//////////////////////////////$user['reward']
//Считаем сразу за отчётный период, не по дням! (по хорошему надо бы по дням... )
				$personnel[$index]['reward'] = 0;

				$UPV3 = getPaymentsValue($personnel[$index]['userPaymentsValues'], 3, $to);
				$UPV39 = getPaymentsValue($personnel[$index]['userPaymentsValues'], 39, $to);
				$UPV40 = getPaymentsValue($personnel[$index]['userPaymentsValues'], 40, $to);
				$UPV33 = getPaymentsValue($personnel[$index]['userPaymentsValues'], 33, $to);
				$UPV11 = $personnel[$index]['userPaymentsValuesLT']['11'];
//				printr(($personnel[$index]['aervicesApplied']));
				if ($UPV33) {
					$personnel[$index]['reward'] += $UPV33 *
							round((array_reduce(($personnel[$index]['aervicesApplied'] ?? []), function ($carry, $item) {
										if ($item['servicesAppliedPrice'] ?? false) {
											$carry += ($item['servicesAppliedPrice'] * $item['servicesAppliedQty'] );
										}
										return $carry;
									})), 0) / 100;

//							array_sum(array_column($personnel[$index]['aervicesApplied'], 'servicesAppliedPrice')) / 100;
				}
				if ($UPV39) {

					$personnel[$index]['reward'] += round((array_reduce(($personnel[$index]['salesPayments'] ?? []), function ($carry, $item)use ($personnel, $index) {
								$percent = getPaymentsValue($personnel[$index]['userPaymentsValues'], 39, $item['date']);

								if ($item['participants'] ?? false) {
									$carry += (($percent / 100) * $item['paymentValue']) / $item['participants'];
								}
								return $carry;
							})), 0);
				}
				if ($UPV3) {
					$personnel[$index]['reward'] += $UPV3 * mfa(mysqlQuery("SELECT count(1) as `qty` FROM `f_sales` WHERE "
											. "  `f_salesDate` >= '" . min($from, $to) . "'"
											. " AND `f_salesDate` <= '" . max($from, $to) . "'"
											. " AND `f_salesType` IN (1,2)"
											. " AND (SELECT COUNT(1) FROM `f_salesRoles` WHERE `f_salesRolesSale`=`idf_sales` AND `f_salesRolesRole`=5 AND `f_salesRolesUser`=" . $personnel[$index]['idusers'] . ")"
											. ""))['qty'] ?? 0;
				}
				if ($UPV40) {
					$personnel[$index]['reward'] += round($UPV40 * mfa(mysqlQuery("SELECT SUM(`summ`) AS `summ` FROM(SELECT SUM(`f_paymentsAmount`) as `summ` FROM `f_payments`"
											. " WHERE "
											. " `f_paymentsDate` >= '" . min($from, $to) . " 00:00:00'"
											. " AND `f_paymentsDate` <= '" . max($from, $to) . " 23:59:59'"
											. " UNION ALL "//кредиты
											. " SELECT SUM(`f_creditsSumm`) as `summ` FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
											. " WHERE "
											. " `f_salesDate` >= '" . min($from, $to) . "'"
											. " AND `f_salesDate` <= '" . max($from, $to) . "'"
											. " UNION ALL "
											. " SELECT SUM(-`f_salesCancellationSumm`) as `summ` FROM `f_sales` "
											. "WHERE"
											. " `f_salesCancellationDate` >= '" . min($from, $to) . "'"
											. " AND `f_salesCancellationDate` <= '" . max($from, $to) . "') AS `temp`"//Платежи
											. ""
											. ""))['summ'] ?? 0);
				}
				if ($UPV11 && ($personnel[$index]['salesPayments'] ?? false)) {
					$ltgrids = [];
					foreach ($UPV11 as $LTdataRow) {
						$ltgrids[$LTdataRow['LTdate']]['type'] = $LTdataRow['LTtype'] ?? '-';
						$ltgrids[$LTdataRow['LTdate']]['data'][] = [
							'from' => $LTdataRow['LTfrom'],
							'to' => $LTdataRow['LTto'],
							'result' => $LTdataRow['LTresult'],
						];
					}

					$totalpaymentsparts = round((array_reduce(($personnel[$index]['salesPayments'] ?? []), function ($carry, $item) {
								if ($item['participants'] ?? false) {
									$carry += ($item['paymentValue'] + $item['cancellationSumm'] ) / $item['participants'];
								}
								return $carry;
							})), 0);

					$personnel[$index]['reward'] += floor(LT($ltgrids, $totalpaymentsparts, $to) * $totalpaymentsparts);
				}

				/////////////////////////////////////\\\\\\\\\\\\\$user['reward']









				$times[__LINE__][] = microtime(1) - $start;
//				printr($personnel[$index]['schedule'] ?? '');
			}
			$times[__LINE__][] = microtime(1) - $start;

//			printr($personnel, 1);
			?>


			<div style="display: grid; grid-template-columns: auto auto; grid-gap: 10px; margin: 10px;">
				<input type="date" onchange="GR({'from': this.value});" value="<?= $from; ?>">
				<input type="date" onchange="GR({'to': this.value});" value="<?= $to; ?>">
			</div>

			<div>
				<table border='1' style="border-collapse: collapse;">
					<tr>
						<th>Сотрудник</th>
						<th>Оклад</th>
						<th>Смены</th>
						<th>Часы</th>
						<th>Премия</th>
						<th>Допы</th>
						<th>Возвратов</th>
					</tr>
					<?
					usort($personnel, function ($a, $b) {
						return
						mb_strtolower($a['usersLastName'] . $a['usersFirstName'] . $a['usersMiddleName']) <=>
						mb_strtolower($b['usersLastName'] . $b['usersFirstName'] . $b['usersMiddleName'])
						;
					});
					$times[__LINE__][] = microtime(1) - $start;
					$output = "";
					foreach ($personnel as $user) {
						if (!($user['idusers'] ?? false)) {
							continue;
						}
						?>
						<tr <?= $user['idusers'] == ($_GET['user'] ?? false) ? ' style="background-color: silver;"' : ''; ?>>
							<td>
								<a target="_blank" href="/pages/personal/options.php?employee=<?= $user['idusers'] ?>"><?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?></a>
							</td><? $output .= trim($user['usersLastName']) . ' ' . trim($user['usersFirstName']) ?>
							<td class="R">

								<?=
								max(getPaymentsValue($user['userPaymentsValues'], 9, $to),
										getPaymentsValue($user['userPaymentsValues'], 1, $to),
										getPaymentsValue($user['userPaymentsValues'], 6, $to), 0);
								?><?
								$output .= "\t" . max(getPaymentsValue($user['userPaymentsValues'], 9, $to),
												getPaymentsValue($user['userPaymentsValues'], 1, $to),
												getPaymentsValue($user['userPaymentsValues'], 6, $to), 0);
								?>
							</td>

							<td class="C"><a href="<?= GR2(['show' => 'schedule', 'user' => $user['idusers']]); ?>"><?=
									round((array_reduce(($user['schedule'] ?? []), function ($carry, $item) {
												$carry += $item['totalShift'];
												return $carry;
											})), 2);
									$output .= "\t" . number_format(
													(array_reduce(($user['schedule'] ?? []), function ($carry, $item) {
														$carry += $item['totalShift'];
														return $carry;
													})), 2, ",", "");
									?></a></td>
							<td class="C"><?
								$seconds = array_reduce(($user['schedule'] ?? []), function ($carry, $item) {
									$carry += $item['seconds'] ?? 0;
									return $carry;
								});
//								round(() / 3600, 0);
								if ($seconds) {
									$H = floor($seconds / 3600);
									$i = floor(($seconds - $H * 3600) / 60);
									print $H . ':' . ($i < 10 ? ('0' . $i) : $i);
									$output .= "\t" . $H . ':' . ($i < 10 ? ('0' . $i) : $i);
								} else {
									$output .= "\t";
								}
								?></td>
							<td class="C"><?=
								($user['reward'] ?? '-' );
								$output .= "\t" . ($user['reward'] ?? '-' );
								?></td>
							<td class="C"><a href="<?= GR2(['show' => 'wage', 'user' => $user['idusers']]); ?>">
									<?= array_sum(array_column(($user['wage'] ?? []), 'wage')); ?><? $output .= "\t" . array_sum(array_column(($user['wage'] ?? []), 'wage')); ?>
								</a></td>

							<td class="C"><a href="<?= GR2(['show' => 'sales', 'user' => $user['idusers']]); ?>"><?=
									round((array_reduce(($user['salesPayments'] ?? []), function ($carry, $item)use ($user) {
												$percent = getPaymentsValue($user['userPaymentsValues'], 39, $item['date']);
												if ($item['participants'] ?? false) {
													$carry += (($percent / 100) * $item['cancellationSumm']) / $item['participants'];
												}
												return $carry;
											})), 0);
									$output .= "\t" . round((array_reduce(($user['salesPayments'] ?? []), function ($carry, $item)use ($user) {
														$percent = getPaymentsValue($user['userPaymentsValues'], 39, $item['date']);
														if ($item['participants'] ?? false) {
															$carry += (($percent / 100) * $item['cancellationSumm']) / $item['participants'];
														}
														return $carry;
													})), 0);
									?></a></td>
						</tr>
						<?
						$output .= "\n";
					}
					$times[__LINE__][] = microtime(1) - $start;
					?>
				</table>
				<br>
				<?
				if (isset($_GET['show'])) {
					?>
					<a href="<?= GR2(['show' => null, 'user' => null]); ?>"><i class="fas fa-times-circle" style="color: red;"></i></a>
					<?
				}

				$times[__LINE__][] = microtime(1) - $start;
				if (($_GET['show'] ?? false) == 'sales') {
					$sales = array_search_2d(($_GET['user'] ?? false), $personnel, 'idusers')['salesPayments'] ?? [];
					$times[__LINE__][] = microtime(1) - $start;
//					printr($sales);
					?><table border='1' style="border-collapse: collapse;">
						<tr>
							<th>#</th>
							<th>Дата</th>
							<th>Клиент</th>
							<th>Сумма<br>продажи, р.</th>
							<th>Тип/id</th>
							<th>Сумма<br> платежей, р.</th>
							<th>Кол-во <br>участ-<br>ников</th>
							<th>%</th>
							<th>Доля&Cross;%, р.</th>
						</tr>
						<?
						$n = 0;

						foreach ($sales as $sale) {
							$percent = getPaymentsValue($user['userPaymentsValues'], 39, $sale['date']);
							?>
							<tr>
								<td class="R"><?= (++$n); ?></td>
								<td class="R"><?= date("d.m.Y", strtotime($sale['date'])); ?>
									<? // printr($sale);                                    ?>
								</td>
								<td>
									<?= $sale['clientsLName']; ?>
									<?= $sale['clientsFName']; ?>
									<?= $sale['clientsMName']; ?>
								</td>
								<td class="R">
									<?= round($sale['f_salesSumm']); ?>
								</td>

								<td class="C">
									<?= ['1' => 'П', '2' => 'В', '3' => 'Р'][$sale['f_salesType']]; ?>/<?= ($sale['idsale']); ?>
								</td>

								<td class="R" style="<?= round($sale['paymentValue']) != round($sale['f_salesSumm']) ? 'color: red;' : ''; ?>">
									<?= round($sale['paymentValue'] + $sale['cancellationSumm']); ?>
								</td>

								<td class="C">
									<?= round($sale['participants']); ?>
								</td>
								<td class="C">
									<?= $percent; ?>
								</td>
								<td  class="R">
									<?= $sale['participants'] ? round($percent / 100 * ($sale['paymentValue'] + $sale['cancellationSumm']) / ($sale['participants'])) : 'дел. 0'; ?>
								</td>
							</tr>
							<?
						}
						$times[__LINE__][] = microtime(1) - $start;
						?>
					</table>
					<?
				}
				if (($_GET['show'] ?? false) == 'schedule') {
					$times[__LINE__][] = microtime(1) - $start;
					$schedule = array_search_2d(($_GET['user'] ?? false), $personnel, 'idusers')['schedule'] ?? [];
//					printr(array_column($schedule, 'clients'));
					ksort($schedule);
					$times[__LINE__][] = microtime(1) - $start;
					?><table border='1' style="border-collapse: collapse;">
						<tr>
							<th>#</th>
							<th>Дата</th>
							<th>Приход</th>
							<th>Смена, с...по</th>
							<th>Уход</th>
							<th>Отработано,<br>час:мин</th>
							<th>Полная<br>смена,<br>часов</th>
							<th>Смена<br>отработано,<br>часть</th>
							<th>Ставка, <br>р*ч</th>
							<th>Ставка,<br> р*смена</th>
							<th>К оплате</th>


						</tr>
						<?
						$n = 0;
						/////////////////////////////				/////////////////////////////				/////////////////////////////
						foreach ($schedule as $date => $day) {
							?>
							<tr>
								<td class="R"><?= (++$n); ?></td>
								<td class="C"><?= date("d.m", strtotime($date)); ?></td>
								<td class="C"><?= ($day['fact'] ?? false) ? date("H:i", strtotime(min($day['fact']))) : '-'; ?></td>
								<td class="C"><?= ($day['plan'] ?? false) ? date("H:i", strtotime(min($day['plan']))) : '-'; ?>...<?= ($day['plan'] ?? false) ? date("H:i", strtotime(max($day['plan']))) : '-'; ?></td>
								<td class="C"><?= ($day['fact'] ?? false) ? date("H:i", strtotime(max($day['fact']))) : '-'; ?></td>
								<td class="C"><?= ($day['fact'] ?? false) ? secondsToTimeShort(strtotime(max($day['fact'])) - strtotime(min($day['fact']))) : '-'; ?></td>

								<td class="C"><?= ($day['UPV']['10'] ?? '-'); ?></td>
								<td class="C"><?= ($day['totalShift'] ?? '-'); ?></td>
								<td class="C"><?= ($day['rate'] ?? '-'); ?></td>
								<td class="C"><?= ($day['rateShift'] ?? '-'); ?></td>
								<td class="C"><?= round(($day['seconds'] ?? 0) * ($day['rate'] ?? 0) / (60 * 60)); ?></td>

							</tr>
							<?
						}
//						printr($schedule, 1);
						$times[__LINE__][] = microtime(1) - $start;
						?>
					</table>
					<?
				}


				/////////////////////////////				/////////////////////////////				/////////////////////////////

				if (($_GET['show'] ?? false) == 'clients') {
					$times[__LINE__][] = microtime(1) - $start;
					$dates = array_search_2d(($_GET['user'] ?? false), $personnel, 'idusers')['schedule'] ?? [];
//					printr($dates);
//					usort($newclients, function ($a, $b) {
//						return $a['clientsAddedAt'] <=> $b['clientsAddedAt'];
//					});
					$times[__LINE__][] = microtime(1) - $start;
					?><table border='1' style="border-collapse: collapse;">
						<tr>
							<th>№</th>
							<th>Дата</th>
							<th>Клиент</th>
							<th>Источник</th>
						</tr>
						<?
						$n = 0;
						foreach ($dates as $date => $clients) {
							foreach (($clients['clients'] ?? []) as $client) {
								?>
								<tr>
									<td><?= (++$n); ?></td>
									<td>
										<?= date("d.m", strtotime($date)); ?>
									</td>
									<td><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>">
											<?= $client['clientsLName']; ?>
											<?= $client['clientsFName']; ?>
											<?= $client['clientsMName']; ?>
										</a>
									</td>
									<td class="C">
										<?= $client['clientsSourcesLabel'] ?? 'Не указано'; ?>
									</td>
								</tr>
								<?
							}
						}
						$times[__LINE__][] = microtime(1) - $start;
						?>
					</table>
					<?
				}

				/////////////////////////////				/////////////////////////////				/////////////////////////////
				if (($_GET['show'] ?? false) == 'wage') {
					$times[__LINE__][] = microtime(1) - $start;
					$wage = array_search_2d(($_GET['user'] ?? false), $personnel, 'idusers')['wage'] ?? [];
					$times[__LINE__][] = microtime(1) - $start;
//
//					printr($dates);
//					usort($newclients, function ($a, $b) {
//						return $a['clientsAddedAt'] <=> $b['clientsAddedAt'];
//					});
					?><table border='1' style="border-collapse: collapse;">
						<tr>
							<th>№</th>
							<th>Дата</th>
							<th>Клиент</th>
							<th>Услуга</th>
							<th>к-во</th>
							<th>аб</th>
							<th>цен</th>
							<th>З.П.</th>
						</tr>
						<?
						$n = 0;
						$daysumm = 0;
						$day = '';

						foreach ($wage as $service) {
							?>
							<?
							if ($day != $service['date']) {
								if ($day) {
									?>

									<tr>
										<th class="R" colspan="7"><?= date("d.m", strtotime($day)); ?></th>
										<th><?= $daysumm; ?></th>
									</tr>
								<? } ?>
								<?
								$daysumm = 0;
								$day = $service['date'];
							}
							?>
							<tr <?= (($_GET['highlight'] ?? false) == $service['idservicesApplied']) ? ' style="background-color: lightblue;"' : ''; ?>>
								<td><?= (++$n); ?></td>
								<td>
									<? // printr($service);                                          ?>
									<a href="/pages/reception/?client=<?= $service['client']; ?>&date=<?= $service['date']; ?>&highlight=<?= $service['idservicesApplied']; ?>" target="_blank"><?= date("d.m", strtotime($service['date'])); ?></a>
								</td>
								<td>
									<a href="/pages/offlinecall/schedule.php?client=<?= $service['client']; ?>&date=<?= $service['date']; ?>" target="_blank">
										<?= $service["clientsLName"]; ?>
										<?= $service["clientsFName"]; ?>
									</a>
								</td>
								<td>
									<a href="/pages/services/index.php?service=<?= $service['id']; ?>" target="_blank">
										<?= $service['name']; ?>
									</a>
								</td>

								<td class="C">
									<?= $service['qty']; ?>
								</td>
								<td class="C">
									<?= $service['contract']; ?>

								</td>
								<td class="R">
									<?= round($service['price'] ?? 0); ?>
								</td>

								<td class="R">
									<?= $service['wage']; ?>
									<? $daysumm += $service['wage']; ?>
								</td>

							</tr>
							<?
						}
						$times[__LINE__][] = microtime(1) - $start;
						?>
					</table>
					<?
				}


				if (($_GET['show'] ?? false) == 'creditpersonnel') {
					$times[__LINE__][] = microtime(1) - $start;
					$sales = array_search_2d(($_GET['user'] ?? false), $personnel, 'idusers')['creditpersonnel'] ?? [];
					$times[__LINE__][] = microtime(1) - $start;
//					printr($sales);
					?><table border='1' style="border-collapse: collapse;">
						<tr>
							<th>#</th>
							<th>Дата</th>
							<th>Клиент</th>
							<th>Сумма продажи</th>
							<th>Тип</th>
							<!--<th>Сумма платежей</th>-->
							<!--<th>Кол-во участников</th>-->
							<!--<th>Доля</th>-->
						</tr>
						<?
						$n = 0;

						foreach ($sales as $sale) {
							?>
							<tr>
								<td class="R"><?= (++$n); ?></td>
								<td class="R"><?= date("d.m.Y", strtotime($sale["f_salesDate"])); ?>
									<? // printr($sale);                                          ?>
								</td>
								<td>
									<?= $sale["clientsLName"]; ?>
									<?= $sale["clientsFName"]; ?>
									<?= $sale["clientsMName"]; ?>
								</td>
								<td>
									<?= round($sale["f_salesSumm"]); ?>
								</td>
								<td>
									<?= ['1' => 'Первичная', '2' => 'Повторная', '3' => 'Разовая'][$sale["f_salesType"]]; ?><br>
									<? round($sale["idf_sales"]); ?>
								</td>
							</tr>
							<?
						}
						$times[__LINE__][] = microtime(1) - $start;
						?>
					</table>
					<?
				}
				?>
			</div>
			<textarea style="width: 100%;" onclick="this.select();"><?= $output; ?></textarea>
		</div>
	</div>
	<?
}
//printr($times, 1);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
printr($PGT);

