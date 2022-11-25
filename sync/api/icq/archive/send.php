<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

define('ICQ_API_ACCESS_TOKEN', '001.1406025859.1903671726:751326972');
define('ICQ_BOT_ID', '751326972'); //Используемая версия API
define('ICQ_NICK', 'Infinity_clinic_bot'); //Используемая версия API



$curls = [];
$send_mh = curl_multi_init();
$started22 = false;

function ICQ_Api_call($params = array(), $endpoint = '/messages/sendText', $buttons = false) {
	$params['token'] = ICQ_API_ACCESS_TOKEN;
	global $curls, $send_mh, $started22;
	$url = "https://api.icq.net/bot/v1" . $endpoint . '?' . http_build_query($params);
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

//Функция для вызова messages.send
function ICQ_messagesSend($peer_id, $message, $buttons = false) {
	return ICQ_Api_call(
			['chatId' => $peer_id, 'text' => $message],
			'/messages/sendText',
			$buttons
	);
}

if (isset($_JSON['action']) && $_JSON['action'] = 'receptionCall') {


	$service = mfa(mysqlQuery("SELECT * "
					. " FROM `servicesApplied`"
					. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
					. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
					. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
					. " WHERE `idservicesApplied` = '" . FSI($_JSON['service']) . "'"));
	printr($service);



	$text = $service['usersFirstName'] . ($service['usersMiddleName'] ? (' ' . $service['usersMiddleName']) : '') . ', ';
	if (time() > strtotime($service['servicesAppliedTimeBegin'])) {
		$text .= rt(['СРОЧНО подойдите к регистратуре!', 'как можно скорее подойдите к регистратуре!', 'в кротчайшее время подойдите к регистратуре!']);
	} else {
		$text .= rt(['не могли бы Вы подойти к регистратуре.', 'Вас просят подойти к регистратуре.', 'подойдите пожалуйста к регистратуре.']);
	}

	$text .= ' ' . rt(['Вас ожидает']);
	$text .= ' ' . $service['clientsLName'] . ($service['clientsFName'] ? (' ' . $service['clientsFName']) : '') . ($service['clientsMName'] ? (' ' . $service['clientsMName']) : '');
	$text .= ' ' . rt(['для прохождения процедуры', ' на процедуру']);
	$text .= ' "' . $service['servicesName'] . '"';

	if ($service['usersICQ']) {
		ICQ_messagesSend($service['usersICQ'], $text);
	}
}



