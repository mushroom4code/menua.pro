<?

header("Content-type: application/json; charset=utf8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
//	ICQ_messagesSend_SYNC('sashnone', "🚨 ");
if (($_REQUEST['card'] ?? "")) {
	$_REQUEST['card'] = array_filter(explode(',', $_REQUEST['card']));
}
$allowed = query2array(mysqlQuery("SELECT * FROM `SKUD`"
				. " LEFT JOIN `users` ON (`idusers` = `SKUD_user`)"
				. " WHERE `SKUD_lock`='" . mysqli_real_escape_string($link, $_GET['lock']) . "'"
				. " AND NOT isnull(`usersCard`)"
				. " AND isnull(`usersDeleted`)"));
$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersCard` = '" . mysqli_real_escape_string($link, $_REQUEST['card'][count($_REQUEST['card']) - 1]) . "'"));
if ($user) {
	mysqlQuery("INSERT INTO `cardLog` SET "
			. " `cardLogUser` = " . (($user['idusers'] ?? false) ? ("'" . $user['idusers'] . "'") : "null") . ","
			. " `cardLogLock` = '" . mysqli_real_escape_string($link, $_REQUEST['lock']) . "',"
			. " `cardLogAllow` = '" . (in_array($_REQUEST['card'][count($_REQUEST['card']) - 1], array_column($allowed, 'usersCard')) ? '1' : '0') . "'");
}
if (0 || !in_array($_REQUEST['card'][count($_REQUEST['card']) - 1], array_column($allowed, 'usersCard'))) {


	sendTelegram('sendMessage', ['chat_id' => '325908361', 'text' => "Lock: " . $_REQUEST['lock'] . "\r\nCard: " . $_REQUEST['card'][count($_REQUEST['card']) - 1] . "\r\nAllowed: " . (in_array($_REQUEST['card'][count($_REQUEST['card']) - 1], array_column($allowed, 'usersCard')) ? 'yes' : 'no')]);
}
print json_encode(array_column($allowed, 'usersCard')); // отправляем данные в замок