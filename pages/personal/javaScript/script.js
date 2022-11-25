
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
async function suggest(value, confirm = false) {
	clearElement(qs('#suggestions'));
	if (value !== '' && value.length > 2) {
		let n = 0;
		let services = await fetch('/pages/offlinecall/IO.php', {
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
		if (result.length >= 1) {
			clearElement(qs('#suggestions'));
			result.forEach(
					element => {
						n++;
						if (n <= 10) {
							if (confirm && pointer === n) {
								changeServices({state: true, service: element.idservices, user: (new URL(window.location.href)).searchParams.get("employee"), action: 'includeSrevice'});
								clearElement(qs('#suggestions'));
								return;
							}

							let li = el('li', {innerHTML: `<div class="mask${pointer === (n) ? ' pointed' : ''}"></div><span>${element.typeName || ''} </span>${element.r}`});
							li.addEventListener('click', function (event) {
								changeServices({state: true, service: element.idservices, user: (new URL(window.location.href)).searchParams.get("employee"), action: 'includeSrevice'});
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


function changeServices(data) {
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
				}
			}
			if ((jsn).success && data.action === 'includeSrevice' && data.state === true) {
				GR();
			}
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	});//fetch
}