<?php
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

		function str2utime($str) {
			$parts = explode('.', $str);
			return strtotime($parts[0]) + floatval('0.' . $parts[1]);
		}

		$datapoints = [];
		$datapoints2 = [];
		$DEBUG_pings = query2array(mysqlQuery("SELECT * FROM `DEBUG_pings` WHERE `DEBUG_pings_time`>=DATE_SUB(NOW(3), INTERVAL 12 HOUR)"));
//		printr($DEBUG_pings);
//        (
//            [idDEBUG_pings] => 1
//            [DEBUG_ping_id] => 5
//            [DEBUG_pings_time] => 2020-04-24 11:05:35
//        )
		usort($DEBUG_pings, function($a, $b) {
			return $a['idDEBUG_pings'] <=> $b['idDEBUG_pings'];
		});



		foreach ($DEBUG_pings as $data) {
			if (!isset($lsttime[$data['DEBUG_ping_id']])) {
				$lsttime[$data['DEBUG_ping_id']] = 0;
				$datapoints2[$data['DEBUG_ping_id']] = [];
			}




			$pingCount = $lsttime[$data['DEBUG_ping_id']] ? ($data['DEBUG_pings_count'] - $lsttime[$data['DEBUG_ping_id']] - 0.01 + rand(0, 1000) / 50000) : 0;

			$datapoints2[$data['DEBUG_ping_id']][] = '{x: new Date("' . $data['DEBUG_pings_time'] . '"), y: ' . ($pingCount > 0 ? ($pingCount - 1) : 'null') . '}';



			$lsttime[$data['DEBUG_ping_id']] = $data['DEBUG_pings_count'];
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
						minimum: 0,
						interval: 1.0,
						title: "Lost packets"
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
						horizontalAlign: "center",
//							dockInsidePlotArea: true,
						itemclick: toogleDataSeries
					},
					data: [
<? foreach ($datapoints2 as $name => $ping) { ?>
							{
								type: "stepLine",
								showInLegend: true,
								name: "ping(<?= $name; ?>)",
								//								color: "silver",
								xValueFormatString: "YYYY MM DD HH:mm:ss ",
								dataPoints: [<?= implode(',', $ping); ?>]
							},
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