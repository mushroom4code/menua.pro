
/* global qs, currentLevel, measuringUnits */

async function editField(inputData) {
	console.log('inputData', inputData);
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Редактировать`}));

	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);


	boxBody.appendChild(el('div', {className: 'title', innerHTML: {
			userLName: 'Фамилия',
			userFName: 'Имя',
			userMName: 'Отчество',
			login: 'Логин',
			password: 'Пароль',
			userPhone: 'Номер телефона',
			userBC: 'Личный код',
			usersBday: 'Дата рождения',
			userGroup: 'Группа',
			usersFinger: 'Отпечаток пальца',
			usersICQ: 'Рабочий номер ICQ',
			usersTG: 'Telegram ID',
			userPosition: 'Должность(и)'
		}[inputData.field]}));

	if (inputData.field === 'userLName' || inputData.field === 'userFName' || inputData.field === 'userMName' || inputData.field === 'userPhone' || inputData.field === 'userBC' || inputData.field === 'login' || inputData.field === 'password' || inputData.field === 'usersFinger' || inputData.field === 'usersICQ' || inputData.field === 'usersTG') {
		var input = el('input');
		input.type = 'text';
		input.id = 'inputField';
		input.value = inputData.value;
		boxBody.appendChild(input);
	}
	if (inputData.field === 'userBC') {
		let button = el('button');
		button.innerHTML = 'Создать';
		button.addEventListener('click', function () {
			input.value = RDS(16, true);
		});
		boxBody.appendChild(button);
	}


	if (inputData.field === 'usersBday') {
		var input = el('input');
		input.type = 'date';
		input.id = 'inputField';
		input.value = inputData.value;
		boxBody.appendChild(input);
	}
	if (inputData.field === 'userGroup') {
		let groups = await fetch('personal_IO.php', {
			body: JSON.stringify({getGroups: true}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				return jsn.groups;
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});//fetch
		let select = el('select');
		select.id = 'inputField';

		select.appendChild(new Option());

		groups.forEach(elem => {
			select.appendChild(new Option(elem.name, elem.id, inputData.value == elem.id, inputData.value == elem.id));

		});


		boxBody.appendChild(select);
	}







	if (inputData.field === 'userPosition') {

		let checked = JSON.parse(inputData.value);
		console.log(checked);
		let positions = await fetch('personal_IO.php', {
			body: JSON.stringify({getPositions: true}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				return jsn.positions;
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});//fetch

		let select = el('div');
		select.style.textAlign = "left";

		positions.sort((a, b) => {
			if (a.name.toLowerCase() > b.name.toLowerCase()) {
				return 1;
			}
			if (a.name.toLowerCase() < b.name.toLowerCase()) {
				return -1;
			}
			return 0;
		});

		positions.forEach(elem => {
			let wrap = el('div');
			select.appendChild(wrap);
			let CB = el('input');
			CB.type = "checkbox";
			CB.id = `pos_${elem.id}`;
			CB.value = elem.id;
			CB.checked = checked.indexOf(elem.id) > -1;
			let label = el('label');
			label.innerHTML = elem.name;
			label.htmlFor = `pos_${elem.id}`;
			wrap.appendChild(CB);
			wrap.appendChild(label);

		});
		boxBody.appendChild(select);
	}




	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let addBtn = el('button', {innerHTML: `Сохранить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			let error = false;
			let value;
			if (inputData.field === 'userPosition') {
				let selected = [];
				boxBody.querySelectorAll(':checked').forEach(CB => {
					selected.push(CB.value);
				});
				console.log(selected);
				value = selected;
			} else {
				value = qs('#inputField').value;
			}
			if (!error) {
				let data = {
					action: 'editField',
					key: inputData.field,
					user: inputData.user,
					value: value
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
							GR();
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
;