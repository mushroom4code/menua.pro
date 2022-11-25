<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!1) {
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
			<h1>Все клиенты</h1>
			<?
			$clientsQuery = "SELECT * FROM `clients` ORDER BY `clientsLName`,`clientsFName`,`clientsMName`";
			$clientsResult = (mysqlQuery($clientsQuery));
			?>
			<div style="display: inline-block;">
				<table>
					<tr>
						<th>#</th>
						<th>#</th>
						<th>Клиент</th>
					</tr>

					<?
					$n = 0;
					while ($client = mfa($clientsResult)) {
						$n++;
						?>

						<tr>
							<td>
								<?= $n; ?>
							</td>
							<td>
								<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>"><?= $client['idclients']; ?></a>
							</td>
							<td><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>">
									<?= $client['clientsLName']; ?>
									<?= $client['clientsFName']; ?>
									<?= $client['clientsMName']; ?></a>
							</td>
						</tr>
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
