<?php
$pageTitle = $load['title'] = 'Подтверждение';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(143)) {
	
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
if (!R(143)) {
	?>E403R143<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<?
	if (R(143)) {

		$date = $_GET['date'] ?? date("Y-m-d");

		$clients = query2array(mysqlQuery("SELECT "
						. "`idclients`, `clientsLName`, `clientsFName`, `clientsMName`, `clientsBDay`,`clientsSource`, `clientsOldSince`, `scoreMarket`,`scoreDescription`,"
						. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients` AND `f_salesDate`< '$date'  AND `f_salesType` IN (1,2)) as `sales`,"
						. " (YEAR('$date') - YEAR(`clientsBDay`) - (DATE_FORMAT('$date', '%m%d') < DATE_FORMAT(`clientsBDay`, '%m%d'))) as `age`, "
						. " (SELECT `clientsVisitsTime` FROM `clientsVisits` WHERE `idclientsVisits` = (SELECT MAX(`idclientsVisits`) FROM `clientsVisits` WHERE  `clientsVisitsClient` = `idclients` AND `clientsVisitsDate` = '$date')) as `clientsVisitsTime`,"
						. " (SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ')  FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`"
						. ", (SELECT `clientsStatusesLabel` FROM `clientStatus` LEFT JOIN `clientsStatuses` ON (`idclientsStatuses` = `clientStatusStatus`) WHERE `idclientStatus` = (SELECT MAX(`idclientStatus`) FROM `clientStatus` WHERE `clientStatusClient` = `idclients`)) as `clientsStatusesLabel`"
						. " ,`clientsSourcesLabel` "
						. " FROM `clients`"
						. " LEFT JOIN `score` ON (`idscore` = (SELECT MAX(`idscore`) FROM `score` WHERE `scoreClient` = `idclients` AND `scoreDate` = '$date'))"
						. " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)"
						. " WHERE `idclients` IN (SELECT `servicesAppliedClient`"
						. " FROM `servicesApplied`"
						. " LEFT JOIN `users` ON (`idusers`=`servicesAppliedBy`)"
						. " WHERE `usersGroup`  in (12,17) "
						. " AND `servicesAppliedDate`='$date'"
//						. " AND isnull(`servicesAppliedContract`)"
						. ")"));
//		printr($clients);
		foreach ($clients as $clientsIndex => $client) {

			$clients[$clientsIndex]['SAcomments'] = array_column(query2array(mysqlQuery("SELECT * FROM `servicesApplied` LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA` = `idservicesApplied`) WHERE `servicesAppliedClient` = '" . $client['idclients'] . "' AND NOT isnull(`servicesAppliedCommentText`)")), 'servicesAppliedCommentText');

			$clients[$clientsIndex]['servicesApplied'] = query2array(
					mysqlQuery(""
							. "SELECT"
							. " `servicesAppliedService`,"
							. " `servicesAppliedBy`,"
							. " DATE(`servicesAppliedAt`) AS `servicesAppliedAtDate`,"
							. " `servicesAppliedTimeBegin`,"
							. " `servicesAppliedPrice`,"
							. " `servicesAppliedDeleted`,"
							. " `servicesName`,"
							. " CONCAT_WS(' ',`usersLastName`,`usersFirstName`) as `usersName`,"
							. "  DATEDIFF(`servicesAppliedDate`,`servicesAppliedAt`) as `daysAgo`,"
							. " `servicesTypesNameShort`"
							. " FROM `servicesApplied` "
							. "	LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
							. "	LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
							. " LEFT JOIN `servicesTypes` ON(`idservicesTypes` = `servicesType`)"
							. " WHERE `servicesAppliedDate` = '$date' AND `servicesAppliedClient` = " . $client['idclients'] . " ORDER BY `servicesAppliedTimeBegin`"));

			$clients[$clientsIndex]['calls']['prior'] = query2array(mysqlQuery(""
							. " SELECT *"
							. " FROM `OCC_calls`"
							. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
							. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
							. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
							. " WHERE `OCC_callsClient` = " . $client['idclients'] . " AND `OCC_callsTime` >= (SELECT MIN(`servicesAppliedAt`) FROM `servicesApplied` WHERE `servicesAppliedDate` = '$date' AND `servicesAppliedClient` = " . $client['idclients'] . ") AND  DATE(`OCC_callsTime`) < DATE_SUB('$date',INTERVAL 1 DAY) ORDER BY `OCC_callsTime`"));
			$clients[$clientsIndex]['calls']['yesterday'] = query2array(mysqlQuery(""
							. " SELECT *"
							. " FROM `OCC_calls`"
							. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
							. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
							. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
							. " WHERE `OCC_callsClient` = " . $client['idclients'] . " AND DATE(`OCC_callsTime`)= DATE_SUB('$date',INTERVAL 1 DAY) ORDER BY `OCC_callsTime`"));
			$clients[$clientsIndex]['calls']['today'] = query2array(mysqlQuery(""
							. "SELECT *"
							. " FROM `OCC_calls`"
							. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
							. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
							. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
							. " WHERE `OCC_callsClient` = " . $client['idclients'] . " AND DATE(`OCC_callsTime`)='$date' ORDER BY `OCC_callsTime`"));
		}

//		foreach ($servicesApplied as $serviceApplied) {
//			$clients[$serviceApplied['servicesAppliedClient']] = array_intersect_key($serviceApplied, array_flip([
//				'idclients',
//				'clientsLName',
//				'clientsFName',
//				'clientsMName',
//				'OCC_callsTime',
//				'clientsVisitsTime',
//				'scoreMarket',
//				'scoreDescription',
//			]));
//			$clients[$serviceApplied['servicesAppliedClient']]['servicesApplied'][] = $serviceApplied;
//		}
//		printr(array_keys($clients[0]['servicesApplied'][0]));
//		printr($clients[0], 1);
//		die();
//		printr($clients);

		uasort($clients, function ($a, $b) {
			$byvisit = ($a['clientsVisitsTime'] == null) <=> ($b['clientsVisitsTime'] == null);
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


			return mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName']);
		});
		$callColors = [
			'1' => 'orange',
			'2' => 'orange',
			'3' => 'red',
			'4' => 'pink',
			'5' => 'darkblue',
			'6' => 'red',
			'7' => 'silver',
			'8' => 'green',
			'9' => 'darkred',
			'10' => 'lightblue',
			'11' => 'yellow'
		];
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= $date; ?>"></h2>
				<?
				if (count($clients)) {
//					printr($clients[0]);
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns: repeat(18,auto); font-size: 0.8em; line-height: 0.9em;">
						<div style="display: contents;">
							<div class="B C">#</div>
							<div class="B C">ист</div>

							<div class="B C" style="min-width: 30px;"><i class="fas fa-calendar-alt" title="Записан дней назад"></i></div>
							<div class="B C" style="min-width: 30px;" title="Звонки с даты записи"><i class="fas fa-phone-square"></i><br>&Lt;</div>
							<div class="B C" style="min-width: 30px;" title="Звонки за вчера"><i class="fas fa-phone-square"></i><br>24</div>
							<div class="B C" style="min-width: 30px;" title="Звонки за сегодня"><i class="fas fa-phone-square"></i><br>1.5</div>
							<div class="B C">Приём<br>время</div>
							<div class="B C" style="min-width: 30px;"><i class="fas fa-walking"></i><br>взт</div>
							<div class="B C" style="min-width: 30px;"><i class="far fa-check-square"></i><br>зчт</div>
							<div class="B C" style="min-width: 30px;"><i class="fas fa-star" title="0" style="font-size: 0.7em; color: silver;"></i></div>
							<div class="B C"><i class="fas fa-birthday-cake"></i><br>age</div>
							<div class="B C"><i class="fas fa-info-circle"></i><br>статус</div>
							<div class="B C"><i class="fas fa-tag" title="Номерок"></i></div>
							<div class="B C">ФИО клиента</div>
							<div class="B C">Телефон(ы)<br><input type="text" style="font-size: 0.7em;" oninput="phonefilter(this.value);"></div>
							<div class="B C">Направление</div>
							<div class="B C">Комментарии</div>
							<div class="B C">ФИО оператора</div>
						</div>

						<?
						$n = 0;
						foreach ($clients as $client) {
							?>

							<?
							$n++;
							$time = (array_values(array_filter($client['servicesApplied'], function ($serviceApplied) {
												return !$serviceApplied['servicesAppliedDeleted'];
											}))[0]['servicesAppliedTimeBegin'] ?? false);
//							printr($client);
							?>
							<div data-phone="<?= $client['phones'] ?? ''; ?>"  style="display: contents; background-color: <?= ($time ?? false) ? (date("h", mystrtotime($time)) % 2 == 0 ? 'white' : '#F0F0F0') : 'initial'; ?>;">
								<div style=" background-color: inherit;">
									<?= $n; ?>


								</div>

								<div style=" background-color: inherit;" class="C">
									<?= $client['clientsSourcesLabel'] ?? ''; ?>
								</div>

								<div style=" background-color: inherit;" class="C">
									<?
									$daysAgo = array_column(array_filter($client['servicesApplied'], function ($elem) {
												return $elem['daysAgo'] !== null; // && !$elem['servicesAppliedDeleted'];
											}), 'daysAgo');
									if ($daysAgo) {
										?><?= max($daysAgo); ?><?
									}
									?>
								</div>	

								<div class="C" style=" background-color: inherit; cursor: pointer;" onclick="this.classList.toggle('showExplain');">
									<?
									foreach ($client['calls']['prior'] as $callIndex => $call) {
										?>
										<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>" title="<?= $call['OCC_callTypesName']; ?>"></i>
										<?
									}
									?>
									<div class="explain"><?
										foreach ($client['calls']['prior'] as $callIndex => $call) {
											?>
											<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>"></i>
											<?= date("d.m H:i", strtotime($call['OCC_callsTime'])); ?>
											<?= $call['OCC_callTypesName']; ?>
											<?= $call['usersLastName']; ?>
											<?= $call['usersFirstName']; ?>
											<?= $call['usersMiddleName']; ?>
											<? if ($call['OCC_callsCommentsComment']) {
												?>(<i class="fas fa-info-circle" style="color: navy;"></i> <span style=" font-style: italic;"><?= $call['OCC_callsCommentsComment']; ?></span>)<? }
											?>

											<br>
											<?
										}
										?></div>

								</div>

								<div class="C" style=" background-color: inherit; cursor: pointer;" onclick="this.classList.toggle('showExplain');">
									<?
									foreach ($client['calls']['yesterday'] as $callIndex => $call) {
										?>
										<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>" title="<?= $call['OCC_callTypesName']; ?>"></i>
										<?
									}
									?>
									<div class="explain">
										<?
										foreach ($client['calls']['yesterday'] as $callIndex => $call) {
											?>
											<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>"></i>
											<?= date("H:i", strtotime($call['OCC_callsTime'])); ?>
											<?= $call['OCC_callTypesName']; ?>
											<?= $call['usersLastName']; ?>
											<?= $call['usersFirstName']; ?>
											<?= $call['usersMiddleName']; ?>
											<? if ($call['OCC_callsCommentsComment']) {
												?>(<i class="fas fa-info-circle" style="color: navy;"></i> <span style=" font-style: italic;"><?= $call['OCC_callsCommentsComment']; ?></span>)<? }
											?>
											<br>
											<?
										}
										?>
									</div>
								</div>

								<div class="C" style=" background-color: inherit; cursor: pointer;" onclick="this.classList.toggle('showExplain');">
									<?
									foreach ($client['calls']['today'] as $callIndex => $call) {
										?>
										<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>" title="<?= $call['OCC_callTypesName']; ?>"></i>
										<?
									}
									?>
									<div class="explain">										<?
										foreach ($client['calls']['today'] as $callIndex => $call) {
											?>
											<i class="fas fa-phone-square" style="color: <?= $callColors[$call['OCC_callsType']]; ?>"></i>
											<?= date("H:i", strtotime($call['OCC_callsTime'])); ?>
											<?= $call['OCC_callTypesName']; ?>
											<?= $call['usersLastName']; ?>
											<?= $call['usersFirstName']; ?>
											<?= $call['usersMiddleName']; ?>
											<? if ($call['OCC_callsCommentsComment']) {
												?>(<i class="fas fa-info-circle" style="color: navy;"></i> <span style=" font-style: italic;"><?= $call['OCC_callsCommentsComment']; ?></span>)<? }
											?>

											<br>
											<?
										}
										?></div>

								</div>




								<div class="C" style=" background-color: inherit;">
									<?= $time ? date("H:i", mystrtotime($time)) : '--:--'; ?>
								</div>
								<div class="C" style=" background-color: inherit;">
									<?
									if ($client['clientsVisitsTime']) {
										?><i class="fas fa-walking" title="Визит зафиксирован в <?= date('H:i', mystrtotime($client['clientsVisitsTime'])); ?>" style="color:green;"></i><?
									} else {
										if ($time) {
											if (time() > strtotime($time)) {
												?><i class="fas fa-walking" title="Визит ещё не зафиксирован" style="color:orange;"></i><?
											}
										}
									}
									?>

								</div>
								<div class="C" style=" background-color: inherit;">
									<?
									if (($client['scoreMarket'] ?? false) === '0') {
										?><i class="far fa-check-square " style="color: red; cursor: pointer;" title="<?= htmlentities($client['scoreDescription']) ?>" onclick="alert('<?= htmlentities($client['scoreDescription']) ?>');"></i><?
									} elseif (($client['scoreMarket'] ?? false) === '1') {
										?><i class="far fa-check-square" style="color: green;"></i><?
									}
									?>
								</div>
								<div class="C">

									<?
									$aeREQ = mfa(mysqlQuery("
SELECT
    SUM(payment) as `SUMM`, `client`
FROM
    (SELECT
        SUM(f_paymentsAmount) as `payment`,
        f_salesClient as `client`
    FROM
       f_sales
    LEFT JOIN f_payments ON (idf_sales = f_paymentsSalesID)
    WHERE
        f_salesClient = " . $client['idclients'] . "
            AND f_salesDate = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "'
            UNION ALL
     SELECT
        SUM(f_creditsSumm) as `payment`,
        f_salesClient as `client`
    FROM
       f_sales
    LEFT JOIN f_credits ON (idf_sales = f_creditsSalesID)
    WHERE
        f_salesClient = " . $client['idclients'] . "
            AND f_salesDate = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "'
            ) AS `payments` GROUP BY `client`;"));
									$AE = getAE($aeREQ['SUMM'], ($_GET['date'] ?? date("Y-m-d")));
									?>
									<?
									if ($aeREQ['SUMM'] !== null) {

										$startColors = [
											'0' => 'silver',
											'0.5' => 'lightblue',
											'1' => 'green',
											'1.5' => 'orange'
										];
										$AEVAL = floatval(getAE($aeREQ['SUMM'], ($_GET['date'] ?? date("Y-m-d"))));
										$startColor = ($startColors["$AEVAL"] ?? 'gray');
										?>
										<i class="fas fa-star" title="<?= $AEVAL; ?>" style="font-size: 0.7em; color: <?= $startColor; ?>;"></i>
									<? } ?>



								</div>
								<div class="C" style="background-color: inherit; color: <?= ($client['age'] < 40) ? 'red' : ($client['age'] < 45 ? 'orange' : 'black'); ?>">
									<?= $client['age'] ?? ''; ?>
								</div>

								<div style=" background-color: inherit;">
									<?= $client['clientsStatusesLabel'] ?? ''; ?>
								</div>

								<div style=" background-color: inherit;">
									<?= substr($client['idclients'], -4); ?>
								</div>

								<div style=" background-color: inherit;">
									<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= ($_GET['date'] ?? date("Y-m-d")); ?>">

										<?
										if ($client['clientsOldSince'] && $client['clientsOldSince'] < $date && $client['sales'] == 0) {
											?><i class="fas fa-redo" style="color: orange; font-size: 0.8em;" title="Повторное приглашение первичного клиента"></i><?
										} elseif ($client['clientsOldSince'] ?? false) {
											if ($client['clientsOldSince'] == $date) {
												?><i class="fas fa-angle-double-up" style="color: hsl(120,100%,30%);" title="Первичный клиент"></i><?
											}
										} else {
											?><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i><?
											}
											?>
											<?= $client['clientsLName'] ?? ''; ?>
											<?= $client['clientsFName'] ?? ''; ?>
											<?= $client['clientsMName'] ?? ''; ?>
									</a>
								</div>
								<div style="background-color: inherit;">
									<?= $client['phones'] ?? ''; ?>
								</div>
								<div style=" background-color: inherit; cursor: pointer;" onclick="this.classList.toggle('showExplain');">
									<?= implode(', ', array_filter(array_unique(array_column($client['servicesApplied'], 'servicesTypesNameShort')))); ?>

									<div class="explain" style=" right: 0px; left: auto;">
										<?
										foreach ($client['servicesApplied'] as $serviceApplied) {
											if ($serviceApplied['servicesAppliedDeleted']) {
												?><span style="color: silver; text-decoration: line-through;">
													(<i class="fas fa-calendar-alt" title="Записан дней назад"></i> <?= $serviceApplied['daysAgo']; ?>)
													<?= date("H:i", strtotime($serviceApplied['servicesAppliedTimeBegin'])); ?>
													<?= $serviceApplied['servicesName']; ?></span><br>
												<?
											} else {
												?>
												(<i class="fas fa-calendar-alt" title="Записан дней назад"></i> <?= $serviceApplied['daysAgo']; ?>)
												<?= date("H:i", strtotime($serviceApplied['servicesAppliedTimeBegin'])); ?>
												<?= $serviceApplied['servicesName']; ?><br>
												<?
											}
											?>

											<?
										}
										?>
									</div>
								</div>
								<div style=" background-color: inherit;">
									<?= implode("<br>", ($client['SAcomments'] ?? [])); ?>
								</div>

								<div style=" background-color: inherit;">
									<?= implode(', ', array_unique(array_column($client['servicesApplied'], 'usersName'))); ?></div>
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
<script>
	function phonefilter(value) {
		document.querySelectorAll(`[data-phone]`).forEach(row => {
			if (value !== '') {
				if (row.dataset.phone.endsWith(value)) {
					row.style.display = 'contents';
				} else {
					row.style.display = 'none';
				}
			} else {
				row.style.display = 'contents';
			}
			console.log(row.dataset.phone.endsWith(value));
		});
	}
</script>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
