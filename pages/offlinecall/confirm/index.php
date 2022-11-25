<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(179)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(179)) {
	?>E403R179<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<?
	if (R(179)) {



		$servicesApplied = query2array(mysqlQuery("SELECT * "
						. " FROM `servicesApplied`"
						. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
						. " LEFT JOIN `clientsVisits` ON (`clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "')"
						. " LEFT JOIN `score` ON (`idscore` = (SELECT MAX(`idscore`) FROM `score` WHERE `scoreClient` = `idclients` AND `scoreDate` = `servicesAppliedDate`))"
//						. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
						. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
						. " LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"
						. " LEFT JOIN `OCC_calls` ON (`idOCC_calls` = (SELECT MAX(`idOCC_calls`) FROM `OCC_calls` WHERE DATE(`OCC_callsTime`)=`servicesAppliedDate` AND `OCC_callsType`=8 AND `OCC_callsPhone` IN(SELECT `idclientsPhones` FROM `clientsPhones` WHERE `clientsPhonesClient` = `idclients` AND isnull(`clientsPhonesDeleted`))))"
						. " WHERE "
						. " `servicesAppliedDate`= '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "'"
//						. " AND (isnull(`clientsOldSince`) OR `clientsOldSince`>='" . mres(($_GET['date'] ?? date("Y-m-d"))) . "')"
//						. " AND `usersGroup`='12'"
						. "  "
						. ""));
		$clientsSQL = "SELECT 
    *, 
(SELECT `clientsVisitsTime` FROM `clientsVisits` WHERE `clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "' LIMIT 1)	AS `clientVisit`,
(SELECT COUNT(1) FROM OCC_calls LEFT JOIN OCC_callsConfirm ON (OCC_callsConfirmCall= idOCC_calls) WHERE `OCC_callsClient` = `idclients` AND `OCC_callsType` = '8' AND `OCC_callsConfirmDate` = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "') AS `confirmed`,
	(SELECT MIN(`servicesAppliedTimeBegin`) FROM `servicesApplied` WHERE `servicesAppliedClient` = `idclients` AND `servicesAppliedDate` = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "') AS `firstSA`
FROM
    `clients`
	LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)
WHERE
    `idclients` IN (SELECT 
            `servicesAppliedClient`
        FROM
            `servicesApplied`
        WHERE
            `servicesAppliedDate` = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "'
        GROUP BY `servicesAppliedClient`);";
//		print $clientsSQL;
		$clients = query2array(mysqlQuery($clientsSQL));
//		printr($clients, 1);
//		printr($clients);

		uasort($clients, function ($a, $b) {
			$byvisit = $a['clientVisit'] <=> $b['clientVisit'];
			if ($byvisit) {
				return $byvisit;
			}
			$bycall = $a['confirmed'] <=> $b['confirmed'];
			if ($bycall) {
				return $bycall;
			}

			$byold = (!$a['clientsOldSince'] || $a['clientsOldSince'] > ($_GET['date'] ?? date("Y-m-d"))) <=> (!$b['clientsOldSince'] || $b['clientsOldSince'] > ($_GET['date'] ?? date("Y-m-d")));
			if ($byold) {
				return $byold;
			}


			$bySources = $a['idclientsSources'] <=> $b['idclientsSources'];
			if ($bySources) {
				return $bySources;
			}


			$bytime = $a['firstSA'] <=> $b['firstSA'];
			if ($bytime) {
				return $bytime;
			}

//			$servicesAppliedTimeBegin = (array_values(array_filter($a['servicesApplied'], function ($serviceApplied) {
//								return !$serviceApplied['servicesAppliedDeleted'];
//							}))[0]['servicesAppliedTimeBegin'] ?? 'true') <=> (array_values(array_filter($b['servicesApplied'], function ($serviceApplied) {
//								return !$serviceApplied['servicesAppliedDeleted'];
//							}))[0]['servicesAppliedTimeBegin'] ?? 'true');
//			if ($servicesAppliedTimeBegin) {
//				return $servicesAppliedTimeBegin;
//			}


			return mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName']);
		});
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= ($_GET['date'] ?? date("Y-m-d")); ?>"></h2>
				<?
				if (count($clients)) {
//					printr($clients[0]);
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6,auto);">
						<div style="display: contents;">
							<div class="B C">#</div>
			<!--							<div class="B C" style="min-width: 30px;"><i class="far fa-check-square"></i></div>-->
							<div class="B C" style="min-width: 30px;"><i class="fas fa-walking"></i></div>
							<div class="B C" style="min-width: 30px;"><i class="fas fa-phone-square"></i></div>
							<div class="B C">Запись на</div>
							<div class="B C">ИСТ</div>
							<div class="B C">ФИО клиента</div>
							<!--<div class="B C">Направление</div>-->
							<!--<div class="B C">ФИО оператора</div>-->
						</div>

						<?
						$n = 0;
						foreach ($clients as $client) {
							$n++;
//							printr($client);
							?>
							<div style="display: contents;">
								<div>
									<?= $n; ?>
								</div>
								<div class="C">
									<?
									if ($client['clientVisit'] ?? false) {
										?><i class="fas fa-walking" title="Визит зафиксирован в <?= date('H:i', mystrtotime($client['clientVisit'])); ?>" style="color:green;"></i><?
									}
									?>

								</div>
								<div class="C">
									<? if ($client['confirmed']) { ?><i class="fas fa-phone-square" title="Подтверждён" style="color:green;"></i><? } ?>

								</div>
								<div class="C">
									<?= ($client['firstSA'] ?? false) ? date('H:i', strtotime($client['firstSA'])) : 'Нет процедур' ?>
								</div>
								<div class="C">
									<?= ($client['clientsSourcesLabel'] ?? false) ? $client['clientsSourcesLabel'] : 'Не указан' ?>
								</div>
								<div>
									<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= ($_GET['date'] ?? date("Y-m-d")); ?>">
										<?= (!$client['clientsOldSince'] || $client['clientsOldSince'] > ($_GET['date'] ?? date("Y-m-d"))) ? '<i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i>' : ''; ?>
										<?= $client['clientsLName'] ?? ''; ?>
										<?= $client['clientsFName'] ?? ''; ?>
										<?= $client['clientsMName'] ?? ''; ?>
									</a>
								</div>
							</div>
						<? } ?>
					</div>
					<?
				} else {
					?><h1 style="text-align: center; margin: 20px;">Нет данных</h1><?
				}
				?>
			</div>
		</div>
	<? } ?>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
