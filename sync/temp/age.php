<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
 
$clients = query2array(mysqlQuery("SELECT * FROM `clients` WHERE NOT isnull(`clientsBDay`) AND isnull(`clientsPassedAway`)"));

function age($bday, $text = true) {
	if ($text) {
		return human_plural_form(secondsToTimeObj(time() - strtotime($bday))->format('%y'), ['год', 'года', 'лет'], true);
	} else {
		return intval(secondsToTimeObj(time() - strtotime($bday))->format('%y'));
	}
}
 
print count($clients);
$ages = [];
foreach ($clients as $client) {
	$age = age($client['clientsBDay'], false);
	if ($age > 100 || $age < 10) {
		continue;
	}
	$ages[$age] = ($ages[$age] ?? 0) + 1;
}

$datapoints = [];
foreach ($ages as $age => $qty) {
	$datapoints[] = '{x: ' . $age . ', y: ' . $qty . '}';
}

ksort($ages);
?>
<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
<div class="box neutral">
	<div class="box-body">
		<div id="chartContainer" style="height: 600px; width: 1000px;"></div>
	</div>
</div>

<script>
	window.onload = function () {

		var chart = new CanvasJS.Chart("chartContainer", {
			animationEnabled: true,
			exportEnabled: true,
			theme: "light1", // "light1", "light2", "dark1", "dark2"
			title: {
				text: "Возраст клиентов"
			},
			axisY: {
				includeZero: true,
				title: "Количество клиентов"
			},

			axisX: {
				title: "Возраст клиентов"
			},

			data: [{
					type: "column", //change type to bar, line, area, pie, etc
					//indexLabel: "{y}", //Shows y value on all Data Points
					indexLabelFontColor: "#5A5757",
					color: 'lightskyblue',
					indexLabelFontSize: 16,
					indexLabelPlacement: "outside",
					dataPoints: [<?= implode(',', $datapoints) ?>]
				}]
		});
		chart.render();

	}
</script>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
