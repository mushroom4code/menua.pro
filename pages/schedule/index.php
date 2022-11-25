<?php
$pageTitle = 'График работы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(51)) {
	
}

function getSalesShareSumm($user, $date) {
	return mfa(mysqlQuery("SELECT 
    SUM(`value` / `participants`) AS `summ`
FROM
    (SELECT 
        `f_paymentsAmount` AS `value`,
            (SELECT 
                    COUNT(1)
                FROM
                    `f_salesRoles`
                WHERE
                    `f_salesRolesSale` = `f_paymentsSalesID`
                        AND `f_salesRolesRole` IN (1 , 2, 3)) AS `participants`
    FROM
        `f_payments`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`)
    WHERE
        DATE(`f_paymentsDate`) = '$date'
            AND (SELECT 
                COUNT(1)
            FROM
                `f_salesRoles`
            WHERE
                `f_salesRolesSale` = `idf_sales`
                    AND `f_salesRolesRole` IN (1 , 2, 3)
                    AND `f_salesRolesUser` = $user) = 1 UNION ALL SELECT 
        `f_creditsSumm` AS `value`,
            (SELECT 
                    COUNT(1)
                FROM
                    `f_salesRoles`
                WHERE
                    `f_salesRolesSale` = `idf_sales`
                        AND `f_salesRolesRole` IN (1 , 2, 3)) AS `participants`
    FROM
        `f_credits`
    LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)
    WHERE
        `f_salesDate` = '$date'
            AND (SELECT 
                COUNT(1)
            FROM
                `f_salesRoles`
            WHERE
                `f_salesRolesSale` = `idf_sales`
                    AND `f_salesRolesRole` IN (1 , 2, 3)
                    AND `f_salesRolesUser` = $user) = 1 UNION ALL SELECT 
        - `f_salesCancellationSumm` AS `value`,
            (SELECT 
                    COUNT(1)
                FROM
                    `f_salesRoles`
                WHERE
                    `f_salesRolesSale` = `idf_sales`
                        AND `f_salesRolesRole` IN (1 , 2, 3)) AS `participants`
    FROM
        `f_sales`
    WHERE
        DATE(`f_salesCancellationDate`) = '$date'
            AND (SELECT 
                COUNT(1)
            FROM
                `f_salesRoles`
            WHERE
                `f_salesRolesSale` = `idf_sales`
                    AND `f_salesRolesRole` IN (1 , 2, 3)
                    AND `f_salesRolesUser` = $user) = 1) AS `payments`;"))['summ'] ?? null;
}

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

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(51)) {
	?>E403R27<?
} else {

	$nDays = date("t", mktime(12, 0, 0, ($_GET['m'] ?? date("m")), 1, ($_GET['Y'] ?? date("Y"))));
//idfingerLog, fingerLogTime, fingerLogData, fingerLogUser

	$from = ($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-01';
	$to = ($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $nDays;
	$USER = $_GET['user'] ?? $_USER['id'];

	$scheduleSQL = "SELECT *,"
			. "UNIX_TIMESTAMP(`usersScheduleFrom`) AS `usersScheduleFromTS`,"
			. "UNIX_TIMESTAMP(`usersScheduleTo`) AS `usersScheduleToTS`"
			. " FROM `usersSchedule`"
			. " WHERE "
			. " `usersScheduleUser` = " . mres($USER) . " "
			. " AND `usersScheduleDate`>='" . $from . "'"
			. " AND `usersScheduleDate`<='" . $to . "'"
			. " AND `usersScheduleHalfs` IN ('01','10','11')";

	$userPaymentsValues = query2array(mysqlQuery("SELECT *"
					. " FROM `userPaymentsValues`"
					. " WHERE `userPaymentsValuesUser` = '" . mres($USER) . "'"
					. " AND `userPaymentsValuesDate`<='" . $to . "'"
					. " ORDER BY `userPaymentsValuesDate` DESC, `iduserPaymentsValues` "));

//	9

	$hourpayments = array_values(array_filter($userPaymentsValues, function ($userPaymentsValue) {
				return $userPaymentsValue['userPaymentsValuesType'] == 9;
			})); //
	if (count($hourpayments ?? []) && ($hourpayments[0] ?? false)) {
		$displayHours = true;
	} else {
		$displayHours = false;
	}

	if (0 && $_USER['id'] == 176) {
		printr($userPaymentsValues);
		printr($hourpayments);
	}



	$fingerlog = query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`fingerLogTime`) AS `fingerLogTimeTS`, DATE(`fingerLogTime`) AS `fingerLogDate` FROM `fingerLog` "
					. " WHERE `fingerLogUser` = " . mres($USER) . ""
					. " AND `fingerLogTime`>= '" . $from . ' 00:00:00' . "'"
					. " AND `fingerLogTime`<='" . $to . " 23:59:59'"));

	$schedule = query2array(mysqlQuery($scheduleSQL));

	$scheduleMap = [];
	$fingerLogMap = [];
	foreach ($schedule as $scheduleEntry) {
		$scheduleMap[$scheduleEntry['usersScheduleDate']]['from'] = $scheduleEntry['usersScheduleFromTS'];
		$scheduleMap[$scheduleEntry['usersScheduleDate']]['to'] = $scheduleEntry['usersScheduleToTS'];
	}

	foreach ($fingerlog as $fingerlogEntry) {
		$fingerLogMap[$fingerlogEntry['fingerLogDate']][] = $fingerlogEntry['fingerLogTimeTS'];
		$scheduleMap[$fingerlogEntry['fingerLogDate']]['finger'] = $fingerlogEntry['fingerLogTimeTS'];
	}



//	printr($fingerLogMap);
	?>
	<div class="neutral box">
		<div class="box-body">
			<h2 style="width: 90%;"><div style="display: inline-block;">
					<select onchange="GETreloc('m', this.value);">
						<?
						for ($m = 1; $m <= 12; $m++) {
							?><option value="<?= ($m < 10 ? '0' : '') . $m; ?>"<?= ($m == ($_GET['m'] ?? date("m")) ? ' selected' : ''); ?>><?= $_MONTHES['full']['nom'][$m]; ?></option><?
						}
						?>
					</select>
				</div>
				/
				<div style="display: inline-block;">
					<select onchange="GETreloc('Y', this.value);">
						<?
						for ($Y = date("Y", time() + 60 * 60 * 24 * 30); $Y >= 2020; $Y--) {
							?><option value="<?= $Y; ?>"<?= ($Y == ($_GET['Y'] ?? date("Y")) ? ' selected' : ''); ?>><?= $Y; ?></option><?
						}
						?>
					</select>
				</div></h2>

			<?
			ksort($scheduleMap);
//			printr($scheduleMap);
			if (count($scheduleMap)) {
				?>
				<div style="text-align: center;">
					<div style="display: inline-block; text-align: left;">
						<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6,auto);">
							<div style="display: contents;">
								<div style="display: flex; align-items: center; justify-content: center; font-weight: bold;">Дата</div>
								<div style="display: flex; align-items: center; justify-content: center; font-weight: bold; text-align: center;">Ваш<br>приход</div>
								<div style="display: flex; align-items: center; justify-content: center; font-weight: bold; text-align: center;">Смена<br>начало</div>
								<div style="display: flex; align-items: center; justify-content: center; font-weight: bold; text-align: center;">Смена<br>окончание</div>
								<div style="display: flex; align-items: center; justify-content: center; font-weight: bold; text-align: center;">Ваш<br>уход</div>
								<div style="display: flex; align-items: center; justify-content: center; font-weight: bold;"></div>

							</div>

							<?
							foreach ($scheduleMap as $date => $times) {
								if ($_USER['id'] == 176) {
//									printr($fingerLogMap[$date] ?? null);
								}
								$time = strtotime($date);
								$style = ''; //date("N", $time) > 5 ? ' style="background-color: pink;"' : '';
								?>

								<div style="display: contents;">
									<div class="C" <?= $style; ?>>
										<?= date("d", $time); ?> (<?= $_WEEKDAYS['short'][date("N", $time)]; ?>)
									</div>
									<div class="C"<?= $style; ?>>
										<?= ($fingerLogMap[$date] ?? false) ? date("H:i", min($fingerLogMap[$date])) : '-'; ?>
									</div>
									<div class="C"><?= ($times['from'] ?? false) ? date("H:i", $times['from']) : '-'; ?></div>
									<div class="C"><?= ($times['to'] ?? false) ? date("H:i", $times['to']) : '-'; ?></div>
									<div class="C">
										<?= ($fingerLogMap[$date] ?? false) ? (min($fingerLogMap[$date]) == max($fingerLogMap[$date]) ? ' - ' : date("H:i", max($fingerLogMap[$date]))) : '-'; ?>
									</div>



									<div style="text-align: center;">

										<?
										if (strtotime($date) == strtotime(date("Y-m-d"))) {
											?>
											<i class="fas fa-caret-square-right" style="color: darkblue;"></i>
											<?
										} elseif (strtotime($date) < strtotime(date("Y-m-d"))) {

											if ($fingerLogMap[$date] ?? 0) {
												$deltaFinger = max($fingerLogMap[$date]) - min($fingerLogMap[$date]);
												$deltaSchedule = max($scheduleMap[$date]) - min($scheduleMap[$date]);
												if ($deltaFinger >= $deltaSchedule) {
													?>
													<i class="fas fa-check-square" style="color: green;"></i>
													<?
												} else {
													?>
													<i class="fas fa-exclamation-triangle" style="color: orange;"></i>
													<?
												}
											} else {
												?>
												<i class="fas fa-exclamation-circle" style="color: red;"></i>
												<?
											}
										}
										?>


									</div>

								</div>

								<?
							}
							?>
						</div>
					</div>
				</div><?
			} else {
				?>
				<h3 style="text-align: center; padding: 20px 0px;">Нет данных за этот период.</h3><?
			}
			?>



		</div>
	</div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
