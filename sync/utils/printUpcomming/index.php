<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html lang="ru">
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
				padding: 1cm 0cm;
				border-top: 1px solid black;
				border-bottom: 1px solid black;

			}
			.lightGrid {

				border-top:  1px solid black;
				background-color: white;
			}

			.lightGrid>div:hover div {
				background-color: hsl(180, 80%, 95%);;
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

		$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`= '" . mres($_GET['client']) . "'"));

		$reserved = query2array(mysqlQuery(""
						. "SELECT *"
						. " FROM `servicesApplied`"
						. " LEFT JOIN `services` on (`idservices` = `servicesAppliedService`) WHERE"
						. " `servicesAppliedClient`='" . mres($client['idclients']) . "'"
						. " AND `servicesAppliedDate`>=CURDATE()"
						. " AND isnull(`servicesAppliedDeleted`)"
						. " AND isnull(`servicesAppliedFineshed`)"
//						. " AND NOT isnull(`servicesAppliedContract`)"
						. " ;"));
//		printr($reserved);
		?>



		<div style="text-align: center">
			<img src="/css/images/infinitiMC.png" style="width: 80%; display: block; margin: 20px auto;">
			<h2 style="font-size: 2em; line-height: 1em;"><?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?></h2>
			<div style="margin: 20px auto;">
				Вы записаны на следующие процедуры:
			</div>
			<div style="display: inline-block; text-align: left;">
				<div style="display: grid; grid-template-columns: auto auto 1fr;" class="lightGrid"><?
					usort($reserved, function ($a, $b) {
						return mb_strtolower($a['servicesAppliedTimeBegin']) <=> mb_strtolower($b['servicesAppliedTimeBegin']);
					});
					$date = '';
					foreach ($reserved as $remain) {
						if ($date !== $remain['servicesAppliedDate']) {
							$date = $remain['servicesAppliedDate'];
							?>
							<div style="display: contents; font-size: 2em;">
								<div style="grid-column: span 3; height: 1em; justify-content: center; padding: 0.8em;">
									<?= date("d.m.Y", strtotime($remain['servicesAppliedTimeBegin'])); ?>
								</div>
							</div>
						<? } ?>

						<div style="display: contents;">
							<div style=" font-size: 1.2em; font-weight: bold; line-height: 1.2em;"><?= date("H:i", strtotime($remain['servicesAppliedTimeBegin'])); ?></div>
							<div style="flex-flow: row wrap; line-height: 1em; padding: 3px;">
								<div style=" width: 100%; hyphens: auto;"><?= $remain['servicesName']; ?></div>
							</div>
							<div style="font-size: 1.5em; text-align: center;"><?= $remain['servicesAppliedQty']; ?></div>
						</div>
						<?
					}
					?>
				</div>
			</div>
			<div style="margin: 10px auto; font: 10pt/10pt Calibri;">
				Уточнить информацию можно по телефону
				<h2 style="font-size: 1.5em;">+7 (812) 401-60-33</h2>
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
