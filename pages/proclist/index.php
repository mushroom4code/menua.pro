<?php
$load['title'] = $pageTitle = 'Процедурный лист';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(45)) {
	
}
$maxDate = date("Y-m-d", time() + 60 * 60 * 24 * 3);
$minDate = date("Y-m-d");
$from = max(min($maxDate, ($_GET['from'] ?? date("Y-m-d"))), $minDate);
//printr($from);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(45)) {
	?>E403R45<? } else { ?>

	<ul class="horisontalMenu">
		<? if ($_USER['id'] == 176) { ?><li><a href="salesdrafts.php">Планы лечения</a></li><? } ?>
	</ul>

	<div class="box neutral">
		<div class="box-body" >
			<div style="text-align: center;">
				<span style="border-bottom: 1px solid black; padding: 0px 20px; display: inline-block;"><?= $_USER['lname']; ?> <?= mb_substr($_USER['fname'], 0, 1); ?>.<?= ($_USER['mname'] ?? false) ? (mb_substr($_USER['mname'], 0, 1) . '.') : '' ?></span> ДАТА <span style="border-bottom: 1px solid black; padding: 0px 20px; display: inline-block;"><input type="date"  max="<?= $maxDate; ?>"  min="<?= $minDate; ?>" value="<?= $from; ?>" onchange="GR({from: this.value});" autocomplete="off"></span>
			</div>
			<div style="padding: 5px; min-height: 20px;">
				<?
				$finger = query2array(mysqlQuery("SELECT * FROM `fingerLog` WHERE `fingerLogUser`='" . ($_GET['personal'] ?? $_USER['id'] ) . "' AND `fingerLogTime`>='" . ($from . " 00:00:00") . "' AND `fingerLogTime`<='" . ($from . " 23:59:59") . "'"));
//			printr($finger);

				if (!count($finger) && $from == date("Y-m-d")) {
					?>
					<div style="position: absolute; top: 0px; border-radius: 5px;  left: 0px; width: 100%; height: 100%; background-color: hsla(0,100%,90%,0.8); z-index: 10; text-align: center; font-weight: bolder; overflow: hidden;"><div style="padding-top: 15px; padding-bottom: 15px;  font-size: 40pt; line-height: 1em;">Смена<br>не<br>открыта</div>Отметьтесь у терминала фейс-айди<br>или у секретаря</div>
					<?
				}
				?>




				<?
				$servicesAppliedSQL = "SELECT * FROM "
						. " `servicesApplied` "
						. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
						. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
						. " LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA`=`idservicesApplied`) "
						. " WHERE "
						. " `servicesAppliedDate` = '" . $from . "'"
						. " AND `servicesAppliedPersonal` = '" . ($_GET['personal'] ?? $_USER['id'] ) . "'"
						. " AND isnull(`servicesAppliedDeleted`)";

//			print $servicesAppliedSQL;
				$servicesApplied = query2array(mysqlQuery($servicesAppliedSQL));
//			printr($servicesApplied);
				?>




				<?
				$n = 0;

				usort($servicesApplied, function ($a, $b) {
					return (strtotime($a['servicesAppliedTimeBegin'])) <=> (strtotime($b['servicesAppliedTimeBegin']));
				});

				if (1) {
//				printr($servicesApplied[0] ?? '');
					$clients = [];
					foreach ($servicesApplied as $serviceApplied) {
						$clients[$serviceApplied['servicesAppliedClient']][] = $serviceApplied;
					}
					foreach ($clients as &$client2) {
						usort($client2, function ($a, $b) {
							return (strtotime($a['servicesAppliedTimeBegin'])) <=> (strtotime($b['servicesAppliedTimeBegin']));
						});
					}

					usort($clients, function ($a, $b) {
						return (strtotime($a[0]['servicesAppliedTimeBegin'])) <=> (strtotime($b[0]['servicesAppliedTimeBegin']));
					});

					foreach ($clients as $client) {
						?>
						<?
						$clientVisit = mfa(mysqlQuery("SELECT * "
										. " FROM `clientsVisits`"
										. " WHERE `clientsVisitsClient`='" . $client[0]['idclients'] . "'"
										. " AND `clientsVisitsTime`>='" . $from . " 00:00:00'"
										. " AND `clientsVisitsTime`<='" . $from . " 23:59:59'"
										. ""));
						$servicesAppliedTimeBeginSQL = "SELECT UNIX_TIMESTAMP(MIN(`servicesAppliedTimeBegin`)) AS `servicesAppliedTimeBeginTS` FROM `servicesApplied` WHERE `servicesAppliedClient` = '" . $client[0]['idclients'] . "' AND `servicesAppliedDate`='" . $from . "' AND NOT isnull(`servicesAppliedTimeBegin`)";
						$servicesAppliedTimeBegin = mfa(mysqlQuery($servicesAppliedTimeBeginSQL));
						$color = 'blue';
						if (($clientVisit['clientsVisitsTime'] ?? null)) {
							$color = 'green';
						} else {

							if ($servicesAppliedTimeBegin['servicesAppliedTimeBeginTS'] > time()) {
								$color = 'gray';
							} else {
								$color = 'red';
							}
						}

						if ($color == 'green') {
//
//						$a = $a2 = '';
							$a = '<a style="color: black;" href="/pages/proclist/client.php?client=' . $client[0]['servicesAppliedClient'] . '' . (isset($_GET['personal']) ? ('&personal=' . $_GET['personal']) : '') . '">';
							$a2 = '</a>';
						} else {
							$a = '<span style="color: gray;">';
							$a2 = '</span>';
						}
						?>


						<?= $a; ?>
						<div style="border: 1px solid silver; margin: 20px 2px; padding: 3px; background-color: white; border-radius: 5px; box-shadow: 0px 0px 10px hsla(0,0%,0%,0.2);">
							<div style="font-weight: bold; text-align: center; padding: 10px 0px;">


								<?= clientIsNew($client[0]['idclients'], ($from)) ? '<i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i>' : ''; ?>

								<?= $client[0]['clientsLName'] ?? ''; ?> <?= $client[0]['clientsFName'] ?? ''; ?> <?= $client[0]['clientsMName'] ?? ''; ?>
								<span style="display: inline-block; width: 20px; text-align: right;"><i class="fas fa-walking" title="<?= ($clientVisit['clientsVisitsTime'] ?? null) ? date("H:i", strtotime($clientVisit['clientsVisitsTime'])) : 'Визит не зафиксирован'; ?>" style="color: <?= $color; ?>;"></i></span>
							</div>

							<?
							foreach ($client as $procedure) {

//							printr($procedure);
								$donemark = '';
								if ($color != 'gray') {
									if ($color == 'green' && time() > strtotime($procedure['servicesAppliedTimeBegin']) && !$procedure['servicesAppliedStarted']) {
										$donemark = '<i class="fas fa-exclamation-circle" style="color: red;"></i>';
									} elseif ($color == 'green' && $procedure['servicesAppliedStarted'] && !$procedure['servicesAppliedFineshed']) {
										if (time() > strtotime($procedure['servicesAppliedTimeEnd'])) {
											$donemark = '<i class="fas fa-arrow-circle-right" style="color: red;"></i>';
										} else {
											$donemark = '<i class="fas fa-arrow-circle-right" style="color: darkgreen;"></i>';
										}
									}

									if ($procedure['servicesAppliedFineshed']) {
										if ((strtotime($procedure['servicesAppliedFineshed']) - strtotime($procedure['servicesAppliedStarted'])) < 600) {
											$donemark = '<i class="fas fa-exclamation-triangle" style="color: orange;"></i>';
										} else {
											$donemark = '<i class="fas fa-clipboard-check" style="color: green;"></i>';
										}
									}
								}
								///
								?>
								<div>
									<? $a; ?>
									<span style="display: inline-block; text-align: center;">
										<?= $donemark; ?>
										<?
										if ($procedure['idservicesAppliedComments'] ?? false) {
											?> <i class="fas fa-info-circle" style="color:  hsl(220,100%,78%); display: inline;"></i><?
										}
										if (
												!$procedure['servicesAppliedContract'] &&
												!round($procedure['servicesAppliedPrice'] ?? 0 ) &&
												!$procedure['servicesAppliedIsDiagnostic']
										) {
											?> <i class="fas fa-gift" style="color:  hsl(15,100%,50%); display: inline;"></i><?
										}

										if ($procedure['servicesAppliedIsDiagnostic']) {
											?><i style="color:  hsl(15,100%,50%); display: inline;" class="fas fa-stethoscope" title="ДИАГНОСТИКА"></i><?
										}
										if ($procedure['servicesAppliedLocked'] ?? false) {
											?> <i class="fas fa-lock" style="color:  hsl(0,100%,78%); display: inline;"></i><? }
										?>
									</span>
									<?= date("H:i", strtotime(($procedure['servicesAppliedStarted'] ?? $procedure['servicesAppliedTimeBegin']))); ?>
									-
									<?= date("H:i", strtotime(($procedure['servicesAppliedFineshed'] ?? $procedure['servicesAppliedTimeEnd']))); ?>
									<?= $procedure['servicesName'] ?? 'Услуги не указаны'; ?><?
									if ($procedure['servicesAppliedQty'] > 1) {
										?> (<?= $procedure['servicesAppliedQty']; ?>шт.)<?
									}
									?><? $a2; ?></div>
							<? }
							?>
						</div>
						<?= $a2; ?>
						<?
					}
				}
				?>
			</div>
			<div>
				<h3>Описание значков:</h3>
				<div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px 8px;">
					<div style="text-align: center;"><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i></div><div> - ПЕРВИЧНЫЙ КЛИЕНТ, ВНИМАНИЕ!</div>
					<div style="text-align: center;"><i style="color:  hsl(15,100%,50%); display: inline;" class="fas fa-stethoscope" title="ДИАГНОСТИКА"></i></div><div> -  Диагностика!</div>





					<div style="text-align: center;"><i class="fas fa-info-circle" style="color:  hsl(220,100%,78%); display: inline;"></i></div><div> - к процедуре есть дополнительная информация по клиенту</div>
					<div style="text-align: center;"><i class="fas fa-lock" style="color:  hsl(0,100%,78%); display: inline;"></i></div><div> - процедура закреплена за специалистом</div>
					<div style="text-align: center;"><i class="fas fa-gift" style="color:  hsl(15,100%,50%); display: inline;"></i></div><div> - подарочная процедура</div>
					<div style="text-align: center;"><i class="fas fa-walking" style="color: gray;"></i></div><div> - Клиент ещё не пришел.</div>
					<div style="text-align: center;"><i class="fas fa-walking" style="color: green;"></i></div><div> - Клиент пришел в мед.центр.</div>
					<div style="text-align: center;"><i class="fas fa-walking" style="color: red;"></i></div><div> - Клиент опаздывает.</div>
					<div style="text-align: center;"><i class="fas fa-clipboard-check" style="color: green;"></i></div><div> - Процедура начата и завершена корректно.</div>
					<div style="text-align: center;"><i class="fas fa-exclamation-triangle" style="color: orange;"></i></div><div> - Процедура отмечена не своевременно.</div>
					<div style="text-align: center;"><i class="fas fa-exclamation-circle" style="color: red;"></i></div><div> - Клиент находится в медцентре, время процедуры наступило, но начало процедуры ещё не отмечено.</div>
					<div style="text-align: center;"><i class="fas fa-arrow-circle-right" style="color: darkgreen;"></i></div><div> - Процедура выполняется во время.</div>
					<div style="text-align: center;"><i class="fas fa-arrow-circle-right" style="color: red;"></i></div><div> - Процедура должна была уже закончится, но она не отмечена как завершенная.</div>
				</div>
			</div>

		</div>
	</div>



<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
