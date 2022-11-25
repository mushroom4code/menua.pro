let BCminLength = 5;
document.addEventListener("DOMContentLoaded", function () {
//			console.log('autofocus.js');
	let hiddeninput = el('input');
	hiddeninput.type = 'hidden';
	hiddeninput.id = 'hiddeninput';
	document.body.appendChild(hiddeninput);

	let AFEL = qs('#hiddeninput');
	let start = performance.now();
	let  intId = false;
	document.addEventListener('keypress', async function (e) {
		let interval = performance.now() - start;
		//	console.log(interval, e);

		start = performance.now();

		if (document.activeElement === document.body) {
			if (interval > 70) {
				AFEL.value = '';
				AFEL.value = AFEL.value + filterKeys(e.key);
			} else {
				if (e.keyCode !== 13) {
					AFEL.value = AFEL.value + filterKeys(e.key);
					if (intId) {
						clearTimeout(intId);
					} else {
//					console.log(typeof (intId));
					}
					intId = setTimeout(function () {
						makeRequest(AFEL);
					}, 100);

				} else {
					if (intId) {
						clearTimeout(intId);
					}
					makeRequest(AFEL);
				}
			}
		}

	});
});

async function makeRequest(AFEL) {
	if (AFEL.value.length > BCminLength) {
		let check = await checkBarcode(AFEL.value);
		if ((check == false || check.result === false) && !(check.msgs || []).length) {
			addItemWindow(AFEL.value);
		}
		if ((check.msgs || []).length) {
			check.msgs.forEach(async msg => {
				await MSG(msg);
			});
		}
		if ((((check || {}).result || {}).WH_goodsName || '').length > 0) {
			MSG({type: 'success', text: `<div style="min-width: 200px;">${rt('Так это же:', 'Есть уже такое:', 'Это мне знакомо, это:', 'Уже внесено в базу данных:', 'Есть такое:', 'Как, опять?')}<br><br><a href="/pages/warehouse/goods/item/?item=${check.result.WH_goodsNomenclature}">${check.result.WH_goodsName}</a></div>`});
		}
	}
}


function checkBarcode(BC) {
	let result = fetch('goods_IO.php', {
		body: JSON.stringify({action: 'checkBC', BC: BC}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			return jsn;
		} catch (e) {
			return false;//	MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch
	return result;
}