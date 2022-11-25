<?

header("Content-type: application/json; charset=utf8");
include $_SERVER['DOCUMENT_ROOT'] . '/includes/setupLight.php';

if (($_REQUEST['card'] ?? "")) {
	$_REQUEST['card'] = array_filter(explode(',', $_REQUEST['card']));
}
//printr($_REQUEST['card']);
//if (
//		in_array(($_GET['card'] ?? false), [
//			'343b87', '11274b'
//		])) {
//	print 'allow';
//} else {
//	print 'deny';
//}

$allowed = query2array(mysqlQuery("SELECT * FROM `SKUD` LEFT JOIN `users` ON (`idusers` = `SKUD_user`) WHERE `SKUD_lock`='" . mysqli_real_escape_string($link, $_GET['lock']) . "' AND NOT isnull(`usersCard`)"));
ICQ_messagesSend_SYNC('sashnone', "Lock: " . $_REQUEST['lock'] . "\r\nCard: " . $_REQUEST['card'][count($_REQUEST['card']) - 1] . "\r\nAllowed: " . (in_array($_REQUEST['card'][count($_REQUEST['card']) - 1], array_column($allowed, 'usersCard')) ? 'yes' : 'no'));

print json_encode(array_column($allowed, 'usersCard'));
//
//$fh = fopen($_SERVER['DOCUMENT_ROOT'] . '/sync/api/skud/requests/Z_' . microtime(1) . '.txt', 'w');
//if (0&&$fh) {
//	if (!empty($_REQUEST)) {
//		fwrite($fh,
//				//json_encode($_REQUEST, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
//				print_r($_REQUEST, true)
//		);
//	}
//	fclose($fh);
//} else {
////	print 'cant create file';
//}
/*
*/