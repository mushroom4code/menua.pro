<?php

include '/var/www/html/public/includes/setupLight.php';
ignore_user_abort(true);
set_time_limit(0);

define('ICQ_API_ACCESS_TOKEN', '001.1406025859.1903671726:751326972');
define('ICQ_BOT_ID', '751326972'); //Используемая версия API
define('ICQ_NICK', 'Infinity_clinic_bot'); //Используемая версия API
define('ICQ_API_ENDPOINT', "https://api.icq.net/bot/v1/messages/sendText/");

function ICQ_Api_call($params = array()) {
	$params['token'] = ICQ_API_ACCESS_TOKEN;
	$url = ICQ_API_ENDPOINT . '?' . http_build_query($params);
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$json = curl_exec($curl);
	curl_close($curl);
	$response = json_decode($json, true);
	return $response;
}

function ICQ_messagesSend($peer_id, $message) {
	return ICQ_Api_call(array(
		'chatId' => $peer_id,
		'text' => $message
	));
}

function saveEventId($id) {
	$fp = fopen('/var/www/html/public/sync/api/icq/eventId', 'w');
	fwrite($fp, $id);
	fclose($fp);
}

$lastEventID = (file('/var/www/html/public/sync/api/icq/eventId')[0]) ?? 0;
$params['token'] = ICQ_API_ACCESS_TOKEN;
$params['lastEventId'] = $lastEventID;
$params['pollTime'] = 300;
$url = "https://api.icq.net/bot/v1/events/get" . '?' . http_build_query($params);

$started = time();

ICQ_messagesSend('sashnone', 'Так, я перезагрузилась... ' . rt(['С чего бы это?..', 'Вот и славненько!', 'Так что я на связи.', ' Готова к работе!', 'Возможно меня обновили :-)... или я глючу :-(']));
ICQ_messagesSend('sashnone', 'Последнее что помню.... сообщение №' . $params['lastEventId']);

$running = null;
$ch_1 = curl_init($url);
curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
$mh = curl_multi_init();
curl_multi_add_handle($mh, $ch_1);
$confusingMessages = [];

print '{"restarted":"' . date("Y-m-d H:i:s") . '"}' . "\r\n";

while (true) {
	/* ICQ BLOCK */
	curl_multi_exec($mh, $running);
	if (!$running) {
		curl_multi_remove_handle($mh, $ch_1);
		curl_multi_close($mh);
		$curlResult = json_decode(curl_multi_getcontent($ch_1), true);
		$running = null;
		if (isset($curlResult['events']) && count($curlResult['events'])) {
			foreach ($curlResult['events'] as $ICQ_event) {
				$MSG_TYPE = $ICQ_event['payload']['chat']['type'];
				$MSG_FROM_ID = $ICQ_event['payload']['from']['userId'];
				$MSG_FROM_NAME = $ICQ_event['payload']['from']['firstName'];
				$MSG_TEXT = $ICQ_event['payload']['text'];
				if ($MSG_TYPE === 'private') {
					print '{"' . $MSG_FROM_ID . '":"' . $MSG_TEXT . '"}' . "\r\n";

					if (in_array(mb_strtolower($MSG_TEXT), ['ты как?', 'как ты?', 'как дела?'])) {
						ICQ_messagesSend($MSG_FROM_ID, rt(['Да нормально', 'В целом неплохо', 'Хорошо',]) . ', ' . $MSG_FROM_NAME . ', работаю. Уже ' . human_plural_form(floor((time() - $started) / (60 * 60)), ['целый', 'целых', 'целых']) . ' ' . ((time() - $started) / (60 * 60) > 1 ? (human_plural_form(floor((time() - $started) / (60 * 60)), ['час', 'часа', 'часов'], floor((time() - $started) / (60 * 60)) != 1) . ' и ') : '' ) . human_plural_form(floor((time() - $started) / 60) % 60, ['минуту', 'минуты', 'минут'], true) . '.');
						if (!empty($confusingMessages[$MSG_FROM_ID])) {
//							ICQ_messagesSend($MSG_FROM_ID, 'А это не тоже ли самое, что :"' . $confusingMessages[$MSG_FROM_ID] . '"?');
						}
					} elseif (in_array(mb_strtolower($MSG_TEXT), ['/start'])) {
						ICQ_messagesSend($MSG_FROM_ID, $MSG_FROM_NAME . ', привет! Я пока не очень умная, так что отвечать на вопросы какое-то время не смогу. Но постараюсь держать тебя в курсе, если вдруг узнаю что-то новенькое.');
						ICQ_messagesSend($MSG_FROM_ID, '...секундочку...');
						if ($ICQuser = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersICQ`='" . $MSG_FROM_ID . "'"))) {
							ICQ_messagesSend($MSG_FROM_ID, 'Уж не знаю, как так получилось, но Ваш номер ICQ уже есть в моей базе данных! 😳 ' . $ICQuser['usersFirstName'] . ' ' . $ICQuser['usersMiddleName'] . ', очень приятно познакомиться лично!');
						} else {
							ICQ_messagesSend($MSG_FROM_ID, $MSG_FROM_NAME . '... тут такое дело, мне запретили общаться с посторонними 🙁 Если не сложно, подойдите в IT отдел, чтобы они внесли ваш номер (' . $MSG_FROM_ID . ') в базу данных.');
						}
					} elseif (in_array(mb_strtolower($MSG_TEXT), ['есть чё?'])) {
						ICQ_messagesSend($MSG_FROM_ID, json_encode($confusingMessages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
					} else {
						$confusingMessages[$MSG_FROM_ID] = mb_strtolower($MSG_TEXT);
						ICQ_messagesSend($MSG_FROM_ID, $MSG_FROM_NAME . ', ' . rt(['я Вас слышу, но, пока, не понимаю... ', 'мне пока далеко до Алисы, но я учусь...', 'попробуйте сформулировать вопрос иначе, я пока не понимаю.', 'я не тупая, я учусь...', 'я не поняла. Но Папа сказал, что научит.', 'это слишком сложно для меня сейчас. 🙁🙁🙁']));
					}


//					ICQ_messagesSend('sashnone', 'Мне тут ' . $MSG_FROM_NAME . ' пишет, а именно: ' . $MSG_TEXT);
//					ICQ_messagesSend('sashnone', json_encode($ICQ_event, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
				}
			}
		} else {
			//	ICQ_messagesSend('sashnone', 'Тишина, спокойствие....');
		}

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
//		sleep(1);
	}
	/* ICQ BLOCK END */
	usleep(150000);
}
?>