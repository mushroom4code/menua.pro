<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(111)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>
	.explain {

		text-align: left;
		display: none;
		position: absolute;
		top: 0px;
		left: 0px;
		border: 1px solid silver;
		background: white;
		font-size: 0.8em;
		line-height: 1em;
		padding: 10px;
		border-radius: 5px;
		z-index: 10;
		white-space: nowrap;
		line-height: 1.3em;
		box-shadow: 0px 0px 20px 5px hsla(0,0%,0%,0.6);

	}
	.showExplain .explain  {
		display: block;
	}

</style>


<?
$callColors = [
	'1' => 'orange',
	'2' => 'orange',
	'3' => 'red',
	'4' => 'pink',
	'5' => 'darkblue',
	'6' => 'red',
	'7' => 'silver',
	'8' => 'green',
	'9' => 'red',
	'10' => 'lightblue',
	'11' => 'violet'
];
if (!1) {
	?>E403R111<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<?
	if (1) {
		$date = ($_GET['date'] ?? date("Y-m-d"));
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= $date; ?>"></h2>
				<?
				$clients = query2array(mysqlQuery("SELECT "
								. "`idclients`, `clientsLName`, `clientsFName`, `clientsMName`, `clientsBDay`,`clientsSource`, `clientsOldSince`, `scoreMarket`,`scoreDescription`,"
								. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients` AND `f_salesDate`< '$date'  AND `f_salesType` IN (1,2)) as `sales`,"
								. " (YEAR('$date') - YEAR(`clientsBDay`) - (DATE_FORMAT('$date', '%m%d') < DATE_FORMAT(`clientsBDay`, '%m%d'))) as `age`, "
								. " (SELECT `clientsVisitsTime` FROM `clientsVisits` WHERE `idclientsVisits` = (SELECT MAX(`idclientsVisits`) FROM `clientsVisits` WHERE  `clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = '$date')) as `clientsVisitsTime`,"
								. "(SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ')  FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`"
								. ", (SELECT `clientsStatusesLabel` FROM `clientStatus` LEFT JOIN `clientsStatuses` ON (`idclientsStatuses` = `clientStatusStatus`) WHERE `idclientStatus` = (SELECT MAX(`idclientStatus`) FROM `clientStatus` WHERE `clientStatusClient` = `idclients`)) as `clientsStatusesLabel`"
								. " ,`clientsSourcesLabel` "
								. " FROM `clients`"
								. " LEFT JOIN `score` ON (`idscore` = (SELECT MAX(`idscore`) FROM `score` WHERE `scoreClient` = `idclients` AND `scoreDate` = '$date'))"
								. " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)"
								. " WHERE `idclients` IN (SELECT `servicesAppliedClient`"
								. " FROM `servicesApplied`"
								. " LEFT JOIN `users` ON (`idusers`=`servicesAppliedBy`)"
								. " WHERE `usersGroup`  = 12 "
								. " AND `servicesAppliedDate`='$date'"
								. " AND isnull(`servicesAppliedContract`)"
								. ")"));

				foreach ($clients as $clientsIndex => $client) {
					//idusers, usersLastName, usersFirstName, usersMiddleName, usersBarcode, usersDeleted, usersRightsChanged, usersFired, usersStyles, usersFinger, usersICQ, usersGroup, usersAdded, usersBday, usersCard, usersPHPSESSID, usersGUID, usersTG, usersIP
					$clients[$clientsIndex]['servicesApplied'] = query2array(
							mysqlQuery(""
									. "SELECT"
									. " `servicesAppliedService`,"
									. " `servicesAppliedBy`,"
									. " `servicesAppliedDate`,"
									. " DATE(`servicesAppliedAt`) AS `servicesAppliedAtDate`,"
									. " `servicesAppliedTimeBegin`,"
									. " `servicesAppliedPrice`,"
									. " `servicesAppliedDeleted`,"
									. " `servicesName`,"
									. "  DATEDIFF(`servicesAppliedDate`,`servicesAppliedAt`) as `daysAgo`,"
									. " `servicesTypesNameShort`,"
									. " CONCAT_WS(' ',`usersLastName`,`usersFirstName`) as `usersName`"
									. " FROM `servicesApplied` "
									. "	LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
									. " LEFT JOIN `servicesTypes` ON(`idservicesTypes` = `servicesType`)"
									. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
									. " WHERE `servicesAppliedDate` = '$date' AND `servicesAppliedClient` = " . $client['idclients'] . " ORDER BY `servicesAppliedTimeBegin`"));
				}


				if (count($clients)) {
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto auto auto;">
						<div style="display: contents;">
							<div class="B C">ФИО клиента</div>
							<div class="B C">Тел -клиента</div>
							<div class="B C">24</div>
							<div class="B C">1.5</div>

							<div class="B C">ФИО оператора</div>
							<div class="B C">Направление</div>
						</div>

						<?
						foreach ($clients as $client) {

							if (!in_array(($_GET['user'] ?? $_USER['id']), array_unique(array_column(array_filter($client['servicesApplied'], function ($serviceApplied)use ($date) {
																return $serviceApplied['servicesAppliedDate'] === $date; // && !$serviceApplied['servicesAppliedDeleted'];
															}), 'servicesAppliedBy')))) {
								continue;
							}

							$client['calls']['yesterday'] = query2array(mysqlQuery(""
											. " SELECT *"
											. " FROM `OCC_calls`"
											. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
											. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
											. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
											. " WHERE `OCC_callsClient` = " . $client['idclients'] . " AND DATE(`OCC_callsTime`)= DATE_SUB('$date',INTERVAL 1 DAY)"));
							$client['calls']['today'] = query2array(mysqlQuery(""
											. "SELECT *"
											. " FROM `OCC_calls`"
											. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
											. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
											. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
											. " WHERE `OCC_callsClient` = " . $client['idclients'] . " AND DATE(`OCC_callsTime`)='$date'  ORDER BY `OCC_callsTime`"));
//							printr($client);
							?>
							<div style="display: contents;">
								<div>
									<a href="/pages/offlinecall/schedule.php?client=<?= $client['idclients'] ?? ''; ?>" target="_blank">
										<?= $client['clientsLName'] ?? ''; ?>
										<?= $client['clientsFName'] ?? ''; ?>
										<?= $client['clientsMName'] ?? ''; ?>
									</a>
								</div>
								<div><?= $client['phones'] ?? ''; ?></div>
								<div class="C" style=" background-color: inherit; cursor: pointer;" onclick="this.classList.toggle('showExplain');">
									<?
									foreach ($client['calls']['yesterday'] as $callIndex => $call) {
										?>
										<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>" title="<?= $call['OCC_callTypesName']; ?>"></i>
										<?
									}
									?>
									<div class="explain">	
										<table>

											<?
											foreach ($client['calls']['yesterday'] as $callIndex => $call) {
												?>
												<tr>
													<td><i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>"></i>
													</td>
													<td><?= date("H:i", strtotime($call['OCC_callsTime'])); ?></td>
													<td><?= $call['OCC_callTypesName']; ?></td>
													<td><?= $call['usersLastName']; ?>
														<?= $call['usersFirstName']; ?>
														<? $call['usersMiddleName']; ?></td>
													<td><? if ($call['OCC_callsCommentsComment']) {
															?>(<i class="fas fa-info-circle" style="color: navy;"></i> <span style=" font-style: italic;"><?= $call['OCC_callsCommentsComment']; ?></span>)
														<? }
														?>
													</td>
												</tr>
												<?
											}
											?>
										</table>
									</div>
								</div>
								<div class="C" style="background-color: inherit; cursor: pointer;" onclick="this.classList.toggle('showExplain');">

									<?
									foreach ($client['calls']['today'] as $callIndex => $call) {
										?>
										<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>" title="<?= $call['OCC_callTypesName']; ?>"></i>
										<?
									}
									?>
									<div class="explain">	
										<table>

											<?
											foreach ($client['calls']['today'] as $callIndex => $call) {
												?>
												<tr>
													<td><i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>"></i>
													</td>
													<td><?= date("H:i", strtotime($call['OCC_callsTime'])); ?></td>
													<td><?= $call['OCC_callTypesName']; ?></td>
													<td><?= $call['usersLastName']; ?>
														<?= $call['usersFirstName']; ?>
														<? $call['usersMiddleName']; ?></td>
													<td><? if ($call['OCC_callsCommentsComment']) {
															?>(<i class="fas fa-info-circle" style="color: navy;"></i> <span style=" font-style: italic;"><?= $call['OCC_callsCommentsComment']; ?></span>)
														<? }
														?>
													</td>
												</tr>
												<?
											}
											?>
										</table>	
									</div>


								</div>
								<div><?=
									implode(', ', array_unique(array_column(array_filter($client['servicesApplied'], function ($serviceApplied)use ($date) {
																return $serviceApplied['servicesAppliedDate'] === $date; // && !$serviceApplied['servicesAppliedDeleted'];
															}), 'usersName')));
									?>
									<?
									?>

								</div>

								<div>
									<div style=" background-color: inherit; cursor: pointer;" onclick="this.classList.toggle('showExplain');">
										<?=
										implode(', ', array_filter(array_unique(array_column(array_filter($client['servicesApplied'], function ($serviceApplied)use ($date) {
																			return $serviceApplied['servicesAppliedDate'] === $date;
																		}), 'servicesTypesNameShort'))));
										?>

										<div class="explain" style=" right: 0px; left: auto;">
											<?
											foreach ($client['servicesApplied'] as $serviceApplied) {
												if ($serviceApplied['servicesAppliedDate'] !== $date) {
													continue;
												}
												if ($serviceApplied['servicesAppliedDeleted']) {
													?><span style="color: silver; text-decoration: line-through;">
														(<i class="fas fa-calendar-alt" title="Записан через дней"></i> <?= $serviceApplied['daysAgo']; ?>)
														<?= date("d.m H:i", strtotime($serviceApplied['servicesAppliedTimeBegin'])); ?>
														<?= $serviceApplied['servicesName']; ?></span><br>
													<?
												} else {
													?>
													(<i class="fas fa-calendar-alt" title="Записан через дней"></i> <?= $serviceApplied['daysAgo']; ?>)
													<?= date("d.m H:i", strtotime($serviceApplied['servicesAppliedTimeBegin'])); ?>
													<?= $serviceApplied['servicesName']; ?><br>
													<?
												}
												?>

												<?
											}
											?>
										</div>
									</div>

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
