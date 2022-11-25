function sendSMS(inputData) {

	console.log('inputData', inputData);
	let box = el('div', {className: 'modal neutral'});
	box.style.position = 'fixed';
	box.appendChild(el('h2', {innerHTML: `Отправить SMS`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	var wrapper = el('div');
	wrapper.style.display = 'grid';
	wrapper.style.gridTemplateColumns = 'auto auto';
	wrapper.style.gridGap = '10px';
	boxBody.appendChild(wrapper);
//
	let dateName = el('div');
	dateName.innerHTML = `Номер телефона:`;
	wrapper.appendChild(dateName);
	let dateValue = el('select');
	if (typeof (inputData.phones) === 'object') {
		for (let idphones in inputData.phones) {
//			console.log(idphones, inputData.phones[idphones]);
			if (inputData.showPhoneNumbers) {
				dateValue.appendChild(new Option(`${inputData.phones[idphones]}`, idphones, false, false));
			} else {
				dateValue.appendChild(new Option(`...${inputData.phones[idphones].substr(-4)}`, idphones, false, false));
			}
		}
	}
	wrapper.appendChild(dateValue);
//
	let smsTemplateWrapper = el('div');
	smsTemplateWrapper.innerHTML = `Шаблон смс:`;
	wrapper.appendChild(smsTemplateWrapper);
	let smsTemplateValue = el('select');
	if (typeof (inputData.templates) === 'object' && inputData.templates.length > 0) {
//		console.error(inputData.templates);
		for (let idtemplate in inputData.templates) {

			smsTemplateValue.appendChild(new Option(inputData.templates[idtemplate].name, inputData.templates[idtemplate].id, false, false));
		}
	}
	wrapper.appendChild(smsTemplateValue);
	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let cancelBtn = el('button', {innerHTML: `Отмена`});
		cancelBtn.style.margin = '0px 10px';
		box.appendChild(cancelBtn);
		cancelBtn.addEventListener('click', function () {
			box.parentNode.removeChild(box);
			resolve(false);
		});
		let addBtn = el('button', {innerHTML: `Послать`});
		box.appendChild(addBtn);
		addBtn.style.margin = '0px 10px';
		addBtn.addEventListener('click', async function () {
			let error = false;
			if (!error) {
				inputData.phone = dateValue.value;
				inputData.smsTemplate = smsTemplateValue.value;
				console.log(inputData);
				await fetch('/sync/api/sms/index.php', {
					body: JSON.stringify(inputData),
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
						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				}); //fetch
			}
		});
	});
	document.body.appendChild(box);
	return promise;
}