async function sendOrder(supplier, selectEmail = false) {
	console.log('sendOrder', supplier);
	let nothingTobuy = true;
	let toBuy = [];
	let emailFrom;
	if (selectEmail) {
		emailFrom = await selectSender(selectEmail);
		if (!emailFrom) {
			return false;
		}
	} else {
		emailFrom = null;
	}


	qsa(`input[data-suppliertobuy="${supplier}"]`).forEach(elem => {
		if (elem.value !== '' && elem.value > 0) {
			toBuy.push({id: elem.dataset.itemtobuy, qty: elem.value});
		}
	});
	if (toBuy.length > 0) {
		let result = await fetch('/pages/warehouse/goods/goods_IO.php', {
			body: JSON.stringify({action: 'sendOrder', emailFrom: emailFrom, supplier: supplier, items: toBuy}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if (jsn.success) {
					qsa(`input[data-supplier="${supplier}"]`).forEach(elem => {
						elem.checked = false;
					});
					qs(`#supplier_${supplier}`).checked = false;
					qsa(`input[data-suppliertobuy="${supplier}"]`).forEach(elem => {
						elem.value = '';
					});
					if (jsn.items) {
						jsn.items.forEach(item => {
							qs(`#s_${supplier}_i_${item.id}_exqty`).innerHTML = (parseFloat(qs(`#s_${supplier}_i_${item.id}_exqty`).innerHTML) || 0) + parseFloat(item.qty);
							qs(`#s_${supplier}_i_${item.id}_exu`).innerHTML = item.u;
						});
					}
				}
				if ((jsn.msgs || []).length) {
					for (let msg of jsn.msgs) {
						let data = await MSG(msg);
					}
				}
			} catch (e) {
				MSG("27Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		}); //fetch
	} else {
		MSG(rt('А что заказывать-то?', 'Заказ пуст', 'Нет позиций для заказа', 'Давай попробуем ещё разок,<br> но в этот раз заполним нужные поля.'));
	}



	console.log('toBuy', toBuy);
}

async function selectSender(inputData) {
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Выбрать отправителя`}));
	console.log('inputData', inputData);
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);


	let select = el('select');
	select.id = 'inputField';
	inputData.forEach(element => {
		select.appendChild(new Option(element.text, element.value));
	});
	boxBody.appendChild(select);



	let promise = new Promise(function (resolve, reject) {
		let addBtn = el('button', {innerHTML: `Отправить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			box.parentNode.removeChild(box);
			resolve(select.value);

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
