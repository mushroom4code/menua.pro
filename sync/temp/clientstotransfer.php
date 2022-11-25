<?php

$pageTitle = 'сетка';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

//idLT, LTtype, LTid, LTuser, LTdate, LTfrom, LTto, LTresult, LTset
$clients = query2array(mysqlQuery("SELECT 
    idclients,
    clientsLName,
    clientsFName,
    clientsMName,
    idf_sales,
    f_salesDate,
    f_salesSumm
FROM
    vita.f_sales
        LEFT JOIN
    `vita`.`clients` ON (idclients = f_salesClient)
WHERE
    idf_sales IN (22906 , 20762,
        12690,
        316,
        322,
        6705,
        11984,
        12402,
        12826,
        15793,
        20374,
        2598,
        22340,
        22123,
        23100,
        4674,
        11840,
        13478,
        15157,
        21671,
        22406,
        16535,
        16536,
        16538,
        16539,
        20539,
        16841,
        16842,
        16843,
        13870,
        14174,
        15541,
        19709,
        20532,
        21103,
        21137,
        11653,
        13300,
        22744,
        23035,
        23157)"));
foreach ($clients as $clientsIndex => $client) {
	//idf_subscriptions, f_subscriptionsContract, f_salesContentService, f_salesContentPrice, f_salesContentQty, f_subscriptionsDate, f_subscriptionsUser, f_subscriptionsExpDate, f_subscriptionsImport
	$clients[$clientsIndex]['remains'] = array_filter(query2array(mysqlQuery("SELECT subscriptions.*, servicesVAT FROM (SELECT f_salesContentService,f_salesContentPrice, sum(f_salesContentQty) as f_salesContentQty FROM vita.f_subscriptions  WHERE f_subscriptionsContract = " . $client['idf_sales'] . " group by f_salesContentService,f_salesContentPrice ) as `subscriptions` LEFT JOIN vita.services ON (idservices = f_salesContentService) ")), function ($elem) {
		return $elem['f_salesContentQty'] > 0;
	});
}


printArray($clients);
?>

