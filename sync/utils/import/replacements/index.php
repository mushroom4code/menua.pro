<?php
$pageTitle = 'Импорт клиентов';
header('Content-Encoding: none;');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176) {

}
die('выключено'); 
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {
//	die('not now');
	$json = json_decode(file_get_contents("Изменения и замены_2023.json"), true);
	$replacements = $json['Замены процедур'];
//	printr($sales[1]);
	$BANKGUIDS = [];
	$start = microtime(1);
//	die();
	?>
	<style>

		.sq {
			display: inline-block;
			width: 6px;
			height: 6px;
			margin: 1px;
		}
		.ok {
			background-color: green;
		}
		.err {
			background-color: red;
		}

		.warn {
			background-color: orange;
		}
		.wrapper {
			line-height: 8px;
		}
		.box {
			margin: 0.0em 1.2em 1.0em 1.2em;
		}
	</style>


	<div class="box neutral">
		<div class="box-body">
			<h2>Импорт замен</h2>
			<? $start = microtime(1); ?>
			<div class="wrapper">
				<?
				$clients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'GUID');
				$services = query2array(mysqlQuery("SELECT `idservices`,`servicesGUIDsGUID` FROM `warehouse`.`services` left join `warehouse`.`servicesGUIDs` on (`servicesGUIDsService`=`idservices`) where not isnull(`servicesGUIDsGUID`);"), 'servicesGUIDsGUID');
				$f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID');

				$noguids = 0;
				$withguids = 0;
				$head = "INSERT INTO `f_subscriptions` (`f_subscriptionsContract`, `f_salesContentService`, `f_salesContentPrice`, `f_salesContentQty`, `f_subscriptionsDate`, `f_subscriptionsUser`,  `f_subscriptionsImport`) VALUES ";
				$insert = [];
				foreach ($replacements as $client) {
//				printr($client);
					if ($client['GUIDКлиента']) {

						$replacenetDocuments = $client['Документ замены'];

						foreach ($replacenetDocuments as $replacenetDocument) {


							if (!($replacenetDocument['Процедуры'] ?? false)) {
								print '<div class="sq err" title="Нет процедур"></div>';

								continue;
							}
							$guidTemp = false;
							usort($replacenetDocument['Процедуры'], function ($a, $b) {
								return ($b['GUIDДоговора'] ?? false) <=> ($a['GUIDДоговора'] ?? false);
							});
							foreach ($replacenetDocument['Процедуры'] as $replacement) {
								if ($replacement['GUIDДоговора'] ?? false) {
									$guidTemp = $replacement['GUIDДоговора'];
								}

								if (!($replacement['GUIDДоговора'] ?? false) && $guidTemp) {
									$replacement['GUIDДоговора'] = $guidTemp;
								}

								if (!($replacement['GUIDДоговора'] ?? false)) {
									$noguids++;
									print '<div class="sq err" title="нет GUID договора" onclick="alert(\'Номер документа замены: ' . $replacenetDocument['Номер документа'] . '\');"></div>';
									continue;
								}
								$withguids++;

								$isf_sales = ($f_sales[$replacement['GUIDДоговора']]['idf_sales'] ?? false);
								if (!$isf_sales) {
									print '<div class="sq err" title="нет idf_sales" onclick="alert(\'' . $replacement['GUIDДоговора'] . '\');"></div>';
									continue;
								}

								if (!($replacement['GUIDПроцедуры'] ?? false)) {
									print '<div class="sq err" title="нет GUIDПроцедуры"></div>';
									continue;
								}

								$idservices = ($services[$replacement['GUIDПроцедуры']]['idservices'] ?? false);
								if (!$idservices) {
									mysqlQuery("INSERT INTO `services` SET `servicesName`='" . mres(($replacement['Процедура'] ?? false) ? $replacement['Процедура'] : 'ПУСТАЯ СТРОКА') . "'");
									$insertid = mysqli_insert_id($link);
									mysqlQuery("INSERT INTO `warehouse`.`servicesGUIDs` SET `servicesGUIDsService`='" . $insertid . "', `servicesGUIDsGUID`='" . $replacement['GUIDПроцедуры'] . "'");
									$services[$replacement['GUIDПроцедуры']] = ['idservices' => $insertid, 'servicesGUIDsGUID' => $replacement['GUIDПроцедуры']];
									$idservices = $insertid;
								}

								$qty = ($replacement['СписаноСКлиента'] ?? $replacement['ОставленоУКлиента'] ?? false);
								if (!$qty) {
									print '<div class="sq warn" title="нет количество =0" onclick="alert(\'' . $replacement['GUIDДоговора'] . '\');"></div>';
									continue;
								}
//							printr($replacement);
//								print '<div class="sq ok"></div>';
								$insert[] = "('" . $isf_sales . "',"
										. " '" . $idservices . "',"
										. " '" . $replacement['Цена продажи'] . "',"
										. " '" . $qty . "',"
										. " '" . date("Y-m-d", strtotime($replacement['Дата замены'])) . "',"
										. " 176,"
										. "  NOW())";
//							[GUIDПроцедуры] => 6ae14b6c-ecb1-11e4-aba5-002590649803

								if (count($insert) > 50) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="25"></div>';
									} else {
										print '<div class="sq warn" title="MYSQL ERROR"></div>';
									}
									$insert = [];
								}
								for ($n = 0; $n <= 10; $n++) {
									print '<!--                                                                                                    -->';
								}
								flush();
							}
						}
					} else {
						print '<div class="sq err" title="NO GUIDКлиента"></div>';
					}
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>

			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>

	<?
}
print 'PGT:' . (microtime(1) - $start);
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
