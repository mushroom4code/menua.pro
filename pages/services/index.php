<?php
$load['title'] = $pageTitle = 'Услуги';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176 && $_POST) {
//	printr($_POST, 1);
//	die();
}

function exportCSV($rows = false) {
  if (!empty($rows)) {
	 $name = date("YmdHis") . ".csv";
	 header('Content-Type: text/csv; charset=utf-8');
	 header('Content-Disposition: attachment; filename=' . $name);
	 $output = fopen('php://output', 'w');
	 fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
	 foreach ($rows as $row) {
		if (!is_array($row)) {
		  $row = [$row];
		}
		fputcsv($output, $row, ';');
	 }
	 exit();
  }
  return false;
}

$servicesSQL = "SELECT *,"
		  . "  (SELECT COUNT(1) FROM `servicesPrimecost` WHERE `servicesPrimecostService` = `idservices`) as `PCqty`"
		  . " , GREATEST((SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`),(SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = `idservices`)) AS `personal`"
		  . " ,(SELECT COUNT(1) FROM `servicesApplied` WHERE `servicesAppliedService` = `idservices`) as `servicesApplied`"
		  . " ,(SELECT COUNT(1) FROM `f_subscriptions` WHERE `f_salesContentService` = `idservices`) as `f_subscriptions`"
		  . " ,(SELECT COUNT(1) FROM `servicesEquipment` WHERE `servicesEquipmentService` = `idservices`) as `servicesEquipmentQty`"
		  . " ,(SELECT COUNT(1) FROM `servicesGUIDs` WHERE `servicesGUIDsService` = `idservices`) as `GUIDsQty`"
		  . " ,(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
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
 SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 4) as `maxCost`"
		  . " FROM `services`"
		  . " LEFT JOIN `servicesDescriptions` ON (`servicesDescriptionsService` = `idservices`)"
		  . " LEFT JOIN `warehouse`.`servicesTypes` ON (`idservicesTypes` = `servicesType`)"
		  . " WHERE"
		  . " NOT isnull(`idservices`)"
		  . " AND (isnull(`servicesEntryType`) OR `servicesEntryType`<>1) "
		  . (isset($_GET['search']) ? '' : 'AND isnull(`servicesDeleted`)')
		  . (isset($_GET['search']) ? '' : ((isset($_GET['type']) ? ($_GET['type'] === 'all' ? '' : (" AND `servicesType` = " . FSI($_GET['type'])) ) : (" AND isnull(`servicesType`)"))))
		  . (isset($_GET['search']) ? (" AND (`servicesName` LIKE  '%" . mres($_GET['search']) . "%' OR `serviceNameShort` LIKE  '%" . mres($_GET['search']) . "%' )" ) : '')
		  . "  "
//									. "AND ("
//									. "((SELECT COUNT(1) FROM `f_subscriptions` WHERE `f_salesContentService` = `idservices`)>0)"
//									. " OR "
//									. "((SELECT COUNT(1) FROM `servicesApplied` WHERE `servicesAppliedService` = `idservices`)>0)"
//									. ")"
		  . "";
//print $servicesSQL;
$services = query2array(mysqlQuery($servicesSQL));
if (isset($_GET['save'])) {
  exportCSV(array_map(function ($el) {
				return [
		  $el['idservices'],
		  $el['servicesName'],
		  $el['minPrice'],
		  $el['maxPrice'],
				];
			 }, $services), 1);
}

/*
  {
  "idservices": 9,
  "servicesParent": null,
  "servicesCode": null,
  "servicesName": "Восстановление зуба виниром, полукоронкой E-max",
  "serviceNameShort": "Винир, полукоронка E-max",
  "servicesType": 4,
  "servicesDeleted": null,
  "servicesEquipment": null,
  "servicesDuration": 60,
  "servicesURL": "",
  "servicesAdded": null,
  "servicesEquipped": 1,
  "servicescolN804": "A16.07.003.001",
  "servicesSupplierCode": null,
  "servicesEntryType": 2,
  "servicesNewPlan": null,
  "servicesVat": 0,
  "servicesAddedBy": null,
  "idservicesTypes": 4,
  "servicesTypesName": "Стоматология",
  "PCqty": 41,
  "personal": 2,
  "servicesApplied": 11,
  "f_subscriptions": 6,
  "servicesEquipmentQty": 1,
  "GUIDsQty": 0,
  "minPrice": 25000,
  "maxPrice": 30000,
  "minCost": 2500,
  "maxCost": null
  },
 */

//printr($services, 1);

if (
		  (isset($_GET['deleteConsumable']))
) {
  mysqlQuery("DELETE FROM `servicesPrimecost` WHERE `idservicesPrimecost` = '" . mysqli_real_escape_string($link, $_GET['deleteConsumable']) . "'");
  header("Location: " . GR('deleteConsumable'));
  die();
}


if (
		  (isset($_POST['copyFrom']))
) {
  //
  mysqlQuery("insert into `servicesPrimecost` (`servicesPrimecostService`, `servicesPrimecostNomenclature`, `servicesPrimecostNomenclatureQty`, `servicesPrimecostIsOptional`,`servicesPrimecostVariable`, `servicesPrimecostMultiply`)
select '" . mres($_GET['service']) . "' as `servicesPrimecostService`, `servicesPrimecostNomenclature`, `servicesPrimecostNomenclatureQty`, `servicesPrimecostIsOptional`,`servicesPrimecostVariable`, `servicesPrimecostMultiply` FROM `servicesPrimecost`  where `servicesPrimecostService` = '" . mres($_POST['copyFrom']) . "';");
  header("Location: " . GR());
  die();
}



if (
		  (isset($_GET['serviceDone']) && isset($_GET['service']))
) {
  mysqlQuery("UPDATE `services` SET `servicesEquipped` =" . (($_GET['serviceDone'] ?? '') == 'true' ? "'1'" : "null") . " WHERE `idservices` = '" . mres($_GET['service']) . "'");
  header("Location: " . GR('serviceDone'));
  die();
}

if (
		  (isset($_GET['deleteEquipment']))
) {
  mysqlQuery("DELETE FROM `servicesEquipment` WHERE `servicesEquipmentService` = '" . mysqli_real_escape_string($link, $_GET['service']) . "' AND `servicesEquipmentEquipment` = '" . mysqli_real_escape_string($link, $_GET['deleteEquipment']) . "'");
  header("Location: " . GR('deleteEquipment'));
  die();
}

if (
		  ($_GET['action'] ?? '') == 'addNewConsumable' && isset($_GET['nomenclature']) && isset($_GET['qty']) && isset($_GET['service'])
) {

//	idservicesPrimecost, servicesPrimecostService, servicesPrimecostNomenclature, servicesPrimecostNomenclatureQty

  mysqlQuery("INSERT INTO `servicesPrimecost` SET"
			 . " `servicesPrimecostService` = '" . mysqli_real_escape_string($link, $_GET['service']) . "',"
			 . " `servicesPrimecostNomenclature` = '" . mysqli_real_escape_string($link, $_GET['nomenclature']) . "',"
			 . " `servicesPrimecostNomenclatureQty` = '" . mysqli_real_escape_string($link, $_GET['qty']) . "'"
			 . "");

  header("Location: " . GR2(['action' => null, 'nomenclature' => null, 'qty' => null]));
  die();
}
if (
		  ($_GET['action'] ?? '') == 'addNewEquipment' && isset($_GET['equipment']) && isset($_GET['service'])
) {
  mysqlQuery("INSERT IGNORE INTO `servicesEquipment` SET"
			 . " `servicesEquipmentService` = '" . mysqli_real_escape_string($link, $_GET['service']) . "',"
			 . " `servicesEquipmentEquipment` = '" . mysqli_real_escape_string($link, $_GET['equipment']) . "'"
			 . "");

  header("Location: " . GR2(['action' => null, 'equipment' => null]));
  die();
}


if (R(28)) {
//	printr($_POST);
//	die();
//Array
//(
//    [idservices] => 83
//    [servicesName] => Аква Релакс
//    [servicesGUID] =>
//    [serviceType] => 1
//    [servicesEquipment] =>
//    [servicesBasePrice] => 3500
//    [servicesCost] =>
//    [priceType1] => 3500
//    [priceType2] =>
//    [priceType3] =>
//    [priceType4] =>
//    [servicesDuration] => 60
//)~

  if (isset($_POST['idservices']) && isset($_POST['servicesName']) && isset($_POST['serviceType'])) {
	 $serviceBefore = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($_POST['idservices']) . "'"));

	 $p1 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . FSI($_POST['idservices']) . "' AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = '" . FSI($_POST['idservices']) . "')"));
	 $p2 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . FSI($_POST['idservices']) . "' AND `servicesPricesType`='2') AND `servicesPricesType`='2'  AND `servicesPricesService` = '" . FSI($_POST['idservices']) . "')"));
	 $p3 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . FSI($_POST['idservices']) . "' AND `servicesPricesType`='3') AND `servicesPricesType`='3'  AND `servicesPricesService` = '" . FSI($_POST['idservices']) . "')"));
	 $p4 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . FSI($_POST['idservices']) . "' AND `servicesPricesType`='4') AND `servicesPricesType`='4'  AND `servicesPricesService` = '" . FSI($_POST['idservices']) . "')"));

	 if (isset($_POST['priceType1']) && $_POST['priceType1'] !== ($p1['servicesPricesPrice'] ?? '')) {
		mysqlQuery("INSERT INTO `servicesPrices` SET `servicesPricesService`= '" . FSI($_POST['idservices']) . "',  servicesPricesPrice=" . ($_POST['priceType1'] ? FSI($_POST['priceType1']) : 'null') . ", `servicesPricesType` = '1', `servicesPricesSetBy`='" . $_USER['id'] . "'");
//			print 'p1';
	 }
	 if (isset($_POST['priceType2']) && $_POST['priceType2'] !== ($p2['servicesPricesPrice'] ?? '')) {
		mysqlQuery("INSERT INTO `servicesPrices` SET `servicesPricesService`= '" . FSI($_POST['idservices']) . "',  servicesPricesPrice=" . ($_POST['priceType2'] ? FSI($_POST['priceType2']) : 'null') . ", `servicesPricesType` = '2', `servicesPricesSetBy`='" . $_USER['id'] . "'");
//			print 'p2';
	 }
	 if (isset($_POST['priceType3']) && ($_POST['priceType3'] ?? null) !== ($p3['servicesPricesPrice'] ?? '')) {
		mysqlQuery("INSERT INTO `servicesPrices` SET `servicesPricesService`= '" . FSI($_POST['idservices']) . "',  servicesPricesPrice=" . ($_POST['priceType3'] ? FSI($_POST['priceType3']) : 'null') . ", `servicesPricesType` = '3', `servicesPricesSetBy`='" . $_USER['id'] . "'");
//			print 'p3';
	 }
	 if (isset($_POST['priceType4']) && ($_POST['priceType4'] ?? null) !== ($p4['servicesPricesPrice'] ?? '')) {
//			print 'p4';
		mysqlQuery("INSERT INTO `servicesPrices` SET `servicesPricesService`= '" . FSI($_POST['idservices']) . "',  servicesPricesPrice=" . ($_POST['priceType4'] ? FSI($_POST['priceType4']) : 'null') . ", `servicesPricesType` = '4', `servicesPricesSetBy`='" . $_USER['id'] . "'");
	 }




//		die();
	 if (isset($_POST['servicesMotivations']) && is_array($_POST['servicesMotivations'])) {
		mysqlQuery("DELETE FROM `serviceMotivation` WHERE `serviceMotivationService`='" . FSI($_POST['idservices']) . "'");
		foreach ($_POST['servicesMotivations'] as $motivation => $value) {
		  if ($value) {
			 mysqlQuery("INSERT INTO `serviceMotivation` SET `serviceMotivationService`='" . FSI($_POST['idservices']) . "', `serviceMotivationMotivation` = '" . mres($motivation) . "'");
		  }
		}
	 }

	 if (isset($_POST['servicesGUID']) && $_POST['servicesGUID'] !== '') {
		mysqlQuery("INSERT INTO `servicesGUIDs` SET "
				  . "`servicesGUIDsService` = '" . FSI($_POST['idservices']) . "',"
				  . "`servicesGUIDsGUID` = '" . $_POST['servicesGUID'] . "'");
	 }

	 if (mysqlQuery("UPDATE `services` SET  "
						  . "`servicesName` = " . sqlVON($_POST['servicesName'], 1) . ""
						  . ",`serviceNameShort` = " . sqlVON($_POST['serviceNameShort'], 1) . ""
						  . (($_POST['servicesCode'] ?? '') === '' ? (",`servicesCode` = NULL") : (",`servicesCode` = '" . mres($_POST['servicesCode']) . "'"))
						  . (($_POST['servicescolN804'] ?? '') === '' ? (",`servicescolN804` = NULL") : (",`servicescolN804` = '" . mres($_POST['servicescolN804']) . "'"))
						  . ", `servicesURL` = '" . mysqli_real_escape_string($link, $_POST['servicesURL']) . "'"
						  . ", `servicesNewPlan` = " . ($_POST['servicesNewPlan'] ? "'1'" : "null") . ""
//						. ", `servicesCost` = '" . FSI($_POST['servicesCost'] ? $_POST['servicesCost'] : 0) . "'"
						  . ", `servicesDuration`  = " . ($_POST['servicesDuration'] == '' ? 'null' : FSI($_POST['servicesDuration'])) . ""
						  . ", `servicesVat`  = " . ($_POST['servicesVat'] === '' ? 'null' : FSI($_POST['servicesVat'])) . ""
						  . ", `servicesType`  = " . ($_POST['serviceType'] === '' ? 'null' : FSI($_POST['serviceType'])) . ""
						  . ", `servicesEntryType`  = " . ($_POST['servicesEntryType'] === '' ? 'null' : FSI($_POST['servicesEntryType'])) . ""
						  . ", `servicesParent`  = " . ($_POST['servicesParent'] === '' ? 'null' : FSI($_POST['servicesParent'])) . ""
						  . ", `servicesEquipment`  = " . ($_POST['servicesEquipment'] == '' ? 'null' : FSI($_POST['servicesEquipment'])) . ""
						  . ", `servicesTestsReferral`  = " . ($_POST['testsReferral'] == '' ? 'null' : FSI($_POST['testsReferral'])) . ""
						  . " WHERE `idservices` = '" . FSI($_POST['idservices']) . "'")) {
		$servicesDescription = mfa(mysqlQuery("SELECT * FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM servicesDescriptions WHERE`servicesDescriptionsService` = '" . mres($_POST['idservices']) . "')"));
		printr($servicesDescription);
		if (isset($_POST['serviceDescription']) && ($servicesDescription ?? false) != $_POST['serviceDescription']) {
		  mysqlQuery("INSERT INTO `servicesDescriptions` SET "
					 . "`servicesDescriptionsService`='" . mres($_POST['idservices']) . "' ,"
					 . "`servicesDescriptionsDescription`= '" . mres($_POST['serviceDescription']) . "' ");
		}

//			. ", `serviceDescription` = " . sqlVON($_POST['serviceDescription'], 1) . ""
		$changes = [
			 "idservices" => $serviceBefore['idservices']
		];
		$serviceAfter = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($_POST['idservices']) . "'"));
		foreach ($serviceBefore as $key => $value) {
		  if ($value !== $serviceAfter[$key]) {
			 $changes[$key] = ("\"" . $value . "\"" . ' => ' . "\"" . $serviceAfter[$key] . "\"");
		  }
		}
		telegramSendByRights([177], 'Изменения в процедуре ' . json_encode(['changes' => $changes, 'author' => ['idusers' => $_USER['id'], 'lname' => $_USER['lname'], 'fname' => $_USER['fname']]], 288 + 128));
		header("Location: /pages/services/index.php?service=" . FSI($_POST['idservices']));
		die();
	 } else {
		die(mysqli_error($link));
	 }
  } elseif (isset($_POST['action']) && $_POST['action'] == 'addNew' && isset($_POST['servicesName']) && isset($_POST['serviceType'])) {
	 if (mysqlQuery("INSERT INTO `services` SET  "
						  . "`servicesName` = '" . FSS($_POST['servicesName']) . "'"
						  . ",`servicesAddedBy` = '" . $_USER['id'] . "'"
//						. ", `servicesBasePrice` = '" . FSI($_POST['servicesBasePrice'] ? $_POST['servicesBasePrice'] : 0) . "'"
//						. ", `servicesCost` = '" . FSI($_POST['servicesCost'] ? $_POST['servicesCost'] : 0) . "'"
						  . ", `servicesType`  = " . ($_POST['serviceType'] == '' ? 'null' : FSI($_POST['serviceType'])) . ""
				)) {
		$idservices = mysqli_insert_id($link);
		telegramSendByRights([177], 'Добавлена процедура ' . json_encode(['new' => mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . $idservices . "'")), 'author' => ['idusers' => $_USER['id'], 'lname' => $_USER['lname'], 'fname' => $_USER['fname']]], 288 + 128));
//			header("Location: /pages/services/index.php?service=" . FSI($_POST['idservices']));
		header("Location: /pages/services/index.php?service=" . $idservices);
		die();
	 } else {
		die(mysqli_error($link));
	 }
  }



  if (isset($_POST['deleteService'])) {
	 if (mysqlQuery("UPDATE `services` SET  "
						  . "`servicesDeleted` = CURRENT_TIMESTAMP"
						  . " WHERE `idservices` = '" . FSI($_POST['deleteService']) . "'")) {
		header("Location: /pages/services/index.php" . (isset($_GET['type']) ? ('?type=' . $_GET['type']) : '' ));
		die();
	 } else {
		die(mysqli_error($link));
	 }
  }
  if (isset($_POST['undeleteService'])) {
	 if (mysqlQuery("UPDATE `services` SET  "
						  . "`servicesDeleted` = NULL"
						  . " WHERE `idservices` = '" . FSI($_POST['undeleteService']) . "'")) {
		header("Location: " . GR());
		die();
	 } else {
		die(mysqli_error($link));
	 }
  }
}



include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(28)) {
  ?>E403R27<?
} else {
  ?>
  <style>
    .hoverhighlight div{
  	 padding: 3px 10px;
    }
    .hoverhighlight:nth-child(5n+2) div{
  	 background-color: #EEE;
    }
    .highlight div{
  	 background-color: sandybrown;
    }
    .hoverhighlight:hover div{
  	 background-color: pink;
    }
    .suggestions {
  	 position: absolute;
  	 width: auto;
  	 background-color: white;
  	 border: none;
  	 box-shadow: 0px 0px 10px hsla(0,0%,0%,0.3);
  	 border-radius: 4px;
  	 z-index: 10;
  	 list-style: none;
  	 white-space: nowrap;
  	 left: 0px;
  	 top: 25px;
    }
    .suggestions .red {
  	 color: red;
    }
    .suggestions span {
  	 color: gray;
    }
    .suggestions li {
  	 font-size: 0.8em;
  	 padding: 2px 10px;
  	 cursor: pointer;
    }
    .suggestions li .mask{
  	 position: absolute;
  	 top: 0px;
  	 left: 0px;
  	 width: 100%;
  	 height: 100%;
  	 z-index: 10;
    }

    .suggestions li .mask:hover{
  	 background-color:  hsla(0,0%,0%,0.1);
    }

    .suggestions li .pointed{
  	 background-color:  hsla(0,0%,0%,0.1);
    }

    .isActive {
  	 background-color: #d0FFd0 !important;
    }


  </style>
  <ul class="horisontalMenu">
	 <? if (R(155)) { ?><li><a href="?add" <?= (isset($_GET['add'])) ? ' class="activeButton"' : ''; ?>>Добавить</a></li><? } ?>
    <li><a href="?"<?= empty($_GET['type']) ? ' class="activeButton"' : ''; ?>>Без типа</a></li>
    <li><a href="?type=all"<?= (!empty($_GET['type']) && $_GET['type'] === 'all') ? ' class="activeButton"' : ''; ?>>Все</a></li>


	 <?
	 $serviceTypes = query2array(mysqlQuery("SELECT * FROM `warehouse`.`servicesTypes`"));
	 $servicesEntryTypes = query2array(mysqlQuery("SELECT * FROM `warehouse`.`servicesEntryTypes`"));
	 foreach ($serviceTypes AS $type) {
		?>
	   <li><a href = "?type=<?= $type['idservicesTypes']; ?>"<?= (!empty($_GET['type']) && $_GET['type'] === $type['idservicesTypes']) ? ' class="activeButton"' : ''; ?>><?= $type['servicesTypesName']; ?></a></li>
		<?
	 }
	 ?>
  </ul>
  <div id="vueapp">
    <div style=" display: inline-block; padding: 10px; background-color: white; border-radius: 10px;">
  	 <div>
  		<a href="<?= GR2(['save' => true]); ?>"><i class="fas fa-save"></i></a>
  	 </div>
    </div>
    <div style=" display: inline-block; padding: 10px; background-color: white; border-radius: 10px;">
  	 <div>
  		<input type="text" v-model="servicesSearchText" v-on:keyup="searchServices"  autocomplete="off"  placeholder="Поиск" id="serviceSearch" style="display: inline; width: auto;">
  		<ul class="suggestions">
  		  <li v-for="(suggestion,index) in suggestions" v-on:click="confirmSearch(index);">
  			 <span v-html="suggestion.servicesNameHighlighted"></span>
  			 <div v-bind:class="[{ 'pointed': suggestionsIndex==index }, 'mask']"></div>
  		  </li>
  		</ul>

  	 </div>
    </div>


  </div><script src="/sync/3rdparty/vue.min.js" type="text/javascript"></script>
  <script>
    var app = new Vue({
  	 el: '#vueapp',
  	 data: {
  		servicesSearchText: '',
  		suggestions: [],
  		suggestionsIndex: -1
  	 },
  	 methods: {
  		summByService: function (service) {
  		  return (service.qty || 0) * (service.price || 0);
  		},
  		confirmSearch: function (n) {
  		  delete(this.suggestions[n].servicesNameHighlighted);
  		  delete(this.suggestions[n].servicesDuration);
  		  GR({service: this.suggestions[n].idservices});
  		  console.log(this.suggestions[n]);
  		  this.resetSearch();
  		},
  		resetSearch: function () {
  		  this.servicesSearchText = '';
  		  this.suggestions = [];
  		  this.lastSuccessSearchLength = 0;
  		},

  		searchServices: function (event) {
  		  console.log(this.suggestionsIndex);
  		  if (event.keyCode === 8) {
  			 this.suggestions = [];
  		  }
  		  if (event.keyCode === 27) {
  			 this.resetSearch();
  			 return false;
  		  }
  		  if (event.keyCode === 38) {
  			 event.stopPropagation();
  			 event.preventDefault();
  			 if (this.suggestionsIndex > 0) {
  				this.suggestionsIndex--;
  			 } else {
  				this.suggestionsIndex = 0;
  			 }
  			 return false;
  		  }
  		  if (event.keyCode === 40) {
  			 event.stopPropagation();
  			 event.preventDefault();
  			 if (this.suggestionsIndex < this.suggestions.length - 1) {
  				this.suggestionsIndex++;
  			 } else {
  				this.suggestionsIndex = this.suggestions.length - 1;
  			 }
  			 return false;
  		  }
  		  if (event.keyCode === 13) {
  			 event.stopPropagation();
  			 event.preventDefault();
  			 if (this.suggestionsIndex > -1) {
  				this.confirmSearch(this.suggestionsIndex);
  			 } else {
  				GR({search: event.target.value, service: null});
  			 }
  			 return false;
  		  }

  		  if (event.target.value.length < 3) {
  			 this.suggestions = [];
  			 return false;
  		  }

  		  this.suggestionsIndex = -1;
  		  fetch('/sync/api/local/services/suggestions.php', {
  			 body: JSON.stringify({search: event.target.value}),
  			 credentials: 'include',
  			 method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
  		  }).then(result => result.text()).then(function (text) {
  			 try {
  				let jsn = JSON.parse(text);
  				if (jsn.success) {
  				  app.lastSuccessSearchLength = event.target.value.length;
  				  app.suggestions = jsn.services;
  				} else {

  				}
  			 } catch (e) {
  				console.log('no');
  				app.schedule = [];
  				console.log(e);
  			 }
  		  });
  		  console.log(event.target.value, event.keyCode);
  		}

  	 },
  	 mounted: function () {
  		this.$nextTick(function () {
  		  //			this.poolArray = (JSON.parse(window.localStorage.getItem('poolArray')) || []);
  		  //			this.call.smsTemplate = (JSON.parse(window.localStorage.getItem('smsTemplate')) || '');
  		});
  	 }
    });
  </script>
  <div class="box neutral">

    <div class="box-body">


  	 <div></div>
		<?
		if (isset($_GET['service'])) {
		  $service = mfa(mysqlQuery("SELECT *,"
								. "(SELECT `servicesDescriptionsDescription` FROM `servicesDescriptions` WHERE `idservicesDescriptions` = (SELECT MAX(idservicesDescriptions) FROM `servicesDescriptions` WHERE`servicesDescriptionsService` = `idservices`)) as `serviceDescription` "
								. " FROM `services` LEFT JOIN `warehouse`.`servicesTypes` ON (`idservicesTypes` = `servicesType`) WHERE `idservices` = '" . $_GET['service'] . "'"));

//				print $p1SQL;
		  $p = []; //prices
		  for ($pindex = 1; $pindex <= 7; $pindex++) {
			 $p[$pindex] = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . $service['idservices'] . "' AND `servicesPricesType`='$pindex') AND `servicesPricesType`='$pindex'  AND `servicesPricesService` = '" . $service['idservices'] . "')"));
		  }
//				printr($p);
//				$p1 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . $service['idservices'] . "' AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = '" . $service['idservices'] . "')"));
//				$p2 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . $service['idservices'] . "' AND `servicesPricesType`='2') AND `servicesPricesType`='2'  AND `servicesPricesService` = '" . $service['idservices'] . "')"));
//				$p3 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . $service['idservices'] . "' AND `servicesPricesType`='3') AND `servicesPricesType`='3'  AND `servicesPricesService` = '" . $service['idservices'] . "')"));
//				$p4 = mfa(mysqlQuery("SELECT * FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = '" . $service['idservices'] . "' AND `servicesPricesType`='4') AND `servicesPricesType`='4'  AND `servicesPricesService` = '" . $service['idservices'] . "')"));
		  ?>
	 	 <h2><?= $service['servicesName']; ?></h2>
	 	 <form action="/pages/services/index.php?service=<?= $service['idservices']; ?>" method="post">
	 		<input type="hidden" name="idservices" value="<?= $service['idservices']; ?>">
	 		<div style="display: inline-block;">
	 		  <div style="display: grid; grid-template-columns: auto auto; margin: 20px; grid-gap: 10px;">
	 			 <div>Наименование услуги:</div><div><input type="text" name="servicesName" value='<?= $service['servicesName']; ?>'></div>
	 			 <div>Сокращенное наименование услуги:</div><div><input type="text" name="serviceNameShort" value='<?= $service['serviceNameShort']; ?>'></div>

	 			 <div>GUID 1C</div><div>
					 <?
					 $GUIDs = query2array(mysqlQuery("SELECT * FROM `servicesGUIDs` WHERE `servicesGUIDsService` = '" . $service['idservices'] . "'"));
					 foreach ($GUIDs as $GUID) {
						?>
						<div><?= $GUID['servicesGUIDsGUID']; ?></div>

						<?
					 }
					 ?> 

	 				<input type="text" name="servicesGUID">
	 			 </div>
	 			 <div>КОД ручного ввода:</div><div><input type="text" name="servicesCode" value='<?= $service['servicesCode']; ?>'></div>
	 			 <div>код N804н:</div><div><input type="text" name="servicescolN804" value='<?= $service['servicescolN804']; ?>'></div>
	 			 <div>URL:</div><div>
	 				<input type="text" name="servicesURL" value="<?= $service['servicesURL']; ?>">
	 			 </div>
	 			 <div>Описание услуги:</div>
	 			 <div>
	 				<textarea style="width: 100%;" rows="5"  autocomplete="off" name="serviceDescription"><?= htmlspecialchars($service['serviceDescription'] ?? ''); ?></textarea>
	 			 </div>
	 			 <div>Проц.лист:</div><div><input type="hidden" name="servicesNewPlan" value="0"><input type="checkbox" name="servicesNewPlan" id="servicesNewPlan" value="1" <?= $service['servicesNewPlan'] ? 'checked' : ''; ?>><label for="servicesNewPlan">Формировать план лечения</label></div>
	 			 <div>Мотивационная система:</div><div>
					 <?
					 $motivations = query2array(mysqlQuery("SELECT * FROM `serviceMotivation` WHERE `serviceMotivationService` = '" . $service['idservices'] . "'"), 'serviceMotivationMotivation');
					 foreach (query2array(mysqlQuery("SELECT * FROM `servicesMotivations`")) as $motivation) {
						?>
						<div>

						  <input type="hidden" name="servicesMotivations[<?= $motivation['idservicesMotivations'] ?>]" value="0"><input type="checkbox" name="servicesMotivations[<?= $motivation['idservicesMotivations'] ?>]" id="servicesMotivations[<?= $motivation['idservicesMotivations'] ?>]" value="1" <?= ($motivations[$motivation['idservicesMotivations']] ?? false) ? 'checked' : ''; ?>><label for="servicesMotivations[<?= $motivation['idservicesMotivations'] ?>]"><?= $motivation['servicesMotivationsName']; ?></label>
						</div>
						<?
					 }
					 ?>
	 			 </div>

	 			 <div>Раздел (устарело):</div><div><select name="serviceType" autocomplete="off"><option value=""></option><? foreach ($serviceTypes as $serviceType) { ?><option value="<?= $serviceType['idservicesTypes']; ?>"<?= $serviceType['idservicesTypes'] == $service['servicesType'] ? ' selected' : ''; ?>><?= $serviceType['servicesTypesName']; ?></option><? } ?></select></div>


	 			 <div>Тип записи (по Услуги 2):</div><div><select name="servicesEntryType" autocomplete="off"><option value=""></option><? foreach (($servicesEntryTypes ?? []) as $servicesEntryType) { ?><option value="<?= $servicesEntryType['idservicesEntryTypes']; ?>"<?= $servicesEntryType['idservicesEntryTypes'] == $service['servicesEntryType'] ? ' selected' : ''; ?>><?= $servicesEntryType['servicesEntryTypesName']; ?></option><? } ?></select></div>
	 			 <div>Родительский раздел (по Услуги 2):</div><div><select name="servicesParent" autocomplete="off">
	 				  <option value=""></option>
						<? foreach (query2array(mysqlQuery("SELECT * FROM `services` WHERE `servicesEntryType`=1 AND isnull(`servicesDeleted`) order by `servicesName`")) as $parent) { ?>
						  <option value="<?= $parent['idservices']; ?>"<?= $parent['idservices'] == $service['servicesParent'] ? ' selected' : ''; ?>><?= $parent['idservices']; ?>] <?= $parent['servicesName']; ?></option><? } ?></select></div>


	 			 <div>Аппарат:</div><div><select name="servicesEquipment" autocomplete="off"><option value="">Без аппарата</option><? foreach (query2array(mysqlQuery("SELECT * FROM `equipment` ORDER BY `equipmentName`")) as $equipment) { ?><option value="<?= $equipment['idequipment']; ?>"<?= $equipment['idequipment'] == $service['servicesEquipment'] ? ' selected' : ''; ?>><?= $equipment['equipmentName']; ?></option><? } ?></select></div>
	 			 <div>Направление:</div>
	 			 <div>
	 				<select name="testsReferral" autocomplete="off">
	 				  <option value="">Без направления</option>
						<? foreach (query2array(mysqlQuery("SELECT * FROM `testsReferrals` ORDER BY `testsReferralsName`")) as $testsReferral) { ?>
						  <option value="<?= $testsReferral['idtestsReferrals']; ?>"<?= $testsReferral['idtestsReferrals'] == $service['servicesTestsReferral'] ? ' selected' : ''; ?>>
							 <?= $testsReferral['testsReferralsName']; ?></option>
						<? } ?>
	 				</select>
	 			 </div>

	 			 <div>Продолжительность</div>
	 			 <div>
	 				<div style="display: inline-block;">
	 				  <select name="servicesDuration" autocomplete="off">
	 					 <option></option>
						  <? for ($time = 15; $time <= 300; $time += 15) {
							 ?> 
							 <option<?= ($service['servicesDuration'] == $time) ? ' selected' : ''; ?> value="<?= $time; ?>"><?= floor($time / 60); ?>:<?= ($time % 60) ? ($time % 60) : '00' ?></option>
							 <?
						  }
						  ?>
	 				  </select>
	 				</div>
	 			 </div>

	 			 <div>НДС</div>
	 			 <div>
	 				<div style="display: inline-block;">
	 				  <select name="servicesVat" autocomplete="off">
	 					 <option value="">НДС не указан</option>
	 					 <option<?= ($service['servicesVat'] == '0') ? ' selected' : ''; ?> value="0">без НДС</option>
	 					 <option<?= ($service['servicesVat'] == '20') ? ' selected' : ''; ?> value="20">20%</option>
	 				  </select>
	 				</div>
	 			 </div>

	 			 <div></div>
				  <? if (R(169)) { ?>
					 <div style="text-align: right;"><input type="submit" value="Сохранить"></div>
				  <? } ?>

	 		  </div>
	 		</div>
	 	 </form>
	 	 <script>
	 		function priceHighlight(elem) {
	 		  if (elem.dataset.prevvalue != elem.value) {
	 			 elem.style.backgroundColor = 'lightgoldenrodyellow';
	 		  } else {
	 			 elem.style.backgroundColor = 'white';
	 		  }
	 		}
	 		function priceSave(button) {
	 		  //						button.disabled = true;
	 		  let priceForm = document.querySelector(`#prices`);
	 		  let inputs = priceForm.querySelectorAll('input');
	 		  let dataToSend = {p: {}};
	 		  let length = 0;
	 		  inputs.forEach(input => {
	 			 if (input.dataset.prevvalue !== input.value && input.dataset.type) {
	 				console.log(input.dataset.type, input.value);
	 				dataToSend.p[input.dataset.type] = input.value;
	 				length++;
	 			 }
	 		  });
	 		  if (length > 0) {
	 			 console.log(dataToSend);
	 			 dataToSend['action'] = 'saveprice';
	 			 dataToSend['service'] = '<?= $service['idservices']; ?>';
	 			 fetch('IO.php', {
	 				body: JSON.stringify(dataToSend),
	 				credentials: 'include',
	 				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	 			 }).then(result => result.text()).then(async function (text) {
	 				try {
	 				  let jsn = JSON.parse(text);
	 				  if (jsn.success) {
	 					 inputs.forEach(input => {
	 						if (input.dataset.type) {
	 						  input.dataset.prevvalue = input.value;
	 						  input.value = input.value;
	 						  console.log(input.dataset.prevvalue, input.value);
	 						  input.style.backgroundColor = 'white';
	 						}
	 					 });
	 				  }
	 				  if ((jsn.msgs || []).length) {
	 					 for (let msge of jsn.msgs) {
	 						await MSG(msge);
	 					 }
	 				  }
	 				} catch (e) {
	 				  MSG(`Ошибка парсинга ответа сервера. <br><br><i>${e}</i><br>${text}`);
	 				}
	 			 }); //fetch
	 		  }




	 		}


	 	 </script>
	 	 <form id="prices">
	 		<div style="border: 1px solid silver; padding: 10px; background-color: #FFF; display: inline-block; margin: 20px;">
	 		  <div>Цены:</div>
	 		  <div>
	 			 <div style="display: inline-block;"><table>
	 				  <tr>
	 					 <td></td>
	 					 <th>min</th><th>max</th></tr>
						<? if (R(181)) { ?><tr>
						  <tr>
							 <td>По абонементу</td>
							 <td><input type="text" autocomplete="off" data-type="1" style="width: 70px;" oninput="digon(); priceHighlight(this);" data-prevvalue="<?= $p[1]['servicesPricesPrice'] ?? ''; ?>" value="<?= $p[1]['servicesPricesPrice'] ?? ''; ?>"></td>
							 <td><input type="text" autocomplete="off" data-type="2" style="width: 70px;" oninput="digon(); priceHighlight(this);" data-prevvalue="<?= $p[2]['servicesPricesPrice'] ?? ''; ?>" value="<?= $p[2]['servicesPricesPrice'] ?? ''; ?>"></td>
						  </tr>
						<? } else {
						  ?>
						  <tr>
							 <td>По абонементу</td>
							 <td><?= $p[1]['servicesPricesPrice'] ?? 'Не указана'; ?></td>
							 <td><?= $p[2]['servicesPricesPrice'] ?? 'Не указана'; ?></td>
						  </tr>
						  <?
						}
						?>

						<? if (R(154)) { ?>
						  <tr>
							 <td>Зп специалиста</td>
							 <td><input type="text" autocomplete="off" data-type="3"style="width: 70px;" oninput="digon(); priceHighlight(this);" data-prevvalue="<?= $p[3]['servicesPricesPrice'] ?? ''; ?>" value="<?= $p[3]['servicesPricesPrice'] ?? ''; ?>"></td>
							 <td><input type="text" autocomplete="off" data-type="4" style="width: 70px;" oninput="digon(); priceHighlight(this);" data-prevvalue="<?= $p[4]['servicesPricesPrice'] ?? ''; ?>" value="<?= $p[4]['servicesPricesPrice'] ?? ''; ?>"></td>
						  </tr>
						<? } else { ?>
						  <tr>
							 <td>Зп специалиста</td>
							 <td><?= $p[3]['servicesPricesPrice'] ?? 'Не указана'; ?></td>
							 <td><?= $p[4]['servicesPricesPrice'] ?? 'Не указана'; ?></td>
						  </tr>
						<? } ?>
	 				  <tr>
	 					 <td>Автоцена</td>
	 					 <td><?= $p[5]['servicesPricesPrice'] ?? 'Не указана'; ?></td>
	 					 <td><?= $p[6]['servicesPricesPrice'] ?? 'Не указана'; ?></td>
	 				  </tr>
	 				  <tr>
	 					 <td>Аутсорс</td>
	 					 <td><input type="text" autocomplete="off" data-type="7" style="width: 70px;" oninput="digon(); priceHighlight(this);" data-prevvalue="<?= $p[7]['servicesPricesPrice'] ?? ''; ?>" value="<?= $p[7]['servicesPricesPrice'] ?? ''; ?>"></td>
	 					 <td></td>
	 				  </tr>

	 				  <tr><td colspan="3" class="C"><input type="button" onclick="priceSave(this);" value="Сохранить цену"></td></tr>
	 				</table>
	 			 </div>
	 		  </div>
	 		</div>
	 	 </form>
	 	 <!--				153	28	Состав: расходные материалы
	 												152	28	Состав: оборудование-->
		  <? if (R(152)) { ?>
			 <div class="box" style="background-color: #eee;">
				<div class="box-body">
				  <h2>Оборудование</h2>
				  <div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto;">
					 <div style="display: contents;">
						<div class="B C">Наименование</div>
						<div class="B C">Амортизация<br>за 1 проц.</div>
						<div class="B C">X</div>
					 </div>
					 <div style="display: contents;">
						<div class="B C">
						  <select id="newEquipment">
							 <option></option>
							 <?
							 $alleqipment = query2array(mysqlQuery("SELECT * FROM `equipment` WHERE isnull(`equipmentDeleted`)"));
							 uasort($alleqipment, function ($a, $b) {
								return mb_strtolower($a['equipmentName']) <=> mb_strtolower($b['equipmentName']);
							 });
							 foreach ($alleqipment as $equipment) {
								?><option value="<?= $equipment['idequipment']; ?>"><?= $equipment['equipmentName']; ?></option><?
							 }
							 ?>
						  </select>
						</div>
						<div class="B C"></div>
						<div class="B C"><input type="button" style="color: green;" value="+" onclick="if (qs('#newEquipment').value) {
										GR({action: 'addNewEquipment', equipment: qs('#newEquipment').value});
									 }"></div>
					 </div>
					 <?
					 $servicesEquipment = query2array(mysqlQuery("SELECT * FROM `servicesEquipment` LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipmentEquipment`) WHERE `servicesEquipmentService` = '" . $service['idservices'] . "'"));
					 foreach ($servicesEquipment as $serviceEquipment) {
//								printr($serviceEquipment);
						?>
		  			 <div style="display: contents;">
		  				<div style="display: flex; align-items: center; padding-left: 20px;"><?= $serviceEquipment['equipmentName']; ?></div>
		  				<div class="R"><?= round($serviceEquipment['equipmentDepreciation'], 3); ?>р.</div>
		  				<div><input type="button" onclick="GR({deleteEquipment:<?= $serviceEquipment['idequipment']; ?>});" style="color: red;" value="x"></div>
		  			 </div>
						<?
					 }
					 ?>


				  </div>
				</div>
			 </div>
		  <? } ?>
	 	 <br>
		  <? if (R(152) && R(153)) { ?>
			 <input type="checkbox" id="serviceDone" oninput="GR({serviceDone:this.checked});" <?= $service['servicesEquipped'] ? 'checked' : ''; ?>><label for="serviceDone">Процедура полностью укомплектована</label><br>
		  <? } ?>
		  <? if (R(153)) { ?>
			 <div class="box" style="background-color: #eee;">
				<div class="box-body">
				  <h2>Расходные материалы</h2>

				  <form method="post">
					 <div>Копировать из <input type="text" name="copyFrom" oninput="digon();" style="display: inline-block; width: auto;" size="3"><input type="submit" value="ok"></div>
				  </form>
				  <div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto auto auto auto auto auto ;">
					 <div style="display: contents;">
						<div class="B C">Наименование</div>
						<div class="B C">опци-<br>онально</div>
						<div class="B C">измен-<br>яемо</div>
						<div class="B C">умно-<br>жать</div>
						<div class="B C">количество<br>на 1 пр-ру</div>
						<div class="B C">ед. изм.</div>
						<div class="B C">цена за<br>1 ед.изм.</div>
						<div class="B C">Итого</div>
						<div class="B C">X</div>
					 </div>

					 <div style="display: contents;">
						<div><input type="hidden" name="newConsumable" id="idnewConsumable">
						  <input type="text" autocomplete="off"  id="consumablesName"  placeholder="Наименование" oninput="searchСonsumablesByName(this.value);" onblur="setTimeout(function () {
										  //													qs('#consumablesSuggestions').innerHTML = '';
										}, 300);"><ul id="consumablesSuggestions" class="suggestions"></ul>
						  <div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;"></div>
						</div>
						<div id="consumablesMUnDiv" class="C"></div>
						<div id="consumablesMUnDiv" class="C"></div>
						<div id="consumablesMUnDiv" class="C"></div>
						<div id="consumablesQtyDiv" class="C"><input type="text" style="text-align: center;" size="3" oninput="digon();" name="consumablesQty" id="consumablesQty"></div>


						<div id="consumablesMUnDiv" class="C"></div>
						<div id="consumablesPriceDiv" class="C"></div><input type="hidden" name="consumablesPrice">
						<div id="consumablesSummDiv" class="R"></div>
						<div><input type="button" style="color: green;" value="+" onclick="if (!qs('#consumablesQty').value) {
										MSG('А сколько????');
									 } else if (!qs('#idnewConsumable').value) {
										MSG('А что добавляем????');
									 } else {
										GR({action: 'addNewConsumable', nomenclature: qs('#idnewConsumable').value, qty: qs('#consumablesQty').value});
									 }"></div>
					 </div>


					 <?
					 $consumables = query2array(mysqlQuery(""
										  . "SELECT * FROM `servicesPrimecost` "
										  . "LEFT JOIN (SELECT * FROM    `WH_goodsIn` AS `a`        INNER JOIN    (SELECT         MAX(`idWH_goodsIn`) AS `idWH_goodsInMAX`    FROM        `WH_goodsIn` LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`)    GROUP BY `WH_goodsNomenclature`) AS `b` ON (`a`.`idWH_goodsIn` = `b`.`idWH_goodsInMAX`) LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`)) as `PC` ON (`servicesPrimecostNomenclature`=`WH_goodsNomenclature`)"
										  . "LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `servicesPrimecostNomenclature`)"
										  . "LEFT JOIN `units` ON (`idunits` = `WH_nomenclatureUnits`)"
										  . "WHERE `servicesPrimecostService` = '" . $service['idservices'] . "'"
										  . ""));
					 ?>

					 <?
					 $totalPC = 0;
					 foreach ($consumables as $consumable) {


						if ($consumable['WH_goodsPrice'] == null) {
						  $consumable['WH_goodsPrice'] = mfa(mysqlQuery("SELECT `WH_goodsPrice` FROM `WH_goods` WHERE `WH_goodsNomenclature` = '" . $consumable['servicesPrimecostNomenclature'] . "' ORDER BY `idWH_goods` DESC LIMIT 1"))['WH_goodsPrice'] ?? null;
						}
						?>
		  			 <div style="display: contents;">

		  				<div><?= $consumable['WH_nomenclatureName']; ?><div style="font-size: 0.7em; color: <?= $consumable['WH_goodsName'] ? 'grey' : 'red'; ?>;"><?= $consumable['WH_goodsName'] ?? 'Нет данных о закупках'; ?></div></div>
		  				<div class="C" style="display:flex; justify-content:center; align-items: center;"><input type="checkbox" onclick="makeOptional('makeOptional',<?= $consumable['idservicesPrimecost']; ?>, this.checked);" <?= $consumable['servicesPrimecostIsOptional'] ? 'checked' : '' ?> autocomplete="off" id="consumablesOptional<?= $consumable['idservicesPrimecost']; ?>"><label for="consumablesOptional<?= $consumable['idservicesPrimecost']; ?>"></label></div>

		  				<div class="C" style="display:flex; justify-content:center; align-items: center;"><input type="checkbox" onclick="makeOptional('makeVariable',<?= $consumable['idservicesPrimecost']; ?>, this.checked);" <?= $consumable['servicesPrimecostVariable'] ? 'checked' : '' ?> autocomplete="off" id="makeVariable<?= $consumable['idservicesPrimecost']; ?>"><label for="makeVariable<?= $consumable['idservicesPrimecost']; ?>"></label></div>

		  				<div class="C" style="display:flex; justify-content:center; align-items: center;"><input type="checkbox" onclick="makeOptional('makeMultiply',<?= $consumable['idservicesPrimecost']; ?>, this.checked);" <?= $consumable['servicesPrimecostMultiply'] ? 'checked' : '' ?> autocomplete="off" id="makeMultiply<?= $consumable['idservicesPrimecost']; ?>"><label for="makeMultiply<?= $consumable['idservicesPrimecost']; ?>"></label></div>

		  				<div class="C" style="display:flex; justify-content:center; align-items: center;"><?= round($consumable['servicesPrimecostNomenclatureQty'], 3); ?></div>

		  				<div class="C" style="display:flex; justify-content:center; align-items: center;"><?= $consumable['unitsName']; ?></div>
		  				<div class="C" style="display:flex; justify-content:center; align-items: center;"><?= $consumable['WH_goodsPrice'] ? nf($consumable['WH_goodsPrice'], 2) . 'р.' : 'Цена не указана'; ?></div>
		  				<div class="R" style="display:flex; justify-content:center; align-items: center;"><?= ($consumable['WH_goodsPrice'] && $consumable['servicesPrimecostNomenclatureQty']) ? (nf($consumable['WH_goodsPrice'] * $consumable['servicesPrimecostNomenclatureQty'], 2)) . 'р.' : '?' ?></div>
		  				<div style="display:flex; justify-content:center; align-items: center;"><input type="button" onclick="GR({deleteConsumable:<?= $consumable['idservicesPrimecost']; ?>});" style="color: red;" value="x"></div>
		  			 </div>

						<?
						$totalPC += ($consumable['WH_goodsPrice'] && $consumable['servicesPrimecostNomenclatureQty']) ? ($consumable['WH_goodsPrice'] * $consumable['servicesPrimecostNomenclatureQty']) : 0;
					 }
					 ?>


					 <div style="display: contents;">
						<div style="grid-column: span 7; justify-content:flex-end; display:flex; align-items: center;">Итого:</div>
						<div style="display:flex; align-items: center;"><?= nf($totalPC, 2); ?>р.</div>
						<div></div>
					 </div>








					 <script>
						function makeOptional(action, id, value) {
						  fetch('IO.php', {
							 body: JSON.stringify({
								action: action,
								id: id,
								val: value
							 }),
							 credentials: 'include',
							 method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
						  }).then(result => result.text()).then(async function (text) {
							 try {
								let jsn = JSON.parse(text);
								if ((jsn.msgs || []).length) {
								  for (let msge of jsn.msgs) {
									 await MSG(msge);
								  }
								}
							 } catch (e) {
								MSG(`Ошибка парсинга ответа сервера. <br><br><i>${e}</i><br>${text}`);
							 }
						  }); //fetch

						}

						async function searchСonsumablesByName(name) {
						  qs('#consumablesSuggestions').innerHTML = '';
						  fetch('IO.php', {
							 body: JSON.stringify({
								action: 'consumablesSuggestions',
								name: name
							 }),
							 credentials: 'include',
							 method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
						  }).then(result => result.text()).then(async function (text) {
							 try {
								let jsn = JSON.parse(text);
								if ((jsn.consumables || []).length) {
								  jsn.consumables.forEach(consumable => {
									 let li = el('li', {innerHTML: `<div class="mask"></div><span>${consumable.WH_nomenclatureName || ''}</span>`});
									 li.addEventListener('click', function () {
										qs('#consumablesSuggestions').innerHTML = '';
										qs('#consumablesName').value = '';
										addConsumable(consumable);
									 });
									 qs('#consumablesSuggestions').appendChild(li);
								  });
								}
								if ((jsn.msgs || []).length) {
								  for (let msge of jsn.msgs) {
									 await MSG(msge);
								  }
								}
							 } catch (e) {
								MSG(`Ошибка парсинга ответа сервера. <br><br><i>${e}</i><br>${text}`);
							 }
						  }); //fetch
						}
						function addConsumable(consumable) {
						  console.log(consumable);
						  qs('#idnewConsumable').value = consumable.idWH_nomenclature;
						  qs('#consumablesName').value = consumable.WH_nomenclatureName;
						  qs('#consumablesMUnDiv').innerHTML = consumable.unitsName;
						  qs('#consumablesPriceDiv').innerHTML = `${(consumable.item || {}).WH_goodsPrice || 0}р.`;
						  //{
						  //  "idWH_nomenclature": 943,
						  //  "WH_nomenclatureName": "Нити Аптос",
						  //  "WH_nomenclatureUnits": 3,
						  //  "WH_nomenclatureEntryType": 2,
						  //  "WH_nomenclatureParent": 19,
						  //  "WH_nomenclatureType": 1,
						  //  "WH_nomenclatureMin": null,
						  //  "WH_nomenclatureMax": null,
						  //  "idunits": 3,
						  //  "unitsCode": 5,
						  //  "unitsName": "комп.",
						  //  "unitsFullName": "комплект",
						  //  "unitsOKEI": 839
						  //}



						}
					 </script>


				  </div>


				</div>

			 </div>
		  <? } ?>
		  <? ?>
		  <? if (R(170)) { ?>
			 <form action="<?
			 if ($service['servicesDeleted']) {
				print GR();
			 } else {
				if (isset($service['servicesType'])) {
				  print '/pages/services/index.php?type=' . $service['servicesType'];
				}
			 }
			 ?>" method="post"><input type="hidden" name="<?= $service['servicesDeleted'] ? 'un' : ''; ?>deleteService" value="<?= $service['idservices']; ?>"><div style="text-align: right;"><input type="submit" style="background-color: pink;" value="<?= $service['servicesDeleted'] ? 'Разу' : 'у'; ?>далить"></div></form>
				  <? } ?>
	 	 <!--
	 									 Array
	 	 (
	 				[0] => Array
	 						  (
	 									 [idservices] => 17
	 									 [servicesCode] => 000000237
	 									 [servicesName] => Имплант Nobel
	 									 [servicesBasePrice] =>
	 									 [servicesType] =>
	 									 [servicesDeleted] =>
	 									 [idservicesTypes] =>
	 									 [servicesTypesName] =>
	 						  )

	 	 )-->

		  <?
		  //	printr($service);
		} elseif (isset($_GET['add']) && R(155)) {
		  ?>

	 	 <form action="/pages/services/index.php" method="post">
	 		<input type="hidden" name="action" value="addNew">
	 		<div style="display: inline-block;">
	 		  <div style="display: grid; grid-template-columns: auto auto; margin: 20px; grid-gap: 10px;">
	 			 <div>Наименование услуги:</div><div><input type="text" name="servicesName" autocomplete="off"></div>
	 			 <div>Раздел:</div><div><select name="serviceType"><option value=""></option><? foreach ($serviceTypes as $serviceType) { ?><option value="<?= $serviceType['idservicesTypes']; ?>"><?= $serviceType['servicesTypesName']; ?></option><? } ?></select></div>
	 			 <!--<div>Цена:</div><div><input oninput="digon();" type="text" name="servicesBasePrice" autocomplete="off"></div>-->
	 			 <!--<div>З.П. специалиста:</div><div><input oninput="digon();" type="text" name="servicesCost" autocomplete="off"></div>-->
	 			 <div></div>
	 			 <div style="text-align: right;"><input type="submit" value="Сохранить"></div>
	 		  </div>
	 		</div>
	 	 </form>

		  <?
		} else {///УСЛУГИ ПО ТИПУ
		  ?>
	 	 <div style="display: grid; grid-template-columns: auto  auto auto auto  auto auto auto auto auto  auto auto auto auto; margin: 10px;">
	 		<div style="display: contents;">
	 		  <div>#</div>
	 		  <div>Тип</div>
	 		  <div>1C</div>
	 		  <div><i class="fas fa-bed"></i></div>
	 		  <div><i class="fas fa-cog"></i></div>
	 		  <div><i class="fab fa-internet-explorer"></i></div>
	 		  <div><i class="far fa-check-circle"></i></div>
	 		  <div><i class="fas fa-info-circle"></i></div>
	 		  <div>сост</div>
	 		  <div><i class="fas fa-exclamation-triangle"></i></div>
	 		  <div>Наименование процедуры</div>
	 		  <div>Цена</div>
	 		  <div>З.П.</div>

	 		</div>
			 <?
//

			 $N = 0;
			 usort($services, function ($a, $b) {
				return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
			 });
			 if ($_USER['id'] == 176) {
//						printr($services, 1);
			 }
			 foreach ($services as $service) {
				$N++;
				?>
				<div style="display: contents;<?= $service['servicesDeleted'] ? ' color: silver;' : ''; ?>" class="hoverhighlight">
				  <div><?= $N; ?></div>
				  <div><?= $service['servicesTypesName']; ?></div>
				  <div><?= $service['GUIDsQty'] ? '1C' : '-'; ?></div>
				  <div style="color: gray;"><? if ($service['servicesEquipment']) { ?>
		  			 <i class="fas fa-bed"></i>
					 <? } ?>
				  </div>

				  <div style="color: gray;"><? if ($service['servicesEquipmentQty']) { ?>
		  			 <div><i class="fas fa-cog"></i></div>
					 <? } ?>
				  </div>

				  <div style="color: gray;"><? if ($service['servicesURL']) { ?>
		  			 <a href="<?= $service['servicesURL'] ?>" target="_blank" title="Ссылка на сайт"><i class="fab fa-internet-explorer"></i></a>
					 <? } ?>
				  </div>
				  <div style="color: green;"><? if ($service['servicesEquipped'] ?? false) { ?>
		  			 <div><i class="far fa-check-circle" title="Процедура укомплектована"></i></div>
					 <? } ?>
				  </div>
				  <div style="color: green;"><? if ($service['servicesDescriptionsDescription'] ?? false) { ?>
		  			 <div><i class="fas fa-info-circle" title="Есть информацияя по процедуре"></i></div>
					 <? } ?>
				  </div>
				  <div class="C"><?= $service['PCqty']; ?></div>
				  <div style="color: orange;"><? if (($service['personal'] ?? 0) == 0) { ?>
		  			 <div><i class="fas fa-exclamation-triangle" title="не назначен специалист"></i></div>
						<? } ?>
				  </div>

				  <div><?= $service['idservices']; ?>] (<?= $service['f_subscriptions']; ?>)(<?= $service['servicesApplied']; ?>) <a href="/pages/services/index.php?service=<?= $service['idservices']; ?>"><?= ($_GET['search'] ?? false) ? preg_replace('/' . $_GET['search'] . '/iu', '<span style="color:red;"><b>$0</b></span>', $service['servicesName']) : $service['servicesName'];
						?></a></div>
				  <!---->

				  <div><? if ($service['minPrice']) { ?>
						<?=
						$service['minPrice'];
						if ($service['maxPrice'] && $service['maxPrice'] !== $service['minPrice']) {
						  ?>...<?= $service['maxPrice']; ?><?
						}
						?>
					 <? } ?></div>
				  <div><? if ($service['minCost']) { ?>
						<?=
						$service['minCost'];
						if ($service['maxCost'] && $service['maxCost'] !== $service['minCost']) {
						  ?>...<?= $service['maxCost']; ?><?
						}
						?>
					 <? } ?></div>

				</div>
				<?
			 }
			 ?>
	 	 </div>
	 	 <script>
	 		let rows = document.querySelectorAll(`.hoverhighlight`).forEach(row => {
	 		  row.addEventListener('click', function () {
	 			 row.classList.toggle('highlight');
	 		  });
	 		});
	 	 </script>
		<? } ?>




    </div>
  </div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
