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

	<script>
		let suppliers = JSON.parse('<?= json_encode(query2array(mysqlQuery("SELECT * FROM `suppliers` ORDER BY `suppliersName`")), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
		let companies = JSON.parse('<?= json_encode(query2array(mysqlQuery("SELECT identities, entitiesName FROM `entities` ORDER BY `entitiesName`")), 288); ?>');

		let suppliersKPP = JSON.parse('<?= json_encode(query2array(mysqlQuery("SELECT * FROM `kpps`")), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
		let units = JSON.parse('<?= json_encode(query2array(mysqlQuery("SELECT * FROM `units`")), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
		let goodsTypes = JSON.parse('<?= json_encode(query2array(mysqlQuery("SELECT * FROM `goodsTypes`")), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>');
		let items = [];
		let newRow = {};

		document.addEventListener("DOMContentLoaded", function () {
			units.sort((a, b) => {
				if (a.unitsName.toLowerCase() > b.unitsName.toLowerCase()) {
					return 1;
				}
				if (a.unitsName.toLowerCase() < b.unitsName.toLowerCase()) {
					return -1;
				}
				return 0;
			});
			qs('#newUnits').appendChild(new Option('', ''));
			units.forEach(unit => {
				qs('#newUnits').appendChild(new Option(decodeHtmlEntity(unit.unitsName), unit.idunits));
				qs('#WHunits').appendChild(new Option(decodeHtmlEntity(unit.unitsName), unit.idunits));
				qs('#WHunits2').appendChild(new Option(decodeHtmlEntity(unit.unitsName), unit.idunits));
				qs('#Nunits').appendChild(new Option(decodeHtmlEntity(unit.unitsName), unit.idunits));
				qs('#Nunits2').appendChild(new Option(decodeHtmlEntity(unit.unitsName), unit.idunits));
				qs('#newUnits2').appendChild(new Option(decodeHtmlEntity(unit.unitsName), unit.idunits));
			});



			qs('#newType').appendChild(new Option('', ''));
			goodsTypes.forEach(type => {
				qs('#newType').appendChild(new Option(decodeHtmlEntity(type.goodsTypesName), type.idgoodsTypes));
			});


			suppliers.sort((a, b) => {
				if (a.suppliersName.toLowerCase() > b.suppliersName.toLowerCase()) {
					return 1;
				}
				if (a.suppliersName.toLowerCase() < b.suppliersName.toLowerCase()) {
					return -1;
				}
				return 0;
			});
			suppliers.forEach(supplier => {
				qs('#idsuppliers').appendChild(new Option(decodeHtmlEntity(supplier.suppliersName), supplier.idsuppliers));
			});
			companies.forEach(company => {
				qs('#idcompany').appendChild(new Option(decodeHtmlEntity(company.entitiesName), company.identities));
			});
			let consignmentNote = {};
			var searchResults = qs('#searchResults');
		});

	</script>
	<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
	<div class="box neutral" style="display: none;">
		<div class="box-body">
			<div style="display: inline-block;"><pre id="json"></pre></div>
			<div style="display: inline-block;"><pre id="json2"></pre></div>
		</div>
	</div>
	<style>
		.CNtable {
			display: inline-table;
		}
		.CNtable input{
			width: auto;
			display: inline;
		}

		.CNtable td,.CNtable th {
			border: 1px solid silver;
		}

	</style>
	<div class="box neutral">
		<div class="box-body">
			<div style="padding: 20px;">Дата операции: <input id="actionDate" style="display: inline-block; width: auto;"type="date" value="<?= date("Y-m-d"); ?>"></div>

			<table>
				<tr>
					<td></td>
					<td>Номер документа</td>
					<td>Дата составления</td>
				</tr>
				<tr><th>ТОВАРНАЯ НАКЛАДНАЯ</th>
					<td><input id="CNnum" type="text"></td>
					<td><input id="CNdate" type="date"></td>
				</tr>
			</table>
			<div style="display: inline-block; padding: 3px;">
				<div style="display: grid; grid-template-columns: auto auto;">
					<select id="idsuppliers" onchange="setSupplier(this.value);">
						<option value="">Поставщик</option>
					</select>
					<select id="suppliersKPP">
						<option value="">КПП</option>
					</select>
				</div>
			</div>
			<br>
			<div style="display: inline-block; padding: 3px;">
				<select id="idcompany">
					<option value="">Плательщик</option>
				</select>
			</div>
			<style>
				.consignmentNote {
					font: 12pt/12pt Calibri;
					border-bottom: 2px solid silver;
					border-right: 2px solid silver;
					border-collapse: collapse;
					background-color: white;
				}
				.consignmentNote td, .consignmentNote th {
					/*border: none;*/
					border-top: 1px solid gray;
					border-left: 1px solid gray;

				}
				.consignmentNote input,.consignmentNote select, .consignmentNote button {
					display: inline;
					border-radius: 1px;
					margin: 2px 0px;
					width: auto;
					line-height: 12pt;
					font-size: 12pt;
					vertical-align: middle;
					height: 1.3em;
					box-shadow: none;
					background-color: #EEE;
					border: 1px solid silver;
				}

			</style>

			<table class="consignmentNote" border="1">
				<thead>
					<tr>
						<th rowspan="2">#</th>
						<th rowspan="2">IdDB</th>
						<th colspan="4">Товар по накладной/чеку</th>
						<th colspan="2">по факту</th>
						<th rowspan="2">Единицы номенклатуры</th>
						<th rowspan="2">Отпуск со склада</th>
						<th rowspan="2">НДС<br>сумма</th>
						<th rowspan="2">Сумма с<br>учётом НДС</th>
						<th rowspan="2"></th>

					</tr>
					<tr>
						<th>Наименование</th>
						<th>Штрихкод</th>
						<th>Тип</th>
						<th>Ед.изм</th>
						<th>Кол-во</th>
						<th>Ед.изм</th>
					</tr>
				</thead>
				<tbody id="consignmentNoteBody"></tbody>
				<tfoot>
					<tr>
						<td>+</td>
						<td><input id="idgoods" type="text" size="2" style="width: auto;"></td>
						<td><input id="newItemName" type="text" placeholder="Наименование" oninput="searchItemByName(this); newRow.name = this.value.trim();"><div id="searchResults" style="position: absolute; z-index: 10;"></div></td>
						<td style="white-space: nowrap;"><input id="newItemBarcode" size="8" style="display: inline; width: auto;" type="text" placeholder="Штрихкод"><button style="" onclick="qs('#newItemBarcode').value = RDS(13, true);">+</button></td>
						<td><select id="newType" style="width: 60px;"></select></td>
						<td><select id="newUnits" onchange="qs('#newUnits2').value = this.value;"></select></td>
						<td><input type="text" id="qty" oninput="digon();" style="background-color: #cfc; width: auto;" size="2"></td>
						<td><select id="WHunits2" disabled><option></option></select></td>

						<td style="white-space: nowrap;"><input id="newNomenclatureID" type="hidden"><input id="newNomenclatureName" type="text" placeholder="Наименование" oninput="searchNomenclatureByName(this,qs('#searchNomenclatureResults')); "><div id="searchNomenclatureResults" style="position: absolute; z-index: 10;"></div><br>1<div style="display: inline-block;"><select  id="newUnits2" disabled><option></option></select></div>=<div style="display: inline-block;"><input type="text" id="newQty" style="width: auto;" size="2"></div><div style="display: inline-block;"><select id="Nunits" onchange="qs('#Nunits2').value = this.value;"><option></option></select></div></td>
						<td style="white-space: nowrap;">
							1<div style="display: inline-block;"></div><div style="display: inline-block;"><select id="WHunits" onchange="qs('#WHunits2').value = this.value;"><option></option></select></div>=<div style="display: inline-block;"><input type="text" id="WHqty" oninput="digon();" style="width: auto;" size="2"></div><div style="display: inline-block;"><select id="Nunits2" disabled><option></option></select></div>
						</td>


						<td><input type="text" id="newVatSumm" style="background-color: #cfc; width: auto;" size="5" placeholder="сумма НДС" oninput="digon();"></td>
						<td><input type="text" id="summIncVat" style="background-color: #cfc; width: auto;" size="5" placeholder="Сумма c учётом НДС" oninput="digon();"></td>
						<td><input type="button" style="color: green; font-weight: bold;" onclick="addToConsignmentNote(); this.blur();" value="+"></td>
					</tr>
				</tfoot>
			</table>
			<script>

				setInterval(function () {
					qs('#json2').innerHTML = JSON.stringify(items, null, 2);
				}, 1000);
				setInterval(function () {
					newRow.idgoods = parseInt(qs('#idgoods').value.trim());
					newRow.name = qs('#newItemName').value.trim();
					newRow.barcode = qs('#newItemBarcode').value.trim();
					newRow.type = parseInt(qs('#newType').value.trim());
					newRow.units = parseInt(qs('#newUnits').value.trim());
					newRow.qty = parseFloat(qs('#qty').value.trim());
					newRow.nomenclatureID = parseInt(qs('#newNomenclatureID').value.trim());
					newRow.nomenclatureName = (qs('#newNomenclatureName').value.trim());
					newRow.nomenclatureQty = parseFloat(qs('#newQty').value.trim());
					newRow.nomenclatureUnits = parseInt(qs('#Nunits').value.trim());
					newRow.WHunits = parseInt(qs('#WHunits').value.trim());
					newRow.WHqty = parseFloat(qs('#WHqty').value.trim());
					newRow.vatSumm = parseFloat(qs('#newVatSumm').value.trim());
					newRow.summIncVat = parseFloat(qs('#summIncVat').value.trim());
					qs('#json').innerHTML = JSON.stringify(newRow, null, 2);
				}, 1000);
			</script>
			<div style="text-align: center; padding: 50px;"><input type="button" value="Сохранить накладную" data-function="saveConsignmentNote"></div>
		</div>
	</div>
	<?
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
