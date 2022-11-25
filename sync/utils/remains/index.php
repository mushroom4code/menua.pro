<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
$start = microtime(1);
die();
$fileContents = file_get_contents("2020.json");

print microtime(1) - $start;

$json = json_decode($fileContents, true);
print '<br>';
print microtime(1) - $start;
print '<br>';
//printr($json);

$remains = $json['remains'];
$DBservices = query2array(mysqlQuery("SELECT * FROM `services` LEFT JOIN `servicesGUIDs` ON (`servicesGUIDsService` = `idservices`)"), 'servicesGUIDsGUID');
$DBclients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'GUID');

//printr($DBservices);
$undefined = [];

$exist = 0;
?>

<div class="box neutral">
	<div class="box-body">
		<?
//		printr($DBservices);

		$importServices = [];
		$importBanks = [];

		foreach ($remains as &$remain2) {
			$remain2['ДатаПродажи'] = date("Y-m-d", strtotime($remain2['ДатаПродажи']));
			$importServices[$remain2['GUIDПроцедуры']] = $remain2['Процедура'];
			if ($remain2['Регистратор']['GUIDБанк'] == 'd582e3c1-446f-11e8-9136-94de80660867') {
				$remain2['Регистратор']['GUIDБанк'] = 'eee97b2b-c01c-11e9-ab07-001b2179fd44';
			}
			$importBanks[$remain2['Регистратор']['GUIDБанк']] = $remain2['Регистратор']['Банк'];
		}
		printr($importBanks);

		usort($remains, function($a, $b) {
			if ($a['GUIDКонтрагента'] <=> $b['GUIDКонтрагента']) {
				return $a['GUIDКонтрагента'] <=> $b['GUIDКонтрагента'];
			} elseif ($a['GUIDСертификата'] <=> $b['GUIDСертификата']) {
				return $a['GUIDСертификата'] <=> $b['GUIDСертификата'];
			} else {
				return $a['ДатаПродажи'] <=> $b['ДатаПродажи'];
			}
		});
		printr($remains[0]);


		$clients = [];


		$clientLast = null;
		foreach ($remains as $remain) {
			if ($clientLast != $remain['GUIDКонтрагента']) {//new client
				$clientLast = $remain['GUIDКонтрагента'];
				$saleDate = null;
				$contractGuid = null;
				$certificatesN = -1;
				$clients[$remain['GUIDКонтрагента']]['name'] = $remain['Контрагент'];
				if ($DBclients[$remain['GUIDКонтрагента']] ?? 0) {
//					$clients[$remain['GUIDКонтрагента']]['exist'] = true;
//					$DBclients[$remain['GUIDКонтрагента']]['clientsOldSince'];

					$clients[$remain['GUIDКонтрагента']]['old'] = $DBclients[$remain['GUIDКонтрагента']]['clientsOldSince'];

					if ($clients[$remain['GUIDКонтрагента']]['old'] === null || $clients[$remain['GUIDКонтрагента']]['old'] > $remain['ДатаПродажи']) {
						$clients[$remain['GUIDКонтрагента']]['old'] = $remain['ДатаПродажи'];
						$clients[$remain['GUIDКонтрагента']]['update'] = true;
					}


//					if (empty($clients[$remain['GUIDКонтрагента']]['old']) || ($clients[$remain['GUIDКонтрагента']]['oldNew'] ?? null) > $remain['ДатаПродажи']) {
//						$clients[$remain['GUIDКонтрагента']]['oldNew'] = $remain['ДатаПродажи'];
//						$clients[$remain['GUIDКонтрагента']]['update'] = true;
//					}
				} else {
					$name = explode(' ', trim(preg_replace('/\s+/', ' ', $remain['Контрагент'])));

					$filteredClients = array_filter($DBclients, function($element) {
						global $name;
						return
								mb_strtolower($name[0]) == trim(mb_strtolower($element['clientsLName'])) &&
								mb_strtolower($name[1]) == trim(mb_strtolower($element['clientsFName'])) &&
								mb_strtolower($name[2]) == trim(mb_strtolower($element['clientsMName']));
					});
					$filteredClients = obj2array($filteredClients);
//idclients, GUID, clientsLName, clientsFName, clientsMName, clientsBDay, clientsAKNum, clientsAddedBy, clientsAddedAt, clientsGender, clientsIsNew, clientsCallerId, clientsCallerAdmin, clientsSource, 
					if (count($filteredClients) == 1) {
						mysqlQuery("UPDATE `clients` SET `GUID` = '" . $remain['GUIDКонтрагента'] . "' WHERE `idclients` = '" . $filteredClients[0]['idclients'] . "'");
					}
//					printr([$filteredClients, $name, $remain['Контрагент'], $remain['GUIDКонтрагента']]);
				}
			}
		}



		print count($clients);
		foreach ($clients as $GUID => $client) {
			if ($client['update'] ?? 0) {
				mysqlQuery("UPDATE `clients` SET `clientsOldSince` = '" . $client['old'] . "' WHERE `GUID`='$GUID'");
				print 'U ';
			}
		}
		printr($clients);



		foreach ($importServices as $guid => $importService) {
			if (empty($DBservices[$guid])) {
				printr([$guid, $importService]);
			}
		}
		?>
		<table>
			<tr>
				<td>Клиент</td>
				<td>Дата</td>
				<td>Процедура</td>
				<td>Количество</td>
				<td>Стоимость</td>
				<td>+</td>
			</tr>
		</table>
	</div>
</div>


<?
print '<br>';
print microtime(1) - $start;
print '<br>';
//	mysqlQuery("select * from `asasas`");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
