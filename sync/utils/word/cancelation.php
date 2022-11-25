<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';

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
//die();



$credit = mfa(mysqlQuery("SELECT * FROM `f_credits`"
				. " LEFT JOIN `RS_banks` ON (`idRS_banks`=`f_creditsBankID`) "
				. " WHERE `f_creditsSalesID` = '" . $contract['idf_sales'] . "'"));

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
	'clientsPassportNumber' => $contract['clientsPassportNumber'],
	'gender' => [null => '(ый/ая)', '0' => 'ая', '1' => 'ый'][$contract['clientsGender']],
	'clientsPassportsCode' => $contract['clientsPassportsCode'],
	'seriya' => (explode(' ', $contract['clientsPassportNumber'] ?? '')[0]),
	'Nomer' => (explode(' ', $contract['clientsPassportNumber'] ?? '')[1] ?? ''),
	'f_salesEntityName' => $contract['entitiesNamePrint'],
	'entitiesDirectorDat' => $contract['entitiesDirectorDat'],
	'clientsPassportsRegistration' => $contract['clientsPassportsRegistration'] ?? $contract['clientsPassportsResidence'],
	'clientsPassportsBirthPlace' => $contract['clientsPassportsBirthPlace'],
	'RS_banksName' => $contract['RS_banksName'],
	'f_creditRS' => ($contract['f_creditRS'] ?? "                                   "),
	'f_salesCancellationSumm' => $contract['f_salesCancellationSumm'],
	'f_creditsBankAgreementNumber' => $contract['f_creditsBankAgreementNumber'],
	'f_salesCancellationSummString' => number2string($contract['f_salesCancellationSumm']),
	'f_salesCancellationDate' => '«' . date("d", strtotime($contract['f_salesCancellationDate'])) . '» ' . $_MONTHES['full']['gen'][date("n", strtotime($contract['f_salesCancellationDate']))] . ' ' . date("Y", strtotime($contract['f_salesCancellationDate']))
];

if ($contract['f_salesCancellationSumm'] < $contract['f_salesSumm']) {
	$file = 'cancellationPart.docx';
} else {
	
}

$file = 'cancellation20220708.docx';

$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/' . $file);

foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}


header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="Заявление о расторжении ' . date("Y.m.d") . ' - ' . $data['fioPokupatelya'] . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');

