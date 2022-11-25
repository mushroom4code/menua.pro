<?php

ini_set('memory_limit', '1G');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

mysqlQuery("UPDATE vita.f_sales set f_salesUUID = (SELECT uuid()) where isnull(f_salesUUID);");
mysqlQuery("UPDATE vita.clients set clientsUUID = (SELECT uuid()) where isnull(clientsUUID);");

$DATABASE = 'vita';
//`$DATABASE`.
$sales = query2array(mysqlQuery("SELECT "
				. "`idf_sales`,"
				. "`f_salesNumber`,"
				. "`f_salesCreditManager`,"
				. "`f_salesSumm`,"
				. "`f_salesComment`,"
				. "`f_salesTime`,"
				. "`f_salesDate`,"
				. "`f_salesType`,"
				. "`f_salesCancellationDate`,"
				. "`f_salesCancellationSumm`,"
				. "`f_salesEntity`,"
				. "`f_salesUUID`,"
				. "`clientsUUID`"
				. " FROM"
				. " `$DATABASE`.`f_sales` "
				. "LEFT JOIN `$DATABASE`.`clients` ON (`idclients`=`f_salesClient`)"
				. ""));

/*            "GUID": "dc7fb01f-e3c7-11e7-aaa0-2c768a5d8193",
  "": "Грязнова",
  "": "Людмила",
  "": "Николаевна",
  "": "1943-09-14",
  "": null,
  "clientsAddedBy": 176,
  "clientsAddedAt": "2021-02-05 07:18:43",
  "": null,
  "clientsCallerId": null,
  "clientsCallerAdmin": null,
  "clientsSource": 4,
  "clientsOldSince": "2017-12-18",
  "clientsControl": null,
  "clientsPassedAway": null,
  "clientsPassedAwayBy": null,
  "clientsHash": null,
  "clientsTIN": null,
  "clientsDatabase": null,
  "clientsContractDate": null,
  "clientscQR": null,
  "clientscQRset": null,
  "clientsTG": null,
  "clientsUUID": "36a53e1a-c61f-11ec-a5a3-a4bb6dd075fa" */




header('Content-disposition: attachment; filename=sales_export ' . date("y-m-d H-i-s") . '.json');
header('Content-type: application/json');

print(json_encode([
			'sales' => $sales,
				], 288 + 128));
