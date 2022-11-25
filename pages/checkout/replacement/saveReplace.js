async function saveReplace() {
//	if (!_toRemove.length && !_toAppend.length) {
//		await MSG(rt('А чё менять то?', 'Нечего менять.'));
//		return false;
//	}

//	if (!_coordinators.length) {
//		await MSG(rt('Укажите координаторов, пожалуйста.', 'Надо указать ответственного координатора.'));
//		return false;
//	}


	fetch('/pages/checkout/IO.php', {
		body: JSON.stringify({
			action: 'saveReplace',
			data: {
				contract: _contract,
				comment: _comment,
				coordinators: _coordinators,
				toAppend: _toAppend,
				toRemove: _toRemove
			}
		}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if ((jsn.msgs || []).length) {
				for (let msge of jsn.msgs) {
					await MSG(msge);
				}
			}
		} catch (e) {
			MSG(`Ошибка парсинга ответа сервера. <br><br><i>${e}</i><br>${text}`);
		}
	}); //fetch
}