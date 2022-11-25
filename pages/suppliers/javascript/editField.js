
/* global qs, currentLevel, measuringUnits */
let loadUnits = async function () {

	let units = await fetch('/pages/goods/goods_IO.php', {
		body: JSON.stringify({getUnits: true}),
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

			if (jsn.units) {
//console.log(jsn.units);
				return jsn.units;
			}
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch
	return units;
};



let loadDirTree = async function () {

	let dirTree = await fetch('/pages/goods/goods_IO.php', {
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
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch
	return dirTree;
};

function treeRender(self, element, data, indent = 0) {
	//self = 1*(self);
	console.log('data', data);
	let textindent = '';
	for (let i = 0; i < indent * 1; i++) {
		textindent += `--`;
	}
//	let domElement = el('optgroup', {className: 'L'});
	data.sort((a, b) => {
		if (a.goodsName.toLowerCase() < b.goodsName.toLowerCase()) {
			return -1;
		}
		if (a.goodsName.toLowerCase() > b.goodsName.toLowerCase()) {
			return 1;
		}
		return 0;

	});
	for (let chunkID in data) {
//		console.log(data[chunkID].idgoods, self);
		if (
				((data[chunkID] || {}).childs || []).length
				) {
			//let optigroup = el('optgroup', {className: 'L'});
			//optigroup.label = `${self}-${data[chunkID].idgoods}: ${data[chunkID].goodsName}---`;
			let option = new Option(`${textindent}${data[chunkID].goodsName}`, `${data[chunkID].idgoods}`, +data[chunkID].idgoods === self, +data[chunkID].idgoods === self);
			option.style.fontWeight = 'bold';
			element.appendChild(option);
			treeRender(self, element, data[chunkID].childs, indent + 1);
//			element.appendChild(optigroup);
		} else {
			let option = new Option(`${textindent}${data[chunkID].goodsName}`, `${data[chunkID].idgoods}`, +data[chunkID].idgoods === self, +data[chunkID].idgoods === self);

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
			suppliersName: 'Название',
			suppliersCode: 'Штрих-код',
			managerPhoneNumber: 'Номер телефона',
			suppliersEmail: 'E-mail для заказов',
			managerPhoneComment: 'Комментарий к телефону',
			newPhone: 'Номер телефона',
			suppliersINN: 'ИНН',
			newManager: 'Фамилия Имя',
			suppliersPhone: 'Основной телефон',
			newKPP: 'Добавить КПП',
			vatsAmount: 'Величина НДС'
		}[inputData.field]}));


	if (['newPhone', 'newKPP', 'suppliersINN', 'newManager', 'suppliersEmail', 'managerPhoneNumber', 'managerPhoneComment', 'suppliersCode', 'suppliersName', 'vatsAmount', 'suppliersPhone'].indexOf(inputData.field) > -1) {
		var input = el('input');
		input.type = 'text';
		input.id = 'inputField';
		input.value = inputData.value || '';
		boxBody.appendChild(input);
	}




	if (inputData.field === 'suppliersCode' || inputData.field === 'suppliersCode') {
		input.addEventListener('input', function () {
			input.value = filterKeys(input.value);
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


	if (inputData.field === 'idunits') {
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


	if (inputData.field === 'suppliersCode') {
		let button = el('button');
		button.innerHTML = 'Создать';
		button.addEventListener('click', function () {
			input.value = 'sply' + RDS(16, true);
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
					field: inputData.field,
					key: inputData.key,
					value: ((qs('#inputField') || {}).value || '')
				};
				if (typeof (inputData.supplier) !== 'undefined') {
					data.supplier = inputData.supplier;
				}
				if (typeof (inputData.goods) !== 'undefined') {
					data.goods = inputData.goods;
				}




				console.log(data);
				fetch('IO.php', {
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
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
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