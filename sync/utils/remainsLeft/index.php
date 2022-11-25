<?php
$pageTitle = 'Приложения';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
<?

function memoryDump($_MEMORY) {
	$outArray = [];
	foreach (($_MEMORY ?? [])as $line => $data) {
		foreach ($data as $value) {
			$outArray[] = "{x: $line, y: $value}";
		}
	}
	return implode(',', $outArray);
}

$_MEMORY[__LINE__][] = (memory_get_usage());
//mysqlQuery("UPDATE `clients` SET `clientsControl` = null");
$f_subscriptions = query2array(mysqlQuery("SELECT `f_salesClient`,`clients`.*"
				. " FROM `f_subscriptions`"
				. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
				. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
				. " GROUP BY `f_salesClient`"));
$_MEMORY[__LINE__][] = (memory_get_usage());
?>
<style>
	a:visited {
		color: red;
		font-weight: bolder;
	}

</style>
<div class="box neutral">
	<div class="box-body">
		<table>
			<tr>
				<th></th>
				<th></th>
				<th>КЦ</th>
				<th>ОПЛ</th>
				<th>ФИО</th>

				<th>ДР</th>
				<th>КАРТА</th>
				<th>Вторичка с</th>
			</tr>
			<?
			$n = 0;
			usort($f_subscriptions, function($a, $b) {
				if (mb_strtolower($b['clientsControl']) <=> mb_strtolower($a['clientsControl'])) {
					return mb_strtolower($b['clientsControl']) <=> mb_strtolower($a['clientsControl']);
				}
				if (mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName'])) {
					return mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName']);
				}
				if (mb_strtolower($a['clientsFName']) <=> mb_strtolower($b['clientsFName'])) {
					return mb_strtolower($a['clientsFName']) <=> mb_strtolower($b['clientsFName']);
				}
				if (mb_strtolower($a['clientsMName']) <=> mb_strtolower($b['clientsMName'])) {
					return mb_strtolower($a['clientsMName']) <=> mb_strtolower($b['clientsMName']);
				}
			});
			$_MEMORY[__LINE__][] = (memory_get_usage());
			foreach ($f_subscriptions as $client) {
//				mysqlQuery("UPDATE `clients` SET `clientsControl` = 1 where `idclients` = '" . $client['idclients'] . "'");
				$n++;
				?>
				<tr>
					<td><?= $n; ?></td>
					<td>
						<a target="_blank" href="/sync/utils/fixabon/?client=<?= $client['idclients']; ?>"><?= $client['clientsControl'] ? '+' : '-'; ?></a>
					</td>
					<td><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>">КЦ</a></td>
					<td><a target="_blank" href="/pages/checkout/payments.php?client=<?= $client['idclients']; ?>">ОПЛ</a></td>
					<td><?= $client['clientsLName']; ?> <?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?></td>
					<td><?= $client['clientsBDay']; ?></td>
					<td><?= $client['clientsAKNum']; ?></td>
					<td><?= $client['clientsOldSince']; ?></td>
				</tr>

				<?
			}
			$_MEMORY[__LINE__][] = (memory_get_usage());
			?>
		</table>
		<? ?>
	</div>

</div>


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
				labelFormatter: function (e) {
					return e.value / 1000000;
				}
			},
			data: [{
					type: "line",
					dataPoints: [<?= memoryDump($_MEMORY); ?>]
				}]
		});
		chart.render();
	}
</script>
<br>
<br>
<br>
<div id="chartContainer" style="height: 600px; width: 100%;"></div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
