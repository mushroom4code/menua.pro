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





let pointer = 0;
function suggest(value, confirm = false) {
	clearElement(qs('#suggestions'));
	if (value !== '') {
		let n = 0;
		let result = recursiveReduce(services, value);
		if (result.length >= 1) {
			result.forEach(
					element => {
						n++;
						if (n <= 10) {
							if (confirm && pointer === n) {
								GETreloc('add', element.idservices);
								clearElement(qs('#suggestions'));
								return;
							}
							let li = el('li', {innerHTML: `<div onclick="GETreloc('add',${element.idservices})" class="mask${pointer === (n) ? ' pointed' : ''}"></div><span>${element.typeName || ''} </span>${element.r}`});
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





async function saveTime(id, value, column) {
	fetch('IO.php', {
		body: JSON.stringify(
				{
					action: 'saveTime',
					id: id,
					value: value,
					column: column
				}
		),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	});
}

async function setGift(id, state) {
	fetch('IO.php', {
		body: JSON.stringify(
				{
					action: 'setGift',
					id: id,
					state: state
				}
		),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	});
}
async function setPersonal(id, personal) {
	fetch('IO.php', {
		body: JSON.stringify(
				{
					action: 'setPersonal',
					id: id,
					personal: personal
				}
		),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	});
}