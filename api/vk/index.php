<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

if ($_JSON['object']['message'] ?? false) {
	$get_params = http_build_query(['message_ids' => $_JSON['object']['message']['id'],
		'v' => '5.131',
		'access_token' => '869bcdf20ad6789f2df93c4e4d482a51351a8feae4d62d4f1477d77d31b0799653431007f0e5ae3b031dd']);
	$trys = 0;
	do {
		$trys++;
		usleep(1000000);
		$result = json_decode(file_get_contents('https://api.vk.com/method/messages.getById?' . $get_params), 1);
	} while ($trys < 5 && !($result['response']['items'][0]['attachments'][0]['audio_message']['transcript'] ?? false));

	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => 'С ' . $trys . 'й попытки мы получили текст: ' . ($result['response']['items'][0]['attachments'][0]['audio_message']['transcript'] ?? 'Да нихерашечки мы не получили :(')]);
}
?>ok