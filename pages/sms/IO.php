<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
$username = '072c6477c5';
$password = '0906751026';
$URL = 'https://target.tele2.ru/api/v2/send_message/message-id-' . ($_GET['smsHash']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($username . ":" . $password), 'Content-Type:application/json']);

$result = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
curl_close($ch);
//	printr($status_code);
//	printr($result);

$resultARR = explode("\r\n", $result);
$resultJSON = $resultARR[count($resultARR) - 1];
$resultOBJ = json_decode($resultJSON, 1);
if ($resultOBJ['result']['status'] ?? false) {
	mysqlQuery("UPDATE `sms` SET `smsState` = '" . $resultOBJ['result']['status'] . "' WHERE `smsHash` = '" . $_GET['smsHash'] . "'");
}
print json_encode(['status' => ($resultOBJ['result']['status'] ?? false)], 288);
