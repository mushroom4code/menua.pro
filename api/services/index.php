<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

header("Content-type: application/json; charset=utf8");

$servicesAll = query2array(mysqlQuery("SELECT *,(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
(PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
 SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 1) as `minPrice` FROM `services`
 WHERE
 (`servicesEntryType`=1) OR (NOT isnull(`servicesEntryType`) &&  NOT isnull(`servicesParent`))

 AND isnull(`servicesDeleted`)"));

$OUTtree = adjArr2obj($servicesAll, 'idservices', 'servicesParent', 'descendants');
//$OUT['$servicesAll'] = $servicesAll;

$OUT = treeRebuild($OUTtree);

function treeRebuild($services) {
  $out = [];
  foreach ($services as $service) {
	 if ($service['descendants'] ?? false) {
		$out[$service['servicesName']] = treeRebuild($service['descendants']);
	 } else {
		if ($service['servicesEntryType'] == 2) {
		  $out[$service['servicesName']] = $service['minPrice'];
		}
	 }
  }
  return $out;
}

exit(json_encode($OUT ?? [], 288));
