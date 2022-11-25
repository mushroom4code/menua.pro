<?php
$pageTitle = 'Оприходовать';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

if (!R(7)) {
	?>E403R07<?
} else {
	?>
	<script src="/pages/warehouse/goods/loadUnits.js" type="text/javascript"></script>
	<script src="/pages/warehouse/goods/addItemsWindow.js" type="text/javascript"></script>
	<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
	<div class="box neutral">
		<div class="box-body">
			<table style="color: black;">
				<tr>
					<td>Дата:</td>
					<td><input type="date" id="date" autocomplete="off" value="<?= date("Y-m-d"); ?>" onchange="if (this.value == '') {
								this.valueAsDate = new Date();
							}
							loadReportForDate(this.value);
							this.blur();"></td>
				</tr>
				<tr>
					<td>Поставщик:</td>
					<td>
						<select id="idsuppliers">
							<option value=""><?= rt(['Надо бы выбрать', 'Не важно', 'Мне всё равно', 'Без разницы', 'Не знаю', 'Не помню', 'Какой-то там']) ?></option>
							<?
							foreach (query2array(mysqlQuery("SELECT * FROM `suppliers` ORDER BY `suppliersName`")) as $supplier) {
								?>
								<option value="<?= $supplier['idsuppliers']; ?>"><?= $supplier['suppliersName']; ?></option>
								<?
							}
							?>
						</select>
					</td>
				</tr>

				<tr>
					<td>Наименование:</td>
					<td><input type="text" id="INitemName" autocomplete="off" readonly></td>
				</tr>
				<tr>
					<td>Штрих-код:</td>
					<td style="white-space: nowrap;"><input type="text" id="itemBarcode" autocomplete="off" onkeypress="bcinput();
							if (event.keyCode == 13) {
								makeRequest(this);
							}">
					</td>
				</tr>
				<tr>
					<td>Единицы измерения:</td>
					<td><input type="text" id="itemUnit" readonly autocomplete="off"></td>
				</tr>
				<tr>
					<td>Количество:</td>
					<td><input  lang="en" type="number" style="-moz-appearance: textfield;" step="0.01"  id="qty" autocomplete="off" onblur="this.value = parseFloat(this.value.replace(',', '.')) || 0;" onkeypress="if (event.keyCode == 13) {
								sendData();
								this.blur();
							}"></td>
				</tr>
				<tr>
					<td></td>
					<td style="vertical-align: middle;"><input type="checkbox" onclick="loadReportForDate(qs('#date').value);" id="stocktaking" autocomplete="off"><label for="stocktaking" style="vertical-align: middle; display: inline-block;">Инвентаризация</label></td>
				</tr>

				<tr>
					<td></td>
					<td style="text-align: right;"><button id="sbmbtn" onclick="clearForm();this.blur();"> Очистить </button> <button id="sbmbtn" onclick="sendData();this.blur();"> Добавить </button></td>
				</tr>
			</table>
		</div>

	</div>
	<br>
	<div class="box neutral">
		<div class="box-body" id="dayReport"></div>
	</div>
	<script>
		let currentItem = {};
		let dataTOSend = {
			date: '',
			id: '',
			qty: ''
		};
	</script>

	<?
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
