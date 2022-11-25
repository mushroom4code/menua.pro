<?php
$pageTitle = 'Расписание персонала';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

//printr($personal);
//printr($_MONTHES);
$nDays = date("t", mktime(12, 0, 0, ($_GET['m'] ?? date("m")), 1, ($_GET['Y'] ?? date("Y"))));

$scheduleSQL = "SELECT * FROM `usersSchedule`"
		. " WHERE "
		. "`usersScheduleDate`>='" . (($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-01') . "'"
		. " AND `usersScheduleDate`<='" . (($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $nDays) . "'";

$personalSQL = "SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` FROM `users`"
		. "LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`)"
		. "WHERE"
		. " (isnull(`usersDeleted`) OR `usersDeleted`>'" . (($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $nDays) . "')"
		. " AND (isnull(`usersAdded`) OR `usersAdded`<'" . (($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $nDays) . "')"
		. " AND NOT isnull(`usersGroup`)";
$personal = query2array(mysqlQuery($personalSQL));
//printr($personal);
usort($personal, function ($a, $b) {
	if ($a['usersGroupsSort'] <=> $b['usersGroupsSort']) {
		return $a['usersGroupsSort'] <=> $b['usersGroupsSort'];
	}
	if ($a['positions'] <=> $b['positions']) {
		return $a['positions'] <=> $b['positions'];
	}
	return $a['usersLastName'] <=> $b['usersLastName'];
});

//print $scheduleSQL;
$schedule = query2array(mysqlQuery($scheduleSQL));

$scheduleMap = [];
foreach ($schedule as $scheduleEntry) {
//	$scheduleMap['date']['user']= halfs;
	$scheduleMap[$scheduleEntry['usersScheduleDate']][$scheduleEntry['usersScheduleUser']]['halfs'] = $scheduleEntry['usersScheduleHalfs'];
	$scheduleMap[$scheduleEntry['usersScheduleDate']][$scheduleEntry['usersScheduleUser']]['duty'] = $scheduleEntry['usersScheduleDuty'];
}
printr($scheduleMap);
?>

<div class="box neutral">
	<div class="box-body">
		<? if (!R(50)) { ?>E403R50<? } else { ?>
			<h2><div style="display: inline-block;">
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
						for ($Y = date("Y") + 1; $Y >= 2020; $Y--) {
							?><option value="<?= $Y; ?>"<?= ($Y == ($_GET['Y'] ?? date("Y")) ? ' selected' : ''); ?>><?= $Y; ?></option><?
						}
						?>
					</select>
				</div></h2>


			<table style="border-top: 1px solid gray; border-left: 1px solid gray; margin: 20px 0px 20px auto" cellspacing="0">
				<tr><td colspan="4" style="border-bottom: 1px solid gray; border-right: 1px solid gray; text-align: center;">Рабочее время</td></tr>
				<tr>

					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;">Полная смена</td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; width: 30px;" class="H11"><br></td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><input type="time" id="H11start" value="10:00"></td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><input type="time" id="H11end" value="20:00"></td>
				</tr>

				<tr>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;">Первая половина дня</td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;" class="H10"><br></td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><input type="time" id="H10start" value="10:00"></td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><input type="time" id="H10end" value="15:00"></td>
				</tr>

				<tr>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;">Вторая половина дня</td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;" class="H01"><br></td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><input type="time" id="H01start" value="15:00"></td>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><input type="time" id="H01end" value="20:00"></td>
				</tr>

				<tr>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;">Дежурная смена<br><span style="color: gray; font-size: 0.6em; line-height: 0.6em;">Зажать Alt для установки</span></td>
					<td colspan="3" style="border-bottom: 1px solid gray; border-right: 1px solid gray; text-align: center;">
						<table style="margin: 0 auto;">
							<tr>
								<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; width: 30px;" class="H11 duty"><br></td>
								<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; width: 30px;" class="H10 duty"><br></td>
								<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; width: 30px;" class="H01 duty"><br></td>
							</tr>
						</table>
					</td>
				</tr>

			</table>
			<? if (R(54)) { ?><input type="checkbox" id="edit"><label for='edit'>Редактировать</label><? } ?>

			<script>
				async function calendarChedule(data) {
					console.log(data);
				}
				async function cicle(dir, data) {

					if (!qs('#edit').checked) {
						return false;
					}
					let domElement = qs(`#r${data.r}_c${data.c}`);
					console.log(data);
					let classes = ['H00', 'H01', 'H10', 'H11'];
					let stateCount = classes.length;
					let state = 1 * domElement.dataset.state;
					let fallback = state;

					if (dir != 0) {
						state--;
					} else {
						state = 0;
					}

					if (state > stateCount - 1) {
						state = 0;
					}
					if (state < 0) {
						state = stateCount - 1;
					}



					for (let cls of classes) {
						domElement.classList.remove(cls);
					}
					domElement.dataset.state = state;
					domElement.classList.add(classes[domElement.dataset.state]);
					if (event.altKey) {
						domElement.classList.add('duty');
					} else {
						domElement.classList.remove('duty');
					}

					let result = await fetch('IO.php', {
						body: JSON.stringify({
							user: data.user,
							date: data.date,
							halfs: dec2bin(state),
							duty: event.altKey,
							from: (qs(`#H${dec2bin(state)}start`) || {}).value,
							to: (qs(`#H${dec2bin(state)}end`) || {}).value
						}),
						credentials: 'include',
						method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
					}).then(result => result.text()).then(async function (text) {
						try {
							let jsn = JSON.parse(text);
							return jsn;
						} catch (e) {
							MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
						}
					});//fetch

					if (result.success) {

					} else {
						for (let cls of classes) {
							domElement.classList.remove(cls);
						}
						domElement.dataset.state = fallback;
						domElement.classList.add(classes[fallback]);
					}
				}
			</script>
			<? $calendarScheduleSchemas = query2array(mysqlQuery("SELECT * FROM `calendarScheduleSchemas`")); ?>


			<br>
			<table style="border-top: 1px solid gray; border-left: 1px solid gray;" cellspacing="0">
				<tr>
					<td rowspan="2" style="border-bottom: 1px solid gray; border-right: 1px solid gray;">
						<h3 style="padding: 20px;">Схема</h3>
					</td>
					<?
					for ($d = 1; $d <= $nDays; $d++) {
						$time = strtotime(($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $d);
						$today = strtotime("today");
						$N = date('N', mktime(12, 0, 0, ($_GET['m'] ?? date("m")), $d, ($_GET['Y'] ?? date("Y"))));
						?><td class="C" style="<?= ($today == $time) ? 'border: 2px solid red;border-bottom: 1px solid gray;' : 'border-bottom: 1px solid gray; border-right: 1px solid gray;' ?><?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"><?= $d; ?></td><?
					}
					?>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td>
				</tr>
				<tr>
					<?
					for ($d = 1; $d <= $nDays; $d++) {
						$time = strtotime(($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $d);
						$today = strtotime("today");
						$N = date('N', $time);
						?><td class="C" style="<?= ($today == $time) ? 'border: 2px solid red; border-top: none;' : 'border-bottom: 1px solid gray; border-right: 1px solid gray;' ?><?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"><?= $_WEEKDAYS['short'][$N]; ?></td><?
					}
					?>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td>
				</tr>				
				<?
				foreach ($calendarScheduleSchemas as $calendarScheduleSchema) {
//					idcalendarScheduleSchemas
					?>
					<tr>

						<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;">
							<a><?= $calendarScheduleSchema['calendarScheduleSchemasName']; ?></a>
						</td>
						<?
						for ($d = 1; $d <= $nDays; $d++) {
							$date = ($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . ($d > 9 ? $d : ('0' . $d));

							$time = strtotime(($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $d);
							$today = strtotime("today");
							$N = date('N', $time);
							?><td style="border-bottom: 1px solid gray; border-right: 1px solid gray;<?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"<?
							?> id="s<?= $calendarScheduleSchema['idcalendarScheduleSchemas']; ?>d<?= $d; ?>" onclick="calendarChedule({scheme:<?= $calendarScheduleSchema['idcalendarScheduleSchemas']; ?>, day:<?= $d; ?>, date: '<?= date("Y-m-d", $time); ?>'});"></td><?
							}
							?>
						<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td>

					</tr>
					<?
				}
				?>
				<tr>
					<td colspan="<?= $nDays + 2; ?>" style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><br></td>
				</tr>

				<?
				$group = null;
				$ttl = [];
				$gttl = [];
				foreach ($personal as $row => $persona) {
					if (in_array($persona['usersGroup'], [12]) && !R(126)) {//Маркетинг
						continue;
					}
					if (in_array($persona['usersGroup'], [9]) && !R(128)) {//Сервис
						continue;
					}

					if (!in_array($persona['usersGroup'], [9, 12]) && !R(127)) {//НЕ Маркетинг
						continue;
					}

					if ($group != $persona['usersGroup']) {


						if ($group) {
							?>
							<tr>
								<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"></td>
								<?
								for ($d = 1; $d <= $nDays; $d++) {
									$time = strtotime(($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $d);
									$today = strtotime("today");
									$N = date('N', $time);
									?>
									<td class="C" style="border-bottom: 1px solid gray; border-right: 1px solid gray;<?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"><?= $ttl[$d] ?? ''; ?></td>
									<?
								}
								$ttl = [];
								?>
								<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td>
							</tr><tr>
								<td colspan="<?= $nDays + 2; ?>" style="border-bottom: 1px solid gray; border-right: 1px solid gray;"><br></td>
							</tr>
							<?
						}

						$group = $persona['usersGroup'];
						?>

						<tr>
							<td rowspan="2" style="border-bottom: 1px solid gray; border-right: 1px solid gray;">
								<h3 style="padding: 20px;"><?= $persona['usersGroupsName']; ?></h3>
							</td>
							<?
							for ($d = 1; $d <= $nDays; $d++) {
								$time = strtotime(($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $d);
								$today = strtotime("today");
								$N = date('N', mktime(12, 0, 0, ($_GET['m'] ?? date("m")), $d, ($_GET['Y'] ?? date("Y"))));
								?><td class="C" style="<?= ($today == $time) ? 'border: 2px solid red;border-bottom: 1px solid gray;' : 'border-bottom: 1px solid gray; border-right: 1px solid gray;' ?><?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"><?= $d; ?></td><?
							}
							?>
							<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td>
						</tr>
						<tr>
							<?
							for ($d = 1; $d <= $nDays; $d++) {
								$time = strtotime(($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $d);
								$today = strtotime("today");
								$N = date('N', $time);
								?><td class="C" style="<?= ($today == $time) ? 'border: 2px solid red; border-top: none;' : 'border-bottom: 1px solid gray; border-right: 1px solid gray;' ?><?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"><?= $_WEEKDAYS['short'][$N]; ?></td><?
							}
							?>
							<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td>
						</tr>

					<? } ?>
					<tr>
						<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;">
							<div style="display: grid; grid-template-columns: auto auto;">
								<a href="/pages/personal/info.php?employee=<?= $persona['idusers']; ?>" target="_blank"><?= $persona['usersLastName']; ?> <?= $persona['usersFirstName']; ?></a><div style="text-align: right; max-width: 270px; overflow: hidden; white-space: nowrap;" title="<?= htmlentities(($persona['positions'] ?? 'должность не указана')); ?>">(<?=
									(mb_strlen($persona['positions'] ?? 'должность не указана') > 20 ? (mb_substr(($persona['positions'] ?? 'должность не указана'), 0, 20) . '...') : $persona['positions'] ?? 'должность не указана');
									?>)</div>
							</div></td><?
						$uttl = null;
						for ($d = 1; $d <= $nDays; $d++) {
							$date = ($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . ($d > 9 ? $d : ('0' . $d));

							$time = strtotime(($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $d);
							$today = strtotime("today");
							$N = date('N', $time);
							?><td style="border-bottom: 1px solid gray; border-right: 1px solid gray;<?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"<?
								if ($scheduleMap[$date][$persona['idusers']]['halfs'] ?? 0) {
									$ttl[$d] = ($ttl[$d] ?? 0) + ($scheduleMap[$date][$persona['idusers']]['halfs'] == '11' ? 1 : 0.5);
									$gttl[$d] = ($gttl[$d] ?? 0) + ($scheduleMap[$date][$persona['idusers']]['halfs'] == '11' ? 1 : 0.5);
									$uttl = ($uttl ?? 0) + ($scheduleMap[$date][$persona['idusers']]['halfs'] == '11' ? 1 : 0.5);
									?> class="H<?= $scheduleMap[$date][$persona['idusers']]['halfs']; ?><?= $scheduleMap[$date][$persona['idusers']]['duty'] ? ' duty' : ''; ?>" data-state="<?= bindec($scheduleMap[$date][$persona['idusers']]['halfs']); ?>" <? } else { ?> data-state="0" <?
								}
								?> id="r<?= $row; ?>_c<?= $d; ?>" onclick="cicle(1, {r:<?= $row; ?>, c:<?= $d; ?>, user: <?= $persona['idusers']; ?>, date: '<?= $date; ?>'});" oncontextmenu="cicle(0, {r:<?= $row; ?>,c:<?= $d; ?>, user: <?= $persona['idusers']; ?>,date: '<?= $date; ?>'}); void(0); return false;"></td><?
							}
							?><td style="border-bottom: 1px solid gray; border-right: 1px solid gray; text-align: right; background-color: skyblue;"><?= $uttl ?? ''; ?></td>
					</tr><?
				}
				?>
				<tr>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"></td>
					<?
					for ($d = 1; $d <= $nDays; $d++) {
						$N = date('N', mktime(12, 0, 0, ($_GET['m'] ?? date("m")), $d, ($_GET['Y'] ?? date("Y"))));
						?><td class="C" style="border-bottom: 1px solid gray; border-right: 1px solid gray;<?= ($N >= 6 ? ' background-color: pink;' : ''); ?>"><?= $ttl[$d] ?? ''; ?></td><?
					}
					?><td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td></tr>
				<tr><td style="border-bottom: 1px solid gray; border-right: 1px solid gray;"></td>
					<?
					for ($d = 1; $d <= $nDays; $d++) {
						$N = date('N', mktime(12, 0, 0, ($_GET['m'] ?? date("m")), $d, ($_GET['Y'] ?? date("Y"))));
						?><td class="C" style="border-bottom: 1px solid gray; border-right: 1px solid gray;<?= ($N >= 6 ? ' background-color: pink;' : ''); ?>; font-weight: bold; font-size: 0.6em;"><?= $gttl[$d] ?? ''; ?></td><?
					}
					?>
					<td style="border-bottom: 1px solid gray; border-right: 1px solid gray; background-color: skyblue;"></td>
				</tr>
			</table>
		<? } ?>
	</div>
</div>


<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
