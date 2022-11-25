<?php
$pageTitle = 'Регистратура';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!empty($_POST)) {
	foreach ($_POST as &$pdata) {
		$pdata = trim($pdata);
	}
}
if (R(42)) {
	if (isset($_GET['client']) && isset($_GET['add'])) {
		if (mysqlQuery("INSERT INTO `servicesApplied` SET "
						. "`servicesAppliedService`='" . FSI($_GET['add']) . "',"
						. "`servicesAppliedClient` = '" . FSI($_GET['client']) . "',"
						. "`servicesAppliedBy` = '" . $_USER['id'] . "',"
						. "`servicesAppliedByReal` = '" . $_USER['id'] . "',"
						. "`servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'")) {
			header("Location: /pages/reception/?client=" . FSI($_GET['client']) . '&date=' . ($_GET['date'] ?? date("Y-m-d")));
			die();
		} else {
			die(mysqli_error($link));
		}
	}

	/*
	  [editidservicesApplied] => 7
	  [servicesAppliedPersonal] =>
	  [servicesAppliedTimeBegin] =>
	  [servicesAppliedTimeEnd] =>
	  [servicesAppliedIsFree] => 0
	 */
//	printr($_POST);
//	printr($_GET);

	if (isset($_POST['editidservicesApplied'])) {
//idservicesApplied, servicesAppliedService, servicesAppliedClient, servicesAppliedBy, , servicesAppliedDate, servicesAppliedTimeBegin, servicesAppliedTimeEnd, servicesAppliedIsFree, servicesAppliedDeleted, servicesAppliedAt, servicesAppliedStarted, servicesAppliedFineshed

		$servicesAppliedTimeBegin = empty($_POST['servicesAppliedTimeBegin']) ? 'null' : "'" . date("Y-m-d H:i:s", strtotime($_GET['date'] . ' ' . FSS($_POST['servicesAppliedTimeBegin']) . ':00')) . "'";
		$servicesAppliedTimeEnd = empty($_POST['servicesAppliedTimeEnd']) ? 'null' : "'" . date("Y-m-d H:i:s", strtotime($_GET['date'] . ' ' . FSS($_POST['servicesAppliedTimeEnd']) . ':00')) . "'";

		$servicesAppliedSQL = ("UPDATE `servicesApplied` SET "
				. "`servicesAppliedPersonal` = " . (empty($_POST['servicesAppliedPersonal']) ? 'null' : FSI($_POST['servicesAppliedPersonal'])) . ","
				. "`servicesAppliedTimeBegin` = " . $servicesAppliedTimeBegin . ","
				. "`servicesAppliedTimeEnd` = " . $servicesAppliedTimeEnd . ""
//				. "`servicesAppliedIsFree` = " . ($_POST['servicesAppliedIsFree'] == 1 ? 1 : 'null' ) . ""
				. " WHERE `idservicesApplied` = '" . FSI($_POST['editidservicesApplied']) . "'");

		if (mysqlQuery($servicesAppliedSQL)) {
			header("Location: ", GR('edit', null));
		} else {
			print mysqli_error($link);
		}

		die();
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(42)) {
	?>E403R42<?
} else {

	include 'menu.php';
	?>
	<!--<script src="/sync/js/idle.js" type="text/javascript"></script>--> 


	<?
	if (isset($_GET['personal'])) {
		$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . FSI($_GET['personal']) . "'"));
		?>

		<div class="box neutral">
			<div class="box-body">
				<h2><input type="date" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>" onchange="GETreloc('date', this.value);"></h2>
			</div>
			<div style="padding: 10px;">
				<div style="display: inline-block; font-size: 1.5em; padding: 10px; margin-bottom: 20px;">
					<?= $user['usersLastName'] ?? 'Без специалиста'; ?>
					<?= $user['usersFirstName']; ?>
					<?= $user['usersMiddleName']; ?>
				</div>


				<div style="padding: 20px;">

					<?
					$subscriptions = query2array(mysqlQuery("SELECT * FROM"
									. " `servicesApplied` "
									. "LEFT JOIN `services` ON (`idservices`=`servicesAppliedService`)"
									. "LEFT JOIN `clients` ON (`idclients`=`servicesAppliedClient`)"
									. "WHERE "
									. ($user['idusers'] ? ("`servicesAppliedPersonal` = '" . $user['idusers'] . "'") : " isnull(`servicesAppliedPersonal`) ") . ""
									. " AND `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"));
					usort($subscriptions, function ($a, $b) {
						return $a['servicesAppliedTimeBegin'] <=> $b['servicesAppliedTimeBegin'];
					});
					?>


					<?
//					printr($_POST);
					if (isset($_GET['edit'])) {
						?>
						<form action="<?= GR('edit', null); ?>" method="post">
							<input type="hidden" name="editidservicesApplied" value="<?= $_GET['edit']; ?>">
						<? } ?>

						<div style="display: grid; grid-template-columns: auto auto auto auto auto; grid-gap: 5px;">
							<div style="display: contents;">
								<div style="padding: 12px 10px; font-weight: bold;">Клиент</div>
								<div style="padding: 12px 10px; font-weight: bold;">Процедура</div>
								<div style="padding: 12px 10px; font-weight: bold;">Начало</div>
								<div style="padding: 12px 10px; font-weight: bold;">Окончание</div>
								<div style="padding: 12px 10px; font-weight: bold; text-align: center;"><i class="fas fa-gift"></i></div>
							</div>
							<div id="subscriptions" style="display: contents;">
								<?
								foreach ($subscriptions as $subscription) {
//									printr($subscription);
									?>

									<div style="display: contents;">
										<div><a href="/pages/reception/?client=<?= $subscription['idclients']; ?>&date=<?= $subscription['servicesAppliedDate']; ?>">
												<?= mb_ucfirst($subscription['clientsLName']); ?>
												<?= mb_ucfirst($subscription['clientsFName']); ?>
												<?= mb_ucfirst($subscription['clientsMName']); ?>
											</a>

										</div>
										<div style=""><?= $subscription['servicesName']; ?></div>

										<div style="text-align: center;">
											<? if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) { ?>
												<input name="servicesAppliedTimeBegin" id="servicesAppliedTimeBegin" type="time"<?= $subscription['servicesAppliedTimeBegin'] ? (' value="' . date("H:i", strtotime($subscription['servicesAppliedTimeBegin'])) . '"') : '' ?>>
											<? } else { ?>
												<?= $subscription['servicesAppliedTimeBegin'] ? date("H:i", strtotime($subscription['servicesAppliedTimeBegin'])) : '--:--'; ?>
											<? } ?>
										</div>
										<div style="text-align: center;">
											<? if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) { ?><input name="servicesAppliedTimeEnd" id="servicesAppliedTimeEnd" type="time"<?= $subscription['servicesAppliedTimeEnd'] ? (' value="' . date("H:i", strtotime($subscription['servicesAppliedTimeEnd'])) . '"') : '' ?>>
											<? } else { ?>
												<?= $subscription['servicesAppliedTimeEnd'] ? date("H:i", strtotime($subscription['servicesAppliedTimeEnd'])) : '--:--'; ?>
											<? } ?>
										</div>
										<div style="text-align: center;">
											<? if ((!round($subscription['servicesAppliedPrice']) && !$subscription['servicesAppliedContract'])) {
												?>
												<i class="fas fa-gift"></i>1
											<? }
											?>
										</div>
									</div>

									<?
									if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) {
										$strHeight = 25;
										?>
										<div style="grid-column: 1/-1;"><?
//											printr();

											$thisServiceAvailibility = query2array(mysqlQuery("SELECT *,"
															. " (UNIX_TIMESTAMP(`servicesAppliedTimeEnd`) - UNIX_TIMESTAMP(`servicesAppliedTimeBegin`)) AS `servicesAppliedDuration`"
															. " FROM `servicesApplied`"
															. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
															. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`) "
															. "WHERE `servicesAppliedService` = '" . $subscription['servicesAppliedService'] . "'"
															. "AND `servicesAppliedDate` = '" . $subscription['servicesAppliedDate'] . "'"));

											usort($thisServiceAvailibility, function ($a, $b) {
												return $a['servicesAppliedTimeBegin'] <=> $b['servicesAppliedTimeBegin'];
											});
											if (count($subscription['allpersonal'])) {
												$allpersonalSQL = implode(',', array_column($subscription['allpersonal'], 'idusers'));
												$allpersonalSQLavailibility = "SELECT *,"
														. " (UNIX_TIMESTAMP(`servicesAppliedTimeEnd`) - UNIX_TIMESTAMP(`servicesAppliedTimeBegin`)) AS `servicesAppliedDuration`"
														. " FROM `servicesApplied`"
														. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
														. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`) "
														. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) "
														. " WHERE `servicesAppliedPersonal` IN (" . $allpersonalSQL . ")"
														. " AND `servicesAppliedDate` = '" . $subscription['servicesAppliedDate'] . "'"
														. "";
//												printr($allpersonalSQLavailibility);
												$allpersonalAvailibility = query2array(mysqlQuery($allpersonalSQLavailibility));
//												printr($allpersonalAvailibility);
											}
											?>

											<script>

												function drgSrt(e, data) {
													e.dataTransfer.setData("text/plain", JSON.stringify(data));
													qs(`#SA${data.idservicesApplied}`).style.display = 'none';
													console.log(data);
												}

												function makeDroppable(e, data) {
													e.preventDefault();
													let rdata = e.dataTransfer.getData("text/plain");
													rdata = JSON.parse(rdata);

													let startDate = new Date(data.timeStart);

													let endDate = new Date(data.timeStart);
													endDate.setSeconds(endDate.getSeconds() + rdata.duration);
													//													console.log('startDate', startDate);
													qs('#servicesAppliedPersonal').value = data.personal;
													qs('#servicesAppliedTimeBegin').value = `${startDate.getHours()}:${_0(startDate.getMinutes())}`;
													qs('#servicesAppliedTimeEnd').value = `${endDate.getHours()}:${_0(endDate.getMinutes())}`;

													//													console.log('endDate', endDate);
													//
													//													console.log(data, rdata);
												}

												function dropImg(e, data) {
													e.preventDefault();
													var rdata = e.dataTransfer.getData("text/plain");
													rdata = JSON.parse(rdata);
													console.log('data', data);
													console.log('rdata', rdata);
													qs(`#SA${rdata.idservicesApplied}`).style.display = 'block';
													qs(`#SA${rdata.idservicesApplied}`).style.top = '0px';
													e.target.appendChild(qs(`#SA${rdata.idservicesApplied}`));
													e.target.style.backgroundColor = '';
													//if (confirm('Перенести?')) {}
												}
											</script>
											<div style="display: inline-block; background-color: white;">
												<div style="display: grid; grid-template-columns: auto auto <?
												for ($n = 0; $n < count($subscription['allpersonal']); $n++) {
													print 'auto ';
												}
												?>; border-top: 1px solid silver; border-left: 1px solid silver;">

													<div style="border-bottom: 2px solid gray; border-right: 1px solid silver;"></div>
													<div style="padding: 0px 10px;  border-bottom: 2px solid gray; border-right: 1px solid silver;"><?= $subscription['servicesName']; ?></div>

													<?
													for ($n = 0; $n < count($subscription['allpersonal']); $n++) {
														?><div style="padding: 0px 10px; border-right: 1px solid silver;  border-bottom: 2px solid gray; font-size: 0.8em; cursor: pointer;" onclick="qs('#servicesAppliedPersonal').value =<?= $subscription['allpersonal'][$n]['idusers'] ?>"><?= $subscription['allpersonal'][$n]['usersLastName']; ?> <?= mb_substr($subscription['allpersonal'][$n]['usersFirstName'], 0, 1); ?>.</div><?
													}
													?>

													<?
													for ($time = strtotime($subscription['servicesAppliedDate'] . " 10:00:00"); $time <= strtotime($subscription['servicesAppliedDate'] . " 20:00:00"); $time += 30 * 60) {
														?>

														<div style="border-bottom: 1px solid silver; border-right: 1px solid silver; padding: 0px 10px; height: 25px; align-items: center; display: flex;"><?= date("H:i", $time); ?></div>
														<div style="border-bottom: 1px solid silver; border-right: 1px solid silver;">


															<?
															$curtime = $subscription['servicesAppliedDate'] . ' ' . date("H:i:s", $time);
															$currentServices = array_filter($thisServiceAvailibility, function ($element) use ($curtime) {

																return (
																strtotime($element['servicesAppliedTimeBegin']) >= strtotime($curtime) &&
																strtotime($element['servicesAppliedTimeBegin']) < strtotime($curtime) + 1800);
															});
															foreach ($currentServices as $currentService) {
																?> <div style="
																	 justify-content: center;
																	 align-items: center;
																	 border: 1px solid black;
																	 position: absolute;
																	 top: <?= ($strHeight * (strtotime($currentService['servicesAppliedTimeBegin']) - strtotime($curtime)) / 1800) ?>px;
																	 left: 0px;
																	 width: 100%;
																	 height: <?= ($currentService['servicesAppliedDuration'] / 1800) * 25; ?>px;
																	 z-index: 1;
																	 background-color: hsla(220,50%,90%,0.7);
																	 font-size: 0.7em;
																	 line-height: 1em;
																	 display: flex;">
																	 <? if ($currentService['idusers']) { ?>
																		<?= $currentService['usersLastName']; ?> <?= mb_substr($currentService['usersFirstName'], 0, 1); ?>.
																	<? } else { ?>Без спец-та.<? }
																	?><br>
																	<?= $currentService['clientsLName']; ?> <?= mb_substr($currentService['clientsFName'], 0, 1); ?>. <?= $currentService['clientsMName'] ? (mb_substr($currentService['clientsMName'], 0, 1) . '.') : '' ?>
																</div><?
															}
															?>




														</div>
														<?
//														printr($subscription);
														for ($n = 0; $n < count($subscription['allpersonal']); $n++) {
															?><div
																ondragleave="this.style.backgroundColor='';"
																ondragover="makeDroppable(event,{timeStart:'<?= $curtime; ?>',personal:<?= $subscription['allpersonal'][$n]['idusers']; ?>},this.style.backgroundColor='pink');"
																ondrop="dropImg(event, {personal:<?= $subscription['allpersonal'][$n]['idusers']; ?>})"
																style ="border-bottom: 1px solid silver; border-right: 1px solid silver;"><? ?>

																<?
																$currentServices = array_filter($allpersonalAvailibility, function ($element) {
																	global $curtime, $subscription, $n;
																	return (
																	(
																	strtotime($element['servicesAppliedTimeBegin']) >= strtotime($curtime) &&
																	strtotime($element['servicesAppliedTimeBegin']) < strtotime($curtime) + 1800
																	) &&
																	$subscription['allpersonal'][$n]['idusers'] == $element['servicesAppliedPersonal']
																	);
																});
																$m = 0;
																foreach ($currentServices as $currentService) {
																	?> <div
																		id="SA<?= $currentService['idservicesApplied']; ?>"
																		<? if ($currentService['idservicesApplied'] == ($_GET['edit'])) { ?>

																			draggable="true"
																			ondragstart="drgSrt(event,{idservicesApplied:<?= $currentService['idservicesApplied']; ?>,duration:<?= $currentService['servicesAppliedDuration']; ?>});"
																		<? } ?>

																		style="
																		cursor: pointer;
																		border: 1px solid black;
																		position: absolute;
																		top: <?= ($strHeight * (strtotime($currentService['servicesAppliedTimeBegin']) - strtotime($curtime)) / 1800) ?>px;
																		left:<?= (100 / count($currentServices)) * $m; ?>%;
																		width: <?= 100 / count($currentServices); ?>%;
																		height: <?= ($currentService['servicesAppliedDuration'] / 1800) * 25; ?>px;
																		z-index: 10; background-color: hsla(<?= $currentService['idservicesApplied'] == ($_GET['edit'] ?? 0) ? '120' : '220'; ?>,50%,90%,0.7);
																		font-size: 0.7em;
																		line-height: 1em;
																		overflow: hidden;">
																		<? if ($currentService['idservicesApplied'] != ($_GET['edit'])) { ?><a href="/pages/reception/?client=<?= $currentService['idclients']; ?>&date=2020-05-22&edit=<?= $currentService['idservicesApplied']; ?>"><? } ?>
																			<?= $currentService['servicesName']; ?>

																			<?= $currentService['clientsLName']; ?> <?= mb_substr($currentService['clientsFName'], 0, 1); ?>. <?= $currentService['clientsMName'] ? (mb_substr($currentService['clientsMName'], 0, 1) . '.') : '' ?>
																			<? if ($currentService['idservicesApplied'] != ($_GET['edit'])) { ?></a><? } ?>
																	</div><?
																	$m++;
																}
																?>


																<? ?></div><?
														}
														?>
														<?
													}
													?>
												</div>

											</div>
										</div>
										<?
									}
									?>
								<? } ?>
							</div>
						</div>

						<?
						if (isset($_GET['edit'])) {
							?>
							<div style="text-align: right; margin-top: 20px;">
								<input type="submit" value="Сохранить"><input type="submit" onclick="GETreloc('edit', null);
													void(0);
													return false;" value="Отмена">
							</div>
						</form>
						<?
					}
					?>


				</div>



				<?
			} else {
				?>
				<div class="box neutral">
					<div class="box-body">
						<h2><input type="date" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>" onchange="GETreloc('date', this.value);"></h2>

						<div style="display: grid; grid-template-columns: auto auto ; grid-gap: 5px;">
							<div>#</div>
							<div>Ф.И.О.</div>

							<?
							$clientsSQL = "SELECT `servicesAppliedPersonal`,`usersLastName`,`usersFirstName`,`usersMiddleName`"
									. "FROM `servicesApplied`"
									. "LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
									. "WHERE `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"
									. "GROUP BY `servicesAppliedPersonal`";
//					print $clientsSQL . '<hr>';
							$clients = query2array(mysqlQuery($clientsSQL));
							$n = 0;
							foreach ($clients as $client) {
								$n++;
								?>
								<div><?= $n; ?></div>
								<div><a href="/pages/reception/personal.php?personal=<?= $client['servicesAppliedPersonal']; ?>&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>"><?= $client['usersLastName'] ?? 'Без специалиста'; ?> <?= $client['usersFirstName']; ?> <?= $client['usersMiddleName']; ?></a></div>

								<?
							}
						}
						?>
					</div>

				</div>
			</div>
			<?
		}
		?>

		<?
		include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
		