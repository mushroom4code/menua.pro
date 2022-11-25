/* global qs, items, units, goodsTypes, measuringUnits */

async function getVAT() {
	if (newRow.supplier && (newRow || {}).iditems) {
		fetch('/pages/warehouse/goods/goods_IO.php', {
			body: JSON.stringify({action: 'getVAT', supplier: newRow.supplier, item: newRow.iditems}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				newRow.vat = jsn.vatsAmount;
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});
	}
}


function setSupplier(idsupplier) {
	clear(qs('#suppliersKPP'));
	let kpps = suppliersKPP.filter(element => {
		return element.kppsSupplier == idsupplier;
	});
	if (kpps.length) {
		kpps.forEach(element => {
			qs('#suppliersKPP').appendChild(new Option(element.kppsKpp, element.idkpps));
		});
	} else {
		qs('#suppliersKPP').appendChild(new Option(`Не указан КПП`, ``));
	}
	newRow.supplier = idsupplier;
	getVAT();
}


function searchItemByName(inputElement) {
	console.log(inputElement.value);
	clear(searchResults);
	if (inputElement.value.trim().length >= 3) {
		fetch('/pages/warehouse/goods/goods_IO.php', {
			body: JSON.stringify({action: 'searchGoods', search: inputElement.value.trim()}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if ((jsn.items || []).length > 0) {
					jsn.items.forEach((item) => {
						let reg = new RegExp("(" + inputElement.value.trim() + ")", 'gi');
						let html = item.WH_goodsName.replace(reg, function (str) {//itemsName
							return '<b style="color: pink;">' + str + '</b>';
						});

						let params = '';

						for (let elem in item) {
							params += `data-${elem.toString().toLowerCase()}="${item[elem]}"`;
						}
						searchResults.appendChild(el('div', {innerHTML: `<div style="background-color: white; padding: 3px 10px; border: 1px solid silver; border-radius: 0px; white-space: nowrap;">${item.parentsName || ''} ${html || ''} (${item.WH_goodBarCode || ''})<div style="position: absolute; width: 100%; height: 100%; cursor: pointer; top: 0px; left: 0px; z-index: 10;" data-clear="${searchResults.id}"  data-function="choeseItem" ${params}></div></div>`}));
					});
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});
	}
}

function searchNomenclatureByName(inputElement, searchResults) {
	console.log(inputElement.value);
	clear(searchResults);
	if (inputElement.value.trim().length >= 3) {
		fetch('/pages/warehouse/goods/goods_IO.php', {
			body: JSON.stringify({action: 'searchNomenclature', search: inputElement.value.trim()}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if ((jsn.items || []).length > 0) {
					jsn.items.forEach((item) => {
						let reg = new RegExp("(" + inputElement.value.trim() + ")", 'gi');
						let html = item.WH_nomenclatureName.replace(reg, function (str) {//itemsName
							return '<b style="color: pink;">' + str + '</b>';
						});

						let params = '';
//idWH_nomenclature	934
//WH_nomenclatureName	"Лаеннек"
//WH_nomenclatureUnits	5
//WH_nomenclatureEntryType	2
//WH_nomenclatureParent	19
//WH_nomenclatureType	1
//WH_nomenclatureMin	null
//WH_nomenclatureMax	null


						for (let elem in item) {
							params += `data-${elem.toString().toLowerCase()}="${item[elem]}"`;
						}
						searchResults.appendChild(el('div', {innerHTML: `<div style="background-color: white; padding: 3px 10px; border: 1px solid silver; border-radius: 0px; white-space: nowrap;">${html || ''}<div style="position: absolute; width: 100%; height: 100%; cursor: pointer; top: 0px; left: 0px; z-index: 10;" data-clear="${searchResults.id}"  data-function="choeseNomenclature" ${params}></div></div>`}));
					});
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});
	}
}






function choeseItem2(data) {

	console.log(data);
	newRow.iditems = data.iditem;
	newRow.name = data.itemname;
	newRow.units = data.units;

	getVAT();
	if (data.units) {
		qs('#newUnits').value = data.units;
	}


	clear(searchResults);
	qs('#newItemName').value = data.itemname;
}



function choeseNomenclature(item) {
	console.log('choeseNomenclature');
//clear: "searchNomenclatureResults"
//idwh_nomenclature: 934
//wh_nomenclatureentrytype: 2
//wh_nomenclaturemax: "null"
//wh_nomenclaturemin: "null"
//wh_nomenclaturename: "Лаеннек"
//wh_nomenclatureparent: 19
//wh_nomenclaturetype: 1
//wh_nomenclatureunits: 5
	if (item.wh_nomenclaturename) {
		qs('#newNomenclatureName').value = item.wh_nomenclaturename;
	}

	if (item.idwh_nomenclature) {
		qs('#newNomenclatureID').value = item.idwh_nomenclature;
	}

	if (item.wh_nomenclatureunits && qs('#Nunits')) {
		qs('#Nunits').value = (measuringUnits.find(elem => {
			return elem.id == item.wh_nomenclatureunits;
		}) || {}).fname || '???';
	}

	if (item.wh_nomenclatureunits && qs('#unitsname')) {
		qs('#unitsname').innerHTML = ', ' + measuringUnits.find(elem => {
			return elem.id == item.wh_nomenclatureunits;
		}).name || '???';
		qs('#unitsname2').innerHTML = ', руб. за 1' + measuringUnits.find(elem => {
			return elem.id == item.wh_nomenclatureunits;
		}).name || '???';
	}

	if (item.wh_nomenclatureunits && qs('#Nunits2')) {
		qs('#Nunits2').value = item.wh_nomenclatureunits;
	}

	console.log(item);
	if (item.clear) {
		clear(qs(`#${item.clear}`));
	}
}
async function choeseItem(item) {
	console.log('choeseItem', item);
	if (item.clear) {
		clear(qs(`#${item.clear}`));
	}
	qs('#qty').focus();
//			clear: "searchResults"
//			​idwh_goods: 1027
//			​idwh_nomenclature: 934
//			​wh_goodbarcode: 4987480010100
//			​wh_goodsdeleted: "null"
//			​wh_goodsname: "Лаеннек р-р д/ин. амп. 2 мл №10"
//			​wh_goodsnomenclature: 934
//			​wh_goodsnomenclatureqty: 20
//			​wh_goodstype: 1
//			​wh_goodsunits: 8
//			​wh_goodswhqty: 2
//			​wh_goodswhunits: 10
//			​wh_nomenclatureentrytype: 2
//			​wh_nomenclaturemax: "null"
//			​wh_nomenclaturemin: "null"
//			​wh_nomenclaturename: "Лаеннек"
//			​wh_nomenclatureparent: 19
//			​wh_nomenclaturetype: 1
//			​wh_nomenclatureunits: 5


	if (item.wh_nomenclatureunits && qs('#unitsname')) {
		qs('#unitsname').innerHTML = ', ' + measuringUnits.find(elem => {
			return elem.id == item.wh_nomenclatureunits;
		}).name || '???';
		qs('#unitsname2').innerHTML = ', руб. за 1' + measuringUnits.find(elem => {
			return elem.id == item.wh_nomenclatureunits;
		}).name || '???';
	}



	if (item.idwh_goods && item.idwh_goods !== 'null') {
		qs('#idgoods').value = item.idwh_goods;
	}

	if (item.wh_goodsname && item.wh_goodsname !== 'null') {
		qs('#newItemName').value = item.wh_goodsname;
	}

	if (item.wh_goodbarcode && item.wh_goodbarcode !== 'null') {
		qs('#newItemBarcode').value = item.wh_goodbarcode;
	}
	if (item.wh_goodsunits && item.wh_goodsunits !== 'null' && qs('#newUnits')) {
		qs('#newUnits').value = item.wh_goodsunits;
	}
	if (item.wh_goodsunits && item.wh_goodsunits !== 'null' && qs('#newUnits2')) {
		qs('#newUnits2').value = item.wh_goodsunits;
	}



	if (item.wh_nomenclaturetype && item.wh_nomenclaturetype !== 'null' && qs('#newType')) {
		qs('#newType').value = item.wh_nomenclaturetype;
	}

	if (item.idwh_goods && item.idwh_goods !== 'null' && qs('#WHunits')) {
		qs('#WHunits').value = item.wh_goodswhunits;
	}

	if (item.idwh_goods && item.idwh_goods !== 'null' && qs('#WHunits2')) {
		qs('#WHunits2').value = item.wh_goodswhunits;
	}


	if (item.wh_goodswhqty && item.wh_goodswhqty !== 'null' && qs('#WHqty')) {
		qs('#WHqty').value = item.wh_goodswhqty;
	}


	if (item.idwh_nomenclature && item.idwh_nomenclature !== 'null') {
		qs('#newNomenclatureID').value = item.idwh_nomenclature;
	}

	if (item.wh_nomenclaturename && item.wh_nomenclaturename !== 'null') {
		qs('#newNomenclatureName').value = item.wh_nomenclaturename;
	}

	if (item.wh_nomenclatureunits && item.wh_nomenclatureunits !== 'null') {
		qs('#Nunits').value = item.wh_nomenclatureunits;
	}


	if (item.idwh_nomenclature && item.idwh_nomenclature !== 'null') {
		if (qs('#Nunits')) {
			if (item.wh_nomenclatureunits && item.wh_nomenclatureunits !== 'null') {
				qs('#Nunits').value = (measuringUnits.find((elem) => {
					return item.wh_nomenclatureunits === elem.id;
				})).fname || 'Не указаны';
			} else {
				await MSG('Не указаны единицы измерения');
				qs('#newNomenclatureName').focus();
			}
		}
	} else {
		await MSG('Привяжите товар к номенклатуре');
		qs('#newNomenclatureName').focus();
	}


	if (item.wh_nomenclatureunits && item.wh_nomenclatureunits !== 'null' && qs('#Nunits2')) {
		qs('#Nunits2').value = item.wh_nomenclatureunits;
	}

	if (item.wh_goodsnomenclatureqty && item.wh_goodsnomenclatureqty !== 'null' && qs('#newQty')) {
		qs('#newQty').value = item.wh_goodsnomenclatureqty;
	}






}




function round2(x) {
	return (Math.ceil(x * 100) / 100).toFixed(2);
}


async function addToConsignmentNote() {
	let errors = [];
	if (newRow.type === null) {
		errors.push('Не указан тип товара');
	}
	if (newRow.summIncVat === null) {
		errors.push('Не указана стоимость');
	}

	if (!newRow.name || !newRow.name.trim()) {
		errors.push('Не указан товар');
	}
	if (!newRow.qty) {
		errors.push('Не указано количество');
	}

	if (!errors.length) {
		items.push(newRow);
		newRow = {};
		resetForm();
		renderConsignmentNoteBody();
	} else {
		for (let i = 0; i < errors.length; i++) {
			await MSG(errors[i]);
		}
	}
}

function removeOne(data) {
	console.log('index', data.index);
	items.splice(data.index, 1);
	renderConsignmentNoteBody();
}

function renderConsignmentNoteBody() {
	let consignmentNoteBody = qs('#consignmentNoteBody');
	clear(consignmentNoteBody);
	let i = 0;
	let totalsumm = 0;
	let totalVAT = 0;
	items.forEach((item, index) => {
		i++;
		totalVAT += item.vatSumm;
		item.summExVat = item.summIncVat - item.vatSumm;
		item.newVatPerc = Math.round(((item.vatSumm || 0) / item.summExVat) * 100);
		let price = item.qty != 0 ? round2(item.summExVat / item.qty) : 0;
		let unitsName = item.units ? units.find(elem => {
			return elem.idunits == item.units;
		}
		)['unitsName'] : null;
		totalsumm += item.summIncVat;
		let gTN = goodsTypes.find(elem => {
			return elem.idgoodsTypes === item.type;
		}
		).goodsTypesName || '??';

		let gUN = units.find(elem => {
			return elem.idunits === item.units;
		}).unitsName || '??';

		let gNuN = units.find(elem => {
			return elem.idunits === item.nomenclatureUnits;
		}).unitsName || '??';
		let gWHuN = units.find(elem => {
			return elem.idunits === item.WHunits;
		}).unitsName || '??';


		let tr = `	<tr>
						<td class="R">${i}</td>
						<td class="C">${item.idgoods || 'Нов.'}</td>
						<td>${item.name}</td>
						<td class="C">${item.barcode}</td>
						<td class="C">${gTN}</td>
						<td class="C">${gUN}</td>		
						<td class="R">${item.qty || 0}</td>
						<td>${gWHuN}</td>		
						<td class="C">${item.nomenclatureName}<br>1${gUN} = ${item.nomenclatureQty}${gNuN}</td>
						<td class="C">1${gWHuN} = ${item.WHqty}${gNuN}</td>
						<td class="R">${round2(item.vatSumm) || '-'}</td>
						<td class="R">${round2(item.summIncVat) || '-'}</td>
						<td><input type="button" value="X" data-function="removeOne" data-index="${index}"></td>
					</tr>`;

		consignmentNoteBody.innerHTML += tr;
	});
	let tr = `	<tr>
					<td colspan="11" style="text-align: right;">${round2(totalVAT) || '-'}</td>
					<td style="text-align: right;">${round2(totalsumm) || '-'}</td>
					<td></td>
				</tr>`;

	consignmentNoteBody.innerHTML += tr;
}
function resetForm() {
	qs('#idgoods').value = '';
	qs('#newItemName').value = '';
	qs('#newItemBarcode').value = '';
	qs('#newType').value = '';
	qs('#newUnits').value = '';
	qs('#qty').value = '';
	qs('#newNomenclatureID').value = '';
	qs('#newNomenclatureName').value = '';
	qs('#newUnits2').value = '';
	qs('#newQty').value = '';
	qs('#Nunits').value = '';
	qs('#WHunits').value = '';
	qs('#WHqty').value = '';
	qs('#Nunits2').value = '';
	qs('#newVatSumm').value = '';
	qs('#summIncVat').value = '';
}

let cnErr = [];
async function saveConsignmentNote() {
	cnErr = [];
	let consignmentNote = {
		items: items,
		CNnum: qs('#CNnum').value.trim(),
		CNdate: qs('#CNdate').value.trim(),
		idsuppliers: qs('#idsuppliers').value.trim(),
		suppliersKPP: qs('#suppliersKPP').value.trim(),
		company: qs('#idcompany').value.trim()
	};

	if (consignmentNote.company == "") {
		cnErr.push({text: 'Укажите плательщика', options: [{text: ['сейчас исправлю'], value: false}]});
	}

	if (consignmentNote.CNdate == "") {
		cnErr.push({text: 'Укажите дату по накладной', options: [{text: ['Это не важно'], value: true}, {text: ['сейчас исправлю'], value: false}]});
	}

	if (consignmentNote.idsuppliers == "") {
		cnErr.push({text: 'Укажите поставщика', options: [{text: ['Это не важно'], value: true}, {text: ['сейчас исправлю'], value: false}]});
	}

	if (consignmentNote.suppliersKPP == "") {
		cnErr.push({text: 'Укажите КПП поставщика', options: [{text: ['Это не важно'], value: true}, {text: ['сейчас исправлю'], value: false}]});
	}

	if (consignmentNote.CNnum == "") {
		cnErr.push({text: 'Укажите номер накладной', options: [{text: ['Это не важно'], value: true}, {text: ['сейчас исправлю'], value: false}]});
	}

	if (qs('#actionDate').value === '') {
		cnErr.push({text: 'Укажите дату операции', options: [{text: ['сейчас исправлю'], value: false}]});
	}

	if (consignmentNote.items.length <= 0) {
		cnErr.push({text: 'Добавьте позиции в накладную', options: [{text: ['сейчас исправлю'], value: false}]});
	}


	if (cnErr.length) {
		for (let err of cnErr) {
			if (await MSG(err)) {
				cnErr = cnErr.filter((elem) => {
					return elem !== err;
				});
			}
		}
	}

	if (cnErr.length == 0) {
		console.log(consignmentNote);
		fetch('/pages/warehouse/in/IO.php', {
			body: JSON.stringify({action: 'saveConsignmentNote', date: qs('#actionDate').value, consignmentNote: consignmentNote}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if (jsn.success) {
					items = [];
					qs('#CNnum').value = '';
					qs('#CNdate').value = '';
					qs('#idsuppliers').value = '';
					qs('#suppliersKPP').value = '';
					renderConsignmentNoteBody();
				} else {
					MSG("Беда.. что-то не работает");
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});


	} else {
		console.log('oops');
	}




}