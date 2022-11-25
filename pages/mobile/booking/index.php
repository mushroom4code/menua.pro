<?php
$load['title'] = $pageTitle = 'Запись на приём';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(79)) {


	if (($_GET['save'] ?? '') == '1') {
		printr('saving');
		if (!($_GET['idclients'] ?? false)) {
//			printr('not a client');
			mysqlQuery("INSERT INTO `clients` SET "
					. " `clientsLName` = " . ((trim($_GET['clientLname'] ?? '') ? ("'" . (trim($_GET['clientLname']) . "'")) : 'null')) . ","
					. " `clientsFName` = " . ((trim($_GET['clientFname'] ?? '') ? ("'" . (trim($_GET['clientFname']) . "'")) : 'null')) . ","
					. " `clientsMName` = " . ((trim($_GET['clientMname'] ?? '') ? ("'" . (trim($_GET['clientMname']) . "'")) : 'null')) . ", "
					. " `clientsBDay` = " . ((trim($_GET['clientsBDay'] ?? '') ? ("'" . (trim($_GET['clientsBDay']) . "'")) : 'null')) . ", "
				
					
					. " `clientsAddedBy` = '" . $_USER['id'] . "',"
					. " `clientsGender` = " . (isset($_GET['clientGender']) ? mysqli_real_escape_string($link, $_GET['clientGender']) : 'null') . ","
					. " `clientsSource` = '3'"
					. "");
			$_GET['idclients'] = mysqli_insert_id($link);
			//idclientsPhones, clientsPhonesClient, clientsPhonesPhone, clientsPhonesType, clientsPhonesInvalid, clientsPhonesDeleted
			mysqlQuery("INSERT INTO `clientsPhones` SET "
					. "`clientsPhonesPhone` = '" . mysqli_real_escape_string($link, $_GET['clientPhone']) . "',"
					. "`clientsPhonesClient` = '" . $_GET['idclients'] . "'"
					. "");
		}

		$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mysqli_real_escape_string($link, $_GET['service']) . "'"));
		mysqlQuery("INSERT INTO `servicesApplied` SET"
				. " `servicesAppliedService`='" . $service['idservices'] . "',"
				. " `servicesAppliedClient`='" . mysqli_real_escape_string($link, $_GET['idclients']) . "',"
				. " `servicesAppliedPersonal`='" . mysqli_real_escape_string($link, $_GET['personnel']) . "',"
				. " `servicesAppliedDate`='" . mysqli_real_escape_string($link, $_GET['date']) . "',"
				. " `servicesAppliedTimeBegin`='" . mysqli_real_escape_string($link, $_GET['date'] . ' ' . $_GET['time'] . ':00') . "',"
				. " `servicesAppliedTimeEnd`='" . (date("Y-m-d H:i:s", (strtotime($_GET['date'] . ' ' . $_GET['time'] . ':00') + $service['servicesDuration'] * 60))) . "',"
//				. " `servicesAppliedIsFree`=" . ($_GET['price'] > 0 ? 'null' : '1') . ","
				. " `servicesAppliedPrice`=" . ($_GET['price'] > 0 ? intval($_GET['price']) : '0') . ","
				. " `servicesAppliedBy`='" . $_USER['id'] . "',"
				. " `servicesAppliedByReal`='" . $_USER['id'] . "',"
				. " `servicesAppliedQty`=1"
				. "");
		header("Location: /pages/mobile/booking/");
	}


//    [] => 2020-09-07
//    [] => 319
//    [] => 12:30
//    [] => 282
//    [price] => 0
//	  [clientPhone] => 123456789
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(79)) {
	?>E403R79<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/mobile/menu.php';

	$recents = query2array(mysqlQuery("SELECT count(1) as `qty`,`servicesAppliedService`"
					. " FROM `servicesApplied`"
					. " WHERE `servicesAppliedBy` = '" . $_USER['id'] . "'"
					. " AND isnull(`servicesAppliedContract`)"
//					. " AND `servicesAppliedAt`>=DATE_SUB(curdate(), interval 6 day)"
					. " GROUP BY `servicesAppliedService` ORDER BY `qty` DESC LIMIT 15"));
//	printr($recents);
	if (count($recents)) {
		$recentServices = query2array(mysqlQuery("SELECT"
						. " `idequipment`, "
						. " `equipmentQty`, "
						. " `idservices` as `idservices`, "
						. " `servicesName` as `name`,"
						. " `servicesTypesName` as `typeName`,"
						. " (SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `servicesPrice` "
						. " FROM `services` "
						. " LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) "
						. " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`)"
						. " WHERE isnull(`servicesDeleted`) "
						. " AND `idservices` in (" . implode(',', array_column($recents, 'servicesAppliedService')) . ")"));
	} else {
		$recentServices = [];
	}
	$services = query2array(mysqlQuery("SELECT * FROM `services` WHERE isnull(`servicesDeleted`)"));
	usort($services, function ($a, $b) {
		return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
	});
	?>
	<style>
		.lightGrid {
			border-left: 1px solid silver;
			border-top:  1px solid silver;
			background-color: white;
		}

		.lightGrid>div:hover div {
			background-color: hsl(180, 80%, 95%);;
		}
		.lightGrid>a:hover div {
			background-color: hsl(180, 80%, 95%);;
		}



		.lightGrid>div>div {
			padding: 3px;
			border-right: 1px solid silver;
			border-bottom:  1px solid silver;
		}



		.lightGrid>a>div {
			padding: 3px;
			border-right: 1px solid silver;
			border-bottom:  1px solid silver;
		}


		.menuButton {
			display: inline-block;
			border: 1px solid gray; 
			font: 2em/1em Arial;
			padding: 10px;
			margin: 5px;
			border-radius: 7px;
		}

	</style>
	<div class="box neutral" id="scrl">
		<div class="box-body">
			<div class="C" style="margin-bottom: 20px;">
				<div class="menuButton" style="background-color: <?= (($_GET['service'] ?? false) && $_GET['service'] != '') ? 'lightgreen' : 'pink' ?>;"><a href="<?= GR('page', 'service'); ?>">П</a></div>
				<div class="menuButton" style="background-color: <?= (($_GET['date'] ?? false) && $_GET['date'] != '') ? 'lightgreen' : 'pink' ?>;"><a href="<?= GR('page', 'date'); ?>">Д</a></div>
				<div class="menuButton" style="background-color: <?= (($_GET['time'] ?? false) && $_GET['time'] != '') ? 'lightgreen' : 'pink' ?>;"><a href="<?= GR('page', 'time'); ?>">В</a></div>
				<div class="menuButton" style="background-color: <?= (($_GET['price'] ?? '') != '') ? 'lightgreen' : 'pink' ?>;"><a href="<?= GR('page', 'price'); ?>">Ц</a></div>
				<div class="menuButton" style="background-color: <?=
				((
				($_GET['clientFname'] ?? '') != '' &&
				($_GET['clientPhone'] ?? '') != '' &&
				($_GET['clientLname'] ?? '') != '') || ($_GET['idclients'] ?? '') != ''
				) ? 'lightgreen' : 'pink'
				?>;"><a href="<?= GR('page', 'client'); ?>">К</a></div>
				<br>
				<?
				$ok = (($_GET['service'] ?? false) && $_GET['service'] != '') &&
						(($_GET['date'] ?? false) && $_GET['date'] != '') &&
						(($_GET['time'] ?? '') && $_GET['time'] != '') &&
						(($_GET['price'] ?? '') != '') &&
						((
						($_GET['clientFname'] ?? '') != '' &&
						($_GET['clientPhone'] ?? '') != '' &&
						($_GET['clientLname'] ?? '') != '') || ($_GET['idclients'] ?? '') != ''
						);
				?>
				<div class="menuButton" style="background-color: <?= ($ok) ? 'lightgreen' : 'pink'
				?>;"><?
					 if ($ok) {
						 ?><a href="<?= GR('save', true); ?>">Записать</a><?
					} else {
						?>Заполнить данные<?
					}
					?></div>
			</div>
			<?
			if (($_GET['page'] ?? '') == 'client') {
				if (($_GET['search'] ?? '') == 'true') {

					$searchby = [];
					if (($_GET['clientLname'] ?? '') != '') {
						$searchby[] = "`clientsLName` LIKE '%" . mysqli_real_escape_string($link, trim($_GET['clientLname'])) . "%'";
					}
					if (($_GET['clientFname'] ?? '') != '') {
						$searchby[] = "`clientsFName` LIKE '%" . mysqli_real_escape_string($link, trim($_GET['clientFname'])) . "%'";
					}
					if (($_GET['clientMname'] ?? '') != '') {
						$searchby[] = "`clientsMName` LIKE '%" . mysqli_real_escape_string($link, trim($_GET['clientMname'])) . "%'";
					}
					if (($_GET['clientPhone'] ?? '') != '') {
						$searchby[] = "`clientsPhonesPhone` LIKE '%" . mysqli_real_escape_string($link, trim($_GET['clientPhone'])) . "%'";
					}
					if (count($searchby)) {

						$searchQuery = "SELECT * FROM `clients` LEFT JOIN `clientsPhones` ON (`clientsPhonesClient` = `idclients`) WHERE isnull(`clientsPhonesDeleted`) AND " . implode(" AND ", $searchby);
//						print $searchQuery;
						$searchResult = query2array(mysqlQuery($searchQuery));
						if (count($searchResult)) {
							?>
							<div style="display: grid; grid-template-columns: auto auto auto; border-top: 2px solid gray; border-right: 2px solid gray;" class="lightGrid"><?
								foreach ($searchResult as $s_client) {
//									printr($client);
									?>
									<a href="<?= GR2(['search' => null, 'idclients' => $s_client['idclients']]); ?>" style="display: contents;">
										<div style="border-left: 2px solid gray;"><?= $s_client['clientsLName']; ?></div>	
										<div><?= $s_client['clientsFName']; ?></div>	
										<div><?= $s_client['clientsMName']; ?></div>	
										<div style="grid-column: 1/-1; border-left: 2px solid gray; border-bottom: 2px solid gray;"><?
											if (trim($s_client['clientsPhonesPhone'])) {
												?><?= $s_client['clientsPhonesPhone']; ?><? } else {
												?>Телефон не указан<?
											}
											?></div>
									</a>
									<?
								}
								?></div><?
						} else {
							?>Клиенты не найдены<?
							}
						} else {
							?>Отсутствуют параметры поиска!<?
					}
				}
				if ($_GET['idclients'] ?? false) {
					$client = mfa(mysqlQuery("SELECT * FROM `clients` LEFT JOIN `clientsPhones` ON (`clientsPhonesClient` = `idclients`) WHERE `idclients` = '" . mysqli_real_escape_string($link, $_GET['idclients']) . "'"));
//					printr($client);
//					https://menua.pro/pages/mobile/booking/index.php?page=client&date=2020-09-07&service=319&time=12%3A30&personnel=282&price=0&clientLname=%D0%A2%D0%B5%D1%81%D1%82&search=true
				}
				?>
				<div style="padding: 10px;"><input autocomplete="off" style="border: 1px solid silver; font-size: 2em;" id="clientLname" value="<?= ($client['clientsLName'] ?? $_GET['clientLname'] ?? ''); ?>" type="text" placeholder="Фамилия"></div>
				<div style="padding: 10px;"><input autocomplete="off" style="border: 1px solid silver; font-size: 2em;" id="clientFname" value="<?= ($client['clientsFName'] ?? $_GET['clientFname'] ?? ''); ?>" type="text" placeholder="Имя"></div>
				<div style="padding: 10px;"><input autocomplete="off" style="border: 1px solid silver; font-size: 2em;" id="clientMname" value="<?= ($client['clientsMName'] ?? $_GET['clientMname'] ?? ''); ?>" type="text" placeholder="Отчество"></div>
				<div style="padding: 10px;"><input autocomplete="off" style="border: 1px solid silver; font-size: 2em;" id="clientPhone" value="<?= ($client['clientsPhonesPhone'] ?? $_GET['clientPhone'] ?? ''); ?>" oninput="digon();" type="text" placeholder="Телефон"></div>

				<div style="padding: 10px;"><select autocomplete="off" style="border: 1px solid silver; font-size: 2em;" id="clientGender" placeholder="Телефон">
						<option value="">Указать пол</option>
						<option value="0" <?= ($client['clientsGender'] ?? $_GET['clientGender'] ?? '') == '0' ? ' selected' : ''; ?>>Жен</option>
						<option value="1" <?= ($client['clientsGender'] ?? $_GET['clientGender'] ?? '') == '1' ? ' selected' : ''; ?>>Муж</option>
					</select></div>
				<div style="padding: 10px;">
					<input type="date" name="clientsBDay" id="clientsBDay" style="border: 1px solid silver; font-size: 2em;">
				</div>
				<div style="display: grid; grid-template-columns: auto auto; grid-gap: 10px;">
					<input type="button" value="Добавить" style=" font-size: 2em;" onclick="GR({
										search: false,
										idclients: null,
										clientGender: qs('#clientGender').value || null,
										clientLname: qs('#clientLname').value.trim(),
										clientFname: qs('#clientFname').value.trim(),
										clientMname: qs('#clientMname').value.trim(),
										clientPhone: qs('#clientPhone').value.trim(),
										clientsBDay: qs('#clientsBDay').value.trim()
									});">
					<input type="button" onclick="GR({
										search: true,
										idclients: null,
										clientGender: qs('#clientGender').value || null,
										clientLname: qs('#clientLname').value.trim(),
										clientFname: qs('#clientFname').value.trim(),
										clientMname: qs('#clientMname').value.trim(),
										clientPhone: qs('#clientPhone').value.trim()
									});" value="Найти" style=" font-size: 2em;">
				</div>
				<?
			}
			if (($_GET['page'] ?? '') == 'service') {
				?>

				<? if ($_GET['service'] ?? false) {
					?>
					<div style="text-align: center; padding: 10px; margin: 5px; background-color: lightgreen; border: 1px solid gray; font-weight: bolder; font-size: 1.2em;"><?= array_search_2d($_GET['service'], $services, 'idservices')['servicesName'] ?></div>
				<? } else {
					?>
					<div style="text-align: center; padding: 10px; margin: 5px; background-color: pink; border: 1px solid gray; font-weight: bolder; font-size: 1.2em;"> Не выбрана процедура</div>
				<? } ?>

				<div>
					<select onchange="GR({service: this.value});">
						<option></option>
						<? foreach ($services as $service) {
							?><option value="<?= $service['idservices']; ?>"><?= $service['servicesName']; ?></option><? }
						?>
					</select>
				</div><?
//					printr($recentServices);
				usort($recentServices, function ($a, $b) {
					return mb_strtolower($a['name']) <=> mb_strtolower($b['name']);
				});
				foreach ($recentServices as $service) {
					?>
					<div style="border: 1px solid silver; background-color: white; margin: 10px 3px; padding: 3px 10px; font-size: 1.5em; line-height: 1.5em;"><a href="<?= GR('service', $service['idservices']); ?>"><?= $service['name']; ?></a></div>
					<?
				}
			}


			if (($_GET['page'] ?? '') == 'date') {
				?>
				<div style="display: grid; grid-template-columns:auto auto;">
					<input type="date" id="date" value="<?= $_GET['date'] ?? ''; ?>">
					<input type="button" onclick="GR({date: (qs('#date').value)});" value="ok">
				</div>
				<?
			}




			if (($_GET['page'] ?? '') == 'price') {
				?>
				<div>
					<input type="text" oninput="digon();" id="price" value="<?= ($_GET['price'] ?? '') ?>" placeholder="Цена" style="font-size: 2em; border: 1px solid silver; text-align: center;">		
				</div>
				<div class="C" style="padding-top: 2em;">
					<input type="button" style="font-size: 2em;" onclick="GR({price: qs('#price').value});" value="Сохранить">		
				</div>

				<?
			}
			if (($_GET['page'] ?? '') == 'time') {

				if (($_GET['date'] ?? false) && ($_GET['service'] ?? false)) {


					$equipment = mfa(mysqlQuery("SELECT * FROM `services` LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`) WHERE `idservices` = '" . $_GET['service'] . "'"));
					$idequipment = $equipment['servicesEquipment'];
					$equipmentQty = $equipment['equipmentQty'];

					$personnelSQL = "SELECT `idusers`, `usersLastName`, `usersFirstName`,`usersDeleted`,`usersScheduleFrom`,`usersScheduleTo` "
							. " FROM `positions2services` "
							. " LEFT JOIN `usersPositions` ON (`usersPositionsPosition` = `positions2servicesPosition`) "
							. " LEFT JOIN `users` ON (`idusers` = `usersPositionsUser`) "
							. " LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers` AND `usersScheduleDate` = '" . $_GET['date'] . "')"
							. " LEFT JOIN `users2services` ON (`users2servicesUser` = `idusers`)"
							. " WHERE `positions2servicesService` = '" . $_GET['service'] . "' "
							. " AND isnull(`usersDeleted`) "
							. "AND ((SELECT
            COUNT(1)
        FROM
            `users2services`
        WHERE
            `users2servicesExclude` = `positions2servicesService`
                AND `users2servicesUser` = `idusers`) = 0
        OR (SELECT
            COUNT(1)
        FROM
            `users2services`
        WHERE
            `users2servicesInclude` = `positions2servicesService`
                AND `users2servicesUser` = `idusers`) > 0)"
							. " AND NOT isnull(`idusers`) "
							. " AND NOT isnull(`idusersSchedule`) "
							. " GROUP BY `idusers`,`idusersSchedule`";

					$personnelSQL = "SELECT `idusers`, `usersLastName`, `usersFirstName`,`usersDeleted`,`usersScheduleFrom`,`usersScheduleTo` "
							. " FROM `users` "
							. " LEFT JOIN `usersPositions` ON (`idusers` = `usersPositionsUser`) "
							. " LEFT JOIN  `positions2services` ON (`usersPositionsPosition` = `positions2servicesPosition`) "
							. " LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers` AND `usersScheduleDate` = '" . $_GET['date'] . "')"
							. " LEFT JOIN `users2services` ON (`users2servicesUser` = `idusers`)"
							. " WHERE "
							. " (isnull(`usersDeleted`) OR (`usersDeleted`>'" . $_GET['date'] . " 23:59:59'))"
							. (isset($_GET['service']) ? ("AND "
							. ""
							. "("
							. "`positions2servicesService` = '" . $_GET['service'] . "'  "
							. "OR (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = '" . $_GET['service'] . "' AND `users2servicesUser` = `idusers`)>0"
							. ")"
							. " AND (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesExclude` = '" . $_GET['service'] . "' AND `users2servicesUser` = `idusers`) = 0"
							. "") : '')
							. " AND NOT isnull(`idusers`) "
							. ""
							. " AND `usersGroup` IN (1,2,3,4,5,6,7,10,11)"
							. " AND NOT isnull(`idusersSchedule`) "
							. " GROUP BY `idusers`,`idusersSchedule`";

					$personnel = query2array(mysqlQuery($personnelSQL));

//idequipment

					if (count($personnel)) {
						$servicesApplied = query2array(mysqlQuery("SELECT * FROM "
										. " `servicesApplied` "
										. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
										. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
										. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
										. " LEFT JOIN `equipment` ON (`idequipment` = `servicesEquipment`)"
										. " WHERE `servicesAppliedPersonal` IN (" . implode(',', array_column($personnel, 'idusers')) . ")"
										. " AND `servicesAppliedDate` = '" . $_GET['date'] . "'"
										. " AND isnull(`servicesAppliedDeleted`)"));

						if ($idequipment) {
							$equipment['idequipment'] = $idequipment;
							$start = null;
							$finish = null;
							$lastState = false;
							for ($time = strtotime($_GET['date'] . ' 08:00:00'); $time <= strtotime($_GET['date'] . ' 22:00:00'); $time += 60 * 5) {

								$nowused = count(obj2array(array_filter($servicesApplied, function ($element) {
													global $time, $idequipment;
													if ($idequipment == $element['idequipment']) {
														if ($time >= strtotime($element['servicesAppliedTimeBegin']) && $time < strtotime($element['servicesAppliedTimeEnd'])) {
															return true;
														}
													}
													return false;
												})));
								$state = $nowused >= $equipmentQty;
								if (!$lastState && $state) {
									$lastState = $state;
									$start = $time;
								}
								if ($lastState && !$state) {
									$lastState = $state;
									$finish = $time;
									$equipment['time'][] = [
										'from' => date("Y-m-d H:i:s", $start),
										'to' => date("Y-m-d H:i:s", $time),
									];
									$start = null;
									$finish = null;
								}
							}
						}




						foreach ($personnel as &$person2) {
							$person2['services'] = obj2array(array_filter($servicesApplied, function ($element) {
										global $person2;
										return $element['servicesAppliedPersonal'] == $person2['idusers'];
									}));
						}
					}


					usort($personnel, function ($a, $b) {
						return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
					});

					json_encode([
						'personnel' => $personnel,
//						'equipment' => $equipment ?? [],
//		'sql' => $personnelSQL,
						'success' => true], 288);

//					printr(['$equipment' => $equipment]);

					$start = strtotime($_GET['date'] . ' 10:00:00');
					$end = strtotime($_GET['date'] . ' 19:30:00');

					$time = $start;
					?><div class="lightGrid" style="display: grid; grid-template-columns: <? for ($n = 1; $n <= count($personnel); $n++) { ?>auto <? } ?>;">
						<div style="display: contents;">
							<?
							foreach ($personnel as $user) {
								?><div class="C B"><?= $user['usersLastName'] . ' ' . mb_substr($user['usersFirstName'], 0, 1); ?>.</div><?
							}
							?>
						</div>

						<div style="display: contents;">
							<?
							foreach ($personnel as $user) {
								?><div>
									<?
									for ($time = $start; $time <= $end; $time += 60 * 30) {
										if (!count(array_filter(($equipment['time'] ?? []), function ($el) {
															global $time;

															return $time >= strtotime($el['from']) && $time < strtotime($el['to']);
														})) &&
												!count(array_filter($servicesApplied, function ($el) {
															global $time, $user;
															return ($user['idusers'] == $el['servicesAppliedPersonal'] && $time >= strtotime($el['servicesAppliedTimeBegin']) && $time < strtotime($el['servicesAppliedTimeEnd']));
														}))
										) {
											?><div class="C" style="font-size: 2em;"><a href="<?= GR2(['time' => date("H:i", $time), 'personnel' => $user['idusers']]); ?>" style="border: 1px solid silver; padding: 10px 10px; display: inline-block; margin: 10px; border-radius: 5px; background-color: <?= (date("H:i", $time) == ($_GET['time'] ?? '') && $user['idusers'] == ($_GET['personnel'] ?? '')) ? '#a0FFa0' : '#F8FFF8'; ?>"><?= date("H:i", $time); ?></a></div><?
										}
									}
									?>

								</div><?
							}
							?>
						</div>

					</div><?
				} else {
					?>
					<div style="text-align: center; padding: 10px; margin: 5px; background-color: pink; border: 1px solid gray; font-weight: bolder; font-size: 1.2em;"> Не выбрана дата или процедура</div>
					<?
				}
				?>

				<?
			}






//			printr($_GET);
			?></div>
	</div>
	</div>
	</div>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
	<br>
<? }
?>
<script>
	document.addEventListener("DOMContentLoaded", function () {
		setTimeout(function () {
			qs('#scrl').scrollIntoView();
		}, 100);
	});

</script>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
