<?php
define('ICQ_API_ACCESS_TOKEN', '001.1406025859.1903671726:751326972');
define('ICQ_BOT_ID', '751326972'); //Используемая версия API
define('ICQ_NICK', 'Infinity_clinic_bot'); //Используемая версия API
define('ICQ_API_ENDPOINT', "https://api.icq.net/bot/v1/messages/sendText/");
include '/var/www/html/public/includes/setupLight.php';

//Функция для вызова произвольного метода API
function ICQ_Api_call($params = array()) {
	$params['token'] = ICQ_API_ACCESS_TOKEN;
	$url = ICQ_API_ENDPOINT . '?' . http_build_query($params);
//	print_r($url);
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$json = curl_exec($curl);
	curl_close($curl);
	//var_dump($json);
	$response = json_decode($json, true);
	//print_r($response);
	return $response;
}

//Функция для вызова messages.send
function ICQ_messagesSend($peer_id, $message) {
	return ICQ_Api_call(array(
		'chatId' => $peer_id,
		'text' => $message
	));
}


function getEventsRequest($params = array()) {
	$params['token'] = ICQ_API_ACCESS_TOKEN;
	$params['lastEventId'] = 7;
	$params['pollTime'] = 30;
	$url = "https://api.icq.net/bot/v1/events/get" . '?' . http_build_query($params);




	// build the individual requests, but do not execute them
	$ch_1 = curl_init($url);
	curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);


// build the multi-curl handle, adding both $ch
	$mh = curl_multi_init();
	curl_multi_add_handle($mh, $ch_1);


// execute all queries simultaneously, and continue when all are complete
	$running = null;
	do {
		curl_multi_exec($mh, $running);
	} while ($running);

//close the handles
	curl_multi_remove_handle($mh, $ch1);

	curl_multi_close($mh);

// all of our requests are done, we can now access the results
	$response_1 = curl_multi_getcontent($ch_1);

	return (json_decode($response_1, true)); // output results
}

function ICQ_getEvents() {
	return (getEventsRequest());
}

$otvet = ICQ_getEvents();
printr($otvet);
?><hr><?
var_dump(ICQ_messagesSend('sashnone', 'Сам ' . $otvet['events'][0]['payload']['text']));
