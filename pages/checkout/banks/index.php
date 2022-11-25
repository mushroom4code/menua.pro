<?php
$load['title'] = $pageTitle = 'Отчёты';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$from = $_GET['from'] ?? date("Y-m-01");
$to = $_GET['to'] ?? date("Y-m-d");
$dates = [$from, $to];
//	printr($f_sales_II_Summ);
//	die();
$credits = query2array(mysqlQuery("SELECT *"
				. " FROM `f_credits`"
				. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
				. " LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`)"
				. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
				. " WHERE `f_salesDate`>='" . min($dates) . "' "
				. " AND"
				. " `f_salesDate`<='" . max($dates) . "' "
				. " ORDER BY `RS_banksName`,`f_salesDate`,`clientsLName`,`clientsFName`,`clientsMName`"
		));
?>
<head>
	<style>
		.dates {
			display: none;
			position: fixed;
		}
		body:hover .dates {
			display: block;
		}
		body {
			min-height: 100vh;
		}
		td,th {
			padding: 2px 8px;
		}

		tr {
			cursor: pointer;
			page-break-inside:avoid;
			page-break-after:auto
		}
		table {
			page-break-inside:auto;
			font-size: 11pt;

		}


		.pagebrake {
			background-color: #EEE;
			page-break-after: always;
		}
	</style>
	<script src="/sync/js/basicFunctions.js" type="text/javascript"></script>

</head>
<body>
	<div class="dates"> 
		<div style="display: grid; grid-template-columns: auto auto auto; margin: 20px; grid-gap: 10px;">
			<input type="date" id="from" value="<?= $from ?>">
			<input type="date" id="to" value="<?= $to ?>">
			<input type="button" value="ok" onclick="GR({from: document.querySelector(`#from`).value, to: document.querySelector(`#to`).value});">
		</div>
	</div>

	<?
	$currentBank = null;
	$currentBankName = null;
	foreach ($credits as $credit) {
		if ($currentBank !== $credit['idRS_banks']) {
			$n = 0;
			if ($currentBank) {

				if ($currentBank == 8) {//keb
					?>
				</table>
				<p>Сотрудник сдал в банк  (дата, ФИО, подпись) __________________________</p>
				<p>Сотрудник УМК и УД/ОМО и УД принял (дата, ФИО, подпись) __________________________</p>

				<?
			} else {
				?>
				<tr>
					<td colspan="7">Настоящий Акт составлен в двух экземплярах по одному для каждой из Сторон.</td>
				</tr>
				<tr>
					<td colspan="7">Дата фактического получения <?= $currentBankName; ?> оригиналов документов КЛИЕНТА:</td>
				</tr>
				<tr>
					<td colspan="7"><?= date('d'); ?>
						<?= ($_MONTHES['full']['gen'][date('n')]); ?>
						<?= date('Y'); ?> г.
					</td>
				</tr>
				<tr>
					<td colspan="3">От БАНКА:	</td>
					<td colspan="4">От Предприятия:				</td>
				</tr>

				<tr>
					<td colspan="2" style="border-bottom: 1px solid black;"></td>
					<td></td>
					<td colspan="4" style="border-bottom: 1px solid black;">специалист</td>
				</tr>
				<tr>
					<td colspan="3"><small><sup>должность</sup></small></td>
					<td colspan="4"><small><sup>должность</sup></small></td>
				</tr>
				<tr>
					<td colspan="7"><br></td>
				</tr>
				<tr>
					<td colspan="3">__________(_____________________)		</td>
					<td colspan="4">__________(_____________________)		</td>
				</tr>
				<tr>
					<td colspan="3"><small><sup>подпись</sup></small>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <small><sup>ФИО</sup></small></td>
					<td colspan="4"><small><sup>подпись</sup></small>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <small><sup>ФИО</sup></small></td>

				</tr>

				<tr>
					<td colspan="3">МП</td>
					<td colspan="4">МП</td>
				</tr>
				</table>

				<?
			}


			print '<hr class="pagebrake">';
		}
		$currentBank = $credit['idRS_banks'];
		$currentBankName = $credit['RS_banksName'];
		if ($credit['idRS_banks'] == 8) {//keb
			?>
			<img src="image_2022-05-10_18-47-13.png" alt=""/>

			<h3>Акт приема–передачи документов из ООО «Инфинити»</h3>
			<h3>
				<?= date('d'); ?>
				<?= ($_MONTHES['full']['gen'][date('n')]); ?>
				<?= date('Y'); ?> г.
			</h3>
			<table border='1' style=" border-collapse: collapse;">
				<tr>
					<th>№ п/п</th>
					<th>ФИО клиента</th>
					<th>Номер кредитного договора</th>
					<th>Дата выдачи<br> кредита</th>
					<th>Количество<br> листов<br> в досье</th>
				</tr>
				<?
			} else {
				?>
				<table style=" border-collapse: collapse;">

					<tr><th colspan="7">ФОРМА</th></tr>						
					<tr><th colspan="7">Акт приема-передачи документов КЛИЕНТОВ №						</th></tr>
					<tr><th colspan="7"><br></th></tr>

					<tr>
						<th colspan="4" style="text-align: left;">г. Санкт-Петербург</th>
						<th colspan="3" style="text-align: right;">	<?= date('d'); ?>
							<?= ($_MONTHES['full']['gen'][date('n')]); ?>
							<?= date('Y'); ?> г.</th>
					</tr>		
					<tr><th colspan="7">Настоящий акт приема-передачи составлен в том, что</th></tr>	
					<tr><th colspan="7" style="text-align: left; border-bottom: 1px solid black;"><br></th></tr>	
					<tr><th colspan="7"><small><sup>ФИО сотрудника, передающего документы</sup></small><br></th></tr>	



					<tr><th colspan="7" style="border-bottom: 1px solid black;">СПЕЦИАЛИСТ</th></tr>					
					<tr><th colspan="7"><small><sup>Наименование должности сотрудника Предприятия, передающего документы</sup></small><br></th></tr>					
					<tr><th colspan="7" style="text-align: left; border-bottom: 1px solid black;">передал, а</th></tr>   						
					<tr><th colspan="7"><small><sup>ФИО сотрудника, принявшего документы</sup></small><br></th></tr>						
					<tr><th colspan="7" style="text-align: left; border-bottom: 1px solid black;"><br></th></tr>   						
					<tr><th colspan="7"><small><sup>Наименование должности сотрудника БАНКА, принявшего документы</sup></small><br></th></tr>					




					<tr style="border-left: 1px solid black; border-top: 1px solid black; font-size: 0.7em;">
						<td style="border-right: 1px solid black; border-bottom: 1px solid black;">№ д/д</td>
						<td style="border-right: 1px solid black; border-bottom: 1px solid black;">№ кредитного<br> договора</td>
						<td style="border-right: 1px solid black; border-bottom: 1px solid black;">ФИО клиента</td>
						<th style="border-right: 1px solid black; border-bottom: 1px solid black;" class="vertical"><div class="vertical">Заявление</div></th>
						<th style="border-right: 1px solid black; border-bottom: 1px solid black;" class="vertical"><div class="vertical">Копия<br>паспорта</div></th>
						<td style="border-right: 1px solid black; border-bottom: 1px solid black;">Другие<br>документы</td>
						<td style="border-right: 1px solid black; border-bottom: 1px solid black;">Примечание</td>
					</tr>
					<?
				}
			}
			if ($credit['idRS_banks'] == 8) {
				?>
				<tr>
					<td><?= ++$n; ?></td>
					<td>
						<?= $credit['clientsLName']; ?>
						<?= $credit['clientsFName']; ?>
						<?= $credit['clientsMName']; ?>
					</td>
					<td>
						<?= $credit['f_creditsBankAgreementNumber']; ?>
					</td>
					<td>
						<?= date('d.m.Y', strtotime($credit['f_salesDate'])); ?>



					</td>
					<td></td>
				</tr>
				<?
			} else {
				?>
				<tr style="border-left: 1px solid black; border-top: 1px solid black;">
					<td style="border-right: 1px solid black; border-bottom: 1px solid black;"><?= ++$n; ?></td>

					<td style="border-right: 1px solid black; border-bottom: 1px solid black;">
						<?= $credit['f_creditsBankAgreementNumber']; ?>
					</td>
					<td style="border-right: 1px solid black; border-bottom: 1px solid black;">
						<?= $credit['clientsLName']; ?>
						<?= $credit['clientsFName']; ?>
						<?= $credit['clientsMName']; ?>
					</td>
					<td class="C" style="border-right: 1px solid black; border-bottom: 1px solid black;">+</td>
					<td class="C" style="border-right: 1px solid black; border-bottom: 1px solid black;">+</td>
					<td style="border-right: 1px solid black; border-bottom: 1px solid black;"></td>
					<td style="border-right: 1px solid black; border-bottom: 1px solid black;"></td>
				</tr>
				<?
			}
		}

		if ($currentBank == 8) {//keb
			?>
		</table>
		<p>Сотрудник сдал в банк  (дата, ФИО, подпись) __________________________</p>
		<p>Сотрудник УМК и УД/ОМО и УД принял (дата, ФИО, подпись) __________________________</p>

		<?
	} else {
		?>
		<tr>
			<td colspan="7">Настоящий Акт составлен в двух экземплярах по одному для каждой из Сторон.</td>
		</tr>
		<tr>
			<td colspan="7">Дата фактического получения <?= $credit['RS_banksName']; ?> оригиналов документов КЛИЕНТА:</td>
		</tr>
		<tr>
			<td colspan="7"><?= date('d'); ?>
				<?= ($_MONTHES['full']['gen'][date('n')]); ?>
				<?= date('Y'); ?> г.
			</td>
		</tr>
		<tr>
			<td colspan="3">От БАНКА:	</td>
			<td colspan="4">От Предприятия:				</td>
		</tr>

		<tr>
			<td colspan="2" style="border-bottom: 1px solid black;"></td>
			<td></td>
			<td colspan="4" style="border-bottom: 1px solid black;">специалист</td>
		</tr>
		<tr>
			<td colspan="3"><small><sup>должность</sup></small></td>
			<td colspan="4"><small><sup>должность</sup></small></td>
		</tr>
		<tr>
			<td colspan="7"><br></td>
		</tr>
		<tr>
			<td colspan="3">__________(_____________________)		</td>
			<td colspan="4">__________(_____________________)		</td>
		</tr>
		<tr>
			<td colspan="3"><small><sup>подпись</sup></small>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <small><sup>ФИО</sup></small></td>
			<td colspan="4"><small><sup>подпись</sup></small>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <small><sup>ФИО</sup></small></td>

		</tr>

		<tr>
			<td colspan="3">МП</td>
			<td colspan="4">МП</td>
		</tr>
	</table>

	<?
}
?>



<? //printr($credits, 1);    ?>
</body>

<script>
	document.querySelectorAll('tr').forEach(row => {
		row.addEventListener('click', () => {
			row.classList.toggle('pagebrake');
		});
	});
</script>


