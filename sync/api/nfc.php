<?
header("Content-type: application/json; charset=utf8");
include $_SERVER['DOCUMENT_ROOT'] . '/includes/setupLight.php';
?>ok<?
$fh = fopen('requests/Z_' . microtime(1) . '.txt', 'w');

if (!empty($_REQUEST)) {
	fwrite($fh, json_encode($_REQUEST, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	if (isset($_REQUEST['data'])) {
		$fingerPrint = hexdec(FSS($_REQUEST['data']));
		mysqlQuery("INSERT INTO `fingerLog` SET `fingerLogData`='" . $fingerPrint . "', `fingerLogUser` = (SELECT `idusers` FROM `users` WHERE `usersFinger` = '" . $fingerPrint . "' )");
	}
}
fclose($fh);
