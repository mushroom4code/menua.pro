
/* global qs, currentLevel, measuringUnits */

async function editField(inputData) {
	console.log('inputData', inputData);
	let box = el('div', {className: 'modal neutral'});
	if (inputData.moveFrom) {
		box.appendChild(el('h2', {innerHTML: `Перенести на`}));
	}
	if ((inputData.action || '') === 'saveAppliedServices') {
		box.appendChild(el('h2', {innerHTML: `Укажите период:`}));
	}


	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);

	var wrapper = el('div');
	wrapper.style.display = 'grid';
	wrapper.style.gridTemplateColumns = 'auto auto';
	wrapper.style.gridGap = '10px';


	boxBody.appendChild(wrapper);


//
	if (inputData.moveFrom) {
		var dateName = el('div');
		dateName.innerHTML = `Дата:`;
		wrapper.appendChild(dateName);

		var dateValue = el('input');
		dateValue.type = 'date';
		dateValue.min = inputData.mindate;

		dateValue.value = inputData.moveFrom;
		wrapper.appendChild(dateValue);
	}

	if ((inputData.action || '') === 'saveAppliedServices') {
		var dateNameFrom = el('div');
		dateNameFrom.innerHTML = `C:`;
		wrapper.appendChild(dateNameFrom);
		var dateValueFrom = el('input');
		dateValueFrom.type = 'date';
		dateValueFrom.value = inputData.moveFrom;
		wrapper.appendChild(dateValueFrom);

		var dateNameTo = el('div');
		dateNameTo.innerHTML = `По:`;
		wrapper.appendChild(dateNameTo);
		var dateValueTo = el('input');
		dateValueTo.type = 'date';
		dateValueTo.value = inputData.moveFrom;
		wrapper.appendChild(dateValueTo);



	}




	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

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
				let data;
				if (inputData.moveFrom) {
					data = {
						action: 'moveTheDate',
						moveFrom: inputData.moveFrom,
						moveTo: dateValue.value,
						servicesAppliedClient: inputData.servicesAppliedClient
					};
				}
				if ((inputData.action || '') === 'saveAppliedServices') {
					GR({action: 'saveAppliedServices',
						dateFrom: dateValueFrom.value,
						dateTo: dateValueTo.value});
					return false;

				}


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
							GETreloc('date', dateValue.value);
						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				});//fetch
			}
		});
	});
	document.body.appendChild(box);
	return promise;
}
