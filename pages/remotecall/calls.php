<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(111)) {

}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(111)) {
	?>E403R111<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<? if (R(111)) { ?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= ($_GET['date'] ?? date("Y-m-d")); ?>"></h2>
				<?
				$query = "SELECT *,"
						. "(SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ')  FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`,"
						. "(SELECT GROUP_CONCAT(`servicesName` SEPARATOR ', ') FROM `servicesApplied` LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) WHERE  `servicesAppliedClient` = `idclients` AND `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'  AND isnull(`servicesAppliedDeleted`)) as `services`"
						. ""
						. " FROM `clients`"
						. " LEFT JOIN `users` ON (`idusers` = `clientsAddedBy`)"
						. " WHERE "
						. " `clientsSource`='2'"
						. " AND (SELECT COUNT(1) FROM `servicesApplied` "
						. " WHERE `servicesAppliedClient` = `idclients` "
						. " AND `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "' AND isnull(`servicesAppliedDeleted`)) > 0 "
						. " AND (isnull(`clientsOldSince`) OR `clientsOldSince`>'" . ($_GET['date'] ?? date("Y-m-d")) . "')";
				$clients = query2array(mysqlQuery($query));

				uasort($clients, function ($a, $b) {
					return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
				});
				if (count($clients)) {
//					printr($clients[0]);
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto;">
						<div style="display: contents;">
							<div class="B C">ФИО клиента</div>
							<div class="B C">Тел -клиента</div>
							<div class="B C">Направление</div>
							<div class="B C">ФИО оператора</div>
						</div>

						<? foreach ($clients as $client) { ?>
							<div style="display: contents;">
								<div>
									<?= $client['clientsLName'] ?? ''; ?>
									<?= $client['clientsFName'] ?? ''; ?>
									<?= $client['clientsMName'] ?? ''; ?>
								</div>
								<div><?= $client['phones'] ?? ''; ?></div>
								<div><div><?= $client['services'] ?? ''; ?></div></div>
								<div><?= $client['usersLastName'] ?? ''; ?> <?= $client['usersFirstName'] ?? ''; ?></div>
							</div>
						<? } ?>
					</div>
					<?
				} else {
					?><h1 style="text-align: center; margin: 20px;">Нет данных</h1><?
				}
				?>
				<div style="color: gray; font-size: 0.7em; line-height: 1em; padding: 20px; max-width: 800px;">* список клиентов у которых <?= ($_GET['date'] ?? date("Y-m-d")); ?> есть назначенные и не удалённые процедуры, источник клиента = маркетинг, отсутствует дата перехода в статус вторичного клиента, либо эта дата больше текущей (<?= ($_GET['date'] ?? date("Y-m-d")); ?>) даты.</div>
			</div>
		</div>
	<? } ?>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
