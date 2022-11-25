<?php

ini_set('memory_limit', '1G');
header("Content-type: text/plain; charset=utf8");
//header("Content-Type: ");
//header('Content-type: plain/text');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$clients = query2array(mysqlQuery("SELECT *,"
				. " (SELECT `clientsPhonesPhone` FROM `vita`.`clientsPhones` WHERE `idclientsPhones` = (SELECT MAX(`idclientsPhones`) FROM `vita`.`clientsPhones` WHERE `clientsPhonesClient` = `idclients`)) as `clientsPhonesPhone`,"
				. " (SELECT ROUND(sum(`f_salesSumm`)) FROM `vita`.`f_sales` WHERE `f_salesClient` =  `idclients`) as `f_salesSumm`,"
				. " (SELECT count(1) FROM `vita`.`clientsVisits` WHERE `clientsVisitsClient` =  `idclients`) as `clientVisits`,"
				. " (SELECT max(`clientsVisitsDate`) FROM `vita`.`clientsVisits` WHERE `clientsVisitsClient` =  `idclients`) as `clientVisitLast`"
				. ""
				. " FROM `vita`.`clients`"
				. " LEFT JOIN `vita`.`clientsPassports` ON (`idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `vita`.`clientsPassports` WHERE `clientsPassportsClient` = `idclients`))"));

foreach ($clients as $client) {
	if (!($printed ?? false)) {
//		printr($client);
		$printed = true;
	}
	print ""
			. $client['idclients'] . "\t"
			. $client['clientsLName'] . "\t"
			. $client['clientsFName'] . "\t"
			. $client['clientsMName'] . "\t"
			. $client['clientsAKNum'] . "\t"
			. "\t"//Номер полиса ОМС
			. $client['clientsPhonesPhone'] . "\t"
			. "\t"//Дополнительный телефон
			. "\t"//Баланс
			. $client['f_salesSumm'] . "\t"//Сумма продаж
			. $client['clientVisits'] . "\t"//Визитов
			. "\t"//Скидка
			. "\t"//E-mail
			. ([null => '', 0 => 'Ж', 1 => 'М'][$client['clientsGender']]) . "\t"//Пол
			. "\t"//Разрешить SMS
			. $client['clientsBDay'] . "\t"//День рождения
			. $client['clientsAKNum'] . "\t"
			. "\t"//	Город
			. "\t"//	Улица
			. "\t"//	Дом
			. "\t"//	Квартира
			. "\t"//	Контрагент
			. "\t"//	Тип
			. $client['clientVisitLast'] . "\t"//Дата последнего визита
			. "\t"//Комментарий
			. $client['clientsPassportNumber'] . "\t"//Паспортные данные
			. "\t"//Серия паспорта
			. $client['clientsPassportNumber'] . "\t"//Номер паспорта
			. $client['clientsPassportsDate'] . "\t"//Дата выдачи паспорта
			. $client['clientsPassportsDepartment'] . "\t"//Кем выдан паспорт
			. "\t"//Instagram
			. "\t"//VK
			. "\t"//Facebook
			. "\t"//Одноклассники
			. "\n";
}





//    "idclientsPassports": 281319,
//    "clientsPassportsClient": 1,
//    "clientsPassportNumber": null,
//    "clientsPassportsResidence": "196084, Санкт-Петербург г, Московский пр-кт, дом № 82, кв.27",
//    "clientsPassportsRegistration": "196084, Санкт-Петербург г, Московский пр-кт, дом № 82, кв.27",
//    "": null,
//    "clientsPassportsBirthPlace": null,
//    "clientsPassportsDepartment": null,
//    "clientsPassportsAdded": "2021-02-05 07:18:49",
//    "clientsPassportsCode": null,
//    "clientsPassportsAddedBy": null,