<?php
$pageTitle = 'Зарплата';
mb_internal_encoding("UTF-8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
include 'functions.php';
if (!1) {
	?>E403R27<?
} else {





	$date = $_GET['date'] ?? date("Y-m");
	$from = date2monthFromTo($date)['from'];
	$to = date2monthFromTo($date)['to'];
	$f_roles = query2array(mysqlQuery("SELECT * FROM `f_roles`"));
	$users = query2array(mysqlQuery("SELECT *,(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions` FROM `users`"
					. " WHERE isnull(`usersDeleted`) ORDER BY `usersLastName`"));

	if (($_GET['user'] ?? false) && $_user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . $_GET['user'] . "'"))) {


		$userSales = getPayments(getUserSales($_user['idusers'], $from, $to));
		$userReturns = array_filter($userSales, function ($sale) {
			return $sale['f_salesCancellationDate'];
		});
//				getPayments(query2array(mysqlQuery("SELECT * FROM `f_salesRoles`"
//								. " LEFT JOIN `f_roles` ON (`idf_roles` = `f_salesRolesRole`)"
//								. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_salesRolesSale`) WHERE `f_salesRolesUser` = '" . $_user['idusers'] . "' AND `f_salesCancellationDate`>='" . mres($from) . "' AND `f_salesCancellationDate`<='" . mres($to) . "'")));

		foreach ($f_roles as $f_role) {
			$sales = array_filter($userSales, function ($sale)use ($f_role) {
				return $sale['idf_roles'] == $f_role['idf_roles'];
			});

			if (count($sales)) {
				$salesByRoles[] = [
					'role' => $f_role,
					'sales' => $sales
				];
//				printr([count($sales), $f_role]);
			}
		}

		$rewardsPool = query2array(mysqlQuery("SELECT * FROM `userPaymentsValues` WHERE `userPaymentsValuesDate`<='$to' AND `userPaymentsValuesUser` = '" . $_user['idusers'] . "'"));
		usort($rewardsPool, function ($a, $b) {
//сортируем по пользователю
			if ($a['userPaymentsValuesUser'] <=> $b['userPaymentsValuesUser']) {
				return $a['userPaymentsValuesUser'] <=> $b['userPaymentsValuesUser'];
			}
			//потом по типу правила
			if ($a['userPaymentsValuesType'] <=> $b['userPaymentsValuesType']) {
				return $a['userPaymentsValuesType'] <=> $b['userPaymentsValuesType'];
			}
			//потом по дате
			if ($a['userPaymentsValuesDate'] <=> $b['userPaymentsValuesDate']) {
				return $b['userPaymentsValuesDate'] <=> $a['userPaymentsValuesDate'];
			}
			//и если прям всё совпало - по айди от последнего к первому.
			return $b['iduserPaymentsValues'] <=> $a['iduserPaymentsValues'];
		});
	}
	$LT = query2array(mysqlQuery("SELECT *,`LTtype` AS `type` "
					. " FROM `LT` "
					. " WHERE"
					. " `LTuser` = '" . $_user['idusers'] . "'"
	));
	usort($LT, function ($a, $b) {
		if ($a['LTdate'] <=> $b['LTdate']) {
			return $b['LTdate'] <=> $a['LTdate'];
		}

		if ($a['LTresult'] <=> $b['LTresult']) {
			return floatval($a['LTresult']) <=> floatval($b['LTresult']);
		}
	});

//	printr($LT);
//	printr($rewardsPool);
	?>



	<div style="display: inline-block;"><?= $from; ?><?= $to; ?>
		<div style="padding: 20px  20px 0px  20px;">
			<div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 10px;">
				<div>
					<select onchange="GETreloc('date', this.value);" autocomplete="off">
						<option></option>
						<?
						$n = 0;
						do {
							$time = mktime(0, 0, 0, date("n") - $n, 1, date("Y")); {
								?>
								<option<?= ($_GET['date'] ?? $date ?? false) == date("Y-m", $time) ? ' selected' : ''; ?> value="<?= date("Y-m", $time); ?>"><?= date("m.Y", $time); ?> <?= $_MONTHES['full']['nom'][date("n", $time)]; ?></option>
								<?
								$n++;
							}
						} while (date("Ym", $time) > "202108");
						?>

					</select>
					<!--<input type="date" autocomplete="off" value="<?= $from; ?>" onchange="GETreloc('from', this.value);"></div>-->
				</div>
			</div>
			<div style="padding: 20px  20px 0px  20px;">
				<select onchange="GR({user: this.value});" style="display: inline-block; width: auto;" autocomplete="off">
					<option value=""></option>
					<?
					foreach ($users as $user) {
						?>
						<option <?= ($_GET['user'] ?? '') == $user['idusers'] ? 'selected' : ''; ?> value="<?= $user['idusers']; ?>"><?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?> (<?= $user['positions'] ?? 'Без должности'; ?>)</option>
						<?
					}
					?>
				</select>
			</div>
			<br><!-- comment -->
			<div class="box neutral">
				<div class="box-body">
					<? if ($_user) { ?>
						<!--Сотрудник:--> 	
						<? // printr($_user);                                                                       ?>
						<br>
						Продажи сотрудника (за период) по ролям
						<? printr($salesByRoles ?? []); ?>					
						на сумму 

						<br>
						Возвраты сотрудника (за период)
						<? printr($userReturns); ?>
						распределить возвраты по периодам продажи

						<?
						$returnsByMonth = [];
						foreach ($userReturns as $userReturn) {
//							printr($userReturn);
							$returnsByMonth[date("Y-m", mystrtotime($userReturn['f_salesDate']))][] = $userReturn;
						}
						printr($returnsByMonth);
						?>
						Считаем ПМП. Алгоритм:<br>
						1. Находим все продажи сотрудника за отчётный период. <?= count($salesByRoles ?? []); ?> роли.<?= count($userSales ?? []); ?> продаж <br>
						2. Получаем сумму (стоимостей или оплат) <b>, общая сумма <?= nf(array_sum(array_column($userSales, 'summIncReturns'))); ?> (с учётом возвратов текущего месяца) из них оплачено <?= nf(array_sum(array_column($userSales, 'paymentsTotal')) - array_sum(array_column($userSales, 'f_salesCancellationSumm'))); ?>(с учётом возвратов)</b><br>
						3. Получаем данные Сетки данного сотрудника в соответствии с ролью (ПМП, СПЛ). 
						<?
						foreach ($salesByRoles as $salesByRole) {
//							printr($salesByRole);
							?>
							<br><?= $salesByRole['role']['f_rolesNameShort']; ?>: продаж <?= count($salesByRole['sales']); ?>,  на сумму  <?= nf(array_sum(array_column($salesByRole['sales'], 'f_salesSumm'))); ?>, оплачено  <?= nf(array_sum(array_column($salesByRole['sales'], 'paymentsTotal'))); ?>

						<? } ?>
						<?
						$salesByRole1index = array_search('1', array_column(array_column($salesByRoles, 'role'), 'idf_roles'));
						$salesByRole1 = $salesByRoles[$salesByRole1index];
						$summByRole1 = array_sum(array_column($salesByRole1['sales'], 'f_salesSumm'));
						printr($summByRole1);
						?>
						<br>
						4. Находим сумму вознаграждения.<? //LT($LT, $summByRole1, $to);        ?><br>
						5. найти все возвраты с участием данного сотрудника.<br>
						6. Найти все периоды в которые были куплены абонементы возврат которых осуществлуён в рамах данного месяца.





					<? } ?>


					<br>


				</div>			
			</div>
		</div>
		<?
	}
	?>

	<?
	include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
	