<?php
$pageTitle = '';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$coords = [110, 118, 541];
$summs = [5000000, 7000000, 9000000, 11000000, 13000000, 15000000, 17000000, 19000000, 21000000, 23000000, 25000000, 27000000];
$dates = [
	'2020-01-01',
	'2020-03-01',
	'2020-05-01',
	'2020-07-01',
	'2020-10-01',
	'2020-12-01',
	'2021-01-01',
	'2021-03-01',
	'2021-05-01',
	'2021-07-01',
	'2021-10-01',
	'2021-12-01'
];



$LT = query2array(mysqlQuery("SELECT * FROM `LT`"));
printr($LT);
usort($LT, function ($a, $b) {
	if ($a['LTdate'] <=> $b['LTdate']) {
		return $b['LTdate'] <=> $a['LTdate'];
	}
	if ($a['LTvalue'] <=> $b['LTvalue']) {
		return $a['LTvalue'] <=> $b['LTvalue'];
	}
	return 0;
});
?>
<div class="box neutral">
	<div class="box-body">
		<div style="display: grid; grid-template-columns: repeat(<?= count($summs) ?>,auto);" class="lightGrid">
			<?
			foreach ($summs as $summ) {
				?>
				<div>
					<div><?= $summ; ?></div>
					<?
					foreach ($dates as $date) {
						?>
						<div><?= $date; ?></div>
						<div>
							<?
							foreach ($coords as $coord) {
								?>
								<div><?= $coord; ?> => <?= (LT($LT, $coord, $summ, $date)??'--'); ?></div>
								<?
							}
							?>
						</div>
						<?
					}
					?>
				</div>
				<?
			}
			?>
		</div>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
