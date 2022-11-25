<?php
$pageTitle = 'Аська';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	if (!empty($_POST['text'])) {
		if (!empty($_POST['user'])) {
			$usericq = mfa(mysqlQuery("SELECT * FROM `users` WHERE NOT isnull(`usersICQ`) AND isnull(`usersDeleted`) AND `idusers` = '" . FSI($_POST['user']) . "'"));
			$text = preg_replace("/name/", $usericq['usersFirstName'], $_POST['text']);
//			ICQ_messagesSend_SYNC($usericq['usersICQ'], $text);
			ICQMSDelay(7000, $usericq['usersICQ'], $text);
		} elseif (!empty($_POST['all']) && $_POST['all'] === '1') {
			$uwers = query2array(mysqlQuery("SELECT * FROM `users` WHERE NOT isnull(`usersICQ`) AND isnull(`usersDeleted`)"));
			foreach ($uwers as $uwer) {
				$text = preg_replace("/name/", $uwer['usersFirstName'], $_POST['text']);
//				ICQ_messagesSend_SYNC($uwer['usersICQ'], $text);
				ICQMSDelay(7000, $uwer['usersICQ'], $text);
			}
		}
		header("Location: " . GR());
		die();
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(27)) {
	?>E403R27<?
} else {
	$users = query2array(mysqlQuery("SELECT * FROM `users` WHERE isnull(`usersDeleted`) AND NOT isnull(`usersICQ`) ORDER BY `usersLastName`"));
	?>
	<div class="box neutral">
		<div class="box-body" style="text-align: center;">
			<form action="?" method="post">
				<div style="text-align: left;"><input type="checkbox" name="all" autocomplete="off" value="1" id="all"><label for="all"> Всем</label></div>
				<select name="user">
					<option></option>
					<? foreach ($users as $user) {
						?><option value="<?= $user['idusers'] ?>"><?= $user['usersLastName'] ?> <?= $user['usersFirstName'] ?> <?= $user['usersMiddleName'] ?></option><? }
					?>
				</select>
				<textarea style="width: 100%; height: 100px; resize: none; padding: 10px;" name="text"></textarea>
				<input type="submit" value="Отправить">
			</form>
		</div>
	</div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
