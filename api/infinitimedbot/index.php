<?php

include $_SERVER['DOCUMENT_ROOT'] . "/sync/includes/setupLight.php";

$text = trim($_JSON['message']['text'] ?? '');

if (mb_strtolower($text) == 'chatid') {
	infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $_JSON['message']['chat']['id']]);
	exit();
}

if (($_JSON['message']['chat']['type'] ?? false) == 'private') {//Общение через личку
	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => json_encode($_JSON, 288 + 128)]);

	$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `clientsTG`='" . mres($_JSON['message']['chat']['id']) . "'"));

	if (mb_strtolower($text) == 'сколько') {
		$cnt = mfa(mysqlQuery("SELECT count(1) as `cnt` FROM `clients` WHERE NOT isnull(`clientsTG`)"));
		infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Телеграм подключили ' . human_plural_form($cnt['cnt'], ['клиент', 'клиента', 'клиентов'], 1)]);
		exit();
	}


	preg_match('/(\/start) (\w+)/', $text, $matches);

	if (($_JSON['message']['entities'][0]['type'] ?? '') == "bot_command") {
		infinitimedbot('deleteMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'message_id' => $_JSON['message']['message_id']]);
	}

	if (trim($_JSON['message']['text'] ?? '') === '/start') {
		if ($client) {
			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $client['clientsFName'] . ' ' . $client['clientsMName'] . ', Вы уже успешно подключили телеграм бота медицинского центра Инфинити.' . "\n\n" . 'Дополнительных действий не требуется.', 'idclients' => ($client['idclients'] ?? null)]);
		} else {
			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Подключить телеграм-бота могут только клиенты клиники перейдя по пригласительной ссылке или QR коду. Обратитесь в регистратуру.', 'idclients' => ($client['idclients'] ?? null)]);
		}

		exit();
	}
	if (($matches[1] ?? '') === '/start' && strlen($matches[2] ?? '') == 5) {
		sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => json_encode($matches, 288 + 128)]);
		if ($client) {
			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $client['clientsFName'] . ' ' . $client['clientsMName'] . ', Вы уже успешно подключили телеграм бота медицинского центра Инфинити.' . "\n\n" . 'Дополнительных действий не требуется.', 'idclients' => ($client['idclients'] ?? null)]);
			die();
		}
		$clientREG = mfa(mysqlQuery("SELECT * FROM `clients` WHERE BINARY `clientscQR`='" . mres($matches[2]) . "'"));
		if (!$clientREG) {
			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Недействительный код', 'idclients' => ($client['idclients'] ?? null)]);
			die();
		}
		mysqlQuery("UPDATE `clients` SET `clientsTG`= NULL WHERE `clientsTG` = " . $_JSON['message']['chat']['id']);

		if (!$clientREG['clientsTG']) {
			//подключаем
			mysqlQuery("UPDATE `clients` SET `clientsTG`= '" . $_JSON['message']['chat']['id'] . "',"
					. " `clientsTGset` = NOW() WHERE `idclients` = " . $clientREG['idclients']);

			$greetingmessage = mfa(mysqlQuery("SELECT * FROM `infinitimedbotTemplates` WHERE `idinfinitimedbotTemplates` = (SELECT MAX(`idinfinitimedbotTemplates`) FROM `infinitimedbotTemplates` WHERE `infinitimedbotTemplatesType` = 1)"))['infinitimedbotTemplatesText'];

			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => replaceInTemplate($greetingmessage, $clientREG), 'idclients' => ($clientREG['idclients'] ?? null)
			]);
//			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' =>
//				'В многопрофильном медицинском центре «Инфинити» всегда отличное настроение! Сегодня мы хотим поделиться им с Вами!
//Каждую субботу и воскресенье в клинике проходит акция «День стоматолога»! В подарок своим пациентам мы дарим:
//💥 Компьютерную томографию
//💥 ультразвуковую чистку зубов
//💥 фторирование
//
//👉 Количество мест ограничено!
//*акция действует при предъявлении пенсионного удостоверения.'
//			]);
		} else {
			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $clientREG['clientsFName'] . ' ' . $clientREG['clientsMName'] . ', с возвращением!', 'idclients' => ($clientREG['idclients'] ?? null)]);
		}
		mysqlQuery("UPDATE `clients` SET `clientscQR`=NULL, `clientscQRset`=NULL WHERE `idclients` = " . $clientREG['idclients']);
		exit();
	}
	if (trim($_JSON['message']['text'] ?? '') && $client) {

		mysqlQuery("INSERT INTO `infinitimedbotMessages` SET"
				. " `infinitimedbotMessagesMessage` = '" . mres($_JSON['message']['text']) . "',"
				. " `infinitimedbotMessagesClient` = " . sqlVON($client['idclients'] ?? null) . ","
				. " `infinitimedbotMessagesType` = 'I',"
				. " `infinitimedbotMessagesChatid` = " . $_JSON['message']['chat']['id'] . " ");
		infinitimedbot('sendMessage', ['chat_id' => '-641358100', 'text' => $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'] . "\n" . "https://menua.pro/pages/offlinecall/schedule.php?client=" . $client['idclients'] . "\nПишет:\n<i>" . htmlentities($_JSON['message']['text']) . "</i>\n\n<b>Ответить:</b>\nhttps://menua.pro/pages/telegram/chat.php"]);

		if (date("H") > 19 || date("H") < 10) {
			infinitimedbot('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $client['clientsFName'] . ' ' . $client['clientsMName'] . ", отдел сервиса работает с 10:00 до 20:00 ежедневно. Мы передали Ваше сообщение специалистам, и поставили в первую очередь. Утром с вами свяжутся! Благодарим за обращение!"]);
		}
	}
}