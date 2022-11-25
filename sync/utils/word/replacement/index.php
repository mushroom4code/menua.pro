<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';
$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/replacement.docx');

$contract = mfa(mysqlQuery(""
				. "SELECT *,"
				. " DATE_ADD(`f_salesDate`, INTERVAL 2 YEAR) AS `procend`, "
				. " DATE_ADD(`f_salesDate`, INTERVAL 1 MONTH) AS `installmentdate` "
				. "FROM `f_sales`"
				. "LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
				. "LEFT JOIN `clientsPassports` ON (`idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = `idclients`))"
				. "LEFT JOIN `entities` ON (`identities` = `f_salesEntity`)"
				. "LEFT JOIN `f_credits` ON (`f_creditsSalesID` = `idf_sales`)"
				. "LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`)"
				. " WHERE `idf_sales` = " . (($_GET['contract'] ?? 0) ? FSI($_GET['contract']) : '(SELECT MAX(`idf_sales`) FROM `f_sales`)') . ""));
//printr($contract);
$phones = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $contract['idclients'] . "'"));



$_toAppend = query2array(mysqlQuery("SELECT `servicesName` as `addedServicesName`,`f_salesContentQty` as `addedSalesContentQty` FROM "
				. " `f_subscriptions`"
				. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
				. " WHERE"
				. " `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'"
				. "AND `f_subscriptionsDate`>='" . ($_GET['date'] . " 00:00:00") . "'"
				. "AND `f_subscriptionsDate`<='" . ($_GET['date'] . " 23:59:59") . "'"
				. "AND `f_salesContentQty`>0"
				. ""));

$_toRemove = query2array(mysqlQuery("SELECT `servicesName` as `removedServicesName`, -`f_salesContentQty` as `removedSalesContentQty` FROM `f_subscriptions` "
				. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
				. "WHERE"
				. " `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'"
				. "AND `f_subscriptionsDate`>='" . ($_GET['date'] . " 00:00:00") . "'"
				. "AND `f_subscriptionsDate`<='" . ($_GET['date'] . " 23:59:59") . "'"
				. "AND `f_salesContentQty`<0"
				. ""));

foreach ($_toAppend as &$_toAppenda) {
	$_toAppenda['addedServicesName'] = $_toAppenda['addedServicesName'] . ' - ' . $_toAppenda['addedSalesContentQty'] . 'шт.';
}
foreach ($_toRemove as &$_toRemovea) {
	$_toRemovea['removedServicesName'] = $_toRemovea['removedServicesName'] . ' - ' . $_toRemovea['removedSalesContentQty'] . 'шт.';
}

$coords = query2array(mysqlQuery("SELECT "
				. "`usersFirstName` as `fname`, "
				. "`usersLastName` as `lname`, "
				. "`usersMiddleName` as `mname` "
				. "FROM `f_salesReplacementsCoordinator`"
				. "LEFT JOIN `users` ON (`idusers` = `f_salesReplacementsCoordinatorCurator`)"
				. " WHERE `f_salesReplacementsCoordinatorContract` = '" . $contract['idf_sales'] . "' AND `f_salesReplacementsCoordinatorDate` = '" . $_GET['date'] . "';"));

$coordsPrint = [];
foreach ($coords as $coord) {
	$coordsPrint[]['coordName'] = $coord['lname'] . ' ' . $coord['fname'] . ' ' . $coord['mname'];
}

$data = [
	'NomerDog' => $contract['f_salesNumber'] ?? ($contract['f_salesClient'] . '.' . date("Ymd", strtotime($contract['f_salesDate'])) . date("Hi", strtotime($contract['f_salesTime']))),
	'DayDog' => date("d", strtotime($contract['f_salesDate'])),
	'MesDog' => $_MONTHES['full']['gen'][date("n", strtotime($contract['f_salesDate']))],
	'GodDog' => date("Y", strtotime($contract['f_salesDate'])),
	'fioPokupatelya' => (
	($contract['clientsLName'] ? mb_ucfirst($contract['clientsLName']) : '')
	. ($contract['clientsFName'] ? (' ' . mb_ucfirst($contract['clientsFName'])) : '')
	. ($contract['clientsMName'] ? (' ' . mb_ucfirst($contract['clientsMName'])) : '')),
	'fioPokupatelya2' => (
	($contract['clientsLName'] ? mb_ucfirst($contract['clientsLName']) : '')
	. ($contract['clientsFName'] ? (' ' . (mb_substr(mb_ucfirst($contract['clientsFName']), 0, 1)) . '.') : '')
	. ($contract['clientsMName'] ? (' ' . (mb_substr(mb_ucfirst($contract['clientsMName']), 0, 1)) . '.') : '')
	),
	'chislo1' => date("j", strtotime($contract['clientsBDay'])),
	'mes1' => date("m", strtotime($contract['clientsBDay'])),
	'god1' => date("Y", strtotime($contract['clientsBDay'])),
	'mestorojd' => $contract['clientsPassportsBirthPlace'] ?? '-',
	'seriya' => (explode(' ', $contract['clientsPassportNumber'] ?? '')[0]),
	'Nomer' => (explode(' ', $contract['clientsPassportNumber'] ?? '')[1] ?? ''),
	'Kemvidan' => $contract['clientsPassportsDepartment'] ?? '-',
	'AdresReg' => $contract['clientsPassportsRegistration'] ? $contract['clientsPassportsRegistration'] : $contract['clientsPassportsResidence'],
	'AdresFakt' => $contract['clientsPassportsResidence'] ? $contract['clientsPassportsResidence'] : $contract['clientsPassportsRegistration'],
	'telefon' => implode(", ", array_unique(array_column($phones, 'clientsPhonesPhone'))),
	'procend' => date("d.m.Y", strtotime($contract['procend'])),
	'installmentdate' => $contract['installmentdate'],
	'ProcentSummaKredit' => $contract['f_salesSumm'],
	'ProcentSummaKreditStr' => number2string($contract['f_salesSumm']),
	'entitiesName' => $contract['entitiesNamePrint'],
	'entitiesAddressFact' => $contract['entitiesAddressFact'],
	'entitiesLicense' => $contract['entitiesLicense'],
	'entitiesOGRN' => $contract['entitiesOGRN'],
	'entitiesRegDate' => $contract['entitiesRegDate'],
	'entitiesDirectorFullGen' => $contract['entitiesDirectorFullGen'],
	'entitiesDirectorDat' => $contract['entitiesDirectorDat'],
	'entitiesData' => $contract['entitiesData'],
	'entitiesDirectorNom' => $contract['entitiesDirectorNom'],
	'comment' => mfa(mysqlQuery("SELECT * FROM `f_salesReplacementComments` WHERE"
					. " `f_salesReplacementCommentsContract`='" . $contract['idf_sales'] . "'"
					. " AND `f_salesReplacementCommentsDate`= '" . $_GET['date'] . "'"
					. ""))['f_salesReplacementCommentsText'] ?? '--',
	'added' => count($_toAppend) ? 'Добавлено:' : '',
	'removed' => count($_toRemove) ? 'Удалено:' : '',
];


//printr($_toAppend);
//printr($_toRemove);

$templateProcessor->cloneRowAndSetValues('coordName', $coordsPrint);

if (count($_toAppend)) {
	$templateProcessor->cloneRowAndSetValues('addedServicesName', $_toAppend);
} else {
	$data['added'] = '';
	$data['addedServicesName'] = '';
}

if (count($_toRemove)) {
	$templateProcessor->cloneRowAndSetValues('removedServicesName', $_toRemove);
} else {
	$data['removed'] = '';
	$data['removedServicesName'] = '';
}


foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}
//printr($_toRemove);
//die();

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="Замена ' . date("Y.m.d") . ' ' . $data['fioPokupatelya'] . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');
