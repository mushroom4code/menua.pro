<?php
$load['title'] = $pageTitle = 'Обследования';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(46)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(46)) {
	?>E403R46<?
} else {
	?>
	<ul class="horisontalMenu">
		<li><a href="#" onclick="addTPS({'parent':<?= $_GET['tps'] ?? 'null' ?>});">Добавить</a></li>
	</ul>

	<?
	$TPservices = query2array(mysqlQuery(""
					. "SELECT * FROM `TPS_Services`"
					. "LEFT JOIN (SELECT * FROM `TPS_costs` AS `A` INNER JOIN (SELECT MAX(`idTPS_costs`) AS `idTPS_costsMAX` FROM `TPS_costs` GROUP BY `TPS_costsService`) AS `B` ON (`A`.`idTPS_costs` = `B`.`idTPS_costsMAX`)) AS `TPS_costs` ON (`TPS_costsService` = `idTPS_Services`)"
					. "LEFT JOIN (SELECT * FROM `TPS_prices` AS `A` INNER JOIN (SELECT MAX(`idTPS_prices`) AS `idTPS_pricesMAX` FROM `TPS_prices` GROUP BY `TPS_pricesService`) AS `B` ON (`A`.`idTPS_prices` = `B`.`idTPS_pricesMAX`)) AS `TPS_prices` ON (`TPS_pricesService` = `idTPS_Services`)"
					. " WHERE `TPS_ServicesCatalog`='" . ($_GET['tps'] ?? '') . "'"));
//	printr($TPservices[0]);
	?>

	<div class="box neutral">
		<div class="box-body">
			<div style="display: inline-block;">
				<div class="lightGrid" style="display: grid; grid-template-columns:  auto auto auto auto auto; grid-gap: 0px 0px;">
					<div style="display: contents;">
						<div style="text-align: center; font-weight: bold;">ID</div>
						<div style="text-align: center; font-weight: bold;">Код</div>
						<div style="text-align: center; font-weight: bold;">Наименование</div>
						<div>Стоимость</div>
						<div>Цена</div>
					</div>

					<?
					foreach ($TPservices as $TPservice) {
						?>
						<div style="display: contents;">
							<div><?= $TPservice['idTPS_Services']; ?></div>
							<div class="C"><?= $TPservice['TPS_ServicesCode']; ?></div>
							<div><?= $TPservice['TPS_ServicesName']; ?></div>
							<div class="R" style="padding-right: 10px;" data-function="editField" data-field="TPS_costsValue" data-service="<?= $TPservice['idTPS_Services']; ?>" data-value="<?= $TPservice['TPS_costsValue']; ?>"><?= nf($TPservice['TPS_costsValue']); ?></div>
							<div class="R" style="padding-right: 10px;" data-function="editField" data-field="TPS_pricesValue" data-service="<?= $TPservice['idTPS_Services']; ?>" data-value="<?= $TPservice['TPS_pricesValue']; ?>"><?= $TPservice['TPS_pricesValue'] == 0 ? '=' : ''; ?><?= nf($TPservice['TPS_pricesValue']); ?></div>
						</div>
						<?
					}
					?>

				</div>
			</div>
		</div>

	<? }
	?>

	<?
	include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
	