function loadReportForDate(date) {
	qs('#dayReport').innerHTML = '';
	if (!date) {
		return false;
	}


	fetch('/pages/stocktaking/IO.php', {
		body: JSON.stringify({
			action: (qs('#stocktaking') || {}).checked ? 'stocktaking' : 'getIn',
			date: date}),
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
			if ((jsn.entries || []).length > 0) {
				drowList(date, jsn);
			} else {

			}
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	});
}