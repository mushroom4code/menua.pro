<?php
$pageTitle = 'Приветсвовать';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
	<div class="box-body">
		<?
		printr($_SERVER);
		?>
		<? printr($_USER); ?>		
		<?
		printr($_SERVER["HTTP_COOKIE"]);
		?>		
	</div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
