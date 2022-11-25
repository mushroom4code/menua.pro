<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setup.php';

//{"phones":{"8090":"89052084769"},"date":"2020-12-16","client":"112","phone":"8090"}
//$_JSON['phone']
//$_JSON['text']
//usleep(600000);
$clientPhone = mfa(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `idclientsPhones`='" . mysqli_real_escape_string($link, $_JSON['phone']) . "'"));
$out['success'] = false;
$out['idmsg'] = '';

if (($clientPhone['clientsPhonesPhone'] ?? false)) {

	$username = SMSLOGIN;
	$password = SMSPASSWORD;
	$URL = 'https://target.tele2.ru/api/v2/send_message';
	$clientPhone['clientsPhonesPhone'][0] = 7;
	$data = [
		"msisdn" => $clientPhone['clientsPhonesPhone'],
		"shortcode" => SMSNAME, 
		"text" => $_JSON['text']
	];

	$data_string = json_encode($data);


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($username . ":" . $password), 'Content-Type:application/json']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	$result = curl_exec($ch);
	$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
	curl_close($ch);
	$resultARR = explode("\r\n", $result);
	$resultJSON = $resultARR[count($resultARR) - 1];
	$resultOBJ = json_decode($resultJSON, 1);
	$out['success'] = (($resultOBJ['status'] ?? '') === 'ok');
	$uid = preg_replace("/message-id-/", '', $resultOBJ['result']['uid']);
	$out['idmsg'] = $uid;
	if ($out['success'] ?? false) {
		mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesSmsTotal` = `clientsPhonesSmsTotal`+1 WHERE `idclientsPhones` = '" . $clientPhone['idclientsPhones'] . "'");
		mysqlQuery("INSERT INTO `sms` SET "
				. "`smsHash` = '" . $uid . "', "
				. "`smsUser` = '" . $_USER['id'] . "', "
				. "`smsClient` = '" . $clientPhone['clientsPhonesClient'] . "', "
				. "`smsText` = '" . mysqli_real_escape_string($link, $data['text']) . "', "
				. "`smsPhone` = '" . $clientPhone['idclientsPhones'] . "'");
	}
}
print json_encode($out, 288);
