<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';



$clients = [
//	['idclients' => 7798],
//	['idclients' => 2908],
	['idclients' => 2934]
];
?>
<div class="box neutral">
	<div class="box-body">
		<?
		foreach ($clients as $client) {
			printr(array_sum(array_column(getRemainsByClient($client['idclients']), 'f_salesContentQty')));
		}
		?>
	</div>
</div>


<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
