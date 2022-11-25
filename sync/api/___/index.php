<?php

//print date("Y-m-d H:i:s");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

//user validation
if ($_JSON['message']['from']['id'] ?? false) {
	$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersTG`='" . mres($_JSON['message']['from']['id']) . "'"));
}
mysqlQuery("INSERT INTO `TGmessages` SET"
		. " `TGmessagesMessage` = '" . mres(json_encode($_JSON, 288 + 128)) . "',"
		. " `TGmessagesUser` = " . ($user['idusers'] ?? 'null') . " ");

if (!($_JSON['message']['chat']['id'] ?? false)) {
	die();
}

if (trim($_JSON['message']['text'] ?? '') == 'chatid') {
	sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $_JSON['message']['chat']['id']]);
}

if (($_JSON['message']['chat']['type'] ?? false) == 'private') {//Общение через личку
	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => json_encode($_JSON, 288 + 128)]);

	$text = trim($_JSON['message']['text'] ?? '');
	preg_match('/\/start (\w+)/', $text, $matches);
	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => json_encode($matches, 288 + 128)]);

	if (($matches[0] ?? false) && strlen($matches[1] ?? '') == 5) {
		$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE BINARY `clientscQR`='" . mres($matches[1]) . "'"));
		if (!$client) {
			sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Недействительный код']);
			die();
		}
		if (!$client['clientsTG']) {
			//подключаем
			mysqlQuery("UPDATE `clients` SET `clientsTG`= '" . $_JSON['message']['chat']['id'] . "' WHERE `idclients` = " . $client['idclients']);
			sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $client['clientsFName'] . ' ' . $client['clientsMName'] . ', здравствуйте! Рады видеть Вас в Вашем личном кабинете. Здесь вы можете ознакомиться со своими остатками по абонементам, найти интересные предложения и скидки на актуальные для Вас услуги.']);
		} else {
			sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => $client['clientsFName'] . ' ' . $client['clientsMName'] . ', с возвращением!']);
		}
		mysqlQuery("UPDATE `clients` SET `clientscQR`=NULL, `clientscQRset`=NULL WHERE `idclients` = " . $client['idclients']);
	}
	if (($matches[0] ?? false) && ($matches[1] ?? false) == 'giftcert20211223') {
		sendTelegram('sendPhoto', [
			'chat_id' => $_JSON['message']['chat']['id'],
			'photo' => 'AgACAgQAAxkBAAEChg5hxDrKUYOPjG3ZBAgcfNfc_P0NWgAC36wxGwjSLVI5XPaGQqONHQEAAwIAA20AAyME',
			'caption' => 'Покажите данный сертификат на регистратуре медицинского центра «Инфинити» для проведения подарочной чистки зубов и фторирования🌸']);
		telegramSendByRights(['186'], "🎁 Запрошен подарочный сертификат. " . ($_JSON['message']['from']['first_name'] ?? '') . " " . ($_JSON['message']['from']['last_name'] ?? '') . " (" . ($_JSON['message']['from']['username'] ?? 'ник скрыт') . ")");
		exit();
	}
	if (!$user) {
		if (trim($_JSON['message']['text'] ?? '') !== '') {
			if (($matches[0] ?? false) && strlen($matches[1] ?? '') == 24) {
				$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersPHPSESSID`='" . mres($matches[1]) . "'"));
			}
			if ($user ?? false) {
				if (mysqlQuery("UPDATE `users` SET `usersTG` = '" . mres($_JSON['message']['chat']['id']) . "' WHERE `idusers`='" . $user['idusers'] . "'")) {
					sendTelegram('sendSticker', ['chat_id' => $_JSON['message']['from']['id'], 'sticker' => 'CAACAgIAAxkBAAMFYI_S0LndE9RlJQ3roFS6zmst31YAAj8AA0QNzxfQ0mUJZ61_Eh8E']);
					sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => $user['usersLastName'] . ' ' . $user['usersFirstName'] . ' подключил(а) телеграм.']);
					die();
				} else {
					sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Возникли какие-то проблемы при записи в базу данных :-( Давай потом...']);
					die();
				}
			} else {
				sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Мы не знакомы. Подключение возможно из меню программы. Пункт "Подключить", рядом с кнопкой "Выход". Седуйте инструкции.']);
				die();
			}
		} else {
			if (($_JSON['message']['sticker']['file_unique_id'] ?? false)) {
				sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Я люблю стикеры, но сначала надо познакомиться. Введите личный код.']);
			} else {
				sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['chat']['id'], 'text' => 'Чего молчим?']);
			}

			die();
		}
		die();
	}

	if ($_JSON['message']['sticker']['file_unique_id'] ?? false) {//Автосохранение стикеров
		if (mfa(mysqlQuery("SELECT * FROM `TGstickers` WHERE `TGstickersUnique`='" . mres($_JSON['message']['sticker']['file_unique_id']) . "' "))) {
			sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['from']['id'], 'text' => 'У меня уже есть этот стикер! :)']);
		} else {
			mysqlQuery("INSERT INTO `TGstickers` SET "
					. "`TGstickersFile`='" . mres($_JSON['message']['sticker']['file_id']) . "',"
					. "`TGstickersUnique`='" . mres($_JSON['message']['sticker']['file_unique_id']) . "',"
					. "`TGstickersAddedBy` = " . ($user['idusers'] ?? 'null') . ""
					. ";");
			sendTelegram('sendMessage', ['chat_id' => $_JSON['message']['from']['id'], 'text' => 'Спасибо! Добавила к себе!! 😘']);
		}
	}
}



	/*
	  {
	  "update_id": 20649892,
	  "message": {
	  "message_id": 5,
	  "from": {
	  "id": 325908361,
	  "is_bot": false,
	  "first_name": "Alexander",
	  "username": "sashnone",
	  "language_code": "ru"
	  },
	  "chat": {
	  "id": 325908361,
	  "first_name": "Alexander",
	  "username": "sashnone",
	  "type": "private"
	  },
	  "date": 1620038352,
	  "sticker": {
	  "width": 512,
	  "height": 512,
	  "emoji": "🥳",
	  "set_name": "DoggyShark",
	  "is_animated": true,
	  "thumb": {
	  "file_id": "AAMCAgADGQEAAwVgj9LQud0T1GUlDeugVLrOay3fVgACPwADRA3PF9DSZQlnrX8SrFHeDwAEAQAHbQAD2wUAAh8E",
	  "file_unique_id": "AQADrFHeDwAE2wUAAg",
	  "file_size": 6602,
	  "width": 128,
	  "height": 128
	  },
	  "file_id": "CAACAgIAAxkBAAMFYI_S0LndE9RlJQ3roFS6zmst31YAAj8AA0QNzxfQ0mUJZ61_Eh8E",
	  "file_unique_id": "AgADPwADRA3PFw",
	  "file_size": 13745
	  }
	  }
	  }
	  {
	  "update_id": 20649891,
	  "message": {
	  "message_id": 4,
	  "from": {
	  "id": 325908361,
	  "is_bot": false,
	  "first_name": "Alexander",
	  "username": "sashnone",
	  "language_code": "ru"
	  },
	  "chat": {
	  "id": 325908361,
	  "first_name": "Alexander",
	  "username": "sashnone",
	  "type": "private"
	  },
	  "date": 1620037591,
	  "text": "Hi"
	  }
	  }
	 */	