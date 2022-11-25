<?php

if ($_JSON['smsTemplate'] ?? false) {
	$smsTemplatesText = mfa(mysqlQuery("SELECT * FROM `smsTemplates` WHERE isnull(`smsTemplatesGroup`)"))['smsTemplatesText'] ?? null;
}



$TS = strtotime($SA['time']);

$username = SMSLOGIN;
$password = SMSPASSWORD;
$URL = 'https://target.tele2.ru/api/v2/send_message';
$smsdata = [
	'dateone' => date("d.m", $TS),
	'timeone' => date("H:i", $TS),
];
$data = [
	"msisdn" => $clientPhone['clientsPhonesPhone'],
	"shortcode" => SMSNAME,
	"text" => smsTemplate($smsTemplatesText, $smsdata)
];

$data_string = json_encode($data);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout after 30 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($username . ":" . $password), 'Content-Type:application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

$result = curl_exec($ch);
//					sendTelegram('sendMessage', ['chat_id' => -522070992, 'text' => json_encode($result)]); 
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
//				printr($result);
curl_close($ch);
$resultARR = explode("\r\n", $result);
$resultJSON = $resultARR[count($resultARR) - 1];

$resultOBJ = json_decode($resultJSON, 1);
$success = (($resultOBJ['status'] ?? '') === 'ok');
//					ICQ_messagesSend_SYNC('sashnone', json_encode($resultOBJ, 288));
mysqlQuery("INSERT INTO `sms` SET "
		. "`smsUser` = '" . $_USER['id'] . "', "
		. "`smsClient` = '" . $clientPhone['clientsPhonesClient'] . "', "
		. "`smsText` = '" . mysqli_real_escape_string($link, $data['text']) . "', "
		. "`smsPhone` = '" . $clientPhone['idclientsPhones'] . "'");
$idsms = mysqli_insert_id($link);
if ($success) {
	$uid = preg_replace("/message-id-/", '', $resultOBJ['result']['uid']);
	mysqlQuery("UPDATE `sms` SET "
			. "`smsHash` = '" . $uid . "' "
			. " WHERE `idsms` = '" . $idsms . "'");
	mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesSmsTotal` = `clientsPhonesSmsTotal`+1 WHERE `idclientsPhones` = '" . $clientPhone['idclientsPhones'] . "'");

//	print json_encode(['success' => $success, 'uid' => $uid], 288);
//						sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => json_encode($resultOBJ, 288)]);
} else {
	sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => '$request: ' . json_encode(json_decode($data_string, 1), 288 + 128) . "\n\n" . '$result: ' . $result]);
	sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => '$resultOBJ: ' . json_encode($resultOBJ, 288)]);

//	print json_encode(['success' => false, 'msgs' => ['SMS не ушло, возникла ошибка на стороне провайдера.']], 288);
}
