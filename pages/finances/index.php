<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(27)) {
	?>E403R27<?
} else {
	?>
	<div class="box neutral">
		<div class="box-body">
			<button>Сохранить</button>
			<input type="button" value="Сохранить">
			<input type="submit" value="Сохранить">
		</div>
	</div>


<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
