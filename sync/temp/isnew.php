<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(27)) {
	?>E403R27<?
} else {

	function clientIsNew2($idclient, $date = null) {
		$date = $date ?? date("Y-m-d");
		$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`= '" . $idclient . "' AND `clientsAddedAt`<='" . $date . " 23:59:59'"));
		if (!$client) {
			return null;
		}
		if ($client['clientsOldSince']) {
			return $date <= $client['clientsOldSince'];
		} else {
			return true;
		}
	}
	?>
	<div class="box neutral">
		<div class="box-body" style="font-size: 1px; line-height: 1px;">


			<?
			$clients = query2array(mysqlQuery("SELECT * FROM `clients`"));
			foreach ($clients as $client) {
				?><div style="display: block;"><?
				for ($time = strtotime("2020-06-01"); $time <= time(); $time += (60 * 60 * 24)) {

					$cin = clientIsNew2($client['idclients'], date('Y-m-d', $time));
					?><div
							title="<?= $client['idclients']; ?> <?
							if ($cin === null) {
								?>N/A<?
							}
							if ($cin === true) {
								?>NEW<?
							}
							if ($cin === false) {
								?>OLD<?
							}
							?>" style="display: inline-block; width: 1px; height: 1px; background-color: <?
							if ($cin === null) {
								?>silver<?
							}
							if ($cin === true) {
								?>lightgreen<?
							}
							if ($cin === false) {
								?>green<?
							}
							?>;"></div><? }
						?></div><? } ?></div></div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
