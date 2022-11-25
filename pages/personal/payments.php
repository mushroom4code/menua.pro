<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
?>
<?
$start = microtime(1);

if (R(121)
) {

	if (
			($_POST['action'] ?? '') == 'saveUserPayment' &&
			($_POST['user'] ?? false) &&
			($_POST['from'] ?? false) &&
			($_POST['to'] ?? false) &&
			($_POST['paymentValue'] ?? false)
	) {
//		[action] => saveUserPayment
//		[user] => 176
//		[from] => 2021-04-01
//		[to] => 2021-04-15
//		[paymentValue] => 52500
//		printr($_POST);
//		idusersPayments, usersPaymentsUser, usersPaymentsFrom, usersPaymentsTo, usersPaymentsAmount, usersPaymentsTime, usersPaymentsBy
		if (mysqlQuery("INSERT INTO `usersPayments` SET "
						. " `usersPaymentsUser`='" . mres($_POST['user']) . "',"
						. " `usersPaymentsFrom`='" . mres($_POST['from']) . "',"
						. " `usersPaymentsTo`='" . mres($_POST['to']) . "',"
						. " `usersPaymentsAmount`='" . mres($_POST['paymentValue']) . "',"
						. " `usersPaymentsBy` = '" . $_USER['id'] . "'")) {
			header("Location: " . GR());
			die();
		} else {
			print '<H1>Ошибка</H1>';
			die();
		}
	}
}
include 'includes/top.php';
if (!R(121)) {
	?>
	<div class="box-body" style="background-color: white; margin: 5px; min-height: 400px;">
		E401R121
	</div>
	<?
} else {

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

	if ($_GET['from'] ?? false) {
		$_SESSION['userPaymentsFrom'] = $_GET['from'];
	}
	if ($_GET['to'] ?? false) {
		$_SESSION['userPaymentsTo'] = $_GET['to'];
	}


	$from = $_GET['from'] ?? $_SESSION['userPaymentsFrom'] ?? '';
	$to = $_GET['to'] ?? $_SESSION['userPaymentsTo'] ?? '';
	?>
	<style>
		.tooltip {display: grid; z-index: 10; top: 100%; right: 0px; background-color: #EEE;}
		.hidden {display: none;}
	</style>

	<div class="box-body" style="background-color: white; margin: 5px; min-height: 400px;">
		<div style="text-align: center; padding-bottom: 15px;">
			<select onchange="GR({from: this.selectedOptions[0].dataset.from, to: this.selectedOptions[0].dataset.to});">
				<option></option>
	<?
	$theDate = '2021-01-01';
	while (strtotime($theDate) < time()) {
		$time = strtotime($theDate);
		$year = date("Y", $time);
		$month = date("m", $time);
		?>
					<option <?= ($from == date("Y-m-01", $time) && ($to == date("Y-m-15", $time))) ? ' selected' : '' ?> data-from="<?= date("Y-m-01", $time); ?>"  data-to="<?= date("Y-m-15", $time); ?>"><?= date("01.m.Y", $time); ?> - <?= date("15.m.Y", $time); ?></option>
					<option <?= ($from == date("Y-m-16", $time) && ($to == date("Y-m-t", $time))) ? ' selected' : '' ?>  data-from="<?= date("Y-m-16", $time); ?>" data-to="<?= date("Y-m-t", $time); ?>"><?= date("16.m.Y", $time); ?> - <?= date("t.m.Y", $time); ?></option>
		<?
		$theDate = date("Y-m-d", 24 * 60 * 60 + strtotime(date("Y-m-t", $time)));
	}
	?>
			</select>
		</div>

	<? ?>

		<?
		if ($from && $to && (strtotime($to) >= strtotime($from))) {
			?>
			<!--			<div class="lightGrid" style="display: grid; grid-template-columns: auto  auto  auto auto;">
							<div style="display: contents;" class="B">
								<div style="display: flex; align-items: center; justify-content: center;"><span style="text-align: center;">Остаток за<br>прошлый период</span></div>
								<div style="display: flex; align-items: center; justify-content: center;">Начислено</div>
								<div style="display: flex; align-items: center; justify-content: center;">К выдаче</div>
								<div style="display: flex; align-items: center; justify-content: center;">Выдать</div>
							</div>
						</div>-->
		<?
		$userPaymentsValues = query2array(mysqlQuery("SELECT * FROM `userPaymentsValues` WHERE `userPaymentsValuesUser` = '" . mres($_GET['employee']) . "'"));

		usort($userPaymentsValues, function ($a, $b) {
			if ($a['userPaymentsValuesType'] != $b['userPaymentsValuesType']) {
				return $a['userPaymentsValuesType'] <=> $b['userPaymentsValuesType'];
			}
			if ($a['userPaymentsValuesDate'] != $b['userPaymentsValuesDate']) {
				return $a['userPaymentsValuesDate'] <=> $b['userPaymentsValuesDate'];
			}
			return $b['iduserPaymentsValues'] <=> $a['iduserPaymentsValues'];
		});

//			printr($userPaymentsValues);
		if (date("Ym", strtotime($from)) === date("Ym", strtotime($to))) {//В пределах одного месяца
			$monthStart = date("Y-m-01", strtotime($from));
			$monthEnd = date("Y-m-t", strtotime($from));

			function getUsersSchedule($user, $date) {
				return mfa(mysqlQuery("SELECT * FROM `usersSchedule` WHERE `usersScheduleUser` = '" . mres($user) . "' AND `usersScheduleDate` = '" . mres($date) . "'"));
			}

//				function getShiftAmount($seconds, $full) {
//					if (!$full) {
//						return null;
//					}
//				}

			function getWorkingTime($user, $date) {
				$shift = query2array(mysqlQuery("SELECT * FROM `fingerLog` WHERE `fingerLogTime`>='" . ($date . ' 06:00:00') . "' AND  `fingerLogTime`<='" . ($date . ' 23:59:59') . "' AND `fingerLogUser`='" . $user . "' "));
				if (count($shift)) {
					usort($shift, function ($a, $b) {
						return $a['fingerLogTime'] <=> $b['fingerLogTime'];
					});
					print $shift[0]['fingerLogTime'];
					print $shift[count($shift) - 1]['fingerLogTime'];

					return strtotime($shift[count($shift) - 1]['fingerLogTime']) - strtotime($shift[0]['fingerLogTime']);
				}
				return null;
			}

			function getPaymentsValue($userPaymentsValues, $userPaymentsValuesType, $userPaymentsValuesDate) {//опции по оплате
				$typePayments = array_filter($userPaymentsValues, function ($userPaymentsValue) use ($userPaymentsValuesType, $userPaymentsValuesDate) {

					return ($userPaymentsValue['userPaymentsValuesType'] == $userPaymentsValuesType) && strtotime($userPaymentsValue['userPaymentsValuesDate']) <= strtotime($userPaymentsValuesDate);
				});
				if (count($typePayments)) {
					usort($typePayments, function ($a, $b) {
						return strtotime($b['userPaymentsValuesDate']) <=> strtotime($a['userPaymentsValuesDate']);
					});
					return floatval($typePayments[0]['userPaymentsValuesValue']);
				} else {
					return 0;
				}
			}

			$countAllSales = getPaymentsValue($userPaymentsValues, 27, $from);
			$coordinatorSales = array_values(array_filter(query2array(mysqlQuery(""
											. " SELECT * "
											. " FROM `f_sales` "
											. ($countAllSales ? "" : "LEFT JOIN `f_salesToCoord` ON (`f_salesToCoordSalesID`=`idf_sales`)")
											. " WHERE"
											. "  (isnull(`f_salesCancellationDate`) OR `f_salesCancellationDate`>'$monthEnd')"
											. " AND `f_salesDate`>='$monthStart' AND `f_salesDate`<='$monthEnd'"
											. " AND (ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales`),0)+"
											. "  ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0))>=`f_salesSumm`"
											. ($countAllSales ? "" : " AND `f_salesToCoordCoord` = '" . mres($_GET['employee']) . "'")
											. "")), function ($f_sale) {
								return getAE($f_sale['f_salesSumm'], $f_sale['f_salesDate']) > 0;
							}));

			$coordinatorCanceledSales = array_values(array_filter(query2array(mysqlQuery(""
											. " SELECT * "
											. " FROM `f_sales` "
											. ($countAllSales ? "" : "LEFT JOIN `f_salesToCoord` ON (`f_salesToCoordSalesID`=`idf_sales`)")
											. " WHERE"
											. " NOT isnull(`f_salesCancellationDate`)"
											. " AND `f_salesCancellationDate`>='$monthStart' AND `f_salesCancellationDate`<='$monthEnd'"
											. " AND `f_salesDate`<'$monthStart'"
											. " AND (ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales`),0)+"
											. "  ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0))>=`f_salesSumm`"
											. ($countAllSales ? "" : " AND `f_salesToCoordCoord` = '" . mres($_GET['employee']) . "'")
											. "")), function ($f_sale) {
								return getAE($f_sale['f_salesSumm'], $f_sale['f_salesDate']) > 0;
							}));
//				printr($coordinatorCanceledSales);
			$allSalesSumm = array_sum(array_column($coordinatorSales, 'f_salesSumm')) - array_sum(array_column($coordinatorCanceledSales, 'f_salesSumm'));
			?>


				В пределах одного месяца (<?= date("m", strtotime($from)); ?>)<br>
				Дней в месяце <?= $ndaysMonth = date("t", strtotime($from)); ?><br>
				Дней в периоде <?= $ndaysPeriod = (1 + (strtotime($to) - strtotime($from)) / (60 * 60 * 24)); ?><br>

				<div style="display: inline-block;">
					<div class="lightGrid" style="display: grid; grid-template-columns: repeat(14, auto);">
						<div style="display: contents">
							<div class="C B">День</div>
							<div class="C B"><i class="far fa-calendar-alt"></i></div>
							<div class="C B">1</div>
							<div class="C B">3</div>
							<div class="C B">6</div>
							<div class="C B">7</div>

							<div class="C B">9</div>
							<div class="C B">10</div>
							<div class="C B">11</div>
							<div class="C B">Д</div>
							<div class="C B">М</div>
							<div class="C B">С</div>
							<div class="C B">К</div>
							<div class="C B">Р</div>
						</div>
			<?
			$dn = 0;
			$total = [];
			$dopsTotal = 0;

			$recruitingResults = query2array(mysqlQuery("SELECT * FROM `recruiting` WHERE `recruitingDate`>='$monthStart' AND `recruitingDate`<='$monthEnd'"));
			$recruitingResultsByUser = [];
			$recruitingResultsByDate = [];
			$recruitingValues = query2array(mysqlQuery("SELECT * FROM `recruitingValues` WHERE `recruitingValuesDate`<='$monthEnd'"));

			usort($recruitingValues, function ($a, $b) {
				if ($b['recruitingValuesDate'] <=> $a['recruitingValuesDate']) {
					return $b['recruitingValuesDate'] <=> $a['recruitingValuesDate'];
				}
				if ($b['recruitingValuesQty'] <=> $a['recruitingValuesQty']) {
					return $b['recruitingValuesQty'] <=> $a['recruitingValuesQty'];
				}
			});

			foreach ($recruitingResults as $recruitingResult) {
				$recruitingResultsByUser[$recruitingResult['recruitingUser']]['bydate'][$recruitingResult['recruitingDate']]['qty'] = $recruitingResult['recruitingQty'];
			}
			foreach ($recruitingResultsByUser as $iduser => &$recruitingResultByUser2) {
				$recruitingResultByUser2['total'] = array_sum(array_column($recruitingResultByUser2['bydate'], 'qty'));

				foreach ($recruitingResultByUser2['bydate'] as $date => &$param) {
					$param['wage'] = getRecruitingWage($recruitingValues, $date, $recruitingResultByUser2['total']);
					$param['totalwage'] = $param['wage'] * $param['qty'];
					$recruitingResultsByDate[$date]['byuser'][$iduser]['qty'] = $param['qty'];
					$recruitingResultsByDate[$date]['totalqty'] = ($recruitingResultsByDate[$date]['totalqty'] ?? 0) + $param['qty'];
				}
			}

//						printr($recruitingResultsByDate);
//						printr($recruitingResultsByUser);
			for ($time = strtotime($from); $time <= strtotime($to); $time += 60 * 60 * 24) {
				$dn++;

				/* 10	Продолжительность смены, часов */
				$shiftDuration = getPaymentsValue($userPaymentsValues, 10, date("Y-m-d", $time));
				/////////////////////10	Продолжительность смены, часов/////////////////////////////

				$conciderDuty = getPaymentsValue($userPaymentsValues, 16, date("Y-m-d", $time)); //Дежурные смены

				/* 1 Оклад за смену */
				$payPerShift = getPaymentsValue($userPaymentsValues, 1, date("Y-m-d", $time));

				$usersSchedule = getUsersSchedule($_GET['employee'], date("Y-m-d", $time));

				if ($usersSchedule) {
					$plannedShiftDuration = strtotime($usersSchedule['usersScheduleTo']) - strtotime($usersSchedule['usersScheduleFrom']);
				} else {
					$plannedShiftDuration = 0;
				}

				$isShiftPlanned = is_array($usersSchedule);
				$workingTime = getWorkingTime($_GET['employee'], date("Y-m-d", $time));
				if ((!$conciderDuty || ($conciderDuty && ($usersSchedule['usersScheduleDuty'] ?? null))) &&
						$workingTime !== null && $shiftDuration && $isShiftPlanned) {
					$workingTime = min($plannedShiftDuration, $workingTime);
					$shift = min(1, ($workingTime / (60 * 60)) / $shiftDuration);
				} else {
					$shift = 0;
				}
				$totalPayPerShift = ($totalPayPerShift ?? 0) + $payPerShift * $shift;
				/////////////////////1	Оклад за смену/////////////////////////////

				/* 3	Премия за оформление кредита */
				$payPerSale = getPaymentsValue($userPaymentsValues, 3, date("Y-m-d", $time));

				$credits = query2array(mysqlQuery("SELECT * FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`) WHERE `f_salesCreditManager` = '" . mres($_GET['employee']) . "' AND `f_salesDate` = '" . date("Y-m-d", $time) . "';"));

				$salesQty = 0;
				$AEs = [];
				foreach ($credits as $credit) {
					$AE = getAE($credit['f_salesSumm'], date("Y-m-d", $time));
					if ($AE > 0) {
						$AEs[] = $AE;
						$salesQty++;
					}
				}

				$payments = query2array(mysqlQuery("SELECT (unix_timestamp('" . date("Y-m-d", $time) . "')-unix_timestamp(f_salesDate)) AS `f_salesAge`,f_salesSumm, paymentsSum FROM f_sales left join (SELECT saleid, sum(`sum`) as `paymentsSum` FROM (SELECT f_paymentsSalesID as `saleid`, (f_paymentsAmount) as `sum` FROM f_payments union all select f_creditsSalesID as `saleid`, (f_creditsSumm) as `sum` from f_credits) AS `temp` GROUP BY `saleid`) as `payments` on (`saleid` = `idf_sales`) WHERE idf_sales IN ((SELECT f_paymentsSalesID FROM f_payments WHERE f_paymentsDate >= '" . date("Y-m-d", $time) . " 00:00:00' AND f_paymentsDate <= '" . date("Y-m-d", $time) . " 23:59:59')) AND f_salesCreditManager = '" . mres($_GET['employee']) . "';"
				));

				foreach ($payments as $payment) {
					$AE = getAE($payment['f_salesSumm'], date("Y-m-d", $time));
					if ($AE > 0 && $payment['paymentsSum'] >= $payment['f_salesSumm'] && $payment['f_salesAge'] < (60 * 60 * 24 * 31)) {
						$AEs[] = $AE;
						$salesQty++;
					}
				}
				$totalpayPerSale = ($totalpayPerSale ?? 0) + $payPerSale * $salesQty;
				/////////////////////3	Премия за оформление кредита/////////////////////////////



				/* 6	 оклад за месяц */
				$payPerMonth = getPaymentsValue($userPaymentsValues, 6, date("Y-m-d", $time)) / $ndaysMonth;
				$totalpayPerMonth = ($totalpayPerMonth ?? 0) + $payPerMonth;
				/////////////////////6 оклад за месяц/////////////////////////////

				/* 7	Официальный оклад за месяц */
				$payPerMonthOficial = getPaymentsValue($userPaymentsValues, 7, date("Y-m-d", $time)) / $ndaysMonth;
				$totalpayPerMonthOficial = ($totalpayPerMonthOficial ?? 0) + $payPerMonthOficial;
				/////////////////////7	Официальный оклад за месяц/////////////////////////////


				/* 9 Оплата за 1 час, р. */
				$payPerHour = getPaymentsValue($userPaymentsValues, 9, date("Y-m-d", $time));

				if ($workingTime !== null) {
					$workingTimeHours = ($workingTime / (60 * 60));
				} else {
					$workingTimeHours = 0;
				}

				$totalpayPerHour = ($totalpayPerHour ?? 0) + $payPerHour * $workingTimeHours;
				/////////////////////9 Оплата за 1 час, р./////////////////////////////

				/* A	Премиальные за участие в продаже (3%) */
				$paymentsSQL = "SELECT `idf_sales`,`f_salesClient`, (UNIX_TIMESTAMP('" . date("Y-m-d", $time) . "') - UNIX_TIMESTAMP(`f_salesDate`)) AS `f_salesAge`, `f_salesSumm`, `paymentsSum`, `f_salesDate`,`f_paymentDate`, (SELECT COUNT(1) FROM `f_salesToPersonal` WHERE `f_salesToPersonalSalesID` = `saleid`) AS `participants` FROM `f_sales` LEFT JOIN `f_salesToPersonal` ON (`f_salesToPersonalSalesID` = `idf_sales`) LEFT JOIN (SELECT * FROM ((SELECT `f_paymentsSalesID` AS `saleid`, (`f_paymentsAmount`) AS `paymentsSum`, `f_paymentsDate` AS `f_paymentDate` FROM `f_payments`) UNION ALL (SELECT `f_creditsSalesID` AS `saleid`, `f_creditsSumm` AS `paymentsSum`, `f_salesDate` AS `f_paymentDate` FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`))) AS `temp`) AS `payments` ON (`saleid` = `idf_sales`) WHERE (`idf_sales` IN ((SELECT `f_paymentsSalesID` FROM `f_payments` WHERE `f_paymentsDate` >= '" . date("Y-m-d", $time) . " 00:00:00' AND `f_paymentsDate` <= '" . date("Y-m-d", $time) . " 23:59:59')) OR `f_salesDate`='" . date("Y-m-d", $time) . "' ) AND `f_paymentDate` <= '" . date("Y-m-d", $time) . " 23:59:59' AND `f_salesToPersonalUser` = '" . mres($_GET['employee']) . "' ; ";
//							print $paymentsSQL;

				$payments = query2array(mysqlQuery($paymentsSQL));
//							die();
				$saleBonus = 0;
				$percent = (getPaymentsValue($userPaymentsValues, 11, date("Y-m-d", $time)) ?? 0) / 100;
				$tooltip = '';
				$sales = [];
				foreach ($payments as $payment) {
					$sales[$payment['idf_sales']]['payments'][] = $payment;
				}
//							printr($sales);

				foreach ($sales as $idf_sales => $sale) {
					if (count($sale['payments'] ?? [])) {
						usort($sale['payments'], function ($a, $b) {
							return strtotime($a['f_paymentDate']) <=> strtotime($b['f_paymentDate']);
						});
						$sale['paymentsSumm'] = array_sum(array_column($sale['payments'], 'paymentsSum'));
					}
					$paymentsSumm = 0;
					foreach ($sale['payments'] as $payment) {
						if ($tooltip == '') {
							$tooltip = '<div style="display: contents;" class="C B">'
									. '<div>№ Абонемента</div>'
									. '<div>Дата<br>продажи</div>'
									. '<div>Дата<br>платежа</div>'
									. '<div>Сумма<br>платежа</div>'
									. '<div>Суммарно</div>'
									. '<div>Стоимость<br>абонемента</div>'
									. '<div>Уч-ков</div>'
									. '<div>Коэфф</div>'
									. '<div>Доля<br>' . ($percent * 100) . '%</div></div>';
						}
						//saleid, paymentsSum, participants
						if ($payment['participants']) {
							$errors = [];
							$paymentsSumm += $payment['paymentsSum'];
							if ($payment['f_salesAge'] >= (60 * 60 * 24 * 31)) {
								$errors[] = '<i class="fas fa-exclamation-triangle" style="color: orange;" title="Не зачёт. Абонементу ' . human_plural_form(round($payment['f_salesAge'] / (60 * 60 * 24)), ['день', 'дня', 'дней'], true) . '"></i>';
							}
							if ($sale['paymentsSumm'] < $payment['f_salesSumm']) {
								$errors[] = '<i class="fas fa-exclamation-triangle" style="color: red;" title="Абонемент оплачен не полностью."></i>';
							}
							$AE = getAE($payment['f_salesSumm'], date("Y-m-d", $time));
							if (!count($errors)) {
								$saleBonus += ($percent * $payment['paymentsSum']) / $payment['participants'];
							}

							$tooltip .= '<div style="display: contents;">'
									. '<div><a target="_blank" href="/pages/checkout/payments.php?client=' . $payment['f_salesClient'] . '&contract=' . $payment['idf_sales'] . '">' . $payment['idf_sales'] . '</a></div>'
									. '<div>' . date("d.m.Y", strtotime($payment['f_salesDate'])) . '</div>'
									. '<div>' . date("d.m.Y", strtotime($payment['f_paymentDate'])) . '</div>'
									. '<div>' . nf($payment['paymentsSum']) . '</div><div>' . nf($paymentsSumm) . '</div>'
									. '<div>' . nf($payment['f_salesSumm']) . '</div>'
									. '<div class="C">' . $payment['participants'] . '</div>'
									. '<div class="C">' . $AE . '</div>'
									. '<div>' . nf(($AE * $percent * $payment['paymentsSum']) / $payment['participants'], 2) . '&nbsp;' . (count($errors) ? implode('', $errors) : '<i class="fas fa-check-circle" style="color: green;"></i>') . '</div>'
									. '</div>';
						}
					}
				}
				$saleBonusTotal = ($saleBonusTotal ?? 0) + $saleBonus;
//getWage($service['idservicesApplied'])
				/////////////////////////////*11	Премиальные за участие в продаже*/
				//
				//
//								13	Без доп.оплаты		cb
				$skipWage = getPaymentsValue($userPaymentsValues, 13, date("Y-m-d", $time));
				$isGiftCounts = getPaymentsValue($userPaymentsValues, 15, date("Y-m-d", $time));
//							Считаем допы только если:
				$wage = [];
				$topayDayWages = 0;

				$tooltip2 = '';
				if (!$skipWage) {//Если не установлена галочка "без доп.оплаты"
					if ((!$conciderDuty) || ($conciderDuty && !($usersSchedule['usersScheduleDuty'] ?? null))) {
						//Не учитывать дежурные, или (учитывать дежурные и смена не дежурная)
						$servicesApplied = query2array(mysqlQuery("SELECT * FROM `servicesApplied` LEFT JOIN `services` ON (`idservices`=`servicesAppliedService`) WHERE `servicesAppliedDate` = '" . date("Y-m-d", $time) . "' AND `servicesAppliedPersonal` = '" . mres($_GET['employee']) . "' AND isnull(`servicesAppliedDeleted`) AND NOT isnull(`servicesAppliedFineshed`)"));
						$tooltip2 = '';
						foreach ($servicesApplied as $serviceApplied) {

							if ($tooltip2 == '') {
								$tooltip2 = '<div style="display: contents; background-white;" class="C B">'
										. '<div>ID операции</div>'
										. '<div>Услуга</div>'
										. '<div>Абонемент</div>'
										. '<div>Сумма</div>'
										. '<div>Подарочная</div>'
										. '<div>Допы</div>'
										. '<div>Кол-во</div>'
										. '<div>К оплате</div>'
										. '</div>';
							}

							$wageAmount = getWage($serviceApplied['idservicesApplied']);
							$isFree = !(($serviceApplied['servicesAppliedContract']) || (!$serviceApplied['servicesAppliedContract'] && intval($serviceApplied['servicesAppliedPrice'])));
							$wage[] = [
								'idservicesApplied' => $serviceApplied['idservicesApplied'],
								'servicesName' => $serviceApplied['servicesName'],
								'contract' => $serviceApplied['servicesAppliedContract'],
								'summ' => $serviceApplied['servicesAppliedPrice'],
								'isFree' => intval($isFree),
								'wage' => $wageAmount,
								'toPay' => (($isFree && $isGiftCounts) || (!$isFree)) ? ($wageAmount * $serviceApplied['servicesAppliedQty']) : 0
							];
							$tooltip2 .= '<div style="display: contents;">'
									. '<div>' . $serviceApplied['idservicesApplied'] . '</div>'
									. '<div class="L">' . $serviceApplied['servicesName'] . '</div>'
									. '<div><a target="_blank" href="/pages/checkout/payments.php?client=' . $serviceApplied['servicesAppliedClient'] . '&contract=' . $serviceApplied['servicesAppliedContract'] . '">' . $serviceApplied['servicesAppliedContract'] . '</a></div>'
									. '<div class="R">' . nf($serviceApplied['servicesAppliedPrice']) . '</div>'
									. '<div class="C">' . ($isFree ? 'Да' : 'Нет') . '</div>'
									. '<div class="R">' . nf($wageAmount) . '</div>'
									. '<div class="C">' . $serviceApplied['servicesAppliedQty'] . '</div>'
									. '<div class="R">' . nf((($isFree && $isGiftCounts) || (!$isFree)) ? ($wageAmount * $serviceApplied['servicesAppliedQty']) : 0) . '</div>'
									. '</div>';
						}
						if (count($wage)) {
							$topayDayWages = array_sum(array_column($wage, 'toPay'));
							$dopsTotal = ($dopsTotal ?? 0) + $topayDayWages;
						}
					}
				}

				///////////////////////////////*ДОПЫ*/







				if (1) {/* МАРКЕТИНГ */
//							$skipWage = getPaymentsValue($userPaymentsValues, 13, date("Y-m-d", $time));
					$nday2paymentTypeId = [8, 17, 18, 19, 20, 21, 22];
					$dayOfTheWeek = date("N", $time) - 1;
					$paymentPerClientWeekDay = getPaymentsValue($userPaymentsValues, $nday2paymentTypeId[$dayOfTheWeek], date("Y-m-d", $time));

					$score = query2array(mysqlQuery("SELECT * FROM `score` LEFT JOIN `clients` ON (`idclients` = `scoreClient`) WHERE `scoreDate`='" . date("Y-m-d", $time) . "'"));
					usort($score, function ($a, $b) {
						return $a['idscore'] <=> $b['idscore'];
					});

					$scoreClients = [];
					foreach ($score as $scoreClient) {
						$scoreClients[$scoreClient['scoreClient']] = $scoreClient;
					}
					foreach ($scoreClients as &$scoreClient2) {
						$servicesApplied = query2array(mysqlQuery("SELECT"
										. " `idservicesApplied`,"
										. " `servicesAppliedBy`,"
										. " `servicesName`,"
										. " `servicesAppliedDeletedBy`,"
										. " `servicesAppliedService`,"
										. " `servicesAppliedFineshed`,"
										. " `daleteReasonsName`,"
										//
										. " `SAby`.`idusers` as `SABYidusers`,"
										. " `servicesAppliedAt`,"
										. " `SAby`.`usersLastName` as `SABYusersLastName`,"
										. " `SAby`.`usersFirstName` as `SABYusersFirstName`,"
										. " `SAby`.`usersMiddleName` as `SABYusersMiddleName`,"
										//
										. " `SADel`.`idusers` as `SSADelidusers`,"
										. " `servicesAppliedDeleted`,"
										. " `SADel`.`usersLastName` as `SADelusersLastName`,"
										. " `SADel`.`usersFirstName` as `SADelusersFirstName`,"
										. " `SADel`.`usersMiddleName` as `SADelusersMiddleName`"
										//
										. " FROM `servicesApplied`"
										. " LEFT JOIN `services` ON (`idservices`=`servicesAppliedService`)"
										. " LEFT JOIN `users` AS `SAby` ON (`SAby`.`idusers` = `servicesAppliedBy`)"
										. " LEFT JOIN `users` AS `SADel` ON (`SADel`.`idusers` = `servicesAppliedDeletedBy`)"
										. " LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`)"
										. " WHERE `servicesAppliedClient` = '" . $scoreClient2['idclients'] . "' AND `servicesAppliedDate` ='" . $scoreClient2['scoreDate'] . "'"));
						usort($servicesApplied, function ($a, $b) {
							return $a['idservicesApplied'] <=> $b['idservicesApplied'];
						});
						$scoreClient2['servicesAppliedBy'] = $servicesApplied[0]['servicesAppliedBy'] ?? 0;
						$scoreClient2['servicesApplied'] = $servicesApplied;
						$scoreClient2['diagnostics'] = array_filter($servicesApplied, function ($service) {
							return $service['servicesAppliedService'] == 362;
						});
						$scoreClient2['procedures'] = array_filter($servicesApplied, function ($service) {
							return $service['servicesAppliedService'] != 362;
						});
					}
					$theEmployee = $_GET['employee'];
					$myClients = array_values(array_filter($scoreClients, function ($client) use ($theEmployee) {
								return $client['scoreMarket'] == 1 && $client['servicesAppliedBy'] == $theEmployee;
							}));

					$totalMarketingClientsWage = ($totalMarketingClientsWage ?? 0) + count($myClients) * $paymentPerClientWeekDay;
					/* надо найти всех клиентов этого сотрудника. */
					$tooltipData['marketing'][mydates("Y-m-d", $time)] = obj2array($myClients);
					/* ----МАРКЕТИНГ */
				}




				if (1) {/* СЕРВИС */
//23	Сервис. Премия за клиента прошедшего диагностику
//24	Сервис. Премия за клиента прошедшего процедуры
					$diagnosticsReward = getPaymentsValue($userPaymentsValues, 23, date("Y-m-d", $time));
					$serviceAppliedReward = getPaymentsValue($userPaymentsValues, 24, date("Y-m-d", $time));
					$diagnosticsAnyReward = getPaymentsValue($userPaymentsValues, 29, date("Y-m-d", $time));
					$serviceAppliedAnyReward = getPaymentsValue($userPaymentsValues, 30, date("Y-m-d", $time));
					$serviceServicesClients = query2array(mysqlQuery("SELECT  "
									. " `idclients`,"
									. " `clientsLName`,"
									. " `clientsFName`,"
									. " `clientsMName`,"
									. " `scoreMarket`,"
									. " `idservicesApplied`,"
									. " `clientsVisitsTime`,"
									. " `idservices`,"
									. " `servicesAppliedBy`,"
									. " `servicesName`,"
									. " `servicesAppliedDeletedBy`,"
									. " `servicesAppliedService`,"
									. " `servicesAppliedFineshed`,"
									. " `daleteReasonsName`,"
									. "(SELECT DATE(`clientsVisitsTime`) FROM `clientsVisits` WHERE `idclientsVisits` = (SELECT MAX(idclientsVisits) FROM `clientsVisits` WHERE `clientsVisitsClient` =  `idclients`) AND `clientsVisitsTime`<'" . date("Y-m-d", $time) . "') AS `lastVizit`,"
									. "(SELECT GROUP_CONCAT(`idf_sales` SEPARATOR ', ') AS `contracts` FROM `f_sales` WHERE `f_salesClient`= `idclients` AND `f_salesDate`<'" . date("Y-m-d", $time) . "')  AS `contracts`,"
//
									. " `SAby`.`idusers` as `SABYidusers`,"
									. " `servicesAppliedAt`,"
									. " `SAby`.`usersLastName` as `SABYusersLastName`,"
									. " `SAby`.`usersFirstName` as `SABYusersFirstName`,"
									. " `SAby`.`usersMiddleName` as `SABYusersMiddleName`,"
									//
									. " `SADel`.`idusers` as `SSADelidusers`,"
									. " `servicesAppliedDeleted`,"
									. " `SADel`.`usersLastName` as `SADelusersLastName`,"
									. " `SADel`.`usersFirstName` as `SADelusersFirstName`,"
									. " `SADel`.`usersMiddleName` as `SADelusersMiddleName`"
									//
									. " FROM `servicesApplied`"
									. " LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`)"
									. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
									. " LEFT JOIN `score` ON (`idscore` = (SELECT MAX(`idscore`) FROM `score` WHERE `scoreClient` = `servicesAppliedClient` AND `scoreDate` = `servicesAppliedDate`))"
									. " LEFT JOIN `users` AS `SAby` ON (`SAby`.`idusers` = `servicesAppliedBy`)"
									. " LEFT JOIN `users` AS `SADel` ON (`SADel`.`idusers` = `servicesAppliedDeletedBy`)"
									. " LEFT JOIN `clients` ON (`idclients`=`servicesAppliedClient`)"
									. " LEFT JOIN `clientsVisits` ON (`idclientsVisits`=(SELECT MAX(`idclientsVisits`) FROM `clientsVisits` WHERE `clientsVisitsClient` = `servicesAppliedClient` AND DATE(`clientsVisitsTime`)='" . date("Y-m-d", $time) . "'))"
									. " WHERE  `servicesAppliedDate`='" . date("Y-m-d", $time) . "'"));
//								printr($serviceServicesClients);
					$serviceClientsAll = [];
					foreach ($serviceServicesClients as $service) {
						$serviceClientsAll[$service['idclients']]['info'] = [
							'idclients' => ($service['idclients'] ?? null),
							'date' => date("Y-m-d", $time),
							'clientsLName' => ($service['clientsLName'] ?? null),
							'clientsFName' => ($service['clientsFName'] ?? null),
							'clientsMName' => ($service['clientsMName'] ?? null),
							'scoreMarket' => ($service['scoreMarket'] ?? null),
							'contracts' => ($service['contracts'] ?? null),
							'lastVizit' => ($service['lastVizit'] ?? null),
							'lastVizitAge' => ($service['lastVizit'] ?? null) ? (($time - mystrtotime($service['lastVizit'])) / (60 * 60 * 24)) : null
						];
						$serviceClientsAll[$service['idclients']]['services'][] = [
							'idservices' => ($service['idservices'] ?? null),
							'servicesName' => ($service['servicesName'] ?? null),
							'idservicesApplied' => ($service['idservicesApplied'] ?? null),
							'servicesAppliedFineshed' => ($service['servicesAppliedFineshed'] ?? null),
							'clientsVisitsTime' => ($service['clientsVisitsTime'] ?? null),
							//
							'servicesAppliedAt' => ($service['servicesAppliedAt'] ?? null),
							'servicesAppliedBy' => ($service['servicesAppliedBy'] ?? null),
							'SABYusersLastName' => ($service['SABYusersLastName'] ?? null),
							'SABYusersFirstName' => ($service['SABYusersFirstName'] ?? null),
							'SABYusersMiddleName' => ($service['SABYusersMiddleName'] ?? null),
							//Удаление
							'servicesAppliedDeleted' => ($service['servicesAppliedDeleted'] ?? null),
							'servicesAppliedDeletedBy' => ($service['servicesAppliedDeletedBy'] ?? null),
							'daleteReasonsName' => ($service['daleteReasonsName'] ?? null),
							'SADelusersLastName' => ($service['SADelusersLastName'] ?? null),
							'SADelusersFirstName' => ($service['SADelusersFirstName'] ?? null),
							'SADelusersMiddleName' => ($service['SADelusersMiddleName'] ?? null),
						];
					}

					$date = date("Y-m-d", $time);
					$AEss = query2array(mysqlQuery("SELECT * FROM `AEvalues` WHERE `AEvaluesDate`<='$date'"));
					foreach ($serviceClientsAll as &$serviceClientsAll2) {
						if ($serviceClientsAll2['info']['contracts']) {
							$serviceClientsAll2['f_sales'] = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesSumm`,`f_salesCancellationDate`	 FROM `f_sales` WHERE `idf_sales` in (" . $serviceClientsAll2['info']['contracts'] . ")"));
							$serviceClientsAll2['info']['haveContracts'] = count(array_values(array_filter($serviceClientsAll2['f_sales'], function ($sale) use ($AEss, $date) {
														return getAEs($AEss, $sale['f_salesSumm'], $date);
													}))) > 0;
						} else {
							$serviceClientsAll2['f_sales'] = [];
							$serviceClientsAll2['info']['haveContracts'] = false;
						}
						$serviceClientsAll2['info']['cold'] = ($serviceClientsAll2['info']['lastVizitAge'] == null || $serviceClientsAll2['info']['lastVizitAge'] >= 180) && !$serviceClientsAll2['info']['haveContracts'];

						usort($serviceClientsAll2['services'], function ($a, $b) {
							return $a['idservicesApplied'] <=> $b['idservicesApplied'];
						});

						$serviceClientsAll2['diagnostics'] = array_values(array_filter($serviceClientsAll2['services'], function ($service) {
									return $service['idservices'] == 362 && $service['servicesAppliedDeleted'] == null;
								}));

						$serviceClientsAll2['procedures'] = array_values(array_filter($serviceClientsAll2['services'], function ($service) {
									return $service['idservices'] != 362 && $service['clientsVisitsTime'] != null; // && $service['servicesAppliedFineshed'] != null;
								}));
					}
//								printr($serviceClientsAll);
//								printr($serviceClientsProcedures);
//								printr(count($serviceClients));
//								printr($serviceClientsAll);
					$serviceClientsWithDiagnostics = array_values(array_filter($serviceClientsAll, function ($client) {
								return ($client['info']['scoreMarket'] ?? false) == 1 && ($client['diagnostics'][0]['servicesAppliedBy'] ?? '') == $_GET['employee'];
							}));
//								printr(count($serviceClientsWithDiagnostics));
//								printr(($serviceClientsAll));
					$serviceClientsWithoutDiagnostics = array_values(array_filter($serviceClientsAll, function ($client) {
								return count($client['diagnostics']) == 0 && count($client['procedures']) > 0 && ($client['procedures'][0]['servicesAppliedBy'] ?? '') == $_GET['employee'];
							}));
					$serviceAnyClientsWithDiagnostics = array_values(array_filter($serviceClientsAll, function ($client) {
								return ($client['info']['scoreMarket'] ?? false) == 1; // && ($client['diagnostics'][0]['servicesAppliedBy'] ?? '') == $_GET['employee'];
							}));
//								printr(count($serviceClientsWithDiagnostics));
//								printr(($serviceClientsAll));
					$serviceAnyClientsWithoutDiagnostics = array_values(array_filter($serviceClientsAll, function ($client) {
								return count($client['diagnostics']) == 0 && count($client['procedures']) > 0; // && ($client['procedures'][0]['servicesAppliedBy'] ?? '') == $_GET['employee'];
							}));
//								printr(count($serviceClientsWithoutDiagnostics));
					$tooltipData['service'][mydates("Y-m-d", $time)] = [
						'services' => array_values($serviceClientsAll),
						'diagnostics' => $serviceClientsWithDiagnostics,
						'servicesApplied' => $serviceClientsWithoutDiagnostics
					];
//								printr($serviceClientsAll);
					/* ------------СЕРВИС */
					$ServiceWage = ($diagnosticsReward ?? 0) * count($serviceClientsWithDiagnostics) +
							($serviceAppliedReward ?? 0) * count($serviceClientsWithoutDiagnostics) +
							($diagnosticsAnyReward ?? 0) * count($serviceAnyClientsWithDiagnostics) +
							($serviceAppliedAnyReward ?? 0) * count($serviceAnyClientsWithoutDiagnostics)
					;

					$totalServiceWage = ($totalServiceWage ?? 0) + $ServiceWage;
				}


				/* Координаторы */
				if (1) {
					$LT = query2array(mysqlQuery("SELECT * "
									. " FROM `LT` "
									. " WHERE"
									. " `LTuser` = '" . mres($_GET['employee']) . "'"
									. " AND `LTtype` = '1'"
									. " AND `LTdate`= (SELECT MAX(`LTdate`) FROM `LT` WHERE `LTdate`<='" . mydates("Y-m-d", $time) . "' AND `LTuser` = '" . mres($_GET['employee']) . "' AND `LTtype` = '1')"));

					usort($LT, function ($a, $b) {
						if ($a['LTdate'] <=> $b['LTdate']) {
							return $b['LTdate'] <=> $a['LTdate'];
						}

						if ($a['LTvalue'] <=> $b['LTvalue']) {
							return floatval($a['LTvalue']) <=> floatval($b['LTvalue']);
						}
					});

					$ltreward = LT($LT, mres($_GET['employee']), $allSalesSumm, mydates("Y-m-d", $time));
					$coordinatorSalesToday = array_values(array_filter($coordinatorSales, function ($f_sale) use ($time) {
								return $f_sale['f_salesDate'] == mydates("Y-m-d", $time);
							}));
					$coordinatorCanceledSalesToday = array_values(array_filter($coordinatorCanceledSales, function ($f_sale) use ($time) {
								return $f_sale['f_salesCancellationDate'] == mydates("Y-m-d", $time);
							}));
//							printr($coordinatorCanceledSales);
//									print mydates("Y-m-d", $time);

					$coordinatorSalesTodaySumm = (
							array_sum(array_column($coordinatorSalesToday, 'f_salesSumm')) -
							array_sum(array_column($coordinatorCanceledSalesToday, 'f_salesSumm'))
							) * $ltreward / 100;
					$coordinatorSalesTotalSumm = ($coordinatorSalesTotalSumm ?? 0) + ($coordinatorSalesTodaySumm);
				}

				/* //Координаторы */

				/* РЕКРУТИНГ */
				if (1) {
					$recrutingReward = getPaymentsValue($userPaymentsValues, 26, date("Y-m-d", $time));
					$recrutingPayments = $recruitingResultsByUser[$_GET['employee']]['bydate'][mydates("Y-m-d", $time)] ?? [];
					$recrutingPayments['totalQty'] = ($recruitingResultsByDate[mydates("Y-m-d", $time)]['totalqty'] ?? 0);
					$recrutingPayments['recrutingReward'] = ($recrutingReward);
					$recrutingPaymentsSumm = ($recrutingPayments['totalwage'] ?? 0) + $recrutingPayments['totalQty'] * $recrutingReward;
					$totalRecrutingPayments = ($totalRecrutingPayments ?? 0) + $recrutingPaymentsSumm;
//								printr($recrutingPayments);
				}

				/* //РЕКРУТИНГ */
				?>
							<div style="display: contents;">
								<div style="color: silver; font-size: 0.5em; text-align: center;"><?= $dn; ?></div>
								<div><a href="/pages/reception/?personal=<?= $_GET['employee']; ?>&date=<?= mydates("Y-m-d", $time); ?>" target="_blank"><?= mydates("d.m.Y", $time); ?></a></div>
								<div class="R"><?= ($payPerShift * $shift) ? (nf($payPerShift * $shift, 2) . ' (' . round($shift, 2) . ') ' . (($usersSchedule['usersScheduleDuty'] ?? false) ? ' Д' : '')) : '<div class="C">-</div>'; ?></div>
								<div class="R"><?= ($payPerSale * $salesQty) ? (nf($payPerSale * $salesQty, 2) . ' (' . $salesQty . ')') : '<div class="C">-</div>'; ?> </div>
								<div class="R"><?= $payPerMonth ? nf($payPerMonth, 2) : '<div class="C">-</div>'; ?></div>
								<div class="R"><?= $payPerMonthOficial ? nf($payPerMonthOficial, 2) : '<div class="C">-</div>'; ?></div>

								<div class="R">
				<?
				$shiftErrors = [];
				if (($isShiftPlanned && $workingTime === null)) {
					$shiftErrors[] = '<i class="fas fa-exclamation-triangle" style="color: red;" title="Смена есть в расписании, но нет данных о приходе."></i>';
				}
				if (($isShiftPlanned && $workingTime === 0)) {
					$shiftErrors[] = '<i class="fas fa-exclamation-triangle" style="color: pink;" title="Нет отметки об уходе"></i>';
				}
				if (!$isShiftPlanned && $workingTime !== null) {
					$shiftErrors[] = '<i class="fas fa-exclamation-triangle" style="color: orange;" title="Смены нет в расписании, но есть данные о приходе."></i>';
				}


				if ($workingTimeHours || count($shiftErrors)) {
					?>
										<?= nf($payPerHour * $workingTimeHours, 2); ?>
										(<?= round($workingTimeHours, 1); ?>ч.) <?= implode('', $shiftErrors); ?><?
									} else {
										?><div class="C">-</div><?
									}
									?>

								</div>
								<div class="C"><?= $shiftDuration ? round($shiftDuration, 2) : '<div class="C">-</div>'; ?></div>
								<div class="R" oncontextmenu="this.querySelector('.tooltip').classList.toggle('hidden'); return false;"><?= $saleBonus ? nf($saleBonus, 2) : '<div class="C">-</div>'; ?><div class="lightGrid tooltip hidden" style="grid-template-columns: auto auto auto auto auto auto auto auto auto; position: absolute; border: 5px solid #00aaaac2; white-space: nowrap; background-color: white !important;"><?= $tooltip; ?></div></div>
								<div class="R" oncontextmenu="this.querySelector('.tooltip').classList.toggle('hidden'); return false;">
									<div class = "lightGrid tooltip hidden" style = "grid-template-columns: auto auto auto auto auto auto auto auto; position: absolute; border: 5px solid #00aaaac2; white-space: nowrap;"><?= $tooltip2; ?></div>
				<?= intval($topayDayWages) ? nf($topayDayWages) : '<div class="C">-</div>'; ?></div>
								<div class="R" data-function="showTooltip" data-column="marketing" data-date="<?= date("Y-m-d", $time); ?>">
									<?= count($myClients ?? []) * $paymentPerClientWeekDay; ?>
									<div style="color: gray; font-size: 0.7em; line-height: 0.7em;"><?= count($myClients) ? (count($myClients) . ' &Cross; ' . $paymentPerClientWeekDay) : ''; ?></div>

								</div>
								<div class="R" data-function="showTooltip" data-column="service" data-date="<?= date("Y-m-d", $time); ?>"><?= round($ServiceWage); ?>
									<div style="color: gray; font-size: 0.7em; line-height: 0.7em;">
				<?= count($serviceClientsWithDiagnostics); ?>&Cross;<?= ($diagnosticsReward ?? 0); ?> +
										<?= count($serviceClientsWithoutDiagnostics); ?>&Cross;<?= ($serviceAppliedReward ?? 0); ?> +<br>
										<?= count($serviceAnyClientsWithDiagnostics); ?>&Cross;<?= ($diagnosticsAnyReward ?? 0); ?> +
										<?= count($serviceAnyClientsWithoutDiagnostics); ?>&Cross;<?= ($serviceAppliedAnyReward ?? 0); ?>


									</div>

								</div>
								<div class="R"><?
						//printr($ltreward . 'x' . $coordinatorSalesTodaySumm . '=' . ($coordinatorSalesTodaySumm * $ltreward) ?? []);
						print nf($coordinatorSalesTodaySumm);
										?></div>


								<div class="R"><?= round($recrutingPaymentsSumm ?? 0); ?>
									<div style="color: gray; font-size: 0.7em; line-height: 0.7em;">
				<?= round($recrutingPayments['qty'] ?? 0); ?>&Cross;<?= round($recrutingPayments['wage'] ?? 0); ?> +
										<?= round($recrutingPayments['totalQty'] ?? 0); ?>&Cross;<?= round($recrutingPayments['recrutingReward'] ?? 0); ?>

									</div>

								</div>

							</div>
			<? } ?>
						<div style="display: contents">

							<div style="grid-column: span 2;" class="R B">Итого:</div>
							<div class="R B"><?= $totalPayPerShift ? nf($totalPayPerShift, 2) : ''; ?></div>
							<div class="R B"><?= $totalpayPerSale ? nf($totalpayPerSale, 2) : ''; ?></div>
							<div class="R B"><?= $totalpayPerMonth ? nf($totalpayPerMonth, 2) : ''; ?></div>
							<div class="R B"><?= $totalpayPerMonthOficial ? nf($totalpayPerMonthOficial, 2) : ''; ?></div>
							<div class="R B"><?= $totalpayPerHour ? nf($totalpayPerHour, 2) : ''; ?></div>
							<div><!-- за час --></div>
							<div class="R B"><?= $saleBonusTotal ? nf($saleBonusTotal, 2) : ''; ?></div>
							<div class="R B"><?= $dopsTotal ? nf($dopsTotal, 2) : ''; ?></div>
							<div class="R B"><?= $totalMarketingClientsWage ? nf($totalMarketingClientsWage, 2) : ''; ?></div>
							<div class="R B"><?= $totalServiceWage ? nf($totalServiceWage, 2) : ''; ?></div>
							<div class="R B"><?= nf($coordinatorSalesTotalSumm); ?></div>
							<div class="R B"><?= nf($totalRecrutingPayments); ?></div>
						</div>
						<div style="display: contents">
							<div style="grid-column: span 13;" class="B R">Итого ЗП:</div>
							<div class="B R"><?= nf($totalPayPerShift + $totalpayPerSale + $totalpayPerMonth + $totalpayPerHour + $saleBonusTotal + $dopsTotal + $totalMarketingClientsWage + $coordinatorSalesTotalSumm + $totalServiceWage + $totalRecrutingPayments, 2); ?></div>
						</div>
						<div style="display: contents">
							<div style="grid-column: span 13;" class="B R">ЗП минус оф:</div>
							<div class="B R"><?= nf($totalPayPerShift + $totalpayPerSale + $totalpayPerMonth + $totalpayPerHour + $saleBonusTotal + $dopsTotal + $totalMarketingClientsWage + $coordinatorSalesTotalSumm + $totalServiceWage + $totalRecrutingPayments - $totalpayPerMonthOficial, 2); ?></div>
						</div>
						<div></div>
					</div>
				</div>
				<div class="C" style="padding: 30px;">
					<div class="L" style="display: inline-block;">
						<form action="<?= GR(); ?>" method="POST">
							<input type="hidden" name="action" value="saveUserPayment">
							<input type="hidden" name="user" value="<?= $employee['idusers']; ?>">
							<input type="hidden" name="from" value="<?= $from; ?>">
							<input type="hidden" name="to" value="<?= $to; ?>">
							<input type="text" name="paymentValue" oninput="digon();" style="display: inline-block; width: auto;">
							<input type="submit" value="Выдать" style="display: inline-block; width: auto;">
						</form>
					</div>
				</div>
				<script>

					let tooltipData = JSON.parse('<?= json_encode($tooltipData ?? []) ?>');


				</script>


				<div>	1	Оклад за смену</div>
				<div>	3	Премия за оформление абонемента</div>
				<div>	6	Оклад за месяц (разделить на <?= $ndaysMonth; ?>дней)</div>
				<div>	7	Официальный оклад за месяц (разделить на <?= $ndaysMonth; ?>дней)</div>
				<div>	9	Оплата за 1 час, р.
					<ul style="list-style: none; margin-left: 20px;">
						<li><i class="fas fa-exclamation-triangle" style="color: red;"></i> Смена есть в расписании, но нет данных о приходе.</li>
						<li><i class="fas fa-exclamation-triangle" style="color: pink;" ></i> Нет отметки об уходе</li>
						<li><i class="fas fa-exclamation-triangle" style="color: orange;" ></i> Смены нет в расписании, но есть данные о приходе.</li>
					</ul>
				</div>


				<div>	10	Продолжительность смены, часов</div>
				<div>	11	Премиальные за участие в продаже</div>



			<?
		} else {
			?>За пределами одного месяца<?
			}
		} else {
			?>
			Укажите период
			<?
		}
		?>



	</div>
	PGT: <?= microtime(1) - $start; ?>c;
<? } ?>

<? include 'includes/bottom.php'; ?>