function getClientWindow() {
	console.log('getClientWindow');
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Клиент`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);



	boxBody.appendChild(el('div', {innerHTML: `
<div  style="">

</div>
`}));




	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let addBtn = el('button', {innerHTML: `Добавить`});
		box.appendChild(addBtn);
		addBtn.addEventListener('click', async function () {

			let error = false;

			if (!error) {
				let data = {};
				console.log(data);
				fetch('/pages/goods/goods_IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
						if ((jsn.msgs || []).length) {

							if (jsn.success) {
								box.parentNode.removeChild(box);
								resolve(true);
								if (typeof (loadGoods) !== 'undefined') {
									loadGoods(currentLevel);
								}
							} else {
								resolve(false);
							}

							for (let msge of jsn.msgs) {
								await MSG(msge);
							}
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