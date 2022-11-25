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
	DATA:
	<?
	
	?>;
	<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
	<script>
		async function readData() {
			let instastat = await fetch('https://www.instagram.com/nicol.clinic/?__a=1').then(res => res.json());
			console.log(instastat);
			dps.push({
				x: new Date(),
				y: instastat.graphql.user.edge_followed_by.count
			});
		}
		setInterval(readData, 30000);

	</script>

	<script>
		var dps = []; // dataPoints
		window.onload = function () {


			var chart = new CanvasJS.Chart("chartContainer", {
				title: {
					text: "Dynamic Data"
				},
				axisY: {
					includeZero: false,

				},
				data: [{
						type: "line",
						dataPoints: dps
					}]
			});


			var updateInterval = 1000;
			var dataLength = 200; // number of dataPoints visible at any point

			var updateChart = function () {



				if (dps.length > dataLength) {
					dps.shift();
				}

				chart.render();
			};

			updateChart(dataLength);
			setInterval(function () {
				updateChart();
			}, updateInterval);

		};
	</script>
	<div id="chartContainer" style="height: 670px; width: 1000px;"></div>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
