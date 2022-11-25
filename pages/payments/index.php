<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (R(194) && ($_GET['deletePayment'] ?? false)) {
  mysqlQuery("UPDATE `usersPayments` SET "
			 . "`usersPaymentsDeletedTime` = NOW(),"
			 . "`usersPaymentsDeletedBy` = '" . $_USER['id'] . "'"
			 . " WHERE `idusersPayments` = '" . mres($_GET['deletePayment']) . "'");

  mysqlQuery("UPDATE `cashFlow` SET "
			 . "`cashFlowDeleted` = NOW() "
			 . " WHERE `idcashFlow` = (SELECT `usersPaymentsCFid` FROM `usersPayments` WHERE `idusersPayments` = '" . mres($_GET['deletePayment']) . "')");

  header("Location: " . GR2(['deletePayment' => null]));
  exit('ok');
}

if (R(194) &&
		  ($_POST['usersPaymentsUser'] ?? false) &&
		  ($_POST['usersPaymentsFrom'] ?? false) &&
		  ($_POST['usersPaymentsTo'] ?? false) &&
		  ($_POST['cashFlowType'] ?? false) &&
		  ($_POST['usersPaymentsAmount'] ?? false) &&
		  ($_POST['upt'] ?? false)
) {

  $user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = " . sqlVON($_POST['usersPaymentsUser']) . ""));
  if (($_POST['upt'] ?? false) == 1) {
	 mysqlQuery("INSERT INTO `cashFlow` SET "
				. "`cashFlowSumm`= " . mres(-$_POST['usersPaymentsAmount']) . ""
				. ", `cashFlowComment`='" . $user['usersLastName'] . " " . $user['usersFirstName'] . "'"
				. ", `cashFlowDate`= NOW()"
				. (!empty($_POST['cashFlowType']) ? ", `cashFlowType`='" . FSI($_POST['cashFlowType']) . "'" : ''));
	 $usersPaymentsCFid = mysqli_insert_id($link);
  }


  mysqlQuery("INSERT IGNORE INTO `usersPayments` SET"
			 . " `usersPaymentsUser` = " . sqlVON($_POST['usersPaymentsUser']) . ","
			 . " `usersPaymentsFrom` = " . sqlVON($_POST['usersPaymentsFrom']) . ","
			 . " `usersPaymentsTo` = " . sqlVON($_POST['usersPaymentsTo']) . ","
			 . " `usersPaymentsAmount` = '" . floatval($_POST['usersPaymentsAmount']) . "',"
			 . " `usersPaymentType` = " . sqlVON($_POST['upt']) . ","
			 . " `usersPaymentsCFT` = " . sqlVON($_POST['cashFlowType']) . ","
			 . " `usersPaymentsCFid` = " . sqlVON($usersPaymentsCFid ?? null) . ","
			 . " `usersPaymentsTime`= NOW(),"
			 . " `usersPaymentsBy`='" . $_USER['id'] . "'"
			 . "");

  header("Location: " . GR());
  exit('ok');
}











$pageTitle = $load['title'] = 'Начисления';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$nDays = date("t", mktime(12, 0, 0, ($_GET['m'] ?? date("m")), 1, ($_GET['Y'] ?? date("Y"))));
//idfingerLog, fingerLogTime, fingerLogData, fingerLogUser

$UPTs = [
	 '1' => 'Наличные',
	 '2' => 'Оф/банк'
];

$usersPaymentsTotal = 0;

$from = ($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-01';
$to = ($_GET['Y'] ?? date("Y")) . '-' . ($_GET['m'] ?? date("m")) . '-' . $nDays;
$user = (($_GET['user'] ?? false) && in_array($_USER['id'], [135, 199, 176])) ? $_GET['user'] : $_USER['id'];
$usersSchedule = query2array(mysqlQuery("SELECT * FROM `usersSchedule` WHERE `usersScheduleUser`=$user AND `usersScheduleDate`>='$from' AND  `usersScheduleDate`<='$to'"), 'usersScheduleDate');
?>
<style>
  .mytable {
	 border-top: 1px solid gray;
	 border-left: 1px solid gray;
	 border-collapse: collapse;
	 background-clip: padding-box;
  }
  .mytable>tbody>tr>td, th {
	 padding: 2px 12px;
	 border-bottom: 1px solid gray;
	 border-right: 1px solid gray;
	 background-clip: padding-box;
  }

</style>
<div class="neutral box">
  <div class="box-body">
	 <?
	 $payments = getPayments($user, ['from' => $from, 'to' => $to]);
//printr(get_weekdays(3, 2022));
	 ?>

	 <h2 style="width: 90%;"><div style="display: inline-block;">
		  <select onchange="GETreloc('m', this.value);" autocomplete="off">
			 <?
			 for ($m = 1; $m <= 12; $m++) {
				?><option value="<?= ($m < 10 ? '0' : '') . $m; ?>"<?= ($m == ($_GET['m'] ?? date("m")) ? ' selected' : ''); ?>><?= $_MONTHES['full']['nom'][$m]; ?></option><?
			 }
			 ?>
		  </select>
		</div>
		/
		<div style="display: inline-block;">
		  <select onchange="GETreloc('Y', this.value);" autocomplete="off">
			 <?
			 for ($Y = date("Y", time() + 60 * 60 * 24 * 30); $Y >= 2020; $Y--) {
				?><option value="<?= $Y; ?>"<?= ($Y == ($_GET['Y'] ?? date("Y")) ? ' selected' : ''); ?>><?= $Y; ?></option><?
			 }
			 ?>
		  </select>
		</div></h2>

	 <?

	 function hideEmpty($value) {
		return $value !== '' ? round($value) : '';
	 }
	 ?>
	 <? ?>
	 <div style="text-align: center;">
		<div style="display: inline-block; text-align: left;">

		  <table style="margin: 20px;" class="mytable">
			 <? if (0 && R(194)) {
				?>
  			 <tr style="color: gray; padding: 5px;">
  				<td style=" padding: 5px;" colspan="<?= count($payments['types']); ?>"><input type="button" value="Начальный остаток"></td>
  				<th style=" padding: 5px;" class="C"><input type="text" style="width: 100px;"></th>
  			 </tr>
			 <? } ?>
			 <tr>
				<td></td>
				<? foreach (($payments['types'] ?? []) as $idtype => $type) {
				  ?>
  				<th class="C"><?= R(194) ? ('п.' . $idtype . '] ') : ''; ?><?= $type['titleShort'] ?? '???'; ?></th>
				<? } ?>
				<? if (!count(($payments['types'] ?? []))) {
				  ?>
  				<td class="C"> - </td>
				<? }
				?>
			 </tr>

			 <?
			 if ($payments['dates'] ?? false) {
				ksort($payments['dates']);
			 }

			 $firsthalf = false;
			 $dayindex = 0;
//					printr();
			 foreach (dates($from, $to) as $date) {
				$data = ($payments['dates'][$date] ?? null);
				$dayindex++;
				if (!$firsthalf && (date("d.m", strtotime($date)) >= 16)) {
				  $firsthalf = true;
				  ?>
	 			 <tr style="color: gray;">
	 				<th class="R">1-15:</th>
					 <?
					 $presumm = 0;

					 foreach (($payments['types'] ?? []) as $idtype => $type) {
						$paymentsSumm = paymentsSumm($payments, $idtype, 1);
						$presumm += $paymentsSumm;
						?>
						<th class="C"><?= $paymentsSumm; ?></th>
					 <? } ?>
	 			 </tr>
	 			 <tr style="color: gray;">
	 				<td colspan="<?= count($payments['types'] ?? []); ?>">По всем начислениям c 1 по 15</td>
	 				<th class="C"><?= $presumm; ?></th>
	 			 </tr>

				  <?
				  $usersPayments = query2array(mysqlQuery("SELECT * FROM `usersPayments`"
										. " LEFT JOIN `cashFlowTypes` ON (`idcashFlowType` = `usersPaymentsCFT`)"
										. " WHERE "
										. " `usersPaymentsUser` = '$user' AND "
										. " `usersPaymentsFrom` = '$from' AND "
										. " `usersPaymentsTo` =  '" . date("Y-m-15", strtotime($date)) . "' "
										. " AND isnull(`usersPaymentsDeletedTime`)"
										. ""));
				  ?>
				  <?
				  if (R(194) && count($payments['types'] ?? [])) {
					 ?>
					 <tr style="color: gray; padding: 5px;">
						<td style=" padding: 5px;" colspan="<?= count($payments['types']) + 1; ?>">
						  <form action="<?= GR(); ?>" method="POST">
							 <input autocomplete="off" name="usersPaymentsUser" type="hidden" value="<?= $user; ?>">
							 <input autocomplete="off" name="usersPaymentsFrom" type="hidden" value="<?= $from; ?>">
							 <input autocomplete="off" name="usersPaymentsTo" type="hidden" value="<?= date("Y-m-15", strtotime($date)); ?>">
							 <table>
								<tr>
								  <td colspan="3" class="C">Выплаты</td>
								</tr>

								<?
								$usersPaymentsTotal1 = array_sum(array_column($usersPayments, 'usersPaymentsAmount'));
								foreach (($usersPayments ?? []) as $usersPayment) {
								  ?>
		  						<tr>
		  						  <td><?= $usersPayment['cashFlowTypeName'] ?? '-'; ?></td>
		  						  <td><?= $usersPayment['usersPaymentsAmount'] ?? ''; ?></td>
		  						  <td><?= ($usersPayment['usersPaymentType'] ?? false) ? $UPTs[$usersPayment['usersPaymentType']] : '--'; ?></td>
		  						  <td><button type="button" onclick="GR({deletePayment:<?= $usersPayment['idusersPayments']; ?>})"><i class="fas fa-times-circle" style="color: #990000;"></i></button></td>
		  						</tr> 
								  <?
								}
								?>
								<tr>

								  <td><select name="cashFlowType"><option value="">ДДС</option><?
										foreach (query2array(mysqlQuery("SELECT * FROM `cashFlowTypes`")) as $type) {
										  ?><option value="<?= $type['idcashFlowType'] ?>"><?= $type['cashFlowTypeName'] ?></option><? } ?></select></td>
								  <td><input autocomplete="off" name="usersPaymentsAmount" type="text" style="width: 100px; float: right;" value=""></td>
								  <td>
									 <select name="upt">
										<option value="1"><?= $UPTs['1']; ?></option>
										<option value="2"><?= $UPTs['2']; ?></option>
									 </select>
								  </td>
								  <td colspan="3" class="C">
									 <button autocomplete="off" type="submit"><i class="fas fa-plus-circle" style="color: #009900;"></i></button>
								  </td>
								</tr>
							 </table>


						  </form>
						</td>
					 </tr>
					 <?
				  }

				  if (!R(194) && count($payments['types'] ?? [])) {
					 $usersPaymentsTotal1 = array_sum(array_column($usersPayments, 'usersPaymentsAmount'));
					 foreach ($usersPayments as $usersPayment) {
						?>
		  			 <tr>
		  				<td style=" padding: 5px;" colspan="<?= count($payments['types']); ?>">Выплаты <?= $UPTs[$usersPayment['usersPaymentType']]; ?> <?= date("d.m в H:i", strtotime($usersPayment['usersPaymentsTime'])); ?> </td>
		  				<td class="C"><?= round($usersPayment['usersPaymentsAmount'], 2); ?></td>
		  			 </tr>

						<?
					 }
				  }
				  ?>
				<? }
				?>

  			 <tr>
  				<td>
					 <?= date("d.m", strtotime($date)); ?>
					 <?= ($usersSchedule[$date]['usersScheduleDuty'] ?? false) ? 'ДС' : ''; ?>
  				</td>
				  <?
				  foreach (($payments['types'] ?? []) as $idtype => $type) {
					 ?>
	 				<td class="C" <?= (($_GET['type'] ?? false) == $idtype && ($_GET['date'] ?? false) == $date) ? ' style="background-color: #DDD; border-bottom: none;"' : ''; ?>><a href="<?=
						GR2(
								  [
										'date' => ((($_GET['date'] ?? false) == $date) && (($_GET['type'] ?? false) == $idtype)) ? null : $date,
										'type' => ((($_GET['date'] ?? false) == $date) && (($_GET['type'] ?? false) == $idtype)) ? null : $idtype,
						]);
						?>"><?= hideEmpty($data[$idtype]['reward'] ?? ''); ?></a></td>
					 <? } ?>
  			 </tr>
				<?
				if (in_array(($_GET['type'] ?? false), [39, 40, 49, 51, 52, 53, 54, 57, 58, 59, 60, 61, 62, 63, 64]) && ($_GET['date'] ?? false) == $date) {//Процент от продаж всех
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
						<?
						if ($_USER['id'] == 176) {
//										printr(($payments['dates'][$_GET['date']][$_GET['type']] ?? []));
						}
						?>
	 				  <table class="mytable" style="width: 100%; background-color: #FFF; position: relative;">
	 					 <tr style="position: sticky; top: 0px; z-index: 3;">
	 						<th style="background-color: #FFF;">#</th>
	 						<th style="background-color: #FFF;">Клиент</th>
	 						<th class="C" style="background-color: #FFF;">Сумма<br>продажи<br><span style="font-size:0.6em; line-height: 0.6em;">(дата)</span></th>
	 						<th class="C" style="background-color: #FFF;">Тип<br>абон</th>
	 						<th class="C" style="background-color: #FFF;">Пред.<br>плат.</th>
	 						<th class="C" style="background-color: #FFF;">Поступ<br>платеж<br><span style="font-size:0.6em; line-height: 0.6em;">(дата)</span></th>
	 						<th class="C" style="background-color: #FFF;">%</th>
	 						<th class="C" style="background-color: #FFF;"><i class="fas fa-user" title="Количество участников продажи"></i></th>
	 						<th class="C" style="background-color: #FFF;">Премия</th>
	 					 </tr>
						  <?
						  $n = 0;
						  foreach (($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []) as $client) {
//                                            if (!($client['paymentValue'] * ($client['PV40'] ?? 0))) {
//                                                continue;
//                                            }
//												printr($payments['dates'][$_GET['date']][$_GET['type']]);
//												printr($client);
							 ?>
							 <tr>
								<td><?= ++$n; ?></td>
								<td>
								  <a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>">

									 <?= $client['clientsLName']; ?>
									 <?= $client['clientsFName']; ?>
									 <?= $client['clientsMName']; ?>
								  </a>
								</td>
								<td class="R"><div style="line-height: 0.6em; padding: 3px;"><?= round($client['f_salesSumm']); ?> <br><span style="font-size:0.6em; line-height: 0.6em;">(<?= date("d.m.Y", strtotime($client['f_salesDate'])); ?>)</span></div></td>
								<td class="C">
								  <?= ['1' => 'I', '2' => 'II'][$client['f_salesType']]; ?>
								</td>
								<td class="R"<?= $client['prePaymentsSumm'] < 0 ? ' style="color: red; font-weight: bolder;"' : '' ?>><div style="line-height: 0.6em; padding: 3px;"><?= $client['prePaymentsSumm']; ?></div></td>
								<td class="R"<?= $client['payment'] < 0 ? ' style="color: red; font-weight: bolder;"' : '' ?>><div style="line-height: 0.6em; padding: 3px;"><?= $client['payment']; ?> <?= $client['payment'] < 0 ? ('<br><span style="font-size:0.6em; line-height: 0.6em;">(' . date("d.m.Y", strtotime($client['paymentDate'])) . ')</span>') : '' ?></div></td>
								<td class="C"><?= ($client['percent'] ?? 0) * 100; ?></td>
								<td class="C"><?= $client['saleParticipants']; ?></td>
								<td class="R"<?= $client['payment'] < 0 ? ' style="color: red; font-weight: bolder;"' : '' ?>><?= $client['reward']; ?></td>
							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>
				  <?
				}


				if (in_array(($_GET['type'] ?? false), [11]) && ($_GET['date'] ?? false) == $date) {//Процент от продаж (сетка)
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
						<?
//									printr(($payments['dates'][$_GET['date']][$_GET['type']] ?? []));
						?>
	 				  <table class="mytable" style="width: 100%; background-color: #FFF; position: relative;">
	 					 <tr style="position: sticky; top: 0px; z-index: 3;">
	 						<th style="background-color: #FFF;">#</th>
	 						<th style="background-color: #FFF;">Клиент</th>
	 						<th class="C" style="background-color: #FFF;">Т.А.</th>
	 						<th class="C" style="background-color: #FFF;">Сумма<br>продажи</th>
	 						<th class="C" style="background-color: #FFF;">Поступивший<br>платеж</th>
	 						<th class="C" style="background-color: #FFF;">%</th>
	 						<th class="C"><i class="fas fa-user"></i></th>
	 						<th class="C" style="background-color: #FFF;">Премия</th>
	 					 </tr>
						  <?
						  $n = 0;
						  foreach (($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []) as $client) {
//			if (!($client['paymentValue'] * ($client['PV40'] ?? 0))) {
//				continue;
//			}
//											printr();
//												printr($client); 
							 ?>
							 <tr>
								<td><?= ++$n; ?></td>
								<td>
								  <a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>">

									 <?= $client['clientsLName']; ?>
									 <?= $client['clientsFName']; ?>
									 <?= $client['clientsMName']; ?>
								  </a>
								</td>
								<td class="C"><?= [null => '???', '1' => 'I', '2' => 'II', '3' => '?'][$client['f_salesType']]; ?></td>
								<td class="R"><?= round($client['f_salesSumm']); ?></td>
								<td class="R"<?= $client['payment'] < 0 ? ' style="color: red; font-weight: bolder;"' : '' ?>><?= $client['payment']; ?></td>
								<td class="C"><?= ($payments['dates'][$_GET['date']][$_GET['type']]['coeff'] ?? 0) * 100; ?></td>
								<td class="C"><?= $client['saleParticipants'] > 1 ? '1/' . $client['saleParticipants'] : $client['saleParticipants']; ?></td>

								<td class="R"<?= $client['payment'] < 0 ? ' style="color: red; font-weight: bolder;"' : '' ?>><?= round($client['payment'] * ($payments['dates'][$_GET['date']][$_GET['type']]['coeff']) / $client['saleParticipants']); ?></td>
							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>
				  <?
				}






				if (in_array(($_GET['type'] ?? false), [0]) && ($_GET['date'] ?? false) == $date) {//Процент от продаж всех
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
						<?
						if ($_USER['id'] == 176) {
//										printr(($payments['dates'][$_GET['date']][$_GET['type']] ?? []));
						}
						?>
	 				  <table class="mytable" style="width: 100%; background-color: #FFF; position: relative;">
	 					 <tr style="position: sticky;">
	 						<th>#</th>
	 						<th>Клиент</th>
	 						<th class="C">Сумма<br>продажи<br><span style="font-size:0.6em; line-height: 0.6em;">(дата)</span></th>
	 						<th class="C">Поступивший<br>платеж<br><span style="font-size:0.6em; line-height: 0.6em;">(дата)</span></th>

	 						<th class="C">%</th>
	 						<th class="C">Премия</th>
	 					 </tr>
						  <?
						  $n = 0;
						  foreach (($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []) as $client) {
							 if (1) {//$client['myShift']
//												printr($payments['dates'][$_GET['date']][$_GET['type']]);
//												printr($client);
								?>
		  					 <tr>
		  						<td><?= ++$n; ?></td>
		  						<td>
		  						  <a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>">

										<?= $client['clientsLName']; ?>
										<?= $client['clientsFName']; ?>
										<?= $client['clientsMName']; ?>
		  						  </a>
		  						  <!--
									 <?= $client['mySale']; ?>
									 <?= $client['idf_sales']; ?>
		  						  -->
		  						</td>
		  						<td class="R"<?= $client['paymentValue'] < 0 ? ' style=" font-weight: bolder;"' : '' ?>><div style="line-height: 0.6em; padding: 3px;"><?= round($client['f_salesSumm']); ?> <?= $client['paymentValue'] < 0 ? ('<br><span style="font-size:0.6em; line-height: 0.6em;">(от ' . date("d.m.Y", strtotime($client['f_salesDate'])) . ')</span>') : '' ?></div></td>
		  						<td class="R"<?= $client['paymentValue'] < 0 ? ' style="color: red; font-weight: bolder;"' : '' ?>><div style="line-height: 0.6em; padding: 3px;"><?= $client['paymentValue']; ?> <?= $client['paymentValue'] < 0 ? ('<br><span style="font-size:0.6em; line-height: 0.6em;">(' . date("d.m.Y", strtotime($client['paymentDate'])) . ')</span>') : '' ?></div></td>
		  						<td class="R"><?= 100 * ($client['PV49'] ?? 0); ?></td>
		  						<td class="R"<?= $client['paymentValue'] < 0 ? ' style="color: red; font-weight: bolder;"' : '' ?>><?= $client['paymentValue'] * $client['PV49']; ?></td>

		  					 </tr>
								<?
							 } else {
								continue;
								?>
		  					 <tr style="color: silver;">
		  						<td><?= ++$n; ?></td>
		  						<td>
		  						  <a style="color: silver;" target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>">
										<?= $client['clientsLName']; ?>
										<?= $client['clientsFName']; ?>
										<?= $client['clientsMName']; ?>
		  						  </a>
		  						</td>
		  						<td class="R"><?= round($client['f_salesSumm']); ?></td>
		  						<td class="R"><?= $client['paymentValue']; ?></td>
		  						<td class="R"><?= $client['paymentValue'] * $client['PV49']; ?></td>
		  					 </tr>
								<?
							 }
							 ?>

							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>
				  <?
				}




				if (in_array(($_GET['type'] ?? false), [48]) && ($_GET['date'] ?? false) == $date) {//% отпродаж
				  $data = ($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []);
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
	 				  <table class="mytable" style="width: 100%; background-color: #FFF;">
	 					 <tr>
	 						<th>Клиент</th>
	 						<th>Оплачено</th>
	 						<th class="C"><i class="fas fa-user"></i></th>
	 						<th>мой %</th>
	 					 </tr>

						  <?
						  foreach ($data as $payment) {
							 ?>
							 <tr>
								<td>
								  <?= $payment['clientsLName']; ?>
								  <?= $payment['clientsFName']; ?>
								</td>
								<td class="R"><?= ($payment['payment']); ?></td>
								<td class="C"><?= $payment['saleParticipants'] > 1 ? '1/' . $payment['saleParticipants'] : $payment['saleParticipants']; ?></td>
								<td class="R">
								  <?= round(($payment['payment'] / $payment['saleParticipants']) * ($payment['percent'] / 100)); ?>
								</td>
							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>


				  <?
				}




				if (in_array(($_GET['type'] ?? false), [3]) && ($_GET['date'] ?? false) == $date) {// кредитники
				  $data = ($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []);
				  printr($payments['dates'][$_GET['date']][$_GET['type']]);
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
	 				  <table class="mytable" style="width: 100%; background-color: #FFF;">
	 					 <tr>
	 						<th class="C">Клиент</th>
	 						<th class="C">Тип абонемента</th>
	 						<th class="C">Разовая</th>
	 						<th class="C">Стоимость</th>
	 						<th class="C">Премия</th>
	 					 </tr>

						  <?
						  foreach ($data as $sale) {
							 ?>
							 <tr>
								<td>
								  <?= $sale['clientsLName']; ?>
								  <?= $sale['clientsFName']; ?>
								  <?= $sale['clientsMName']; ?>
								</td>
								<td class="R"><?= ['1' => 'Первичный', '2' => 'Вторичный', '3' => '????'][$sale['f_salesType']]; ?></td>
								<td class="C"><?= $sale['f_salesIsSmall'] ? 'Разовая' : ''; ?></td>
								<td class="R"><?= round($sale['f_salesSumm']); ?></td>
								<td class="R"><? ?></td>

							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>


				  <?
				}


				if (in_array(($_GET['type'] ?? false), [0]) && ($_GET['date'] ?? false) == $date) {//% отпродаж
				  $data = ($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []);
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
	 				  <table class="mytable" style="width: 100%; background-color: #FFF;">
	 					 <tr>
	 						<th class="C">Клиент</th>
	 						<th class="C">T.A.</th>
	 						<th class="C">Оплачено</th>
	 						<th class="C">ставка %</th>
	 						<th class="C">мой %,р</th>
	 					 </tr>

						  <?
						  foreach ($data as $payment) {
							 ?>
							 <tr>
								<td>
								  <?= $payment['clientsLName']; ?>
								  <?= $payment['clientsFName']; ?>
								</td>
								<td class="C"><?= [null => '???', '1' => 'I', '2' => 'II', '3' => '?'][$payment['f_salesType']]; ?></td>
								<td class="R"><?= ($payment['payment']); ?></td>
								<td class="C"><?= $payment['percent']; ?></td>
								<td class="R">
								  <?= round(($payment['payment']) * ($payment['percent'] / 100)); ?>
								</td>
							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>


				  <?
				}


				if (
						  in_array(($_GET['type'] ?? false), [6, 9]) && ($_GET['date'] ?? false) == $date) {//Почасовая
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">

						<?
						$usersScheduleHalfs = [
							 null => 'Не установлена',
							 '11' => 'Полная смена',
							 '10' => 'Утренняя смена',
							 '01' => 'Вечерняя смена',
							 'SD' => 'Больничный',
							 'NA' => 'Недоступен',
							 'V' => 'Выходной'];

//									printr(($payments['dates'][$_GET['date']][$_GET['type']] ?? []));
						$data = ($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []);
//									printr($data);
						?>
	 				  <table class="mytable" style="width: 100%; background-color: #FFF;">
	 					 <tr>
	 						<td>Смена:</td>
	 						<td><?= $usersScheduleHalfs[($data['scheduleHalfs'] ?? null)]; ?></td>
	 					 </tr>
	 					 <tr>
	 						<td>Приход:</td>
	 						<td><?= ($data['fingerFrom'] ?? false) ? date("H:i", strtotime($data['fingerFrom'])) : 'Не зафиксирован'; ?></td>
	 					 </tr>

	 					 <tr>
	 						<td>Начало смены:</td>
	 						<td><?= ($data['scheduleFrom'] ?? false) ? date("H:i", strtotime($data['scheduleFrom'])) : 'Не установлена'; ?></td>
	 					 </tr>


	 					 <tr>
	 						<td>Окончание смены:</td>
	 						<td><?= ($data['scheduleTo'] ?? false) ? date("H:i", strtotime($data['scheduleTo'])) : 'Не установлена'; ?></td>
	 					 </tr>
	 					 <tr>
	 						<td>Уход:</td>
	 						<td><?= ($data['fingerTo'] ?? false) ? date("H:i", strtotime($data['fingerTo'])) : 'Не зафиксирован'; ?></td>
	 					 </tr>
	 					 <tr>
	 						<td>Отработано:</td>
	 						<td>
								<?= $H = floor(($data['fingerDuration'] ?? 0) / 3600); ?>ч.
								<?= floor((($data['fingerDuration'] ?? 0) - $H * 3600) / 60); ?>м.
	 						</td>
	 					 </tr>
	 					 <tr>
	 						<td>Ставка:</td>
	 						<td>
								<?= $payments['dates'][$_GET['date']][$_GET['type']]['userPaymentsValuesValue'] ?? '--'; ?>
	 						</td>
	 					 </tr>



	 				  </table>
	 				</td>
					 <? if (!count(($payments['types'] ?? []))) {
						?><th class="C"> - </th><? }
					 ?>
	 			 </tr>
				  <?
				}
				if (in_array(($_GET['type'] ?? false), [43, 44, 45, 46, 50, 56]) && ( ($_GET['date'] ?? false) == $date || !($_GET['date'] ?? false))) {//приходы маркетинг
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
	 				  <table class="mytable" style="width: 100%; background-color: #FFF;">
	 					 <tr>
	 						<th>Ф.И.О. Клиента</th>
	 						<th class="C">Источник</th>
	 						<th class="C">Премия</th>
	 						<th class="C">абн<br>до</th>
	 						<th class="C">раз<br>до</th>
	 						<th class="C">прод<br>сег</th>
	 						<th class="C">пос. виз. мес.</th>
	 						<th class="C">зчт</th>
	 					 </tr>
						  <?
						  foreach (($payments['dates'][$date][$_GET['type']]['data'] ?? []) as $client) {
							 ?>
							 <tr>
								<td>
								  <a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
									 <?= $client['clientsLName']; ?>
									 <?= $client['clientsFName']; ?>
									 <?= $client['clientsMName']; ?>
								  </a>
								</td>
								<td class="C" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
								  <?= $client['clientsSourcesLabel']; ?>
								</td>
								<td class="C" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
								  <?= round($client['clientsSourceReward'] ?? 0); ?>
								</td>
								<td class="C" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
								  <?= $client['salesQty']; ?>
								</td> 
								<td class="C" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
								  <?= $client['not_salesQty']; ?>
								</td>
								<td class="C" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
								  <?= $client['todaysSalesQty']; ?>
								</td>
								<td class="C" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
								  <?= $client['lastVizitMonthes'] ?? '1й визит'; ?>
								</td>

								<td class="C" <?= ($client['check'] ?? false) ? '' : 'style="color: silver;"' ?>>
								  <? if ($client['scoreMarket']) {
									 ?>	
		  						  <i class="fas fa-check-circle" style="color: <?= ($client['check'] ?? false) ? 'green' : 'silver' ?>;"></i>
								  <? } else { ?>
		  						  <i class="fas fa-times-circle" onclick="alert('<?= htmlentities($client['scoreDescription']); ?>')" style="color: red; cursor: pointer;"></i>
									 <?
								  }
								  ?>
								</td>

							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>
				  <?
				}
				if (($_GET['type'] ?? false) == 'dops' && ($_GET['date'] ?? false) == $date) {

				  /* {
					 "servicesAppliedQty": 1,
					 "servicesAppliedDate": "2022-04-22",
					 "servicesAppliedContract": 40746,
					 "servicesAppliedPrice": 85000,
					 "servicesAppliedIsDiagnostic": null,
					 "idservices": 15596,
					 "servicesName": "Нити Soft Lift LONG 8",
					 "idclients": 143,
					 "clientsLName": "Антонова",
					 "clientsFName": "Светлана",
					 "clientsMName": "Владимировна",
					 "usersServicesPaymentsSumm": 2500,
					 "usersServicesPaymentsSummFree": null,
					 "minWage": null,
					 "maxWage": null
					 } */
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
	 				  <table class="mytable" style="width: 100%; background-color: #FFF; hyphens: auto;">
						  <?
						  $servicesApplied = ($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []);
						  usort($servicesApplied, function ($a, $b) {
							 return $a['idclients'] <=> $b['idclients'];
						  });
						  $idclient = null;
						  $clientIndex = 0;
						  foreach ($servicesApplied as $client) {
							 if (!round($client['usersServicesPaymentsSumm'] ?? $client['minWage'] ?? 0)) {
								continue;
							 }
							 ?>
							 <?
							 if ($idclient != $client['idclients']) {
								$idclient = $client['idclients'];
								$clientIndex++;
								?>
		  					 <tr>
		  						<td colspan="3" style="text-indent: 20px; padding-top: 10px;">
									 <?= $clientIndex; ?>] <a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>">
										<?= $client['clientsLName']; ?>
										<?= $client['clientsFName']; ?>
										<?= $client['clientsMName']; ?>
		  						  </a>
		  						</td>
		  					 </tr>
								<?
							 }
							 ?>

							 <tr>
								<td>
								  <a href="/pages/services/index.php?service=<?= $client['idservices']; ?>" target="_blank"><?= $client['servicesName']; ?></a>
								</td>
								<td>
								  <?= $client['servicesAppliedQty']; ?>&Cross;<? if ($client['usersServicesPaymentsSumm'] ?? false) { ?><a target="_blank" href="/pages/personal/procedures.php?employee=<?= $user ?>&highlight=<?= $client['idservices']; ?>#service<?= $client['idservices']; ?>"><? } ?><?= round($client['usersServicesPaymentsSumm'] ?? $client['minWage'] ?? 0); ?>р.<? if ($client['usersServicesPaymentsSumm'] ?? false) { ?></a><? } ?>
								</td>
								<td class="R">
								  <?= $client['servicesAppliedQty'] * round($client['usersServicesPaymentsSumm'] ?? $client['minWage'] ?? 0); ?>
								</td>
							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>
				  <?
				}//dops
				if (($_GET['type'] ?? false) == 42 && ($_GET['date'] ?? false) == $date) {//приходы медиа
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">
	 				  <table class="mytable" style="width: 100%; background-color: #FFF;">
						  <?
						  foreach (($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []) as $client) {
							 ?>
							 <tr>
								<td>
								  <a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $date; ?>">
									 <?= $client['clientsLName']; ?>
									 <?= $client['clientsFName']; ?>
									 <?= $client['clientsMName']; ?>
								  </a>
								</td>
							 </tr>
							 <?
						  }
						  ?>
	 				  </table>
	 				</td>
	 			 </tr>
				  <?
				}
				if (in_array(($_GET['type'] ?? false), [1, 41, 47]) && ($_GET['date'] ?? false) == $date) {//Выходы
				  ?>
	 			 <tr>
	 				<td style="padding: 2px; background-color: #DDD;" colspan="<?= 1 + count(($payments['types'] ?? [])); ?>">

						<?
						$usersScheduleHalfs = [
							 null => 'Не установлена',
							 '11' => 'Полная смена',
							 '10' => 'Утренняя смена',
							 '01' => 'Вечерняя смена',
							 'SD' => 'Больничный',
							 'NA' => 'Недоступен',
							 'V' => 'Выходной'];

//									printr(($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []));
						$data = ($payments['dates'][$_GET['date']][$_GET['type']]['data'] ?? []);

						$earlyleave = (($data['fingerTo'] ?? false) ?? ($data['scheduleTo'] ?? false)) ? (

								  round((strtotime($data['scheduleTo']) - strtotime($data['fingerTo'])) / (60)) > 0 ? (
								  'на ' . round((strtotime($data['scheduleTo']) - strtotime($data['fingerTo'])) / (60)) . ' мин. раньше'
								  ) : false
								  ) : false;

						$dalay = (($data['fingerFrom'] ?? false) ?? ($data['scheduleFrom'] ?? false)) ? (
								  round((strtotime($data['fingerFrom']) - strtotime($data['scheduleFrom'])) / (60)) > 0 ? round((strtotime($data['fingerFrom']) - strtotime($data['scheduleFrom'])) / (60)) . ' мин.' : false
								  ) : false;
						?>
	 				  <table class="mytable" style="width: 100%; background-color: #FFF;">
	 					 <tr>
	 						<td>Смена:</td>
	 						<td class="C"><?= $usersScheduleHalfs[($data['scheduleHalfs'] ?? null)]; ?></td>
	 					 </tr>
	 					 <tr>
	 						<td>Приход:</td>
	 						<td class="C"><?= ($data['fingerFrom'] ?? false) ? date("H:i", strtotime($data['fingerFrom'])) : 'Не зафиксирован'; ?></td>
	 					 </tr>

	 					 <tr>
	 						<td>Начало смены:</td>
	 						<td class="C"><?= ($data['scheduleFrom'] ?? false) ? date("H:i", strtotime($data['scheduleFrom'])) : 'Не установлена'; ?></td>
	 					 </tr>
						  <? if ($dalay) { ?>
							 <tr>
								<td style=" background-color: pink;">Опоздание:</td>
								<td class="C" style=" background-color: pink;"><?= $dalay; ?></td>
							 </tr>
						  <? } ?>

	 					 <tr>
	 						<td>Окончание смены:</td>
	 						<td class="C"><?= ($data['scheduleTo'] ?? false) ? date("H:i", strtotime($data['scheduleTo'])) : 'Не установлена'; ?></td>
	 					 </tr>
	 					 <tr>
	 						<td>Уход:</td>
	 						<td class="C"><?= ($data['fingerTo'] ?? false) ? date("H:i", strtotime($data['fingerTo'])) : 'Не зафиксирован'; ?></td>
	 					 </tr>
						  <? if ($earlyleave) { ?>
							 <tr>
								<td style=" background-color: pink;">Ранний уход:</td>
								<td class="C" style=" background-color: pink;"><?= $earlyleave;
							 ?></td>
							 </tr>
						  <? } ?>
						  <?
						  if (($payments['dates'][$_GET['date']]['47']['reward'] ?? false)) {
							 ?>
							 <tr>
								<td style=" background-color: lightgoldenrodyellow;">Сверхурочно:</td>
								<td class="C" style=" background-color: lightgoldenrodyellow;"><?= secondsToTimeShort($payments['dates'][$_GET['date']]['47']['data']['overtimeTimeSeconds'] ?? 0);
							 ?></td>
							 </tr>
						  <? } ?>


	 				  </table>
	 				</td>
					 <? if (!count(($payments['types'] ?? []))) {
						?><th class="C"> - </th><? }
					 ?>
	 			 </tr>
				  <?
				}
				?>

				<?
			 }
			 ?>
			 <tr style="font-size: 0.7em; line-height: 1em;">
				<td></td>
				<? foreach (($payments['types'] ?? []) as $type) { ?>
  				<th class="C"><?= $type['titleShort'] ?? 'Название не установлено'; ?></th>
				<? } ?>
				<? if (!count(($payments['types'] ?? []))) {
				  ?>
  				<th class="C"> - </th>
				<? }
				?>
			 </tr>
			 <tr style="color: gray;">
				<th class="R">16-<?= date("t", strtotime($to)); ?>:</th>
				<?
				$presumm = 0;
				foreach (($payments['types'] ?? []) as $idtype => $type) {
				  $paymentsSumm = paymentsSumm($payments, $idtype, 2);
				  $presumm += $paymentsSumm;
				  ?>
  				<th class="C"><?= $paymentsSumm; ?></th>
				<? } ?>
				<? if (!count(($payments['types'] ?? []))) {
				  ?>
  				<th class="C"> - </th>
				<? }
				?>
			 </tr>
			 <tr style="color: gray;">
				<td colspan="<?= count($payments['types'] ?? []); ?>">По всем начислениям c 16 по <?= date("t", strtotime($to)); ?></td>
				<th class="C"><?= $presumm; ?></th>
			 </tr> 
			 <?
			 $usersPayments = query2array(mysqlQuery("SELECT * FROM `usersPayments`"
								  . " LEFT JOIN `cashFlowTypes` ON (`idcashFlowType` = `usersPaymentsCFT`)"
								  . " WHERE "
								  . " `usersPaymentsUser` = '$user' AND "
								  . " `usersPaymentsFrom` = '" . date("Y-m-16", strtotime($date)) . "' AND "
								  . " `usersPaymentsTo` =  '$to'"
								  . " AND isnull(`usersPaymentsDeletedTime`)"
								  . ""));
			 $usersPaymentsTotal2 = array_sum(array_column($usersPayments, 'usersPaymentsAmount'));
			 ?>	
			 <? if (R(194) && count($payments['types'] ?? [])) {
				?>



  			 <tr>
  				<td colspan="<?= count($payments['types'] ?? []) + 1; ?>">
  				  <form action="<?= GR(); ?>" method="POST">
  					 <input autocomplete="off" name="usersPaymentsUser" type="hidden" value="<?= $user; ?>">
  					 <input autocomplete="off" name="usersPaymentsFrom" type="hidden" value="<?= date("Y-m-16", strtotime($date)); ?>">
  					 <input autocomplete="off" name="usersPaymentsTo" type="hidden" value="<?= $to; ?>">
  					 <table>
  						<tr>
  						  <td colspan="3" class="C">Выплаты</td>
  						</tr>

						  <?
						  foreach (($usersPayments ?? []) as $usersPayment) {
							 ?>
	 						<tr>
	 						  <td><?= $usersPayment['cashFlowTypeName'] ?? '-'; ?></td>
	 						  <td><?= $usersPayment['usersPaymentsAmount'] ?? ''; ?></td>
	 						  <td><?= ($usersPayment['usersPaymentType'] ?? false) ? $UPTs[$usersPayment['usersPaymentType']] : '--'; ?></td>
	 						  <td><button type="button" onclick="GR({deletePayment:<?= $usersPayment['idusersPayments']; ?>})"><i class="fas fa-times-circle" style="color: #990000;"></i></button></td>
	 						</tr> 
							 <?
						  }
						  ?>
  						<tr>

  						  <td><select name="cashFlowType" required><option value="">ДДС</option><?
								  foreach (query2array(mysqlQuery("SELECT * FROM `cashFlowTypes`")) as $type) {
									 ?><option value="<?= $type['idcashFlowType'] ?>"><?= $type['cashFlowTypeName'] ?></option><? } ?></select></td>
  						  <td><input autocomplete="off" name="usersPaymentsAmount" type="text" style="width: 100px; float: right;" value=""></td>
  						  <td>
  							 <select name="upt">
  								<option value="1"><?= $UPTs['1']; ?></option>
  								<option value="2"><?= $UPTs['2']; ?></option>
  							 </select>
  						  </td>
  						  <td colspan="3" class="C">
  							 <button autocomplete="off" type="submit"><i class="fas fa-plus-circle" style="color: #009900;"></i></button>
  						  </td>
  						</tr>
  					 </table>




  				  </form>
  				</td>
  			 </tr>
				<?
			 }
			 if (!R(194) && count($payments['types'] ?? [])) {
				foreach ($usersPayments as $usersPayment) {
				  ?>
	 			 <tr>
	 				<td style=" padding: 5px;" colspan="<?= count($payments['types']); ?>">Выплаты <?= $UPTs[$usersPayment['usersPaymentType']]; ?> <?= date("d.m в H:i", strtotime($usersPayment['usersPaymentsTime'])); ?> </td>
	 				<td class="C"><?= round($usersPayment['usersPaymentsAmount'], 2); ?></td>
	 			 </tr>

				  <?
				}
			 }
			 ?>
			 <tr>
				<th class="R">Итого:</th>
				<?
				$presumm = 0;
				if (!count(($payments['types'] ?? []))) {
				  ?>
  				<th class="C"> - </th>
				  <?
				}
				foreach (($payments['types'] ?? []) as $idtype => $type) {

				  $psumm = paymentsSumm($payments, $idtype);
				  $psummTotal = ($psummTotal ?? 0) + $psumm;
				  ?>
  				<th class="C"><?= $psumm; ?></th>
				<? } ?>
			 </tr>





			 <tr>
				<th colspan="<?= 1 + max(1, count(($payments['types'] ?? []))); ?>">
				  <br>
				</th>

			 </tr>
			 <tr>
				<th class="R" colspan="<?= count(($payments['types'] ?? [])); ?>">Начислено: </th>
				<th class="C"><?= $psummTotal ?? '-'; ?></th>
			 </tr>
			 <tr>
				<th class="R" colspan="<?= count(($payments['types'] ?? [])); ?>">Выплат: </th>
				<th class="C"><?= ($usersPaymentsTotal1 ?? 0) + ($usersPaymentsTotal2 ?? 0); ?></th>
			 </tr>
			 <tr>
				<th class="R" colspan="<?= count(($payments['types'] ?? [])); ?>">К выплате: </th>
				<th class="C"><?= ($psummTotal ?? 0) - (($usersPaymentsTotal1 ?? 0) + ($usersPaymentsTotal2 ?? 0)); ?></th>
			 </tr>

		  </table>





		  <?
		  if (isset($_GET['showlog'])) {
			 printr(json_encode($payments, 288 + 128), 1);
		  }
		  ?>

		</div>
	 </div>
  </div>
</div>
<?
//printr(($_QUERIES ?? 'NOOOOO!'), 1);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
if ($_USER['id'] == 176) {
  ?><div style="background-color: white; color: black; border-top: 1px solid black;"><?= $PGT; ?></div><?
}
