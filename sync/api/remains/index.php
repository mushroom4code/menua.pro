<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/includes/setupLight.php';

//ICQ_messagesSend_SYNC('sashnone', "REMAINS API ACCESS\n\r GET: " . (json_encode($_GET ?? [])) . "\n\rSERVER: " . (json_encode($_SERVER ?? [])));

if ($_SERVER['REMOTE_ADDR'] !== '5.101.156.133') {
	die('403');
}

// GET: {"h":"A3kV"}
if (!($client = mfa(mysqlQuery("SELECT idclients,clientsFName,clientsMName FROM `clients` WHERE BINARY `clientsHash` = '" . mysqli_real_escape_string($link, $_GET['h']) . "'")))) {
	print json_encode(['error' => '404', 'success' => false]);
	die();
}
mysqlQuery("INSERT INTO `clientsApiUse` SET `clientsApiUseClient` = '" . $client['idclients'] . "'");
$totalRemainsFlat = getRemainsByClient($client['idclients']);
$totalRemainsOUT = [];
$reserved = query2array(mysqlQuery(""
				. "SELECT SUM(`servicesAppliedQty`) AS `qty`,"
				. "`servicesAppliedService` FROM `servicesApplied` WHERE"
				. " `servicesAppliedClient`='" . mysqli_real_escape_string($link, $client['idclients']) . "'"
				. " AND `servicesAppliedDate`>=CURDATE()"
				. " AND isnull(`servicesAppliedDeleted`)"
				. " AND isnull(`servicesAppliedFineshed`)"
				. " AND NOT isnull(`servicesAppliedContract`)"
				. " GROUP BY `servicesAppliedService`;"));

foreach ($totalRemainsFlat as $remain) {
	$reservedService = obj2array(array_filter($reserved, function($el) use ($remain) {
//																printr($remain);
				return $el['servicesAppliedService'] == $remain['f_salesContentService'];
			}));
//															printr($reservedService);
	if (($remain['f_salesContentQty'] ?? 0) > 0 || count($reservedService)) {
		$totalRemainsOUT[$remain['f_salesContentService']]['each'][] = $remain;
		$totalRemainsOUT[$remain['f_salesContentService']]['reserved'] = $reservedService[0]['qty'] ?? null;
		$totalRemainsOUT[$remain['f_salesContentService']]['name'] = $remain['servicesName'];
		$totalRemainsOUT[$remain['f_salesContentService']]['qty'] = ($totalRemainsOUT[$remain['f_salesContentService']]['qty'] ?? 0) + $remain['f_salesContentQty'];
	}
}


$_GET['success'] = true;
$_GET['time'] = time();
$_GET['date'] = date("Y-m-d H:i:s");

$_GET['data']['remains'] = $totalRemainsOUT;

$_GET['data']['client'] = $client;
print json_encode($_GET, 288);

