<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
?><!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>График работы</title>
		<script src="/sync/js/basicFunctions.js" type="text/javascript"></script>
		<style>
			body {
				padding: 0px;
				margin: 0px;
			}
			body:hover .dateselect{
				display: block;
			}
			.dateselect {
				display: none;
			}
		</style>
    </head>
    <body>
		<?
//printr($personal);
//printr($_MONTHES);
		$_Y = ($_GET['Y'] ?? date("Y"));
		$_M = ($_GET['m'] ?? date("m"));
		$nDays = date("t", mktime(12, 0, 0, ($_M), 1, ($_Y)));

		$schedule = query2array(mysqlQuery("SELECT *, (SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
						. " FROM `usersSchedule`"
						. " LEFT JOIN `users` ON (`idusers` = `usersScheduleUser`)"
						. " WHERE "
						. " `usersScheduleDate`>='" . (($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-01') . "'"
						. " AND `usersScheduleDate`<='" . (($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $nDays) . "'"
						. " AND NOT isnull(`usersScheduleFrom`) "
						. " AND NOT isnull(`usersScheduleTo`) "
						. (($_GET['user'] ?? false) ? (" AND `idusers` = '" . mres($_GET['user']) . "'") : "")
						. ""));

		$users = [];

		foreach ($schedule as $shift) {
			$users[$shift['idusers']]['name'] = implode(" ", array_filter([$shift['usersLastName'], $shift['usersFirstName'], $shift['usersMiddleName']]));
			$users[$shift['idusers']]['nameShort'] = implode(" ", array_filter([$shift['usersLastName'], mb_substr($shift['usersFirstName'], 0, 1) . ($shift['usersFirstName'] ? '.' : ''), mb_substr($shift['usersMiddleName'], 0, 1) . ($shift['usersFirstName'] ? '.' : '')]));
			$users[$shift['idusers']]['position'] = $shift['positions'];
			$users[$shift['idusers']]['shifts'][] = [
				'date' => date("d.m.Y", mystrtotime($shift['usersScheduleDate'])),
				'from' => date("H:i", mystrtotime($shift['usersScheduleFrom'])),
				'dayname' => $_WEEKDAYS['short'][date("N", mystrtotime($shift['usersScheduleFrom']))],
				'to' => date("H:i", mystrtotime($shift['usersScheduleTo'])),
				'isduty' => $shift['usersScheduleDuty'] == '1'
			];
		}
		?>
		<h2 style="page-break-after: always;" class="dateselect"><div style="display: inline-block;">
				<a style="color: black; text-decoration: none; font-size: 0.6em;" href="<?= GR2(['user' => null]); ?>">Все сотрудники</a>
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
						?><option value="<?= $Y; ?>"<?= (($Y == $_Y) ? ' selected' : ''); ?>><?= $Y; ?></option><?
					}
					?>
				</select>
			</div></h2>

		<?
		if (!count($schedule)) {
			?><h1>Нет данных</h1><?
		}
		foreach ($users as $user) {
			usort($user['shifts'], function ($a, $b) {
				return $a['date'] <=> $b['date'];
			});
			?>

			<div style="border: 0px solid silver; padding: 2em; margin: 2em; line-height: 1.8em;page-break-after: always;">
				Я, <b><?= $user['name'] ?></b>, занимая должность <b><?= $user['position'] ?></b>, подтверждаю своё согласие с графиком моей работы на <?= $_MONTHES['full']['nom'][(int) $_M] ?> <?= $_Y; ?>г. со следующим составом рабочих смен:
				<?
				foreach ($user['shifts'] as $shift) {
					?>
					<div><?= $shift['date']; ?> (<?= $shift['dayname']; ?>) c <?= $shift['from']; ?> до <?= $shift['to']; ?> <?= $shift['isduty'] ? ' (дежурная смена)' : ''; ?></div>
					<?
				}
				?>
				<div></div>Итого: <?= human_plural_form(count($user['shifts']), ['смена', 'смены', 'смен'], 1); ?>
				<div> <?= $user['nameShort'] ?><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>/<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= date("Y"); ?>г.&nbsp;&nbsp;&nbsp;</u></div>
			</div>
		<? }
		?>
    </body>
</html>