<?php
$pageTitle = 'Настройки';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (isset($_POST['style'])) {
	mysqlQuery("UPDATE `users` SET `usersStyles` = " . ($_POST['style'] == 'null' ? "null" : "'" . FSI($_POST['style']) . "'") . " ");
	$_SESSION['user']['style'] = ($_POST['style'] == 'null' ? null : FSI($_POST['style']) );
	$_USER['style'] = ($_POST['style'] == 'null' ? null : FSI($_POST['style']) );
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<div class="box neutral">
	<div class="box-body">
		<form action="/pages/settings/index.php" method="post" style="text-align: center;">
			<div style="display: inline-block; width: 300px; text-align: center; padding: 10px;"><input type="radio" name="style" value="null" id="style1" <?= !isset($_USER['style']) ? 'checked' : '' ?>><label for="style1">Классический<br><img src="/css/images/designClassic.jpg" style="border: 2px solid black;" alt=""/></label></div>
			<div style="display: inline-block; width: 300px; text-align: center; padding: 10px;"><input type="radio" name="style" value="2" id="style2" <?= (isset($_USER['style']) && $_USER['style'] == 2) ? 'checked' : '' ?>><label for="style2">INFINITY 2019<br><img src="/css/images/designInfinity2019.jpg" style="border: 2px solid black;" alt=""/></label></div>
			<br>

			<input type="submit" value="Сохранить">
		</form>		
	</div>
</div>



<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
