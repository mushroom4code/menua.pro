<?php
$pageTitle = 'Пользователи';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>


<ul class="horisontalMenu">
	<li><a>Все</a></li>
	<li><a>Добавить</a></li>
	<li><a>Поиск</a></li>
	<li id="printSelected" style="display: none;"><a href="#" onclick="printSelected();">Печать выбранных</a></li>
</ul>
<div class="divider"></div>


<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
