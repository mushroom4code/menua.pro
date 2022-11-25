<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(100)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(100)) {
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
			Есть абонементы, давно не звонили<br>
			<?
			$clientsQuery = "SELECT *,"
					. " (SELECT UNIX_TIMESTAMP(MAX(`OCC_callsTime`)) FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE `clientsPhonesClient` = `idclients` AND NOT `OCC_callsType`=7) as `OCC_callsTime`,"
					. " (SELECT `remainsQty` FROM `remains` WHERE `remainsClient` = `idclients` and `remainsDate` = (SELECT MAX(`remainsDate`) FROM `remains` WHERE `remainsClient` = `idclients`)) AS `remainsLeft` "
					. " FROM `clients` WHERE "
					. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient`=`idclients` AND isnull(`f_salesCancellationDate`)) > 0"
					. " AND (SELECT COUNT(1) FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE `clientsPhonesClient` = `idclients`) > 0";
			$clientsResult = (mysqlQuery($clientsQuery));
			?>
			<div style="display: inline-block;">
				<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto;">
					<div style="display: contents;">
						<div>#</div>
						<div class="C B">Клиент</div>
						<div class="C B">ост</div>
						<div class="C B">Последний звонок</div>
					</div>

					<?
					$clients = query2array($clientsResult);
					uasort($clients, function($a, $b) {
						return $a['OCC_callsTime'] <=> $b['OCC_callsTime'];
					});
					$n = 0;
					foreach ($clients as $client) {
						$n++;

						$remainsLeft = $client['remainsLeft'] ?? array_sum(array_column(getRemainsByClient($client['idclients']), 'f_salesContentQty'));
						if (!$remainsLeft) {
							continue;
						}
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

								<?= $remainsLeft; ?>
							</div>
							<div class="C">
								<?= date("d.m.Y", $client['OCC_callsTime']); ?>
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
