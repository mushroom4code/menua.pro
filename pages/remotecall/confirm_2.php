<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(143)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(143)) {
	?>E403R143<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<?
	if (R(143)) {



		$servicesApplied = query2array(mysqlQuery("SELECT *, (null) as `call24` "
						. " FROM `servicesApplied`"
						. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
						. " LEFT JOIN `clientsVisits` ON (`clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "')"
						. " LEFT JOIN `score` ON (`idscore` = (SELECT MAX(`idscore`) FROM `score` WHERE `scoreClient` = `idclients` AND `scoreDate` = `servicesAppliedDate`))"
						. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
						. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
						. " LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"
						. " LEFT JOIN `OCC_calls` ON (`idOCC_calls` = (SELECT MAX(`idOCC_calls`) FROM `OCC_calls` WHERE DATE(`OCC_callsTime`)=`servicesAppliedDate` AND `OCC_callsType`=8 AND `OCC_callsPhone` IN(SELECT `idclientsPhones` FROM `clientsPhones` WHERE `clientsPhonesClient` = `idclients` AND isnull(`clientsPhonesDeleted`))))"
						. " WHERE "
						. " `servicesAppliedDate`= '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "'"
						. " AND (isnull(`clientsOldSince`) OR `clientsOldSince`>='" . mres(($_GET['date'] ?? date("Y-m-d"))) . "')"
						. " AND `usersGroup`='12'"
						. "  "
						. ""));
//		printr($servicesApplied);
		$clients = [];
		foreach ($servicesApplied as $serviceApplied) {
			$clients[$serviceApplied['servicesAppliedClient']]['info'] = array_intersect_key($serviceApplied, array_flip([
				'idclients',
				'clientsLName',
				'clientsFName',
				'clientsMName',
				'OCC_callsTime',
				'clientsVisitsTime',
				'scoreMarket',
				'scoreDescription',
			]));
			$clients[$serviceApplied['servicesAppliedClient']]['servicesApplied'][] = $serviceApplied;
		}
//		printr($clients);

		uasort($clients, function ($a, $b) {
			$byvisit = ($a['info']['clientsVisitsTime'] == null) <=> ($b['info']['clientsVisitsTime'] == null);
			if ($byvisit) {
				return $byvisit;
			}

			$servicesAppliedTimeBegin = (array_values(array_filter($a['servicesApplied'], function ($serviceApplied) {
								return !$serviceApplied['servicesAppliedDeleted'];
							}))[0]['servicesAppliedTimeBegin'] ?? 'true') <=> (array_values(array_filter($b['servicesApplied'], function ($serviceApplied) {
								return !$serviceApplied['servicesAppliedDeleted'];
							}))[0]['servicesAppliedTimeBegin'] ?? 'true');
			if ($servicesAppliedTimeBegin) {
				return $servicesAppliedTimeBegin;
			}


			return mb_strtolower($a['info']['clientsLName']) <=> mb_strtolower($b['info']['clientsLName']);
		});
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= ($_GET['date'] ?? date("Y-m-d")); ?>"></h2>
				<?
				if (count($clients)) {
//					printr($clients[0]);
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns: repeat(10,auto); font-size: 0.8em; line-height: 0.9em;">
						<div style="display: contents;">
							<div class="B C">#</div>
							<div class="B C" style="min-width: 30px;"><i class="fas fa-calendar-alt	"></i></div>
							<div class="B C" style="min-width: 30px;"><i class="fas fa-phone-square"></i><br>24</div>
							<div class="B C" style="min-width: 30px;"><i class="fas fa-phone-square"></i><br>1.5</div>
							<div class="B C">Приём</div>
							<div class="B C" style="min-width: 30px;"><i class="fas fa-walking"></i><br>взт</div>
							<div class="B C" style="min-width: 30px;"><i class="far fa-check-square"></i><br>зчт</div>
							<div class="B C">ФИО клиента</div>
							<div class="B C">Направление</div>
							<div class="B C">ФИО оператора</div>
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

								<div>

								</div>	

								<div class="C">
								</div>

								<div class="C">
									<?
									if ($client['info']['OCC_callsTime']) {
										if ($client['info']['clientsVisitsTime']) {
											if (mystrtotime($client['info']['OCC_callsTime']) > mystrtotime($client['info']['clientsVisitsTime'])) {
												?><i class="fas fa-phone-square" title="Подтверждающий звонок состоялся в <?= date('H:i', mystrtotime($client['info']['OCC_callsTime'])); ?>, хотя клиент уже пришел в клинику в <?= date('H:i', mystrtotime($client['info']['clientsVisitsTime'])); ?>" style="color:orange;"></i><?
											} else {
												?><i class="fas fa-phone-square" title="Подтверждающий звонок состоялся в <?= date('H:i', mystrtotime($client['info']['OCC_callsTime'])); ?>, клиент пришел в <?= date('H:i', mystrtotime($client['info']['clientsVisitsTime'])); ?>" style="color:green;"></i><?
											}
										} else {
											?><i class="fas fa-phone-square" title="Подтверждающий звонок состоялся в <?= date('H:i', mystrtotime($client['info']['OCC_callsTime'])); ?>, клиент ещё не пришел" style="color:green;"></i><?
											}
											?><?
										}
										?>

								</div>




								<div class="C">
									<?
									$time = (array_values(array_filter($client['servicesApplied'], function ($serviceApplied) {
														return !$serviceApplied['servicesAppliedDeleted'];
													}))[0]['servicesAppliedTimeBegin'] ?? false);
									?>
									<?= $time ? date("H:i", mystrtotime($time)) : '--:--'; ?>
								</div>
								<div class="C">
									<?
									if ($client['info']['clientsVisitsTime']) {
										?><i class="fas fa-walking" title="Визит зафиксирован в <?= date('H:i', mystrtotime($client['info']['clientsVisitsTime'])); ?>" style="color:green;"></i><?
									} else {
										if ($time) {
											if (time() > strtotime($time)) {
												?><i class="fas fa-walking" title="Визит ещё не зафиксирован" style="color:orange;"></i><?
											}
										}
									}
									?>

								</div>
								<div class="C">
									<?
									if ($client['info']['scoreMarket'] == '0') {
										?><i class="far fa-check-square " style="color: red; cursor: pointer;" title="<?= htmlentities($client['info']['scoreDescription']) ?>"></i><?
									} elseif ($client['info']['scoreMarket'] == '1') {
										?><i class="far fa-check-square" style="color: green;"></i><?
									}
									?>
								</div>
								<div>
									<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['info']['idclients']; ?>&date=<?= ($_GET['date'] ?? date("Y-m-d")); ?>">
										<?= $client['info']['clientsLName'] ?? ''; ?>
										<?= $client['info']['clientsFName'] ?? ''; ?>
										<?= $client['info']['clientsMName'] ?? ''; ?>
									</a>
								</div>
								<div><div><?= implode(', ', array_filter(array_unique(array_column($client['servicesApplied'], 'servicesTypesName')))); ?></div></div>
								<div><?= $client['servicesApplied'][0]['usersLastName'] ?? ''; ?> <?= $client['servicesApplied'][0]['usersFirstName'] ?? ''; ?></div>
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
