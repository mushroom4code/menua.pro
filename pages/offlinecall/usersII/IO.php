<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");



if (1 && ($_JSON['action'] ?? '') === 'userOptions') {
//action	"userOptions"
//option	1
//user	229
//value	1
//, , , userOptionsOption, userOptionsValue
	mysqlQuery("INSERT INTO `userOptions` SET "
			. "`userOptionsUser` = '" . mysqli_real_escape_string($link, $_JSON['user']) . "',"
			. "`userOptionsOption` = '" . mysqli_real_escape_string($link, $_JSON['option']) . "',"
			. "`userOptionsValue` = '" . mysqli_real_escape_string($link, $_JSON['value']) . "'"
			. "");
	print json_encode(['success' => true], 288);
	die();
}

