function addFolder(parent = null) {
	console.log('addFolder', parent);
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Создать папку`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	boxBody.appendChild(el('div', {className: 'title', innerHTML: `Название`}));
	var input = el('input');
	input.type = 'text';
	input.id = 'inputField';
	boxBody.appendChild(input);
	let promise = new Promise(function (resolve, reject) {
		let addBtn = el('button', {innerHTML: `Создать`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			let error = false;
			if (!error) {
				let data = {
					action: 'addFolder',
					parent: parent,
					name: ((qs('#inputField') || {}).value || '')
				};
				console.log(data);
				fetch('/pages/files/IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
						if ((jsn.msgs || []).length) {
							for (let msg of jsn.msgs) {
								let data = await MSG(msg);
								if (data === true) {
									//								reset form
								}
								console.log(data);
							}
						}
						if (jsn.success) {
							box.parentNode.removeChild(box);
							resolve(false);
							document.location.reload();
						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
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
	return promise;
}