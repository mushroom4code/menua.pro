<?php

//array(3) { ["fileId"]=> string(33) "I0005XD9wnFDtGmxKdNrES5e7dcd221bd" ["msgId"]=> string(19) "6808823761861607700" ["ok"]=> bool(true) } 
//array(3) { ["fileId"]=> string(33) "I0005hhnT6h3HZjerVxCh45e7dcd491bd" ["msgId"]=> string(19) "6808823929365331971" ["ok"]=> bool(true) } 
//array(3) { ["fileId"]=> string(33) "I0004UnGgU0Ryf9REWa7NP5e7dcd661bd" ["msgId"]=> string(19) "6808824049624417072" ["ok"]=> bool(true) } 

function strigToBinary($string) {



	$len = strlen($string);
	$str = $string;

	$out_str = "";
	for ($i = 0; $i < $len; $i++) {
		$out_str .= pack("c", ord(substr($str, $i, 1)));
	}
	return $out_str;
}

define('ICQ_API_ACCESS_TOKEN', '001.1406025859.1903671726:751326972');
define('ICQ_BOT_ID', '751326972'); //Используемая версия API
define('ICQ_NICK', 'Infinity_clinic_bot'); //Используемая версия API

//Функция для вызова произвольного метода API
function ICQ_Api_call($params = array()) {
	$params['token'] = ICQ_API_ACCESS_TOKEN;
	$url = "https://api.icq.net/bot/v1/messages/sendVoice";
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
	curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
	$json = curl_exec($curl);
	curl_close($curl);
	$response = json_decode($json, true);
	return $response;
}

//Функция для вызова messages.send
function ICQ_audiomessagesSend($peer_id, $message) {
	return ICQ_Api_call(array(
		'chatId' => $peer_id,
		'file' => $message
	));
}

if (!empty($_FILES)) {
	print_r($_FILES);
	$filecontents = file_get_contents($_FILES['audio']['tmp_name']);
	$packed = strigToBinary($filecontents);
	print '<hr>';
	var_dump(ICQ_audiomessagesSend('sashnone', $packed));
	print '<hr>';
}
?>



<form action="audiofile.php" method="post" enctype="multipart/form-data">
	<input type="file" name="audio">
	<input type="submit">
</form>