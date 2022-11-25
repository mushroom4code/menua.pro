<?php
$pageTitle = 'Приветсвовать';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
	<div class="box-body">
		<div style="padding: 10px;">
			Добро пожаловать в систему  управления стратегическими запасами.<br>
			Для продолжения работы выберите соответствующий пункт меню
		</div>

		<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
		<div id = "chartContainer" style = "height: 800px; width: 100%; min-width: 1000px; display: none;"></div>
		<?
		$lastTime = 0;
		$delta = 0;
		$query = mysqlQuery("SELECT path, size, UNIX_TIMESTAMP(`lastmod`) AS `lastmod` FROM `backup` WHERE `lastmod` > DATE_SUB(NOW(), INTERVAL 30 DAY)"); //

		while ($row = mfa($query)) {

			$datapointsArray[$row['path']][] = '{x: new Date(' . ($row['lastmod'] * 1000) . '), y: ' . $row['size'] . '}';
			if ($lastTime + 1200 > $row['lastmod']) {
				$delta += (($row['lastmod'] - $lastTime) > 0 ? ($row['lastmod'] - $lastTime) : 0);
//			print '+' . date("Y.m.d H:i", $lastTime) . ' -> ' . date("Y.m.d H:i", $row['lastmod']) . ' (' . ($row['lastmod'] - $lastTime) . ')<br>';
			} else {
				$delta += 300;
//			print '-' . date("Y.m.d H:i", $lastTime) . ' -> ' . date("Y.m.d H:i", $row['lastmod']) . ' (' . ($row['lastmod'] - $lastTime) . ')<br>';
			}
			$lastTime = $row['lastmod'];
		}
//	print 'H';

		foreach (($datapointsArray ?? []) as $key => $datapoints) {
			//$datapoints[] = '{x: new Date(' . date("Y, m-1, d", time()) . '), y: ' . $prices[$key]['subdivision'][(count($prices[$key]['subdivision']) - 1)]['pricesPrice'] . '}';
			$datapointsString[$key] = implode(",", $datapoints);
		}
//printr($datapointsString);
		?>

		<script>
			/**/
			var dataPointsArray = [
<? foreach (($datapointsString ?? []) as $isdatapoints => $datapoints) { ?>
					{
						lineThickness: 4,
						type: "scatter", //stepLine
						//“circle”, “square”, “triangle” and “cross”
						markerType: "<?
	if (preg_match('/\.php/', $isdatapoints)) {
		print 'circle';
	} elseif (preg_match('/\.css/', $isdatapoints)) {
		print 'triangle';
	} elseif (preg_match('/\.js/', $isdatapoints)) {
		print 'square';
	} else {
		print 'cross';
	}
	?>",
						xValueType: "dateTime",
						//showInLegend: true,
						legendText: '<?= $isdatapoints; ?>',
						name: '<?= $isdatapoints; ?>',
						//xValueFormatString: "DD.MM.YYYY",
						dataPoints: [<?= $datapoints; ?>]
					},
<? } ?>];
			window.onload = function () {
				if (typeof (dataPointsArray) !== 'undefined') {
					var chart = new CanvasJS.Chart("chartContainer", {
						title: {
							text: "Изменения файлов"
						},
						zoomEnabled: true,
						legend: {
							horizontalAlign: "center", // "center" , "right"
							verticalAlign: "bottom", // "top" , "bottom"
							fontSize: 15
						},
						toolTip: {
							content: "{name}<br>{x}<br>{y} bites"
						},
						axisX: {
							lineThickness: 4,
							valueFormatString: "YYYY.MM.DD HH:mm",
							labelAngle: -90,
							interval: 'auto',
							//						intervalType: "hour",
							labelFontSize: 12
						},
						axisY: {
							includeZero: false,
							labelFormatter: function (e) {
								let mult = ['B', 'KB', 'MB'];
								let i = 0;
								while (e.value > 1024) {
									e.value = e.value / 1024;
									i++;
								}

								return	Math.floor(e.value) + mult[i];
							}
						},
						data: dataPointsArray
					});
					chart.render();
				}
			};



			/* */
		</script>
	</div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
