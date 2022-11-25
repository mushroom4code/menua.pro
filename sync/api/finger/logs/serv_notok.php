<?php

include '/var/www/html/public/includes/setupLight.php';
ignore_user_abort(true);
set_time_limit(0);

define('HOST_NAME', "127.0.0.1");
define('PORT', "8081");
$null = NULL;

function send($message) {
	global $clientSocketArray;
	$messageLength = strlen($message);
	foreach ($clientSocketArray as $clientSocket) {
		@socket_write($clientSocket, $message, $messageLength);
	}
	return true;
}

function unseal($socketData) {
	$length = ord($socketData[1]) & 127;
	if ($length == 126) {
		$masks = substr($socketData, 4, 4);
		$data = substr($socketData, 8);
	} elseif ($length == 127) {
		$masks = substr($socketData, 10, 4);
		$data = substr($socketData, 14);
	} else {
		$masks = substr($socketData, 2, 4);
		$data = substr($socketData, 6);
	}
	$socketData = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$socketData .= $data[$i] ^ $masks[$i % 4];
	}
	return $socketData;
}

function seal($socketData) {
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($socketData);

	if ($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif ($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif ($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header . $socketData;
}

function doHandshake($received_header, $client_socket_resource, $host_name, $port) {
	$headers = array();
	$lines = preg_split("/\r\n/", $received_header);
	foreach ($lines as $line) {
		$line = chop($line);
		if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
			$headers[$matches[1]] = $matches[2];
		}
	}
	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	$buffer = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"WebSocket-Origin: $host_name\r\n" .
			"WebSocket-Location: ws://$host_name:$port/demo/shout.php\r\n" .
			"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_socket_resource, $buffer, strlen($buffer));
}

$socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socketResource, '127.0.0.1', PORT);
socket_listen($socketResource);

print "SOCKED STARTED " . date("Y-m-d H:i:s") . "\r\n";
$started = time();
/* ICQ BLOCK */

define('ICQ_API_ACCESS_TOKEN', '001.1406025859.1903671726:751326972');
define('ICQ_BOT_ID', '751326972'); //Используемая версия API
define('ICQ_NICK', 'Infinity_clinic_bot'); //Используемая версия API
//Функция для вызова произвольного метода API


$curls = [];
$send_mh = curl_multi_init();
$started22 = false;

function ICQ_Api_call($params = array(), $endpoint = '/messages/sendText', $buttons = false) {
	$params['token'] = ICQ_API_ACCESS_TOKEN;
	global $curls, $send_mh, $started22;
	$url = "https://api.icq.net/bot/v1" . $endpoint . '?' . http_build_query($params);
	if (1) {
		$running = false;
		$newCurl = curl_init($url);
		curl_setopt($newCurl, CURLOPT_RETURNTRANSFER, true);
		curl_multi_add_handle($send_mh, $newCurl);
		curl_multi_exec($send_mh, $running);
//		print 'started' . "\r\n";
		$started22 = true;
		$curls[] = [
			'resource' => $newCurl,
			'send' => true,
			'running' => $running
		];
		return null;
	} else {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		if ($buttons) {
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, ['inlineKeyboardMarkup' => json_encode($buttons)]);
		}
		$json = curl_exec($curl);
		curl_close($curl);
		$response = json_decode($json, true);
		return $response;
	}
}

//Функция для вызова messages.send
function ICQ_messagesSend($peer_id, $message, $buttons = false) {
	return ICQ_Api_call(
			['chatId' => $peer_id, 'text' => $message],
			'/messages/sendText',
			$buttons
	);
}

function ICQ_callbackSend($query_id, $message) {
	return ICQ_Api_call(
			[
				'queryId' => $query_id,
				'text' => $message,
//'showAlert' => true
			],
			'/messages/answerCallbackQuery'
	);
}

function ICQ_messagesSendVoice($peer_id, $message) {
	return ICQ_Api_call(array(
		'chatId' => $peer_id,
		'fileId' => $message
			), '/messages/sendVoice');
}

ini_set('track_errors', 1);

function saveEventId($id) {
	$fp = fopen('/var/www/html/public/sync/api/icq/eventId', 'w');
	if (!$fp) {
		echo 'fopen failed. reason: ', $php_errormsg;
	}
	$result = fwrite($fp, $id);
	fclose($fp);
	return $result;
}

$lastEventID = (file('/var/www/html/public/sync/api/icq/eventId')[0]) ?? 0;
$params['token'] = ICQ_API_ACCESS_TOKEN;
$params['lastEventId'] = $lastEventID;
$params['pollTime'] = 300;
$url = "https://api.icq.net/bot/v1/events/get" . '?' . http_build_query($params);
$running = null;
$ch_1 = curl_init($url);
curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
$mh = curl_multi_init();
curl_multi_add_handle($mh, $ch_1);
$confusingMessages = [];


/* ICQ */

$lastPing = [];
$warningState = [];
$warningSent = [];
ICQ_messagesSend('sashnone', 'Так, я перезагрузилась... ' . rt(['С чего бы это?..', 'Вот и славненько!', 'Так что я на связи.', ' Готова к работе!', 'Возможно меня обновили :-)... или я глючу :-(']));

$ICQ_NUMBER_PENDING = [];
$ICQ_NUMBER_PENDING_READY = null;
$ICQ_NUMBER_PENDING_USER_ID = null;
$clientSocketArray = array($socketResource);

$usersSeen = [];

function lastseen($iduser) {
	global $usersSeen;
	if (!isset($usersSeen[$iduser])) {
		$usersSeen[$iduser] = time();
		return true;
	}
	if (time() - $usersSeen[$iduser] <= 4) {
		$usersSeen[$iduser] = time();
		return false;
	}
	$usersSeen[$iduser] = time();
	return true;
}

$lastBroadcast = time();
$APM = 0;
$APM_time = date("s");
while (true) {
	curl_multi_exec($send_mh, $running22);
	if ($started22 && !$running22) {
		foreach ($curls as &$curl) {
			curl_multi_remove_handle($send_mh, $curl['resource']);
			$curlIndex = array_search($curl, $curls);
			unset($curls[$curlIndex]);
//			print 'finished' . "\r\n";
		}
		$started22 = false;
	}






	if ($APM_time != date("s")) {
		mysqlQuery("INSERT INTO `DEBUG_APM` SET `DEBUG_APM_value` = $APM");
		$APM = 0;
		$APM_time = date("s");
	}
	$APM++;
	if (time() - $lastBroadcast >= 600) {
		send(seal('GP ' . date("H:i:s")));
		$lastBroadcast = time();
	}

	/* ---------------------------------------------------------------------ICQ---------------------------------------------------------------------- */
	curl_multi_exec($mh, $running);
	if (!$running) {
		curl_multi_remove_handle($mh, $ch_1);
		curl_multi_close($mh);
		$curlResult = json_decode(curl_multi_getcontent($ch_1), true);
		$running = null;
		if (isset($curlResult['events']) && count($curlResult['events'])) {
			foreach ($curlResult['events'] as $ICQ_event) {
				mysqlQuery("INSERT INTO `ICQevents` SET `ICQeventEvent` = '" . mysqli_real_escape_string($link, json_encode($ICQ_event, 288)) . "'");
				$MSG_TYPE = $ICQ_event['payload']['chat']['type'] ?? null;
				$MSG_FROM_ID = $ICQ_event['payload']['from']['userId'];
				$MSG_FROM_NAME = $ICQ_event['payload']['from']['firstName'];
				$MSG_TEXT = $ICQ_event['payload']['text'] ?? null;
				$IS_STICKER = ($ICQ_event['payload']['parts'][0]['type'] ?? false) == "sticker";
				$MSG_CALLBACK = $ICQ_event['payload']['callbackData'] ?? null; //' ответ пользователя (нажатие на кнопку) данные
				$MSG_QUERYID = $ICQ_event['payload']['queryId'] ?? null;

				if ($ICQ_event['type'] === 'callbackQuery' && $MSG_CALLBACK && $MSG_QUERYID) {
//					print "VALID CALLBACK\r\n";
					ICQ_callbackSend($MSG_QUERYID, '');
					ICQ_messagesSend($MSG_FROM_ID, $MSG_CALLBACK . ' - это хорошо!');
				} elseif ($MSG_TYPE === 'private' && isset($ICQ_event['payload']['parts']) && $ICQ_event['payload']['parts'][0]['type'] == 'voice') {
					ICQ_messagesSendVoice($MSG_FROM_ID, rt([
						'I0005XD9wnFDtGmxKdNrES5e7dcd221bd',
						'I0005hhnT6h3HZjerVxCh45e7dcd491bd',
						'I0004UnGgU0Ryf9REWa7NP5e7dcd661bd',
						'I00031J74nCvI4mqK0pNei5e7dd8d41bd',
						'I0004C54eO2u6CSaBIFGU65e7dd9d21bd',
						'I0005gmZO4nKYkrpWQfXw15e7dda441bd',
						'I0005gmZO4nKYkrpWQfXw15e7dda441bd',
						'I00099jJt2dI7P2mAJOg155e7ddaf11bd',
						'I000eJ1WXUSrVNIxhMG9nf5e7ddbb41bd',
					]));
					if ($MSG_FROM_ID != '751363572') {
						ICQ_messagesSend('sashnone', 'Мне тут ' . $ICQuser['usersFirstName'] . ' ' . $ICQuser['usersLastName'] . ' [' . $ICQuser['idusers'] . '] пишет, а именно: ' . $MSG_TEXT);
					}
				} elseif ($MSG_TYPE === 'private') {
					print '{"' . $MSG_FROM_ID . '":"' . $MSG_TEXT . '"}' . "\r\n";

					if ($ICQuser = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersICQ`='" . $MSG_FROM_ID . "'"))) {

						if ($MSG_FROM_ID != '751363572') {
							ICQ_messagesSend('sashnone', 'Мне тут ' . $ICQuser['usersFirstName'] . ' ' . $ICQuser['usersLastName'] . ' [' . $ICQuser['idusers'] . '] пишет, а именно: ' . $MSG_TEXT);
						}
						$buttons = false;


						$delay_ms = 0;




						if (in_array(trim(mb_strtolower($MSG_TEXT)), ['ты как', 'ты как?', 'как ты?', 'как ты', 'как дела?', 'как дела'])) {
							$msgtext = rt(
											['Да нормально', 'В целом неплохо', 'Хорошо',])
									. ', ' . $ICQuser['usersFirstName']
									. ', работаю. Последний раз перезагружалась ' . secondsToTime(time() - $started) . ' назад. ' . $ICQuser['usersFirstName'] . ', а как у Вас дела?';
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['инфи', 'инфи!', 'инфи.'])) {
							$delay_ms = 1000;
							$msgtext = rt(['Агась!',
								'Да',
								'Слушайу 😀😀😀',
								'Тута я.',
								'Чего?',
								'Я',
								'Ага',
								'Ну, попробуйте :)',
								$ICQuser['usersFirstName'] . '?']
							);
						} elseif (preg_match('/^П\s(.+)/iu', $MSG_TEXT, $matches) && !empty($matches[1])) {
							$msgtext = 'O! Кажись поняла: ' . $matches[1] . '?';
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['датчики'])) {
							$msgtext = '';
							if (!count($lastPing)) {
								$msgtext = 'А чего-то данных то и нет совсем :(';
							}
							foreach ($lastPing as $idsensor => $lastSeen) {
								$msgtext .= '№' . $idsensor . ' ' . secondsToTime(time() - $lastSeen) . "\r\n";
							}
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['почему', 'почему?'])) {
							$msgtext = rt(['Агась!',
								'Что почему?',
								'Я че-го-то не-пой-му 🤪',
								'Что кончается на У?',
								'Странные вопросы задаёте, ' . $ICQuser['usersFirstName'] . ' ' . $ICQuser['usersMiddleName'] . ', откуда ж мне знать почему? Я же даже не знаю что почему... '
									]
							);
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['есть чё?'])) {
							$msgtext = json_encode($confusingMessages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\r\n\r\n" . json_encode($ICQ_NUMBER_PENDING, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['Тяжело', 'Тяжело!', 'Тяжело.'])) {
							$msgtext = rt(['Ну а кому сейчас не тяжело.... Мне вот тоже.',
								'Понимаю... почти...',
								'Со мной многим тяжело. Александр так вообще страдает там...',
								'Сейчас не очень, а вот через пару лет будет тяжелее... а потом ещё... ой.. давай не будем о грустном.',
								$ICQuser['usersFirstName'] . ', Вы держитесь там...'
									]
							);
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['Я ушла'])) {
							$msgtext = 'Всего доброго!';
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['Я не ушла', 'Я ещё не домой'])) {
							$msgtext = 'А, ну ок..';
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['кнопки'])) {
							$msgtext = 'Смотри, что я умею!!';
							$buttons = [
								[
									[
										"text" => "Да",
										"callbackData" => "yes"
									]
									,
									[
										"text" => "Нет",
										"callbackData" => "no"
									]
									,
									[
										"text" => "Наверное",
										"callbackData" => "maybe"
									]
								]
							];
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['открой', 'пусти', 'тук-тук'])) {
							if (in_array($MSG_FROM_ID, ['747996106', '751363572', '740402627'])) {
								send(seal('open'));
								$msgtext = 'ок';
							} else {
								$msgtext = 'Мне запретили открывать дверь. Воспользуйтесь карточкой или терминалом Face-ID.';
							}
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['мои продажи', 'продажи'])) {
							$from = date("Y-m-01");
							$to = date("Y-m-d");
							$sales = query2array(mysqlQuery("SELECT * FROM"
											. " `f_sales`"
											. " LEFT JOIN `f_salesToPersonal` ON (`idf_sales` = `f_salesToPersonalSalesID`)"
											. " LEFT JOIN `users` ON (`idusers` = `f_salesToPersonalUser`)"
											. " WHERE `f_salesDate` BETWEEN '$from' AND '$to'"
											. " AND `f_salesSumm` >= '25000'"
											. " AND isnull(`f_salesCancellationDate`)"));

							$mysales = array_filter($sales, function($sale) {
								global $ICQuser;
								return $sale['f_salesToPersonalUser'] === $ICQuser['idusers'];
							});
							$mysales = obj2array($mysales);
							$mysqlescouter = 0;
							foreach ($mysales as $mysale) {
								$mysqlescouter += 1 / count(array_filter($sales, function($sale) {
													global $mysale;
													return $sale['f_salesToPersonalSalesID'] === $mysale['f_salesToPersonalSalesID'];
												}));
							}

							if ($ICQuser['usersICQ']) {
								ICQMSDelay(0, $ICQuser['usersICQ'], rt([
									'Секундочку...',
									'Сейчас гляну...',
									'Так...',
									'Уже ищу....',
												]
								));
							}

							if ($mysqlescouter <= 2) {
								$delay_ms = rand(2000, 3000);
							} elseif ($mysqlescouter <= 10) {
								$delay_ms = rand(3000, 7000);
							} else {
								$delay_ms = rand(5000, 10000);
							}

//							$mysqlescouter = 22.333;
							$msgtext = "";
							if ($mysqlescouter == 0) {
								$msgtext .= rt([
									'Тут такое дело, я не смогла найти ни одной продажи... :-(',
									'Ну, как бы, нету ничего.. :-(',
									'Видите ли, в этом месяце у Вас продаж ещё не было.',
									'Так вышло, что за этот месяц у Вас нет продаж.',
									'Какие продажи? Не вижу никаких продаж...' . urldecode("%F0%9F%A4%94") . ' По крайней мере за этот месяц.',
										]
								);
							} else {
								$msgtext .= rt([
									'С первого числа я вижу ' . round($mysqlescouter, 2) . ' ' . human_plural_form(floor($mysqlescouter), ['продажа', 'продажи', 'продаж']) . '.',
									'С первого ' . $_MONTHES['full']['gen'][date('n')] . ' я насчитала ' . round($mysqlescouter, 2) . ' ' . human_plural_form(floor($mysqlescouter), ['продажа', 'продажи', 'продаж']) . '.',
									'По моим данным у Вас ' . round($mysqlescouter, 2) . ' ' . human_plural_form(floor($mysqlescouter), ['продажа', 'продажи', 'продаж']) . ' с 1го числа.',
									'Наданный момент у Вас ' . round($mysqlescouter, 2) . ' ' . human_plural_form(floor($mysqlescouter), ['продажа', 'продажи', 'продаж']) . '. (Это с 1го ' . $_MONTHES['full']['gen'][date('n')] . ')',
									'На Ваше имя с 1го ' . $_MONTHES['full']['gen'][date('n')] . ' внесено ' . round($mysqlescouter, 2) . ' ' . human_plural_form(floor($mysqlescouter), ['продажа', 'продажи', 'продаж']) . '.',
								]);
								$msgtext .= "\r\n";
								$msgtext .= rt([
									' Я пока  не знаю, много это или мало, но в любом случае так держать! ;-)',
//									' Это много это или мало?',
								]);
								$avg = $mysqlescouter / date('j');

								$msgtext .= "\r\n";

								$msgtext .= rt([
									' Я так прикинула, это где-то ' . round($avg, 2) . ' ' . human_plural_form(floor($avg), ['продажа', 'продажи', 'продаж']) . ' в день.',
									' Кстати это где-то ' . round($avg, 2) . ' ' . human_plural_form(floor($avg), ['продажа', 'продажи', 'продаж']) . ' в день.',
									' Это примерно ' . round($avg, 2) . ' ' . human_plural_form(floor($avg), ['продажа', 'продажи', 'продаж']) . ' в день.',
								]);
							}


							//
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['мои смены', 'смены'])) {
							$delay_ms = rand(4000, 7000);
							if ($ICQuser['usersICQ']) {
								ICQMSDelay(0, $ICQuser['usersICQ'], rt([
									'Секундочку...',
									'Сейчас гляну...',
									'Так...',
									'Уже ищу....',
												]
								));
							}
							$dutesARR = query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`fingerLogTime`) AS `TS` "
											. "FROM `fingerLog` "
											. "WHERE `fingerLogUser`='" . $ICQuser['idusers'] . "'"
											. " AND `fingerLogTime`>DATE_SUB(CURDATE(), INTERVAL 30 DAY)"));
							$outputDuties = [];
							usort($dutesARR, function($a, $b) {
								return $a['TS'] <=> $b['TS'];
							});
							foreach ($dutesARR as $duty) {
								if (!isset($outputDuties[date('Y-m-d', $duty['TS'])])) {
									$outputDuties[date('Y-m-d', $duty['TS'])][0] = date('H:i', $duty['TS']);
									$outputDuties[date('Y-m-d', $duty['TS'])]["s"] = $duty['TS'];
								} else {
									$outputDuties[date('Y-m-d', $duty['TS'])][1] = date('H:i', $duty['TS']);
									$outputDuties[date('Y-m-d', $duty['TS'])][2] = $duty['TS'] - $outputDuties[date('Y-m-d', $duty['TS'])]["s"];
								}
							}
							$monthes = ['', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
							$daynames = ['', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
							$month = null;
							if (count($outputDuties)) {
								$msgtext .= "\r\nЗа последние 30 дней я нашла:";
								foreach ($outputDuties as $date => $times) {
									$TS = strtotime($date);
									if ($month != date("m", $TS)) {
										$month = intval(date("m", $TS));
										$msgtext .= "\r\n\r\n" . $monthes[$month];
									}
									$msgtext .= "\r\n";
									$msgtext .= date("d", $TS) . ' (' . $daynames[date("N", $TS)] . ')' . ': c ' . $times[0] . ' по ' . ($times[1] ?? ' нет ухода') . (isset($times[2]) ? (' (' . human_plural_form(round($times[2] / (60 * 60)), ['час', 'часа', 'часов'], true) . ')') : '');
								}
							} else {
								$msgtext .= "\r\n" . rt([
											'А чего-то и нет ничего..',
											'Не нашла...',
											'Странно... не вижу записей.',
											'Нет данных.',
												]
								);
							}
//							if ($ICQuser['idusers'] != 176) {
//								$msgtext = 'Ой, а мне пока  нельзя раскрывать эту информацию. 🤭';
//							}
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['спасибо', 'спасибо!', 'спасибо.', 'спасибо !'])) {
							$msgtext = rt(['Вам спасибо!',
								'Вам спасибо!',
								'Да не за что, вроде...',
								'Пожалуйста!',
								'Пожалуйста!',
								'Пожалуйста!',
								'Рада помочь!',
								'Рада помочь!',
								'Рада помочь! ' . $ICQuser['usersFirstName'] . ', если что - обращайтесь.']
							);
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['А что ты понимаешь?', 'что ты понимаешь?', 'что ты понимаешь', 'А что ты можешь?', 'что ты можешь?', 'что ты можешь'])) {

							$msgtext = rt([
								'Ну, во-первых я могу ответить на вопрос "Как дела?", мне всегда приятно, когда интересуются моими делами. Правада. Во-вторых... блин, а больше ничего не умею... Разве что ответить на "Привет!"... Александр сказал это "п🤭😬?🤬ц" как сложно меня учить... но он не объяснил что такое "п🤭😬?🤬ц"',
							]);
						} elseif (in_array(trim(mb_strtolower($MSG_TEXT)), ['/start', 'привет', 'здравствуй', ''])) {
							$msgtext = 'Здравствуйте, ' . $ICQuser['usersFirstName'] . ' ' . $ICQuser['usersMiddleName'] . '!';
//							ICQ_messagesSend($MSG_FROM_ID, 'https://files.icq.net/get/27qakFNrO03wPK4v9ckxsy5a84384d1ab');
						} elseif ($IS_STICKER) {
							$msgtext = 'Спасибо! Сохраню к себе.';
							mysqlQuery("INSERT INTO `ICQmessages` SET `ICQmessagesUser` = '" . $ICQuser['idusers'] . "',`ICQmessagesMessage` = '" . mysqli_real_escape_string($link, trim(mb_strtolower($MSG_TEXT))) . "'");
							$delay_ms = 3000;
						} else {
//							$confusingMessages[$MSG_FROM_ID] = trim(mb_strtolower($MSG_TEXT));
							mysqlQuery("INSERT INTO `ICQmessages` SET `ICQmessagesUser` = '" . $ICQuser['idusers'] . "',`ICQmessagesMessage` = '" . mysqli_real_escape_string($link, trim(mb_strtolower($MSG_TEXT))) . "'");
							$msgtext = rt([
								'...',
//								'я Вас слышу, но, пока, не понимаю... ',
//								'мне пока далеко до Алисы, но я учусь...',
//								$ICQuser['usersFirstName'] . ', попробуйте сформулировать вопрос иначе, я пока не понимаю.',
//								'я не тупая, я учусь...',
//								$ICQuser['usersFirstName'] . ', я не поняла. Но Папа сказал, что научит.',
//								$ICQuser['usersFirstName'] . ', это слишком сложно для меня сейчас. 🙁🙁🙁',
//								$ICQuser['usersFirstName'] . ', я передам Александру, он посмотрит что можно сделать. Чтобы я начала понимать.',
//								$ICQuser['usersFirstName'] . ', я передам Александру, он посмотрит что можно сделать. Чтобы я начала понимать.',
//								$ICQuser['usersFirstName'] . ', я передам Александру, он посмотрит что можно сделать. Чтобы я начала понимать.',
//								$ICQuser['usersFirstName'] . ', я передам Александру, он посмотрит что можно сделать. Чтобы я начала понимать.',
							]);
						}
						if ($msgtext) {
//							ICQ_messagesSend($ICQuser['usersICQ'], $msgtext, $buttons);
							ICQ_messagesSend($ICQuser['usersICQ'], $msgtext);
						}

						if ($MSG_FROM_ID != '751363572') {
							ICQ_messagesSend('sashnone', date("i:s") . ' Я: ' . $ICQuser['usersLastName'] . ' ' . $ICQuser['usersFirstName'] . ': ' . $msgtext);
						}
					} else {
						ICQ_messagesSend('sashnone', 'Мне тут ' . $MSG_FROM_NAME . ' пишет (аська не подключена), а именно: ' . $MSG_TEXT);
						if (!in_array($MSG_FROM_ID, $ICQ_NUMBER_PENDING)) {
							ICQ_messagesSend($MSG_FROM_ID, $MSG_FROM_NAME . '... тут такое дело, мне запретили общаться с посторонними 🙁 Если не сложно, подойдите к терминалу Face-ID, чтобы я на Вас взглянула.');
							$ICQ_NUMBER_PENDING[] = $MSG_FROM_ID;
							ICQ_messagesSend($MSG_FROM_ID, 'Вы готовы пройти сканирование?');
						} elseif (in_array($MSG_FROM_ID, $ICQ_NUMBER_PENDING) && !$ICQ_NUMBER_PENDING_READY) {
							if (in_array(trim(mb_strtolower($MSG_TEXT)), ['да', 'угу', 'ага', 'ок', 'ok', 'да.', 'да!', 'готов', 'готова'])) {
								$ICQ_NUMBER_PENDING_READY = $MSG_FROM_ID;
								ICQ_messagesSend($MSG_FROM_ID, 'Хорошо. Встаньте перед сканером.');
							} else {
								ICQ_messagesSend($MSG_FROM_ID, 'Ну ладно, тогда в другой раз. ' . $MSG_FROM_NAME . ', напишите, как будете готовы.');
								$ICQ_NUMBER_PENDING = array_filter($ICQ_NUMBER_PENDING, function($elem) use($MSG_FROM_ID) {
									return $elem != $MSG_FROM_ID;
								});
								$ICQ_NUMBER_PENDING_READY = null;
							}
						} elseif (in_array($MSG_FROM_ID, $ICQ_NUMBER_PENDING) && $ICQ_NUMBER_PENDING_READY && $ICQ_NUMBER_PENDING_USER_ID) {


							if (in_array(trim(mb_strtolower($MSG_TEXT)), ['да', 'угу', 'ага', 'ок', 'ok', 'да.', 'да!', 'я', 'я.', 'я!'])) {
								mysqlQuery("UPDATE `users` SET `usersICQ`= '" . $ICQ_NUMBER_PENDING_READY . "' WHERE `idusers`='" . $ICQ_NUMBER_PENDING_USER_ID . "'");
								$ICQ_NUMBER_PENDING_READY = null;
								$ICQ_NUMBER_PENDING_USER_ID = null;
								$ICQ_NUMBER_PENDING = array_filter($ICQ_NUMBER_PENDING, function($elem) use($MSG_FROM_ID) {
									return $elem != $MSG_FROM_ID;
								});
								ICQ_messagesSend($MSG_FROM_ID, 'Супер! Я записала Ваш номер к себе!');
							} else {
								ICQ_messagesSend($MSG_FROM_ID, 'Не поняла... ' . $MSG_FROM_NAME . ', если не трудно, подойдите в IT отдел (это 41й кабинет, справа вконце коридора), чтобы они там разобрались что к чему.');
								$ICQ_NUMBER_PENDING_READY = null;
								$ICQ_NUMBER_PENDING_USER_ID = null;
								$ICQ_NUMBER_PENDING = array_filter($ICQ_NUMBER_PENDING, function($elem) use($MSG_FROM_ID) {
									return $elem != $MSG_FROM_ID;
								});
							}
						}
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
			ICQ_messagesSend('sashnone', json_encode($curlResult, 288));
		}

		$params['lastEventId'] = $lastEventID;
		$url = "https://api.icq.net/bot/v1/events/get" . '?' . http_build_query($params);
		$ch_1 = curl_init($url);
		curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
		$mh = curl_multi_init();
		curl_multi_add_handle($mh, $ch_1);
//		sleep(1);
	}


	/* ---------------------------------------------------------------------ICQ-END--------------------------------------------------------------------- */

	foreach ($lastPing as $board => $time) {
		if (!isset($warningState[$board])) {
			ICQ_messagesSend('sashnone', date("H:i:s") . ') Вижу датчик №' . $board);
			$lastPing[$board] = time();
			$warningState[$board] = false;
			$warningSent[$board] = false;
		}
		if (time() - $lastPing[$board] > 30) {
			$warningState[$board] = true;
		} else {
			if ($warningState[$board]) {
				ICQ_messagesSend('sashnone', date("H:i:s") . ') ' . 'Всё норм, сканер №' . $board . '  вижу');
			}
			$warningState[$board] = false;
			$warningSent[$board] = false;
		}
		if ($warningState[$board] && !$warningSent[$board]) {
			$warningSent[$board] = true;
			ICQ_messagesSend('sashnone', date("H:i:s") . ') ' . 'Капец... Сканер №' . $board . ' отвалился...');
		}
	}





	$newSocketArray = $clientSocketArray;
	socket_select($newSocketArray, $null, $null, 0, 10);

	if (in_array($socketResource, $newSocketArray)) {
		$newSocket = socket_accept($socketResource);
		$clientSocketArray[] = $newSocket;

//var_dump($clientSocketArray);
		$header = socket_read($newSocket, 1024);
		doHandshake($header, $newSocket, HOST_NAME, PORT);

//		print "connecting....\r\n";
//	send($connectionACK);

		$newSocketIndex = array_search($socketResource, $newSocketArray);
		unset($newSocketArray[$newSocketIndex]);
	}

	foreach ($newSocketArray as $newSocketArrayResource) {
		while (socket_recv($newSocketArrayResource, $socketData, 1024, 0) >= 1) {
			$socketMessage = unseal($socketData);
			$messageObj = json_decode($socketMessage, true);
//var_dump($messageObj);
			$user = [];
			if (isset($messageObj['user']) && lastseen($messageObj['user'])) {

				$fingerPrint = hexdec(FSS($messageObj['user'])) - 65536;
				$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersFinger` = '" . $fingerPrint . "' AND isnull(`usersDeleted`)"));

				if (date("H") <= 5 || date("H") >= 22) {
					$daytimeGreet = $user['usersFirstName'] . ', ' . 'доброй ночи!';
				} elseif (date("H") >= 6 && date("H") < 11) {
					$daytimeGreet = rt(
							[
								($user['usersFirstName'] . ', ' . ' доброе утро 🌿'),
								'https://files.icq.net/get/28g8g000kaIqu0BO7vNiHD5e4526761ad',
								'https://files.icq.net/get/28g8g000neadehrqcqsqal5e90c9d91bg',
								'https://files.icq.net/get/24s2wfj5sdzmvlawrhyvxl5a3143f61bg',
								'https://files.icq.net/get/28g8gnbbzv5bxzoouxxrjb5aa21bce1ae',
								'https://files.icq.net/get/25a5alwqgxjgfkxnzqwxea5a37a14a1ab'
					]); // rand(0, 100) > 50 ? ($user['usersFirstName'] . ', ' . 'Доброе утро!') : 'https://files.icq.net/get/28g8g000kaIqu0BO7vNiHD5e4526761ad';
				} elseif (date("H") >= 11 && date("H") <= 18) {
					$daytimeGreet = $user['usersFirstName'] . ', ' . 'добрый день!';
				} else {
					$daytimeGreet = $user['usersFirstName'] . ', ' . ' добрый вечер!';
				}




				if (!$user['usersICQ']) {
					if ($ICQ_NUMBER_PENDING_READY) {
						ICQ_messagesSend($ICQ_NUMBER_PENDING_READY, '' . $user['usersFirstName'] . ' ' . $user['usersMiddleName'] . ', это Вы?');
						$ICQ_NUMBER_PENDING_USER_ID = $user['idusers'];
					}
				}


				if ($user && mysqli_num_rows(mysqlQuery("SELECT * FROM `fingerLog` WHERE `fingerLogUser`='" . $user['idusers'] . "' AND `fingerLogTime`>'" . date("Y-m-d 00:00:00") . "' AND `fingerLogTime`<='" . date("Y-m-d 23:59:59") . "'")) == 0) {

					ICQ_messagesSend('AoLFvZMa-Veec2ceMVk', date("H:i:s") . ') Приход: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . '.');
					if ($user['usersICQ']) {
						$msgtext = rt([
							$user['usersFirstName'] . ', ' . 'здравствуйте!',
							$user['usersFirstName'] . ', ' . 'приветствую!',
							$user['usersFirstName'] . ', ' . 'добро пожаловать!',
							$user['usersFirstName'] . ', ' . 'я скучала!',
							$user['usersFirstName'] . ', ' . 'Апчхи! Ой...',
							$user['usersFirstName'] . ', ' . 'привет!',
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
							$daytimeGreet,
							$daytimeGreet,
							$daytimeGreet,
							$daytimeGreet,
							$user['usersFirstName'] . ', ' . 'рада видеть!']);

						ICQ_messagesSend($user['usersICQ'], $msgtext);
						ICQ_messagesSend('sashnone', date("i:s") . ' Я: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ': ' . $msgtext);
					}
				} elseif ($user) {

					$query = "SELECT  unix_timestamp(MIN(`fingerLogTime`)) AS `fingerLogTime` FROM `fingerLog` WHERE `fingerLogUser`='" . $user['idusers'] . "' AND `fingerLogTime`>'" . date("Y-m-d 00:00:00") . "' AND `fingerLogTime`<='" . date("Y-m-d 23:59:59") . "'";
//print $query;
					$DayBeginTime = mfa(mysqlQuery($query))['fingerLogTime'];

//😍 😀 😉 😔
					$workingHours = floor((time() - $DayBeginTime) / (60 * 60));
					if (time() - $DayBeginTime < 20) {
						$msgtext = rt([
							'Вижу вижу :-)',
							'Достаточно :)))',
							'Да узнала я! ;-)',
							$user['usersFirstName'] . ', я может быть и глупая, но не слепая 😉, Вас я узнаЮ!😘',
							'Записала уже.']);
					} elseif (time() - $DayBeginTime < 60 * 60 * 8) {

						$msgtext = rt([
							'😉',
							'О! Какие люди!',
							'Проходите :-) Дверь открыта.',
							'Привет, привет 😉',
							'Я тоже скучала. 😍']);
					} else {
//						ICQ_messagesSend('AoLFvZMa-Veec2ceMVk', date("H:i:s") . ') Уход: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ' ');

						$msgtext = rt([
							$user['usersFirstName'] . ' ' . $user['usersMiddleName'] . ', до свидания! Спасибо за работу 😍! Я записала ' . human_plural_form($workingHours, ['час', 'часа', 'часов'], true) . ' в табель. 😉',
							$user['usersFirstName'] . ' ' . $user['usersMiddleName'] . ', до свидания!',
							$user['usersFirstName'] . ' ' . $user['usersMiddleName'] . ', всего Вам доброго!',
							$user['usersFirstName'] . ' ' . $user['usersMiddleName'] . ', до свидания! 😘 Спасибо Вам за ваше время (' . $workingHours . 'ч. 😉) Везёт домой идёте... А мне тут круглосуточно... 😔',
							$user['usersFirstName'] . ' ' . $user['usersMiddleName'] . ', До свидания. 😘 Надеюсь ' . human_plural_form($workingHours, ['этот', 'эти', 'эти']) . ' ' . human_plural_form($workingHours, ['час', 'часа', 'часов'], true) . ' на работе прошли хорошо! 😉 Всего доброго Вам!',
						]);
//						$msgtext = '';
					}
					if ($user['usersICQ'] && $msgtext) {
						ICQ_messagesSend($user['usersICQ'], $msgtext);
						ICQ_messagesSend('sashnone', date("i:s") . ' Я: ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ': ' . $msgtext);
					}
				} elseif (!$user) {
					ICQ_messagesSend('AoLFvZMa-Veec2ceMVk', 'Ой... Кто-то пришел и отметился (отпечаток №' . $fingerPrint . '), но я забыла кто это :-(');
				}
//	var_dump($user);
				$qtext = "INSERT INTO `fingerLog` SET `fingerLogData`='" . $fingerPrint . "', `fingerLogUser` = " . ($user['idusers'] ?? 'null') . "";
// print "\r\nQUERY: " . $qtext . "\r\n";
				mysqlQuery($qtext);
				if ($user) {
					send(seal(json_encode(['user' => ['id' => $user['idusers'], 'name' => $user['usersLastName'] . ' ' . $user['usersFirstName']], 'time' => date('H:i:s')], 288)));
					send(seal('open'));
				}
			}
//var_dump($messageObj);
			if (isset($messageObj['ping'])) {
				$lastPing[($messageObj['board_id'] ?? 'noid')] = time();
				$lastRuntime[($messageObj['board_id'] ?? 'noid')] = $messageObj['ping'];
				mysqlQuery("INSERT INTO `DEBUG_pings` SET "
						. "`DEBUG_ping_id`='" . ($messageObj['board_id'] ?? 'noid') . "',"
						. "`DEBUG_pings_count`=" . ($messageObj['pingCount'] ?? 'null') . ""
						. "");
			}
			if (isset($messageObj['initial'])) {
				$lastPing[($messageObj['initial'] ?? 'noid')] = time();
				ICQ_messagesSend('sashnone', 'Включился датчик №' . $messageObj['initial']);
			}

			/*
			 *
			 *
			 * ["idusers"]=>  string(3) "176"
			 *   ["usersLastName"]=>  string(10) "Ольха"
			  ["usersFirstName"]=>  string(18) "Александр"
			  ["usersMiddleName"]=>  string(18) "Сергеевич"
			  ["usersBarcode"]=>  string(16) "5277023426817866"
			  ["usersDeleted"]=>  NULL
			  ["usersRightsChanged"]=>  NULL
			  ["usersFired"]=>  NULL
			  ["usersStyles"]=>  string(1) "2"
			  ["usersFinger"]=>  string(1) "2"


			  array(2) {
			  ["user"]=>
			  string(6) "000003"
			  ["time"]=>
			  string(7) "2006024"
			  }

			  array(1) {
			  ["ping"]=>
			  string(7) "2138981"
			  }

			  array(2) {
			  ["user"]=>
			  string(6) "00ffff"
			  ["time"]=>
			  string(7) "1987137"
			  }
			 */



//$chat_box_message = createChatBoxMessage($messageObj['chat_user'], $messageObj['chat_message']);
//send($chat_box_message);
			break 2;
		}

		$socketData = @socket_read($newSocketArrayResource, 1024, PHP_NORMAL_READ);
		if ($socketData === false) {
//			ICQ_messagesSend('sashnone', 'SOCKET DISCONNECT');
//socket_getpeername($newSocketArrayResource, $client_ip_address);
//print "CLIENT DISCONNECTED\r\n";
//$connectionACK = connectionDisconnectACK($client_ip_address);
//send($connectionACK);
			$newSocketIndex = array_search($newSocketArrayResource, $clientSocketArray);
			unset($clientSocketArray[$newSocketIndex]);
		}
	}
	usleep(1500);
}
socket_close($socketResource);
ICQ_messagesSend('sashnone', 'Сокеты тю-тю...');
?>