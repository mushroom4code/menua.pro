async function searchWindow() {
	console.log('searchWindow');
	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Поиск`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	let table = el('div');
	boxBody.appendChild(table);
	let inputElement = el('input');
	inputElement.type = 'text';
	table.appendChild(inputElement);
	let searchResults = el('div');
	searchResults.id = 'searchResults';
	searchResults.className = 'itemsSearchResultsTable';

	table.appendChild(searchResults);




	inputElement.addEventListener('input', (event) => {
		console.log(inputElement.value);
		clear(searchResults);
		if (inputElement.value.length >= 3) {
			fetch('/pages/warehouse/goods/goods_IO.php', {
				body: JSON.stringify({action: 'searchItem', search: inputElement.value.trim()}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);
					if ((jsn.items || []).length > 0) {
						jsn.items.forEach((item) => {
							let reg = new RegExp("(" + inputElement.value + ")", 'gi');
							item.WH_goodsName = item.WH_goodsName.replace(reg, function (str) {//itemsName
								return '<b style="color: pink;">' + str + '</b>';
							});
							searchResults.appendChild(el('div', {innerHTML: `<div><a href="/pages/warehouse/goods/?dir=${item.idparents || 'null'}">${item.parentsName || ''}</a></div><div><a href="/pages/warehouse/goods/item/?item=${item.idWH_nomenclature}">${item.WH_goodsName || ''}</div>`}));
						});
					}

				} catch (e) {
					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
			});
		}
	});

	let promise = new Promise(function (resolve, reject) {
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