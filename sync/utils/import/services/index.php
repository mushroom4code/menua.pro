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
	$json = json_decode(file_get_contents("Procedures.json"), true);
	$json2 = json_decode(file_get_contents("Services.json"), true);
	$procedures = array_merge($json['procedures'], $json2['services']);

//	printr($procedures);

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
			<h2>Процедур</h2>
			<? $start = microtime(1); ?>
			<div class="wrapper">

				<?
				$found = 0;
				$servicesByGUID = query2array(mysqlQuery("SELECT `idservices`,`servicesGUIDsGUID` FROM `warehouse`.`services` left join `warehouse`.`servicesGUIDs` on (`servicesGUIDsService`=`idservices`);"), 'servicesGUIDsGUID');
				$servicesByName = query2array(mysqlQuery("SELECT `idservices`,`servicesName` FROM `warehouse`.`services` left join `warehouse`.`servicesGUIDs` on (`servicesGUIDsService`=`idservices`);"), 'servicesName');
//				printr($procedures);
				foreach ($procedures as $procedure) {
					if (!($servicesByGUID[$procedure['GUID']] ?? false)) {
						if (!($servicesByName[$procedure['Наименование']] ?? false)) {
//
							mysqlQuery("INSERT INTO `warehouse`.`services` SET "
									. " `servicesName` = '" . mres($procedure['Наименование']) . "',"
									. (($procedure['Тип номенклатуры'] ?? false) == 'Товар' ? ("`servicesParent`='8046',") : "`servicesParent`='10869',")
									. " `serviceNameShort` = '" . mres($procedure['Сокращенное наименование'] ?? '') . "', "
									. " `servicesDuration` = " . ($procedure['Длительность'] ?? 'null') . "");
							$idservices = mysqli_insert_id($link);
							mysqlQuery("INSERT INTO `warehouse`.`servicesGUIDs`"
									. "SET "
									. "`servicesGUIDsService`='" . $idservices . "',"
									. "`servicesGUIDsGUID`='" . $procedure['GUID'] . "'"
							);
// , , , servicesPricesType
							if ($procedure['Цена'] ?? false) {
								mysqlQuery("INSERT INTO `warehouse`.`servicesPrices`"
										. "SET "
										. "`servicesPricesService`='" . $idservices . "',"
										. "`servicesPricesDate`='2000-01-01',"
										. "`servicesPricesPrice`='" . $procedure['Цена'] . "'"
										. ""
								);
							}
							print '<div class="sq warn" onclick="alert(\'' . mres($procedure['Наименование']) . '\');"></div>';
						} else {
							$service = $servicesByName[$procedure['Наименование']];
							mysqlQuery("INSERT INTO `warehouse`.`servicesGUIDs`"
									. "SET "
									. "`servicesGUIDsService`='" . $service['idservices'] . "',"
									. "`servicesGUIDsGUID`='" . $procedure['GUID'] . "'"
							);
//							printr($service);
							print '<div class="sq ok" onclick="alert(\'' . mres($procedure['Наименование']) . '\');"></div>';
						}
					} else {

					}
					for ($n = 0; $n <= 10; $n++) {
						print '<!--                                                                                                    -->';
					}
					flush();
				}

				/*
				  "Наименование": "Педикюр",
				  "Сокращенное наименование": "Педикюр",
				  "GUID": "1b53f992-1e2d-11e5-867d-002590649803",
				  "Длительность": 90,
				  "Цена": 1900
				 */
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
