<?php

if (isset($argv)) {
	print_r($argv);
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
ICQ_messagesSend_SYNC('sashnone', "SOCKED STARTED " . date("Y-m-d H:i:s"));
sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "SOCKED STARTED " . date("Y-m-d H:i:s")]);
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
	$messagedata = json_decode(decode($data)['payload'], 1) ?? decode($data)['payload'];
	/*
	  ОБРАБОТКА СООБЩЕНИЙ
	 */

//		ICQ_messagesSend_SYNC('sashnone', $messagedata);
	if ($messagedata == 'open') {
		ICQ_messagesSend_SYNC('sashnone', json_encode($messagedata, 288));
		socketSendALL('open');
	}



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
						$stickers = query2array(mysqlQuery("SELECT * FROM `TGstickers` WHERE `TGstickersAddedBy`=176"));
						$rand = rand(0, count($stickers) - 1);
						sendTelegram('sendSticker', ['chat_id' => $dooruser['usersTG'], 'sticker' => $stickers[$rand]['TGstickersFile']]);
					}
					if ($dooruser['usersICQ']) {
						$msgtext = rt([
							$dooruser['usersFirstName'] . ', ' . 'здравствуйте!',
							$dooruser['usersFirstName'] . ', ' . 'приветствую!',
							$dooruser['usersFirstName'] . ', ' . 'добро пожаловать!',
							$dooruser['usersFirstName'] . ', ' . 'я скучала!',
							$dooruser['usersFirstName'] . ', ' . 'привет!',
							'https://files.icq.net/get/28g8gQ3iQWRGAfbznh26w05aa21ba81ae',
							'https://files.icq.net/get/27qakFNrO03wPK4v9ckxsy5a84384d1ab',
							'https://files.icq.net/get/28g8g000qt1MrXPspJputh5e4526401ad',
							'https://files.icq.net/get/28g8g000qt1MrXPspJputh5e4526401ad',
							'https://files.icq.net/get/2gwgwOzm1ZZ92qhJAj0NyN5b6046ff1ac',
							'https://files.icq.net/get/28g8gaq3g0plwhvawwvt1q5c7fa6e31ad',
							'https://files.icq.net/get/28g8gzqygdcjoewzqrpkum5dab650b1ag',
							'https://files.icq.net/get/28g8gadupdzrykqerdvtg25c7e7d891ad',
							'https://files.icq.net/get/28g8g000zlr5e1gn6ppwie5e8b57c11bg',
							'https://files.icq.net/get/28g8gkg8gttuxreqheidks5ac927ec1be',
							'https://files.icq.net/get/28g8g000vwsesp6e5dmt1i5e8be2591ba',
							'https://files.icq.net/get/25aakiugjafqonvzwxxpvb5a8308291ab',
							'https://files.icq.net/get/2bfakwihy6ezlfhoc3v5ej5a8308631ab',
							'https://files.icq.net/get/2ec9mafvbpcajhe2yssdbr5a8439341ab',
							'https://files.icq.net/get/2ak9gzsod3l4ahamidqvix5a7029b21ab',
							'https://files.icq.net/get/27maku1atgauzsvsdhlym05a7028d81ab',
							'https://files.icq.net/get/28aakuba2hoi4nopkgy36a5a7028db1ab',
							'https://files.icq.net/get/28g8gztzxhmw0gqja4xsc25c98b3a41ad',
							'https://files.icq.net/get/28g8gp2coaptntwlnljqyh5c98b3a41ad',
							'https://files.icq.net/get/2gwgwpeurm8gx8lftxvu4d5c1a089a1ab',
							'https://files.icq.net/get/2hqj3hwcebnaz87pizxztb5bb49a0a1be',
							'https://files.icq.net/get/28g8g000eW3xTVrvXYdMrJ5e8892d51ab',
							'https://files.icq.net/get/28g8g000xayqxwJyB7ZBiO5e8892d51ab',
							$dooruser['usersFirstName'] . ', ' . 'рада видеть!']);

						ICQ_messagesSend_SYNC($dooruser['usersICQ'], $msgtext);
						ICQ_messagesSend_SYNC('sashnone', date("i:s") . ' Я: ' . $dooruser['usersLastName'] . ' ' . $dooruser['usersFirstName'] . ': ' . $msgtext);
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
