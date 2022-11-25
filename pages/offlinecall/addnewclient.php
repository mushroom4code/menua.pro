<?php
$pageTitle = 'Коллцентр';
$load['title'] = $pageTitle;
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(47)) {
//	printr($_GET);
	$getSQL = [];

	if (!empty($_GET['idclients'])) {
		$getSQL[] = "`idclients` = '" . mysqli_real_escape_string($link, trim($_GET['idclients'])) . "'";
	} else {
		if (!empty($_GET['clientLName'])) {
			$getSQL[] = "`clientsLName` like '%" . mysqli_real_escape_string($link, trim($_GET['clientLName'])) . "%'";
		}
		if (!empty($_GET['clientFName'])) {
			$getSQL[] = "`clientsFName` like '%" . mysqli_real_escape_string($link, trim($_GET['clientFName'])) . "%'";
		}
		if (!empty($_GET['clientMName'])) {
			$getSQL[] = "`clientsMName` like '%" . mysqli_real_escape_string($link, trim($_GET['clientMName'])) . "%'";
		}
		if (!empty($_GET['phone'])) {
			$getSQL[] = "`idclients` IN (SELECT `clientsPhonesClient` FROM `clientsPhones` WHERE `clientsPhonesPhone` like '%" . mysqli_real_escape_string($link, trim($_GET['phone'])) . "%')";
		}
	}

	$clients = [];
	if (count($getSQL)) {
		$clients = query2array(mysqlQuery("SELECT *, (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients`) as `sales`  FROM"
						. " `clients`"
						. " LEFT JOIN `clientsComments` ON (`clientsCommentsClient` = `idclients` AND isnull(`clientsCommentsDate`)) "
						. " WHERE " . implode(" AND ", $getSQL)));
		if (count($clients) == 1) {
			header("Location: /pages/offlinecall/schedule.php?client=" . $clients[0]['idclients']);
			die();
		}
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(47)) {
	?>E403R47<?
} else {

	include 'menu.php';
	?>

	<div class="box neutral">
		<div class="box-body">
			<h2>Клиент</h2>
			<form action="/pages/offlinecall/addnewclient.php" method="get">
				<table>

					<tr>
						<td>Фамилия</td>
						<td><input type="text" id="clientLName"  onkeydown="if (event.keyCode == 32) {
									qs('#clientFName').focus();
									void(0);
									return false;
								}"  name="clientLName" value="<?= $_GET['clientLName'] ?? ''; ?>"></td>
					</tr>
					<tr>
						<td>Имя</td>
						<td><input type="text" id="clientFName"  onkeydown="if (event.keyCode == 32) {
									qs('#clientMName').focus();
									void(0);
									return false;
								}" name="clientFName" value="<?= $_GET['clientFName'] ?? ''; ?>"></td>
					</tr>
					<tr>
						<td>Отчество</td>
						<td><input type="text" id="clientMName" name="clientMName" value="<?= $_GET['clientMName'] ?? ''; ?>"></td>
					</tr>

					<tr>
						<td>Номер телефона</td>
						<td><input type="text" id="phone" name="phone" oninput="digon();checkPhone(this.value);" value="<?= $_GET['phone'] ?? ''; ?>"></td>
					</tr>

					<tr>
						<td>Пол</td>
						<td>
							<input type="radio" autocomplete="off" name="clientGender" <?= ($_GET['clientGender'] ?? '') === 0 ? ' checked' : ''; ?> value="0" id="clientGenderF"><label for="clientGenderF">Женский</label><br>
							<input type="radio" autocomplete="off" name="clientGender" <?= ($_GET['clientGender'] ?? '') === 1 ? ' checked' : ''; ?> value="1" id="clientGenderM"><label for="clientGenderM">Мужской</label>
						</td>
					</tr>

					<tr>
						<td>Источник:</td>
						<td><select name="idclientsSources" id="idclientsSources">
								<option></option>
								<?
								foreach (query2array(mysqlQuery("SELECT * FROM `warehouse`.`clientsSources` ORDER BY `clientsSourcesDeleted`, `clientsSourcesName`")) as $source) {
									if ($source['clientsSourcesDeleted']) {
										continue;
									}
									?>
									<option <?= $source['clientsSourcesDeleted'] ? 'disabled' : ''; ?> value="<?= $source['idclientsSources'] ?>"><?= $source['clientsSourcesName'] ?></option>
									<?
								}
								?>
							</select></td>
					</tr>


					<tr>
						<td>дата рождения</td>
						<td><input type="date" id="clientBDate" name="clientBDate"></td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea style="width: 100%; resize: none; padding: 3px;" placeholder="Комментарий" id="comment"></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2" style="text-align: right;">
							<input type="submit" value="Найти">
							<input type="submit" onclick="addClient();
									void(0);
									return false;" value="Добавить">
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
	<? if (count($clients)) {
		?>
		<div class="box neutral" style="vertical-align: top;">
			<div class="box-body">  
				<h2>Результаты поиска базе данных ИНФИНИТИ</h2>
				<div style="display: inline-block;">
					<table>
						<tr>
							<td>id</td>
							<td>Ф.И.О.</td>
							<td>№ карты</td>
							<td>Абонемент(ы)</td>
						</tr>
						<?
						foreach ($clients as $client) {
//							printr($client);
							?>
							<tr>
								<td><?= $client['idclients']; ?>]</td>
								<td>
									<a href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>">
										<?= $client['clientsLName']; ?>
										<?= $client['clientsFName']; ?>
										<?= $client['clientsMName']; ?>
									</a>
								</td>
								<td class="C"><?= $client['clientsAKNum']; ?></td>
								<td class="C"><?= $client['sales'] ? 'Есть' : 'Нет'; ?></td>
								<td><a href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>"><?= $client['clientsCommentsText'] ? '+' : ''; ?></a></td>
							</tr>

						<? } ?>
					</table>
				</div>
			</div>
		</div>
	<? } ?>
<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
