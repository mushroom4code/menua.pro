<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
include 'functions.php';
header("Content-type: application/json; charset=utf8");
//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => "ЭВОТОР\n" . json_encode($_SERVER, 288 + 128)]);
if (getBearerToken() !== 'AIuZLsEgEShFbCNuwzko') {
	header("HTTP/1.1 401 Unauthorized");
	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => "Unauthorized"]);
	die();
}
if ($_JSON['data']['totalAmount'] ?? false) {
	if (($_JSON['data']['paymentSource'] ?? '') == "PAY_CASH") {
		$method = "💵";
	}
	if (($_JSON['data']['paymentSource'] ?? '') == "PAY_CARD") {
		$method = "💳";
	}
	if (($_JSON['data']['deviceId'] ?? '') == "20181116-6CA6-4008-80F4-1A21D5726CE0") {
		$entity = "♾";
	}
	if (($_JSON['data']['deviceId'] ?? '') == "20191206-5345-4077-80F6-01F288A97817") {
		$entity = "🦷";
	}
	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => ($entity ?? '??') . ($method ?? '??') . ' ' . $_JSON['data']['totalAmount'] . "р."]);
} else {
	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => 'Отсутствует totalAmount в запросе ' . ($entity ?? '??') . ($method ?? '??') . ' ' . json_encode($_JSON, 288 + 128)]);
}


//sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => json_encode($_JSON, 288 + 128)]);
