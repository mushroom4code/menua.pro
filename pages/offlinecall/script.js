/* global qs */

async function addClient() {
	if (!qs('#clientGenderF').checked && !qs('#clientGenderM').checked) {
		MSG('Укажите пол клиента');
		return false;
	}
	if (qs('#phone').value.trim() == '') {
		MSG('Укажите телефон клиента');
		return false;
	}
	if (qs('#clientLName').value.trim() == '' || qs('#clientFName').value.trim() == '') {
		MSG('Укажите ФАМИЛИЮ И ИМЯ клиента');
		return false;
	}


	let data = {
		action: 'addNewClient',
		lastname: qs('#clientLName').value.trim(),
		firstname: qs('#clientFName').value.trim(),
		middlename: qs('#clientMName').value.trim(),
		gender: qs('#clientGenderF').checked ? '0' : (qs('#clientGenderM').checked ? '1' : null),
		birthday: qs('#clientBDate').value.trim(),
		clientsPhone: qs('#phone').value.trim(),
		idclientsSources: qs('#idclientsSources').value.trim(),
		comment: qs('#comment').value.trim()
	};
	console.log(data);
	if (data.lastname !== '' && data.firstname !== '') {// && data.acardnumber !== ''

		fetch('IO.php', {
			body: JSON.stringify(data),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);

				if (jsn.success && jsn.client) {
					window.location.href = `/pages/offlinecall/schedule.php?client=${jsn.client}`;
				}
				if (jsn.msgs) {
					jsn.msgs.forEach(msg => {
						MSG(msg);
					});
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});//fetch

	} else {
		MSG('Должны быть указаны <br><br><div style="text-align: left;">1.Фамилия <br>2.Имя</div>');
	}
	console.log(data);

}

async function checkPhone(phone) {
	if (`${phone}`.length >= 7) {
		console.log(`${phone}`);
		let response = await fetch('IO.php', {
			body: JSON.stringify({clientsByPhone: phone}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				return jsn;
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}

		}); //fetch

		if (response.clients.length === 1) {
			if (response.clients[0].idclients) {
				window.location.href = `/pages/offlinecall/addnewclient.php?idclients=${response.clients[0].idclients}`;
			} else {
				qs('#clientLName').value = '';
				qs('#clientFName').value = '';
				qs('#clientMName').value = '';
				qs('#clientLName').value = (response.clients[0].clientsLName || 'Не заполнено');
				qs('#clientFName').value = (response.clients[0].clientsFName || 'Не заполнено');
				qs('#clientMName').value = (response.clients[0].clientsMName || 'Не заполнено');
			}

		} else {

		}
		console.log(response.clients);
	}
}




async function suggest(value, confirm = false) {
	clearElement(qs('#suggestions'));
	if (value !== '' && value.length > 2) {
		let n = 0;
		let services = await fetch('IO.php', {
			body: JSON.stringify({action: 'getServices', serviceName: value}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);


				if (jsn.msgs) {
					jsn.msgs.forEach(msg => {
						MSG(msg);
					});
				}
				return (jsn.services || []);
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});


		let result = recursiveReduce(services, value);
		if (result.length === 1) {
			clearElement(qs('#suggestions'));
			result[0].idclients = _client;
			renderUnsortedAdd(result[0]);
		} else if
				(result.length > 1) {
			clearElement(qs('#suggestions'));
			result.forEach(
					element => {
						n++;
						if (n <= 10) {
							if (confirm && pointer === n) {
//								GETreloc('service', element.idservices);
								element.idclients = _client;
								renderUnsortedAdd(element);
								clearElement(qs('#suggestions'));
								return;
							}

							let li = el('li', {innerHTML: `<div class="mask${pointer === (n) ? ' pointed' : ''}"></div><span>${element.typeName || ''} </span>${element.r}`});
							li.addEventListener('click', function (event) {
								element.idclients = _client;
								renderUnsortedAdd(element);
								clearElement(qs('#suggestions'));
							});
							qs('#suggestions').appendChild(li);
						}
					});
		} else if (result.length === 1) {

		}

	}
	if (confirm) {
		clearElement(qs('#suggestions'));
}
}




function renderUnsortedAdd(element) {
	console.log('renderUnsortedAdd', element);
	clearElement(qs('#unsortedPillsAddWrapper'));
	clearElement(qs('#unsortedPillsAddName'));

	qs('#unsortedPillsAddWrapper').appendChild(pill(element));
	qs('#unsortedPillsAddName').appendChild(el('div', {innerHTML: `${element.name}`}));
}

function recursiveReduce(inputArray = [], str = '') {
//	console.log('recursiveReduce.js');
	str = str.trim();
	let strArr = str.toString().split(" ".toString());
	let res = [];
	for (let bit of strArr) {
		res[res.length] = "(" + bit + ")";
	}

	for (let item of inputArray) {
		if (str !== '' && res.length > 0) {
			let skip = false;
			item.r = '';
			//	console.log('item', item);
			for (let bit of res) {
//	console.log(bit);
				let reg = new RegExp(bit, 'gi');
				if (!item.hasOwnProperty('name') || !item.name || !item.name.toString().match(reg)) {
					skip = true;
				} else {
					item.r = (item.r || item.name).toString().replace(reg, function (str) {//itemsName
						return '<b class="red">' + str + '</b>';
					});
				}
			}
			if (!skip) {
				item.marked = true;
			} else {
				item.marked = false;
			}
		}

	}

	return inputArray.filter(el => {
		return el.marked == true;
	});
}
