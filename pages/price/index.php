<?php
$load['title'] = $pageTitle = 'Прайс';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

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
							. " ,(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `minPrice`"
							. " FROM `services`"
							. " WHERE isnull(`servicesDeleted`)")), 'idservices', 'servicesParent');
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
$prnt = ($_GET['service'] ?? $_GET['parent'] ?? false);
//print $prnt;
if (!($_GET['service'] ?? false)) {
	$services = query2array(mysqlQuery("SELECT *"
					. " ,(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `minPrice`"
					. ",(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='2') AND `servicesPricesType`='2'  AND `servicesPricesService` = `idservices`)) as `maxPrice`"
					. ",(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='3') AND `servicesPricesType`='3'  AND `servicesPricesService` = `idservices`)) as `minCost`"
					. ",(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='4') AND `servicesPricesType`='4'  AND `servicesPricesService` = `idservices`)) as `maxCost`,"
					. "(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') FROM `positions2services` LEFT JOIN `positions` ON (`idpositions` = `positions2servicesPosition`) WHERE `positions2servicesService`= `idservices`)  AS `positions`"
					. " FROM `services`"
					. " LEFT JOIN `servicesEntryTypes` ON (`idservicesEntryTypes` = `servicesEntryType`)"
					. " WHERE `servicesParent`='" . mres($_GET['parent']) . "'"
					. " AND isnull(`servicesDeleted`)"));
}

$load['vuejs'] = true;
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>



<div class="box neutral">
	<div class="box-body">
		<div style="padding: 15px; font-size: 1.4em;">
			<?
			$bctext = [];
//			print$prnt;
			while ($prnt) {
				$bc = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices`='" . mres($prnt) . "'"));
				$prnt = $bc['servicesParent'];
//				printr($bc);
				if ($bc['servicesEntryType'] == 2) {

					$bctext[] = '<a href="/pages/price/index.php?service=' . $bc['idservices'] . '">' . $bc['idservices'] . $bc['servicesName'] . '</a>';
				}
				if ($bc['servicesEntryType'] == 1) {
					$bctext[] = '<a href="/pages/price/index.php?parent=' . $bc['idservices'] . '">' . $bc['servicesName'] . '</a>';
				}

//				$bctext[] = '<a href="' . GR2(['parent' => $bc['idservices']]) . '">' . $bc['servicesName'] . '</a>';
			}
			print implode(' | ', array_reverse($bctext));
			?>
			<a href="<?= GR2(['save' => true]); ?>" style="float: right;"><i class="fas fa-save"></i></a>
		</div>
		<hr style="display: block;"><!-- comment -->
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
		</div>


		<? if (!($_GET['service'] ?? false)) {
			?>
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

						<div  class="C"><a href="/pages/price/index.php?service=<?= $service['idservices']; ?>"><?= getServicesCode($service); ?></a></div>
						<div class="C"><?= $service['servicesSupplierCode']; ?></div>
						<div class="C"><?= (in_array($service['servicesEntryType'], [2, 3, 4])) ? ($service['positions'] ?? '<span style="color: red;">Не назначен</span>') : ''; ?></div>
						<div>
							<?= $service['servicesEntryTypesName']; ?>
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
									<a target="_blank"<?= $service['servicesDeleted'] ? ' style="color: red;"' : ''; ?>  href="/pages/price/index.php?service=<?= $service['idservices']; ?>">
									<? } ?>
									<?= $service['servicesDeleted'] ? 'УДАЛЕНА [' . $service['idservices'] . ']' : ''; ?> <?= $service['servicesName']; ?>
									<? if ($aclose) { ?></a><? } ?>
						</div>
						<div><?= htmlentities($service['serviceNameShort'] ?? ''); ?></div>
						<div class="R"><?= $service['servicesEntryType'] == 1 ? '' : nf($service['minPrice']); ?></div>

					</div>
					<?
				}
				?>

			</div>

		<? } ?>


		<?

		function getPersonnelByService($idservice) {
			$out = [];

			$personnelSQL = "SELECT `users`.*,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions`  "
					. " FROM `users` "
					. " LEFT JOIN `usersPositions` ON (`idusers` = `usersPositionsUser`) "
					. " LEFT JOIN  `positions2services` ON (`usersPositionsPosition` = `positions2servicesPosition`) "
//					. " LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers` AND `usersScheduleDate` = '" . $_GET['date'] . "')"
					. " LEFT JOIN `users2services` ON (`users2servicesUser` = `idusers`)"
					. " WHERE "
					. " (isnull(`usersDeleted`))"
					. "AND "
					. ""
					. "("
					. "`positions2servicesService` = '" . $idservice . "'  "
					. "OR (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = '" . $idservice . "' AND `users2servicesUser` = `idusers`)>0"
					. ")"
					. " AND (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesExclude` = '" . $idservice . "' AND `users2servicesUser` = `idusers`) = 0"
					. ""
					. " AND NOT isnull(`idusers`) "
					. ""
//					. " AND `usersGroup` IN (1,2,3,4,5,6,7,10,11)"
//					. " AND NOT isnull(`idusersSchedule`) "
					. " GROUP BY `idusers`";
			$out = query2array(mysqlQuery($personnelSQL));

			return $out;
		}

		if (($_GET['service'] ?? false)) {
			$service = mfa(mysqlQuery("SELECT *"
							. " ,(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `minPrice`"
							. ",(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='2') AND `servicesPricesType`='2'  AND `servicesPricesService` = `idservices`)) as `maxPrice`"
							. " FROM `services` WHERE `idservices` = '" . mres($_GET['service']) . "'"));
//			printr($service, 1);
			?>
			<div style="display: inline-block;">
				<div class="lightGrid" style="display: grid; grid-template-columns: repeat(2, auto);">
					<div style="display:  contents;">
						<div class="B">Наименование</div>
						<div><?= $service['servicesName']; ?></div>
					</div>
					<div style="display:  contents;">
						<div class="B">Наименование сокр.</div>
						<div><?= $service['serviceNameShort'] ?? '<i style="color: gray;">Не указано</i>'; ?></div>
					</div>

					<div style="display:  contents;">
						<div class="B">Продолжительность</div>
						<div><?= $service['servicesDuration'] ? ($service['servicesDuration'] . 'мин.') : '<i style="color: gray;">Не указана</i>'; ?></div>
					</div>


					<div style="display:  contents;">
						<div class="B">Цена</div>
						<div>
							от <?= $service['minPrice'] ?? '<i style="color: gray;">Не указана</i>'; ?>
							до <?= $service['maxPrice'] ?? '<i style="color: gray;">Не указана</i>'; ?>
						</div>
					</div>


					<div style="display:  contents;">
						<div class="B">Специалист</div>
						<div><? foreach (query2array(mysqlQuery("SELECT * FROM `positions2services` LEFT JOIN `positions` ON (`idpositions` = `positions2servicesPosition`) WHERE `positions2servicesService` = '" . $service['idservices'] . "'")) as $position) {
				?>
								<?= $position['positionsName']; ?><br><? }
							?></div>
					</div>

					<div style="display:  contents;">
						<div class="B">Персонал</div>
						<div><? foreach (getPersonnelByService($service['idservices']) as $user) {
								?>
								<?= $user['usersLastName']; ?>
								<?= $user['usersFirstName']; ?>
								<?= $user['usersMiddleName']; ?>
								(<?= $user['positions'] ?? 'Должность не указана'; ?>)

								<br><? }
							?></div>
					</div>



				</div>
			</div>
			<?
		}
		?>



	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
