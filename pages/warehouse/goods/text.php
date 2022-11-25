<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$items = query2array(mysqlQuery("SELECT * FROM `goods` WHERE `goodsParent`=" . $_GET['dir']));
foreach ($items as $item) {
	?>
	<?= $item['goodsName']; ?><br>


	<?
}