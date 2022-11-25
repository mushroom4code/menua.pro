<?php
$pageTitle = $load['title'] = 'Телеграм бот';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(196)) {
	die('E403R196');
}
?>
<? include 'menu.php'; ?>
<?
$clientsOk = mfa(mysqlQuery("SELECT COUNT(1) AS `qty` FROM `clients` WHERE NOT isnull(`clientsTG`)"))['qty'];
?>
<div class="box neutral">
	<div class="box-body">
		Телеграм <?= human_plural_form($clientsOk, ['подключил', 'подключили', 'подключили']); ?>  <?= human_plural_form($clientsOk, ['клиент', 'клиента', 'клиентов'], 1); ?>

	</div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
