<?php
$pageTitle = 'Медиа';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (($_POST['phone'] ?? '') != '') {
	if (count($_POST['phone'])) {
		foreach ($_POST['phone'] as $client => $phone) {
			if ($phone) {
				mysqlQuery("INSERT INTO `clientsPhones` SET"
						. " `clientsPhonesPhone` = '" . $phone . "',"
						. "`clientsPhonesClient` = '" . $client . "'");
			}
		}
	}

	header("Location: " . GR());
	die();
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (0) {
	?>E403R27<?
} else {
	$toprint = [];

	$toprint[] = query2array(mysqlQuery("SELECT * FROM clients left join users on (idusers = clientsAddedBy) where usersGroup = 13 AND clientsAddedAt between '2021-03-01 00:00:00' AND '2021-04-01 00:00:00';"));
	$toprint[] = query2array(mysqlQuery("SELECT * FROM clients left join users on (idusers = clientsAddedBy) where usersGroup = 13 AND clientsAddedAt between '2021-04-01 00:00:00' AND '2021-05-01 00:00:00';"));
	$toprint[] = query2array(mysqlQuery("SELECT * FROM clients left join users on (idusers = clientsAddedBy) where usersGroup = 13 AND clientsAddedAt between '2021-05-01 00:00:00' AND '2021-06-01 00:00:00';"));
//	printr($clientsAddedMarch);
//	printr($clientsAddedApril);
//	printr($clientsAddedMay);
	?>

	<div class="box neutral">
		<div class="box-body">
			<? foreach ($toprint as $month) {
				?>
				<h3>Месяц</h3>
				<table border="1">
					<tr>
						<td>#</td>
						<td class="B">Клиент</td>
						<td>Добавлен</td>
						<td>Добавлил</td>
						<td>Абонементы</td>
						<td>Сумма продаж</td>
						<td>Разовые процедуры</td>
						<td>Направления абонементов</td>
					</tr>

					<?
					$n = 0;
					$march_total = 0;
					foreach ($month as &$client2) {
						$client2['f_sales'] = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesClient` = '" . $client2['idclients'] . "' AND isnull(`f_salesCancellationDate`)"));
						foreach ($client2['f_sales'] as &$f_sale2) {
							$f_sale2['f_subscriptions'] = query2array(mysqlQuery("SELECT * "
											. "FROM `f_subscriptions` "
											. "LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
											. "LEFT JOIN `servicesTypes` ON (`idservicesTypes`=`servicesType`)"
											. " WHERE `f_subscriptionsContract` = '" . $f_sale2['idf_sales'] . "'"));
						}
						$n++;
						?>
						<tr>
							<td><?= $n; ?></td>
							<td><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client2['idclients']; ?>"><?= $client2['clientsLName']; ?> <?= $client2['clientsFName']; ?> <?= $client2['clientsMName']; ?></a></td>
							<td><?= date("d.m.Y", strtotime($client2['clientsAddedAt'])); ?></td>
							<td><?= $client2['usersLastName']; ?> <?= $client2['usersFirstName']; ?></td>
							<td><?
								foreach ($client2['f_sales'] as $f_sale) {
									?>
									<?= date('d.m.Y', strtotime($f_sale['f_salesDate'])); ?> (<?= $f_sale['f_salesSumm'] ?>р.)<br>
									<?
								}
								?></td>
							<?
							$clientsum = array_sum(array_column($client2['f_sales'], 'f_salesSumm'));
							$march_total += $clientsum;
							?>
							<td class="R"><?= $clientsum; ?></td>
							<td>
								<?
								foreach ($client2['f_sales'] as $f_sale) {
									if ($f_sale['f_salesSumm'] < 25000) {
										?>
										<?= implode('<br>', array_unique(array_column($f_sale['f_subscriptions'], 'servicesName'))); ?><br>
										<?
									}
								}
								?>

							</td>
							<td>
								<?
								foreach ($client2['f_sales'] as $f_sale) {
									if ($f_sale['f_salesSumm'] >= 25000) {
										?>
										<?= implode('<br>', array_filter(array_unique(array_column($f_sale['f_subscriptions'], 'servicesTypesName')))); ?><br>
										<?
									}
								}
								?>
							</td>
						</tr>

					<? }
					?>
					<tr>
						<td></td>
						<td class="B"></td>
						<td></td>
						<td></td>
						<td></td>
						<td class="R"><?= $march_total; ?></td>
						<td></td>
						<td></td>
					</tr>
				</table>

				<?
			}
			?>





		</div><!-- comment -->
	</div><!-- comment -->
	<?
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
