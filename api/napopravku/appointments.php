<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}

include $_ROOTPATH . '/sync/includes/setupLight.php';

$GUID = '988bcd21-65f9-4b6b-b52a-add0d65df876';
$cid = '13744';

$URL = 'https://api.napopravku.ru/loop/v4/history_get';
//$URL = 'https://api.napopravku.ru/loop/v4/history_gen_debug';
$DATABASE = ['1' => 'warehouse', '2' => 'vita'];
$subdomens = ['1' => '', '2' => 'vita.'];

$query = http_build_query(['onlyNew' => '0', 'since' => '0'], '', '&');
$headers = [
//	"Host: api.napopravku.ru",
//	"Accept: */*",
	"Content-Type: application/x-www-form-urlencoded",
	"GUID: " . $GUID,
	"CID: " . $cid
];

$curl = curl_init($URL);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // return the results instead of outputting it
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_POSTFIELDS, $query);

function getPersonnel($idusers) {
	$result = (mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers`='" . mres($idusers) . "'"))['idusers'] ?? null);
	return $result ?? 176;
}

// Verify SSL
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
$exec = curl_exec($curl);
//printr($exec);
//$exec = "ok\nsince\ttimestamp\tclinicId\tdoctorId\tappDateTime\tpatientSurname\tpatientName\tpatientFathername\tpatientPhone\tcomment\n163794918967198\t1637949189\t9ce551dd-56ca-45a4-b4d7-3d9b4389dbf1\t3c9e15ce-6a17-4c38-97dc-02f6213619e2\t2021-11-27 17:15\tФамилия-103\tИмя-106\tОтчество-70\t+75761659712\t-\n163794919828591\t1637949198\t25ec5b48-ee2f-4c00-a9fb-c8832e2557de\t402316\t2021-12-02 11:40\tФамилия-94\tИмя-24\tОтчество-25\t+75072461683\tкакой-то произвольный комментарий\n163794920157068\t1637949201\tb7a4ebd0-7669-4980-9fb4-865baeecdeb3\tabcaebeb-3a13-41aa-b35e-54123c31d596\t2021-12-03 18:40\tФамилия-106\tИмя-105\tОтчество-52\t+71696619236\t-\n";
$result = array_map(function ($el) {
	return explode("\t", $el);
}, explode("\n", $exec));
//0 since
//1 timestamp
//2 clinicId
//3 doctorId
//4 appDateTime
//5 patientSurname
//6 patientName
//7 patientFathername
//8 patientPhone
//9 comment
$appointments = [];
if (count($result ?? []) > 2) {
	for ($n = 2; $n < count($result) && ($result[$n][1] ?? false); $n++) {
//		print '<pre>';
//		var_dump($result[$n]);
//		print '</pre>';
		$servicesAppliedTimestamp = strtotime($result[$n][4]);
		$database = ($DATABASE[$result[$n][2]] ?? 'warehouse');
		$subdomen = ($subdomens[$result[$n][2]] ?? '');

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if (1) {
			$phoneNumber = preg_replace("/[^0-9]/", "", $result[$n][8] ?? '');
			if (strlen($phoneNumber) == 11) {
				$phoneNumber[0] = '8';
			} elseif (strlen($phoneNumber) == 10) {
				$phoneNumber = '8' . $phoneNumber;
			}

			$clients = query2array(mysqlQuery("SELECT *"
							. " FROM `$database`.`clients`"
							. " LEFT JOIN `$database`.`clientsPhones` ON (`clientsPhonesClient` = `idclients`)"
							. " WHERE `clientsPhonesPhone`='" . mres($phoneNumber) . "'"
							. " AND isnull(`clientsPhonesDeleted`)"));

			if (count($clients) > 1) {
//try to filter by name
				$clients = array_values(array_filter($clients, function ($client) use ($result, $n) {
							return $client['clientsLName'] == ($result[$n][5] ?? '') && $client['clientsFName'] == trim($result[$n][6] ?? '');
						}));
				if (count($clients) > 1) {
					telegramSendByRights([159], "🚨🚨🚨При записи через НаПоправку найдено больше 1го клиента с номером телефона\n" . mres($phoneNumber) . "\nКлиент сайта: " . ($result[$n][5] ?? '') . ' ' . trim($result[$n][6] ?? '') . "\nСрочно принять меры!");
					print json_encode(["status_code" => 416, "detail" => "Slot doesn't exist"]);
					die();
				} elseif (count($clients) == 1) {
					$client = $clients[0];
				} else {
					telegramSendByRights([159], "🚨🚨🚨При записи через НаПоправку найдено больше 1го клиента с номером телефона\n" . mres($phoneNumber) . ", но ни одного с данными которые ввёл клиент (" . trim($result[$n][5] ?? '') . ' ' . trim($result[$n][6] ?? '') . ")\nСрочно принять меры!");
					print json_encode(["status_code" => 416, "detail" => "Slot doesn't exist"]);
					die();
				}
			} elseif (count($clients) == 1) {

				if ($clients[0]['clientsLName'] == ($result[$n][5] ?? '') && $clients[0]['clientsFName'] == trim($result[$n][6] ?? '')) {
					$client = $clients[0];
				} else {
					telegramSendByRights([159], "🚨🚨🚨При записи через НаПоправку возникла ошибка! Записывается " . trim($result[$n][5] ?? '') . ' ' . trim($result[$n][6] ?? '') . ' ' . trim($result[$n][7] ?? '') . " c телефонным номером $phoneNumber, а в базе данных на с этим же номером записан клиент " . $clients[0]['clientsLName'] . ' ' . $clients[0]['clientsFName'] . "\nhttps://" . $subdomen . "menua.pro/pages/offlinecall/schedule.php?client=" . $clients[0]['idclients']);
					print json_encode(["status_code" => 416, "detail" => "Slot doesn't exist"]);
					die();
				}
			} else {
//ADD NEW CLIENT
//				if ($_JSON['appointment_source'] == 'Prodoctorov') {
//					$idclientsSources = 19;
//				} elseif ($_JSON['appointment_source'] == 'infiniti-clinic.ru') {
//					$idclientsSources = 20;
//				} else {
//					$idclientsSources = null;
//				}

				$idclientsSources = 21;

				mysqlQuery("INSERT INTO `$database`.`clients` SET "
						. " `clientsLName` = '" . mres(trim($result[$n][5] ?? '')) . "', "
						. " `clientsFName` = '" . mres(trim($result[$n][6] ?? '')) . "', "
						. " `clientsMName` = '" . mres(trim($result[$n][7] ?? '')) . "', "
						. " `clientsSource`=" . sqlVON($idclientsSources) . "");
				$idclient = mysqli_insert_id($link);
				if (!$idclient) {
					telegramSendByRights([158], "🚨 ошибка добавления клиента через НаПоправку\n" . trim($result[$n][5] ?? '') . ' ' . trim($result[$n][6] ?? ''));
					die();
				}
				$client = mfa(mysqlQuery("SELECT * FROM `$database`.`clients` WHERE `idclients` = '" . $idclient . "'"));
				mysqlQuery("INSERT INTO `$database`.`clientsPhones` SET `clientsPhonesClient` = '" . $idclient . "', `clientsPhonesPhone` = '" . mres($phoneNumber) . "'");
				telegramSendByRights([158], "✅ Добавлен новый клиент через НаПоправку\n" . trim($result[$n][5] ?? '') . ' ' . trim($result[$n][6] ?? '') . "\nhttps://" . $subdomen . "menua.pro/pages/offlinecall/schedule.php?client=" . $idclient);
			}
		}
//		print "<h2>Клиент " . $client['clientsLName'] . "</h2>"
//				. '<div style="color: red; border: 1px solid red;">';
//		printr($client);
//		print "</div>";
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////







		if (!($client['idclients'] ?? false)) {
			die('error client not found');
		}




		$appointment = [
			'servicesAppliedService' => 361,
			'servicesAppliedQty' => 1,
			'servicesAppliedClient' => $client['idclients'],
			'servicesAppliedPersonal' => getPersonnel($result[$n][3]),
			'servicesAppliedDate' => date("Y-m-d", $servicesAppliedTimestamp),
			'servicesAppliedAt' => date("Y-m-d", $result[$n][1]),
			'servicesAppliedTimeBegin' => date("Y-m-d H:i:s", $servicesAppliedTimestamp),
			'servicesAppliedTimeEnd' => date("Y-m-d H:i:s", $servicesAppliedTimestamp + 45 * 60),
			'servicesAppliedPrice' => '0'
		];
		mysqlQuery("INSERT INTO `servicesApplied` SET"
				. "`servicesAppliedService`='" . $appointment['servicesAppliedService'] . "',"
				. "`servicesAppliedQty`='" . $appointment['servicesAppliedQty'] . "',"
				. "`servicesAppliedClient`='" . $appointment['servicesAppliedClient'] . "',"
				. "`servicesAppliedPersonal`='" . $appointment['servicesAppliedPersonal'] . "',"
				. "`servicesAppliedDate`='" . $appointment['servicesAppliedDate'] . "',"
				. "`servicesAppliedAt`='" . $appointment['servicesAppliedAt'] . "',"
				. "`servicesAppliedTimeBegin`='" . $appointment['servicesAppliedTimeBegin'] . "',"
				. "`servicesAppliedTimeEnd`='" . $appointment['servicesAppliedTimeEnd'] . "',"
				. "`servicesAppliedPrice`='" . $appointment['servicesAppliedPrice'] . "'"
				. "");
		$idappointments = mysqli_insert_id($link);
		if ($idappointments) {
			$comment = '';
			if (!empty(trim($result[$n][9] ?? '')) && trim($result[$n][9] ?? '') != '-') {
				$comment = trim($result[$n][9] ?? '');
				mysqlQuery("INSERT INTO `servicesAppliedComments` SET "
						. "`servicesAppliedCommentsSA` = " . sqlVON($idappointments ?? null) . ", "
						. "`servicesAppliedCommentText` = " . sqlVON($result[$n][9] ?? null) . " "
				);
			}
			telegramSendByRights([158], "✅ Новая запись через НаПоправку\n" . $client['clientsLName'] . ' ' . $client['clientsFName'] . "\nhttps://" . SUBDOMEN . "menua.pro/pages/offlinecall/schedule.php?client=" . $client['idclients'] . '&date=' . date("Y-m-d", strtotime($appointment['servicesAppliedTimeBegin'])) . ' ' . ($comment ?? ''));
		}
	}
}