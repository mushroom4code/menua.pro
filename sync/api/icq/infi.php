<?php

ignore_user_abort(true);
set_time_limit(0);
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
mysqli_query($link, "SET collation_connection=utf8mb4_unicode_ci");
mysqli_query($link, "SET character_set_client = utf8mb4");
mysqli_query($link, "SET character-set-server = utf8mb4");
mysqli_query($link, "SET character_set_results = utf8mb4");

function saveEventId($id) {
	global $_ROOTPATH;
	$fp = fopen($_ROOTPATH . '/sync/api/icq/eventId', 'w');
	fwrite($fp, $id);
	fclose($fp);
}

$lastEventID = (file($_ROOTPATH . '/sync/api/icq/eventId')[0]) ?? 0;
$params['token'] = ICQAPIKEY;
$params['lastEventId'] = $lastEventID;
$params['pollTime'] = 300;
$url = "https://api.icq.net/bot/v1/events/get" . '?' . http_build_query($params);
$running = null;
$ch_1 = curl_init($url);
curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
$mh = curl_multi_init();
curl_multi_add_handle($mh, $ch_1);
$start = time();
while (true) {
//	if (time() - $start > 40) {
//		break;
//	}
	curl_multi_exec($mh, $running);
	if (!$running) {
		curl_multi_remove_handle($mh, $ch_1);
		curl_multi_close($mh);
		$curlResult = json_decode(curl_multi_getcontent($ch_1), true);
		$running = null;
		if (isset($curlResult['events']) && count($curlResult['events'])) {
			foreach ($curlResult['events'] as $ICQ_event) {
				if (($ICQ_event['payload']['from']['userId'] ?? false) == 751326972) {
					//continue;
				}
				$MSG_TYPE = $ICQ_event['payload']['chat']['type'] ?? null;
				$MSG_FROM_ID = $ICQ_event['payload']['from']['userId'] ?? null;
				$MSG_FROM_NAME = $ICQ_event['payload']['from']['firstName'] ?? null;
				$MSG_TEXT = $ICQ_event['payload']['text'] ?? null;
				$MSG_CALLBACK = $ICQ_event['payload']['callbackData'] ?? null; //' ответ пользователя (нажатие на кнопку) данные
				$MSG_QUERYID = $ICQ_event['payload']['queryId'] ?? null;
				$ICQuser = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersICQ`='" . $MSG_FROM_ID . "'"));
				mysqlQuery("INSERT INTO `ICQevents` SET `ICQeventEvent` = '" . mres(json_encode($ICQ_event, 288)) . "'");
				if (($ICQ_event['payload']['chat']['type'] ?? '') == 'private') {
					mysqlQuery("INSERT INTO `ICQmessages` SET `ICQmessagesUser` = " . ($ICQuser['idusers'] ?? 'null') . ", `ICQmessagesMessage` = '" . mres($ICQ_event['payload']['text'] ?? 'пустая строка') . "'");
				}


				if ($MSG_TYPE === 'private') {

					if ($ICQuser) {
						if ($MSG_FROM_ID != '751363572') {
							ICQ_messagesSend_SYNC('sashnone', 'Мне тут ' . $ICQuser['usersFirstName'] . ' ' . $ICQuser['usersLastName'] . ' [' . $ICQuser['idusers'] . '] пишет, а именно: ' . $MSG_TEXT);
						}
					} else {
						$tryUser = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersBarcode`='" . mres(trim($MSG_TEXT)) . "' AND isnull(`usersDeleted`)"));
						if ($tryUser) {
							mysqlQuery("UPDATE `users` SET `usersICQ`='" . $MSG_FROM_ID . "' WHERE `idusers` = '" . $tryUser['idusers'] . "'");
							ICQ_messagesSend_SYNC('sashnone', $tryUser['usersFirstName'] . ' ' . $tryUser['usersLastName'] . ' подключил(а) аську');
							ICQ_messagesSend_SYNC($MSG_FROM_ID, $tryUser['usersFirstName'] . ', Вам удалось подключить ICQ! Теперь Вы будете получать уведомления.');
						} else {
							ICQ_messagesSend_SYNC('sashnone', 'Мне тут кто-то (' . $MSG_FROM_ID . ') пишет: ' . $MSG_TEXT);
							ICQ_messagesSend_SYNC($MSG_FROM_ID, ($MSG_FROM_NAME ? $MSG_FROM_NAME . ', ' : '') . 'Вашего номера ICQ нет в моей базе. Зайдите в программу и пришлите мне свой код со вкладки "Подключить ICQ"');
						}
					}
				}
			}
		}
//751363572
//RE RUN LISTENER
		if (isset($curlResult['events']) && count($curlResult['events'])) {
			$lastEventID = $curlResult['events'][count($curlResult['events']) - 1]['eventId'];
			saveEventId($lastEventID);
		}
		$params['lastEventId'] = $lastEventID;
		$url = "https://api.icq.net/bot/v1/events/get" . '?' . http_build_query($params);
		$ch_1 = curl_init($url);
		curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
		$mh = curl_multi_init();
		curl_multi_add_handle($mh, $ch_1);
	}
}
print "NORMAL EXIT";
