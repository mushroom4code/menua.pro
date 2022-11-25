<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?>
<!DOCTYPE html>
<html>
	<head>
		<title>План на день</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
			table {
				page-break-after: always;
			}
		</style>
	</head>
	<body style="font-size: 12pt; line-height: 12pt;">

		<script src="/sync/js/basicFunctions.js" type="text/javascript"></script>

		<input type="date" onchange="GR({date: this.value});">

		<?php
		if ($_GET['date'] ?? false) {
			$servicesApplied = query2array(mysqlQuery("SELECT *"
							. " FROM `servicesApplied`"
							. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
							. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
							. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
							. " LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA` = `idservicesApplied`)"
							. " WHERE `servicesAppliedDate`='" . mres($_GET['date']) . "'"
							. " AND NOT isnull(`servicesAppliedPersonal`)"
							. " AND NOT isnull(`servicesAppliedClient`)"
							. " AND  isnull(`servicesAppliedDeleted`)"
							. " ORDER BY `servicesAppliedPersonal`,`servicesAppliedClient`"
							. ""));
			$currentUser = null;
			$currentClient = null;

			foreach ($servicesApplied as $serviceApplied) {
				if ($currentUser !== $serviceApplied['idusers']) {
					if ($currentUser) {
						?></table><?
				}
				$currentUser = $serviceApplied['idusers'];
				?><h3>
						<?= $serviceApplied['usersLastName']; ?>
						<?= $serviceApplied['usersFirstName']; ?>
						<?= $serviceApplied['usersMiddleName']; ?>
				</h3>
				<table style="border-top: 1px solid black;border-left: 1px solid black; border-collapse: collapse; width: 100%;" celllspacing="0">
					<?
				}
				?>
				<tr>
					<td  style="border-bottom: 1px solid black;border-right:  1px solid black; padding: 2px 10px; width: 200px;">
						<?
						if ($currentClient !== $serviceApplied['idclients']) {
							$currentClient = $serviceApplied['idclients'];
							?>
							<?= $serviceApplied['clientsLName']; ?>
							<?= $serviceApplied['clientsFName']; ?>
							<?= $serviceApplied['clientsMName']; ?>
							<?
						}
						?>
					</td>
					<td style="border-bottom: 1px solid black;border-right:  1px solid black; padding: 2px 10px; width: 200px;"><?= $serviceApplied['serviceNameShort'] ?? $serviceApplied['servicesName']; ?></td>
					<td style="border-bottom: 1px solid black;border-right:  1px solid black; padding: 2px 10px; width: auto"><?= $serviceApplied['servicesAppliedCommentText']; ?></td>
				</tr>
				<?
			}
			?></table><?
//	printr($servicesApplied, 1);
	} else {
		print 'Укажите дату';
	}
	?>
</body>
</html>
