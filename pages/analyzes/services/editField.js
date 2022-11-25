
/* global qs, currentLevel, measuringUnits */

async function editField(inputData) {
	console.log('inputData', inputData);
	let box = el('div', {className: 'modal neutral'});
	box.style.position = 'fixed';
	if (inputData.field == 'TPS_costsValue') {
		box.appendChild(el('h2', {innerHTML: `Стоимость`}));
	}
	if (inputData.field == 'TPS_pricesValue') {
		box.appendChild(el('h2', {innerHTML: `Цена`}));
	}


	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);

	var wrapper = el('div');
	wrapper.style.display = 'grid';
	wrapper.style.gridTemplateColumns = 'auto auto';
	wrapper.style.gridGap = '10px';


	boxBody.appendChild(wrapper);

//field: "TPS_costsValue"
//service: 7202
//value: 620
//
	let summValue = el('input');
	summValue.type = 'text';
	summValue.addEventListener('input', digon);
	summValue.value = inputData.value;
	wrapper.appendChild(summValue);

	let promise = new Promise(function (resolve, reject) {
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
					action: inputData.field,
					summValue: summValue.value,
					TPservice: inputData.service
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
							if (typeof (jsn.newValue) !== 'undefined') {
								inputData.DOM.innerHTML = jsn.newValue;
							}
							resolve(false);
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
