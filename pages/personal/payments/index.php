<?
ini_set("memory_limit", -1);

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
?>
<?
$start = microtime(1);

include $_SERVER['DOCUMENT_ROOT'] . '/pages/personal/payments/postActions.php';

if ($_GET['from'] ?? false) {
	$_SESSION['userPaymentsFrom'] = $_GET['from'];
}
if ($_GET['to'] ?? false) {
	$_SESSION['userPaymentsTo'] = $_GET['to'];
}


$from = $_GET['from'] ?? $_SESSION['userPaymentsFrom'] ?? date("Y-m-01");
$to = $_GET['to'] ?? $_SESSION['userPaymentsTo'] ?? date("Y-m-t");

include $_SERVER['DOCUMENT_ROOT'] . '/pages/personal/payments/clientsPool.php';

include $_SERVER['DOCUMENT_ROOT'] . '/pages/personal/includes/top.php';
if (!R(121)) {
	?>
	<div class="box-body" style="background-color: white; margin: 5px; min-height: 400px;">
		E401R121
	</div>
	<?
} else {
	?>
	<div class="box-body" style="background-color: white; margin: 5px; min-height: 400px;">
		<div style="text-align: center; padding-bottom: 15px;">
			<div style="padding: 20px;">
				<select  onchange="GR({from: this.selectedOptions[0].dataset.from, to: this.selectedOptions[0].dataset.to});">
					<option></option>
					<?
					$theDate = '2021-01-01';
					while (strtotime($theDate) < time()) {
						$time = strtotime($theDate);
						$year = date("Y", $time);
						$month = date("m", $time);
						?>
						<option <?= ($from == date("Y-m-01", $time) && ($to == date("Y-m-t", $time))) ? ' selected' : '' ?> data-from="<?= date("Y-m-01", $time); ?>"  data-to="<?= date("Y-m-t", $time); ?>"><?= date("01.m.Y", $time); ?> - <?= date("t.m.Y", $time); ?></option>
			<!--					<option <?= ($from == date("Y-m-16", $time) && ($to == date("Y-m-t", $time))) ? ' selected' : '' ?>  data-from="<?= date("Y-m-16", $time); ?>" data-to="<?= date("Y-m-t", $time); ?>"><?= date("16.m.Y", $time); ?> - <?= date("t.m.Y", $time); ?></option>-->
						<?
						$theDate = date("Y-m-d", 24 * 60 * 60 + strtotime(date("Y-m-t", $time)));
					}
					?>
				</select>
			</div>

			<?
			if ($personnelPool[$_GET['employee']] ?? false) {
				$ttl = [];
				$ttl[1] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), '1'), 'total'));
				$ttl[3] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), '3'), 'total'));
				$ttl[6] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), '6'), 'total'));
				$ttl[7] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), '7'), 'total'));
				$ttl[9] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), '9'), 'total'));
				$ttl[11] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), '11'), 'total'));
				$ttl['dops'] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), 'dops'), 'total'));
				$ttl['recrut'] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), 'recrut'), 'total'));
				$ttl['coords'] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), 'coords'), 'total'));
				$ttl['diagnostics'] = array_sum(array_column(array_column(array_column($personnelPool[$_GET['employee']], 'payments'), 'diagnostics'), 'total'));
				if (1) {
					$ttl = array_filter($ttl, function ($value) {
						return $value > 0;
					});
				}
				?>
				<div style="display: inline-block;">
					<div class="lightGrid" style="display: grid; grid-template-columns: repeat(<?= count($ttl) + 2; ?>, auto);">
						<div style="display: contents;">
							<div class="C B">Дата</div>
							<? if ($ttl[1] ?? false) { ?><div class="C B">Выход</div><? } ?>
							<? if ($ttl[3] ?? false) { ?><div class="C B">Оформления<br>абонементов</div><? } ?>
							<? if ($ttl[6] ?? false) { ?><div class="C B">Оклад</div><? } ?>
							<? if ($ttl[7] ?? false) { ?><div class="C B">Оф.Оклад</div><? } ?>
							<? if ($ttl[9] ?? false) { ?><div class="C B">Почасовая</div><? } ?>
							<div class="C B">Смена</div>
							<? if ($ttl[11] ?? false) { ?><div class="C B">% от продаж</div><? } ?>
							<? if ($ttl['dops'] ?? false) { ?><div class="C B">допы</div><? } ?>
							<? if ($ttl['recrut'] ?? false) { ?><div class="C B">Рекрутинг</div><? } ?>
							<? if ($ttl['coords'] ?? false) { ?><div class="C B">% от продаж</div><? } ?>
							<? if ($ttl['diagnostics'] ?? false) { ?><div class="C B">Диагностики</div><? } ?>
						</div>
						<?
						$dn = 0;
						for ($time = strtotime($from); $time <= strtotime($to); $time += 60 * 60 * 24) {
							$dn++;
							?>


							<div style="display: contents;">
								<div><a target="_blank" href="/pages/timetracking/?date=<?= mydates('Y-m-d', $time) ?>"><?= mydates('d.m.Y', $time) ?></a></div>
								<? if ($ttl[1] ?? false) { ?>	<div><?= $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['1']['total'] ?? ''; ?><?= (($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['1']['schedule']['isDuty'] ?? false) ? ' (Д)' : '') ?><?= (($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['1']['toPayHours'] ?? false) ? (' (' . $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['1']['toPayHours'] . 'ч.)') : '') ?></div><? } ?>
								<? if ($ttl[3] ?? false) { ?><div>
										<?= $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['3']['total'] ?? ''; ?>
										<div style="color: silver; font-size: 0.6em;"><?= $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['3']['count'] ?? ''; ?>&Cross;<?= $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['3']['reward'] ?? ''; ?></div>
									</div><? } ?>
								<? if ($ttl[6] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['6']['total'] ?? 0); ?></div><? } ?>
								<? if ($ttl[7] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['7']['total'] ?? 0); ?></div><? } ?>
								<? if ($ttl[9] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['9']['total'] ?? 0); ?><?= (($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['1']['toPayHours'] ?? false) ? (' (' . $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['1']['toPayHours'] . 'ч.)') : '') ?>
										<!--<div style="color: silver; font-size: 0.6em;"><?= $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['9']['hours'] ?? ''; ?>&Cross;<?= $personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['9']['reward'] ?? ''; ?></div> comment-->
									</div><? } ?>
								<div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['1']['hoursQtyReward'] ?? 0); ?></div>
								<? if ($ttl[11] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['11']['total'] ?? 0); ?></div><? } ?>
								<? if ($ttl['dops'] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['dops']['total'] ?? 0); ?></div><? } ?>
								<? if ($ttl['recrut'] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['recrut']['total'] ?? 0); ?></div><? } ?>
								<? if ($ttl['coords'] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['coords']['total'] ?? 0); ?></div><? } ?>
								<? if ($ttl['diagnostics'] ?? false) { ?><div><?= round($personnelPool[$_GET['employee']][mydates('Y-m-d', $time)]['payments']['diagnostics']['total'] ?? 0); ?></div><? } ?>
							</div>


							<? if (mydates("d", $time) == 15) {
								?>
								<div style="display: contents;">
									<div class="C B"></div>
									<? if ($ttl[1] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[3] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[6] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[7] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[9] ?? false) { ?><div class="C B"></div><? } ?>
									<div class="C B"></div>
									<? if ($ttl[11] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['dops'] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['recrut'] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['coords'] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['diagnostics'] ?? false) { ?><div class="C B"></div><? } ?>
								</div>

								<?
							}
							?>
							<? if (mydates("d", $time) == mydates("t", $time)) {
								?>
								<div style="display: contents;">
									<div class="C B"></div>
									<? if ($ttl[1] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[3] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[6] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[7] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl[9] ?? false) { ?><div class="C B"></div><? } ?>
									<div class="C B"></div>
									<? if ($ttl[11] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['dops'] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['recrut'] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['coords'] ?? false) { ?><div class="C B"></div><? } ?>
									<? if ($ttl['diagnostics'] ?? false) { ?><div class="C B"></div><? } ?>
								</div>

								<?
							}
							?>

							<?
						}
						?>
						<? ?>


						<!-- Итоговая строка -->
						<div style="display: contents;">
							<div class="C B">Итого</div>
							<? if ($ttl[1] ?? false) { ?><div class="C B"><?= $ttl[1] ?? 0; ?></div><? } ?>
							<? if ($ttl[3] ?? false) { ?><div class="C B"><?= $ttl[3] ?? 0; ?></div><? } ?>
							<? if ($ttl[6] ?? false) { ?><div class="C B"><?= $ttl[6] ?? 0; ?></div><? } ?>
							<? if ($ttl[7] ?? false) { ?><div class="C B"><?= $ttl[7] ?? 0; ?></div><? } ?>
							<? if ($ttl[9] ?? false) { ?><div class="C B"><?= $ttl[9] ?? 0; ?></div><? } ?>
							<div class="C B">-</div>
							<? if ($ttl[11] ?? false) { ?><div class="C B"><?= $ttl[11] ?? 0; ?></div><? } ?>
							<? if ($ttl['dops'] ?? false) { ?><div class="C B"><?= $ttl['dops'] ?? 0; ?></div><? } ?>
							<? if ($ttl['recrut'] ?? false) { ?><div class="C B"><?= $ttl['recrut'] ?? 0; ?></div><? } ?>
							<? if ($ttl['coords'] ?? false) { ?><div class="C B"><?= $ttl['coords'] ?? 0; ?></div><? } ?>
							<? if ($ttl['diagnostics'] ?? false) { ?><div class="C B"><?= $ttl['diagnostics'] ?? 0; ?></div><? } ?>
						</div>
						<div style="display: contents;">
							<div class="R B" style="grid-column: span <?= count($ttl) + 1; ?>;">Итого за месяц</div>
							<div class="R B"><?= nf(array_sum($ttl)); ?></div>
						</div>
						<div style="display: contents;">
							<div class="R B" style="grid-column: span <?= count($ttl) + 1; ?>;" >Итого за месяц минус официальная часть</div>
							<div class="R B"><?= nf(array_sum($ttl) - ($ttl[7] ?? 0)); ?></div>
						</div>
					</div>
				</div>
				<?
			} else {
				?>
				<h1>Нет данных по этому пользователю</h1>
				<?
			}
			?>

		</div>
	</div>



	PGT: <?= microtime(1) - $start; ?>c;
<? } ?>
<?

function memoryDump($_MEMORY, $index = 0) {
	$outArray = [];
	foreach (($_MEMORY ?? [])as $line => $data) {
		foreach ($data as $value) {
			$outArray[] = "{x: $line, y: $value[$index]}";
		}
	}
	return implode(',', $outArray);
}
?>

<div id="chartContainer" style="height: 600px; width: 100%; display: block;"></div>
<script>
	window.onload = function () {
		var chart = new CanvasJS.Chart("chartContainer", {
			zoomEnabled: true,
			title: {
				text: "Использование памяти"
			},
			axisX: {
			},
			axisY: {
				title: "memory",
				labelFormatter: function (e) {
					return e.value / 1000000;
				}
			},
			axisY2: {
				title: "time",
			},
			data: [{
					type: "line",
					dataPoints: [<?= memoryDump($_MEMORY, 0); ?>]
				}, {
					type: "line",
					axisYType: "secondary",
					dataPoints: [<?= memoryDump($_MEMORY, 1); ?>]
				}
			]
		});
		chart.render();
	}
</script>
<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/personal/includes/bottom.php'; ?>