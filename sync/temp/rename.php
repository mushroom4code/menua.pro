<?php
$pageTitle = 'ПРОБЛЕМЫ  С ФИО';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (0) {
	?>E403R27<?
} else {
	$clients = query2array(mysqlQuery(""
					. "SELECT * FROM `clients`"
					. "LEFT JOIN `users` ON (`idusers` = `clientsAddedBy`)"
					. " where"
					. "(clientsMName like '% %' or  clientsMName like '%(%' or  clientsMName like '%)%' or  clientsMName like '%.%' or  clientsMName like '%цам%') "
					. "AND NOT "
					. "("
					. "clientsMName LIKE '%Кызы%'"
					. "OR clientsMName LIKE '%Кизи%'"
					. "OR clientsMName LIKE '%Оглы%'"
					. "OR clientsMName LIKE '%Аглы%'"
					. ")"));
	?>
	<style>
		a:visited {
			color: red;
		}
	</style>
	<div class="box neutral">
		<div class="box-body">
			<?
			foreach ($clients as $client) {
				?>
				<div><?= $client['usersLastName']; ?> <?= date("d.m.Y H:i", strtotime($client['clientsAddedAt'])); ?> <a href="https://menua.pro/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>" target="_blank"><?= $client['idclients']; ?> <?= $client['clientsLName']; ?> <?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?></a></div>
				<?
			}
			?>	
		</div>
	</div>
<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
