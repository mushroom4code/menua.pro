<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

//    [action] => savePos2Serv
//    [position] => 2
//    [service] => 61
//    [state] => 1


if (R(43) && isset($_JSON['action']) && $_JSON['action'] === 'savePos2Serv') {

	if ($_JSON['state']) {
		mysqlQuery("INSERT IGNORE INTO `positions2services`"
				. "SET"
				. " `positions2servicesPosition` = '" . FSI($_JSON['position']) . "',"
				. " `positions2servicesService` = '" . FSI($_JSON['service']) . "'");
	} else {
		mysqlQuery("DELETE FROM `positions2services`"
				. "WHERE"
				. " `positions2servicesPosition` = '" . FSI($_JSON['position']) . "'"
				. "AND `positions2servicesService` = '" . FSI($_JSON['service']) . "'");
	}
}

//printr($_JSON);
