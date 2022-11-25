let loadSuppliers = async function ()
{
	let suppliers = [];
	console.log('loadSuppliers');
	suppliers = await fetch('/pages/suppliers/IO.php', {
		body: JSON.stringify({
		}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if ((jsn.msgs || []).length) {
				for (let msg of jsn.msgs) {
					await MSG(msg);
				}
			}
			return jsn.suppliers || [];
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	});
	return suppliers;
};