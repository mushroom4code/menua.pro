<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';


include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (0) {
	?>E403R100<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/menu.php';
	$start = microtime(1);
//99] Таблица РБ - "нет абонементов, давно не звонили"
//98] Таблица РБ - "нет абонементов, не звонили" 
//
//	$lastCallSQL = "("
//			. "SELECT idOCC_calls,OCC_callsType,OCC_callsUser, OCC_callTypesName, clientsPhonesClient, OCC_callsTime,usersLastName,usersFirstName"
//			. " FROM `OCC_calls` AS `a`"
//			. " INNER JOIN (SELECT MAX(`idOCC_calls`) AS `idOCC_callsMAX` FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE (NOT `OCC_callsType` = 7) AND `OCC_callsTime`<=NOW() GROUP BY `clientsPhonesClient`) AS `b` ON (`a`.`idOCC_calls` = `b`.`idOCC_callsMAX`)"
//			. " LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`)"
//			. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
//			. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
//			. ")";
//	
//	printr($lastCallSQL);  
	?>




	<div class="box neutral">
		<div class="box-body">
			<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/calls/callsmenu.php'; ?>
			Нет телефонов<br>
			<?
			$clientsQuery = "SELECT * "
					. ", (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients`) as `f_sales`"
					. ", (SELECT COUNT(1) FROM `clientsVisits` WHERE `clientsVisitsClient` = `idclients`) as `clientsVisits`"
					. ", (SELECT COUNT(1) FROM `clientsPhones` WHERE `clientsPhonesClient` = `idclients` AND isnull(`clientsPhonesDeleted`)) as `clientsPhones`"
					. " FROM `clients`"
					. " LEFT JOIN (SELECT * FROM `clientStatus` LEFT JOIN `clientsStatuses` on (`idclientsStatuses`=`clientStatusStatus`) WHERE `idclientStatus` in (SELECT max(`idclientStatus`) FROM `clientStatus` group by `clientStatusClient`)) as `status` ON (`clientStatusClient` = `idclients`)"
					. " WHERE "
					. "(`idclients` IN (SELECT `clientsPhonesClient` FROM `clientsPhones` where  LENGTH(`clientsPhonesPhone`)<>11 AND   LENGTH(`clientsPhonesPhone`)<>7 AND isnull(`clientsPhonesDeleted`) group by `clientsPhonesClient`)"
					. " OR (SELECT COUNT(1) FROM `clientsPhones` WHERE `clientsPhonesClient`=`idclients` AND isnull(`clientsPhonesDeleted`)) = 0) AND (isnull(`clientStatusStatus`) OR `clientStatusStatus`<>7)";

//			print $clientsQuery; 

			$clientsResult = (mysqlQuery($clientsQuery));
			$clients = query2array($clientsResult);
//			printr($clients[0]);
			?>
			<div style="display: inline-block;">
				<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto auto auto;">
					<div style="display: contents;">
						<div>#</div>
						<div class="C B">Клиент</div>
						<div class="C B">абоны</div>
						<div class="C B">Визиты</div>
						<div class="C B">№ телефонов</div>
						<div class="C B">Статус</div>
					</div>

					<?
					usort($clients, function($a, $b) {
						if ($a['f_sales'] <=> $b['f_sales']) {
							return $b['f_sales'] <=> $a['f_sales'];
						}
						return$b['clientsVisits'] <=> $a['clientsVisits'];
					});
					$n = 0;
					foreach ($clients as $client) {
						$n++;
						?>
						<div  style="display: contents;">
							<div>
								<?= $n; ?>
							</div>
							<div>
								<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>">
									<?= $client['clientsLName']; ?>
									<?= $client['clientsFName']; ?>
									<?= $client['clientsMName']; ?>
								</a>
							</div>
							<div class="C">
								<?= $client['f_sales']; ?>
							</div>
							<div class="C">
								<?= $client['clientsVisits']; ?>
							</div>
							<div class="C">
								<?= $client['clientsPhones']; ?>
							</div>
							<div>
								<?= $client['clientsStatusesName']; ?>
							</div>
						</div>
						<?
					}
					?>
				</div>
			</div>

		</div>
	</div>



	<br>
	<br>
	<br>

	<br>
	<?
	print microtime(1) - $start;
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
