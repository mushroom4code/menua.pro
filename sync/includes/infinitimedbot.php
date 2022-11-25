<?php

function infinitimedbot($method, $data, $headers = []) {
	global $_USER;
//	$var_info = print_r($data, true);
//	https://api.telegram.org/bot5249650365:AAGOq5FX0cIrSZ3kYbd0bb3lK1qEWkXVknE/setWebhook?url=https://menua.pro/api/infinitimedbot/index.php
	if ($method == 'sendMessage') {
		$GUID = GUID();
		$textArray = str_split_unicode($data['text'], 4000);
		$n = 0;
		mysqlQuery("INSERT INTO `infinitimedbotTexts` SET"
				. " `infinitimedbotTextsGUID` = '" . $GUID . "',"
				. " `infinitimedbotTextsText` = '" . mres($data['text']) . "'"
		);
		mysqlQuery("INSERT INTO `infinitimedbotMessages` SET"
				. " `infinitimedbotMessagesMessage` = '" . mres($data['text']) . "',"
				. " `infinitimedbotMessagesClient` = " . sqlVON($data['idclients'] ?? null) . ","
				. " `infinitimedbotMessagesType` = 'O',"
				. " `infinitimedbotMessagesReaded` = NOW(),"
				. " `infinitimedbotMessagesUser` = " . sqlVON($_USER['id'] ?? null) . ","
				. " `infinitimedbotMessagesChatid` = " . $data['chat_id'] . " ");

		foreach ($textArray as $msg) {
			$n++;
			$data['parse_mode'] = 'html';
			$data['text'] = (count($textArray) > 1 ? ("[" . $n . "/" . count($textArray) . "]\n") : '') . ($msg);
			mysqlQuery("INSERT INTO `infinitimedbotQueue` SET "
					. (($data['chat_id'] ?? false) ? ("`infinitimedbotQueueChatId`='" . mres($data['chat_id']) . "',") : "")
					. " `infinitimedbotQueueMethod` = '" . $method . "',"
					. " `infinitimedbotQueueMessageGUID` = '" . $GUID . "',"
					. " `infinitimedbotQueueData`='" . mres(json_encode($data, JSON_UNESCAPED_UNICODE)) . "'"
					. "");
		}
		return false;
	}

	if ($method == 'sendSticker') {
		mysqlQuery("INSERT INTO `infinitimedbotQueue` SET"
				. (($data['chat_id'] ?? false) ? ("`infinitimedbotQueueChatId`='" . mres($data['chat_id']) . "',") : "")
				. "`infinitimedbotQueueMethod` = '" . $method . "',"
				. "`infinitimedbotQueueData`='" . mres(json_encode($data)) . "'"
				. "");
		return false;
	}

	if ($method == 'deleteMessage') {
		mysqlQuery("INSERT INTO `infinitimedbotQueue` SET"
				. (($data['chat_id'] ?? false) ? ("`infinitimedbotQueueChatId`='" . mres($data['chat_id']) . "',") : "")
				. "`infinitimedbotQueueMethod` = '" . $method . "',"
				. "`infinitimedbotQueueData`='" . mres(json_encode($data)) . "'"
				. "");
		return false;
	}
}
