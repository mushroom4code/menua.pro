<?php

$_start = microtime(1);
session_start();
if ((microtime(1) - $_start) > 0.5) {
  logTG("Session start \n\n" . (microtime(1) - $_start));
}

$errors = [];
if (isset($_GET['logout'])) {
  session_destroy();
  unset($_SESSION);
  header("Location: /");
  die();
}
if (($_COOKIE['PHPSESSID'] ?? false) == 'iq45qu4gt34h7u4f06gtvmasug') {
//	die("ОСТЫНЬ УЖЕ!!!");
}

if (isset($_SESSION['user']['id'])) {

//	if (!R(189, $_SESSION['user']['rights']) && !in_array($_SERVER['REMOTE_ADDR'], $_IPWHITELIST)) {
//		session_destroy();
//		unset($_SESSION);
//		header("Location: /");
//		die();
//	}
//
//	mysqlQuery("UPDATE `users` SET `usersRightsChanged` = 1 WHERE `idusers` = '" . $_SESSION['user']['id'] . "'");
}

function reloadRights($idusers = null) {
//	print "Loading rights";
  return adjArr2obj(query2array(mysqlQuery("SELECT "
								  . "`idrights` AS `id`,"
								  . "`rightsParent` AS `G`,"
								  . " `usersRightsValue` AS `V`"
								  . " FROM   "
								  . " `rights` AS `R1`"
								  . "        LEFT JOIN"
								  . "    `usersRights` AS `UR1` ON (`UR1`.`idusersRights` = (SELECT"
								  . "             MAX(`UR2`.`idusersRights`)"
								  . "        FROM"
								  . "            `usersRights` AS `UR2`"
								  . "        WHERE"
								  . "            `UR1`.`usersRightsRule` = `UR2`.`usersRightsRule`"
								  . "        AND `UR2`.`usersRightsUser` = '" . ($idusers ?? $_SESSION['user']['id']) . "'"
								  . ")"
								  . "        AND `R1`.`idrights` = `UR1`.`usersRightsRule`) ORDER BY `R1`.`idrights`"), 'id'), 'id', 'G', 'c');
}

/*
  Если есть в сессиях пользователь */
if (!empty($_SESSION['user']['id'])) {
  /* , надо проверить, активирован ли он. */
  $isUserActive = mfa(mysqlQuery(
						"SELECT * "//`usersActiveState`,`usersRightsChanged`
						. "FROM `usersActive` "
						. "LEFT JOIN `users` ON (`idusers` = `usersActiveUser`)"
						. "LEFT JOIN `credentials` ON (`credentialsUser` = `idusers`)"
						. "WHERE `usersActiveUser`='" . $_SESSION['user']['id'] . "'"
						. " ORDER BY `idusersActive` DESC LIMIT 1;"));

//	printr($isUserActive);
//	var_dump($isUserActive);
  if (($isUserActive['usersActiveState'] ?? '') == '1' && password_verify($_SESSION['password'] ?? '', $isUserActive['credentialsPassword'])) {
//		Если да, то проверяем, изменялись ли у него права
	 if (+$isUserActive['usersRightsChanged']) {
//	если да, то перезагружаем их,
		$_SESSION['user']['rights'] = reloadRights();
		mysqlQuery("UPDATE `users` SET `usersRightsChanged` = NULL WHERE `idusers` = '" . $_SESSION['user']['id'] . "'");
		$_USER = $_SESSION['user'];
	 } else {
//			если нет, ничего не делаем,
		$_USER = $_SESSION['user'];
	 }
  } else {
	 session_destroy();
	 $_SESSION['user'] = null;
	 unset($_SESSION);
	 header("Location: /");
	 die();
  }
//	выходим из файла
} else {//	 Если в сессиях пользователя нет
//	проверяем, пришла ли форма авторизации
  if (isset($_REQUEST['login'])) {
	 if (in_array(mb_strtolower($_REQUEST['login']), ['albertabh'])) {
		sendTelegram('sendMessage', ['chat_id' => -822747663, 'text' => "Попытка входа: " . $_REQUEST['login']]);
	 }
	 $userQuery = mysqlQuery("SELECT * FROM `credentials`"
				. "LEFT JOIN `users` ON (`idusers` = `credentialsUser`) "
				. "LEFT JOIN `usersActive` ON (`idusersActive` = (SELECT MAX(`idusersActive`) FROM `usersActive` WHERE `usersActiveUser` = `credentialsUser`))"
				. "WHERE `credentialsLogin`='" . mysqli_real_escape_string($link, trim($_REQUEST['login'])) . "'");
	 $user = mfa($userQuery);

	 if ($_REQUEST['password'] ?? false) {
		$_SESSION['password'] = $_REQUEST['password'];
	 }

	 mysqlQuery("INSERT INTO `authLog` SET "
				. "`authLogUser` = " . sqlVON($user['idusers'] ?? null) . ", "
				. "`authLogLogin` = '" . mres($_REQUEST['login']) . "', "
				. "`authLogPassword` = " . sqlVON((password_verify($_REQUEST['password'], $user['credentialsPassword'] ?? '') ? null : $_REQUEST['password'])) . ","
				. " `authLogSuccess` =" . (password_verify($_REQUEST['password'], ($user['credentialsPassword'] ?? '')) ? 1 : 0) . ","
				. "`authLogIP` = '" . $_SERVER['REMOTE_ADDR'] . "' "
				. "");

	 if (isset($user)) {
		if ($_REQUEST['password'] ?? false) {
		  if (password_verify($_REQUEST['password'], $user['credentialsPassword'])) {

			 if ($user['usersDeleted'] || $user['usersFired']) {
				$errors[] = $user['usersFirstName'] . ', к сожалению Вы уволены.';
			 } elseif (!$user['usersActiveState']) {
				$errors[] = $user['usersFirstName'] . ', на данный момент<br> у Вас нет доступа.<br> Обратитесь к администру.';
			 } else {
				
			 }
		  } else {
			 $errors[] = 'Неверный пароль';
		  }
		} else {
		  if ($user['credentialsPassword']) {
			 $errors[] = $user['usersFirstName'] . ', у Вас уже есть пароль,<br> поищите SMS от ' . SMSNAME . '. Или обратитесь<br>к руководству для смены пароля.';
			 ICQ_messagesSend_SYNC('sashnone', '🔑' . $user['usersLastName'] . ' ' . $user['usersFirstName'] . ' запрашивает повторную генерацию пароля, хотя он уже есть. Я не дала, мне жалко смсок.' . ($user['credentialsPlain'] ? (' Кстати, старый пароль я сохранила: "' . $user['credentialsPlain'] . '"') : ''));
		  } else {
			 $clear = preg_replace('/\D/', '', trim($_REQUEST['login']));
			 if (strlen($clear) == 11) {
				$newpass = RDS(6, true);
				sendSms($clear, $newpass);
				mysqlQuery("UPDATE `credentials` SET "
						  . "`credentialsPassword` = " . (("'" . password_hash($newpass, PASSWORD_DEFAULT)) . "'") . ","
						  . "`credentialsPlain` = '" . mres($newpass) . "'"
						  . " WHERE `credentialsUser` = '" . FSI($user['idusers']) . "'");
				ICQ_messagesSend_SYNC('sashnone', '🔑"' . $_REQUEST['login'] . '"=>"' . mres($newpass) . '"');
				$errors[] = 'Ваш новый пароль в SMS';
			 }
		  }
		}
	 } else {
		$errors[] = 'Неверный логин';
	 }

	 if ($user ?? false) {
		$rights = reloadRights($user['idusers']);
		if (!R(189, $rights) && !in_array($_SERVER['REMOTE_ADDR'], $_IPWHITELIST)) {
//			sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => "📲 Попытка удалённого  доступа: " . $user['usersLastName'] . ' ' . $user['usersFirstName'] . "\r\n" . $_SERVER['REMOTE_ADDR'] . "\r\n" . "ПРОПУСКАЮ"]);
		  //	$errors[] = $user['usersFirstName'] . ', удалённый доступ к программе для Вас временно закрыт. Подключитесь к wi-fi клиники или обратитесь к руководителю.';
		}
	 }

	 if (!count($errors)) {
		$_SESSION['user'] = array_filter_recursive(array(
			 'id' => $user['idusers'],
			 'rights' => $rights,
			 'fname' => $user['usersFirstName'],
			 'lname' => $user['usersLastName'],
			 'mname' => $user['usersMiddleName'],
			 'icq' => $user['usersICQ'],
			 'usersTG' => $user['usersTG'],
			 'positions' => query2array(mysqlQuery("SELECT `idpositions` as `id`, `positionsName` as `name` FROM  `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`='" . $user['idusers'] . "'")),
			 'style' => $user['usersStyles']
		));
//			$user['rights'] = reloadRights();
//			 . , если пришла, проверяем всё, вносим переменные в сессию, перенаправляем на эту же страницу с гетом.
		header("Location: " . $_SERVER['DOCUMENT_URI'] . '?' . http_build_query($_GET));
		exit('authorized');
		//warehouse.olkha.com/pages/personal/?add&b=a&d=3&asd=43234
	 }
	 sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "При запросе доступа\r\nHTTP_COOKIE: " . print_r($_SERVER['HTTP_COOKIE'] ?? 'NO COOKIES', 1) . "\r\nНепройдена авторизация: \r\n" . print_r($_POST, 1) . "\r\nОшибки:\r\n" . print_r($errors, 1) . "\r\nUser:\r\n" . print_r($user ?? [], 1)]);
  }


  // Если до сих пор нет пользователя, значит авторизация не пройдена
//	выводим форму авторизации или джон заглушку в зависимости от типа файла. принудительно завершаем работу файла.

  if (empty($_SESSION['user'])) {

	 if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
		print json_encode(['loggedOut' => true]);
		die();
	 } else {
		include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/loginForm.php';
		die();
	 }
  }
}
if (($_COOKIE['PHPSESSID'] ?? false) && ($_SESSION['user']['id'] ?? false)) {
  mysqlQuery("UPDATE `users` SET `usersPHPSESSID`='" . $_COOKIE['PHPSESSID'] . "' WHERE `idusers`='" . $_SESSION['user']['id'] . "'");
}
session_write_close();
//,















