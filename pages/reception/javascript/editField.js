
/* global qs, currentLevel, measuringUnits */

async function editField(inputData) {
	console.log('inputData', inputData);
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Перенести${inputData.servicesApplied.length > 1 ? ' всё' : ''} на`}));

	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);

	var wrapper = el('div');
	wrapper.style.display = 'grid';
	wrapper.style.gridTemplateColumns = 'auto auto';
	wrapper.style.gridGap = '10px';


	boxBody.appendChild(wrapper);


//
	let dateName = el('div');
	dateName.innerHTML = `Дата:`;
	wrapper.appendChild(dateName);

	let dateValue = el('input');
	dateValue.type = 'date';

	dateValue.value = inputData.moveFrom;
	wrapper.appendChild(dateValue);

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
				let data = {
					action: 'moveTheDate',
					moveFrom: inputData.moveFrom,
					moveTo: dateValue.value,
					servicesApplied: inputData.servicesApplied
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
