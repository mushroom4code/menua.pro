<?php
$pageTitle = 'Коллцентр';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(47)) {
//	printr($_GET);
//	[phone] => 313213
//	[clientLName] =>
//	[clientFName] =>
//	[clientMName] =>
//	idclients, GUID, clientsLName, clientsFName, clientsMName, clientsBDay, clientsAKNum, clientsAddedBy, clientsAddedAt, clientsGender, clientsIsNew, clientsCallerId, clientsCallerAdmin
	$getSQL = [];
	if (!empty($_GET['phone'])) {
		
	}
	if (!empty($_GET['clientLName'])) {
		$getSQL[] = "`clientsLName` = '" . mysqli_real_escape_string($link, trim($_GET['clientLName'])) . "'";
	}
	if (!empty($_GET['clientFName'])) {
		$getSQL[] = "`clientsFName` = '" . mysqli_real_escape_string($link, trim($_GET['clientFName'])) . "'";
	}
	if (!empty($_GET['clientMName'])) {
		$getSQL[] = "`clientsMName` = '" . mysqli_real_escape_string($link, trim($_GET['clientMName'])) . "'";
	}
	if (count($getSQL)) {
		print implode(" AND ", $getSQL);
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
			<h2>Найти</h2>
			<form action="/pages/offlinecall/clients.php" method="get">
				<table>
					<tr>
						<td>Номер телефона</td>
						<td><input type="text" id="phone" name="phone" oninput="digon();checkPhone(this.value);"></td>
					</tr>
					<tr>
						<td>Фамилия</td>
						<td><input type="text" id="clientLName" name="clientLName" onkeydown="if (event.keyCode == 32) {
									qs('#clientFName').focus();
									void(0);
									return false;
								}"></td>
					</tr>
					<tr>
						<td>Имя</td>
						<td><input type="text" id="clientFName" name="clientFName" onkeydown="if (event.keyCode == 32) {
									qs('#clientMName').focus();
									void(0);
									return false;
								}"></td>
					</tr>
					<tr>
						<td>Отчество</td>
						<td><input type="text" id="clientMName" name="clientMName" onkeydown="if (event.keyCode == 32) {
									void(0);
									return false;
								}"></td>
					</tr>
					<tr>
						<td colspan="2" style="text-align: right;"><input type="submit" value="Найти"></td>
					</tr>
				</table>
			</form>
		</div>
	</div>




<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
