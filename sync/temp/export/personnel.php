<?php

ini_set('memory_limit', '1G');
header("Content-type: text/plain; charset=utf8");
//header("Content-Type: ");
//header('Content-type: plain/text');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$users = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` FROM `vita`.`users`"));
//printr($users);
foreach ($users as $user) {
	print
			$user['idusers'] . "\t"
			. $user['usersLastName'] . "\t"
			. $user['usersFirstName'] . "\t"
			. $user['usersMiddleName'] . "\t"
			. $user['positions'] . "\t"
			. "\t"
			. "\t"
			. $user['usersBday'] . "\t"
			. "\n";
}

/*
№	Фамилия	Имя	Отчество	Должность	Телефон	E-mail	День рождения	Паспорт - серия	Паспорт - номер	Паспорт - дата	Паспорт - кем выдан	ИНН
*/