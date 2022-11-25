<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';

$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . mres($_GET['client'] ?? false) . "'"));

if (!$client) {
	die('client not found');
}

if (!$client['clientsContractDate']) {
	mysqlQuery("UPDATE `clients` SET `clientsContractDate` = CURDATE() WHERE (`idclients` = '" . $client['idclients'] . "');");
	$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . mres($_GET['client'] ?? false) . "'"));
}
$entity = mfa(mysqlQuery("SELECT * FROM `entities` WHERE `identities`='" . mres($_GET['entity'] ?? false) . "'"));
if (!$entity) {
	die('entity not found');
}
//printr($entity);
$passport = mfa(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "')"));
if (!$passport) {
	die('Нет паспортных даных!!!');
}
$phones = mfa(mysqlQuery("SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ') AS `phones` FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`);"))['phones'] ?? 'не найдено';

//$date = date("Y-m-d", strtotime($client['clientsOldSince'])); //возможно тут надо будет подставить какую-то фиксированную дату заключения договора. ДАТА ПЕРЕХОДА В СТАТУС ВТОРИЧНОГО КЛИЕНТА.
if ($client['clientsContractDate']) {
	$services = query2array(mysqlQuery("SELECT * FROM `servicesApplied` LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) WHERE `servicesAppliedClient` = '" . $client['idclients'] . "' AND `servicesAppliedDate` = '" . $client['clientsContractDate'] . "' AND isnull(`servicesAppliedDeleted`)")); //Услуги записанные на день заключения договора
} else {
	die('Не установлена дата первого визита в клинику/дата перехода в статус вторичного клиента');
	$services = [];
}

//printr($services);

$license = mfa(mysqlQuery("SELECT * FROM `licenses` WHERE `licensesEntity` = '" . mres($_GET['entity'] ?? false) . "'"));
if (!$license) {
	die('Ошибка загрузки лицензий');
}
//printr($license);
$data = [
	/* ${ */'ndog' => ENTITY_CODE . $client['idclients'], //} - номер договора
	/* ${ */ 'nak' => ($client['clientsAKNum'] ?? ('99' . $client['idclients'])), //} - Номер амбулаторной карты
	/* ${ */ 'd' => date("d", strtotime($client['clientsContractDate'])), //} - день месяца заключения договора с ведущим нулём.
	/* ${ */ 'monthrp' => $_MONTHES['full']['gen'][date("n", strtotime($client['clientsContractDate']))], //} - название месяца в родительном падеже полностью.
	/* ${ */ 'year' => date("Y", strtotime($client['clientsContractDate'])), //} - год заключения договора полностью
	/* ${ */ 'clientLN' => $client['clientsLName'], //} - Фамилия клиента
	/* ${ */ 'clientFN' => $client['clientsFName'], //} - Имя клиента
	/* ${ */ 'cfn' => mb_substr($client['clientsFName'], 0, 1), //} - Имя клиента первая буква
	/* ${ */ 'clientMN' => $client['clientsMName'], //} - Отчество клиента
	/* ${ */ 'cmn' => mb_substr($client['clientsMName'], 0, 1), //} - Отчество клиента первая буква
	/* ${ */ 'fioPokupatelya' => implode(' ', array_filter([$client['clientsLName'], $client['clientsFName'], $client['clientsMName']])), //} - ФИО полностью

	/* ${ */ 'clientBD' => date("d.m.Y", strtotime($client['clientsBDay'])), //} - дата рождения клиента в формате 01.07.1963
	/* ${ */ 'clientBd' => date("d", strtotime($client['clientsBDay'])), //} - число месяца даты рождения клиента в формате (01)
	/* ${ */ 'clientBm' => date("m", strtotime($client['clientsBDay'])), //} - номер месяца даты рождения клиента в (01)
	/* ${ */ 'clientBY' => date("Y", strtotime($client['clientsBDay'])), //} - год рождения клиента в формате 1963
	/* ${ */ 'clientBP' => $passport['clientsPassportsBirthPlace'], //} - место рождения клиента
	/* ${ */ 'clientsPN' => $passport['clientsPassportNumber'], //} - Серия номер паспорта
	/* ${ */ 'clientsPD' => $passport['clientsPassportsDepartment'], //} - организация выдавшая паспорт
	/* ${ */ 'clientsPassportsRegistration' => ($passport['clientsPassportsRegistration'] ?? $passport['clientsPassportsResidence']), //} - адрес регистрации
	/* ${ */ 'clientsPassportsResidence' => ($passport['clientsPassportsResidence'] ?? $passport['clientsPassportsRegistration']), //} - адрес фактического проживания
	/* ${ */
	'clientsPhonesPhone' => $phones, //} - телефонный номер клиента
	/* ${ */ 'entitiesData' => $entity['entitiesData'], //} - Данные юрлица 
	/* ${ */ 'entitiesName' => $entity['entitiesNamePrint'], //} - наименование юрлица
	/* ${ */ 'entitiesDirectorNom' => $entity['entitiesDirectorNom'], //} - фио директора
	/* ${ */ 'entitiesDirectorFullGen' => $entity['entitiesDirectorFullGen'], //} - фио директора

	/* ${ */ 'entitiesTIN' => $entity['entitiesTIN'], //} - 
	/* ${ */ 'entitiesOGRN' => $entity['entitiesOGRN'], //} - 
	/* ${ */ 'licensesNumber' => $license['licensesNumber'], //} - 
	/* ${ */ 'licensesDate' => $license['licensesDate'], //} - 
	/* ${ */ 'licensesDepartment' => $license['licensesDepartment'], //} - 
	/* ${ */ 'licensesEntityAddress' => $license['licensesEntityAddress'], //} - 
	/* ${ */ 'licensesServices' => $license['licensesServices'], //} - 
	/* ${ */ 'clientsGender' => [null => '_____', '1' => 'ый', '0' => 'ая'][$client['clientsGender']], //} - 
];

$rows = [];

$n = 0;
$total = 0;

foreach ($services as $service) {
	$n++;
	$rows[] = [
		'arn' => $n,
		'avrSerCode' => $service['servicescolN804'] ?? '',
		'avrServiceName' => $service['servicesName'],
		'avrServicePrice' => ($service['servicesAppliedPrice'] ?? 0) * ($service['servicesAppliedQty'] ?? 0)
	];
	$total += ($service['servicesAppliedPrice'] ?? 0) * ($service['servicesAppliedQty'] ?? 0);
}


$data['avrServiceSumm'] = $total;
///* ${ */ 'avrServiceSumm' => '0', //} - Суммарная стоимость услуг в акте выполненных работ (2500)
//	/* ${ */ 'avrServiceSummText' => 'не определено', //} - Суммарная стоимость услуг в акте выполненных работ прописью (две тысячи пятьсот рублей)
$data['avrServiceSummText'] = number2string($total);

//printr($data, 1);
//die();
$file = 'new/maintemplate.docx';

$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/' . $file);

foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}

//$templateProcessor->cloneRowAndSetValues('arn', $rows);

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . date("Y.m.d") . ' - ' . $data['fioPokupatelya'] . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');
/*
*/