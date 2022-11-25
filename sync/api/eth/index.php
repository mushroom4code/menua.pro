<?php

$start = microtime(1);
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
header("Content-type: application/json; charset=utf8");
//sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "\n" . json_encode($_GET, 288 + 128)]);

if (($_GET['barcode'] ?? false)) {

	if (($user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersBarcode`='" . mres($_GET['barcode']) . "'"))) && mysqlQuery("INSERT INTO `fingerLog` SET `fingerLogUser` = '" . $user['idusers'] . "'")) {


		if (mysqli_num_rows(mysqlQuery("SELECT * FROM `fingerLog` WHERE `fingerLogUser`='" . $user['idusers'] . "' AND `fingerLogTime`>'" . date("Y-m-d 00:00:00") . "' AND `fingerLogTime`<='" . date("Y-m-d 23:59:59") . "'")) == 0) {
//			ICQ_messagesSend_SYNC(ICQADMINGROUP, date("H:i:s") . ') Приход: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . '.');

			if ($user['usersTG']) {
				$stickers = query2array(mysqlQuery("SELECT * FROM `TGstickers` WHERE `TGstickersAddedBy`=176"));
				$rand = rand(0, count($stickers) - 1);
				sendTelegram('sendSticker', ['chat_id' => $user['usersTG'], 'sticker' => $stickers[$rand]['TGstickersFile']]);
			}
			sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => '🚪 Приход ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ($user['usersTG'] ? '+' : '-')]);
		} else {
			if ($user['usersTG']) {
				sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => '🚪']);
			}
			sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => ' Я: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ': ' . '🚪']);
		}




		print json_encode(['success' => 'true'], 288);
	} else {
		print json_encode(['success' => 'false'], 288);
	}
}

if (($_GET['faceid'] ?? false)) {

//	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'Данные ' . $_GET['faceid']]);
	$_GET['faceid'] = hexdec($_GET['faceid']);
//	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'После обрезки ' . $_GET['faceid']]);
	$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersFinger`='" . mres($_GET['faceid']) . "' AND isnull(`usersDeleted`)"));
	if ($user) {
		if (mysqli_num_rows(mysqlQuery("SELECT * FROM `fingerLog` WHERE `fingerLogUser`='" . $user['idusers'] . "' AND `fingerLogTime`>'" . date("Y-m-d 00:00:00") . "' AND `fingerLogTime`<='" . date("Y-m-d 23:59:59") . "'")) == 0) {
//			ICQ_messagesSend_SYNC(ICQADMINGROUP, date("H:i:s") . ') Приход: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . '.');

			if ($user['usersTG']) {
				$stickers = query2array(mysqlQuery("SELECT * FROM `TGstickers` WHERE `TGstickersAddedBy`=176"));
				$rand = rand(0, count($stickers) - 1);
				sendTelegram('sendSticker', ['chat_id' => $user['usersTG'], 'sticker' => $stickers[$rand]['TGstickersFile']]);
			}
			sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => '🚪 Приход ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ($user['usersTG'] ? '+' : '-')]);
		} else {
			if ($user['usersTG']) {
				sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => '🚪']);
			}
			sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => ' Я: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ': ' . '🚪']);
		}



		mysqlQuery("INSERT INTO `fingerLog` SET `fingerLogData`='" . mres($_GET['faceid']) . "', `fingerLogUser` = " . ($user['idusers'] ?? 'null') . "");
	} else {
		sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => '🚪 Неопознанный пользователь (' . $_GET['faceid'] . ')']);
	}
	print json_encode(['success' => ($user['idusers'] ?? false) ? 'true' : 'false'], 288);
}

if (($_GET['device'] ?? false) && ($_GET['ping'] ?? false)) {
//	$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersBarcode`='" . mres($_GET['barcode']) . "'"));
//	if ($user && mysqlQuery("INSERT INTO `fingerLog` SET `fingerLogUser` = '" . $user['idusers'] . "'")) {
	mysqlQuery("INSERT INTO `DEBUG_pings` SET "
			. "`DEBUG_ping_id`='" . ($_GET['device'] ?? 'noid') . "',"
			. "`DEBUG_pings_count`=" . ($_GET['pingcount'] ?? 'null') . ""
			. "");
	print json_encode(['success' => 'true'], 288);
//	} else {
//		print json_encode(['success' => 'false'], 288);
//	}
}
$PGT = (microtime(1) - $start);
if (1) {
	$slow = $PGT > 1;
	if ($slow) {
		sendTelegram('sendMessage', ['chat_id' => -522070992, 'text' => ($slow ? '‼' : '') . date("H:i:s") . '️ PGT' . __FILE__ . ': ' . $PGT]);
	}
}


