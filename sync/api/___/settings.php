<?

function infinitimedbot($method, $data, $headers = []) {
//	return false;
	$var_info = print_r($data, true);
//	if (strlen($var_info) > 10000 && ($handle = fopen('/var/www/html/public/pages/files/uploads/176/' . microtime(1) . '.log', 'a'))) {
//		fwrite($handle, $var_info);
//		fclose($handle);
//	}
	if ($method == 'sendMessage') {
		$GUID = exec('uuidgen -r');
		$textArray = str_split_unicode($data['text'], 4000);
		$n = 0;
		mysqlQuery("INSERT INTO `telegramTexts` SET"
				. " `telegramTextsGUID` = '" . $GUID . "',"
				. " `telegramTextsText` = '" . mres($data['text']) . "'"
		);
		foreach ($textArray as $msg) {
			$n++;
			$data['parse_mode'] = 'html';
			$data['text'] = (count($textArray) > 1 ? ("[" . $n . "/" . count($textArray) . "]\n") : '') . htmlentities($msg);
			mysqlQuery("INSERT INTO `telegramQuery` SET "
					. (($data['chat_id'] ?? false) ? ("`telegramQueryChatId`='" . mres($data['chat_id']) . "',") : "")
					. " `telegramQueryMethod` = '" . $method . "',"
					. " `telegramQueryMessageGUID` = '" . $GUID . "',"
					. " `telegramQueryData`='" . mres(json_encode($data, JSON_UNESCAPED_UNICODE)) . "'"
					. "");
		}

		return false;
	}

	if ($method == 'sendSticker') {
		mysqlQuery("INSERT INTO `telegramQuery` SET"
				. (($data['chat_id'] ?? false) ? ("`telegramQueryChatId`='" . mres($data['chat_id']) . "',") : "")
				. "`telegramQueryMethod` = '" . $method . "',"
				. "`telegramQueryData`='" . mres(json_encode($data)) . "'"
				. "");
		return false;
	}




//	 ['text' => $text . "\r\nUSER: " . $_USER['idusers'], 'chat_id' => '-597902452']
	$curl = curl_init('https://api.telegram.org/bot' . TGKEY . '/' . $method);
	curl_setopt_array($curl, [
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_POSTFIELDS => json_encode($data),
		CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $headers)
	]);
	$result = curl_exec($curl);
	curl_close($curl);
	return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}