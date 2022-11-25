<?php
ini_set('memory_limit', '-1');
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setup.php';
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
		<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
		<style>
			html, body {
				min-height: 100%;
				margin: 0px;
				padding: 0px;
				/*border: 1px solid red;*/
			}
		</style>
    </head>
    <body>
		<?
		$DEBUG_APM = query2array(mysqlQuery("SELECT * FROM `DEBUG_APM` WHERE `DEBUG_APM_date`>=DATE_SUB(NOW(3), INTERVAL " . ($_GET['H'] ?? "2") . " HOUR)"));

		function str2utime($str) {
			$parts = explode('.', $str);
			return strtotime($parts[0]) + floatval('0.' . $parts[1]);
		}

//		{x: new Date(2017, 0, 3), y: 650}
//		idDEBUG_APM, DEBUG_APM_date, DEBUG_APM_value
		$datapoints = [];
		$datapoints2 = [];
		$DEBUG_pings = query2array(mysqlQuery("SELECT * FROM `DEBUG_pings` WHERE `DEBUG_pings_time`>=DATE_SUB(NOW(3), INTERVAL " . ($_GET['H'] ?? "2") . " HOUR)"));
//		printr($DEBUG_pings);
//        (
//            [idDEBUG_pings] => 1
//            [DEBUG_ping_id] => 5
//            [DEBUG_pings_time] => 2020-04-24 11:05:35
//        )
		usort($DEBUG_pings, function($a, $b) {
			return $a['idDEBUG_pings'] <=> $b['idDEBUG_pings'];
		});

		foreach ($DEBUG_APM as $data) {
			$datapoints[] = '{x: new Date("' . $data['DEBUG_APM_date'] . '"), y: ' . ($data['DEBUG_APM_value'] / ($data['DEBUG_APM_value'] > 1000 ? 60 : 1)) . '}';
		}

		foreach ($DEBUG_pings as $data) {
			if (!isset($lsttime[$data['DEBUG_ping_id']])) {
				$lsttime[$data['DEBUG_ping_id']] = 0;
				$datapoints2[$data['DEBUG_ping_id']] = [];
			}




			$pingTime = round(($lsttime[$data['DEBUG_ping_id']] ? (str2utime($data['DEBUG_pings_time']) - $lsttime[$data['DEBUG_ping_id']]) : 0), 3);
			if ($pingTime > 30) {
				$datapoints2[$data['DEBUG_ping_id']][] = '{x: new Date("' . $data['DEBUG_pings_time'] . '"), y: null}';
			} else {
				$datapoints2[$data['DEBUG_ping_id']][] = '{x: new Date("' . $data['DEBUG_pings_time'] . '"), y: ' . $pingTime . '}';
			}


			$lsttime[$data['DEBUG_ping_id']] = str2utime($data['DEBUG_pings_time']);
		}


//		idDEBUG_pings, DEBUG_ping_id, DEBUG_pings_time
		?>

		<script>
			window.onload = function () {

			var chart = new CanvasJS.Chart("chartContainer", {
			animationEnabled: false,
					theme: "light2",
					zoomEnabled: true,
					title: {
					text: "Site Traffic"
					},
					axisX: {
					valueFormatString: "HH:mm:ss",
							crosshair: {
							enabled: true,
									snapToDataPoint: true
							}
					},
					axisY: {
					title: "APS",
							includeZero: false,
							crosshair: {
							enabled: true
							}
					},
					axisY2: {
					title: "ping",
//						titleFontColor: "#C0504E",
//						lineColor: "#C0504E",
//						labelFontColor: "#C0504E",
//						tickColor: "#C0504E"
					},
					toolTip: {
					shared: true
					},
					legend: {
					cursor: "pointer",
							verticalAlign: "bottom",
							horizontalAlign: "left",
							dockInsidePlotArea: true,
							itemclick: toogleDataSeries
					},
					data: [
					{
					type: "line",
							showInLegend: true,
							name: "APS",
							xValueFormatString: "YYYY MM DD HH:mm:ss ",
							dataPoints: [<?= implode(',', $datapoints); ?>]
					}
<? foreach ($datapoints2 as $name => $ping) { ?>
						, {
						type: "stepLine",
								showInLegend: true,
								axisYType: "secondary",
								name: "ping(<?= $name; ?>)",
								//								color: "silver",
								xValueFormatString: "YYYY MM DD HH:mm:ss ",
								dataPoints: [<?= implode(',', $ping); ?>]
						}
	<?
}
?>

					]
			});
			chart.render();
			function toogleDataSeries(e) {
			if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
			e.dataSeries.visible = false;
			} else {
			e.dataSeries.visible = true;
			}
			chart.render();
			}

			}
		</script>
		<div id="chartContainer" style="height: 900px; width: 100%;"></div>


    </body>
</html>