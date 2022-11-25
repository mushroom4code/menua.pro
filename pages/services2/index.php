<?php
$load['title'] = $pageTitle = 'Импорт процедур';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!in_array($_USER['id'], [176])) {
  die();
}
$_GET['parent'] = $_GET['parent'] ?? 1;

function getBranch($branch_id, $arrayToWalk, $id_name, $leafsColumn = 'content') {
  foreach ($arrayToWalk as $arrayElement) {
	 if ($arrayElement[$id_name] == $branch_id) {
		return $arrayElement;
	 } else {
		if (is_array($arrayElement[$leafsColumn] ?? false)) {
		  $result = getBranch($branch_id, $arrayElement[$leafsColumn], $id_name, $leafsColumn);
		  if ($result) {
			 return $result;
		  }
		}
	 }
  }
}

if ($_GET['changeEntryTypeFor'] ?? false) {
//	changeEntryTypeFor=1905&to=1
  mysqlQuery("UPDATE `services` SET `servicesEntryType`=" . sqlVON(($_GET['to'] ?? ''), 1) . " WHERE `idservices`='" . mres($_GET['changeEntryTypeFor']) . "'");
  header("Location: " . GR2(['changeEntryTypeFor' => null, 'to' => null]));
  die();
}

if ($_GET['changeEntryTypeForAllOf'] ?? false) {
//	changeEntryTypeFor=1905&to=1
  mysqlQuery("UPDATE `services` SET `servicesEntryType`=" . sqlVON($_GET['to'] ?? '', 1) . " WHERE `servicesParent`='" . mres($_GET['changeEntryTypeForAllOf']) . "'");
  header("Location: " . GR2(['changeEntryTypeForAllOf' => null, 'to' => null]));
  die();
}
if ($_GET['changeVatForAllOf'] ?? false) {
//	changeEntryTypeFor=1905&to=1
  mysqlQuery("UPDATE `services` SET `servicesVat`=" . (($_GET['to'] ?? '') == '' ? "null" : ("'" . mres($_GET['to']) . "'")) . " WHERE `servicesParent`='" . mres($_GET['changeVatForAllOf']) . "'");
  header("Location: " . GR2(['changeVatForAllOf' => null, 'to' => null]));
  die();
}


$n = 0;

function flatter($array) {
  global $n;
  $outArray = [];
  //${rownumber}	${n804}	${name}	${price} 
//	printr($array);
  foreach ($array as $arrayElement) {
	 $n++;
	 if (is_array($arrayElement['content'] ?? false)) {
		$outArray[] = [
			 'rownumber' => $n,
			 'n804' => '',
			 'name' => $arrayElement['servicesName'],
			 'price' => ''
		];
		$outArray = array_merge($outArray, flatter($arrayElement['content']));
	 } else {
//			printr($arrayElement);

		$outArray[] = [
			 'rownumber' => $n,
			 'n804' => $arrayElement['servicescolN804'],
			 'name' => $arrayElement['servicesName'],
			 'price' => $arrayElement['minPrice']
		];
	 }
  }


  return $outArray;
}

$servicesEntryTypes = query2array(mysqlQuery("SELECT * FROM `servicesEntryTypes`"));
if (($_GET['save'] ?? false)) {
  require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';
  $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/pricetemplate.docx');
//	printr($_SESSION);
  $data = [
		"entityName" => "Инфинити",
		"entityDirName" => "Горшкова Р.Р.",
		"date" => date("d.m.Y"),
  ];

  foreach ($data as $variable => $value) {
	 $templateProcessor->setValue($variable, $value);
  }
  $services = adjArr2obj(query2array(mysqlQuery("SELECT *"
								  . " ,(WITH `prices` AS (SELECT *, ROW_NUMBER() OVER 
(PARTITION BY `servicesPricesService`,`servicesPricesType` ORDER BY `idservicesPrices` DESC) AS `rowNumber`  FROM `servicesPrices` WHERE `servicesPricesDate` <= '" . date("Y-m-d H:i:s") . "' AND servicesPricesService = `idservices`)
 SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 1) as `minPrice`"
								  . " FROM `services`"
								  . " WHERE isnull(`servicesDeleted`) ")), 'idservices', 'servicesParent');
  $branch = getBranch($_GET['parent'], $services, 'idservices', 'content');
//	printr($branch);
  $rows = flatter([$branch]);
  $templateProcessor->cloneRowAndSetValues('rownumber', $rows);

  header('Content-Description: File Transfer');
  header('Content-Disposition: attachment; filename="' . date("Y.m.d") . '.docx"');
  header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
  header('Content-Transfer-Encoding: binary');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Expires: 0');

  $templateProcessor->saveAs('php://output');
  die();
}

$up = mfa(mysqlQuery("SELECT `servicesParent` FROM `services` WHERE `idservices`='" . mres($_GET['parent']) . "'"))['servicesParent'] ?? 1;
$prnt = $_GET['parent'];
if ($_POST['textdata'] ?? false) {
  $rows = explode("\r\n", $_POST['textdata']);
  foreach ($rows as &$row2) {
	 $row2 = explode("\t", $row2);
	 if (($row2[1] ?? '') !== '') {
		$query = "INSERT INTO `services` SET "
				  . "`servicescolN804`='" . mres($row2[0]) . "',"
				  . "`servicesName` = " . sqlVON($row2[1]) . ","
				  . "`servicesAddedBy` = '" . $_USER['id'] . "',"
				  . "`servicesParent` = '" . mres($_GET['parent']) . "'";
		mysqlQuery($query);

		mysqlQuery("INSERT INTO `servicesPrices` SET `servicesPricesService` = '" . mysqli_insert_id($link) . "', `servicesPricesPrice`=" . sqlVON($row2[2], 1) . ",`servicesPricesType`='1'");
	 }
  }
  header("Location: " . GR());
  die();
}

function getServicesCode($service) {
  $code = $service['servicesCode'] ?? '00';
  $prnt = $service['servicesParent'];
  while ($prnt && !($prnt == 1)) {
	 $bc = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices`='" . mres($prnt) . "'"));
	 $prnt = $bc['servicesParent'];
	 $code = ($bc['servicesCode'] ?? '00') . $code;
  }
  return $code;
}

$services = query2array(mysqlQuery("SELECT *"
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
 SELECT `servicesPricesPrice` FROM `prices`   WHERE  `rowNumber` = 1 AND servicesPricesType = 4) as `maxCost`,"
					 . "(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') FROM `positions2services` LEFT JOIN `positions` ON (`idpositions` = `positions2servicesPosition`) WHERE `positions2servicesService`= `idservices`)  AS `positions`"
					 . " FROM `services`"
					 . " WHERE `servicesParent`='" . mres($_GET['parent']) . "' "
					 . "ORDER BY `servicesEntryType`,`servicesName`"
					 . ""));

//printr($services);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>
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
<div class="box neutral">
  <div class="box-body">
	  <!--<a href="<?= GR2(['parent' => $up]); ?>">Назад</a>-->
	 <div style="padding: 15px; font-size: 1.4em;">
		<?
		$bctext = '';
		while ($prnt) {
		  $bc = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices`='" . mres($prnt) . "'"));
		  $prnt = $bc['servicesParent'];
		  $bctext = '<a href="' . GR2(['parent' => $bc['idservices']]) . '">' . $bc['servicesName'] . '</a> | ' . $bctext;
		}
		print $bctext;
		?>
		<a href="<?= GR2(['save' => true]); ?>" style="float: right;"><i class="fas fa-save"></i></a>
	 </div>
	 <hr style="display: block;"><!-- comment -->
	 <div style="display: none; white-space: nowrap; padding: 10px;">НДС для всех:<select style="display: inline-block;" autocomplete="off" onchange="GR({changeVatForAllOf:<?= $_GET['parent']; ?>, to: this.value});">
		  <option value=""></option>
		  <option value="">Сбросить</option>
		  <option value="0">0%</option>
		  <option value="20">20%</option>
		</select></div><br> 

	 <div style="display: inline-block; white-space: nowrap; padding: 10px;">Пометить все как: <select style="display: inline-block;" autocomplete="off" onchange="GR({changeEntryTypeForAllOf:<?= $_GET['parent']; ?>, to: this.value});">
		  <option value="none">---</option>
		  <option value=""></option>
		  <?
		  foreach ($servicesEntryTypes as $servicesEntryType) {
			 ?>
  		  <option value="<?= $servicesEntryType['idservicesEntryTypes']; ?>"><?= $servicesEntryType['servicesEntryTypesName']; ?></option>
			 <?
//								, 
		  }
		  ?>
		</select></div>
	 <div style=" height: 150px; overflow-y: scroll; display: none;">
		<? foreach (query2array(mysqlQuery("SELECT * FROM `positions` WHERE isnull(`positionsDeleted`) ORDER BY `positionsName`")) as $position) {
		  ?>
  		<input type="checkbox" id="cb<?= $position['idpositions']; ?>" onclick="savepositions(this.value, this.checked,<?= $_GET['parent'] ?? 'false' ?>)" value="<?= $position['idpositions']; ?>"><label for="cb<?= $position['idpositions']; ?>" style="border: 1px solid silver; padding: 5px; border-radius: 10px; background-color: white; white-space: nowrap;"><span><?= $position['positionsName']; ?></span></label>
		<? } ?>
	 </div>

	 <script>
		function savepositions(idposition, state, parent) {
		  console.log(idposition, state, parent);
		  fetch('IO.php', {
			 body: JSON.stringify({action: 'applyPosition', idposition, state, parent}),
			 credentials: 'include',
			 method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		  }).then(result => result.text()).then(async function (text) {
			 try {
				let jsn = JSON.parse(text);
				if (jsn.success) {

				} else {
				  MSG('Ошибка');
				}

			 } catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			 }
		  }); //fetch
		}

	 </script>


	 <div id="vueapp">
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
			 suggestionsIndex: 0
		  },
		  methods: {
			 summByService: function (service) {
				return (service.qty || 0) * (service.price || 0);
			 },
			 confirmSearch: function (n) {
				delete(this.suggestions[n].servicesNameHighlighted);
				delete(this.suggestions[n].servicesDuration);
				window.location.href = `/pages/services/index.php?service=${this.suggestions[n].idservices}`;
				console.log(this.suggestions[n]);
				this.resetSearch();
			 },
			 resetSearch: function () {
				this.servicesSearchText = '';
				this.suggestions = [];
				this.lastSuccessSearchLength = 0;
			 },

			 searchServices: function (event) {
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
				  this.confirmSearch(this.suggestionsIndex);
				  return false;
				}

				if (event.target.value.length < 3) {
				  this.suggestions = [];
				  return false;
				}

				this.suggestionsIndex = 0;
				fetch('/sync/api/local/services/suggestions.php', {
				  body: JSON.stringify({search: event.target.value, newonly: true}),
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


	 <div class="lightGrid" style="display: grid; grid-template-columns: repeat(10,auto); margin: 20px;">
		<div style="display: contents;">
		  <div></div>
		  <div class="B C">N804</div>
		  <div class="B C">Внутренний<br>Код</div>
		  <div class="B C">Код стор.орг.</div>
		  <div class="B C">Персонал</div>
		  <div class="B C">Тип записи</div>
		  <div class="B C">НДС</div>
		  <div class="B C">Наименование</div>
		  <div class="B C">Сокращённое наименование</div>
		  <div class="B C">Цена</div>

		</div>

		<?
		foreach ($services as $service) {
		  ?>

  		<div style="display: contents;">
  		  <div  class="C"><a name="service<?= $service['idservices']; ?>"></a><?
				if (!$service['servicesCode']) {
				  print '&gt;';
				  $new = sprintf('%02d', intval(mfa(mysqlQuery("SELECT max(`servicesCode`) as `mx` FROM `services` WHERE `servicesParent`='" . mres($_GET['parent']) . "'"))['mx']) + 1);
				  mysqlQuery("UPDATE `services` SET `servicesCode`='" . $new . "' WHERE `idservices` = '" . $service['idservices'] . "'");
				  $service['servicesCode'] = $new;
				}
				?></div>
  		  <div><?= $service['servicescolN804']; ?></div>

  		  <div  class="C"><a target="_blank" href="/pages/services/index.php?service=<?= $service['idservices']; ?>"><?= getServicesCode($service); ?></a></div>
  		  <div class="C"><?= $service['servicesSupplierCode']; ?></div>
  		  <div class="C"><?= (in_array($service['servicesEntryType'], [2, 3, 4])) ? ($service['positions'] ?? '<span style="color: red;">Не назначен</span>') : ''; ?></div>
  		  <div>
  			 <select autocomplete="off" onchange="GR({changeEntryTypeFor:<?= $service['idservices']; ?>, to: this.value});">
  				<option value=""></option>
				  <?
				  foreach ($servicesEntryTypes as $servicesEntryType) {
					 ?>
	 				<option <?= $servicesEntryType['idservicesEntryTypes'] == $service['servicesEntryType'] ? 'selected' : ''; ?> value="<?= $servicesEntryType['idservicesEntryTypes']; ?>"><?= $servicesEntryType['servicesEntryTypesName']; ?></option>
					 <?
//								, 
				  }
				  ?>
  			 </select>
  		  </div>
  		  <div class="С"><?= (in_array($service['servicesEntryType'], [2, 3, 4])) ? ($service['servicesVat'] === null ? '<span style="color: red; font-weight: bold;">НЕ УКАЗАН</span>' : ($service['servicesVat'] . '%')) : ''; ?></div>
  		  <div>
				<?
				$aclose = false;
				if ($service['servicesEntryType'] == 1) {
				  $aclose = true;
				  ?>
	 			 <a href="<?= GR2(['parent' => $service['idservices']]); ?>">
					 <?
				  } elseif (in_array($service['servicesEntryType'], [2, 3, 4])) {
					 $aclose = true;
					 ?>
	 				<a target="_blank"<?= $service['servicesDeleted'] ? ' style="color: red;"' : ''; ?>  href="/pages/services/index.php?service=<?= $service['idservices']; ?>">
					 <? } ?>
					 <?= $service['servicesDeleted'] ? 'УДАЛЕНА [' . $service['idservices'] . ']' : ''; ?> <?= $service['servicesName']; ?>
					 <? if ($aclose) { ?></a><? } ?>
  		  </div>
  		  <div><input value="<?= htmlentities($service['serviceNameShort'] ?? $service['servicesName']); ?>" oninput="saveShort({action: 'saveShort', idservice:<?= $service['idservices']; ?>,name:this.value});" type="text"></div>
  		  <div class="R"><?= $service['servicesEntryType'] == 1 ? '' : nf($service['minPrice']); ?></div>

  		</div>
		  <?
		}
		?>

	 </div>
	 <script>
		function saveShort(data) {
		  console.log(data);
		  fetch('IO.php', {
			 body: JSON.stringify(data),
			 credentials: 'include',
			 method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		  }).then(result => result.text()).then(async function (text) {
			 try {
				let jsn = JSON.parse(text);
				if (jsn.success) {

				} else {
				  MSG('Ошибка');
				}

			 } catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			 }
		  }); //fetch
		}
	 </script>
	 <form action="<?= GR(); ?>" method="post">
		<textarea name="textdata" style="margin: 0 auto; display: block; width: 400px; height: 200px;"></textarea>
		<br>
		<center>
		  <input type="submit" value="Сохранить">	
		</center>

	 </form>
  </div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
