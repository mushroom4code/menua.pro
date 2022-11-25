<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';
$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/sync/utils/word/infinitystom.docx');





$contract = mfa(mysqlQuery(""
				. "SELECT *,"
				. " DATE_ADD(`f_salesDate`, INTERVAL 2 YEAR) AS `procend`, "
				. " DATE_ADD(`f_salesDate`, INTERVAL 1 MONTH) AS `installmentdate` "
				. "FROM `f_sales`"
				. "LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
				. "LEFT JOIN `clientsPassports` ON (`idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = `idclients`))"
				. " WHERE `idf_sales` = " . (($_GET['contract'] ?? 0) ? FSI($_GET['contract']) : '(SELECT MAX(`idf_sales`) FROM `f_sales`)') . ""));

$services = query2array(mysqlQuery("SELECT *"
				. " FROM `f_subscriptions` "
				. " LEFT JOIN `services` ON(`idservices` = `f_salesContentService`) "
				. " WHERE `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'"));

$phones = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $contract['idclients'] . "'"));

$credit = mfa(mysqlQuery("SELECT * FROM `f_credits`"
				. " LEFT JOIN `RS_banks` ON (`idRS_banks`=`f_creditsBankID`) "
				. " WHERE `f_creditsSalesID` = '" . $contract['idf_sales'] . "'"));
//printr($credit);

$installment = mfa(mysqlQuery("SELECT * FROM `f_installments` WHERE `f_installmentsSalesID` = '" . $contract['idf_sales'] . "'"));
//printr($installment);

$payments = query2array(mysqlQuery("SELECT * FROM `f_payments`"
				. " LEFT JOIN `f_paymentsTypes` ON  (`idf_paymentsTypes` = `f_paymentsType`) WHERE `f_paymentsSalesID` = '" . $contract['idf_sales'] . "'"));
//printr($payments);
//printr($services);
//printr($contract);
//printr($phones);
//    [clientsLName] => Сазонова
//    [clientsFName] => Ирина
//    [clientsMName] => Вячеславовна


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
	'mes1' => $_MONTHES['full']['gen'][date("n", strtotime($contract['clientsBDay']))],
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
	'ProcentSummaKredit' => $contract['f_salesSumm']
];


$rows = [];

$n = 0;
$total = 0;
foreach ($services as $service) {
	$n++;
	$rows[] = [
		'rn' => $n,
		'servicename' => $service['servicesName'],
		'servicebumber' => $service['f_salesContentQty'],
		'serviceprice' => $service['f_salesContentPrice'],
		'servicesumm' => ($service['f_salesContentPrice'] ?? 0) * ($service['f_salesContentQty'] ?? 0)
	];
	$total += ($service['f_salesContentPrice'] ?? 0) * ($service['f_salesContentQty'] ?? 0);
}


//А именно:
//В Кредит 
//Единовременная оплата 
//В рассрочку
//Если рассрочка то вручную прописываем 
//Стоимость абонемента:50000 например
//Первоначальный взнос:10000
//Остаток 40000 необходимо оплатить до 30.07.2020 (указываем на месяц)
//	'type' => null


$paymentsTEXTS = [];


if ($credit) {
	$paymentsTEXTS['credit'] = 'В Кредит на сумму ' . ($credit['f_creditsSumm'] ?? 'НЕУКАЗАНО') . 'р. (' . number2string($credit['f_creditsSumm'] ?? 0) . ') в банке ' . htmlspecialchars_decode($credit['RS_banksName']) . '.';
}

if ($installment) {
	$paymentsTEXTS['installment'] = 'В рассрочку. Стоимость абонемента: ' . ($contract['f_salesSumm']) . 'р. (' . number2string($contract['f_salesSumm']) . '). ';
	if (count($payments)) {
		$paymentsSTR = [];
		$paymentsTEXTS['installment'] .= 'Первоначальный взнос: ';
		$paymentsSumm = 0;
		foreach ($payments as $payment) {
			$paymentsSTR[] = ($payment['f_paymentsAmount']) . 'р. (' . number2string($payment['f_paymentsAmount']) . ', ' . $payment['f_paymentsTypesName'] . ')';
			$paymentsSumm += $payment['f_paymentsAmount'];
		}
		$paymentsTEXTS['installment'] .= implode(',', $paymentsSTR) . '.';
		$paymentsTEXTS['installment'] .= ' Остаток ' . ($contract['f_salesSumm'] - $paymentsSumm) . 'р. (' . number2string($contract['f_salesSumm'] - $paymentsSumm) . ') необходимо оплатить до ' . date("d.m.Y", strtotime($contract['installmentdate']));
	}
}



if (!$installment) {



	if (count($payments)) {
		$paymentsTEXTS['instant'] = 'Единовременная оплата в размере ';
		$paymentsSTR = [];
		$paymentsSumm = 0;
		foreach ($payments as $payment) {
			$paymentsSTR[] = ($payment['f_paymentsAmount']) . 'р. (' . number2string($payment['f_paymentsAmount']) . ', ' . $payment['f_paymentsTypesName'] . ')';
			$paymentsSumm += $payment['f_paymentsAmount'];
		}
		$paymentsTEXTS['instant'] .= implode(',', $paymentsSTR) . '.';
	}
}

//printr($paymentsTEXTS);
$data['type'] = implode(' А также: ', $paymentsTEXTS);
$data['SummaItog'] = $total;



foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}
$templateProcessor->cloneRowAndSetValues('rn', $rows);

//printr($rows);
//    [0] => Array
//        (
//            [rownumber] => 1
//            [servicename] => Плазмогель
//            [servicebumber] => 10
//            [serviceprice] => 5000
//            [servicesumm] => 50000
//        )
//printr($data);
//    [NomerDog] => 640.202006101435
//    [DayDog] => 10
//    [MesDog] => Июня
//    [GodDog] => 2020
//    [fioPokupatelya] => Данченко Татьяна Георгиевна
//    [fioPokupatelya2] => Данченко Т. Г.
//    [chislo1] => 5
//    [mes1] => Декабря
//    [god1] => 1949
//    [mestorojd] => пос. Красноселькупск  Красноселькупского р-на, Тюменской обл.
//    [seriya] => 4009
//    [Nomer] => 986124
//    [Kemvidan] => тп №73 отдела УФМС России по Санкт-Петербургу и Ленинградской обл. в Фрунзенском р-не гор. Санкт-Петербурга
//    [AdresReg] => гор. Санкт-Петербург, р-н Фрунзенский, пр. Славы 30 корп. 1 кв. 123
//    [AdresFakt] => гор. Санкт-Петербург, р-н Фрунзенский, пр. Славы 30 корп. 1 кв. 123
//    [telefon] => 89602618316
//    [procend] => 2022-06-10
//    [installmentdate] => 2020-07-10
//    [ProcentSummaKredit] => 50000
//    [type] => В Кредит на сумму 35000р. (тридцать пять тысяч рублей) в банке ФИЛИАЛ "САНКТ-ПЕТЕРБУРГСКИЙ" АО "АЛЬФА-БАНК" г. Санкт-Петербург. А также: Единовременная оплата в размере 15000р. (пятнадцать тысяч рублей, Безналичные).
//    [SummaItog] => 50000





















header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . date("Y.m.d") . ' - ' . $data['fioPokupatelya'] . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');



