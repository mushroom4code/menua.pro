<?php
$pageTitle = 'Рейтинг';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
$dateFrom = $_GET['dateFrom'] ?? date("Y-m-01");
$dateTo = $_GET['dateTo'] ?? date("Y-m-d");

$visits = query2array(mysqlQuery("SELECT *"
				. " FROM `clientsVisits` "
				. " LEFT JOIN `clients` ON (`idclients` = `clientsVisitsClient`)"
				. " LEFT JOIN `servicesApplied` ON (`idservicesApplied` = (SELECT MIN(`idservicesApplied`) FROM `servicesApplied` WHERE `servicesAppliedClient` = `idclients` AND `servicesAppliedDate` = `clientsVisitsDate`))"
				. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
				. " LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`)"
				. "WHERE `clientsVisitsDate` BETWEEN '$dateFrom' AND '$dateTo' AND `usersGroup` IN (9,12,13)"));

$f_sales = query2array(mysqlQuery("SELECT *"
				. " FROM `f_sales` "
				. " LEFT JOIN `f_credits` ON (`f_creditsSalesID` = `idf_sales`)"
				. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
				. " LEFT JOIN `servicesApplied` ON (`idservicesApplied` = (SELECT MIN(`idservicesApplied`) FROM `servicesApplied` WHERE `servicesAppliedClient` = `idclients` AND `servicesAppliedDate` = `f_salesDate`))"
				. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
				. " LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`)"
				. " WHERE `f_salesDate` BETWEEN '$dateFrom' AND '$dateTo' AND `usersGroup` IN (9,12,13) AND NOT isnull(`idf_credits`)")); //

$clients = [];
//printr($f_sales);
foreach ($visits as $visit) {
	$I = ($visit['clientsOldSince'] == null || mystrtotime($visit['clientsOldSince']) >= mystrtotime($visit['clientsVisitsDate']));
	$clients[$visit['idusersGroups']]['group'] = [
		'idusersGroups' => $visit['idusersGroups'],
		'usersGroupsName' => $visit['usersGroupsName'] ?? 'Без группы'
	];
	$clients[$visit['idusersGroups']]['visits'][$I ? 'I' : 'II'] = ($clients[$visit['idusersGroups']]['visits'][$I ? 'I' : 'II'] ?? 0) + 1;
}

foreach ($f_sales as $f_sale) {
	$I = ($f_sale['clientsOldSince'] == null || mystrtotime($f_sale['clientsOldSince']) >= mystrtotime($f_sale['f_salesDate']));
	$clients[$f_sale['idusersGroups']]['group'] = [
		'idusersGroups' => $f_sale['idusersGroups'],
		'usersGroupsName' => $f_sale['usersGroupsName'] ?? 'Без группы'
	];
	$clients[$f_sale['idusersGroups']]['sales'][$I ? 'I' : 'II'] = ($clients[$f_sale['idusersGroups']]['sales'][$I ? 'I' : 'II'] ?? 0) + 1;
}
?>
<div class="box neutral">
	<div class="box-body" style="min-width: 700px;">
		<h2><div style="display: grid; grid-template-columns: auto auto; grid-gap: 10px;">
				<input type="date" onchange="GETreloc('dateFrom', this.value);" value="<?= $dateFrom; ?>">
				<input type="date" onchange="GETreloc('dateTo', this.value);" value="<?= $dateTo; ?>">
			</div></h2>


		<div class="lightGrid" style="display: grid; grid-template-columns: repeat(5,auto);">
			<div style="display: contents;">
				<div class="B C" style="grid-row: span 2;">Отдел</div>
				<div class="B C" style="grid-column: span 2;">Визиты</div>
				<div class="B C" style="grid-column: span 2;">Продажи</div>
			</div>
			<div style="display: contents;">
				<div class="B C">I</div>
				<div class="B C">II</div>

				<div class="B C">I</div>
				<div class="B C">II</div>

			</div>

			<?
			foreach ($clients as $group) {
				?>
				<div style="display: contents;">
					<div><?= $group['group']['usersGroupsName']; ?></div>
					<div class="C"><?= $group['visits']['I'] ?? 0; ?></div>
					<div class="C"><?= $group['visits']['II'] ?? 0; ?></div>
					<div class="C"><?= $group['sales']['I'] ?? 0; ?></div>
					<div class="C"><?= $group['sales']['II'] ?? 0; ?></div>
				</div>
				<?
			}
			?>
		</div>
		<div style="padding: 10px; color: gray; font-size: 0.6em;">
			* Продажи целые продажи по банку<br>
			** принадлежность продажи к отделу определяется по пользователю первым поставившему процедуры на дату продажи абонемента.
		</div>

	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
