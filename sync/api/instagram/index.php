<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';


//$url = "https://graph.instagram.com/me/media?fields=permalink,media_url,caption&access_token=" . $account['instagramAccountsToken'];
$url = "https://api.instagram.com/v1/users/3955450?access_token=" . $account['instagramAccountsToken'];
$instagramCnct = curl_init(); // инициализация cURL подключения
curl_setopt($instagramCnct, CURLOPT_URL, $url); // адрес запроса
curl_setopt($instagramCnct, CURLOPT_RETURNTRANSFER, 1); // просим вернуть результат
$data = json_decode(curl_exec($instagramCnct), true); // получаем и декодируем данные из JSON

curl_close($instagramCnct); // закрываем соединение
//


printr($media);
