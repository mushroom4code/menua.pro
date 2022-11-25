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
function exportCSV($rows = false) {
	$output = '';
	if (!empty($rows)) {
		foreach ($rows as $row) {
			if (!is_array($row)) {
				$row = [$row];
			}
			$output .= implode(';', $row) . "\r\n";
		}
	}
	return $output;
}

$GUID = '988bcd21-65f9-4b6b-b52a-add0d65df876';
$cid = '13744';

//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => '🔷 Доступ разрешен']);
$NAME = [
	'1' => ['1', 'ООО «Инфинити» Московские ворота', ''],
	'2' => ['2', 'ООО «Инфинити» Чкаловская', '']
];
$DATABASE = ['1' => 'warehouse', '2' => 'vita'];

$users = [['Врачи:']];
foreach ($NAME as $n => $clinic) {
	$schedule = [];
//	$schedule[$n] = $NAME[$n];
	$schedule['clinic'] = [];
	$personnelSQL = '';

	$allusers[$n] = (query2array(mysqlQuery("SELECT (`idusers` + " . (100000 * $n) . ") as `idusers`, CONCAT(`usersLastName`,' ',`usersFirstName`,' ',`usersMiddleName`) as `name`,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `" . $DATABASE[$n] . "`.`usersPositions` LEFT JOIN `warehouse`.`positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions`, '" . $n . "' AS `clinic` FROM `" . $DATABASE[$n] . "`.`users` WHERE `idusers`  IN (SELECT `usersPositionsUser` FROM `" . $DATABASE[$n] . "`.`usersPositions` WHERE `usersPositionsPosition` IN ("
							. " 1,3,6,7,8,9,10,11,12,13,27,31,34,36,39,42,43,48,50,51,54,55,56,57,58,59,61,62,63,65,74,75"
							. ")) AND isnull(`usersDeleted`)")));
	$users = array_merge($users, $allusers[$n]);
//	printr($allusers[$n]);
}


$csv = array_merge(['Филиал:'], $NAME, [''], $users);

$csvstr = exportCSV($csv);

$length = strlen($csvstr ?? '');

if ($_GET['test'] ?? false) {
	print $csvstr ?? '';
	die();
}
//

$URL = 'https://api.napopravku.ru/loop/v3/refresh_info';
$headers = [
	"Host: api.napopravku.ru",
	"Accept: */*",
	"Content-Type: text/plain; charset=UTF-8",
	"Content-Length: " . $length,
	"GUID: " . $GUID,
	"CID: " . $cid 
];

$curl = curl_init($URL);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // return the results instead of outputting it
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $csvstr);
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