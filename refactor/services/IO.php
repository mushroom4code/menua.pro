<?

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (($_JSON['action'] ?? false) == "getItemsTree") {
  $services = query2array(mysqlQuery(""
  . " SELECT *,"
  . "(SELECT COUNT(1) FROM `services` AS `s2` WHERE `s2`.`servicesParent` = `s1`.`idservices`".(($_JSON['displayDeletedServices'] == false) ? " AND  isnull(`s2`.`servicesDeleted`)" : "")." AND `servicesEntryType` IN (2,3,4)) AS `count` "
  .",(SELECT COUNT(1) FROM `services` WHERE isnull(`servicesParent`)".(($_JSON['displayDeletedServices'] == false) ? " AND  isnull(`servicesDeleted`)" : "")." AND `servicesEntryType` IN (2,3,4)) AS `countWithoutParent`"
  . " FROM `services` AS `s1`"
  . " WHERE `s1`.`servicesEntryType` = 1"
  ." AND isnull(`s1`.`servicesDeleted`)"
  . " ORDER BY `s1`.`servicesName`"));

  $tree = adjArr2obj($services, 'idservices', 'servicesParent', 'descendants');
  $tree = obj2array($tree);
  exit(json_encode(['dispdel'=>'', 'tree'=>$tree], 288));
}



if (($_JSON['action'] ?? false) == "addService") {
  if (($_JSON['newServicesName'] ?? false) && (($_JSON['newServicesName'] ?? false) || ($_JSON['newServicesEntryType'] === '0')) && ($_JSON['activeService'] ?? false)) {
	 // exit(json_encode(['action' => $_JSON['action'], 'newServicesName' => $_JSON['newServicesName'],'newServiceEntryType' => $_JSON['newServicesEntryType']], 288));
	 $result = mysqlQuery("INSERT INTO services (servicesName, servicesEntryType, servicesParent) VALUES ('" . mres(trim($_JSON['newServicesName'])) . "', '" . mres(trim($_JSON['newServicesEntryType'])) . "', '" . mres(trim($_JSON['activeService'])) . "');");
	 exit(json_encode(['result' => $result, 'action' => $_JSON['action'], 'newServicesName' => $_JSON['newServicesName'], 'newServiceEntryType' => $_JSON['newServicesEntryType'], 'activeService' => $_JSON['activeService']], 288));
  }
  exit(json_encode(['error' => 'error', 'newServicesEntryType' => ($_JSON['newServicesEntryType'] === '0')], 288));
}



if (($_JSON['action'] ?? false) == "changeEntryType") {
  if (($_JSON['newServicesEntryType'] ?? false) && ($_JSON['activeService'] ?? false)) {
	 $result = mysqlQuery("UPDATE `services` SET `servicesEntryType`='" . mres(trim($_JSON['newServicesEntryType'])) . "' WHERE `idservices`='" . mres(trim($_JSON['activeService'])) . "'");
   exit(json_encode(['result' => $result, 'action' => $_JSON['action'], 'activeService' => $_JSON['activeService'], 'newServiceEntryType' => $_JSON['newServicesEntryType']], 288));
  }
  exit(json_encode(['error' => 'error', 'action' => $_JSON['action'], 'newEntryType' => $_JSON['newServiceEntryType']], 288));
}



if (($_JSON['action'] ?? false) == "addGUID") {
  if (($_JSON['newGUID'] ?? false) && ($_JSON['activeService'] ?? false)) {
	 $result = mysqlQuery("INSERT INTO `servicesGUIDs` (`servicesGUIDsService`, `servicesGUIDsGUID`) VALUES ('".mres(trim($_JSON['activeService']))."', '".mres(trim($_JSON['newGUID']))."')");
   exit(json_encode(['result' => $result, 'action' => $_JSON['action'], 'activeService' => $_JSON['activeService'], 'newGUID' => $_JSON['newGUID']], 288));
  }
  exit(json_encode(['error' => 'error', 'action' => $_JSON['action'], 'activeService' => $_JSON['activeService'], 'newGUID' => $_JSON['newGUID']], 288));
}



if (($_JSON['action'] ?? false) == "changeName") {
  if (($_JSON['newName'] ?? false) && ($_JSON['activeService'] ?? false)) {
	 $result = mysqlQuery("UPDATE `services` SET `servicesName`='" . mres(trim($_JSON['newName'])) . "' WHERE `idservices`='" . mres(trim($_JSON['activeService'])) . "'");
   exit(json_encode(['result' => $result, 'action' => $_JSON['action'], 'activeService' => $_JSON['activeService'], 'newName' => $_JSON['newName']], 288));
  }
  exit(json_encode(['error' => 'error', 'action' => $_JSON['action'], 'activeService' => $_JSON['activeService'], 'newName' => $_JSON['newName']], 288));
}



if (($_JSON['action'] ?? false) == "changeShortName") {
  if (($_JSON['newShortName'] ?? false) && ($_JSON['activeService'] ?? false)) {
	 $result = mysqlQuery("UPDATE `services` SET `serviceNameShort`='" . mres(trim($_JSON['newShortName'])) . "' WHERE `idservices`='" . mres(trim($_JSON['activeService'])) . "'");
   exit(json_encode(['result' => $result, 'action' => $_JSON['action'], 'activeService' => $_JSON['activeService'], 'newShortName' => $_JSON['newShortName']], 288));
  }
  exit(json_encode(['error' => 'error', 'action' => $_JSON['action'], 'activeService' => $_JSON['activeService'], 'newShortName' => $_JSON['newShortName']], 288));
}



if (($_JSON['drag_idservices'] ?? false) && ($_JSON['drop_idservices'] ?? false)) {
  $result = mysqlQuery("UPDATE `services` SET `servicesParent`=" . sqlVON(($_JSON['drop_idservices'] ?? ''), 1) . " WHERE `idservices`='" . mres($_JSON['drag_idservices']) . "'");
  $response = 'OK ' . $_JSON['drag_idservices'] . '  ' . $_JSON['drop_idservices'] . '      ' . $result;
  exit(json_encode($response, 288));
}



if (($_JSON['getBy_idservices'] ?? false) || ($_JSON['getBy_idservices'] === 0)) {
// Получить элемент по идентификатору
//предпологаемая структура ответа:

  /*
	 {breadcrumbs:[
	 {idservices,servicesName}, //первым элементом идёт корневой раздел
	 {idservices,servicesName},
	 {idservices,servicesName}... // последний элемент родитель данной услуги/папки
	 ],
	* services: [
	* массив услуг родителем которых является текущий элемент
	* ],
	* service: {
	* Объект данной услуги
	* }
	* 
	* }
	*/

  $query = "
  SELECT *,(SELECT `servicesDescriptionsDescription` FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM `servicesDescriptions` WHERE`servicesDescriptionsService` = `idservices`)) as `serviceDescription`
  ,(SELECT COUNT(1) FROM `servicesPrimecost` WHERE `servicesPrimecostService` = `idservices`) as `PCqty`"
          ." ,(SELECT `serviceMotivationMotivation` FROM `serviceMotivation` WHERE `serviceMotivationService` = `idservices`) AS `serviceMotivationMotivation`"
          . " , GREATEST((SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`),(SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = `idservices`)) AS `personal`"
          . " ,(SELECT COUNT(1) FROM `servicesApplied` WHERE `servicesAppliedService` = `idservices`) as `servicesApplied`"
          . " ,(SELECT COUNT(1) FROM `f_subscriptions` WHERE `f_salesContentService` = `idservices`) as `f_subscriptions`"
          . " ,(SELECT COUNT(1) FROM `servicesEquipment` WHERE `servicesEquipmentService` = `idservices`) as `servicesEquipmentQty`"
          . " ,(SELECT COUNT(1) FROM `servicesGUIDs` WHERE `servicesGUIDsService` = `idservices`) as `GUIDsQty`" . "
    ,(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
  (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 1) as `minPrice`"
          . ",(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
  (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 2) as `maxPrice`"
          . ",(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
  (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 3) as `minCost`"
          . ",(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
  (PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
    SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 4) as `maxCost`
    FROM `services`";


  $service = mfa(mysqlQuery($query." WHERE `idservices` = '" . mres($_JSON['getBy_idservices']) . "'"));

  if($service['idservices'] ?? false) {
      $p = []; //prices
		  for ($pindex = 1; $pindex <= 7; $pindex++) {
			 $p[$pindex] = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . $service['idservices'] . "' AND `servicesPricesType`='$pindex') AND `servicesPricesType`='$pindex'  AND `servicesPricesService` = '" . $service['idservices'] . "')"));
		  }
      $service['pricesList'] = $p;

      $GUIDs = query2array(mysqlQuery("SELECT * FROM `servicesGUIDs` WHERE `servicesGUIDsService` = '" . $service['idservices'] . "'"));
      $service['GUIDs'] = $GUIDs;
  }

  if (
			 (($service['servicesEntryType'] ?? false) == 1) ||
			 (isset($_JSON['getBy_idservices']) && $_JSON['getBy_idservices'] === 0)
  ) {
	 $services = query2array(mysqlQuery($query."
		  WHERE
        "
						  . (($_JSON['getBy_idservices'] == 0) ? (" isnull(`servicesParent`) AND `idservices` != '1'") : (" `servicesParent` = '" . $service['idservices'] . "'"))
						  . ($_JSON['displayDeletedServices'] ? "" : "AND isnull(`servicesDeleted`)")
						  . " ORDER BY `servicesEntryType` DESC"));
  } else {
	 
  }


  $breadcrumbs = [];

  if (!empty($service)) {

	 $parent = $service['servicesParent'];

	 while ($parent) {
		$bcservice = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . $parent . "'"));
		$parent = $bcservice['servicesParent'];

		array_unshift($breadcrumbs, [
			 'idservices' => $bcservice['idservices'],
			 'servicesName' => $bcservice['servicesName']]);
	 }
  } else {

	 array_unshift($breadcrumbs, [
		  'idservices' => 1,
		  'servicesName' => "Корневой раздел"
	 ]);
  }

  $serviceTypes = query2array(mysqlQuery("SELECT * FROM `warehouse`.`servicesTypes`"));
  $servicesEntryTypes = query2array(mysqlQuery("SELECT * FROM `warehouse`.`servicesEntryTypes`"));
  $servicesMotivations = query2array(mysqlQuery("SELECT * FROM `servicesMotivations`"));
  $equipment = query2array(mysqlQuery("SELECT * FROM `equipment` WHERE isnull(`equipmentDeleted`) ORDER BY `equipmentName`"));
  $testsReferrals = query2array(mysqlQuery("SELECT * FROM `testsReferrals` ORDER BY `testsReferralsName`"));
  // В зависимости от типа servicesEntryType находим
  // если servicesEntryType = 1 то нужно получить ещё список всех услуг, которые относятся к этому РОДИТЕЛЮ
  die(json_encode([
		'breadcrumbs' => $breadcrumbs ?? [],
		'service' => $service ?? [],
		'services' => $services ?? [],
    'serviceTypes' => $serviceTypes ?? [],
    'servicesEntryTypes' => $servicesEntryTypes ?? [],
    'servicesMotivations' => $servicesMotivations ?? [],
    'equipment' => $equipment ?? [],
    'testsReferrals' => $testsReferrals ?? []
						], 288));
}



if ($_JSON['allServicesBySearch'] ?? false) {
  $serviceTypes = query2array(mysqlQuery("SELECT * FROM services WHERE servicesParent = 1;"));

  exit(json_encode($serviceTypes, 288));
}



if (($_JSON['action'] ?? '') === 'saveprice') {
	$errors = [];
	$output = [];
	if (is_array($_JSON['p'] ?? false)) {
		foreach ($_JSON['p'] as $type => $price) {
			$oldprice = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE "
							. " `idservicesPrices` = ("
							. "							SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM `servicesPrices` WHERE `servicesPricesService` = " . sqlVON($_JSON['service']) . ""
							. " AND `servicesPricesType` = " . sqlVON($type) . " ) AND `servicesPricesService` = " . sqlVON($_JSON['service']) . ""
							. " AND `servicesPricesType` = " . sqlVON($type) . "  )"
							. " "));

//			$output['msgs'][] = round(($oldprice['servicesPricesPrice'] ?? 0), 2) . ' = ' . round($price, 2);

			if (round(($oldprice['servicesPricesPrice'] ?? 0), 2) != round($price, 2)) {
				if (!mysqlQuery("INSERT INTO `servicesPrices` SET "
								. " `servicesPricesService` = " . sqlVON($_JSON['service']) . ","
								. " `servicesPricesPrice` = " . sqlVON($price, 1) . ","
								. " `servicesPricesType`=" . sqlVON($type) . ","
								. " `servicesPricesSetBy` = " . $_USER['id'] . " "
						)) {
					$errors[] = mysqli_errno($link);
				}
			}
		}//foreach
		if (!count($errors)) {
			$output['success'] = true;
			$output['msgs'][] = ['type' => 'success', 'text' => 'ok', 'autoDismiss' => 500];

			print json_encode($output, 288);
		} else {
			print json_encode(['success' => false, 'errors' => $errors, 'msgs' => [['type' => 'error', 'text' => mysqli_error($link)]]], 288);
		}
	}
}
