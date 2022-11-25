async function searchCoordsByLastName(lastname) {
	if (lastname.length < 3) {
		return false;
	}
	qs('#coordsSuggestions').innerHTML = '';
	fetch('/pages/checkout/IO.php', {
		body: JSON.stringify({
			action: 'coordsSuggestions',
			lastname: lastname
		}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if ((jsn.coords || []).length) {
				jsn.coords.forEach(coord => {
					let li = el('li', {innerHTML: `<div class="mask"></div><span>${coord.lname || ''} ${coord.fname || ''} ${coord.mname || ''}</span>`});
					li.addEventListener('click', function () {
						addCoord(coord);
						qs('#coordsSuggestions').innerHTML = '';
						qs('#coordinatorsLname').value = '';
					});
					qs('#coordsSuggestions').appendChild(li);
				});
			}
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
