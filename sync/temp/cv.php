<?php
$pageTitle = '';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';


?>
<div class="box neutral">
	<div class="box-body">
		<table border="1" style="border-collapse: collapse;">
			<?
			foreach (dates('2021-12-01', '2021-12-16') as $date) {
				?>
				<tr>
					<td><?= $date; ?></td>
				</tr><?
			}
			?>
		</table>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
