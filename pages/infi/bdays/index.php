<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$_GET['month'] = $_GET['month'] ?? date("n");
?>
<!DOCTYPE html>
<html>
	<head>
		<title>План на день</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
			table {
				page-break-after: always;
				border-collapse: collapse;
			}
			td,th {
				padding: 5px 15px;
			}
		</style>
	</head>
	<body style="font-size: 12pt; line-height: 12pt;">
		<script src="/sync/js/basicFunctions.js" type="text/javascript"></script>
		<select onchange="GR({month: this.value});" autocomplete="off">
			<?
			for ($m = 1; $m <= 12; $m++) {
				?>
				<option value="<?= $m; ?>"  <?= $_GET['month'] == $m ? 'selected' : '' ?>><?= $_MONTHES['full']['nom'][$m]; ?></option>
				<?
			}
			?>
		</select>

		<?php
		if ($_GET['month'] ?? false) {
			$users = query2array(mysqlQuery("SELECT *,"
							. "(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions`"
							. " FROM `users`"
							. " LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`)"
							. " WHERE MONTH(`usersBday`)= '" . mres($_GET['month']) . "' AND isnull(`usersDeleted`)"
							. " ORDER BY DAY(`usersBday`)"));
//			printr($users, 1);
		}
		?>
		<table border="1">
			<tr>
				<th>Ф.И.О. Сотрудника</th>
				<th>Группа</th>
				<th>Должность</th>
				<th>Дата рождения</th>
			</tr>
			<?
			foreach ($users as $user) {
				?>
				<tr>
					<td>
						<?= $user['usersLastName']; ?>
						<?= $user['usersFirstName']; ?>
						<?= $user['usersMiddleName']; ?>
					</td>

					<td>
						<?= $user['usersGroupsName']; ?>
					</td>
					<td>
						<?= $user['positions']; ?>
					</td>
					<td style="text-align: center;">
						<?= date("d.m.Y", strtotime($user['usersBday'])); ?>
					</td>

				</tr>
				<?
			}
			?>
		</table>
	</body>
</html>
