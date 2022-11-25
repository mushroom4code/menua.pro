/* global digon */

function returnWindow(inputdata) {
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Оформление разврата договора №${inputdata.idfsale}`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	let table = el('div', {className: 'addItemsTable'});
	boxBody.appendChild(table);
	let tr1 = el('div');
	tr1.appendChild(el('div', {innerHTML: `Дата:`}));
	let td1 = el('div');
	let date = el('input');
	date.type = 'date';
	tr1.appendChild(td1);
	td1.appendChild(date);
	table.appendChild(tr1);
	let tr2 = el('div');
	tr2.appendChild(el('div', {innerHTML: `Сумма к возврату:`}));
	let td2 = el('div');
	tr2.appendChild(td2);
	let returnSumm = el('input');
	returnSumm.type = 'text';
	returnSumm.value = `${inputdata.summ}`;
	returnSumm.addEventListener('input', digon);
	returnSumm.id = 'returnSumm';
	td2.appendChild(returnSumm);
	returnSumm.focus();
	table.appendChild(tr2);
	let tr3 = el('div');

	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let addBtn = el('button', {innerHTML: `Возврат`});
		addBtn.style.marginRight = '20px';
		addBtn.style.backgroundColor = 'hsl(0,100%,90%)';
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			let error = false;

			if (!error && !date.value.trim()) {
				MSG(`Укажите дату возврата`);
				error = true;
				date.focus();
			}

			if (!error && !returnSumm.value.trim()) {
				MSG(`Сумму к возврату`);
				error = true;
			}

			if (!error) {
				let data = {
					sale: inputdata.idfsale,
					action: 'saleCancelation',
					date: date.value,
					summ: returnSumm.value
				};
				console.log(data);
				fetch('IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
						if ((jsn.msgs || []).length) {
							for (let msge of jsn.msgs) {
								await MSG(msge);
							}
						}
						if (jsn.success) {
							box.parentNode.removeChild(box);
							resolve(true);
							GETreloc();
						} else {
							resolve(false);
						}
					} catch (e) {
						MSG(`Ошибка парсинга ответа сервера. <br><br><i>${e}</i><br>${text}`);
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
	returnSumm.focus();
	return promise;
}