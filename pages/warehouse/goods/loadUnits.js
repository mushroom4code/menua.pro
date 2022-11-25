let measuringUnits = [];
let loadUnits = async function () {
	await fetch('/pages/warehouse/goods/goods_IO.php', {
		body: JSON.stringify({getUnits: true}),
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
			if (jsn.units) {
				measuringUnits = jsn.units;
			}
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch
};