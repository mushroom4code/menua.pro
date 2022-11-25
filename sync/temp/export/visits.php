<?php

ini_set('memory_limit', '2G');

//header("Content-type: text/plain; charset=utf8");
//header("Content-Type: ");
//header('Content-type: plain/text');

function exportCSV($rows = false) {
	if (!empty($rows)) {
		$name = date("YmdHis") . ".csv";
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $name);
		$output = fopen('php://output', 'w');
		fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
		foreach ($rows as $row) {
			if (!is_array($row)) {
				$row = [$row];
			}
			fputcsv($output, $row, ';');
		}
		exit();
	}
	return false;
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$servicesApplied = query2array(mysqlQuery("SELECT "
				. "`servicesName`,"
				. "`servicesAppliedCommentText`,"
				. "`servicesApplied`.*,"
				. " (SELECT CONCAT_WS(' ',`usersLastName`,`usersFirstName`,`usersMiddleName`) FROM `vita`.`users` WHERE `idusers` = `servicesAppliedBy`) as `author`,"
				. " (SELECT CONCAT_WS(' ',`usersLastName`,`usersFirstName`,`usersMiddleName`) FROM `vita`.`users` WHERE `idusers` = `servicesAppliedPersonal`) as `personnel`,"
				. " (SELECT CONCAT_WS(' ',`clientsLName`,`clientsFName`,`clientsMName`) FROM `vita`.`clients` WHERE `idclients` = `servicesAppliedClient`) as `client`,"
				. " (SELECT `clientsPhonesPhone` FROM `vita`.`clientsPhones` WHERE `idclientsPhones` = (SELECT MAX(`idclientsPhones`) FROM `vita`.`clientsPhones` WHERE `clientsPhonesClient` = `servicesAppliedClient`)) as `clientsPhonesPhone`,"
				. " (SELECT COUNT(1) FROM `vita`.`clientsVisits` WHERE `clientsVisitsClient` = `servicesAppliedClient` AND `clientsVisitsDate` = `servicesAppliedDate`) as `clientVisit`"
				. " FROM "
				. " `vita`.`servicesApplied`"
				. " LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA` = `idservicesApplied`)"
				. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
				. " ORDER BY `servicesAppliedDate`,`servicesAppliedClient`"
				. " "));
//    "": null,
//    "": null,
//    "": null,

$dateclient = '';
$visitum = 0;
$output = [
	[
		"Номер",
		"Автор",
		"Дата и время",
		"Тип визита",
		"Тип строки",
		"Название",
		"Клиент",
		"Телефон",
		"E-mail",
		"Мастер",
		"Количество",
		"Сумма",
		"Оплачено",
		"Клиент пришел",
		"Визит подтвержден",
		"Визит отменен",
		"Комментарий"
	]
];
foreach ($servicesApplied as $servicApplied) {
	if (!($printed ?? false)) {
//		printr($servicApplied);
		$printed = true;
	}
	if ($dateclient != $servicApplied['servicesAppliedDate'] . '-' . $servicApplied['servicesAppliedClient']) {
		$dateclient = $servicApplied['servicesAppliedDate'] . '-' . $servicApplied['servicesAppliedClient'];
		$visitum++;
	}

	$output[] = [
		$visitum,
		($servicApplied['author'] ?? ''), //Автор
		($servicApplied['servicesAppliedTimeBegin'] ?? ''), //Дата и время
		"", //Тип визита
		"service", //Тип строки
		$servicApplied['servicesName'], //Название
		$servicApplied['client'], //Клиент
		$servicApplied['clientsPhonesPhone'], //Телефон
		"", //E-mail
		($servicApplied['personnel'] ?? ''), //Мастер
		($servicApplied['servicesAppliedQty'] ?? 0), //Количество
		(($servicApplied['servicesAppliedQty'] ?? 0) * ($servicApplied['servicesAppliedPrice'] ?? 0)), //Сумма
		(($servicApplied['servicesAppliedQty'] ?? 0) * ($servicApplied['servicesAppliedPrice'] ?? 0)), //Оплачено
		$servicApplied['clientVisit'] ? 'Да' : 'Нет', //Клиент пришел
		"", //Визит подтвержден
		$servicApplied['servicesAppliedDeleted'] ? 'Да' : 'Нет', //Визит отменен
		($servicApplied['servicesAppliedCommentText'] ?? ""), //Комментарий
	];
}
exportCSV($output);

//printr($output[0]);
/*Справка

Тип строки - "service" услуга, "commodity" - товар, "fundsDocument" - оплата в конкретную кассу
Сумма - это стоимость товара/услуги умноженная на количество.
 */


