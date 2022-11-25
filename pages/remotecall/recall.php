<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (0) {
	?>E403R144<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<?
	if (1 || R(144)) {

		$lastCallSQL = "("
				. "SELECT "
				. "`idOCC_calls` AS `LCidOCC_calls`,"
				. "`OCC_callsType` AS `LCOCC_callsType`,"
				. "`OCC_callsUser` AS `LCOCC_callsUser`, "
				. "`OCC_callTypesName` AS `LCOCC_callTypesName`,"
				. "`clientsPhonesClient` AS `LCclientsPhonesClient`,"
				. "`OCC_callsTime` AS `LCOCC_callsTime`,"
				. "`usersLastName` AS `LCusersLastName`,"
				. "`usersFirstName` AS `LCusersFirstName`"
				. " FROM `OCC_calls`"
				. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
				. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
				. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
				. " WHERE `idOCC_calls` = (SELECT MAX(`idOCC_calls`) AS `OCC_callsTimeMAX` FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE (NOT `OCC_callsType` = 7) AND `OCC_callsTime`<=NOW()) AS `b`"
				. ")";

//		"idOCC_calls": 86259,
//		"OCC_callsPhone": 17904,
//		"OCC_callsType": 7,
//		"OCC_callsDueDate": null,
//		"OCC_callsTime": "2021-05-06 12:00:00",
//		"OCC_callsUser": 176
		$callsSQL = "SELECT "
				. " * "
				. " FROM `OCC_calls`"
				. " LEFT JOIN `clients` ON (`idclients` = `OCC_callsClient`)"
				. " WHERE   `OCC_callsType` = '7'  AND `OCC_callsUser`='" . ($_GET['user'] ?? $_USER['id']) . "'";
		$calls = query2array(mysqlQuery($callsSQL)); //AND `OCC_callsMain`.`OCC_callsTime`<='" . date("Y-m-d 23:59:59") . "'
		foreach ($calls as &$call2) {
			$call2['lastcall'] = mfa(mysqlQuery(""
							. " SELECT * FROM `OCC_calls`"
							. " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
							. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes`=`OCC_callsType`) "
							. "	WHERE `idOCC_calls` = (SELECT MAX(`idOCC_calls`) FROM `OCC_calls` WHERE `OCC_callsTime` = (SELECT MAX(`OCC_callsTime`) FROM  `OCC_calls` WHERE `OCC_callsClient`='" . $call2['idclients'] . "' AND `OCC_callsType`<>7))"));
		}
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= ($_GET['date'] ?? date("Y-m-d")); ?>"></h2>
				<?
				if (count($calls)) {
//					printr($calls);
					?>
					<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6,auto);">
						<div style="display: contents;">
							<div class="B C" style="display: flex; align-items: center; justify-content: center; grid-row: span 2;">ФИО клиента</div>
							<div class="B C" style="display: flex; align-items: center; justify-content: center; grid-column: span 3;">Последний звонок</div>
							<div class="B C" style="display: flex; align-items: center; justify-content: center; grid-row: span 2;">Запланированный<br>звонок<br>(дата)</div>
							<div class="C" style="display: flex; align-items: center; justify-content: center;  grid-row: span 2;"><i class="fas fa-phone-square-alt"></i></div>
						</div>
						<div style="display: contents;">
							<div class="B C" style="display: flex; align-items: center; justify-content: center;">Дата</div>
							<div class="B C" style="display: flex; align-items: center; justify-content: center;">Результат</div>
							<div class="B C" style="display: flex; align-items: center; justify-content: center;">Комментарий</div>
						</div>

						<?
						usort($calls, function ($a, $b) {
							return $a['OCC_callsTime'] <=> $b['OCC_callsTime'];
						});
						foreach ($calls as $call) {
							$color = 'black';
							if (mystrtotime($call['OCC_callsTime']) < mystrtotime(date("Y-m-d 12:00:00"))) {
								$color = 'red';
							}
							if (mystrtotime($call['OCC_callsTime']) > mystrtotime(date("Y-m-d 12:00:00"))) {
								$color = 'silver';
							}
							?>
							<div style="display: contents; color: <?= $color; ?>">
								<div style="display: flex; align-items: center;">
									<?= $call['clientsLName']; ?>
									<?= $call['clientsFName']; ?>
									<?= $call['clientsMName']; ?>


								</div>


								<div class="C" style="display: flex; align-items: center; justify-content: center;"><?= date("d.m.Y H:i", mystrtotime($call['lastcall']['OCC_callsTime'] ?? '')); ?></div>
								<div style="display: flex; align-items: center;"><?= $call['lastcall']['OCC_callTypesName'] ?? ''; ?></div>
								<div style="display: flex; align-items: center;"><?= $call['lastcall']['OCC_callsCommentsComment'] ?? ''; ?></div>
								<div class="C" style="display: flex; align-items: center; justify-content: center;"><?= date("d.m.Y", mystrtotime($call['OCC_callsTime'])); ?></div>
								<div class="C" style="display: flex; align-items: center; justify-content: center;"><a href='/pages/remotecall/call.php?{"idOCC_calls":"<?= $call['idOCC_calls']; ?>"}'><i class="fas fa-phone-square-alt" style="color: green; font-size: 2em;"></i></a></div>
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
printr($PGT ?? '---');
