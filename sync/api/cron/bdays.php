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
$users = query2array(mysqlQuery("SELECT * FROM `users` WHERE isnull(`usersDeleted`)"));
$unknown = [];
$bdays = [];

foreach ($users as $user) {
	if (!$user['usersBday']) {
		$unknown[] = '🤷‍♀️ ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ' ' . $user['usersMiddleName'];
	} else {
		if (date("m.d", strtotime($user['usersBday'])) == date("m.d", strtotime("tomorrow"))) {
			$bdays[] = rt(['🍩', '🍿', '🍫', '🍪', '🥂', '🎂', '🍰', '🧁', '🍾']) . ' ' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ' ' . $user['usersMiddleName'];
		}
	}
}
foreach (getUsersByRights([69]) as $recipient) {
	$text = '';
	if (count($bdays)) {
		$text .= rt([
			'🎉 Завтра день рождения у',
			'🎉 ' . $recipient['usersFirstName'] . ', напоминаю, что завтра надо поздравить с днём рождения',
			'🎉 ' . $recipient['usersFirstName'] . ', Вы просили  напомнить, что завтра надо поздравить с днём рождения',
			'🎉 завтра днюха у',
		]);
		$text .= count($bdays) > 1 ? ' следующих сотрудников:' : ':';
		$text .= "\r\n";
		$text .= implode("\r\n", $bdays);
	} else {
		$text .= rt([
			'Завтра нет дней рождения.',
			'Завтра никого поздравлять не нужно.',
			'Если торт и остался, то его можно съесть самим. Дней рождения завтра нет.',
			'Я бы напомнила про дни рождения, но их завтра нет.',
			'Я помню, что надо посмотреть днюхи, но на завтра ничего не нашла.',
			'16 часов, я помню. Но никого поздравлять не надо.',
			'Завтра без дней рождений.',
		]);
	}

	if (0 && count($unknown)) {
		if (count($bdays)) {
			$text .= "\r\n";
			$text .= '🤦‍♀️ ';
			$text .= rt(
					[
						$recipient['usersFirstName'] . ', я так же хочу напомнить, что я не знаю, когда ' . (count($unknown) > 1 ? 'дни' : 'день') . ' рождения у',
						$recipient['usersFirstName'] . ', я так же хочу напомнить, что в базе данных нет информации, когда ' . (count($unknown) > 1 ? 'дни' : 'день') . ' рождения у',
						'Но я всё ещё не знаю, когда ' . (count($unknown) > 1 ? 'дни' : 'день') . ' рождения у',
					]
			);
		} else {
			$text .= '🤦‍♀️ ';
			$text .= rt([
				$recipient['usersFirstName'] . ', я всё ещё не знаю, когда ' . (count($unknown) > 1 ? 'дни' : 'день') . ' рождения у',
				$recipient['usersFirstName'] . ', если не сложно, напишите когда ' . (count($unknown) > 1 ? 'дни' : 'день') . ' рождения у',
				$recipient['usersFirstName'] . ', если не сложно, внесите в базу данных когда ' . (count($unknown) > 1 ? 'дни' : 'день') . ' рождения у',
			]);
			$text .= rt(['']);
		}

		$text .= count($unknown) > 1 ? ' следующих сотрудников:' : ':';
		$text .= "\r\n";
		$text .= implode("\r\n", $unknown);
	}

	if ($recipient['usersTG'] ?? false) {
		sendTelegram('sendMessage', ['chat_id' => $recipient['usersTG'], 'text' => $text]);
	}
}

//printr($text);
//printr($bdays);
//printr(date("Y-m-d H:i:s", ));
