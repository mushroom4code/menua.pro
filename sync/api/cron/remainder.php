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

$users = query2array(mysqlQuery("SELECT COUNT(1) as `summ`, usersLastName,usersFirstName,usersICQ FROM `servicesApplied`"
				. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
				. " WHERE `servicesAppliedDate` = CURDATE()"
				. " AND NOT ISNULL(`servicesAppliedStarted`)"
				. " AND ISNULL(`servicesAppliedFineshed`)"
				. " AND ISNULL(`servicesAppliedDeleted`)"
				. " GROUP BY `idusers`"
				. ""));
if (count($users)) {
	foreach ($users as $user) {
		if ($user['usersICQ']) {
			$text = 'Напоминаю, что Вам необходимо закрыть начатые процедуры, иначе они не попадут в отчёт, а следовательно не будут учтены при подсчёте заработной платы.';
			ICQ_messagesSend_SYNC($user['usersICQ'], $text);
		}
	}
} else {
	foreach (getUsersByRights([84]) as $user) {
		if ($user['usersICQ']) {
			ICQ_messagesSend_SYNC($user['usersICQ'], '😘😘Какие все молодцы! Никто не забыл закрыть свои процедуры! Спасибо вам!!😘😘');
		}
	}
}




