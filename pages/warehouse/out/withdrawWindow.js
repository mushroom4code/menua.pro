
/* global qs, currentLevel, measuringUnits */

//WH_goodBarCode: "4607085860114"
//WH_goodsDeleted: null
//WH_goodsName: "Гель для рук Sanitelle"
//WH_goodsNomenclature: "979"
//WH_goodsNomenclatureQty: null
//WH_goodsPrice: null
//WH_goodsUnits: null
//WH_goodsWHQty: null
//WH_goodsWHUnits: null
//WH_nomenclatureEntryType: "2"
//WH_nomenclatureMax: null
//WH_nomenclatureMin: null
//WH_nomenclatureName: "Антисептик гель"
//WH_nomenclatureParent: null
//WH_nomenclatureType: "1"
//WH_nomenclatureUnits: "5"
//balance: "4.000"
//idWH_goods: "1051"
//idWH_nomenclature: "979"
//idunits: "5"
//unitsCode: "6"
//unitsFullName: "миллилитр"
//unitsName: "мл."
//unitsOKEI: "111"

let withdrawWindow = function (item) {
	console.log('withdrawWindow', item);
	let box = el('div', {className: `modal ${(item.balance > 0 || item.WH_nomenclatureEntryType == 3) ? 'neutral' : 'error'}`});
	box.id = 'withdrawWindow';
	if (!item.WH_nomenclatureEntryType) {
		box.appendChild(el('h2', {innerHTML: `Ошибка. Невозможно списать товар.`}));
		let boxBody = el('div', {className: 'box-body'});
		box.appendChild(boxBody);
		boxBody.appendChild(el('div', {innerHTML: `<div class='caption'>Товар найден:</div><div>"${item.WH_goodsName}"</div><div>но он не привязан к номенклатуре.</div>`}));
	} else {
		box.appendChild(el('h2', {innerHTML: `Списываем`}));
		let boxBody = el('div', {className: 'box-body'});
		box.appendChild(boxBody);
		let table = el('div', {className: 'addItemsTable'});
		boxBody.appendChild(table);
		table.appendChild(el('div', {innerHTML: `<div class='caption'>Название:</div><div>${item.WH_goodsName || item.WH_nomenclatureName}</div>`}));
		if (item.WH_nomenclatureEntryType == 2) {
			table.appendChild(el('div', {innerHTML: `<div class='caption hide'>Ед.изм.:</div><div class='hide'>${item.unitsFullName || '??'}</div>`}));
			table.appendChild(el('div', {innerHTML: `<div class='caption'>На складе:</div><div>${Math.round(item.balance * 1000) / 1000}${item.unitsName || '??'}</div>`}));
		}



		table.appendChild(el('div', {innerHTML: `<div class='caption'>Количество:</div><div><input size="1"  lang="en" type="number" style="-moz-appearance: textfield; width: 50%;" step="0.01"  size="1" autocomplete="off" id="withdrawQty" oninput="if (+this.value > +currentItem.balance) {qs('#withdrawWindow').classList.add('error'); qs('#withdrawWindow').classList.remove('neutral');} else {qs('#withdrawWindow').classList.remove('error');qs('#withdrawWindow').classList.add('neutral');}" value="1" onblur="this.value = parseFloat(this.value.replace(',', '.')) || 0;"> ${item.unitsName || 'шт.'}</div>`}));
		if (item.WH_nomenclatureEntryType == 2) {
			table.appendChild(el('div', {innerHTML: `<div id="overdraft" style="grid-column: 1/-1; display: ${item.balance > 0 ? 'none' : 'block'};">Вы списываете больше, чем есть на складе!</div>`}));
		}
	}




	document.body.appendChild(box);

	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it



		if (qs('#withdrawQty')) {
			let addBtn = el('button', {innerHTML: `Сохранить`});
			box.appendChild(addBtn);
			addBtn.addEventListener('click', function () {
				sendRequest(resolve, reject);
			});
			qs('#withdrawQty').addEventListener('keypress', e => {
				if (e.keyCode === 13) {
					qs('#withdrawQty').value = parseFloat(qs('#withdrawQty').value.replace(',', '.')) || 0;
					sendRequest(resolve, reject);
					qs('#withdrawQty').blur();
				}


			});
		}

		let cancelBtn = el('button', {innerHTML: `Отмена`});
		box.appendChild(cancelBtn);
		cancelBtn.addEventListener('click', function () {
			box.parentNode.removeChild(box);
			resolve(false);
		});
	});


	async function sendRequest(resolve, reject) {
		console.log('sendRequest');
		let error = [];

		if (+qs('#withdrawQty').value === 0) {
			error.push('Это как так??');
		}


		if (!error.length) {
			let data = {
				action: 'makeWithdraw',
				date: qs('#DATE').value,
				user: _personal.id,
				item: (currentItem.idWH_goods || `SET${currentItem.idWH_nomenclature}`),
				qty: qs('#withdrawQty').value
			};
			console.log(data);
			fetch('/pages/warehouse/out/IO.php', {
				body: JSON.stringify(data),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);
					if ((jsn.msgs || []).length) {
						for (let msge of jsn.msgs) {
							await MSG(msge);
						}
					}
					if (jsn.success) {
						box.parentNode.removeChild(box);
						let withdrawal = await getWithdrawal(_personal.id, jsn.date);
						renderWithdrawal(withdrawal);
						resolve(true);

					} else {
						resolve(false);
					}


				} catch (e) {
					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
			}); //fetch

		} else {
			error.forEach(async err => {
				await MSG(err);
			});
		}
	}



	//qs('#withdrawQty').focus();
	return promise;
}
;