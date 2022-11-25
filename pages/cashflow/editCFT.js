
/* global qs, currentLevel, measuringUnits */

async function editCFT(inputData) {
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

	let cmtName = el('div');
	cmtName.innerHTML = `ДДС (${inputData.cft}): `;
	wrapper.appendChild(cmtName);

	let cmtValue = el('input');
	cmtValue.type = 'text';
	cmtValue.id = 'inputField';
	cmtValue.value = inputData.cftName;
	wrapper.appendChild(cmtValue);


	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it
//
//		let deleteBtn = el('button', {innerHTML: `Удалить`});
//		deleteBtn.style.margin = '0px 10px';
//		box.appendChild(deleteBtn);
//		deleteBtn.style.backgroundColor = 'pink';
//		deleteBtn.addEventListener('click', function () {
//			deleteCFentry(inputData.id);
//			box.parentNode.removeChild(box);
//			resolve(false);
//		});
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
			let data = {
				action: 'editCFT',
				key: inputData.cft,
				cftype: cmtValue.value
			};
			console.log(data);
			fetch('IO.php', {
				body: JSON.stringify(data),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);

					if (jsn.success) {
						box.parentNode.removeChild(box);
						resolve(false);
						GETreloc('rnd', Math.random());

					}
				} catch (e) {
					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
			});//fetch

		});
	});

	document.body.appendChild(box);
	cmtValue.focus();
	return promise;

}
