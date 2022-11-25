<?php
$load['title'] = $pageTitle = 'Инфи - ассистент';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(76)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(76)) {
	?>E403R76<?
} else {

	include $_SERVER['DOCUMENT_ROOT'] . '/pages/infi/menu.php';
	?>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
