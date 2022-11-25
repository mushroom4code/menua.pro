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
//header("Content-Type:text/xml");
$GUID = '988bcd21-65f9-4b6b-b52a-add0d65df876';
$cid = '13744';

$xml = new SimpleXMLElement('<days guid="' . $GUID . '" cid="' . $cid . '"></days>');

//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔷 Доступ разрешен']);
$NAME = ['1' => 'ООО «Инфинити» Московские ворота', '2' => 'ООО «Инфинити» Чкаловская'];
$NAMEshort = ['1' => 'МВ', '2' => 'ЧК'];
$DATABASE = ['1' => 'warehouse', '2' => 'vita'];
$clinics = [];
$dd = 0;
while (($curdate = date("Y-m-d", strtotime('+ ' . $dd . ' days'))) < date("Y-m-d", strtotime('+32 days'))) {
	$alldates[] = $curdate;
	$dd++;
}

for ($n = 1; $n <= 2; $n++) {
	$schedule = [];
//	$schedule[$n] = $NAME[$n];
	$schedule['clinic'] = [];
	$personnelSQL = '';

	$allusers[$n] = array_column(query2array(mysqlQuery("SELECT (`idusers` + " . (100000 * $n) . ") as `idusers` FROM `" . $DATABASE[$n] . "`.`users` WHERE `idusers`  IN (SELECT `usersPositionsUser` FROM `" . $DATABASE[$n] . "`.`usersPositions` WHERE `usersPositionsPosition` IN ("
							. " 1,3,6,7,8,9,10,11,12,13,27,31,34,36,39,42,43,48,50,51,54,55,56,57,58,59,61,62,63,65"
							. ")) AND isnull(`usersDeleted`)")), 'idusers');

	$usersSchedule = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `" . $DATABASE[$n] . "`.`usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` "
					. " FROM `" . $DATABASE[$n] . "`.`users`"
					. " LEFT JOIN `" . $DATABASE[$n] . "`.`usersSchedule`  ON (`idusers` = `usersScheduleUser`)"
					. " WHERE `usersScheduleDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 31 DAY)"
					. $personnelSQL
					. " AND `usersScheduleUser` IN (SELECT `usersPositionsUser` FROM `" . $DATABASE[$n] . "`.`usersPositions` WHERE `usersPositionsPosition` IN ("
					. " 1,3,6,7,8,9,10,11,12,13,27,31,34,36,39,42,43,48,50,51,54,55,56,57,58,59,61,62,63,65"
					. "))"
					. " AND `usersScheduleHalfs` IN ('11','10','01')"
					. " ORDER BY `usersScheduleDate`,`usersScheduleUser`"));
	$servicesApplied = query2array(mysqlQuery("SELECT *"
					. " FROM `" . $DATABASE[$n] . "`.`servicesApplied`"
					. " WHERE `servicesAppliedDate` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 31 DAY)"
					. " AND NOT isnull(`servicesAppliedPersonal`)"
					. " AND isnull(`servicesAppliedDeleted`)"));

//printr($usersSchedule);
	foreach ($usersSchedule as $day) {

//		$schedule['clinic'][$n][$day['usersScheduleUser']]['efio'] = $day['usersLastName'] . ' ' . $day['usersFirstName'] . ' ' . $day['usersMiddleName'];
//		$schedule['clinic'][$n][$day['usersScheduleUser']]['espec'] = $day['positions'];
		$usersScheduleUserservicesApplied = array_filter($servicesApplied, function ($serviceApplied) use ($day) {
			return
			$serviceApplied['servicesAppliedDate'] == $day['usersScheduleDate'] &&
			$serviceApplied['servicesAppliedPersonal'] == $day['usersScheduleUser']
			;
		});
		for ($time = mystrtotime($day['usersScheduleDate'] . " 10:00:00"); $time < mystrtotime($day['usersScheduleDate'] . " 20:00:00"); $time += 30 * 60) {
//print $time;
			$available = !count(array_filter($usersScheduleUserservicesApplied, function ($serviceApplied) use ($time) {
								return ($time >= strtotime($serviceApplied['servicesAppliedTimeBegin']) && $time < strtotime($serviceApplied['servicesAppliedTimeEnd']));
							})) && $time >= mystrtotime($day['usersScheduleFrom']) && $time < mystrtotime($day['usersScheduleTo']);

			if (!($schedule['clinic'][$day['usersScheduleDate']][$day['usersScheduleUser']]['slots'] ?? false)) {
				$schedule['clinic'][$day['usersScheduleDate']][$day['usersScheduleUser']]['slots'] = [];
			}
			if ($available) {
				$schedule['clinic'][$day['usersScheduleDate']][$day['usersScheduleUser']]['slots'][] = [
					"time" => date("H:i", $time) . '-' . date("H:i", $time + 30 * 60),
				];
			}
		}
		unset($schedule['clinic'][$day['usersScheduleUser']]['servicesApplied']);
	}
	$clinics[$n] = $schedule;
}

//		$dayXML = $clinicXML->addChild('day');
//		$dayXML->addAttribute('date', $day['usersScheduleDate']);



/**/

//function to_xml(SimpleXMLElement $object, array $data) {
//	$attr = "Attribute_";
//	foreach ($data as $key => $value) {
//		if (is_array($value)) {
//			$new_object = $object->addChild($key);
//			to_xml($new_object, $value);
//		} else {
//			if (strpos($key, $attr) !== false) {
//				$object->addAttribute(substr($key, strlen($attr)), $value);
//			} else {
//				$object->addChild($key, $value);
//			}
//		}
//	}
//}

/**/





foreach ($NAME as $idclinics => $clinic) {
	$clinicXML = $xml->addChild('clinic');
	$clinicXML->addAttribute('id', $idclinics);
	foreach ($alldates as $date) {
		$dayXML = $clinicXML->addChild('day');
		$dayXML->addAttribute('date', $date);
		foreach ($allusers[$idclinics] as $idusers) {
//			printr($idusers);
			$doctorXML = $dayXML->addChild('doctor');
			$doctorXML->addAttribute('id', $idusers);
//			printr($clinics[$idclinics]['clinic'][$date]);
			foreach (($clinics[$idclinics]['clinic'][$date][$idusers % 10000] ?? []) as $slots) {
				foreach ($slots as $slot) {
					$slotXML = $doctorXML->addChild('slot', $slot['time']);
				}
			}
		}
	}

//		$dayXML = $clinicXML->addChild('day');
//		$dayXML->addAttribute('date', $day['usersScheduleDate']);
}
$xmlText = $xml->asXML();
$length = strlen($xmlText);

if ($_GET['test'] ?? false) {
	Header('Content-type: text/xml');
	print $xmlText;
	die();
}
//


$URL = 'https://api.napopravku.ru/loop/v3/refresh_xml';

//Host: api.napopravku.ru
//Accept: */*
//Content-Type: text/xml; charset=utf-8
//Content-Length: 399
//$GUID = '988bcd21-65f9-4b6b-b52a-add0d65df876';
//$cid = '13744';

$headers = [
	"Host: api.napopravku.ru",
	"Accept: */*",
	"Content-Type: text/xml; charset=utf-8",
	"Content-Length: " . $length,
	"GUID: " . $GUID,
	"CID: " . $cid
];

$curl = curl_init($URL);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // return the results instead of outputting it
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlText);
// Verify SSL
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

$result = (curl_exec($curl));
print("\n" . $result . "\n");

//print $length;
//printr($clinics, 1);
//

/*
<clinic id="1">
	<day date="2017-01-14">
		<doctor id="1" />
		<doctor id="2" />
	</day>
	<day date="2017-01-15">
		<doctor id="1" />
		<doctor id="2">
			<slot>09:10-09:20</slot>
			<slot>11:00-11:40</slot>
			<slot>14:10-14:40</slot>
		</doctor>
	</day>
</clinic>
 *  */