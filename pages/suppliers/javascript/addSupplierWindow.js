function addSupplierWindow() {



	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Добавить нового поставщика`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	let table = el('div', {className: 'addItemsTable'});
	boxBody.appendChild(table);

	let tr2 = el('div');
	tr2.appendChild(el('div', {innerHTML: `Название:`}));
	let td2 = el('div');
	tr2.appendChild(td2);
	let supplierName = el('input');
	supplierName.type = 'text';
	supplierName.id = 'itemName';
	td2.appendChild(supplierName);
	supplierName.focus();
	table.appendChild(tr2);
	let tr3 = el('div');
//	tr3.style.display = 'none';
	tr3.appendChild(el('div', {innerHTML: `Штрих-код:`}));
	let td3 = el('div');
	tr3.appendChild(td3);
	let supplierBarcode = el('input');
	supplierBarcode.type = 'text';
	supplierBarcode.id = 'itemName';

	td3.appendChild(supplierBarcode);
	let bcGenBtn = el('button');
	bcGenBtn.innerHTML = '+';
	bcGenBtn.addEventListener('click', function () {
		supplierBarcode.value = 'sply' + RDS(20, true);
	});
	td3.appendChild(bcGenBtn);
	table.appendChild(tr3);

	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let addBtn = el('button', {innerHTML: `Добавить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			let error = false;

			if (!error && !qs('#itemName').value.trim()) {
				MSG(`Укажите название <br>компании поставщика`);
				error = true;
			}

			if (!error) {
				let data = {
					action: 'addNewSupplier',
					supplierName: supplierName.value.trim(),
					supplierBarcode: supplierBarcode.value.trim()
				};
				console.log(data);
				fetch('/pages/suppliers/IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);

						if (jsn.success) {
							console.log('jsn.success');
							clear(box);
							box.remove();
						}

						console.log(typeof (renderSuppliersTable));
						if (typeof (renderSuppliersTable) !== 'undefined' && (jsn.suppliers || []).length > 0) {
							renderSuppliersTable(jsn.suppliers);
						}

						if ((jsn.msgs || []).length) {
							for (let msg of jsn.msgs) {
								await MSG(msg);
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
	supplierName.focus();
	return promise;


}