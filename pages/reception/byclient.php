<?php

function isClientHaveDebt($idclient) {
	global $_USER;
	if (1 || $_USER['id'] == 176) {
		$contracts = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesSumm` FROM `f_sales` WHERE `f_salesClient`='" . $idclient . "' AND isnull(`f_salesCancellationDate`)"));
		if (count($contracts)) {
//			printr($contracts);
			$debt = 0;
			foreach ($contracts as $contract) {
//				$credit = mfa(mysqlQuery("SELECT * FROM `f_credits` WHERE "));//$contract

				$installment = mfa(mysqlQuery("SELECT * FROM `f_installments` WHERE `f_installmentsSalesID` = '" . $contract['idf_sales'] . "'"));
				if ($installment) {
					$payments = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` = '" . $contract['idf_sales'] . "';"));
					$debt += $contract['f_salesSumm'] - array_sum(array_column($payments, 'f_paymentsAmount'));
				}
			}
			if ($debt > 0) {
				return $debt;
			}
		}
	}
	return false;
}

$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
$clientsSQL = "SELECT `clientsSourcesLabel`,`clientsBDay`,`clientsOldSince`,`idclients`,`servicesAppliedClient`,`servicesAppliedDate`,`clientsLName`,`clientsFName`,`clientsMName`,`clientsAKNum`,  COUNT(1) as `AScount`,"
		. "(SELECT COUNT(1) FROM `OCC_calls` LEFT JOIN `OCC_callsConfirm` ON (`OCC_callsConfirmCall` = `idOCC_calls`) WHERE `OCC_callsClient` = `idclients` AND `OCC_callsType` = '8' AND `OCC_callsConfirmDate` = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "') AS `confirmed` "
		. " FROM `servicesApplied`"
		. " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
		. " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`)"
		. " WHERE `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"
		. " AND isnull(`servicesAppliedDeleted`)"
		. " GROUP BY `servicesAppliedClient`";
$clientsVisits = query2array(mysqlQuery("SELECT * FROM `clientsVisits` WHERE `clientsVisitsTime`>='" . ($_GET['date'] ?? date("Y-m-d")) . " 00:00:00' AND  `clientsVisitsTime`<='" . ($_GET['date'] ?? date("Y-m-d")) . " 23:59:59'"));
$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
?>
<div class="scheduleTable" style="grid-template-columns: auto  auto auto auto auto auto 30px auto auto auto;">
	<div>#</div>
	<div></div>
	<div>№ Карты</div>
	<div>Ист</div>
	<div></div>
	<div><i class="fas fa-star"></i></div>
	<div></div>
	<div></div>
	<div style="text-align: center;">Ф.И.О.</div>
	<div class="leftRightBorder">
		<?
		for ($time = mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 09:00:00"); $time <= mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 21:00:00"); $time += 60 * 60) {
			?><span style="width: 50px;"><?= date("H:i", $time); ?></span><?
		}
		?>
	</div>

	<?
	$clients = query2array(mysqlQuery($clientsSQL));
	$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
	foreach ($clients as $key => &$client2) {
		if (1) {
			$clients[$key]['stomRemains'] = mfa(mysqlQuery("SELECT 
             
   sum((SELECT 
                     SUM(`qty`) as `stomRemainsSum`
                    FROM
                        (SELECT 
                            SUM(`qty`) AS `qty`
                        FROM
                            (SELECT 
                            `f_salesContentService` AS `service`,
                                `f_salesContentQty` AS `qty`,
                                `f_salesContentPrice` AS `price`
                        FROM
                            `f_subscriptions`
                        WHERE
                            `f_subscriptionsContract` = `idf_sales` UNION ALL SELECT 
                            `servicesAppliedService` AS `service`,
                                - `servicesAppliedQty` AS `qty`,
                                `servicesAppliedPrice` AS `price`
                        FROM
                            `servicesApplied`
                        WHERE
                            `servicesAppliedContract` = `idf_sales` AND isnull(`servicesAppliedDeleted`)) AS `services`
                        GROUP BY `service` , `price`) AS `presum`))  AS `stomRemains`
        FROM
            `f_sales`
        WHERE
            `f_salesClient` = " . $client2['idclients'] . "
                AND `f_salesEntity` = 2 and isnull(`f_salesCancellationDate`);"))['stomRemains'];
		}
//		$client2['clientIsNew'] = clientIsNew($client2['idclients'], ($_GET['date'] ?? date("Y-m-d")));
//		print strtotime($client2['clientsOldSince']) . '<br>';
//		print strtotime(($_GET['date'] ?? date("Y-m-d"))) . '<br>';
//		var_dump(($client2['clientsOldSince'] && intval(strtotime($client2['clientsOldSince'])) < intval(strtotime($_GET['date'] ?? date("Y-m-d")))));
		$client2['clientIsNew'] = (!$client2['clientsOldSince'] || intval(strtotime($client2['clientsOldSince'])) >= intval(strtotime($_GET['date'] ?? date("Y-m-d"))));
	}
	$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
	$n = 0;
	usort($clients, function ($a, $b) {
		if ($a['clientIsNew'] <=> $b['clientIsNew']) {
			return $b['clientIsNew'] <=> $a['clientIsNew'];
		} else {
			return mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName']);
		}
	});
	$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
	foreach ($clients as $client) {
		$n++;
		$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
		$filtered = array_filter($allServices, function ($element) {
			global $client;
			return ($element['servicesAppliedTimeBegin'] ?? 0) && ($element['servicesAppliedTimeEnd'] ?? 0) && $client['servicesAppliedClient'] == $element['servicesAppliedClient'] && empty($element['servicesAppliedDeleted']);
		});
		$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);

		$countServices = count($filtered);
		?>
		<div class="nameTimelineRow">
			<div><?= $n; ?></div>
			<div style="white-space: nowrap; color: <?= $countServices == $client['AScount'] ? 'gray' : 'red'; ?>;"><i class="fas fa-notes-medical"></i><span style="font-size: 0.7em; color: black;"> x<?= $client['AScount']; ?></span></div>
			<div style="text-align: center;"><?= $client['clientsAKNum']; ?></div>
			<div style="text-align: center; color: gray; font-size: 0.7em;"><?= $client['clientsSourcesLabel'] ?? '-'; ?></div>
			<div style="text-align: center;">

				<?
				$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
				if (1 || $_USER['id'] == 176) {

					$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
					$curVisit = array_filter($clientsVisits, function ($element) {
						global $client, $curtime;
						return $client['servicesAppliedClient'] == $element['clientsVisitsClient'];
					});
					$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
					if (count($filtered) && !count($curVisit) && time() > 7200 + mystrtotime(min(array_column($filtered, 'servicesAppliedTimeBegin')))) {
						?><i class="fas fa-walking toLate" title="Опаздывает на <?= secondsToTime(mystrtotime(min(array_column($filtered, 'servicesAppliedTimeBegin'))) - time()); ?>" style="
						   color: red;
						   animation-delay: <?= rand(50, 1000); ?>ms;
						   border: 1px solid red;
						   width: 14px;
						   height: 14px;
						   vertical-align: middle;
						   line-height: 11px;
						   font-size: 8px;
						   border-radius: 50%;
						   background-color: hsla(0,50%,90%,0.8);
						   "></i><?
					   } elseif (count($filtered) && !count($curVisit) && time() > 3600 + mystrtotime(min(array_column($filtered, 'servicesAppliedTimeBegin')))) {
						   ?><i class="fas fa-walking" title="Опаздывает на <?= secondsToTime(mystrtotime(min(array_column($filtered, 'servicesAppliedTimeBegin'))) - time()); ?>" style="
						   color: red;
						   border: 1px solid red;
						   width: 14px;
						   height: 14px;
						   vertical-align: middle;
						   line-height: 11px;
						   font-size: 8px;
						   border-radius: 50%;
						   background-color: hsla(0,50%,90%,0.8);
						   "></i><?
					   } elseif (count($filtered) && !count($curVisit) && time() > mystrtotime(min(array_column($filtered, 'servicesAppliedTimeBegin')))) {
						   ?><i class="fas fa-walking" title="Опаздывает на <?= secondsToTime(mystrtotime(min(array_column($filtered, 'servicesAppliedTimeBegin'))) - time()); ?>" style="
							color: orange;
							border: 1px solid orange;
							width: 14px;
							height: 14px;
							vertical-align: middle;
							line-height: 11px;
							font-size: 8px;
							border-radius: 50%;
							background-color: hsla(0,50%,90%,0.8);
							"></i><?
						}
						?><? }
					?>

			</div>

			<div style="display: flex; align-items: center; justify-content: center;">
				<?
				$aeREQ = mfa(mysqlQuery("-- client=17320&date=2021-04-04
SELECT
    SUM(payment) as `SUMM`, `client`
FROM
    (SELECT
        SUM(f_paymentsAmount) as `payment`,
        f_salesClient as `client`
    FROM
       f_sales
    LEFT JOIN f_payments ON (idf_sales = f_paymentsSalesID)
    WHERE
        f_salesClient = " . $client['idclients'] . "
            AND f_salesDate = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "'
            UNION ALL
     SELECT
        SUM(f_creditsSumm) as `payment`,
        f_salesClient as `client`
    FROM
       f_sales
    LEFT JOIN f_credits ON (idf_sales = f_creditsSalesID)
    WHERE
        f_salesClient = " . $client['idclients'] . "
            AND f_salesDate = '" . mres(($_GET['date'] ?? date("Y-m-d"))) . "'
            ) AS `payments` GROUP BY `client`;"));
				$AE = getAE($aeREQ['SUMM'], ($_GET['date'] ?? date("Y-m-d")));
				?>
				<?
				if ($aeREQ['SUMM'] !== null) {

					$startColors = [
						'0' => 'silver',
						'0.5' => 'lightblue',
						'1' => 'green',
						'1.5' => 'orange'
					];
					$AEVAL = floatval(getAE($aeREQ['SUMM'], ($_GET['date'] ?? date("Y-m-d"))));
					$startColor = ($startColors["$AEVAL"] ?? 'gray');
					?>
					<i class="fas fa-star" title="<?= $AEVAL; ?>" style="font-size: 0.7em; color: <?= $startColor; ?>;"></i>
				<? } ?>

			</div>
			<div>
				<?
				if (R(174)) {
					if (count(array_filter($allServices, function ($serviceApplied) {
										global $client;
										return
										$client['servicesAppliedClient'] == $serviceApplied['servicesAppliedClient'] &&
										$serviceApplied['servicesAppliedDate'] == ($_GET['date'] ?? date("Y-m-d")) &&
										$serviceApplied['servicesAppliedContract'] === null;
									}))) {
						?><a style="font-size: 0.8em;" target="_blank" href="/pages/checkout/index.php?client=<?= $client['idclients']; ?>"><i class="fas fa-cash-register"></i></a><?
					}
				}
				?>
			</div>
			<div><? if ($client['confirmed']) { ?><i class="fas fa-phone-square" title="Подтверждён" style="color:green;"></i><? } ?></div>
			<div style=" display: flex; align-items: center;">
				<?
				$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
				if ($client['clientIsNew']) {
					if ($client['clientsOldSince'] == ($_GET['date'] ?? date("Y-m-d"))) {
						?><i class="fas fa-angle-double-up" style="color: hsl(120,100%,30%); margin: 0 3px;"></i><?
					} else {
						?><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%); margin: 0 3px;"></i><?
					}
					?>

				<? }
				?>
				<? $debt = isClientHaveDebt($client['idclients']); ?>
				<?= ($debt) ? '<i class="fab fa-creative-commons-nc" style="color: hsl(0,100%,50%); margin: 0 3px;" title="Непогашенная рассрочка ' . number_format($debt, 0, '.', ' ') . 'р."></i>' : ''; ?>

				<?= ($client['stomRemains'] > 0) ? '<a href="/sync/utils/word/stomtransfer.php?client=' . $client['idclients'] . '" target="_blank">🦷</a>' : ''; ?>

				<?
				$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
				if (1) {
					$bdts = strtotime(date(date("Y") . "-m-d", strtotime($client['clientsBDay'])));
					if (strtotime(($_GET['date'] ?? date("Y-m-d"))) - 60 * 60 * 24 * 3 < $bdts && strtotime(($_GET['date'] ?? date("Y-m-d"))) + 60 * 60 * 24 * 3 > $bdts) {
						?><i class="fas fa-gift" style="color: hsl(120,100%,30%); margin: 0 3px;" title="День рождения <?= date("d.m.Y", strtotime($client['clientsBDay'])); ?>"></i><?
					}
//						printr();
				}
				?>
				<? if (R(47)) {
					?><a target="_blank" style="font-size: 0.6em; line-height: 0.4em;" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&amp;date=<?= ($_GET['date'] ?? date("Y-m-d")); ?>"><i class="fas fa-external-link-alt"></i></a><? } ?><a style="white-space: nowrap;" href="/pages/reception/?client=<?= $client['servicesAppliedClient']; ?>&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>"><?= mb_ucfirst($client['clientsLName']); ?> <?= mb_ucfirst($client['clientsFName']); ?> <?= mb_ucfirst($client['clientsMName']); ?></a>
				<?
				$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
//												printr($clientsVisits);
				?>
																																																			<!--<i class="fas fa-running" style="font-size: 0.7em; color: gray; right: 0px; position: absolute; top: 50%; transform: translateY(-50%);"></i>-->
			</div>
			<div class="leftRightBorder">
				<?
				$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
				for (
						$time = mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 09:00:00"); $time <= mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 21:59:59"); $time += 30 * 60) {
					$PGT[__FILE__]['t1'][$n][__LINE__][$time] = (microtime(1) - $PAGEstart);
					$curtime = $client['servicesAppliedDate'] . ' ' . date("H:i:s", $time);
					?><span style="width: 25px;">
					<?
					$currentServices = array_filter($allServices, function ($element) {
						global $client, $curtime;
						return (
						mystrtotime($element['servicesAppliedTimeBegin']) >= mystrtotime($curtime) &&
						mystrtotime($element['servicesAppliedTimeBegin']) < mystrtotime($curtime) + 1800) &&
						$client['servicesAppliedClient'] == $element['servicesAppliedClient'];
					});
					$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);

					$curVisits = array_filter($clientsVisits, function ($element) {
						global $client, $curtime;
						return (
						mystrtotime($element['clientsVisitsTime']) >= mystrtotime($curtime) &&
						mystrtotime($element['clientsVisitsTime']) < mystrtotime($curtime) + 1800) &&
						$client['servicesAppliedClient'] == $element['clientsVisitsClient'];
					});

					$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);

					if (count($currentServices)) {
						foreach ($currentServices as $service) {
//															$_TICK[__LINE__][] = microtime(1) - $start;
							if ($service['servicesAppliedDeleted']) {
								continue;
							}

//							$usersPositions = query2array(mysqlQuery("SELECT * FROM `usersPositions` WHERE `usersPositionsUser`='" . $service['servicesAppliedBy'] . "'"));

							if (!$service['schedule'] && $service['usersLastName']) {
								$border = 'border: 2px solid red;'; //
							} else {
								$border = '';
							}
							/// COLOR
							$bgcolor = 'hsla(202,50%,70%,0.6)';
							if (!$service['servicesAppliedPersonal']) {
								$bgcolor = 'hsla(30,100%,60%,0.6)';
							}
							if ($service['servicesAppliedStarted']) {
								$bgcolor = 'hsla(106,100%,60%,0.6)';
							}
							if ($service['servicesAppliedFineshed']) {
								$bgcolor = 'hsla(30,0%,60%,0.6)';
							}
							if (mystrtotime($service['servicesAppliedTimeBegin']) < time() && !$service['servicesAppliedStarted']) {
								$bgcolor = 'hsla(0,100%,50%,0.5)';
							}
							if (!$service['idservices']) {
								$bgcolor = 'hsla(60,80%,60%,0.6)';
							}

							/// COLOR
							?>
								<div class="timelinePiece" onclick="this.classList.toggle('showTooltip');" style="
									 font-size: 10px;
									 /*font-weight: bold;*/
									 line-height: 14px;
									 display: flex;
									 justify-content: center;
									 align-items: center;
									 top: 10.5px;
									 <?= $border; ?>
									 background-color: <?= $bgcolor; ?>;
									 left: <?= (25 * (mystrtotime($service['servicesAppliedTimeBegin']) - mystrtotime($curtime)) / 1800) ?>px;
									 width: <?= max(15, ($service['servicesAppliedDuration'] / 1800) * 25 - 1); ?>px;
									 color: #333;
									 ">
										 <?= ($service['servicesAppliedIsDiagnostic'] ? 'д' : '') . ($service['servicesAppliedService'] == 361 ? 'к' : '') . ((!round($service['servicesAppliedPrice']) && !$service['servicesAppliedContract']) ? 'п' : '') . ((round($service['servicesAppliedPrice']) && !$service['servicesAppliedContract']) ? '<b style="color: red;">$</b>' : ''); ?>
									<div class="tooltiptext">
										Специалист: <? if (!$service['schedule'] && $service['usersLastName']) {
											 ?><b title="Смена не установлена">🛑</b><? }
										 ?><?= $service['usersLastName'] ? ('<a ' . ((!$service['schedule']) ? ' style="color: red;"' : '' ) . ' href="/pages/reception/?personal=' . $service['idusers'] . '&date=' . ($_GET['date'] ?? date("Y-m-d")) . '">' . $service['usersLastName'] . ' ' . $service['usersFirstName'] . '</a>') : '<b style="color: orange;">Без специалиста!</b>'; ?>





										<? if (($service['usersICQ'] ?? 0) && (!$service['servicesAppliedStarted']) && (($_GET['date'] ?? date("Y-m-d")) == date("Y-m-d"))) { ?>
											<div style="display: inline-block; text-align: center; width: 50px; height: 20px;"><i class="fas fa-concierge-bell timelineBell" <? if (time() > mystrtotime($service['servicesAppliedTimeBegin'])) { ?>style="color: red;"																					<? } else { ?>style="color: darkgreen;"
													<?
												}
												?> onclick="fetch('/sync/api/icq/send.php', {
																				body: JSON.stringify({action: 'receptionCall', service: <?= $service['idservicesApplied']; ?>}),
																				credentials: 'include',
																				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
																			})"></i></div>
										<? } ?><br>




										Процедура: <?= $service['servicesName'] ?? 'Не указана'; ?><?= (!round($service['servicesAppliedPrice']) && !$service['servicesAppliedContract']) ? ' (подарочная)' : '' ?><br>
										Начало: <?= $service['servicesAppliedStarted'] ? (date("H:i", mystrtotime($service['servicesAppliedStarted'])) . ' (по плану ' . date("H:i", mystrtotime($service['servicesAppliedTimeBegin'])) . ')') : ('запланировано на ' . date("H:i", mystrtotime($service['servicesAppliedTimeBegin']))); ?><br>
										Окончание: <?= $service['servicesAppliedFineshed'] ? (date("H:i", mystrtotime($service['servicesAppliedFineshed'])) . ' (по плану ' . date("H:i", mystrtotime($service['servicesAppliedTimeEnd'])) . ')') : ('запланировано на ' . date("H:i", mystrtotime($service['servicesAppliedTimeEnd']))); ?><br>
									</div>
									<? if (0 && $service['servicesAppliedStarted']) {
										?><i class="far fa-arrow-alt-circle-right" style="position: absolute; left: 1px; top: 1px; background-color: deepskyblue; border-radius: 50%;"></i><? }
									?>
									<? if (0 && $service['servicesAppliedFineshed']) {
										?><i class="far fa-check-circle" style="position: absolute; right: 1px; top: 1px; background-color: greenyellow; border-radius: 50%;"></i><? }
									?>
									<? if (0 && !$service['servicesAppliedPersonal']) {
										?><i class="fas fa-exclamation-triangle" style="position: absolute; top: 1px; transform: translateX(-50%); color: red;"></i><? }
									?>

								</div>
								<?
							}
							$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
							?>

							<?
						}

//            [clientsVisitsClient] => 210
//            [clientsVisitsTime] => 2020-06-01 17:32:43
						if (count($curVisits)) {
							foreach ($curVisits as $curVisit) {
//																printr($curVisit);
								?><i class="fas fa-walking" title="<?= date("H:i", mystrtotime($curVisit['clientsVisitsTime'])); ?>" style="
								   left: <?= (25 * (mystrtotime($curVisit['clientsVisitsTime']) - mystrtotime($curtime)) / 1800) ?>px;
								   color: darkgreen;
								   position: absolute;
								   top: 50%;
								   opacity: 50%;
								   z-index: 20;
								   border: 1px solid darkgreen;
								   width: 14px;
								   height: 14px;
								   line-height: 13px;
								   border-radius: 50%;
								   background-color: hsla(120,50%,90%,0.8);
								   transform: translate(-50%,-50%);"></i><?
							   }
							   $PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
						   }
						   ?>
						   <? if (time() >= mystrtotime($curtime) && time() < mystrtotime($curtime) + 30 * 60) {
							   ?><div class="timelineCursor" style=" left: <?= round(1 + ( 25 * ((time() - mystrtotime($curtime)) / (60 * 30)))); ?>px;"></div><? } ?>
					</span><?
				}
				?>
			</div>

		</div>
		<?
		$PGT[__FILE__]['t1'][$n][__LINE__] = (microtime(1) - $PAGEstart);
	}
	?>
