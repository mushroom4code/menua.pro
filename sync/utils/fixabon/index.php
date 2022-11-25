<?php
$load['title'] = $pageTitle = 'Раздача слонов';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (isset($_GET['del'])) {
	mysqlQuery("DELETE FROM `clients` WHERE `idclients` = '" . intval($_GET['del']) . "'");
	header("Location: " . GR('del'));
	die();
}
if (isset($_GET['control'])) {
	printr(mysqlQuery("UPDATE `clients` SET `clientsControl` = '" . $_USER['id'] . "' WHERE `idclients` = '" . intval($_GET['client']) . "'"));
	header("Location: " . GR('control'));
	die();
}
if (isset($_GET['offcontrol'])) {
	printr(mysqlQuery("UPDATE `clients` SET `clientsControl` = null WHERE `idclients` = '" . intval($_GET['client']) . "'"));
	header("Location: " . GR('offcontrol'));
	die();
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
</div>
</div>
<div class="box negative" style="display: block;">
	<div class="box-body" style="display: block;">
		<div class="box neutral" style="display: block;">
			<div class="box-body" style="display: block;">

				<?
				if ($_GET['client'] ?? false) {

					$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . intval($_GET['client']) . "'"));
//			printr($client);
					?>
					<a target="_blank" href="/pages/checkout/payments.php?client=<?= $client['idclients']; ?>">
						<?= $client['idclients']; ?>]
						<?= $client['clientsLName']; ?>
						<?= $client['clientsFName']; ?>
						<?= $client['clientsMName']; ?>
						(<?= $client['clientsBDay']; ?>)
						№<?= $client['clientsAKNum']; ?>
					</a>
					<div class="C" style="font-size: 3em;"><i class="fas fa-gift" style=" padding: 20px; margin: 10px; border: 1px solid silver; border-radius: 20px; background-color: white;"  ondrop="drop(event, {action: 'makeitfree'});" ondragover="event.preventDefault();"></i></div>
					<?
					//////////////////////////////////////////////////////////////////////////////
					$contracts = query2array(mysqlQuery("SELECT "
									. "*,`idf_sales`,"
									. "`f_salesSumm`,"
									. "`f_salesDate`,"
									. "`f_salesCancellationDate`,"
									. "`f_salesNumber`"
									. " FROM `f_sales`"
									. " LEFT JOIN `users` ON (`idusers` = `f_salesCreditManager`)"
									. " WHERE `f_salesClient` = '" . FSI($_GET['client']) . "'"));

					$servicesAppliedSQL = ""
							. " SELECT * FROM `servicesApplied`"
							. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
							. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedBy`)"
							. " WHERE `servicesAppliedClient` = '" . FSI($_GET['client']) . "'"
							. " AND isnull(`servicesAppliedDeleted`)"
							. " AND NOT isnull(`servicesAppliedFineshed`)"
							. " AND (isnull(`servicesAppliedContract`) OR "
							. "("
							. "NOT isnull(`servicesAppliedContract`) AND isnull(`servicesAppliedPrice`)"
							. ")"
							. ")"
							. " AND NOT `idservices` IN (361,362)";

					$servicesApplied = query2array(mysqlQuery($servicesAppliedSQL));

					usort($contracts, function ($a, $b) {
						return $a['f_salesDate'] <=> $a['f_salesDate'];
					});
					?>
					<div style="display: grid; grid-template-columns:  1fr 1fr;">
						<div style="border: 1px solid red; vertical-align: top; max-height: 80vh; overflow-y: auto; text-align: right;">
	<?
//				printr($servicesApplied);
	usort($servicesApplied, function ($a, $b) {
		if ($a['servicesName'] <=> $b['servicesName']) {
			return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
		}
		return $a['servicesAppliedDate'] <=> $b['servicesAppliedDate'];
	});
	foreach ($servicesApplied as $serviceApplied) {
		?>
								<div  <? if (1) { ?>draggable="true"
														ondragstart="startdrag(event, {
														idservicesApplied: <?= $serviceApplied['idservicesApplied'] ?? 0; ?>,
														servicesAppliedQty: <?= $serviceApplied['servicesAppliedQty'] ?? 0; ?>,
														servicesAppliedService:<?= $serviceApplied['servicesAppliedService']; ?>
														});"<? } ?> style="border: 1px solid silver; background-color: white; padding: 3px; margin: 3px;display: inline-block;"><?= $serviceApplied['idservicesApplied']; ?>] (<?= $serviceApplied['servicesAppliedQty']; ?>шт.) <?= $serviceApplied['servicesName']; ?> [<?= $serviceApplied['servicesAppliedService']; ?>] (<?= $serviceApplied['servicesAppliedPrice'] ?? '--'; ?>р.) <?= date("d.m.Y", strtotime($serviceApplied['servicesAppliedDate'])); ?> / <?= $serviceApplied['usersLastName']; ?><? ?></div>
		<?
	}
	?>
						</div>
						<div style="border: 1px solid blue;  vertical-align: top; max-height: 80vh; overflow-y: auto;"><?
//							printr($contracts[0]);
							uasort($contracts, function ($a, $b) {
								return $a['f_salesDate'] <=> $b['f_salesDate'];
							});

							foreach ($contracts as $contract) {
								?><div style=" white-space: auto; border: 1px solid silver; background-color: white; margin: 5px; padding: 5px; display: inline-block;">
									<div><a style="color: gray;" target="_blank" href="/pages/checkout/payments.php?client=<?= $client['idclients']; ?>&contract=<?= $contract['idf_sales']; ?>"><?= $contract['idf_sales']; ?>] от <?= $contract['f_salesDate']; ?>  <?= $contract['usersLastName']; ?> </a><?= $contract['f_salesCancellationDate'] ? ' <b>РАСТОРГНУТ</b>' : ''; ?></div>
								<?
								$subscriptions = query2array(mysqlQuery(""
												. "SELECT *,"
												. "(SELECT SUM(`servicesAppliedQty`)"
												. " FROM `servicesApplied` WHERE "
												. " `servicesAppliedContract` = `f_subscriptionsContract`"
												. " AND `servicesAppliedService` = `f_salesContentService`"
												. " AND `servicesAppliedPrice` = `f_salesContentPrice`"
												. " AND isnull(`servicesAppliedDeleted`)  AND NOT isnull(`servicesAppliedFineshed`)) as `done` "
												. "FROM `f_subscriptions`"
												. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
//											. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
												. " "
												. ""
												. " WHERE `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'"));
//					printr($subscriptions);
								?>
									<div style="margin: 3px; display: inline-block; vertical-align: middle;">
										<!--							<div>Наименование</div>
																	<div class="C B">Продано</div>
																	<div class="C B">Пройдено</div>-->
		<?
		usort($subscriptions, function ($a, $b) {
			return $a['servicesName'] <=> $b['servicesName'];
		});
		foreach ($subscriptions as $subscription) {
			?>
											<div style="padding: 2px; border:1px solid orange; vertical-align: middle; display: inline; <?= $subscription['f_salesContentQty'] <= 0 ? 'background-color: red;' : ''; ?>" data-service="<?= $subscription['f_salesContentService'] ?? 0; ?>"
												 ondrop="drop(event, {
																		 idf_subscriptions: <?= $subscription['idf_subscriptions']; ?>,
																		 f_subscriptionsContract: <?= $subscription['f_subscriptionsContract']; ?>,
																		 f_salesContentPrice: <?= $subscription['f_salesContentPrice']; ?>,
																		 f_salesContentService: <?= $subscription['f_salesContentService'] ?? 0; ?>,
																		 f_salesContentQty: <?= $subscription['f_salesContentQty'] ?? 0; ?>,
																		 done: <?= $subscription['done'] ?? 0; ?>

																	 });" ondragover="drgover(event,{
												 f_salesContentService:<?= $subscription['f_salesContentService'] ?? 0; ?>,
												 done:<?= $subscription['done'] ?? 0; ?>,
												 f_salesContentQty: <?= $subscription['f_salesContentQty'] ?? 0; ?>
												 }); event.preventDefault();" ondragleave=" this.classList.remove('highlighted');this.classList.remove('highlightedRed');">
			<? $subscription['servicesName']; ?> [<?= $subscription['idservices']; ?>] <?= $subscription['done'] ?? 0; ?>/<?= $subscription['f_salesContentQty'] ?? 0; ?> (<?= $subscription['f_salesContentPrice'] ?? '--'; ?>р.)

											</div>

												<?
											}
											?>
										<i class="fas fa-plus-square" style="color: gray; display: inline-block; vertical-align: middle; margin-left: 5px;"></i>
									</div>

								</div><?
										}
										?>
						</div>
					</div>
					<br>
							<?
							if ($client['clientsControl']) {
								?>
						<a href="<?= GR('offcontrol', 1); ?>">Снять с контроля</a>
						<?
					} else {
						?>
						<a href="<?= GR('control', 1); ?>">На контроль</a>
						<?
					}

					//////////////////////////////////////////////////////////////////////////////
					?>
					<!--			<div style="border: 1px solid red; margin: 10px; display: inline-block; vertical-align: top;" ondrop="drop(event, {target: 3222});" ondragover="event.preventDefault();">TARGET</div>
								<div style="padding: 2px 10px; border: 1px solid silver; background-color:pink; cursor: grab;">SOURCE</div>-->

					<?
				} else {
					print count(query2array(mysqlQuery("SELECT *,"
													. "(SELECT count(1) FROM `servicesApplied` WHERE"
													. " isnull(`servicesAppliedContract`)"
													. "  AND  isnull(`servicesAppliedDeleted`)"
													. " AND isnull(`servicesAppliedIsFree`)"
													. " AND NOT isnull(`servicesAppliedFineshed`)"
													. " AND `servicesAppliedClient` = `idclients`)"
													. " as `undefined`,"
													. "(SELECT count(1) FROM `f_sales` WHERE `f_salesClient` = `idclients`) as `contracts`"
													. " FROM `clients`"
													. "WHERE "
													. "(SELECT count(1) FROM `servicesApplied` WHERE"
													. " isnull(`servicesAppliedContract`)"
													. " AND isnull(`servicesAppliedDeleted`)"
													. " AND isnull(`servicesAppliedIsFree`)"
													. " AND NOT isnull(`servicesAppliedFineshed`)"
													. " AND NOT `servicesAppliedService` IN (361,362)"
													. " AND `servicesAppliedClient` = `idclients`) > 0"
													. " AND "
													. " (SELECT count(1) FROM `f_sales` WHERE `f_salesClient` = `idclients`) > 0 "
													. " "))) . ', на контроле ' . count(query2array(mysqlQuery("SELECT * FROM `clients` WHERE NOT isnull(`clientsControl`)"
					)));
					$clients = query2array(mysqlQuery("SELECT *,"
									. "(SELECT count(1) FROM `servicesApplied` WHERE"
									. " isnull(`servicesAppliedContract`)"
									. " AND isnull(`servicesAppliedDeleted`)"
									. " AND isnull(`servicesAppliedIsFree`)"
									. " AND NOT isnull(`servicesAppliedFineshed`)"
									. " AND `servicesAppliedClient` = `idclients`)"
									. " as `undefined`,"
									. "(SELECT count(1) FROM `f_sales` WHERE `f_salesClient` = `idclients`) as `contracts`"
									. " FROM `clients`"
									. "WHERE "
									. "(SELECT count(1) FROM `servicesApplied` WHERE"
									. " isnull(`servicesAppliedContract`)"
									. " AND isnull(`servicesAppliedDeleted`)"
									. " AND isnull(`servicesAppliedIsFree`)"
									. " AND NOT isnull(`servicesAppliedFineshed`)"
									. " AND NOT `servicesAppliedService` IN (361,362)"
									. " AND `servicesAppliedClient` = `idclients`) > 0"
									. " AND "
									. " (SELECT count(1) FROM `f_sales` WHERE `f_salesClient` = `idclients`) > 0 "
									. " ORDER BY `clientsLName`"));
					?><?
					usort($clients, function ($a, $b) {
						return mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName']);
					});
					foreach ($clients as $client) {
						?>
						<div><? if ($client['clientsControl']) { ?>‼️<? } ?> <a target="_blank" href="?client=<?= $client['idclients']; ?>"><?= $client['clientsLName']; ?> <?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?></a></div>
						<?
					}
					?>
					<?
				}
				?>




			</div>
		</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
