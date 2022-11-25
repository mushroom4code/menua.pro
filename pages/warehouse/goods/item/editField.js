
/* global qs, currentLevel, measuringUnits */
let loadUnits = async function () {

	let units = await fetch('/pages/warehouse/goods/goods_IO.php', {
		body: JSON.stringify({getUnits: true}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if (jsn.units) {
				console.log(jsn.units);
				return jsn.units;
			}
		} catch (e) {
			MSG("27Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch
	units.sort((a, b) => {
		if (a.fname.toLowerCase() > b.fname.toLowerCase()) {
			return 1;
		} else if (a.fname.toLowerCase() < b.fname.toLowerCase()) {
			return -1;
		} else {
			return 0;
		}
	});
	return units;
};

let measuringUnits = [];
(async () => {
	measuringUnits = await loadUnits();
})();
let loadDirTree = async function () {

	let dirTree = await fetch('/pages/warehouse/goods/goods_IO.php', {
		body: JSON.stringify({getDirTree: true}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if ((jsn.msgs || []).length) {
				for (let msg of jsn.msgs) {
					let data = await MSG(msg);
					if (data === true) {
//								reset form
					}
					console.log(data);
				}
			}

			if (jsn.dirTree) {
				console.log(jsn.dirTree);
				return jsn.dirTree;
			}
		} catch (e) {
			MSG("59Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch
	return dirTree;
};

function treeRender(self, element, data, indent = 0) {
	//self = 1*(self);
	console.log('data', data);
	let textindent = '';
	for (let i = 0; i < indent * 1; i++) {
		textindent += `__`;
	}
//	let domElement = el('optgroup', {className: 'L'});
	data.sort((a, b) => {
		if (a.WH_nomenclatureName.toLowerCase() < b.WH_nomenclatureName.toLowerCase()) {
			return -1;
		}
		if (a.WH_nomenclatureName.toLowerCase() > b.WH_nomenclatureName.toLowerCase()) {
			return 1;
		}
		return 0;

	});
	let option = new Option(`Без раздела`, ``);
	element.appendChild(option);
	for (let chunkID in data) {
//		console.log(data[chunkID].idgoods, self);
		if (
				((data[chunkID] || {}).childs || []).length
				) {
			//let optigroup = el('optgroup', {className: 'L'});
			//optigroup.label = `${self}-${data[chunkID].idgoods}: ${data[chunkID].goodsName}---`;
//			idWH_nomenclature, WH_nomenclatureName, WH_nomenclatureUnits, WH_nomenclatureEntryType, WH_nomenclatureParent, WH_nomenclatureType, WH_nomenclatureMin, WH_nomenclatureMax
			let option = new Option(`${textindent}${data[chunkID].WH_nomenclatureName}`, `${data[chunkID].idWH_nomenclature}`, +data[chunkID].idWH_nomenclature === self, +data[chunkID].idWH_nomenclature === self);
			option.style.fontWeight = 'bold';
			element.appendChild(option);
			treeRender(self, element, data[chunkID].childs, indent + 1);
//			element.appendChild(optigroup);
		} else {
			let option = new Option(`${textindent}${data[chunkID].WH_nomenclatureName}`, `${data[chunkID].idWH_nomenclature}`, +data[chunkID].idWH_nomenclature === self, +data[chunkID].idWH_nomenclature === self);

			element.appendChild(option);

		}
}
//	return domElement;
}

async function editField(inputData) {
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Редактировать`}));
	console.log('inputData', inputData);
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	boxBody.appendChild(el('div', {className: 'title', innerHTML: {
			itemName: 'Название',
			goodsBarcode: 'Штрих-код',
			addBarcode: 'Новый штрих-код',
			idunits: 'Единицы измерения (склад)',
			idunitsSupplier: 'Единицы измерения (поставщик)',
			goodsMinLimit: 'Минимальный лимит',
			goodsMaxLimit: 'Максимальный лимит',
			deleteBarcode: 'Удалить штрихкод??',
			goodsUSUratio: 'Укажите количество',
			goodsQty: 'Укажите количество',
			ballance: 'Укажите количество',
			contentQty: 'Укажите количество',
			price: 'Укажите стоимость',
			istps: 'Услуга/Продукция сторонней организации',
			itemParent: 'Раздел'
		}[inputData.field]}));

	if (['itemName', 'ballance', 'contentQty', 'price', 'goodsBarcode', 'addBarcode', 'goodsUSUratio', 'goodsQty', 'goodsMinLimit', 'goodsMaxLimit'].indexOf(inputData.field) > -1) {
		var input = el('input');
		input.type = 'text';
		input.id = 'inputField';
		input.value = inputData.value || '';
		boxBody.appendChild(input);
	}

	if (inputData.field === 'goodsBarcode' || inputData.field === 'addBarcode') {
		input.addEventListener('input', function () {
			input.value = filterKeys(input.value);
		});
	}

	if (['price', 'ballance', 'goodsMinLimit', 'goodsMaxLimit', 'contentQty'].indexOf(inputData.field) > -1) {
		input.style.display = 'inline';
		input.style.width = 'auto';
		input.style.textAlign = 'right';
		input.size = '5';

	}
	if (['price'].indexOf(inputData.field) > -1) {
		input.style.borderRadius = '1px';
		boxBody.appendChild(el('span', {innerHTML: ` &#x20bd за 1`}));
	}
	if (inputData.units) {
		boxBody.appendChild(el('span', {innerHTML: ` ${inputData.units}`}));
	}


	if (['price', 'goodsUSUratio', 'contentQty'].indexOf(inputData.field) > -1) {
		input.addEventListener('input', function () {
			input.value = onlyDigits(input.value);
		});
	}

	if (inputData.field === 'itemParent') {
		let dirTree = await loadDirTree();

		let select = el('select');
		select.id = 'inputField';
//		let  optgroup = el('optgroup');
//		optgroup.label = 'Корневой раздел';
//		select.appendChild(optgroup);
		treeRender(inputData.value, select, dirTree);
		boxBody.appendChild(select);

	}
	if (inputData.field === 'istps') {

		let select = el('select');
		select.id = 'inputField';
//		let  optgroup = el('optgroup');
//		optgroup.label = 'Корневой раздел';
		select.appendChild(new Option('', ''));
		select.appendChild(new Option('Да', 1, (inputData.value || '') == 1, (inputData.value || '') == 1));
		select.appendChild(new Option('Нет', 0, (inputData.value || '') == 0, (inputData.value || '') == 0));
		boxBody.appendChild(select);
	}


	if (inputData.field === 'idunits' || inputData.field === 'idunitsSupplier') {
		var input = el('select');
		input.id = 'inputField';
		let units = await loadUnits();
		for (let unit of units) {
			input.appendChild(new Option(unit.fname, unit.id));
		}
		console.log(units);
		input.value = inputData.value;
		boxBody.appendChild(input);
	}


	if (inputData.field === 'goodsBarcode' || inputData.field === 'addBarcode') {
		let button = el('button');
		button.innerHTML = 'Создать';
		button.addEventListener('click', function () {
			input.value = RDS(16, true);
		});
		boxBody.appendChild(button);
	}



	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let addBtn = el('button', {innerHTML: inputData.field === 'deleteBarcode' ? `Удалить` : `Сохранить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			let error = false;
			if (!error) {
				let data = {
					action: 'editField',
					key: inputData.field,
					item: inputData.item,
					value: ((qs('#inputField') || {}).value || '')
				};
				console.log(data);
				fetch('/pages/warehouse/goods/goods_IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
						if ((jsn.msgs || []).length) {
							for (let msg of jsn.msgs) {
								let data = await MSG(msg);
								if (data === true) {
									//								reset form
								}
								console.log(data);
							}
						}
						if (jsn.success) {
							box.parentNode.removeChild(box);
							resolve(false);
							document.location.reload();
//							if (typeof (jsn.newValue) !== 'undefined') {
//								inputData.DOM.innerHTML = jsn.newValue;
//							}
//							loadGoods(currentLevel);
						}
					} catch (e) {
						MSG("221Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				}); //fetch

			}




		});
		let cancelBtn = el('button', {innerHTML: `Отмена`});
		box.appendChild(cancelBtn);
		cancelBtn.addEventListener('click', function () {
			box.parentNode.removeChild(box);
			resolve(false);
		});
	});
	document.body.appendChild(box);
	return promise;
}
;