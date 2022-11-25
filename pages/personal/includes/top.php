<?php
if ($_GET['employee'] ?? false) {
	$employeeArr = query2array(mysqlQuery(""
					. "SELECT *,"
					. "(SELECT `usersActiveState` FROM `usersActive` WHERE `usersActiveUser`=`idusers` ORDER BY `idusersActive` DESC LIMIT 1) AS `usersActiveState`,"
					. " null as `usersPhone`"
					. " FROM `users` "
					. "LEFT JOIN `credentials` ON (`credentialsUser` = `idusers`)"
					. "LEFT JOIN `usersPositions` ON (`usersPositionsUser` = `idusers`)"
					. "LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`)"
					. "LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`)"
					. "WHERE `idusers` = '" . FSI($_GET['employee']) . "' "
					. (!R(8) ? " AND `idpositions` in (32)" : "")
					. ""));
}
$pageTitle = 'Рабочие';
$load['title'] = ($employeeArr[0]['usersLastName'] ?? '') . ' ' . ($employeeArr[0]['usersFirstName'] ?? '');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
<? include 'topmenu.php'; ?>


<?
if ($employeeArr) {
	foreach ($employeeArr as $employeeEntry) {
		if (!isset($employee)) {
			$employee = [
				'idusers' => $employeeEntry['idusers'],
				'usersFinger' => $employeeEntry['usersFinger'],
				'usersLastName' => $employeeEntry['usersLastName'],
				'usersFirstName' => $employeeEntry['usersFirstName'],
				'usersMiddleName' => $employeeEntry['usersMiddleName'],
				'usersICQ' => $employeeEntry['usersICQ'],
				'usersTG' => $employeeEntry['usersTG'],
				'usersGroup' => $employeeEntry['usersGroup'],
				'usersGroupsName' => $employeeEntry['usersGroupsName'],
				'usersPhone' => '',
				'usersBday' => $employeeEntry['usersBday'],
				'usersBarcode' => $employeeEntry['usersBarcode'],
				'usersDeleted' => $employeeEntry['usersDeleted'],
				'usersActiveState' => $employeeEntry['usersActiveState'],
				'usersFired' => $employeeEntry['usersFired'],
				'credentialsUser' => $employeeEntry['credentialsUser'],
				'credentialsLogin' => $employeeEntry['credentialsLogin'],
				'usersCard' => $employeeEntry['usersCard'],
				'usersAdded' => $employeeEntry['usersAdded'],
				'credentialsPassword' => $employeeEntry['credentialsPassword']
			];
		}
		if (isset($employeeEntry['idpositions'])) {
			if (!isset($employee['positions'])) {
				$employee['positions'] = [];
			}
			$employee['positions'][] = [
				'id' => $employeeEntry['idpositions'],
				'name' => $employeeEntry['positionsName']
			];
		}
	}
}
//printr($employee);
?>
<div class="divider"></div>




<div class="box neutral">
	<h2><?= $employee['usersLastName']; ?> <?= $employee['usersFirstName']; ?> <?
		if ($employee['usersAdded'] > '2001-01-01 00:00:00') {
			$timeobj = secondsToTimeObj(time() - strtotime($employee['usersAdded']));
			$Y = human_plural_form($timeobj->format('%y'), ['год', 'года', 'лет'], true);
			$m = human_plural_form($timeobj->format('%m'), ['месяц', 'месяца', 'месяцев'], true);
			$d = human_plural_form($timeobj->format('%d'), ['день', 'дня', 'дней'], true);

			$output = array_filter([($timeobj->format('%y') > 0 ? "$Y" : ''), ($timeobj->format('%m') > 0 ? "$m" : ''), ($timeobj->format('%d') > 0 ? "$d" : '')]);

			print '(' . implode(', ', $output) . ')';
		}
		?></h2>
	<div class="box-body">
		<? include 'menu.php'; ?>
		<div style="display: grid; grid-template-columns: 150px auto;">
			<div style="display: inline-block;">
				<div class="personalUserpic"<? if ($employee['usersICQ'] ?? 0) {
			?> style=" background-size: cover;  background-position: center;  background-image: URL('https://rapi.icq.net/avatar/get?targetSn=<?= $employee['usersICQ']; ?>&size=1024')"<? } ?>></div>
					 <?
					 if ($employee['usersBarcode']) {
						 ?>
					<a target="_blank" style="padding: 0px;" href="/sync/plugins/barcodePrint.php?print=[<?= $employee['idusers']; ?>]">
						<svg class="barcode" style="border: 1px solid black; display: block; margin: 0 auto; max-width: 100%; height: auto;"
							 jsbarcode-text="<?= $employee['usersLastName']; ?> <?= $employee['usersFirstName']; ?> (<?= $employee['idusers']; ?>)"
							 jsbarcode-value="<?= $employee['usersBarcode']; ?>"
							 jsbarcode-width="1"
							 jsbarcode-height="30"
							 jsbarcode-fontSize="12"
							 jsbarcode-font="Arial"
							 >
						</svg>
					</a>
					<?
				}
				?>
			</div>
			<div>