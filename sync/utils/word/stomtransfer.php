<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';

$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . mres($_GET['client'] ?? false) . "'"));

if (!$client) {
	die('client not found');
}

$passport = mfa(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "')"));
$phones = mfa(mysqlQuery("SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ') AS `phones` FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`);"))['phones'] ?? 'не найдено';

$sales = query2array(mysqlQuery("SELECT 
             
   *,(SELECT 
                     SUM(`qty`) as `stomRemainsSum`
                    FROM
                        (SELECT 
                            SUM(`qty`) AS `qty`
                        FROM
                            (SELECT 
                            `f_salesContentService` AS `service`,
                                `f_salesContentQty` AS `qty`,
                                `f_salesContentPrice` AS `price`
                        FROM
                            `f_subscriptions`
                        WHERE
                            `f_subscriptionsContract` = `idf_sales` UNION ALL SELECT 
                            `servicesAppliedService` AS `service`,
                                - `servicesAppliedQty` AS `qty`,
                                `servicesAppliedPrice` AS `price`
                        FROM
                            `servicesApplied`
                        WHERE
                            `servicesAppliedContract` = `idf_sales`
							AND isnull(`servicesAppliedDeleted`)
							AND NOT isnull(`servicesAppliedFineshed`)
							) AS `services`
                        GROUP BY `service` , `price`) AS `presum`)  AS `stomRemains`
        FROM
            `f_sales`
        WHERE
            `f_salesClient` = '" . $client['idclients'] . "'
                AND `f_salesEntity` = 2 and isnull(`f_salesCancellationDate`) having `stomRemains`>0;"));
$salesAndServices = '';

foreach ($sales as $sale) {
	$n = 0;
	$salesAndServices .= '№' . ($sale['f_salesNumber'] ?? 'ID' . $sale['idf_sales']) . '[' . $sale['idf_sales'] . '] от ' . date("d.m.Y", strtotime($sale['f_salesDate'])) . 'г. В рамках данного Договора ООО «ИНФИНИТИ СТОМ» обязуется оказать следующие услуги: <w:br/>';
	foreach (query2array(mysqlQuery("SELECT servicesName,qty FROM (SELECT 
    SUM(`qty`) AS `qty`, `service`
FROM
    (SELECT 
        `f_salesContentService` AS `service`,
            `f_salesContentQty` AS `qty`,
            `f_salesContentPrice` AS `price`
    FROM
        `f_subscriptions`
    WHERE
        `f_subscriptionsContract` = " . $sale['idf_sales'] . " UNION ALL SELECT 
        `servicesAppliedService` AS `service`,
            - `servicesAppliedQty` AS `qty`,
            `servicesAppliedPrice` AS `price`
    FROM
        `servicesApplied`
    WHERE
        `servicesAppliedContract` = " . $sale['idf_sales'] . "
							AND isnull(`servicesAppliedDeleted`)
							AND NOT isnull(`servicesAppliedFineshed`)			
) AS `services`
GROUP BY `service` , `price`) as `preselect`
left join `services` ON (idservices = `preselect`.`service`)
 where qty>0;")) as $service) {
		$n++;
		$salesAndServices .= $n . ') ' . $service['servicesName'] . ' (' . $service['qty'] . 'шт)<w:br/>';
	}
	$salesAndServices .= '<w:br/>';
}

//№ _____________________ от «____» _________ ____г. В рамках данного Договора ООО «ИНФИНИТИ СТОМ» обязуется оказать следующие услуги: ____________________________________________________________________.


$data = [
	'clientsFullName' => $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'],
	'clientsAddress' => ($passport['clientsPassportsRegistration'] ?? $passport['clientsPassportsResidence']),
	'clientsPhone' => $phones,
	'clientsEmail' => '',
	'curdate' => date("d.m.Y"),
	'salesAndServices' => $salesAndServices ?? ''
];
$file = 'stomtransfer.docx';

$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/' . $file);

foreach ($data as $variable => $value) {
	$templateProcessor->setValue($variable, $value);
}


header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . date("Y.m.d") . ' - ' . $data['clientsFullName'] . '.docx"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$templateProcessor->saveAs('php://output');
