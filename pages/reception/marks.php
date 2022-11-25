<?php
$load['title'] = $pageTitle = 'Зачёт/незачёт';
$PAGEstart = microtime(1);

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (1) {
	error_reporting(E_ALL); //
	ini_set('display_errors', 1);
}


if (
		R(137) && //137 - редактирование, 129 - просмотр
		isset($_POST['client']) &&
		isset($_POST['date']) &&
		isset($_POST['check']) &&
		isset($_POST['description'])
) {

	if (mysqlQuery("INSERT INTO `score` SET "
					. " `scoreDate` = '" . mres($_POST['date']) . "',"
					. " `scoreClient` = '" . mres($_POST['client']) . "',"
					. " `scoreMarket` = " . ($_POST['check'][1] == '' ? 'null' : mres($_POST['check'][1])) . ","
//					. " `scoreMedic` = " . ($_POST['check'][2] == '' ? 'null' : mres($_POST['check'][2])) . ","
					. " `scoreDescription` = '" . mres($_POST['description']) . "',"
					. " `scoreSetBy` = '" . $_USER['id'] . "'"
					. "")) {

		if ($_POST['check'][1] == '0') {//|| $_POST['check'][2] == '0'
			$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . mres($_POST['client']) . "'"));
			if (!$client['clientsOldSince'] || $client['clientsOldSince'] == date("Y-m-d")) {
				foreach (getUsersByRights([130]) as $user) {
					if ($user['usersICQ']) {
						ICQ_messagesSend_SYNC($user['usersICQ'], "⛔ "
								. urldecode("1%EF%B8%8F%E2%83%A3") . " " . $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'] . "\r\n"
								. "Маркетинг: " . ($_POST['check'][1] == 1 ? 'Зачёт ✅' : 'Незачёт ❌') . "\r\n"
//								. "Процблок: " . ($_POST['check'][2] == 1 ? 'Зачёт ✅' : 'Незачёт ❌') . "\r\n"
								. "👉 \"" . mres($_POST['description']) . "\"\r\n"
								. $_USER['lname'] . ' ' . $_USER['fname']
						);
					}
				}
				//первичка
			} else {
				//вторичка
				foreach (getUsersByRights([131]) as $user) {
					if ($user['usersICQ']) {
						ICQ_messagesSend_SYNC($user['usersICQ'], "⛔ "
								. $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'] . "\r\n"
								. "Маркетинг: " . ($_POST['check'][1] == 1 ? 'Зачёт ✅' : 'Незачёт ❌') . "\r\n"
//								. "Процблок: " . ($_POST['check'][2] == 1 ? 'Зачёт ✅' : 'Незачёт ❌') . "\r\n"
								. "👉 \"" . mres($_POST['description']) . "\"\r\n"
								. $_USER['lname'] . ' ' . $_USER['fname']
						);
					}
				}
			}
		}

//		130	55	Уведомление о Зачёт/Незачёт Первичный
//		131		Уведомление о Зачёт/Незачёт Вторичный
//		
//		
		header("Location: " . GR());
		die();
	}
}


include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

if (!R(129)) {
	?>E403R129<?
} else {
	$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
	include 'menu.php';
	$saSQL = "SELECT *,"
			. "(SELECT MAX(`clientsVisitsTime`) FROM `clientsVisits` WHERE `clientsVisitsClient`=`idclients` AND `clientsVisitsDate`='" . ($_GET['date'] ?? date("Y-m-d")) . "') as `clientsVisitsTime`, "
			. "(SELECT COUNT(1) FROM `clientsVisits` as `PV` LEFT JOIN `score` ON (`idscore` = (SELECT MAX(`idscore`) FROM `score` WHERE `scoreDate`=`PV`.`clientsVisitsDate` AND `scoreClient`=`idclients`)) WHERE "
			. " `PV`.`clientsVisitsClient`=`idclients` AND (ISNULL(`scoreMarket`) OR `scoreMarket` <> 0) AND `PV`.`clientsVisitsDate`>DATE_SUB('" . ($_GET['date'] ?? date("Y-m-d")) . "', INTERVAL 3 MONTH) AND `PV`.`clientsVisitsDate`<'" . ($_GET['date'] ?? date("Y-m-d")) . "') as `previsit`,"
			. " "
			. " (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients` AND `f_salesDate`< '" . ($_GET['date'] ?? date("Y-m-d")) . "'  AND `f_salesType` IN (1,2)) as `sales` "
			. " FROM"
			. " `clients`"
			. " LEFT JOIN `servicesApplied` ON (`servicesAppliedClient` = `idclients` AND `servicesAppliedDate`='" . ($_GET['date'] ?? date("Y-m-d")) . "')"
			. " LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`)"
//					. " LEFT JOIN `users` AS `SA` ON (`SA`.`idusers` = `servicesAppliedBy`)"
			. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedDeletedBy`)"
			. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
			. " LEFT JOIN (SELECT * FROM `score` WHERE `idscore` IN (SELECT MAX(`idscore`) AS `idscore` FROM `score` WHERE `scoreDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'  GROUP BY `scoreClient`)) as `score` ON (`scoreClient` = `idclients`)"
			. " WHERE (`idclients` IN (SELECT `servicesAppliedClient` FROM `servicesApplied` WHERE"
			. " `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"
			. " AND NOT isnull(`servicesAppliedIsDiagnostic`)"
			. " )"
			. " OR "
			. " isnull(`clientsOldSince`) OR `clientsOldSince`='" . ($_GET['date'] ?? date("Y-m-d")) . "' "
			. " OR ((SELECT COUNT(1) FROM `clientsVisits` as `PV`  LEFT JOIN `score` ON (`idscore` = (SELECT MAX(`idscore`) FROM `score` WHERE `scoreDate`=`PV`.`clientsVisitsDate` AND `scoreClient`=`idclients`))  WHERE `PV`.`clientsVisitsClient`=`idclients` AND (ISNULL(`scoreMarket`) OR `scoreMarket` <> 0) AND `PV`.`clientsVisitsDate`>DATE_SUB('" . ($_GET['date'] ?? date("Y-m-d")) . "', INTERVAL 3 MONTH) AND `PV`.`clientsVisitsDate`<'" . ($_GET['date'] ?? date("Y-m-d")) . "')=0 AND (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients` AND `f_salesDate`< '" . ($_GET['date'] ?? date("Y-m-d")) . "' AND `f_salesType` IN (1,2))=0)"
			. " )"
			. " AND NOT isnull((SELECT MAX(`clientsVisitsTime`) FROM `clientsVisits` WHERE `clientsVisitsClient`=`idclients` AND `clientsVisitsDate`='" . ($_GET['date'] ?? date("Y-m-d")) . "'))"
			. " ";
	$servicesApplied = query2array(mysqlQuery($saSQL));
	if ($_USER['id'] == 176) {
//		printr($saSQL);
		printr($servicesApplied);
	}
	$clients = [];
	foreach ($servicesApplied as $serviceApplied) {
		$clients[$serviceApplied['idclients']]['client'] = [
			'idclients' => $serviceApplied['idclients'],
			'clientsOldSince' => $serviceApplied['clientsOldSince'],
			'clientsLName' => $serviceApplied['clientsLName'],
			'clientsFName' => $serviceApplied['clientsFName'],
			'clientsMName' => $serviceApplied['clientsMName'],
			'previsit' => $serviceApplied['previsit'],
			'sales' => $serviceApplied['sales'],
			'clientsVisitsTime' => $serviceApplied['clientsVisitsTime'],
			'scoreMarket' => $serviceApplied['scoreMarket'],
			'scoreMedic' => $serviceApplied['scoreMedic'],
			'scoreDescription' => $serviceApplied['scoreDescription']
		];
		$clients[$serviceApplied['idclients']]['services'][] = [
			'idservicesApplied' => $serviceApplied['idservicesApplied'],
			'servicesAppliedBy' => $serviceApplied['servicesAppliedBy'],
			'servicesAppliedService' => $serviceApplied['servicesAppliedService'],
			'servicesAppliedFineshed' => $serviceApplied['servicesAppliedFineshed'],
			'servicesAppliedDeleted' => $serviceApplied['servicesAppliedDeleted'],
			'servicesAppliedDeleted' => $serviceApplied['servicesAppliedDeleted'],
			'daleteReasonsName' => $serviceApplied['daleteReasonsName'],
			'daletedByUsersLastName' => $serviceApplied['usersLastName'],
			'servicesName' => $serviceApplied['servicesName'],
			'daleteReasonsCritical' => $serviceApplied['daleteReasonsCritical'],
		];
	}
//	printr($clients);

	foreach ($clients as $key => &$client2) {
		$client2['client']['clientIsNew'] = (!$client2['client']['clientsOldSince'] || intval(strtotime($client2['client']['clientsOldSince'])) >= intval(strtotime($_GET['date'] ?? date("Y-m-d"))));
	}


	if (count($clients)) {
		usort($clients, function ($a, $b) {
			$byscore = ($b['client']['scoreMarket'] == null || $b['client']['scoreMedic'] == null) <=> ($a['client']['scoreMarket'] == null || $a['client']['scoreMedic'] == null);
			if ($byscore) {
				return $byscore;
			}
			if ($a['client']['clientIsNew'] <=> $b['client']['clientIsNew']) {
				return $b['client']['clientIsNew'] <=> $a['client']['clientIsNew'];
			}
			return mb_strtolower($a['client']['clientsLName']) <=> mb_strtolower($b['client']['clientsLName']);
		});
		foreach ($clients as $client3) {
			if (count($client3['services'] ?? [])) {
				usort($client3['services'], function ($a, $b) {
					return $a['idservicesApplied'] <=> $b['idservicesApplied'];
				});
			}
		}
	}
	?>
	<div style="text-align: center;">
		<div class="box neutral" style="margin: 20px auto; text-align: left;">
			<div class="box-body">
				<h2><input type="date" value="<?= ($_GET['date'] ?? date("Y-m-d")); ?>" onchange="GETreloc('date', this.value);"></h2>

				<? if (strtotime($_GET['date'] ?? date("Y-m-d") . ' 00:00:00') > time()) {
					?>
					<img src="/css/images/vanga.jpg" alt=""/>
				<? } else {
					?>

					<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6, auto);">
						<div style="display: contents;" class="B C">
							<div style="grid-row: span 2;display: flex; align-items: center; justify-content: center;">#</div>
							<div style="grid-row: span 2;display: flex; align-items: center; justify-content: center;">ФИО клиента</div>
							<div style="grid-row: span 2;display: flex; align-items: center; justify-content: center;">Информация</div>
							<div style="grid-row: span 2;display: flex; align-items: center; justify-content: center;">Зачёт/Незачёт</div>
							<div style="grid-row: span 2;display: flex; align-items: center; justify-content: center;"></div>
							<div style="grid-row: span 2;display: flex; align-items: center; justify-content: center;">Причина незачёта</div>
						</div>

						<!--<div style="display: contents;" class="B C">-->
						<!--<div>Маркетинг</div>-->
						<!--<div>Врачи</div>-->
						<!--</div>-->
						<script>
							function checkThisForm(form) {
								if ((form['check[1]'].value == '0') && form.description.value.length == 0) {
									MSG('<?= $_USER['fname']; ?>, укажите причину незачёта, пожалуйста.');
									return false;
								}
								return true;
							}
						</script>
						<?
						$n = 0;
						foreach ($clients as $client) {
							?><form style="display: contents;"  method="post" onsubmit="return checkThisForm(this);">
								<input type="hidden" name="client" value="<?= $client['client']['idclients']; ?>">
								<input type="hidden" name="date" value="<?= ($_GET['date'] ?? date("Y-m-d")); ?>">
								<div class="R"><?= ++$n; ?></div>
								<div style="display: flex; align-items: center;">
									<?
									if ($client['client']['clientsOldSince'] < ($_GET['date'] ?? date("Y-m-d")) && $client['client']['previsit'] == 0 && $client['client']['sales'] == 0) {
										?><i class="fas fa-redo" style="color: orange;" title="Повторное приглашение первичного клиента"></i><?
									}
									if ($client['client']['clientsOldSince'] ?? false) {
										if ($client['client']['clientsOldSince'] == ($_GET['date'] ?? date("Y-m-d"))) {
											?><i class="fas fa-angle-double-up" style="color: hsl(120,100%,30%);" title="Первичный клиент"></i><?
										}
									} else {
										?><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i><?
										}
										?>

									<a href="/pages/reception/?client=<?= $client['client']['idclients'] ?>&date=<?= ($_GET['date'] ?? date("Y-m-d")); ?>" target="_blank">
										<?= $client['client']['clientsLName'] ?>
										<?= $client['client']['clientsFName'] ?>
										<?= $client['client']['clientsMName'] ?>
									</a>
								</div>
								<div style="display: flex; align-items: center;">
									<?
//						$client['services']

									$deletedServices = array_filter($client['services'], function ($service) {
										return !!$service['servicesAppliedDeleted'];
									});
									$passedServices = array_filter($client['services'], function ($service) {
										return ($service['servicesAppliedService'] != 362 && $service['servicesAppliedFineshed'] && !$service['servicesAppliedDeleted']);
									});

									$diagnosticsDone = array_filter($client['services'], function ($service) {
										return ($service['servicesAppliedService'] == 362 && $service['servicesAppliedFineshed']);
									});
									?>

									<div style="padding: 3px;">
										<i class="fas fa-walking" title="<?= ($client['client']['clientsVisitsTime'] ?? false) ? ('Визит зафиксирован в ' . date("H:i", mystrtotime($client['client']['clientsVisitsTime']))) : 'Визит не зафиксирован'; ?>" style="color:<?= ($client['client']['clientsVisitsTime'] ?? false) ? 'green' : 'red'; ?>;"></i>
									</div>
									<div style="padding: 3px; display: none;">
										<?= count($diagnosticsDone) > 0 ? '' : '<i class="far fa-check-square" style="color: red;" title="Диагностика не пройдена"></i>'; ?>
										<?
										foreach ($diagnosticsDone as $diagnosticDone) {
											?>
											<i class="far fa-check-square" style="padding: 2px;color: <?= $diagnosticDone['servicesAppliedDeleted'] ? 'orange' : 'green' ?>;" title="Диагностика пройдена <?= $diagnosticDone['servicesAppliedDeleted'] ? 'но удалена' : '' ?>"></i>
											<?
										}
										?>
									</div>
									<?
									foreach ($deletedServices as $deletedService) {
										?>
										<div style="padding: 3px;"><i class="fas fa-times" style="color: <?= $deletedService['daleteReasonsCritical'] ?? 'gray'; ?>;" title="<?= $deletedService['servicesName']; ?> <?= $deletedService['daleteReasonsName']; ?>"></i></div>
											<?
										}
//							printr($deletedServices);
//								 [scoreMarket] => 
//            [scoreMedic] => 
//            [scoreDescription] => 
										?></div>
								<div>
									<select name="check[1]"  <?= !R(137) ? 'disabled' : ''; ?> autocomplete="off">
										<option value=""></option>
										<option value="1"<?= $client['client']['scoreMarket'] == '1' ? ' selected' : ''; ?>>Зачёт</option>
										<option value="0"<?= $client['client']['scoreMarket'] == '0' ? ' selected' : ''; ?>>Незачёт</option>
									</select>
								</div>
								<!--								<div>
																	<select name="check[2]"  <?= !R(137) ? 'disabled' : ''; ?>>
																		<option value=""></option>
																		<option value="1"<?= $client['client']['scoreMedic'] == '1' ? ' selected' : ''; ?>>Зачёт</option>
																		<option value="0"<?= $client['client']['scoreMedic'] == '0' ? ' selected' : ''; ?>>Незачёт</option>
																	</select>
																</div>-->
								<div><input type="submit" value="Ok"  <?= !R(137) ? 'disabled' : ''; ?>></div>
								<div><input type="text" name="description" size="80"  <?= !R(137) ? 'readonly' : ''; ?> value="<?= htmlentities($client['client']['scoreDescription']); ?>"></div>
							</form><?
						}
						?>


					</div>
					<? // printr($clients);        ?>
					<? //printr($servicesApplied);     ?>
				<? } ?>
			</div>
		</div>
	</div>
	<?
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
