<?php
$load['title'] = $pageTitle = 'Процедурный лист';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(45)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(45)) {
	?>E403R45<?
} else {




	$f_salesDraft = query2array(mysqlQuery("SELECT * FROM `f_salesDraft` WHERE `f_salesDraftAuthor` = '" . $_USER['id'] . "'"));

//	printr($f_salesDraft, 1);
	?>

	<ul class="horisontalMenu">
		<? if ($_USER['id'] == 176) { ?><li><a href="/salesdrafts.php">Планы лечения</a></li><? } ?>
		<? if ($_USER['id'] == 176) { ?><li><a href="/salesdraftstemplates.php">Шаблоны Планов лечения</a></li><? } ?>
	</ul>

	<div class="box neutral">
		<div class="box-body" >




		</div>
	</div>



<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
