<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
	<head>
		<title>Остатки процедур</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
			* {
				box-sizing: border-box;
			}
			body {
				font: 10pt/10pt Calibri;
				padding: 0cm 0cm;
				border-top: 1px solid black;
				border-bottom: 1px solid black;

			}
			.lightGrid {

				border-top:  1px solid black;
				background-color: white;
			}

			.lightGrid>div:hover div {
				background-color: hsl(180, 80%, 95%);
				;
			}

			.lightGrid>div>div {
				display: flex;
				align-items: center;
				padding: 0px  10px;
				border-bottom:  1px solid black;
			}
			.abonements {
				font: 6pt/6pt Calibri;
			}

		</style>

	</head>
	<body>

		<?php
		include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

		mysqlQuery("UPDATE `clients` SET `clientscQR` = '" . RDS(5) . "', `clientscQRset`=NOW() WHERE `idclients`= '" . mres(($_GET['client'])) . "'");
		$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`= '" . mysqli_real_escape_string($link, ($_GET['client'])) . "'"));

		$totalRemainsFlat = getRemainsByClient($client['idclients']);
		$totalRemainsOUT = [];
		$reserved = query2array(mysqlQuery(""
						. "SELECT SUM(`servicesAppliedQty`) AS `qty`,"
						. "`servicesAppliedService` FROM `servicesApplied` WHERE"
						. " `servicesAppliedClient`='" . mysqli_real_escape_string($link, $client['idclients']) . "'"
						. " AND `servicesAppliedDate`>=CURDATE()"
						. " AND isnull(`servicesAppliedDeleted`)"
						. " AND isnull(`servicesAppliedFineshed`)"
						. " AND NOT isnull(`servicesAppliedContract`)"
						. " GROUP BY `servicesAppliedService`;"));

		foreach ($totalRemainsFlat as $remain) {
			$reservedService = obj2array(array_filter($reserved, function ($el) use ($remain) {
//																printr($remain);
						return $el['servicesAppliedService'] == $remain['f_salesContentService'];
					}));
//															printr($reservedService);
			if (($remain['f_salesContentQty'] ?? 0) > 0 || count($reservedService)) {
				$totalRemainsOUT[$remain['f_salesContentService']]['each'][] = $remain;
				$totalRemainsOUT[$remain['f_salesContentService']]['reserved'] = $reservedService[0]['qty'] ?? null;

				$totalRemainsOUT[$remain['f_salesContentService']]['name'] = $remain['servicesName'];
				$totalRemainsOUT[$remain['f_salesContentService']]['qty'] = ($totalRemainsOUT[$remain['f_salesContentService']]['qty'] ?? 0) + $remain['f_salesContentQty'];
			}
		}
		?>



		<div style="text-align: center">
			<img src="/css/images/infinitiMC.png" style="width: 80%; display: block; margin: 20px auto;">
			<h2 style="font-size: 2em;"><?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?></h2>
			<div style="margin: 20px auto;">
				<?= date("d.m.Y"); ?><br>У вас остались следующие процедуры:
			</div>
			<div style="display: inline-block; text-align: left;">
				<div style="display: grid; grid-template-columns: auto 1fr;" class="lightGrid"><?
					usort($totalRemainsOUT, function ($a, $b) {
						return mb_strtolower($a['name']) <=> mb_strtolower($b['name']);
					});
					foreach ($totalRemainsOUT as $remain) {
						?>
						<div style="display: contents;">
							<div style="flex-flow: row wrap; line-height: 8px; padding: 3px;">
								<div style=" width: 100%;"><?= $remain['name']; ?></div><div><? foreach ($remain['each'] as $service) { ?>
										<span style="" class="abonements"><?= date("d.m.Y", strtotime($service['f_salesDate'])); ?> (<?= $service['f_salesContentQty']; ?>)</span>
									<? }
									?>
								</div>
							</div>

							<div style="font-size: 1.5em; text-align: center;"><?= $remain['qty']; ?><?= $remain['reserved'] ? ('+' . $remain['reserved']) : ''; ?></div>
						</div>
						<?
					}
					?>
				</div>
			</div>
			<?
			if (1) {
				?><br><br>Ваш личный кабинет<br><strong>в Telegram</strong><br>
				<img src="/sync/3rdparty/phpqrcode/client.php?key=<?= $client['clientscQR']; ?>">

				<?
			}
			?>
			<div style="margin: 20px auto; font: 10pt/10pt Calibri;">
				Записаться на процедуры или уточнить информацию можно по телефону
				<h2 style="font-size: 1.5em;">+7 (812) 401-60-33</h2>
				А так же Вы можете посетить наш сайт
				<h4 style="font-size: 1.1em; margin: 6px auto;">www.infiniti-clinic.ru</h4>
			</div>
		</div>
		<script>

			document.addEventListener("DOMContentLoaded", function () {
				print();
				window.addEventListener(
						'afterprint', () => {

					setTimeout(function () {
						window.close();
						console.log('after print event1!');
					}, 110);
					;
				}
				);
			});
		</script>
	</body>
</html>
