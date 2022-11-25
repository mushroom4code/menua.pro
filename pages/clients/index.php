<?php
$pageTitle = 'Клиенты';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(32)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(32)) {
	?>E403R32<?
} else {

	$from = $_GET['from'] ?? mydates("Y-m-01");
	$to = $_GET['to'] ?? mydates("Y-m-d");

	function getInviter($servicesAppliedArray) {///$params = ['idclients' => null, 'date' => null]
		$servicesApplied = array_filter($servicesAppliedArray, function ($serviceApplied) {
			return in_array($serviceApplied['usersGroup'], ['9', '12']);
		});
		usort($servicesApplied, function ($a, $b) {
			return $b['idservicesApplied'] <=> $a['idservicesApplied'];
		});
		if (!count($servicesApplied ?? [])) {
			return null;
		}
//		printr($servicesApplied);
		return $servicesApplied[0]['servicesAppliedBy'];
	}
	?>
	<?
	$personnel = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` FROM `users` WHERE `idusers`=" . $_USER['id'] . " "), 'idusers');
	$previsitSQL = "SELECT "
			. " `CV`.*,"
			. " `scoreMarket`,"
			. " `scoreDescription`,"
			. " `clients`.*,"
			. " `clientsSources`.*,"
			. " (SELECT COUNT(1) FROM `clientsVisits` as `PV` WHERE `PV`.`clientsVisitsClient`=`CV`.`clientsVisitsClient` AND `PV`.`clientsVisitsDate`>DATE_SUB(`CV`.`clientsVisitsDate`, INTERVAL 3 MONTH) AND `PV`.`clientsVisitsDate`<`CV`.`clientsVisitsDate`) AS `previsit`,"
			. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `CV`.`clientsVisitsClient` AND `f_salesDate`< `CV`.`clientsVisitsDate`) as `sales` "
			. " FROM `clientsVisits` AS `CV`"
			. " LEFT JOIN `clients` ON (`idclients` = `CV`.`clientsVisitsClient`)"
			. " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)"
			. " LEFT JOIN `score` ON (`scoreClient` = `idclients` AND `scoreDate` = `clientsVisitsDate`)"
			. " WHERE"
			. " `CV`.`clientsVisitsDate`>='" . min($from, $to) . "'"
			. " AND  `CV`.`clientsVisitsDate`<='" . max($from, $to) . "'"
			. "";
//			print $previsitSQL;
	$visits = array_values(array_filter(query2array(mysqlQuery($previsitSQL)), function ($visit) {
				return !$visit['previsit'] && !$visit['sales'];
			}));
//			printr($visits);
	$visits = array_map(function ($visit) {
		$visit['servicesAppliedAll'] = query2array(mysqlQuery("SELECT servicesAppliedBy,usersGroup,idservicesApplied, servicesAppliedAt,servicesAppliedDate,`servicesAppliedDeleted` "
						. " FROM `servicesApplied`"
						. " LEFT JOIN `users` AS `U2` ON (`U2`.`idusers` = `servicesAppliedBy`)"
						. " WHERE"
						. " `servicesAppliedDate` = '" . $visit['clientsVisitsDate'] . "'"
						. " AND `servicesAppliedClient`  = '" . $visit['clientsVisitsClient'] . "'"
//								. " AND isnull(`servicesAppliedDeleted`)"
//								. " AND NOT isnull(`servicesAppliedFineshed`)"
//								. " AND `usersGroup` = 12"
						. " ORDER BY `idservicesApplied` DESC"));

		$visit['servicesApplied'] = array_values(array_filter($visit['servicesAppliedAll'], function ($serviceApplied) {
					return ($serviceApplied['usersGroup']) == 12;
				}));

		return $visit;
	}, $visits);

//			
//			
//			printr($visits, 1); 
	foreach ($visits as $index => $visit) {
		if (count($visits[$index]['servicesApplied'] ?? []) && ($inviter = getInviter($visits[$index]['servicesApplied'])) == $_USER['id']) {
			$personnel[$inviter]['schedule'][$visit['clientsVisitsDate']]['clients'][$visit['clientsVisitsClient']] = [
				'idclients' => $visit['clientsVisitsClient'],
				'clientsSource' => $visit['clientsSource'],
				'clientsSourcesLabel' => $visit['clientsSourcesLabel'],
				'clientsLName' => $visit['clientsLName'],
				'clientsFName' => $visit['clientsFName'],
				'clientsMName' => $visit['clientsMName'],
				'scoreMarket' => $visit['scoreMarket'],
				'scoreDescription' => $visit['scoreDescription'],
			];
		}
	}


	$personnel = array_values($personnel);

//	printr($personnel, 1);
	?>

	<ul class="horisontalMenu"> 
		<? if (0) { ?><li><a href="/pages/personal/index.php?add">Добавить</a></li><? } ?>
		<? if (0) { ?><li><a href="/pages/personal/index.php?search">Поиск</a></li><? } ?>
	</ul>
	<div class="box neutral">
		<div class="box-body">
			<div style="display: grid; grid-template-columns: auto auto auto; margin: 20px; grid-gap: 10px;">
				<input type="date" id="from" value="<?= $from ?>">
				<input type="date" id="to" value="<?= $to ?>">
				<input type="button" value="ok" onclick="GR({from: document.querySelector(`#from`).value, to: document.querySelector(`#to`).value})">
			</div>
			<table border="1" style="border-collapse: collapse;">
				<tr>
					<th>№</th>
					<th>Дата</th>
					<th>Клиент</th>
					<th>Зачёт</th>
				</tr>

				<?
				$n = 0;
				foreach (($personnel[0]['schedule'] ?? []) as $date => $dateData) {
					foreach ($dateData['clients'] as $client) {
						if ($client['scoreMarket']) {
							$n++;
						}
						?>
						<!--printr($clients);-->
						<tr>
							<td class="C"><?= $client['scoreMarket'] ? $n : '-'; ?></td>
							<td><?= date("d.m", strtotime($date)); ?></td>
							<td>
								<?= $client['clientsLName'] ?>
								<?= $client['clientsFName'] ?>
								<?= $client['clientsMName'] ?>
							</td>
							<td>
								<?= $client['scoreMarket'] ? 'Да' : ('Нет, ' . ($client['scoreDescription'] ?? 'причина не указана')); ?>


							</td>
						</tr>
						<?
					}
				}
				?>
			</table>



		</div>
	</div>


<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
