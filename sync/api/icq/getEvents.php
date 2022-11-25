<?php

if (isset($argv)) {
	print_r($argv);
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include ($_ROOTPATH . "/sync/includes/setupLight.php");
ignore_user_abort(true);
set_time_limit(0);

$params['lastEventId'] = ($_GET['eventId'] ?? 0);
$params['pollTime'] = 30;



$params['token'] = ICQAPIKEY;
$url = "https://api.icq.net/bot/v1/events/get" . '?' . http_build_query($params);
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$json = curl_exec($curl);
//printr($json);
curl_close($curl);
$response = json_decode($json, true);



printr($response);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

