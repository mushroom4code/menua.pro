<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(163)) {
	
}
$_date = ($_GET['date'] ?? date("Y-m-d", time() - 60 * 60 * 24));
if ($_date > date("Y-m-d", time() - 60 * 60 * 24)) {
	header("Location: " . GR2(['date' => null]));
	die();
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(163) && !R(164)) {
	?>E403R163||164<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<? if (R(163) || R(164)) {
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" max="<?= date("Y-m-d", time() - 60 * 60 * 24); ?>" value="<?= $_date; ?>"></h2>
				<?
				$query = "SELECT *,"
						. "(SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ')  FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`,"
						. "(SELECT GROUP_CONCAT(`servicesName` SEPARATOR ', ') FROM `services` WHERE `idservices` IN (SELECT `servicesAppliedService` FROM `servicesApplied` WHERE  `servicesAppliedClient` = `idclients` AND `servicesAppliedDate` = '" . mres($_date) . "'  AND (isnull(`servicesAppliedDeleted`) OR `servicesAppliedDeleted`>`servicesAppliedDate`))) as `services` "
						. " FROM `clients`"
						. " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)"
						. " WHERE `idclients` IN ("
						. " SELECT `servicesAppliedClient` FROM `servicesApplied` AS `A`"
						. " WHERE `A`.`servicesAppliedDate`='" . mres($_date) . "'"
						. " AND (SELECT COUNT(1) FROM `servicesApplied` AS `B` WHERE `B`.`servicesAppliedDate`>'" . mres($_date) . "' AND `A`.`servicesAppliedClient`=`B`.`servicesAppliedClient` AND isnull(`B`.`servicesAppliedDeleted`))=0 "
						. " AND (isnull(`A`.`servicesAppliedDeleted`) OR `A`.`servicesAppliedDeleted`>`A`.`servicesAppliedDate`) "
						. " AND (SELECT COUNT(1) FROM `clientsVisits` WHERE `clientsVisitsClient`=`A`.`servicesAppliedClient` AND `clientsVisitsDate` = '" . mres($_date) . "')=0 "
						. ") "
						. " AND (isnull(`clientsOldSince`) OR `clientsOldSince`>'" . mres($_date) . "')"
						. ((!R(164)) ? (" AND `clientsAddedBy` = '" . $_USER['id'] . "'") : "")
						. "";
//				print $query;
				$clients = query2array(mysqlQuery($query));

//				uasort($clients, function ($a, $b) {
//					return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
//				});
				if (count($clients)) {
//					printr($clients[0]);
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto auto;">
						<div style="display: contents;">
							<div class="B C">ФИО клиента</div>
							<div class="B C">Источник</div>
							<div class="B C">Тел -клиента</div>
							<div class="B C">Дата</div>
							<div class="B C">Направление</div>
						</div>

						<? foreach ($clients as $client) { ?>
							<div style="display: contents;">
								<div><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients'] ?? ''; ?>&date=<?= $_date; ?>">
										<?= $client['clientsLName'] ?? ''; ?>
										<?= $client['clientsFName'] ?? ''; ?>
										<?= $client['clientsMName'] ?? ''; ?>
									</a>
								</div>
								<div><?= $client['clientsSourcesName'] ?? ''; ?></div>
								<div class="C"><a target="_blank" href="/pages/remotecall/call.php?client=<?= $client['idclients'] ?? ''; ?>"><?= $client['phones'] ?? ''; ?></a></div>
								<div class="C"><?= date("d.m.Y", mystrtotime($_date)) ?? ''; ?></div>
								<div><a target="_blank" href="/pages/reception/?client=<?= $client['idclients'] ?? ''; ?>&date=<?= $_date; ?>"><?= $client['services'] ?? ''; ?></a></div>
							</div>
						<? } ?>
					</div>
					<?
				} else {
					?><h1 style="text-align: center; margin: 20px;">Нет подходящих данных данных</h1><?
				}
				?>
				<div style="color: gray; font-size: 0.7em; line-height: 1em; padding: 20px; max-width: 800px;">* список клиентов у которых:<br>1. на <?= $_date; ?> есть назначенные процедуры которые не удалены, либо удалены позже <?= $_date; ?> 00:00:00,<br>2. отсутствует дата перехода в статус вторичного клиента, либо эта дата больше текущей (<?= $_date; ?>) даты.<br>
					3. <?= $_date; ?> отсутствует отметка о визите в клинику.<br>
					4. Отсутствуют неудалённые процедуры на дату позднее <?= $_date; ?>.<br>
					<?= !R(164) ? ('5. Добавленных этим пользователем (' . $_USER['lname'] . ' ' . $_USER['fname']) . ')' : ''; ?>
				</div>
			</div>
		</div>
	<? } ?>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
