
/* global qs, currentLevel, measuringUnits, goodsTypes */

let addItemWindow = function (parent) {
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Добавить`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	let table = el('div', {className: 'addItemsTable'});
	boxBody.appendChild(table);
	let tr1 = el('div');
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


	td1.appendChild(typeItemInput);
	td1.appendChild(label2);
	tr1.appendChild(td1);
	td1.appendChild(typeFolderInput);
	td1.appendChild(label1);


	table.appendChild(tr1);

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
					action: 'addNewTPS_CatalogEntry',
					itemType: itemType,
					itemName: itemName.value.trim(),
					itemParent: (parent || null),
				};
				console.log(data);
				fetch('IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);

						if (jsn.success) {
							box.parentNode.removeChild(box);
							GETreloc('rand', Math.random());
							resolve(true);

						} else {
							resolve(false);
						}

						if ((jsn.msgs || []).length) {
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