<?php
$pageTitle = 'Мобильный офис';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(78)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(78)) {
	?>E403R78<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/mobile/menu.php';
	?>


<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
