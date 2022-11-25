<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';


if (!($_GET['sale'] ?? false)) {
	die('NO CONTRACT PROVIDED');
}


$contract = mfa(mysqlQuery(""
				. "SELECT *,"
				. " DATE_ADD(`f_salesDate`, INTERVAL 2 YEAR) AS `procend`, "
				. " DATE_ADD(`f_salesDate`, INTERVAL 1 MONTH) AS `installmentdate` "
				. "FROM `f_sales`"
				. "LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
				. "LEFT JOIN `entities` ON (`identities` = `f_salesEntity`)"
				. "LEFT JOIN `clientsPassports` ON (`idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = `idclients`))"
				. " WHERE `idf_sales` = " . mres($_GET['sale']) . ""));


$phones = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $contract['idclients'] . "'"));

if ($_GET['iddocument'] ?? false) {
	$iddocument = $_GET['iddocument'];
} else {
	mysqlQuery("INSERT INTO `documents` SET  `documentsTemplate` = 'tax.docx', `documentsClient`='" . $contract['idclients'] . "'");
	$iddocument = mysqli_insert_id($link);
}

if (($_GET['taxPerson'] ?? '') !== '') {

	$clientsTaxPerson = mfa(mysqlQuery("SELECT * FROM `clientsTaxPersons` WHERE `idclientsTaxPersons` = '" . mres($_GET['taxPerson']) . "'"));
	$taxPersonFULLname = $clientsTaxPerson['clientsTaxPersonsFULLName'];
	$taxPersonINN = $clientsTaxPerson['clientsTaxPersonsTIN'];
} else {
	$taxPersonFULLname = ( ($contract['clientsLName'] ? mb_ucfirst($contract['clientsLName']) : '') . ($contract['clientsFName'] ? (' ' . mb_ucfirst($contract['clientsFName'])) : '') . ($contract['clientsMName'] ? (' ' . mb_ucfirst($contract['clientsMName'])) : ''));
	$taxPersonINN = $contract['clientsTIN'];
}
$clientFULLname = ( ($contract['clientsLName'] ? mb_ucfirst($contract['clientsLName']) : '') . ($contract['clientsFName'] ? (' ' . mb_ucfirst($contract['clientsFName'])) : '') . ($contract['clientsMName'] ? (' ' . mb_ucfirst($contract['clientsMName'])) : ''));
$data = [
	'docID' => $iddocument,
	'taxPersonFULLname' => $taxPersonFULLname,
	'taxPersonINN' => $taxPersonINN,
	'clientFULLname' => $clientFULLname,
	'NomerDog' => ($contract['f_salesNumber'] ?? ($contract['f_salesClient'] . '.' . date("Ymd", strtotime($contract['f_salesDate'])) . date("Hi", strtotime($contract['f_salesTime'])))),
	'AKNUM' => $contract['clientsAKNum'],
	'f_salesSumm' => $contract['f_salesSumm'],
	'f_salesSummText' => number2string($contract['f_salesSumm']),
	'f_salesDay' => date("d", strtotime($contract['f_salesDate'])),
	'f_salesMonth' => $_MONTHES['full']['gen'][date("n", strtotime($contract['f_salesDate']))],
	'f_salesYear' => date("Y", strtotime($contract['f_salesDate'])),
	'docDay' => date("d"),
	'docMonth' => $_MONTHES['full']['gen'][date("n")],
	'docYear' => date("Y"),
///
	'urName' => $contract['entitiesNamePrint'],
	'urAddress' => $contract['entitiesAddressFact'],
	'entitiesTIN' => $contract['entitiesTIN'] ?? '',
	'licenseNum' => $contract['entitiesLicenseNum'] ?? '',
	'licenseDate' => date("d.m.Y", strtotime($contract['entitiesLicenseDate'] ?? date("Y-m-d H:i:s"))),
	'licenseSource' => $contract['entitiesLicenseSource'] ?? '',
	'entitiesData' => $contract['entitiesData'] ?? '',
	'entitiesDirectorNom' => $contract['entitiesDirectorNom'] ?? '',
];






$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/tax.docx');

foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . date("Y.m.d") . ' НДС - ' . $clientFULLname . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');



