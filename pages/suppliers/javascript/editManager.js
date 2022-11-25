async function editManager(inputData) {
	console.log('editManager', inputData.manager);
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Редактировать`}));

	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	let manager = await fetch('IO.php', {
		body: JSON.stringify({action: 'getManager', manager: inputData.manager}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			return jsn.manager || [];
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	});//fetch
	console.log(manager);


	let table = el('div', {className: 'addItemsTable'});

	let row1 = el('div');
	table.appendChild(row1);
	row1.appendChild(el('div', {className: `caption`, innerHTML: `Фамилия Имя: `}));

	let managerNameDiv = el('div');
	row1.appendChild(managerNameDiv);

	let managerNameInput = el('input');
	managerNameInput.type = 'text';
	managerNameInput.value = manager.name;

	managerNameDiv.appendChild(managerNameInput);


	let row2 = el('div');
	table.appendChild(row1);
	console.log(manager);
	if (((manager || {}).phones || []).length) {
		manager.phones.forEach(phone => {


			let managerPhone = el('div', {className: 'cherryBoard'});
			managerPhone.style.gridColumn = '1/-1';
			managerPhone.style.display = 'grid';
			managerPhone.style.margin = '5px auto';
			managerPhone.style.gridTemplateColumns = 'auto auto';
			row1.appendChild(managerPhone);
			managerPhone.appendChild(el('div', {className: 'mb10', innerHTML: `Номер:&nbsp;`}));
			managerPhone.appendChild(el('div', {className: 'mb10', innerHTML: `<input type="text" id="newManagerPhone" value="${phone.number}">`}));
			managerPhone.appendChild(el('div', {className: 'mb10', innerHTML: `Описание:&nbsp;`}));
			managerPhone.appendChild(el('div', {className: 'mb10', innerHTML: `<input type="text" id="newManagerPhoneComment" value="${phone.comment}">`}));

		});

	} else {
		console.log('nope');
	}



	let managerNewPhone = el('div', {className: 'cherryBoard'});
	managerNewPhone.style.gridColumn = '1/-1';
	managerNewPhone.style.display = 'grid';
	managerNewPhone.style.margin = '5px auto';
	managerNewPhone.style.gridTemplateColumns = 'auto auto';
	row1.appendChild(managerNewPhone);
	managerNewPhone.appendChild(el('h3', {className: 'gridSpan C m10', innerHTML: `Новый номер телефона`}));
	managerNewPhone.appendChild(el('div', {className: 'mb10', innerHTML: `Номер:&nbsp;`}));
	managerNewPhone.appendChild(el('div', {className: 'mb10', innerHTML: `<input type="text" id="newManagerPhone">`}));
	managerNewPhone.appendChild(el('div', {className: 'mb10', innerHTML: `Описание:&nbsp;`}));
	managerNewPhone.appendChild(el('div', {className: 'mb10', innerHTML: `<input type="text" id="newManagerPhoneDiscription">`}));





	boxBody.appendChild(table);





	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let addBtn = el('button', {innerHTML: `Сохранить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			let error = false;


			if (!error) {
				let data = {
					action: 'editField',
					key: inputData.field,
					user: inputData.user,
					value: qs('#inputField').value
				};
				console.log(data);
				fetch('personal_IO.php', {
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
							if (typeof (jsn.newValue) !== 'undefined') {
								inputData.DOM.innerHTML = jsn.newValue;
							}
//							loadGoods(currentLevel);
						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				});//fetch

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