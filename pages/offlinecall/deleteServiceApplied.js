async function deleteServicesApplied(name, service) {
	let box = el('div', {className: 'modal neutral'});
	box.style.position = 'fixed';
	box.appendChild(el('h2', {innerHTML: `${name}, укажите причину удаления`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	document.body.appendChild(box);


	let variantsDiv = el('div', {innerHTML: `Загружаю варианты...`});
	boxBody.appendChild(variantsDiv);

	let cancelBtn = el('button', {innerHTML: rt(`Отмена`, `Хотя не...`, `В другой раз`, `Я ещё подумаю`)});
	box.appendChild(cancelBtn);
	cancelBtn.addEventListener('click', function () {
		box.parentNode.removeChild(box);
	});

	let variants = await fetch('/pages/reception/IO.php', {
		body: JSON.stringify({action: 'getDeleteReasons'}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			return jsn;
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch




	if (variantsDiv) {
		variantsDiv.innerHTML = '';
		if (variants.length) {
			let promise = new Promise(function (resolve, reject) {
				for (let variant of variants) {
					let btn = el('button', {className: 'buttonVariant', innerHTML: `${variant.name}`});
					variantsDiv.appendChild(btn);
					btn.addEventListener('click', function () {
						resolve(variant.id);
						box.parentNode.removeChild(box);
					});

				}
//				let btn = el('button', {className: 'buttonVariant', innerHTML: `Расподарочить`});
//				variantsDiv.appendChild(btn);
//				btn.addEventListener('click', function () {
//					resolve('notFree');
//					box.parentNode.removeChild(box);
//				});

			}
			);

			return promise;
		} else {
			variantsDiv.innerHTML = 'Не загрузилось...';
			return false;
		}
	}

}