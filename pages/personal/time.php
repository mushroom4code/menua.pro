<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?>
<? include 'includes/top.php'; ?>

<div style="padding: 10px;">
	<!--	122	8	Рабочее время	
		123	122	Отмечать приход/уход	
		124	122	Отмечать приход/уход (произвольная дата)	-->

	<div style="text-align: right; padding: 20px;">
		<a href="/sync/utils/usersschedule/?user=<?= $employee['idusers']; ?>" target="_blank" style="float: left;">Распечатать гарфик работы</a>
		<?
		if (!R(122)) {
			?>E403R22<?
		} else {


			if (R(124)) {
				?>
				<input type="date" id="checkinDate" style="display: inline-block; width: auto;" value="<?= date("Y-m-d"); ?>">
				<input type="time" id="checkinTime" style="display: inline-block; width: auto;" value="<?= date("H:i"); ?>">
				<?
			}
			?>

			<? if (R(123)) { ?>
				<input type="button" value="Отметить приход/уход" onclick="checkIn(<?= $employee['idusers']; ?>);">
			<? } ?>

		</div>	

		<script>
			function checkIn(user) {
				fetch('personal_IO.php', {
					body: JSON.stringify({
						action: 'checkIn',
						date: ((qs('#checkinDate') || {}).value || null),
						time: ((qs('#checkinTime') || {}).value || null),
						user: user
					}),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
						if ((jsn || {}).success) {
							GR();
						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				});
			}

		</script>
		<div id="chartContainer" style="height: 370px; width: 800px;"></div>
		<div class="console">



			<?
			$dutesARR = query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`fingerLogTime`) AS `TS` "
							. "FROM `fingerLog` "
							. "WHERE `fingerLogUser`='" . $employee['idusers'] . "'"
							. " AND `fingerLogTime`>DATE(DATE_SUB(CURDATE(), INTERVAL 30 DAY))"));
			$outputDuties = [];
			usort($dutesARR, function ($a, $b) {
				return $a['TS'] <=> $b['TS'];
			});

			foreach ($dutesARR as $duty) {
				if (!isset($outputDuties[date('Y-m-d', $duty['TS'])])) {
					$outputDuties[date('Y-m-d', $duty['TS'])]["s"] = strtotime(date("Y-m-d", $duty['TS']));
					$outputDuties[date('Y-m-d', $duty['TS'])][0] = ($duty['TS'] - $outputDuties[date('Y-m-d', $duty['TS'])]["s"]) * 1000; //$duty['TS'] - strtotime();
					$outputDuties[date('Y-m-d', $duty['TS'])][1] = ($duty['TS'] - $outputDuties[date('Y-m-d', $duty['TS'])]["s"]) * 1000 + 6 * 60 * 1000; //$duty['TS'] - strtotime();
				} else {
					$outputDuties[date('Y-m-d', $duty['TS'])][1] = ($duty['TS'] - $outputDuties[date('Y-m-d', $duty['TS'])]["s"]) * 1000; //date('Y-m-d H:i:00', $duty['TS']);
					//$duty['TS'] - strtotime(date('Y-m-d', $duty['TS']));
				}
			}
//										printr($outputDuties);
			$datapoints = [];
			foreach ($outputDuties as $date => $datapoint) {
				$datapoints[] = '{label: "' . $_WEEKDAYS['full']['nom'][date("N", strtotime($date))] . ' ' . date("d.m.Y", strtotime($date)) . '", x: new Date("' . $date . '"), y: [' . ($datapoint[0] - 3 * 60 * 60 * 1000) . ',' . ($datapoint[1] - 3 * 60 * 60 * 1000) . ']}';
			}
			?>


			<script>
				window.onload = function () {
					var chart = new CanvasJS.Chart("chartContainer", {
						title: {
							text: "Отработанное время"
						},
						axisX: {
							labelFormatter: function (e) {
								return CanvasJS.formatDate(e.value, "DD.MM");
							},
							//										labelAngle: -20
						},
						axisY: {
							minimum: (4 * 60 * 60 * 1000),
							interval: (60 * 60 * 1000),
							labelFormatter: function (e) {
								return CanvasJS.formatDate(e.value, "HH:mm");
							}
						},
						toolTip: {
							contentFormatter: function (e) {
								return "<strong>" + e.entries[0].dataPoint.label + "</strong></br>Приход: " + CanvasJS.formatDate(e.entries[0].dataPoint.y[0], "HH:mm") + "</br>Уход: " + CanvasJS.formatDate(e.entries[0].dataPoint.y[1], "HH:mm");
							}},
						data: [{
								type: "rangeColumn",
								color: 'lightgreen',
								dataPoints: [<?= implode(',', $datapoints); ?>]
							}]
					});
					chart.render();
				}

			</script>
		</div>
	</div>

<? } ?>


<? include 'includes/bottom.php'; ?>