<?php

header('Content-Encoding: none;');
error_reporting(E_ALL);
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => 'infinitimedbotLoop started']);

$start = time();
$oldtime = $start;
print "\n" . '-------------------------------------------------------------------------- ' . "\n";
while (time() - $start < 60) {
	if ($oldtime != time()) {
		$oldtime = time();
//		print ($oldtime - $start) . ' ';
	}
	$messages = query2array(mysqlQuery("SELECT * FROM `infinitimedbotQueue` WHERE isnull(`infinitimedbotQueueSent`) AND (isnull(`infinitimedbotQueueLastAttempt`) OR `infinitimedbotQueueLastAttempt` < CURRENT_TIMESTAMP) AND `infinitimedbotQueueAttemptsQty`<3 LIMIT 3"));
//	print count($messages) . ' ';
	foreach ($messages as $n => $message) {
//		printr($messages[$n]['telegramQueryData']);
		mysqlQuery("UPDATE `infinitimedbotQueue` SET `infinitimedbotQueueLastAttempt` = NOW(), `infinitimedbotQueueAttemptsQty` = `infinitimedbotQueueAttemptsQty`+1 WHERE `idinfinitimedbotQueue` = '" . $messages[$n]['idinfinitimedbotQueue'] . "'");
		$headers = [];
		$curl = curl_init('https://api.telegram.org/bot' . '5249650365:AAGOq5FX0cIrSZ3kYbd0bb3lK1qEWkXVknE' . '/' . $messages[$n]['infinitimedbotQueueMethod']);
		curl_setopt_array($curl, [
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POSTFIELDS => $messages[$n]['infinitimedbotQueueData'],
			CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $headers)
		]);
		$result = json_decode(curl_exec($curl), 1);
		curl_close($curl);
		if (($result['ok'] ?? false)) {
			mysqlQuery("UPDATE `infinitimedbotQueue` SET `infinitimedbotQueueSent` = NOW() WHERE `idinfinitimedbotQueue` = '" . $messages[$n]['idinfinitimedbotQueue'] . "'");
		} else {
			if (($result['description'] ?? '') === 'Forbidden: bot was blocked by the user') {
				$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `clientsTG` = '" . mres($message['telegramQueryChatId']) . "'"));
				if ($client) {
//					mysqlQuery("UPDATE `users` SET `usersTG`=null WHERE `idusers` = '" . $user['idusers'] . "'");
					sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => 'Клиент ' . '' . $user['idclients'] . ']' . $user['clientsLName'] . ' ' . $user['clientsFName'] . ' ' . $user['clientsMName'] . ' заблокировал бота.']);
					$result['action'] = "Отключён нафиг";
				}
			}
			if (!in_array(($result['error_code'] ?? ''), ['429', '400'])) {
				mysqlQuery("INSERT INTO `infinitimedbotErrors` SET"
						. " `infinitimedbotErrorsQuery` = '" . $messages[$n]['idinfinitimedbotQueue'] . "',"
						. " `infinitimedbotErrorsError` = '" . mres(json_encode($result, JSON_UNESCAPED_UNICODE + 128)) . "'");
			}
			mysqlQuery("UPDATE `infinitimedbotQueue` SET `infinitimedbotQueueAttemptsQty` = 999 WHERE `idinfinitimedbotQueue` = '" . $messages[$n]['idinfinitimedbotQueue'] . "'");
		}
//		printr($result, 1);
		usleep(250000);
	}
	usleep(250000);
//	print '.';
//	for ($n = 0; $n <= 30; $n++) {
//		print '<!--                                                                                                                                                                                                                        -->';
//		ob_flush();
//		flush();
//	}
}



//printr($messages, 1);
