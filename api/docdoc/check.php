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
include 'constants.php';

while (1) {
	$users = query2array(mysqlQuery(""
					. " SELECT `servicesAppliedLogUser` as `idusers`, '1' as `clinic`, 'warehouse' as `database`, `usersPositionsPosition` FROM `warehouse`.`servicesAppliedLog` LEFT JOIN `usersPositions` ON (`usersPositionsUser` = `servicesAppliedLogUser`) GROUP BY `servicesAppliedLogUser`,`usersPositionsPosition` "
					. " UNION ALL"
					. " SELECT `servicesAppliedLogUser` as `idusers`, '2' as `clinic`, 'vita' as `database`, `usersPositionsPosition` FROM `vita`.`servicesAppliedLog` LEFT JOIN `usersPositions` ON (`usersPositionsUser` = `servicesAppliedLogUser`)  GROUP BY `servicesAppliedLogUser`,`usersPositionsPosition` "
	));
//	printr($users);
	foreach ($users as $user) {
		if ($user['idusers']) {
			if (in_array($user['usersPositionsPosition'], [1, 3, 6, 7, 8, 9, 10, 11, 12, 13, 27, 31, 34, 36, 39, 42, 43, 48, 50, 51, 54, 55, 56, 57, 58, 59, 61, 62, 63, 65])) {
				$response = DOCDOC_updateSchedule($user, $user['clinic']);
//				printr(json_decode($response, 1), 1);
			}
			mysqlQuery("DELETE FROM `" . $user['database'] . "`.`servicesAppliedLog` WHERE `servicesAppliedLogUser` = '" . $user['idusers'] . "'");
		} else {
			mysqlQuery("DELETE FROM `" . $user['database'] . "`.`servicesAppliedLog` WHERE isnull(`servicesAppliedLogUser`)");
		}
	}
	sleep(2);
}


