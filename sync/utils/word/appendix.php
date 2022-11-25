<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';

$f_sale = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales` = '" . mres($_GET['sale'] ?? '') . "'"));
if (!$f_sale) {
	die('sale not found');
}

$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . $f_sale['f_salesClient'] . "'"));
if (!$client) {
	die('client not found');
}
$entity = mfa(mysqlQuery("SELECT * FROM `entities` WHERE `identities`='" . $f_sale['f_salesEntity'] . "'"));
if (!$entity) {
	die('entity not found');
}
$passport = mfa(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "')"));
$phones = mfa(mysqlQuery("SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ') AS `phones` FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`);"))['phones'] ?? 'не найдено';

$license = mfa(mysqlQuery("SELECT * FROM `licenses` WHERE `licensesEntity` = '" . $entity['identities'] . "'"));
if (!$license) {
	die('Ошибка загрузки лицензий');
}

//$date = date("Y-m-d", strtotime($client['clientsOldSince'])); //возможно тут надо будет подставить какую-то фиксированную дату заключения договора. ДАТА ПЕРЕХОДА В СТАТУС ВТОРИЧНОГО КЛИЕНТА.
//, f_salesDraftDate, f_salesDraftAuthor, f_salesDraftNumber

$services = query2array(mysqlQuery("SELECT * FROM"
				. " `f_subscriptions` "
				. "LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
				. "WHERE `f_subscriptionsContract` = '" . $f_sale['idf_sales'] . "'")); //Услуги записанные на день заключения договора
//printr($services, 1);
$data = [
	/* ${ */'ndog' => ENTITY_CODE . $client['idclients'], //} - номер договора
	/* ${ */ 'nak' => ($client['clientsAKNum'] ?? ('99' . $client['idclients'])), //} - Номер амбулаторной карты
	/* ${ */ 'd' => date("d", strtotime($client['clientsContractDate'])), //} - день месяца заключения договора с ведущим нулём.
	/* ${ */ 'monthrp' => $_MONTHES['full']['gen'][date("n", strtotime($client['clientsContractDate']))], //} - название месяца в родительном падеже полностью.
	/* ${ */ 'year' => date("Y", strtotime($client['clientsContractDate'])), //} - год заключения договора полностью
	/* ${ */ 'fioPokupatelya' => $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'],
	/* ${ */ 'clientLN' => $client['clientsLName'], //} - Фамилия клиента
	/* ${ */ 'clientFN' => $client['clientsFName'], //} - Имя клиента
	/* ${ */ 'cfn' => mb_substr($client['clientsFName'], 0, 1), //} - Имя клиента первая буква
	/* ${ */ 'clientMN' => $client['clientsMName'], //} - Отчество клиента
	/* ${ */ 'cmn' => mb_substr($client['clientsMName'], 0, 1), //} - Отчество клиента первая буква
	/* ${ */ 'clientBD' => date("d.m.Y", strtotime($client['clientsBDay'])), //} - дата рождения клиента в формате 01.07.1963
	/* ${ */ 'clientBd' => date("d", strtotime($client['clientsBDay'])), //} - число месяца даты рождения клиента в формате (01)
	/* ${ */ 'clientBm' => date("m", strtotime($client['clientsBDay'])), //} - номер месяца даты рождения клиента в (01)
	/* ${ */ 'clientBY' => date("Y", strtotime($client['clientsBDay'])), //} - год рождения клиента в формате 1963
	/* ${ */ 'clientBP' => $passport['clientsPassportsBirthPlace'] ?? '', //} - место рождения клиента
	/* ${ */ 'clientsPN' => $passport['clientsPassportNumber'] ?? '', //} - Серия номер паспорта
	/* ${ */ 'clientsPD' => $passport['clientsPassportsDepartment'] ?? '', //} - организация выдавшая паспорт
	/* ${ */ 'clientsPassportsRegistration' => ($passport['clientsPassportsRegistration'] ?? $passport['clientsPassportsResidence'] ?? ''), //} - адрес регистрации
	/* ${ */ 'clientsPassportsResidence' => ($passport['clientsPassportsResidence'] ?? $passport['clientsPassportsRegistration'] ?? ''), //} - адрес фактического проживания
	/* ${ */ 'clientsPhonesPhone' => $phones, //} - телефонный номер клиента
	/* ${ */ 'appendixNumber' => $f_sale['f_salesNumber'], // номер приложения
	/* ${ */ 'clientsGender' => [null => '_____', '1' => 'ый', '0' => 'ая'][$client['clientsGender']], //} - 
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
	/* ${ */ 'appendixDate' => date("d.m.Y", strtotime($f_sale['f_salesDate'])), //} - 
	/* ${ */ 'appendixDueDate' => date("d.m.Y", strtotime('+2 YEAR', strtotime($f_sale['f_salesDate']))), //} - 
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
		'avrServiceSum' => ($service['f_salesContentPrice'] ?? 0) * ($service['f_salesContentQty'] ?? 0),
		'avrServicePrice' => ($service['f_salesContentPrice'] ?? 0),
		'avrServiceQty' => ($service['f_salesContentQty'] ?? 0)
	];
	$total += ($service['f_salesContentPrice'] ?? 0) * ($service['f_salesContentQty'] ?? 0);
}



$data['avrServiceSumm'] = $total;
///* ${ */ 'avrServiceSumm' => '0', //} - Суммарная стоимость услуг в акте выполненных работ (2500)
//	/* ${ */ 'avrServiceSummText' => 'не определено', //} - Суммарная стоимость услуг в акте выполненных работ прописью (две тысячи пятьсот рублей)
$data['avrServiceSummText'] = number2string($total);

//printr($data, 1);
//die();
$file = 'new/appendix.docx';
//printr($rows, 1);
//printr($data, 1);

$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/' . $file);

foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}

$templateProcessor->cloneRowAndSetValues('arn', $rows);

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . date("Y.m.d") . ' - ' . $data['fioPokupatelya'] . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');

