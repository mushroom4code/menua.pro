<?php
$pageTitle = 'Импорт клиентов';

header('Content-Type: text/HTML; charset=utf-8');
header('Content-Encoding: none; '); //disable apache compressed
ob_implicit_flush(true);
die();
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {
//	die('not now');
	$json = json_decode(file_get_contents("sales.json"), true);
	$allsales = $json['Договоры'];
//	printr($sales[1]);
	$BANKGUIDS = [];
	?>
	<style>

		.sq {
			display: inline-block;
			width: 8px;
			height: 8px;
			margin: 2px;
		}
		.ok {
			background-color: green;
		}
		.err {
			background-color: red;
		}

	</style>




	<div class="box neutral">
		<div class="box-body"><? $start = microtime(1); ?>
			<h2>Процедуры</h2>
			<div style="line-height: 10px;">
				<?
				$head = "INSERT INTO `f_subscriptions` "
						. "("
						. "`f_subscriptionsContract`,"
						. "`f_salesContentService`,"
						. "`f_salesContentPrice`,"
						. "`f_salesContentQty`,"
						. "`f_subscriptionsDate`,"
						. "`f_subscriptionsUser`"
						. ") VALUES ";
				$insert = [];
				$clients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'GUID');
				$f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID');

				$services = query2array(mysqlQuery("SELECT `idservices`,`servicesGUIDsGUID` FROM `warehouse`.`services` left join `warehouse`.`servicesGUIDs` on (`servicesGUIDsService`=`idservices`) where not isnull(`servicesGUIDsGUID`);"), 'servicesGUIDsGUID');
				foreach ($allsales as $client) {
					?><?
//					printr($client);
					if ($client['GUIDКлиента']) {
						$clientSQL = $clients[$client['GUIDКлиента']] ?? null;

						$sales = $client['Договоры'];

						foreach ($sales as $sale) {
							$idf_sales = $f_sales[$sale['GUIDДоговора']]['idf_sales'] ?? null;

							if (!$idf_sales) {
								print '<div class="sq err" title="' . ($idf_sales ?? 'NOIDFSALE') . '" onclick="alert(`' . $sale['GUIDДоговора'] . '`)"></div>';
								continue;
							}

							foreach (($sale['Сертификаты'] ?? []) as $cert) {
								foreach (($cert['Процедуры'] ?? []) as $subscription) {

									if (!($services[$subscription['GUIDПроцедуры']] ?? false)) {
										print '<div class="sq err" title="UNDEFINED PROCEDURE (' . htmlentities($subscription['GUIDПроцедуры']) . ')"></div>';
										mysqlQuery("INSERT INTO `services` SET `servicesName`='" . mres(($subscription['Наименование'] ?? false) ? $subscription['Наименование'] : 'ПУСТАЯ СТРОКА') . "'");
										$insertid = mysqli_insert_id($link);
										mysqlQuery("INSERT INTO `warehouse`.`servicesGUIDs` SET `servicesGUIDsService`='" . $insertid . "', `servicesGUIDsGUID`='" . $subscription['GUIDПроцедуры'] . "'");
										$services[$subscription['GUIDПроцедуры']] = ['idservices' => $insertid, 'servicesGUIDsGUID' => $subscription['GUIDПроцедуры']];
									}
									$insert[] = "("
											. "'" . $idf_sales . "', "
											. "'" . ($services[$subscription['GUIDПроцедуры']]['idservices']) . "', "
											. "'" . intval($subscription['Цена продажи']) . "', "
											. "'" . intval($subscription['Продано']) . "', "
											. "'" . date("Y-m-d", strtotime($cert['ДатаПродажи'])) . "', "
											. "'176'"
											. ")";
								}
								if (count($insert) > 100) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="100"></div>';
									} else {
										print '<div class="sq err" title="UNDEFINED PROCEDURE (' . htmlentities($subscription['GUIDПроцедуры']) . ')"></div>';
									}
									$insert = [];
									for ($n = 0; $n <= 10; $n++) {
										print '<!--                                                                                                    -->';
									}
									ob_flush();
									flush();
								}
							}
						}
					}
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>
				<h3>Завершено за: <?= round((microtime(1) - $start), 2); ?></h3>
			</div>
		</div>
	</div>

	<?
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
