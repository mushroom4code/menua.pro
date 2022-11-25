<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(98)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(98)) {
	?>E403R98<?
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
			нет абонементов, не звонили<br>
			<?
			$clientsQuery = "SELECT * FROM `clients` WHERE "
					. "(SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient`=`idclients`) = '0'"
					. " AND (SELECT COUNT(1) FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE `clientsPhonesClient` = `idclients`)=0";
			$clientsResult = (mysqlQuery($clientsQuery));
			?>
			<div style="display: inline-block;">
				<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto;">
					<div style="display: contents;">
						<div class="C B">#</div>
						<div class="C B">#</div>
						<div class="C B">Клиент</div>
					</div>

					<?
					$n = 0;
					while ($client = mfa($clientsResult)) {
						$n++;
						?>

						<div style="display: contents;">
							<div>
								<?= $n; ?>
							</div>
							<div>
								<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>"><?= $client['idclients']; ?></a>
							</div>
							<div><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>">
									<?= $client['clientsLName']; ?>
									<?= $client['clientsFName']; ?>
									<?= $client['clientsMName']; ?></a>
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
