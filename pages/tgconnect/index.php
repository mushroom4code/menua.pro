<?php
$pageTitle = 'Подключить ICQ';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers`='" . mres($_USER['id']) . "'"));
?>
<div class="box neutral">
	<div class="box-body">
		<?
		if ($user['usersTG']) {
			?><h3 style="text-align: center; padding: 10px;">Телеграм уже подключен</h3><?
		} else {
			$key = RDS(24);
			mysqlQuery("UPDATE `users` SET `usersPHPSESSID` = '" . $key . "' WHERE `idusers` = '" . $_USER['id'] . "'");
			?>
			<div>Чтобы подключить ТЕЛЕГРАМ и получать уведомления необходимо:</div>
			<ul style="padding: 30px;">

				<li>1. Перейти по ссылке <div class="C" style="padding: 20px;"><a target="_blank" href="https://t.me/<?= TGNICK; ?>?start=<?= $key; ?>">https://t.me/<?= TGNICK; ?></a></div></li>
				<li>2. Начать диалог.</li>
				<li>4. Порадоваться результату.</li>
			</ul>
			<?
		}
		?>
	</div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
