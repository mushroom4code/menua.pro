async function checkBarcode(BC) {
	console.log('checkBarcode');
	let result = fetch('/pages/stocktaking/IO.php', {
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