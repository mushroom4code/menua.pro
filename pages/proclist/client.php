<?php
$load['title'] = $pageTitle = 'Процедурный лист';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_POST) {
	printr($_POST);

//	id, , , , 
	mysqlQuery("INSERT INTO `medrecords` SET "
			. "`medrecordsClient` = '" . mres($_GET['client']) . "',"
			. "`medrecordsUser` = '" . mres($_GET['personal'] ?? $_USER['id']) . "', "
			. "`medrecordsForm` =" . sqlVON($_POST['form'] ?? null) . ", "
			. "`medrecordsServiceApplied` =" . sqlVON($_POST['serviceApplied'] ?? null) . ", "
			. "`medrecordsFormData` = '" . mres(json_encode(($_POST['formdata'] ?? []), 288)) . "'");
	header("Location: " . GR2(['mr' => mysqli_insert_id($link)]));
	exit();
}
if (R(45)) {

	if (($_POST['serviceApplySubscriptions'] ?? false) && ($_POST['serviceApplyQty'] ?? false)) {

//		printr($f_subscription);
		if (1) {


			if (1) {
				$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($_POST['idservices'] ?? '') . "'"));
				if (mysqlQuery("INSERT INTO `servicesApplied` SET "
								. " `servicesAppliedService` = '" . mres($_POST['idservices']) . "',"
								. " `servicesAppliedQty`= '" . mres($_POST['serviceApplyQty']) . "',"
								. " `servicesAppliedClient` ='" . mres($_GET['client']) . "',"
								. " `servicesAppliedContract` ='" . mres($_POST['f_subscriptionsContract']) . "',"
								. " `servicesAppliedPrice` ='" . mres($_POST['f_salesContentPrice']) . "',"
								. " `servicesAppliedBy` = '" . mres($_GET['personal'] ?? $_USER['id']) . "',"
								. " `servicesAppliedPersonal` = '" . mres($_GET['personal'] ?? $_USER['id']) . "',"
								. " `servicesAppliedDate` = CURDATE(),"
								. " `servicesAppliedAt` = NOW(),"
								. " `servicesAppliedTimeBegin` = NOW(),"
								. " `servicesAppliedStarted` = NOW(),"
								. " `servicesAppliedStartedBy` = '" . mres($_GET['personal'] ?? $_USER['id']) . "',"
								. " `servicesAppliedTimeEnd` = '" . date("Y-m-d H:i:s", time() + 60 * ( $service['servicesDuration'] ?? 60 )) . "',"
								. " `servicesAppliedByReal` = '" . mres($_GET['personal'] ?? $_USER['id']) . "'")) {
					header("Location: " . GR());
					exit('ok');
				} else {
					die(mysqli_error($link));
				}
			}
		}
	}


	if (($_GET['addSaleDraft'] ?? false) && ($_GET['client'] ?? false) && (1/* ПРАВО НА РАБОТУ С ПЛАНАМИ ЛЕЧЕНИЯ */)) {
		if (mysqlQuery("INSERT INTO `f_salesDraft` SET "
						. " `f_salesDraftClient`='" . mres($_GET['client']) . "',"
						. " `f_salesDraftDate`=CURDATE(),"
						. " `f_salesDraftAuthor`='" . mres($_GET['personal'] ?? $_USER['id']) . "',"
						. " `f_salesDraftNumber` = "
						. " (SELECT * FROM (SELECT ifnull(MAX(`f_salesDraftNumber`),0)+1 FROM `f_salesDraft` WHERE `f_salesDraftClient`='" . mres($_GET['client']) . "') as `maxNum`)"
						. "")) {
			header("Location: " . GR2(['addSaleDraft' => null, 'saleDraftTemplate' => null, 'saleDraft' => mysqli_insert_id($link)]));
			die();
		}
	}

	if (($_GET['saleDraft'] ?? false) && !mfa(mysqlQuery("SELECT * FROM `f_salesDraft` WHERE  `idf_salesDraft`='" . mres($_GET['saleDraft']) . "'"))) {
		header("Location: " . GR2(['saleDraft' => null]));
		die();
	}

	if (($_GET['deleteSaleDraft'] ?? false) && ($_GET['client'] ?? false) && (1/* ПРАВО НА РАБОТУ С ПЛАНАМИ ЛЕЧЕНИЯ */)) {
		if (mysqlQuery("UPDATE `f_salesDraft`"
						. " SET `f_salesDraftDeleted` = NOW() "
						. " WHERE  "
						. " `idf_salesDraft`='" . mres($_GET['deleteSaleDraft']) . "'"
						. "")) {
			header("Location: " . GR2(['saleDraft' => null, 'deleteSaleDraft' => null]));
			die();
		}
	}


	if (($_GET['deleteSaleDraftTemplate'] ?? false) && ($_GET['client'] ?? false) && (1/* ПРАВО НА РАБОТУ С ПЛАНАМИ ЛЕЧЕНИЯ */)) {
		if (mysqlQuery("UPDATE `f_salesDraftTemplates`"
						. " SET `f_salesDraftTemplatesDeleted` = NOW() "
						. " WHERE  "
						. " `idf_salesDraftTemplates`='" . mres($_GET['deleteSaleDraftTemplate']) . "'"
						. "")) {
			header("Location: " . GR2(['saleDraftTemplate' => null, 'deleteSaleDraftTemplate' => null]));
			die();
		}
	}



	$servicesAppliedSQL = "SELECT * FROM "
			. " `servicesApplied` "
			. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
			. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
			. " LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA`=`idservicesApplied`) "
			. " WHERE "
			. " `servicesAppliedDate` = '" . date("Y-m-d") . "' "
			. " AND `servicesAppliedPersonal` = '" . mres($_GET['personal'] ?? $_USER['id']) . "'"
			. " AND `idclients` = '" . mres($_GET['client']) . "'"
			. " AND isnull(`servicesAppliedDeleted`)";

	$servicesApplied = query2array(mysqlQuery($servicesAppliedSQL));
	if (!count($servicesApplied)) {
		header("Location: /pages/proclist/");
		die();
	}


	if (isset($_GET['started'])) {
		if (mysqlQuery("UPDATE `servicesApplied` SET `servicesAppliedStarted` = NOW(), `servicesAppliedStartedBy` ='" . mres($_GET['personal'] ?? $_USER['id']) . "' WHERE `idservicesApplied`='" . FSI($_GET['started']) . "'")) {
			header("Location: " . GR('started', ''));
			die();
		}
	}


	if (isset($_GET['TPS_reservedDone'])) {
		if (mysqlQuery("UPDATE `TPS_reserved` SET `TPS_reservedDone` = NOW(), `TPS_reservedDoneBy` = '" . mres($_GET['personal'] ?? $_USER['id']) . "' WHERE `idTPS_reserved`='" . FSI($_GET['TPS_reservedDone']) . "'")) {
			header("Location: " . GR('TPS_reservedDone', ''));
			die();
		}
	}


	if (isset($_GET['finished'])) {
		if (mysqlQuery("UPDATE `servicesApplied` SET "
						. "`servicesAppliedFineshed` = NOW(),"
						. "`servicesAppliedFinishedBy` = '" . mres($_GET['personal'] ?? $_USER['id']) . "'"
						. "WHERE `idservicesApplied`='" . FSI($_GET['finished']) . "'")) {
			header("Location: " . GR('finished', ''));
			die();
		}
	}
}

if (R(90)) {
//	add=7297

	if (($_GET['add'] ?? false) && (count($servicesApplied))) {
		if (mysqlQuery("INSERT INTO `TPS_reserved` SET "
						. " `TPS_reservedClient` = '" . $servicesApplied[0]['servicesAppliedClient'] . "', "
						. " `TPS_reservedService` = '" . $_GET['add'] . "', "
						. " `TPS_reservedAppliedBy` = '" . mres($_GET['personal'] ?? $_USER['id']) . "', "
						. " `TPS_reservedAppliedTime` = NOW(), "
						. " `TPS_reservedContract` = " . ($_GET['contract'] ?? 'null') . ", "
						. " `TPS_reservedQty` = '1'")) {
			header("Location: " . GR2([
						'add' => null,
						'contract' => null,
						'search' => null
			]));
		}
	}


	if (($_GET['remove'] ?? false) && (count($servicesApplied))) {
		if (mysqlQuery("DELETE FROM `TPS_reserved` WHERE "
						. " `TPS_reservedClient` = '" . $servicesApplied[0]['servicesAppliedClient'] . "' "
						. " AND `TPS_reservedService` = '" . $_GET['remove'] . "' "
						. (($_GET['contract'] ?? false) ? (" AND `TPS_reservedContract` = '" . mysqli_real_escape_string($link, `TPS_reservedContract`) . "'") : " AND isnull(`TPS_reservedContract`)")
				)) {
			header("Location: " . GR2([
						'remove' => null,
						'contract' => null,
						'search' => null
			]));
		}
	}
}




include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(45)) {
	?>E403R45<? } else { ?>
	<style>
		.suggestions {
			position: absolute;
			width: auto;
			background-color: white;
			border: 1px solid silver;
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
	<script src="/sync/3rdparty/vue.min.js" type="text/javascript"></script>
	<?
//	printr($servicesApplied);
	if (count($servicesApplied)) {
		//servicesAppliedTimeBegin
		//servicesAppliedTimeBegin
		?>

		<div>
			<div class="box neutral">
				<div class="box-body" style="">
					<h2><?= mb_ucfirst($servicesApplied[0]['clientsLName']); ?> <?= mb_ucfirst($servicesApplied[0]['clientsFName']); ?> <?= mb_ucfirst($servicesApplied[0]['clientsMName']); ?> </h2>

					<?
					include 'clientsmenu.php';
					?>

					<?
					foreach ($servicesApplied as $serviceApplied) {
						if ($serviceApplied['idservices'] ?? false) {
							$serviceApplied['serviceMotivation'] = query2array(mysqlQuery("SELECT * FROM `serviceMotivation` WHERE `serviceMotivationService` = " . $serviceApplied['idservices']));
						}
						?>

						<div style="border: 1px solid silver; margin: 20px 2px; padding: 3px; background-color: white; border-radius: 5px; box-shadow: 0px 0px 10px hsla(0,0%,0%,0.2);">


							<h3 style="padding: 10px; text-align: center;"><?= $serviceApplied['servicesName'] ?? 'Услуги не выбраны'; ?> <?= (!round($serviceApplied['servicesAppliedPrice']) && !$serviceApplied['servicesAppliedContract']) ? '(Подарочная)' : ''; ?></h3>



							<? if ($serviceApplied['idservicesAppliedComments'] ?? false) {
								?><div style="font-size: 1.5em;"><i class="fas fa-info-circle" style="color:  hsl(220,100%,78%);"></i> <?= $serviceApplied['servicesAppliedCommentText']; ?></div><? }
							?>
							<h4 style="text-align: center;">

								<?= $serviceApplied['servicesAppliedStarted'] ? date("H:i", strtotime($serviceApplied['servicesAppliedStarted'])) : ($serviceApplied['servicesAppliedTimeBegin'] ? (date("H:i", strtotime($serviceApplied['servicesAppliedTimeBegin']))) : ('Не указано время начала') ); ?> -
								<?= $serviceApplied['servicesAppliedFineshed'] ? date("H:i", strtotime($serviceApplied['servicesAppliedFineshed'])) : ($serviceApplied['servicesAppliedTimeEnd'] ? (date("H:i", strtotime($serviceApplied['servicesAppliedTimeEnd']))) : ('Не указано время окончания') ); ?>
							</h4>

							<? if (!$serviceApplied['idservices']) {
								?>
								<center>Выбрать услуги оказываемые<br>по абонементу (<?= $serviceApplied['servicesAppliedContract'] ?? 'ОШИБКА'; ?>)</center>

								<?
								if ($serviceApplied['servicesAppliedContract']) {
									//найти компетенции подходящие специалисту
									$myServices = array_values(array_filter(array_column(query2array(mysqlQuery("SELECT *"
																			. " FROM `positions2services`"
																			. " WHERE `positions2servicesPosition` IN (" . implode(',', array_column($_USER['positions'], 'id')) . ")")), 'positions2servicesService')));
									$myServicesIncExc = query2array(mysqlQuery("SELECT *"
													. " FROM `users2services`"
													. " WHERE `users2servicesUser` = " . mres($_GET['personal'] ?? $_USER['id']) . ""));

									$include = array_values(array_filter(array_column($myServicesIncExc, 'users2servicesInclude')));
									$exclude = array_values(array_filter(array_column($myServicesIncExc, 'users2servicesExclude')));

									$competentions = array_merge(array_diff($myServices, $exclude), $include);
									$remainsSQL = "(SUM(`f_salesContentQty`) "
											. " - ifnull((SELECT SUM(`servicesAppliedQty`) FROM `servicesApplied` WHERE "
											. " `servicesAppliedContract` = `f_subscriptionsContract`"
											. " AND `servicesAppliedPrice` = `f_salesContentPrice`"
											. " AND `servicesAppliedService` = `f_salesContentService`"
											. " AND isnull(`servicesAppliedDeleted`)"
											. " ),0))";
									$subscriptionsSQL = "SELECT "
											. " $remainsSQL as `remains`,"
											. " `f_salesContentService`,"
											. " `f_subscriptionsContract`,"
											. " `f_salesContentPrice`,"
											. " `services`.* "
											. " FROM `f_subscriptions` "
											. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`) "
											. " WHERE "
											. "`f_subscriptionsContract` = '" . $serviceApplied['servicesAppliedContract'] . "'"
											. " AND `f_salesContentService` IN (" . implode(',', $competentions) . ")"
											. " GROUP BY `f_salesContentService`,`f_salesContentPrice`"
											. " ";
									$f_subscriptions = query2array(mysqlQuery($subscriptionsSQL));
//									printr($f_subscriptions);
									if ($_USER['id'] == 176) {
//										print $subscriptionsSQL;
//										printr($f_subscriptions, 1);
									}
									$f_subscriptions = array_filter($f_subscriptions, function ($f_subscription) {
										return $f_subscription['remains'] > 0;
									});
									if ($_USER['id'] == 176) {
//										printr($f_subscriptions, 1);
									}
									if (count($f_subscriptions)) {
//										printr($_POST);
										?>
										<div style=" display: inline-block;">
											<div class="lightGrid" style="display: grid; grid-template-columns: repeat(3,auto);">
												<div style="display: contents;">
													<div>Услуга</div>
													<div>Количество</div>
													<div></div>

												</div>

												<?
												foreach ($f_subscriptions as $f_subscription) {
													?>
													<form method="post" action="<?= GR(); ?>" style="display: contents;">
														<div>
															<input type="hidden" name="serviceApplySubscriptions" value="YES">
															<input type="hidden" name="idservices" value="<?= $f_subscription['idservices']; ?>">
															<input type="hidden" name="f_subscriptionsContract" value="<?= $f_subscription['f_subscriptionsContract']; ?>">
															<input type="hidden" name="f_salesContentPrice" value="<?= $f_subscription['f_salesContentPrice']; ?>">
															<?= $f_subscription['serviceNameShort'] ?? $f_subscription['servicesName'] ?? 'Не указано наименование'; ?></div>
														<div><select name="serviceApplyQty">
																<? for ($n = 1; $n <= $f_subscription['remains']; $n++) {
																	?> 
																	<option value="<?= $n; ?>"><?= $n; ?></option>
																<? } ?>
															</select><? ?></div>
														<div><input type="submit" value="Начать" style="background-color: lightgreen;"></div>
													</form>

												<? }
												?>
											</div>
										</div>

										<?
									} else {
										?>
										<center><span style="color: red; font-weight: bold;">Нет доступных процедур в абонементе</span></center><?
									}
								}
								?>

							<? } ?>

							<style>
								input[type=checkbox]:checked+label {
									background-color: lightgreen;

								}
							</style>
							<script>
								function saveOptions(option, SA, value) {
									console.log(option, SA, value);
									fetch('IO.php', {
										body: JSON.stringify(
												{
													action: 'saveOptions',
													option: option,
													SA: SA,
													value: value
												}),
										credentials: 'include',
										method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
									}).then(result => result.text()).then(async function (text) {
										try {
											let jsn = JSON.parse(text);
											if (!jsn.success) {
												//												document.location.reload(true);
												qs(`#c${option}`).checked = !qs(`#c${option}`).checked;
											}
										} catch (e) {
											MSG("Ошибка парсинга ответа сервера: <br><br><i>" + e + "</i>");
										}
									});
								}
								function saveValue(option, SA, value, deflt = 0, units) {
									console.log(option, SA, value);
									let box = el('div', {className: 'modal neutral'});
									box.style.position = 'fixed';
									box.style.zIndex = '5';
									box.appendChild(el('h2', {innerHTML: `Редактировать`}));
									let boxBody = el('div', {className: 'box-body'});
									box.appendChild(boxBody);
									document.body.appendChild(box);
									let qtyName = el('div');
									qtyName.innerHTML = `Количество (<b>${units}</b>): `;
									boxBody.appendChild(qtyName);
									let qtyValue = el('input');
									qtyValue.type = 'text';
									qtyValue.value = qs(`#var${option}`).innerHTML > 0 ? qs(`#var${option}`).innerHTML : deflt;
									qtyValue.addEventListener('input', digon);
									boxBody.appendChild(qtyValue);
									let cancelBtn = el('button', {innerHTML: `Отмена`});
									cancelBtn.style.margin = '0px 10px';
									box.appendChild(cancelBtn);
									cancelBtn.addEventListener('click', function () {
										box.parentNode.removeChild(box);
									});
									let addBtn = el('button', {innerHTML: `Сохранить`});
									box.appendChild(addBtn);
									addBtn.style.margin = '0px 10px';
									addBtn.addEventListener('click', async function () {
										let data = {
											action: 'saveValue',
											option: option,
											SA: SA,
											qtyValue: qtyValue.value
										};
										console.log(data);
										await fetch('IO.php', {
											body: JSON.stringify(data),
											credentials: 'include',
											method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
										}).then(result => result.text()).then(async function (text) {
											try {
												let jsn = JSON.parse(text);
												if ((jsn.msgs || []).length) {
													jsn.msgs.forEach(async msg => {
														await MSG(msg);
													});
												}

												if (jsn.success) {
													box.parentNode.removeChild(box);
													qs(`#var${option}`).innerHTML = `${qtyValue.value > 0 ? qtyValue.value : '0'}`;
													//													GETreloc('rnd', Math.random());

												} else {
													qs(`#var${option}`).innerHTML = `-`;
												}
											} catch (e) {
												MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
											}
										}); //fetch

									});
								}
							</script>
							<?
							if (1) {
								?>
								<div style="border: 0px solid red;">
									<?
									$options = query2array(mysqlQuery("SELECT * "
													. " FROM `servicesPrimecost`"
													. " LEFT JOIN `WH_nomenclature` ON (`idWH_nomenclature` = `servicesPrimecostNomenclature`)"
													. " LEFT JOIN `servicesAppliedOptions` ON (`servicesAppliedOptionsOption` = `idservicesPrimecost` AND `servicesAppliedOptionsSA` = '" . $serviceApplied['idservicesApplied'] . "') "
													. " LEFT JOIN `units` ON (`idunits`=`WH_nomenclatureUnits`)"
													. " WHERE `servicesPrimecostService` = '" . $serviceApplied['servicesAppliedService'] . "'"
													. " AND (NOT isnull(`servicesPrimecostIsOptional`) OR NOT isnull(`servicesPrimecostVariable`))"));
									?><div style="text-align: center;" id="servicesPrimecostOptions"><?
									if (count($options)) {
										?>
											<h3  style="padding: 10px;">Расходные материалы:</h3>
											<?
											uasort($options, function ($a, $b) {
												if ($a['servicesPrimecostVariable'] <=> $b['servicesPrimecostVariable']) {
													return $b['servicesPrimecostVariable'] <=> $a['servicesPrimecostVariable'];
												}
												return mb_strtolower($a['WH_nomenclatureName']) <=> mb_strtolower($b['WH_nomenclatureName']);
											});
											foreach ($options as $option) {
												if ($serviceApplied['servicesAppliedFineshed'] && !$option['servicesAppliedOptionsSA']) {
													continue;
												}
												?>
												<?
												if ($_USER['id'] == 176) {
//													printr($option);
												}

												if ($option['servicesPrimecostVariable']) {
													?>
													<div <? if (!$serviceApplied['servicesAppliedFineshed']) { ?>onclick="saveValue(<?= $option['idservicesPrimecost']; ?>, <?= $serviceApplied['idservicesApplied']; ?>, '<?= floatval($option['servicesAppliedOptionsQty']); ?>', '<?= floatval($option['servicesPrimecostNomenclatureQty']); ?>', '<?= htmlentities($option['unitsName']); ?>');"<? } ?> style="border: 1px solid silver; padding: 5px; cursor: pointer; white-space: nowrap; display: inline-block; margin: 5px; border-radius: 5px; padding-right: 12px; <?= $option['servicesPrimecostVariable'] ? ' background-color: ivory;' : '' ?>">
														<span style=" padding: 5px; display: inline-block;">(<span class="variableContent" id="var<?= $option['idservicesPrimecost']; ?>"><?= floatval($option['servicesAppliedOptionsQty']); ?></span>) <?= $option['WH_nomenclatureName']; ?></span>
													</div>

													<?
												} else {
													?><input onclick="saveOptions(<?= $option['idservicesPrimecost']; ?>, <?= $serviceApplied['idservicesApplied']; ?>, this.checked);" <?= $option['servicesAppliedOptionsSA'] ? ' checked' : ''; ?> <?= $serviceApplied['servicesAppliedFineshed'] ? ' disabled' : '' ?> type="checkbox" id="c<?= $option['idservicesPrimecost']; ?>"><label style="border: 1px solid silver; padding: 5px; white-space: nowrap; display: inline-block; margin: 5px; border-radius: 5px; padding-right: 12px;" for="c<?= $option['idservicesPrimecost']; ?>"><?= $option['WH_nomenclatureName']; ?></label>
													<?
												}
												?>
												<?
											}
										}
										?>
									</div>
								</div>
								<?
							}
							?>
							<?
							if ($serviceApplied['servicesAppliedStarted'] && !$serviceApplied['servicesAppliedFineshed']) {
								include('phpinclude/medcardform.php');
							}
							?>
							<!--<div style="border: 1px solid red;">-->
							<? if ($serviceApplied['idservices']) { ?>
								<? if (!$serviceApplied['servicesAppliedFineshed']) { ?><div style="margin: 20px; text-align: <?= $serviceApplied['servicesAppliedStarted'] ? 'left' : 'right'; ?>;">
									<?
									if ($serviceApplied['servicesAppliedStarted']) {
//										printr($medrecords);
										if (($formReqired ?? false) && !$medrecords) {
											?>
												<button style=" height: 100px; background-color: pink;" type="button">Для того чтобы завершить приём заполните форму первичного приёма</button>
												<?
											} else {
												?>
												<button style=" height: 100px; background-color: lightgreen;" onclick="startFinish('finished', '<?= $serviceApplied['idservicesApplied']; ?>');" >Завершить<br>приём</button>
												<?
											}
										} else {
											?>
											<button style=" height: 100px; background-color: lightgreen;" onclick="startFinish('started', '<?= $serviceApplied['idservicesApplied']; ?>');" >Начать<br>приём</button>
											<?
										}
										?>
									</div><? } ?>
								<?
							}


							if (
									($serviceApplied['servicesAppliedStarted'] ?? false) &&
									($serviceApplied['servicesTestsReferral'] ?? false)
//											&& $_USER['id'] == 176 
							) {
								?>
								<div style=" text-align: center;">
									<a href="/sync/utils/word/testsreferrals.php?serviceapplied=<?= $serviceApplied['idservicesApplied']; ?>"><div style="display: inline-block; border: 0px solid gray; padding: 5px 10px; margin: 10px; border-radius: 5px; background-color: snow; box-shadow: 0px 0px 10px hsla(0,0%,0%,0.3);">
											Распечатать направление
										</div>
									</a>
								</div>
								<?
							}
							?>

							<!--</div>-->
							<script>

								function startFinish(state, idSA) {
									let elements = qs('#servicesPrimecostOptions').querySelectorAll('input');
									let elementsSelected = qs('#servicesPrimecostOptions').querySelectorAll('input:checked');
									let elementsChanged = qs('#servicesPrimecostOptions').querySelectorAll('.variableContent');
									let anyElementChanged = false;
									for (elementChanged of elementsChanged) {
										if (elementChanged.innerHTML !== '0') {
											anyElementChanged = true; }
									}
									console.log('elementsChanged', elementsChanged);
									if (state == 'finished' && (elements.length || elementsChanged.length) &&
											(!anyElementChanged && elementsSelected.length == 0)) {
										MSG(rt('Укажите расходку', 'Надо потыкать кнопки', 'А что было в процедуре?'));
										return false;
									}
									GETreloc(state, idSA);
								}


							</script>


							<?
							if (1 || $_USER['id'] == 176) {
//print 'Тут должен быть план лечения';
//printr($serviceApplied);
								?>


								<div style="border: 0px solid red;"><?
									if (
											($serviceApplied['servicesNewPlan'] ?? false) &&
											($serviceApplied['servicesAppliedStarted'] ?? false) &&
											((!$serviceApplied['servicesAppliedFineshed']) || date("Y-m-d") == date("Y-m-d", strtotime($serviceApplied['servicesAppliedFineshed']))) &&
											(1/* ПРАВО НА РАБОТУ С ПЛАНАМИ ЛЕЧЕНИЯ */)) {
										?>

										<div style=" text-align: center;">
											<div style="padding: 10px; border: 0px solid red; display: inline-block;  vertical-align:top;">
												<?
												$f_salesDraft = query2array(mysqlQuery("SELECT *,(SELECT COUNT(1) FROM `f_subscriptionsDraft` WHERE `f_subscriptionsDraftSaleDraft` = `idf_salesDraft`) as f_subscriptionsDraftCount "
																. " FROM `f_salesDraft` "
																. " LEFT JOIN `users` ON (`idusers`=`f_salesDraftAuthor`)"
																. " WHERE"
																. " `f_salesDraftClient` = " . sqlVON($_GET['client'] ?? null) . ""
																. " AND isnull(`f_salesDraftDeleted`)"
																. ""));
												if (!count($f_salesDraft)) {
													?>Нет планов лечения, <?
												} else {
													?>
													Планы лечения клиента
													<div class="lightGrid" style="margin: 10px;display: grid; grid-template-columns: repeat(6, auto);">
														<div style="display: contents;">
															<div class="B C">Дата</div>
															<div class="B C">Номер</div>
															<div class="B C">Автор</div>
															<div class="B C">Услуг</div>
															<div class="B C"><i class="far fa-times-circle"></i></div>
															<div class="B C"><i class="fas fa-print"></i></div>
														</div>
														<? foreach ($f_salesDraft as $f_saleDraft) { ?>

															<div style="display: contents;">
																<div class="C"><a href="<?= GR2(['saleDraft' => $f_saleDraft['idf_salesDraft'], 'saleDraftTemplate' => null]); ?>"><?= date("d.m.Y", mystrtotime($f_saleDraft['f_salesDraftDate'])); ?></a></div>
																<div class="C"><a href="<?= GR2(['saleDraft' => $f_saleDraft['idf_salesDraft'], 'saleDraftTemplate' => null]); ?>"><?= $f_saleDraft['f_salesDraftNumber']; ?></a></div>

																<div><a href="<?= GR2(['saleDraft' => $f_saleDraft['idf_salesDraft'], 'saleDraftTemplate' => null]); ?>">
																		<?= $f_saleDraft['usersLastName']; ?>
																		<?= mb_substr($f_saleDraft['usersFirstName'], 0, 1); ?>.
																		<?= mb_substr($f_saleDraft['usersMiddleName'], 0, 1); ?>.
																	</a>
																</div>
																<div class="C"><a href="<?= GR2(['saleDraft' => $f_saleDraft['idf_salesDraft'], 'saleDraftTemplate' => null]); ?>"><?= $f_saleDraft['f_subscriptionsDraftCount']; ?></a></div>
																<div class="B C">
																	<? if (mres($_GET['personal'] ?? $_USER['id']) === $f_saleDraft['f_salesDraftAuthor']) { ?>
																		<a href="<?= GR2(['deleteSaleDraft' => $f_saleDraft['idf_salesDraft']]); ?>"><span style="color: red;"><i class="far fa-times-circle"></i></span></a><? }
																	?>
																</div>
																<div><a href="/sync/utils/word/salesdraft.php?draft=<?= $f_saleDraft['idf_salesDraft']; ?>"><i class="fas fa-print"></i></a></div>
															</div>

														<? } ?>
													</div>

													<?
												}
												?>
												<br>
												<a href="<?= GR2(['addSaleDraft' => true]); ?>">добавить новый план лечения</a>
											</div>
											<? if (1 || $_USER['id'] == 176) { ?>
												<div style="padding: 10px; border: 0px solid blue; display: inline-block;  vertical-align:top;">
													<?
													$f_salesDraftTemplates = query2array(mysqlQuery("SELECT *,(SELECT COUNT(1) FROM `f_subscriptionsDraftTemplates` WHERE `f_subscriptionsDraftTemplatesSaleDraftTemplate` = `idf_salesDraftTemplates`) as f_subscriptionsDraftTemplatesCount "
																	. " FROM `f_salesDraftTemplates` "
																	. " WHERE"
																	. " `f_salesDraftTemplatesAuthor` = '" . mres($_GET['personal'] ?? $_USER['id']) . "'"
																	. " AND isnull(`f_salesDraftTemplatesDeleted`)"
																	. ""));
													if (!count($f_salesDraftTemplates)) {
														?>Нет шаблонов планов лечения, <?
													} else {
														?>
														Мои шаблоны планов лечения:
														<div class="lightGrid" style="margin: 10px;display: grid; grid-template-columns: repeat(4, auto);">
															<div style="display: contents;">
																<div class="B C">Дата</div>
																<div class="B C">Название шаблона</div>
																<div class="B C">Услуг</div>
																<div class="B C"><i class="far fa-times-circle"></i></div>
																<!--<div class="B C"><i class="fas fa-print"></i></div>-->
															</div>
															<? foreach ($f_salesDraftTemplates as $f_saleDraftTemplate) { ?>

																<div style="display: contents;">
																	<div class="C"><a href="<?= GR2(['saleDraft' => null, 'saleDraftTemplate' => $f_saleDraftTemplate['idf_salesDraftTemplates']]); ?>"><?= date("d.m.Y", mystrtotime($f_saleDraftTemplate['f_salesDraftTemplatesDate'])); ?></a></div>

																	<div><a href="<?= GR2(['saleDraft' => null, 'saleDraftTemplate' => $f_saleDraftTemplate['idf_salesDraftTemplates']]); ?>">
																			<?= $f_saleDraftTemplate['f_salesDraftTemplatesName']; ?>
																		</a>
																	</div>
																	<div class="C"><a href="<?= GR2(['saleDraft' => null, 'saleDraftTemplate' => $f_saleDraftTemplate['idf_salesDraftTemplates']]); ?>"><?= $f_saleDraftTemplate['f_subscriptionsDraftTemplatesCount']; ?></a></div>
																	<div class="B C">
																		<a href="<?= GR2(['deleteSaleDraftTemplate' => $f_saleDraftTemplate['idf_salesDraftTemplates']]); ?>"><span style="color: red;"><i class="far fa-times-circle"></i></span></a>
																	</div>
																	<!--																<div>
																																		<a href="/sync/utils/word/salesdraft.php?draftTemplate=<?= $f_saleDraftTemplate['idf_salesDraftTemplates']; ?>"><i class="fas fa-print"></i></a> 
																																	</div>-->
																</div>

															<? } ?>
														</div>

														<?
													}
													?>
												</div>
											<? } ?>
										</div>




										<?
										include ('phpinclude/drafts.php');
										?>

										<?
									}
									?>
								</div>
								<?
							}


							if (R(90) && in_array($serviceApplied['servicesAppliedService'], [87/* Анализы */, 361/* Консультация */])) {
								?>
								<div style="padding: 5px;"><a style="padding: 10px; font-weight: bold;" href="<?= GR2(['TPS' => ($_GET['TPS'] ?? false) == $serviceApplied['idservicesApplied'] ? 'null' : $serviceApplied['idservicesApplied']]); ?>">Назначить анализы</a>
									<? if (($_GET['TPS'] ?? '') == $serviceApplied['idservicesApplied']) { ?>
										<form action="?" method="GET">
											<? get2hidden(); ?>
											<div style="text-align: center;">
												<div style="display: inline-block; text-align: left;">
													<div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px; margin: 10px;">
														<div style="align-self: center;"><input onfocus="this.select();" type="text" placeholder="Поиск" name="search" id="search" value="<?= ($_GET['search'] ?? ''); ?>"></div>
														<div><input type="submit" value="ok"></div>
													</div>
												</div>
											</div>

										</form>


										<?
										if (($_GET['search'] ?? '')) {


											$serachResultsSQL1 = "SELECT *"
													. " FROM `TPS_Services`"
													. " LEFT JOIN (SELECT * FROM `TPS_prices` AS `A` INNER JOIN (SELECT MAX(`idTPS_prices`) AS `idTPS_pricesMAX` FROM `TPS_prices` GROUP BY `TPS_pricesService`) AS `B` ON (`A`.`idTPS_prices` = `B`.`idTPS_pricesMAX`)) AS `TPS_prices` ON (`TPS_pricesService` = `idTPS_Services`)"
													. " WHERE "
													. "`TPS_ServicesCode` = '" . mysqli_real_escape_string($link, trim($_GET['search'])) . "'";

											$serachResultsSQL2 = "SELECT *"
													. " FROM `TPS_Services`"
													. " LEFT JOIN (SELECT * FROM `TPS_prices` AS `A` INNER JOIN (SELECT MAX(`idTPS_prices`) AS `idTPS_pricesMAX` FROM `TPS_prices` GROUP BY `TPS_pricesService`) AS `B` ON (`A`.`idTPS_prices` = `B`.`idTPS_pricesMAX`)) AS `TPS_prices` ON (`TPS_pricesService` = `idTPS_Services`)"
													. " WHERE "
													. " (`TPS_ServicesName` LIKE '%" . mysqli_real_escape_string($link, trim($_GET['search'])) . "%'"
													. " OR `TPS_ServicesCode` LIKE '%" . mysqli_real_escape_string($link, trim($_GET['search'])) . "%')"
													. "AND `TPS_ServicesCode` <> '" . mysqli_real_escape_string($link, trim($_GET['search'])) . "'";

											$searchResults = query2array(mysqlQuery("$serachResultsSQL1 UNION ALL $serachResultsSQL2"
//															. "AND `TPS_ServicesCode` <> '" . mysqli_real_escape_string($link, trim($_GET['search'])) . "'"
											));

											if (count($searchResults)) {
												?><div class="lightGrid" style="display: grid; grid-template-columns: auto auto;">
												<?
												foreach ($searchResults as $searchResult) {
													?>
														<div style="display: contents;">
															<div style="padding: 5px; text-align: center;">
																<div><?= preg_replace("|" . preg_quote(trim($_GET['search'])) . "|iu", '<b style="color: red;">$0</b>', $searchResult['TPS_ServicesCode']); ?></div>
																<div style="color: gray;"><?= nf($searchResult['TPS_pricesValue']); ?>р.</div>
																<div style="text-align: center; margin-top: 10px;">
																	<input type="button" style="background-color: lightgreen;" value="+"
																		   onclick="GR({add:<?= $searchResult['idTPS_Services']; ?>, contract: <?= $serviceApplied['servicesAppliedContract'] ?? 'null'; ?>}
																												   );
																		   ">
																</div>

															</div>
															<div style="font-size: 0.8em;"><?=
																preg_replace("|" . preg_quote(trim($_GET['search'])) . "|iu", '<b style="color: red;">$0</b>', $searchResult['TPS_ServicesName']);
																?></div>
														</div>
														<?
													}
													?>
												</div><?
											} else {
												?>
												<h3 style="color: red; margin-top: 10px; text-align: center;">Поиск не дал результатов</h3>
												<?
											}
										}
										?>



									<? } ?>


									<div style="padding: 5px;">

										<?
										$TPS_reservedSQL = "SELECT * FROM `TPS_reserved`"
												. " LEFT JOIN `TPS_Services` ON (`idTPS_Services` = `TPS_reservedService`)"
												. " LEFT JOIN (SELECT * FROM `TPS_prices` AS `A` INNER JOIN (SELECT MAX(`idTPS_prices`) AS `idTPS_pricesMAX` FROM `TPS_prices` GROUP BY `TPS_pricesService`) AS `B` ON (`A`.`idTPS_prices` = `B`.`idTPS_pricesMAX`)) AS `TPS_prices` ON (`TPS_pricesService` = `idTPS_Services`)"
												. " WHERE " . ($serviceApplied['servicesAppliedContract'] ? ("`TPS_reservedContract`='" . $serviceApplied['servicesAppliedContract'] . "'") : " isnull(`TPS_reservedContract`)")
												. " AND `TPS_reservedClient` = '" . $serviceApplied['servicesAppliedClient'] . "'"
												. " ";
//											print($TPS_reservedSQL);
										$TPS_reserved = query2array(mysqlQuery($TPS_reservedSQL));
										if (count($TPS_reserved)) {
//												printr($TPS_reserved);
											?>
											Уже назначенные анализы на сумму <?= nf(array_sum(array_column($TPS_reserved, 'TPS_pricesValue'))) ?>р.

											<?
											if ($serviceApplied['servicesAppliedService'] == 87) {
												?> из <?= nf($serviceApplied['servicesAppliedPrice']); ?>р. доступных.<?
											}
											?>
											<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto;">

												<div style="display: contents;">
													<div>код/цена</div>
													<div>Наименование</div>
													<div>Сдан</div>
												</div>
												<? foreach ($TPS_reserved as $searchResult) { ?>
													<div style="display: contents;">
														<div style="padding: 5px; text-align: center;">
															<div style="text-align: center;"><?= $searchResult['TPS_ServicesCode']; ?></div>
															<div style="text-align: center; color: gray;"><?= nf($searchResult['TPS_pricesValue']); ?>р.</div>
															<div style="text-align: center; margin-top: 10px;">
																<? if (($_GET['TPS'] ?? '') == $serviceApplied['idservicesApplied']) { ?>
																	<input type="button" style="background-color: pink;" value="-"
																		   onclick="GR({remove:<?= $searchResult['idTPS_Services']; ?>, contract: <?= $serviceApplied['servicesAppliedContract'] ?? 'null'; ?>}
																											   );
																		   ">
																	   <? } ?>
															</div>


														</div>
														<div style="font-size: 0.8em;"><?=
															$searchResult['TPS_ServicesName'];
															?></div>
														<div style="display: flex; align-items:  center; justify-content: center; text-align: center; color: gray;">
															<?
															if ($serviceApplied['servicesAppliedService'] == 87) {
																if (!$searchResult['TPS_reservedDone']) {
																	?>
																	<a href="<?= GR2(['TPS_reservedDone' => $searchResult['idTPS_reserved']]); ?>"><i class="fas fa-check-square" style="color: green; font-size: 3em;"></i></a>
																		<?
																	} else {
																		?>
																		<?= date("d.m", strtotime($searchResult['TPS_reservedDone'])); ?><br>
																		<?= date("Y", strtotime($searchResult['TPS_reservedDone'])); ?>
																		<?
																	}
																} else {
																	if ($searchResult['TPS_reservedDone'] ?? false) {
																		?>
																		<?= date("d.m", strtotime($searchResult['TPS_reservedDone']));
																		?><br>
																	<?= date("Y", strtotime($searchResult['TPS_reservedDone'])); ?>
																	<?
																}
															}
															?>

														</div>

													</div>
												<? } ?>
											</div>
										<? } else { ?>
											Нет назначенных анализов.
											<?
											if (intval($serviceApplied['servicesAppliedPrice'] ?? 0)) {
												?>Доступно <?= $serviceApplied['servicesAppliedPrice']; ?>р.<?
											}
											?>
										<? } ?>
									</div>


								</div>
							<? } ?>
						</div>
					<? } ?>
				</div>
			<? } ?>
		</div>
	</div>



<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
