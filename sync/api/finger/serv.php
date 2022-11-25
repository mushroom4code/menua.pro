<?php

if (isset($argv)) {
//	print_r($argv);
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include ($_ROOTPATH . "/sync/includes/setupLight.php");
include ("staticfunctions.php");
$socket = stream_socket_server("tcp://127.0.0.1:" . SOCKETPORT, $errno, $errstr);

if (!$socket) {
	die("$errstr ($errno)\n");
}

//$redis = new Redis();
//$redis->connect("127.0.0.1", 6379);
$messageCounter = 0;
$connects = array();
//vkSend("SOCKED STARTED " . date("Y-m-d H:i:s"));
sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "FINGER SOCKED STARTED " . date("Y-m-d H:i:s")]);
$APM = 0;
$APM_time = date("s");
$lastPing = [];

while (true) {
//формируем массив прослушиваемых сокетов:
	$read = $connects;
	$read [] = $socket;
	$write = $except = null;

	if (!stream_select($read, $write, $except, null)) {//ожидаем сокеты доступные для чтения (без таймаута)
		break;
	}

	if (in_array($socket, $read)) {//есть новое соединение
		//принимаем новое соединение и производим рукопожатие:
		if (($connect = stream_socket_accept($socket, -1)) && $info = handshake($connect)) {
			$connects[] = $connect; //добавляем его в список необходимых для обработки
			echo count($connects) . "\r\n";
			onOpen($connect, $info); //вызываем пользовательский сценарий
		}
		unset($read[array_search($socket, $read)]);
	}

	foreach ($read as $connect) {//обрабатываем все соединения
		$data = fread($connect, 100000);

		if (!$data) { //соединение было закрыто
			fclose($connect);
			unset($connects[array_search($connect, $connects)]);
			onClose($connect); //вызываем пользовательский сценарий
			continue;
		}

		onMessage($connect, $data, $info); //вызываем пользовательский сценарий
	}

	// INTERCTIVE
//	$fromRedis = $redis->lPop('key1');
//	if ($fromRedis !== false) {
////echo "I've got $fromRedis fromRedis\n";
//
//		$fromRedisArray = json_decode($fromRedis, true);
//		$messageCounter++;
//		$fromRedisArray['messageId'] = $messageCounter;
//		socketSendALL(json_encode($fromRedisArray));
////		json_encode($fromRedisArray);
//	}
}

fclose($server);

//пользовательские сценарии:





function onOpen($connect, $info) {
//	echo "open\n";
//	var_dump($info);
//	fwrite($connect, encode('Соединение установлено!'));
}

function onClose($connect) {
//	echo "close\n";
}

function onMessage($connect, $data, $info) {
	global $lastPing;
	$decoded = decode($data);
	if (!$decoded) {
		sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => 'Не удалось расшифровать сообщение ' . $data]);
		return;
	}
	$messagedata = json_decode($decoded['payload'], 1) ?? $decoded['payload'];
	/*
	  ОБРАБОТКА СООБЩЕНИЙ
	 */

//		ICQ_messagesSend_SYNC('sashnone', $messagedata);
	if ($messagedata == 'open') {
//		ICQ_messagesSend_SYNC('sashnone', json_encode($messagedata, 288));
		socketSendALL('open');
	}
//	print_r($messagedata);

	if (isset($messagedata['ping'])) {// ПИНГ
		$lastPing[($messagedata['board_id'] ?? 'noid')] = time();
		mysqlQuery("INSERT INTO `DEBUG_pings` SET "
				. "`DEBUG_ping_id`='" . ($messagedata['board_id'] ?? 'noid') . "',"
				. "`DEBUG_pings_count`=" . ($messagedata['pingCount'] ?? 'null') . ""
				. "");
	}
	/*

	 */
	if (isset($messagedata['user'])) {// FACE ID
		$fingerPrint = hexdec(FSS($messagedata['user'])) - 65536;
		if ($fingerPrint < 0) {
			$fingerPrint += 65536;
		}

//		ICQ_messagesSend_SYNC('sashnone', $fingerPrint);

		if ($fingerPrint ?? false) {
			$dooruser = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersFinger` = '" . $fingerPrint . "' AND isnull(`usersDeleted`)"));
			if ($dooruser['idusers'] ?? false) {
				socketSendALL('open');
//				ICQ_messagesSend_SYNC('sashnone', 'open');
				if (mysqli_num_rows(mysqlQuery("SELECT * FROM `fingerLog` WHERE `fingerLogUser`='" . $dooruser['idusers'] . "' AND `fingerLogTime`>'" . date("Y-m-d 00:00:00") . "' AND `fingerLogTime`<='" . date("Y-m-d 23:59:59") . "'")) == 0) {
					ICQ_messagesSend_SYNC(ICQADMINGROUP, date("H:i:s") . ') Приход: ' . $dooruser['usersLastName'] . ' ' . $dooruser['usersFirstName'] . '.');
					sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'Приход ' . $dooruser['usersLastName'] . ' ' . $dooruser['usersFirstName']]);
					if ($dooruser['usersTG']) {
						$stickers = query2array(mysqlQuery("SELECT * FROM `TGstickers` WHERE `TGstickersAddedBy` IN(176," . $dooruser['idusers'] . ")"));
						$rand = rand(0, count($stickers) - 1);
						sendTelegram('sendSticker', ['chat_id' => $dooruser['usersTG'], 'sticker' => $stickers[$rand]['TGstickersFile']]);
					}
					
				} else {
					if ($dooruser['usersTG']) {
						sendTelegram('sendMessage', ['chat_id' => $dooruser['usersTG'], 'text' => '🚪']);
						sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => ' Я: ' . $dooruser['usersLastName'] . ' ' . $dooruser['usersFirstName'] . ': ' . '🚪']);
					}
				}
				mysqlQuery("INSERT INTO `fingerLog` SET `fingerLogData`='" . $fingerPrint . "', `fingerLogUser` = " . ($dooruser['idusers'] ?? 'null') . "");
			} else {
				sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => date("H:i:s") . ') Неизвестный ключ: ' . $fingerPrint . '.']);
			}
		}
	}
	/*

	 */
}
