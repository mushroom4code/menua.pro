<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(184)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(184)) {
	?>E403R184<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';

	$clients = query2array(mysqlQuery("SELECT *"
					. ", (SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ')  FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`"
					. ", (SELECT clientsVisitsDate FROM `clientsVisits` WHERE `idclientsVisits` = (SELECT MAX(idclientsVisits) FROM  `clientsVisits` WHERE `clientsVisitsClient` = `idclients`)) as `lastVisit`"
					. " FROM `clients`"
					. " LEFT JOIN "
					. "(SELECT "
					. " `usersLastName`,`OCC_callsClient`,`OCC_callsTime`,`OCC_callTypesName`,`OCC_callsCommentsComment`,`OCC_callsUser`"
					. " FROM `OCC_calls`"
					. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
					. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
					. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes`=`OCC_callsType`) WHERE `idOCC_calls` IN (SELECT MAX(idOCC_calls) FROM `OCC_calls` WHERE `OCC_callsType` IN (1,2,3,4,5,6,8,9,10) GROUP BY `OCC_callsClient`)) as `lastcalls` ON (`OCC_callsClient` = `idclients`)"
					. " WHERE "
					. " `idclients` IN (SELECT `f_salesClient` FROM `f_sales` WHERE `f_salesDate`>='2019-01-01' AND  `f_salesDate`<='2021-08-31')"
					. " AND (SELECT COUNT(1) FROM `clientsVisits` WHERE `clientsVisitsClient` = `idclients` AND `clientsVisitsDate`>'2021-08-31') = 0 "
					. " AND (SELECT COUNT(1) FROM `servicesApplied` WHERE `servicesAppliedClient` = `idclients` AND `servicesAppliedDate`>CURDATE()) = 0 "
					. " AND ((SELECT COUNT(1) FROM `OCC_calls` WHERE `OCC_callsTime`>'2021-12-07 00:00:00' AND `OCC_callsClient` = `idclients`)=0 OR `lastcalls`.`OCC_callsUser`='" . $_USER['id'] . "')"
					. ""
					. ""));
	?>
	<?
	usort($clients, function ($a, $b) {
		return rand() <=> rand();
	});

	function intervaltotext($from, $to) {
		$timeobj = secondsToTimeObj($to - $from);
		$Y = human_plural_form($timeobj->format('%y'), ['г', 'г', 'л'], true);
		$m = human_plural_form($timeobj->format('%m'), ['м', 'м', 'м'], true);
		$d = human_plural_form($timeobj->format('%d'), ['д', 'д', 'д'], true);
		$output = array_filter([($timeobj->format('%y') > 0 ? "$Y" : ''), ($timeobj->format('%m') > 0 ? "$m" : ''), ($timeobj->format('%d') > 0 ? "$d" : '')]);

		return mydates("d.m.Y", $from) . ' (' . ($output ? implode(', ', $output) : 'сегодня') . ')';
	}

	if (1) {
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= ($_GET['date'] ?? date("Y-m-d")); ?>"></h2>
				<?
				if (count($clients ?? [])) {
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns:repeat(8,auto);">
						<div style="display: contents;">
							<div class="B C" style="grid-row: span 2;">#</div>
							<div class="B C" style="grid-row: span 2;">ФИО клиента</div>
							<div class="B C" style="grid-row: span 2;">Тел -клиента</div>
							<div class="B C" style="grid-row: span 2;">Последний визит</div>
							<div class="B C" style="grid-column: span 4;">Последний звонок</div>
						</div>
						<div style="display: contents;">
							<div class="B C">Оператор</div>
							<div class="B C">Дата</div>
							<div class="B C">Результат</div>
							<div class="B C">Комментарий</div>
						</div> 

						<?
						$n = 0;

						foreach ($clients as $client) {
							$n++;
							$lastvisit = 'Нет визитов';
							if ($client['lastVisit'] ?? false) {
								$lastvisitTS = mystrtotime($client['lastVisit']);
								$lastvisit = intervaltotext($lastvisitTS, time());
							}

							$lastcall = 'Нет звонков';
							if ($client['OCC_callsTime'] ?? false) {
								$lastcallTS = mystrtotime($client['OCC_callsTime']);
								$lastcall = intervaltotext($lastcallTS, time());
							}
							?>
							<div style="display: contents;">
								<div><?= $n; ?></div>
								<div>

									<? if (R(47)) { ?><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients'] ?? ''; ?>"><i class="fas fa-external-link-alt"></i></a><? } ?>
									<a href="/pages/remotecall/call.php?client=<?= $client['idclients'] ?? ''; ?>">
										<?= $client['clientsLName'] ?? ''; ?>
										<?= $client['clientsFName'] ?? ''; ?>
										<?= $client['clientsMName'] ?? ''; ?>
									</a>	
									<!--</a>-->
								</div>

								<div class="C"><a target="_blank" href="/pages/remotecall/call.php?client=<?= $client['idclients'] ?? ''; ?>"><?= $client['phones'] ?? ''; ?></a></div>
								<div><?= $lastvisit; ?></div>
								<div><?= $client['usersLastName'] ?? ''; ?></div>
								<div><?= $lastcall; ?></div>
								<div><?= $client['OCC_callTypesName'] ?? ''; ?></div>
								<div><?= $client['OCC_callsCommentsComment'] ?? ''; ?></div>
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
