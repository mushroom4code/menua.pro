
/* global qs, currentLevel, measuringUnits */

async function editField(inputData) {
	console.log('inputData', inputData);
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Редактировать`}));

	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);

	var wrapper = el('div');
	wrapper.style.display = 'grid';
	wrapper.style.gridTemplateColumns = 'auto auto';
	wrapper.style.gridGap = '10px';


	boxBody.appendChild(wrapper);

	let summName = el('div');
	summName.innerHTML = `Сумма:`;
	wrapper.appendChild(summName);

	let summValue = el('input');
	summValue.type = 'text';
	summValue.value = inputData.summ;
	wrapper.appendChild(summValue);


	let cmtName = el('div');
	cmtName.innerHTML = `Назначение:`;
	wrapper.appendChild(cmtName);

	let cmtValue = el('input');
	cmtValue.type = 'text';
	cmtValue.value = inputData.comment;
	wrapper.appendChild(cmtValue);



	let ddss = await fetch('IO.php', {
		body: JSON.stringify({getDDSS: true}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			return jsn;
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	});

	ddss.sort((a, b) => {
		if (a.cashFlowTypeName.toLowerCase() > b.cashFlowTypeName.toLowerCase()) {
			return 1;
		}
		if (a.cashFlowTypeName.toLowerCase() < b.cashFlowTypeName.toLowerCase()) {
			return -1;
		}
		return 0;
	});




	let ddssName = el('div');
	ddssName.innerHTML = `ДДС:`;
	wrapper.appendChild(ddssName);

	let ddssValue = el('select');

	wrapper.appendChild(ddssValue);
	ddss.forEach(element => {
		ddssValue.appendChild(new Option(element.cashFlowTypeName, element.idcashFlowType, element.idcashFlowType == inputData.cft, element.idcashFlowType == inputData.cft));
	});

	let dateName = el('div');
	dateName.innerHTML = `Дата:`;
	wrapper.appendChild(dateName);

	let dateValue = el('input');
	dateValue.type = 'date';

	dateValue.value = inputData.date;
	wrapper.appendChild(dateValue);




	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let deleteBtn = el('button', {innerHTML: `Удалить`});
		deleteBtn.style.margin = '0px 10px';
		box.appendChild(deleteBtn);
		deleteBtn.style.backgroundColor = 'pink';
		deleteBtn.addEventListener('click', function () {
			deleteCFentry(inputData.id);
			box.parentNode.removeChild(box);
			resolve(false);
		});
		let cancelBtn = el('button', {innerHTML: `Отмена`});
		cancelBtn.style.margin = '0px 10px';
		box.appendChild(cancelBtn);
		cancelBtn.addEventListener('click', function () {
			box.parentNode.removeChild(box);
			resolve(false);
		});
		let addBtn = el('button', {innerHTML: `Сохранить`});
		box.appendChild(addBtn);
		addBtn.style.margin = '0px 10px';
		addBtn.addEventListener('click', async function () {
			let error = false;
			if (!error) {
				let data = {
					action: 'editCF',
					key: inputData.id,
					summ: summValue.value,
					comment: cmtValue.value,
					cftype: ddssValue.value,
					date: dateValue.value
				};
				console.log(data);
				await fetch('IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
						if ((jsn.msgs || []).length) {
							jsn.msgs.forEach(async msg => {
								await MSG(msg);
							});
						}

						if (jsn.success) {
							box.parentNode.removeChild(box);
							resolve(false);
							GETreloc('rnd', Math.random());

						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				});//fetch
			}
		});
	});

	document.body.appendChild(box);
	summValue.focus();
	return promise;

}
