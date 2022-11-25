<?php
$pageTitle = 'Прослушивание звонков';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/menu.php';
if (!R(178)) {
	?>E403R178<?
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

	$CDR_calls = query2array(mysqlQuery("SELECT * FROM `asterisk`.`cdr` WHERE  `calldate` >= '" . mres($from) . " 00:00:00' AND `calldate` <= '" . mres($from) . " 23:59:59'  order by id;", $CDR_link));

	$OCC_calls = query2array(mysqlQuery("SELECT * FROM `OCC_calls`"
					. "		LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`)"
					. " LEFT JOIN `clients` ON (`idclients` = `clientsPhonesClient`)"
					. " WHERE `OCC_callsType`='10' AND `OCC_callsTime`>='" . mres($from) . " 00:00:00' AND `OCC_callsTime`<='" . mres($from) . " 23:59:59' ORDER BY `idOCC_calls` DESC;"));

//	print count($CDR_calls);
//	$clients = query2array(mysqlQuery("SELECT * FROM `clientsPhones` LEFT JOIN `clients` ON (`idclients` = `clientsPhonesClient`) WHERE `clientsPhonesPhone` in ('" . implode("','", array_column($CDR_calls, 'dst')) . "') AND isnull(`clientsPhonesDeleted`)"), 'clientsPhonesPhone');
//	foreach ($CDR_calls as $id => $CDR_call) {
//		if (($callData = callExist($CDR_call['uniqueid']))) {
//			$CDR_calls[$id]['calldata'] = $callData;
//
//			if (
//					strlen($CDR_call['dst']) != 11 ||
//					!$CDR_call['billsec'] ||
//					$CDR_call['src'] < 400
//			) {
//				unset($CDR_calls[$id]);
//				continue;
//			}
//			$CDR_calls[$id]['client'] = $clients[$CDR_call['dst']] ?? null;
//		} else {
//			unset($CDR_calls[$id]);
//		}
//	}
//	printr($OCC_calls);
	?>
	<div  class="box neutral">
		<div class="box-body">
			<h2><input type="date" value="<?= $_GET['from'] ?? date("Y-m-d"); ?>" onchange="GETreloc('from', this.value);"></h2>
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(3, auto);">
				<div style="display: contents;">
					<div class="C">Время</div>
					<div class="C">Клиент</div>
					<div class="C">Телефон</div>
					<!--<div class="C">Рабочее<br>место</div>-->
					<!--<div class="C">Про-ть</div>-->
					<!--<div class="C">Ссылка</div>-->
				</div>
				<?
				foreach ($OCC_calls as $OCC_call) {
					?>
					<div style="display: contents;">
						<div class="C"><?= date('H:i', strtotime($OCC_call['OCC_callsTime'])); ?></div>
						<div>
							<?
							if ($OCC_call['idclients']) {
								?><a href="/pages/offlinecall/schedule.php?client=<?= $OCC_call['idclients']; ?>"><?= $OCC_call['clientsLName']; ?> <?= $OCC_call['clientsFName']; ?> <?= $OCC_call['clientsMName']; ?></a><?
							} else {
								?>
								Добавить клиента
								<?
							}
							?>

						</div>
						<div class="C"><?= $OCC_call['clientsPhonesPhone']; ?></div>
						<!--<div>Рабочее<br>место</div>-->
						<!--<div>Про-ть</div>-->
						<!--<div>Ссылка</div>-->
					</div>
					<?
				}
				?>

			</div>

		</div>
	</div>
<? }
?>
<!--<script src="wspami.js" type="text/javascript"></script>-->
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
