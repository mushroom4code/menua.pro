<?php
$pageTitle = 'Прослушивание звонков';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
if (!R(173)) {
	?>E403P32<?
} else {

	function callExist($idcalls) {
		if ($idcalls ?? false) {
			$ch = curl_init('http://192.168.128.100/ivr_stat/audio/' . $idcalls . '.mp3');
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_exec($ch);
			$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($retcode == 200) {
				$cdrtext = [
					'href' => '<a target="_blank" href="http://192.168.128.100/ivr_stat/audio/' . $idcalls . '.mp3' . '">Послушать звонок</a>'
				];
			} else {
				$cdrtext = null;
			}
			return $cdrtext;
		}
	}

	$from = $_GET['from'] ?? date('Y-m-d');

	$CDR_calls = query2array(mysqlQuery("SELECT *,`billsec`,`calldate` AS `OCC_callsTime`, `src`, `dst`, `uniqueid` FROM `asterisk`.`cdr` WHERE  `calldate` >= '" . mres($from) . " 00:00:00' AND `calldate` <= '" . mres($from) . " 23:59:59'  order by id;", $CDR_link));
//	printr($CDR_calls);

	$clients = query2array(mysqlQuery("SELECT * FROM `clientsPhones` LEFT JOIN `clients` ON (`idclients` = `clientsPhonesClient`) WHERE `clientsPhonesPhone` in ('" . implode("','", array_column($CDR_calls, 'dst')) . "') AND isnull(`clientsPhonesDeleted`)"), 'clientsPhonesPhone');

	foreach ($CDR_calls as $id => $CDR_call) {
		if (($callData = callExist($CDR_call['uniqueid']))) {
			$CDR_calls[$id]['calldata'] = $callData;

			if (
					strlen($CDR_call['dst']) != 11 ||
					!$CDR_call['billsec'] ||
					$CDR_call['src'] < 400
			) {
				unset($CDR_calls[$id]);
				continue;
			}
			$CDR_calls[$id]['client'] = $clients[$CDR_call['dst']] ?? null;
		} else {
			unset($CDR_calls[$id]);
		}
	}
//	print '<br> отфильтровано: ' . count($CDR_calls);
//	printr($CDR_calls);
	?>
	<div  class="box neutral">
		<div class="box-body">
			<h2><input type="date" value="<?= $_GET['from'] ?? date("Y-m-d"); ?>" onchange="GETreloc('from', this.value);"></h2>
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6, auto);">
				<div style="display: contents;">
					<div class="C">Время</div>
					<div class="C">Клиент</div>
					<div class="C">Телефон</div>
					<div class="C">Рабочее<br>место</div>
					<div class="C">Про-ть</div>
					<div class="C">Ссылка</div>
				</div>
				<?
				foreach ($CDR_calls as $CDR_call) {
					?>
					<div style="display: contents;">
						<div>
							<?= date("H:i", strtotime($CDR_call['OCC_callsTime'])); ?>
						</div>
						<div><?
							if ($CDR_call['client']) {
								?>
								<a href="/pages/offlinecall/schedule.php?client=<?= $CDR_call['client']['idclients']; ?>" target="_blank">
									<?= $CDR_call['client']['idclients']; ?>]
									<?= (!$CDR_call['client']['clientsOldSince'] || $CDR_call['client']['clientsOldSince'] >= $from) ? '<i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%); margin: 0 3px;"></i>' : ''; ?>
									<?= $CDR_call['client']['clientsLName']; ?>
									<?= $CDR_call['client']['clientsFName']; ?>
									<?= $CDR_call['client']['clientsMName']; ?>
								</a>
								<?
							} else {
								?><?
							}
							?></div>
						<div class="C"><?= $CDR_call['dst']; ?></div>
						<div class="C"><?
							preg_match('/(\d{3})(?:-)/', $CDR_call['channel'], $matches);
							print $matches[1];
							?></div>
						<div class="C"><?= $CDR_call['billsec']; ?>сек.</div>
						<div><?= $CDR_call['calldata']['href']; ?></div>
					</div>
					<?
				}
				?>

			</div>

		</div>
	</div>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
