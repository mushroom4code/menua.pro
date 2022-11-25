
/* global qs, currentLevel, measuringUnits, goodsTypes */

let addItemWindow = function (barcode = null) {
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Добавить`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	let table = el('div', {className: 'addItemsTable'});
	boxBody.appendChild(table);
	let tr1 = el('div');
	tr1.addEventListener('click', function () {
		tr3.style.display = typeItemInput.checked ? '' : 'none';
		tr4.style.display = typeItemInput.checked ? '' : 'none';
	});
	tr1.appendChild(el('div', {innerHTML: `Добавляется:`}));
	let td1 = el('div');

	let typeFolderInput = el('input');
	typeFolderInput.type = 'radio';
	typeFolderInput.name = 'type';
	typeFolderInput.id = 'typeFolder';
	let label1 = el('label');
	label1.style.display = 'block';
	label1.htmlFor = 'typeFolder';
	label1.innerHTML = 'Папка';

	let typeItemInput = el('input');
	typeItemInput.type = 'radio';
	typeItemInput.checked = true;
	typeItemInput.name = 'type';
	typeItemInput.id = 'typeItem';
	let label2 = el('label');
	label2.style.display = 'block';
	label2.htmlFor = 'typeItem';
	label2.innerHTML = 'Элемент';


	let typeSetInput = el('input');
	typeSetInput.type = 'radio';
	typeSetInput.name = 'type';
	typeSetInput.id = 'typeSet';
	let label3 = el('label');
	label3.htmlFor = 'typeSet';
	label3.innerHTML = 'Набор';
	label3.style.display = 'block';

	td1.appendChild(typeItemInput);
	td1.appendChild(label2);
	tr1.appendChild(td1);
	td1.appendChild(typeFolderInput);
	td1.appendChild(label1);
	td1.appendChild(typeSetInput);
	td1.appendChild(label3);
	table.appendChild(tr1);


	let tr5 = el('div');

	tr5.appendChild(el('div', {innerHTML: `Тип элемента:`}));
	let td5 = el('div');
	tr5.appendChild(td5);
	let itemType = el('select');
	itemType.id = 'itemType';
	itemType.appendChild(new Option('Выбрать', ''));
	goodsTypes.sort((a, b) => {
		if (a.name.toLowerCase() < b.name.toLowerCase()) {
			return -1;
		}
		if (a.name.toLowerCase() > b.name.toLowerCase()) {
			return 1;
		}
		return 0;
	});
	for (let unit of goodsTypes) {
		itemType.appendChild(new Option(unit.name, unit.id));
	}
	td5.appendChild(itemType);
	table.appendChild(tr5);




	let tr2 = el('div');
	tr2.appendChild(el('div', {innerHTML: `Название:`}));
	let td2 = el('div');
	tr2.appendChild(td2);
	let itemName = el('input');
	itemName.type = 'text';
	itemName.id = 'itemName';
	td2.appendChild(itemName);
	itemName.focus();
	table.appendChild(tr2);
	let tr3 = el('div');

	tr3.appendChild(el('div', {innerHTML: `Штрих-код:`}));
	let td3 = el('div');
	td3.style.display = 'grid';
//	td3.style.border='1px solid red';
	td3.style.gridTemplateColumns = 'auto auto';
	tr3.appendChild(td3);
	let itemBarcode = el('input');
	itemBarcode.type = 'text';
	itemBarcode.id = 'itemBC';
	if (barcode) {
		itemBarcode.value = barcode;
	}
	td3.appendChild(itemBarcode);
	let bcGenBtn = el('button');
	bcGenBtn.className = 'bcGenBtn';
	bcGenBtn.innerHTML = '+';
	bcGenBtn.addEventListener('click', function () {
		itemBarcode.value = 'i' + RDS(14, true);
	});
	td3.appendChild(bcGenBtn);
//	table.appendChild(tr3);
	let tr4 = el('div');

	tr4.appendChild(el('div', {innerHTML: `Ед. изм.`}));
	let td4 = el('div');
	tr4.appendChild(td4);
	let itemUnit = el('select');
	itemUnit.appendChild(new Option('Выбрать', ''));
	measuringUnits.sort((a, b) => {
		if (a.fname.toLowerCase() < b.fname.toLowerCase()) {
			return -1;
		}
		if (a.fname.toLowerCase() > b.fname.toLowerCase()) {
			return 1;
		}
		return 0;
	});
	for (let unit of measuringUnits) {
		itemUnit.appendChild(new Option(unit.fname, unit.id));
	}
	itemUnit.id = 'itemUnit';
	td4.appendChild(itemUnit);
	table.appendChild(tr4);
	let checkCheck = function () {
		if (typeFolderInput.checked) {
			qs('#BCrow').style.display = 'none';
		} else {
			qs('#BCrow').style.display = '';
		}
	};
	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let addBtn = el('button', {innerHTML: `Добавить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			let error = false;
			if (!(typeFolderInput.checked || typeItemInput.checked || qs('#typeSet').checked)) {
				MSG(`Укажите что добавляется Элемент, Папка или Набор.`);
				error = true;
			}

			if (!error && !qs('#itemType').value.trim()) {
				MSG(`Укажите тип добавляемого элемента`);
				error = true;
			}

			if (!error && !qs('#itemName').value.trim()) {
				MSG(`Укажите имя добавляемого элемента`);
				error = true;
			}

			if (!error) {
				let itemType = null;
				if (qs('#typeFolder').checked) {
					itemType = 1;
				} else if (qs('#typeItem').checked) {
					itemType = 2;
				} else if (qs('#typeSet').checked) {
					itemType = 3;
				}


				let data = {
					action: 'addNewItem',
					itemType: itemType,
					goodsType: qs('#itemType').value,
					itemName: itemName.value.trim(),
					itemBarcode: itemBarcode.value.trim(),
					itemParent: (typeof (currentLevel) !== 'undefined' ? currentLevel : null),
					itemUnit: itemUnit.value
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

							if (jsn.success) {
								box.parentNode.removeChild(box);
								resolve(true);
								if (typeof (loadGoods) !== 'undefined') {
									GETreloc('rand', Math.random());
								}
							} else {
								resolve(false);
							}

							for (let msge of jsn.msgs) {
								await MSG(msge);
							}
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
	itemName.focus();
	return promise;
};