`<?php
$load['title'] = $pageTitle = 'ЗП ОБЩАЯ';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(136)) {

}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

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

if (!R(136)) {
	?>E403R136<?
} else {
	$start = microtime(true);
	$from = $_GET['from'] ?? mydates("Y-m-01");
	$to = $_GET['to'] ?? mydates("Y-m-d");

	$allusers = [];

	$spendsSQL = "SELECT
     `idWH_goods`,`WH_goodsName`, DATE(`WH_goodsOutDate`) AS `goodsOutDate`, SUM(`WH_goodsOutQty`) AS `qty`, `WH_goodsPrice`
FROM
    `WH_goodsOut`
        LEFT JOIN
    `WH_goods` ON (`idWH_goods` = `WH_goodsOutItem`)

WHERE
    `WH_goodsOutDate` >= '" . $from . " 00:00:00'
        AND `WH_goodsOutDate` <= '" . $to . " 23:59:59'
        AND ISNULL(`WH_goodsOutDeleted`)
GROUP BY `idWH_goods`, `WH_goodsOutDate`;";
//	print $spendsSQL;
	$spends = query2array(mysqlQuery($spendsSQL));
//	printr($spends);
	$warehouseSpends = [];
	foreach ($spends as $spend) {
		$warehouseSpends[$spend['goodsOutDate']] = ($warehouseSpends[$spend['goodsOutDate']] ?? 0) + ($spend['qty'] * $spend['WH_goodsPrice']);
	}
//	printr($warehouseSpends);
	if ($from && $to && (mystrtotime($to) >= mystrtotime($from))) {

		$ndaysMonth = date("t", strtotime($from));
		$ndaysPeriod = (1 + (strtotime($to) - strtotime($from)) / (60 * 60 * 24));

		$groups = query2array(mysqlQuery("SELECT * FROM `usersGroups`"
//						. " where `idusersGroups` = '12' "
						. "ORDER BY `usersGroupsSort`"));
		$userPaymentsTypes = query2array(mysqlQuery("SELECT * FROM `userPaymentsTypes`"));

		$AEs = query2array(mysqlQuery("SELECT * FROM `AEvalues` WHERE `AEvaluesDate`<='$to'"));

		$f_salesPeriod = query2array(mysqlQuery(""
						. "SELECT * "
						. "FROM `f_sales` "
						. "WHERE `f_salesDate`>='$from' AND `f_salesDate`<='$to'"));

//		printr(getAEs($AEs, 100000, '2021-04-05'));
		foreach ($f_salesPeriod as &$f_sale2) {
			$f_sale2['AE'] = getAEs($AEs, $f_sale2['f_salesSumm'], $f_sale2['f_salesDate']);
		}
//		printr($f_salesPeriod);

		foreach ($groups as &$group2) {
//				print "SELECT * FROM `users` WHERE `usersGroup` = '" . $group2['idusersGroups'] . "' AND isnull(`usersDeleted`) OR `usersDeleted`>'" . $from . " 00:00:00'<br>";
			$users = query2array(mysqlQuery("SELECT * "
							. "FROM `users` "
							. "WHERE `usersGroup` = '" . $group2['idusersGroups'] . "'"
							. " AND (isnull(`usersDeleted`) OR `usersDeleted`>'" . $from . " 00:00:00')"
//							. " AND `idusers` = 124 " //кварацхелия
							. " AND `usersAdded`<'" . $to . " 00:00:00'"));
			$group2['users'] = $users;
			$allusers = array_merge($allusers, $users);
		}

		//Получаем данные оплат труда по всем пользователям
		if (count($allusers)) {
			$usersPaymentsValues = query2array(mysqlQuery("SELECT * FROM `userPaymentsValues` WHERE `userPaymentsValuesUser` IN (" . implode(',', array_column($allusers, 'idusers')) . ")"));

			usort($usersPaymentsValues, function ($a, $b) {
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
		} else {
			$usersPaymentsValues = [];
		}
//		printr($usersPaymentsValues);

		/**/
//Получим рабочие смены
		$usersSchedule = query2array(mysqlQuery("SELECT * FROM `usersSchedule` WHERE `usersScheduleUser` IN (" . implode(',', array_column($allusers, 'idusers')) . ") AND `usersScheduleDate` >= '" . mres($from) . "' AND `usersScheduleDate` <= '" . mres($to) . "'"));
//		printr($usersSchedule);
		//Получаем данные о приходах
		$fingerLogTime = query2array(mysqlQuery("SELECT *, DATE(`fingerLogTime`) AS `fingerLogDate` FROM `fingerLog` WHERE `fingerLogUser` IN (" . implode(',', array_column($allusers, 'idusers')) . ") AND `fingerLogTime` >= '" . mres($from) . " 00:00:00' AND `fingerLogTime` <= '" . mres($to) . " 23:59:59'"));
		usort($fingerLogTime, function ($a, $b) {
			return $a['fingerLogTime'] <=> $b['fingerLogTime'];
		});

		//переносим данные по оплатам в пользователей
		foreach ($groups as &$group3) {
			foreach ($group3['users'] as &$user3) {

				///////////////////////данные
				$filtered = obj2array(array_filter($usersPaymentsValues, function ($usersPaymentsValue) use ($user3) {//фильтруем по пользователю.
							return $usersPaymentsValue['userPaymentsValuesUser'] == $user3['idusers'];
						}));
//				$user3['usersPaymentsValues'] = $filtered;
				for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
					foreach ($userPaymentsTypes as $userPaymentsType) {
						$user3['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][$userPaymentsType['iduserPaymentsTypes']] = getPaymentsValue($filtered, $userPaymentsType['iduserPaymentsTypes'], mydates("Y-m-d", $time));
					}
				}
//				$user3['userSchedule']

				for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {


					$userSchedule = obj2array(array_filter($usersSchedule, function ($userSchedule) use ($user3, $time) {//фильтруем по пользователю.
								return $userSchedule['usersScheduleUser'] == $user3['idusers'] && $userSchedule['usersScheduleDate'] == mydates("Y-m-d", $time);
							}));
					if (count($userSchedule) && ($userSchedule[0]['usersScheduleFrom'] ?? false) && ($userSchedule[0]['usersScheduleTo'] ?? false)) {
						$user3['userSchedule'][mydates("Y-m-d", $time)] = [
							'from' => $userSchedule[0]['usersScheduleFrom'],
							'to' => $userSchedule[0]['usersScheduleTo'],
							'isduty' => $userSchedule[0]['usersScheduleDuty'] ?? 0,
							'duration' => ceil(((mystrtotime($userSchedule[0]['usersScheduleTo']) - mystrtotime($userSchedule[0]['usersScheduleFrom'])) / 3600) - 0.25)
						];
					}

					$fingerLogTimefiltered = array_filter($fingerLogTime, function ($fingerLogTime) use ($user3, $time) {//фильтруем по пользователю.
						return $fingerLogTime['fingerLogUser'] == $user3['idusers'] && $fingerLogTime['fingerLogDate'] == mydates("Y-m-d", $time);
					});
					$fingerLogTimefiltered = obj2array($fingerLogTimefiltered);
					if (count($fingerLogTimefiltered)) {
						$user3['fingerLogTime'][mydates("Y-m-d", $time)] = [
							'from' => $fingerLogTimefiltered[0]['fingerLogTime'],
							'to' => $fingerLogTimefiltered[count($fingerLogTimefiltered) - 1]['fingerLogTime'],
							'duration' => ceil(((mystrtotime($fingerLogTimefiltered[count($fingerLogTimefiltered) - 1]['fingerLogTime']) - mystrtotime($fingerLogTimefiltered[0]['fingerLogTime'])) / 3600) - 0.25)
						];
						if ($user3['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][10] ?? false) {
							$user3['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] = max(0, min(1,
											(min(
													($user3['userSchedule'][mydates("Y-m-d", $time)]['duration'] ?? 0),
													($user3['fingerLogTime'][mydates("Y-m-d", $time)]['duration'] ?? 0)
											) / $user3['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][10])
											//[10] -продолжительность сменны берем минимальное значение из отработанного и установленного, делим на план смены из расписания
							));
						} else {
							$user3['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] = 0;
						}
					}

					$userFingerLogTime = obj2array($fingerLogTimefiltered);
				}
				///////////////////////------------------------------данные
				//
				/////////////////////////////////////////////////////////////////Расчёты
				//	1	Оклад за смену, р.
			}//users
		}//groups
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include '1.php'; //оклад за смену
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include '3.php'; //премия за оформление договора
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include '6.php'; //оклад за месяц
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include '7.php'; //официальный оклад за месяц
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include '9.php'; //Почасовая оплата
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include '11.php'; //Процент за участие в продаже
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include 'dops.php'; //Допы
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include 'recruiting.php'; //рекрутинг
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include 'coords.php'; //Координаторы
		$_MEMORY[__LINE__][] = (memory_get_usage());
		include 'total.php'; //суммирование
		$_MEMORY[__LINE__][] = (memory_get_usage());
//		printr($___mydates);
	}
	?>
	<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
	<div class="box neutral">
		<div class="box-body">
			<h2>Сводная</h2>
			<?
//			printr($usersPaymentsValues);
			?>
			<div style="display: inline-block;">
				<div><input type="checkbox" <?= ($_GET['showDetails'] ?? false) ? ' checked' : ''; ?> onclick="GR({showDetails: this.checked ? 'true' : null});" id="showDetails"><label for="showDetails">Показать подробности</label></div>
				<select onchange="GR({from: this.selectedOptions[0].dataset.from, to: this.selectedOptions[0].dataset.to});">
					<option></option>
					<?
					$theDate = '2021-01-01';
					while (mystrtotime($theDate) < time()) {
						$time = mystrtotime($theDate);
						$year = mydates("Y", $time);
						$month = mydates("m", $time);
						?>
						<option <?= ($from == mydates("Y-m-01", $time) && ($to == mydates("Y-m-15", $time))) ? ' selected' : '' ?> data-from="<?= mydates("Y-m-01", $time); ?>"  data-to="<?= mydates("Y-m-15", $time); ?>"><?= mydates("01.m.Y", $time); ?> - <?= mydates("15.m.Y", $time); ?></option>
						<option <?= ($from == mydates("Y-m-16", $time) && ($to == mydates("Y-m-t", $time))) ? ' selected' : '' ?>  data-from="<?= mydates("Y-m-16", $time); ?>" data-to="<?= mydates("Y-m-t", $time); ?>"><?= mydates("16.m.Y", $time); ?> - <?= mydates("t.m.Y", $time); ?></option>
						<?
						$theDate = mydates("Y-m-d", 24 * 60 * 60 + mystrtotime(mydates("Y-m-t", $time)));
					}
					?>
				</select>
				<div style="display: grid; grid-template-columns: auto auto; margin: 20px; grid-gap: 10px;">
					<input type="date" onchange="GETreloc('from', this.value);" value="<?= $from ?>">
					<input type="date" onchange="GETreloc('to', this.value);" value="<?= $to ?>">
				</div>
			</div>
			<!--Тут будет пиздец-->


			<div class="lightGrid" style=" white-space: nowrap; display: grid; grid-template-columns: repeat(<?= count($groups) + 1 + 1 + 1; ?>, auto);">
				<div style="display: contents;" class="C B">
					<div>Дата \ группа</div>
					<? foreach ($groups as $group) {
						?>
						<div><?= str_replace(' ', "<br>", $group['usersGroupsName']); ?> [<?= $group['idusersGroups']; ?>]<br>(<?= count($group['users'] ?? []); ?>)</div>
						<?
					}
					?>
					<div class="">Расходы<br>по<br>складу</div>
					<div>Итого</div>
				</div>
				<? for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) { ?>
					<div style="display: contents;">
						<div><?= mydates('d.m', $time); ?></div>
						<?
						$dayTotal[mydates('Y-m-d', $time)] = 0;
						foreach ($groups as $group) {
							?>
							<div class="R">
								<? if ($_GET['showDetails'] ?? false) { ?>
									<div style="display: grid; grid-template-columns: repeat(10,auto); grid-gap: 1px; background-color: white;">
										<div>Сотрудник</div>
										<div class="C B" title="Оклад за смену">1</div>
										<div class="C B" title="Премеия за оформление договора">3</div>
										<div class="C B" title="Оклад за месяц">6</div>
										<div class="C B" title="Официальный оклад за месяц">7</div>
										<div class="C B" title="Почасовая">9</div>
										<div class="C B" title="Процент от продаж">11</div>
										<div class="C B" title="Допы">Д</div>
										<div class="C B" title="Рекрутинг">Р</div>
										<div class="C B" title="Рекрутинг">К</div>
										<? foreach ($group['users'] as $user) { ?>
											<div title=""><?= $user['usersLastName']; ?> <?= mb_substr($user['usersFirstName'], 0, 1); ?>.</div>
											<?
											foreach ($user['wages'][mydates('Y-m-d', $time)] as $wage) {
												?><div><?= ($wage['value'] ?? 0) ? round($wage['value']) : ''; ?></div><?
											}
											?>
										<? } ?>
									</div>
								<? } ?>
								<?=
								($group['wagesTotal'][mydates('Y-m-d', $time)] ?? 0) ? round($group['wagesTotal'][mydates('Y-m-d', $time)]) : '--';
								$dayTotal[mydates('Y-m-d', $time)] += ($group['wagesTotal'][mydates('Y-m-d', $time)] ?? 0);
								?>
							</div>
							<?
						}
						?>
						<div class="R"><?= round($warehouseSpends[mydates('Y-m-d', $time)] ?? 0); ?></div>
						<div class="R"><?= round($dayTotal[mydates('Y-m-d', $time)] + ($warehouseSpends[mydates('Y-m-d', $time)] ?? 0)); ?></div>
					</div>
				<? } ?>

				<div style="display: contents;" class="B R">
					<div>Суммарно</div>
					<?
					foreach ($groups as $group) {
						?>
						<div class="R">
							<?= nf(array_sum($group['wagesTotal'])); ?>
						</div>
						<?
					}
					?>
					<div style=""><?= nf((array_sum($warehouseSpends ?? 0))); ?>р.</div>
					<div style=""><?= nf(array_sum($warehouseSpends ?? 0) + array_sum($dayTotal)); ?>р.</div>
				</div>
			</div>
			<?
//			printr($groups);
			print 'PGT: ' . round((microtime(true) - $start), 3) . 'c.'
			?>
		</div>
	</div>
	<?
}
print "Проверок дат: " . $___mydatesCnt . ";";
?>
<?

function memoryDump($_MEMORY) {
	$outArray = [];
	foreach (($_MEMORY ?? [])as $line => $data) {
		foreach ($data as $value) {
			$outArray[] = "{x: $line, y: $value}";
		}
	}
	return implode(',', $outArray);
}

;
?>
<div id="chartContainer" style="height: 600px; width: 1300px; display: none;"></div>
<script>
	window.onload = function () {
		var chart = new CanvasJS.Chart("chartContainer", {
			zoomEnabled: true,
			title: {
				text: "Использование памяти"
			},
			axisX: {
			},
			axisY: {
				labelFormatter: function (e) {
					return e.value / 1000000;
				}
			},
			data: [{
					type: "line",
					dataPoints: [<?= memoryDump($_MEMORY); ?>]
				}]
		});
		chart.render();
	}
</script>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
