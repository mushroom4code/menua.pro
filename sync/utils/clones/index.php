<?php
$load['title'] = $pageTitle = 'Война клонов';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!R(162)) {
	die('Нет прав доступа');
}
if (isset($_GET['del'])) {
	mysqlQuery("DELETE FROM `clients` WHERE `idclients` = '" . intval($_GET['del']) . "'");
	header("Location: " . GR('del'));
	die();
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>

	*[draggable="true"]{
		cursor: move;
		padding: 10px;
	}
	*[draggable="true"]:hover{
		background-color: red;
	}

</style>
<div class="box neutral">
	<div class="box-body">
		<div>
			<input style="margin: 20px;" type="button" value="По номеру карты" onclick="GR({filter: 'clientsAKNum'});">
			<input style="margin: 20px;" type="button" value="По ФИО" onclick="GR({filter: 'FIO'});">
		</div>
		<div style=" padding: 20px;">
			Задать вручную номера клиентов
			<div style="display: inline-block; width: 100px;"><input type="text" id="clone1"></div>
			<div style="display: inline-block; width: 100px;"><input type="text" id="clone2"></div>
			<div style="display: inline-block; width: 100px;"><input type="text" id="clone3"></div>
			<input type="button" value="совместить" onclick="window.location.href = `/sync/utils/clones/?clones=[${clone1.value},${clone2.value}${clone3.value ? (',' + clone3.value) : ''}]`">
		</div>
		<?
		$FILTER = $_GET['filter'] ?? 'clientsAKNum';
		$iterations = 0;
		$start = microtime(true);
		if (empty($_GET['clones'])) {


			$clients = query2array(mysqlQuery("SELECT "
							. " idclients,"
							. "	clientsLName,"
							. "	clientsFName,"
							. "	clientsMName,"
							. "	clientsBDay,"
							. "	clientsAKNum,"
							. "	usersLastName,"
							. "	clientsAddedAt,"
							. "	clientsGender,"
							. "clientsSource,"
							. "clientsOldSince"
							. " FROM `clients` left join `users` ON (`idusers`=`clientsAddedBy`) ORDER BY `clientsAKNum`"));
			if ($FILTER == 'FIO') {
				foreach ($clients as &$client2) {
					$client2['clientsLName'] = mb_strtolower($client2['clientsLName']);
					$client2['clientsFName'] = mb_strtolower($client2['clientsFName']);
					$client2['clientsMName'] = mb_strtolower($client2['clientsMName']);
				}
			}
			foreach ($clients as $N => $client) {


				$filtered = [];

				if ($FILTER == 'clientsAKNum') {
					if ($client['clientsAKNum']) {
						$filtered = array_filter($clients, function ($el) {
							global $client, $iterations;
							$iterations++;
							return ($el['clientsAKNum'] == $client['clientsAKNum'] && $el['idclients'] != $client['idclients']);
						});
					}
				}
				if ($FILTER == 'FIO') {
//					idclients, GUID, clientsLName, clientsFName, clientsMName, clientsBDay, clientsAKNum, clientsAddedBy, clientsAddedAt, clientsGender, clientsIsNew, clientsCallerId, clientsCallerAdmin, clientsSource, clientsOldSince
					if ($client['clientsLName'] && $client['clientsFName'] && $client['clientsMName']) {
						$filtered = array_filter($clients, function ($el) {
							global $client, $iterations;
							$iterations++;
							return ( $client['clientsLName'] == $el['clientsLName'] && $client['clientsFName'] == $el['clientsFName'] && $client['clientsMName'] == $el['clientsMName'] && $el['idclients'] != $client['idclients']);
						});
					}
				}


				$filtered[$N] = $client;
				if (count($filtered) > 1) {
					printArray(($filtered));
					?>
					<a target="_blank" href="/sync/utils/clones/?clones=<?= urlencode(json_encode(array_column($filtered, 'idclients'), 288)); ?>">Редактировать</a>
					<hr style="display: block; margin: 10px;"><?
				}
				foreach ($filtered as $n => $elem) {
					unset($clients[$n]);
				}
			}
			print '<b>' . $iterations . '</b>;';
			print microtime(true) - $start;
		} else {
			$clones = json_decode($_GET['clones'], true);
			$clients = query2array(mysqlQuery("SELECT "
							. "`idclients`,"
							. "`GUID`,"
							. "`clientsLName`,"
							. "`clientsFName`,"
							. "`clientsMName`,"
							. "`clientsBDay`,"
							. "`clientsAKNum`,"
							. "`clientsGender`,"
							. "`clientsSource`,"
							. "`clientsSourcesName`,"
							. "`clientsOldSince`"
							. " FROM `clients` left join `clientsSources` on (`idclientsSources` = `clientsSource`) where `idclients` in (" . implode(',', $clones) . ")"));
			?>
			<a href="/sync/utils/clones/">Все</a><br>
			<script>
				function startdrag(event, data) {
					event.dataTransfer.setData("data", JSON.stringify(data));
				}
				function drop(event, data) {
					let source = JSON.parse(event.dataTransfer.getData("data"));
					let target = data;
					console.log(source, target);
					if (source.source != target.target) {
						fetch('IO.php', {
							body: JSON.stringify({source: source, target: target}),
							credentials: 'include',
							method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
						}).then(result => result.text()).then(async function (text) {
							try {
								let jsn = JSON.parse(text);
								if (jsn.success) {
									GR({empty: null});
								}
							} catch (e) {
								//	MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
							}
						}); //fetch
					} else {
						console.log('source.source == target.target');
					}



				}
				function drgover() {
					event.preventDefault();
				}

			</script>
			<?
			foreach ($clients as $client) {
				?>
				<div style="border: 1px solid red; margin: 10px; display: inline-block; vertical-align: top;" ondrop="drop(event, {target: <?= $client['idclients']; ?>});" ondragover="drgover();">
					<div>
						<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>"> <?= $client['idclients']; ?></a>

					</div>
					<div style="display: inline-block;">
						<div style="display: grid; grid-template-columns: auto auto; grid-gap: 3px;">
							<?
							foreach ($client as $key => $value) {
								?>
								<div style="padding: 2px;"><?= $key; ?></div>

								<? $bg = (count(array_unique(array_column($clients, $key))) == 1) ? 'lightgreen' : 'pink'; ?>
								<div <? if (in_array($key, ['clientsSourcesName', 'idclients', 'clientsOldSince', 'clientsSource', 'clientsLName', 'clientsFName', 'clientsMName', 'clientsBDay', 'clientsAKNum', 'clientsGender'])) { ?> draggable="true" ondragstart="startdrag(event, {table: 'clients', source:<?= $client['idclients']; ?>, column: '<?= $key; ?>'});"<? } ?> style="padding: 2px 10px; border: 1px solid silver; background-color: <?= $bg; ?>; cursor: grab;"><?= $value; ?></div>
								<?
							}
							?>
						</div>
					</div>
					<h3 draggable="true" ondragstart="startdrag(event, {table: this.innerHTML, source:<?= $client['idclients']; ?>});">clientsComments</h3>
					<?
					$clientsComments = query2array(mysqlQuery("SELECT * FROM `clientsComments` WHERE `clientsCommentsClient` = '" . $client['idclients'] . "'"));
					printr($clientsComments, 1);
					?>
					<h3 draggable="true" ondragstart="startdrag(event, {table: this.innerHTML, source:<?= $client['idclients']; ?>});">clientsPassports</h3>
					<?
					$clientsPassports = query2array(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "'"));
					printr($clientsPassports, 1);
					?>
					<h3 draggable="true" ondragstart="startdrag(event, {table: this.innerHTML, source:<?= $client['idclients']; ?>});">clientsPhones</h3>
					<?
					$clientsPhones = query2array(mysqlQuery("SELECT `clientsPhonesPhone`,`clientsPhonesDeleted` FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "'"));
					printr($clientsPhones, 1);
					?>
					<h3 draggable="true" ondragstart="startdrag(event, {table: this.innerHTML, source:<?= $client['idclients']; ?>});">clientsVisits</h3>
					<?
					$clientsVisits = query2array(mysqlQuery("SELECT `clientsVisitsDate` FROM `clientsVisits` WHERE `clientsVisitsClient` = '" . $client['idclients'] . "'"));
					printr($clientsVisits, 1);
					?>
					<h3 draggable="true" ondragstart="startdrag(event, {table: this.innerHTML, source:<?= $client['idclients']; ?>});">f_sales</h3>
					<?
					$f_sales = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesClient` = '" . $client['idclients'] . "'"));
					printr($f_sales, 1);
					?>
					<h3 draggable="true" ondragstart="startdrag(event, {table: this.innerHTML, source:<?= $client['idclients']; ?>});">servicesApplied</h3>
					<?
					$servicesApplied = query2array(mysqlQuery("SELECT `servicesAppliedDate`,`servicesAppliedDeleted`,`servicesName` FROM `servicesApplied` LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) WHERE `servicesAppliedClient` = '" . $client['idclients'] . "'"));
					printr($servicesApplied, 1);

					if (
							!$servicesApplied &&
							!$clientsVisits &&
							!$clientsPhones &&
							!$clientsComments &&
							!$f_sales
					) {
						?>
						<input type="button" value="удалить" onclick="GR({del:<?= $client['idclients']; ?>})">
						<?
					}
					?>

				</div>
				<?
			}
		}
		?>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
