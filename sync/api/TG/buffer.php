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
$start = time();
$oldtime = $start;
while (1 || time() - $start < 29) {
	if ($oldtime != time()) {
		$oldtime = time();
//		print ($oldtime - $start) . ' ';
	}
	$messages = query2array(mysqlQuery("SELECT * FROM `telegramQuery` WHERE isnull(`telegramQuerySent`) AND (isnull(`telegramQueryLastAttempt`) OR `telegramQueryLastAttempt` < CURRENT_TIMESTAMP) AND `telegramQueryAttemptsQty`<3 LIMIT 3"));
	foreach ($messages as $n => $message) {
//		printr($messages[$n]['telegramQueryData']);
		mysqlQuery("UPDATE `telegramQuery` SET `telegramQueryLastAttempt` = NOW(), `telegramQueryAttemptsQty` = `telegramQueryAttemptsQty`+1 WHERE `idtelegramQuery` = '" . $messages[$n]['idtelegramQuery'] . "'");
		$headers = [];
		$curl = curl_init('https://api.telegram.org/bot' . TGKEY . '/' . $messages[$n]['telegramQueryMethod']);
		curl_setopt_array($curl, [
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POSTFIELDS => $messages[$n]['telegramQueryData'],
			CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $headers)
		]);
		$result = json_decode(curl_exec($curl), 1);
		curl_close($curl);
		if (($result['ok'] ?? false)) {
			mysqlQuery("UPDATE `telegramQuery` SET `telegramQuerySent` = NOW() WHERE `idtelegramQuery` = '" . $messages[$n]['idtelegramQuery'] . "'");
		} else {
			if (($result['description'] ?? '') === 'Forbidden: bot was blocked by the user') {
				$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersTG` = '" . mres($message['telegramQueryChatId']) . "'"));
				if ($user) {
					mysqlQuery("UPDATE `users` SET `usersTG`=null WHERE `idusers` = '" . $user['idusers'] . "'");
					sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => 'Пользователю ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ' был автоматически отключён телеграм по причине блокировки бота пользователем.']);
					$result['action'] = "Отключён нафиг";
				}
			}
			if (!in_array(($result['error_code'] ?? ''), ['429', '400'])) {
				mysqlQuery("INSERT INTO `telegramQueryErrors` SET"
						. " `telegramQueryErrorsQuery` = '" . $messages[$n]['idtelegramQuery'] . "',"
						. " `telegramQueryErrorsError` = '" . mres(json_encode($result, JSON_UNESCAPED_UNICODE + 128)) . "'");
			}
			mysqlQuery("UPDATE `telegramQuery` SET `telegramQueryAttemptsQty` = 999 WHERE `idtelegramQuery` = '" . $messages[$n]['idtelegramQuery'] . "'");
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
