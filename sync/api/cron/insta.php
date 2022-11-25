<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
//
include $_ROOTPATH . '/sync/includes/setupLight.php';
$accounts = query2array(mysqlQuery("SELECT * FROM `instagramAccounts` WHERE NOT isnull(`instagramAccountsToken`)"));

foreach ($accounts as $account) {


	$url = "https://graph.instagram.com/me/media?fields=permalink,media_url,caption&access_token=" . $account['instagramAccountsToken'];
	$instagramCnct = curl_init(); // инициализация cURL подключения
	curl_setopt($instagramCnct, CURLOPT_URL, $url); // адрес запроса
	curl_setopt($instagramCnct, CURLOPT_RETURNTRANSFER, 1); // просим вернуть результат
	$data = json_decode(curl_exec($instagramCnct), true); // получаем и декодируем данные из JSON

	curl_close($instagramCnct); // закрываем соединение
	//
//	$followers = $data['graphql']['user']['edge_followed_by']['count'];
//	mysqlQuery("INSERT INTO `instagramFollowers` SET "
//			. "`instagramFollowersAccount` = '" . $account['idinstagramAccounts'] . "',"
//			. "`instagramFollowersQty` = '" . $followers . "'");

	$instagramPostsPermaLink = mfa(mysqlQuery("SELECT * FROM `instagramPosts` WHERE `instagramPostsAccount` = '" . $account['idinstagramAccounts'] . "' ORDER BY `idinstagramPosts` DESC LIMIT 1"))['instagramPostsPermaLink'];
//	printr($data['data']);
	$permalink = $data['data'][0]['permalink'] ?? '';
	$caption = $data['data'][0]['caption'] ?? '';
	$media_url = $data['data'][0]['media_url'] ?? '';

	if ($instagramPostsPermaLink !== $permalink && $permalink) {
		mysqlQuery("INSERT INTO `instagramPosts` SET `instagramPostsAccount` = '" . $account['idinstagramAccounts'] . "', `instagramPostsPermaLink` = '" . $permalink . "'");

		$users = getUsersByRights([68]);


		if (date("H") >= 10 && date("H") <= 21) {
			foreach ($users as $user) {

				ICQMSDelay(0, $user['usersICQ'], $user['usersFirstName'] . '! Только что новый пост выложил ' . $account['instagramAccountsTitle'] . '! Поставьте лайк, если не сложно! Это очень поможет в продвижении. ');
//			ICQMSDelay(0, $user['usersICQ'], );

				$text = "\r\n" . substr($caption, 0, 200) . "...\r\n" . "\r\n" . $permalink;

				usleep(1000);
				ICQMSDelay(2000, $user['usersICQ'], $text);
			}
		}
	}
}