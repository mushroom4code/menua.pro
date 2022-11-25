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
					<td><input type="date" id="date" autocomplete="off" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>" onchange="GETreloc('date', this.value);"></td>
				</tr>
				<tr>
					<td>Поставщик:</td>
					<td>
						<select id="idsuppliers" style="max-width: 300px;">
							<option value="">Выбрать поставщика</option>
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
					<td><input type="hidden" id="idgoods"><input id="newItemName" type="text" placeholder="Наименование" oninput="searchItemByName(this);qs('#idgoods').value='';nameinput();"><div id="searchResults" style="position: absolute; z-index: 10;"></div></td>
				</tr>
				<tr>
					<td>Штрих-код:</td>
					<td style="white-space: nowrap;"><input type="text" id="newItemBarcode" autocomplete="off" onkeyup="
							if (event.keyCode == 13) {
								makeRequest(this);
							}
							bcinput();">
					</td>
				</tr>
				<tr>
					<td>Номенклатура:<br>ЧТО ЭТО???</td>
					<td><input id="newNomenclatureID" type="hidden"><input id="newNomenclatureName" type="text" placeholder="Наименование" oninput="searchNomenclatureByName(this,qs('#searchNomenclatureResults'));qs('#newNomenclatureID').value=''; qs('#Nunits').value='';qs('#unitsname').innerHTML='';"><div id="searchNomenclatureResults" style="position: absolute; z-index: 10;"></div></td>
				</tr>
				<tr>
					<td>Единицы измерения:</td>
					<td><input type="text" id="Nunits" readonly autocomplete="off"></td>
				</tr>
				<tr>
					<td>Количество<span id="unitsname"></span>:</td>
					<td><input  lang="en" type="text" style="-moz-appearance: textfield;" step="0.01"  id="qty" autocomplete="off" oninput="digon();" onkeypress="if (event.keyCode == 13) {
								//sendData();
								//this.blur();
							}"></td>
				</tr>
				<tr>
					<td>Цена<span id="unitsname2"></span>:</td>
					<td><input  lang="en" type="text" style="-moz-appearance: textfield;" step="0.01"  id="price" autocomplete="off" oninput="digon();" onkeypress="if (event.keyCode == 13) {
								//sendData();
								//this.blur();
							}"></td>
				</tr>
	<!--				<tr>
					<td></td>
					<td style="vertical-align: middle;"><input type="checkbox" onclick="loadReportForDate(qs('#date').value);" id="stocktaking" autocomplete="off"><label for="stocktaking" style="vertical-align: middle; display: inline-block;">Инвентаризация</label></td>
				</tr>-->

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
