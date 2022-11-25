function fileUpload(parent = null) {
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Выбрать файл`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	boxBody.appendChild(el('div', {className: 'title', innerHTML: `Название`}));
	var input = el('input');
	input.type = 'file';
	input.id = 'inputField';
	boxBody.appendChild(input);
	let promise = new Promise(function (resolve, reject) {
		let addBtn = el('button', {innerHTML: `Загрузить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {
			var input = document.querySelector('input[type="file"]');
			var data = new FormData();
			data.append('file', input.files[0]);
			data.append('parent', parent);

			fetch('/pages/files/IO.php', {
				method: 'POST',
				body: data
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