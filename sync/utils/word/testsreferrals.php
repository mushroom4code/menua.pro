<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';

$serviceApplied = mfa(mysqlQuery("SELECT * FROM"
				. " `servicesApplied` "
				. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
				. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
				. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
				. " LEFT JOIN `testsReferrals` ON (`idtestsReferrals`=`servicesTestsReferral`)"
				. " LEFT JOIN `clientsPassports` ON (`idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = `idclients`))"
				. " WHERE `idservicesApplied` = " . mres($_GET['serviceapplied'] ?? false) . " "));
if (!($file = $serviceApplied['testsReferralsFile'])) {
	die('404');
}


$data = [
	'testName' => $serviceApplied['servicesName'],
	'cardNumber' => $serviceApplied['clientsAKNum'] ?? sprintf('9%06d', $serviceApplied['idclients']),
	//// 
	//${} ${} ${}
	'd' => date("d"),
	/* ${ */ 'monthrp' => $_MONTHES['full']['gen'][date("n")], //} - название месяца в родительном падеже полностью.
	'year' => date("Y"),
	'fioPokupatelya' => implode(' ', array_filter([$serviceApplied['clientsLName'], $serviceApplied['clientsFName'], $serviceApplied['clientsMName']])),
	'bday' => $serviceApplied['clientsBDay'] ? date("d.m.Y", strtotime($serviceApplied['clientsBDay'])) : '',
	'clientsPassportNumber' => ($serviceApplied['clientsPassportNumber'] ?? ''),
	'doctorName' => implode(' ', array_filter([$serviceApplied['usersLastName'], $serviceApplied['usersFirstName'], $serviceApplied['usersMiddleName']])),
];

//printr($data, 1);
//printr($serviceApplied, 1);
//die();
$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/sync/utils/word/templates/testsReferrals/' . $file);

foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}

//$templateProcessor->cloneRowAndSetValues('arn', $rows);

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . date("Y.m.d") . ' направление (' . $serviceApplied['servicesName'] . ') ' . $data['fioPokupatelya'] . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');
/*
*/