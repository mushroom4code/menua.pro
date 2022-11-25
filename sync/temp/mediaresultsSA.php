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

	$clientsAddedMarch = query2array(mysqlQuery("SELECT * FROM clients left join users on (idusers = clientsAddedBy) where usersGroup = 13 AND clientsAddedAt between '2021-03-01 00:00:00' AND '2021-04-01 00:00:00';"));
	$clientsAddedApril = query2array(mysqlQuery("SELECT * FROM clients left join users on (idusers = clientsAddedBy) where usersGroup = 13 AND clientsAddedAt between '2021-04-01 00:00:00' AND '2021-05-01 00:00:00';"));
	$clientsAddedMay = query2array(mysqlQuery("SELECT * FROM clients left join users on (idusers = clientsAddedBy) where usersGroup = 13 AND clientsAddedAt between '2021-05-01 00:00:00' AND '2021-06-01 00:00:00';"));
//	printr($clientsAddedMarch);
//	printr($clientsAddedApril);
//	printr($clientsAddedMay);
	?>

	<div class="box neutral">
		<div class="box-body">
			<h3>Март</h3>
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6,auto);">
				<div style="display: contents;">
					<div>#</div>
					<div class="B">Клиент</div>
					<div>Добавлен</div>
					<div>Добавлил</div>
					<div>Абонементы</div>
					<div>Сумма продаж</div>
				</div>

				<?
				$n = 0;
				$march_total = 0;
				foreach ($clientsAddedMarch as &$client2) {
					$client2['f_sales'] = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesClient` = '" . $client2['idclients'] . "' AND isnull(`f_salesCancellationDate`)"));

					$n++;
					?>
					<div style="display: contents;">
						<div><?= $n; ?></div>
						<div><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client2['idclients']; ?>"><?= $client2['clientsLName']; ?> <?= $client2['clientsFName']; ?> <?= $client2['clientsMName']; ?></a></div>
						<div><?= date("d.m.Y", strtotime($client2['clientsAddedAt'])); ?></div>
						<div><?= $client2['usersLastName']; ?> <?= $client2['usersFirstName']; ?></div>
						<div><?
							foreach ($client2['f_sales'] as $f_sale) {
								?>
								<?= date('d.m.Y', strtotime($f_sale['f_salesDate'])); ?> (<?= $f_sale['f_salesSumm'] ?>р.)<br>
								<?
							}
							?></div>
						<?
						$clientsum = array_sum(array_column($client2['f_sales'], 'f_salesSumm'));
						$march_total += $clientsum;
						?>
						<div class="R"><?= $clientsum; ?></div>
					</div>

				<? }
				?>
				<div style="display: contents;">
					<div></div>
					<div class="B"></div>
					<div></div>
					<div></div>
					<div></div>
					<div class="R"><?= $march_total; ?></div>
				</div>
			</div>


			<h3>Апрель</h3>
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6,auto);">
				<div style="display: contents;">
					<div>#</div>
					<div class="B">Клиент</div>
					<div>Добавлен</div>
					<div>Добавлил</div>
					<div>Абонементы</div>
					<div>Сумма продаж</div>
				</div>

				<?
				$n = 0;
				$march_total = 0;
				foreach ($clientsAddedApril as &$client2) {
					$client2['f_sales'] = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesClient` = '" . $client2['idclients'] . "' AND isnull(`f_salesCancellationDate`)"));

					$n++;
					?>
					<div style="display: contents;">
						<div><?= $n; ?></div>
						<div><?= $client2['clientsLName']; ?> <?= $client2['clientsFName']; ?> <?= $client2['clientsMName']; ?></div>
						<div><?= date("d.m.Y", strtotime($client2['clientsAddedAt'])); ?></div>
						<div><?= $client2['usersLastName']; ?> <?= $client2['usersFirstName']; ?></div>
						<div><?
							foreach ($client2['f_sales'] as $f_sale) {
								?>
								<?= date('d.m.Y', strtotime($f_sale['f_salesDate'])); ?> (<?= $f_sale['f_salesSumm'] ?>р.)<br>
								<?
							}
							?></div>
						<?
						$clientsum = array_sum(array_column($client2['f_sales'], 'f_salesSumm'));
						$march_total += $clientsum;
						?>
						<div class="R"><?= $clientsum; ?></div>
					</div>

				<? }
				?>
				<div style="display: contents;">
					<div></div>
					<div class="B"></div>
					<div></div>
					<div></div>
					<div></div>
					<div class="R"><?= $march_total; ?></div>
				</div>
			</div>


			<h3>Май</h3>
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6,auto);">
				<div style="display: contents;">
					<div>#</div>
					<div class="B">Клиент</div>
					<div>Добавлен</div>
					<div>Добавлил</div>
					<div>Абонементы</div>
					<div>Сумма продаж</div>
				</div>

				<?
				$n = 0;
				$march_total = 0;
				foreach ($clientsAddedMay as &$client2) {
					$client2['f_sales'] = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesClient` = '" . $client2['idclients'] . "' AND isnull(`f_salesCancellationDate`)"));

					$n++;
					?>
					<div style="display: contents;">
						<div><?= $n; ?></div>
						<div><?= $client2['clientsLName']; ?> <?= $client2['clientsFName']; ?> <?= $client2['clientsMName']; ?></div>
						<div><?= date("d.m.Y", strtotime($client2['clientsAddedAt'])); ?></div>
						<div><?= $client2['usersLastName']; ?> <?= $client2['usersFirstName']; ?></div>
						<div><?
							foreach ($client2['f_sales'] as $f_sale) {
								?>
								<?= date('d.m.Y', strtotime($f_sale['f_salesDate'])); ?> (<?= $f_sale['f_salesSumm'] ?>р.)<br>
								<?
							}
							?></div>
						<?
						$clientsum = array_sum(array_column($client2['f_sales'], 'f_salesSumm'));
						$march_total += $clientsum;
						?>
						<div class="R"><?= $clientsum; ?></div>
					</div>

				<? }
				?>
				<div style="display: contents;">
					<div></div>
					<div class="B"></div>
					<div></div>
					<div></div>
					<div></div>
					<div class="R"><?= $march_total; ?></div>
				</div>
			</div>





		</div><!-- comment -->
	</div><!-- comment -->
	<?
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
