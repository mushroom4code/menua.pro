async function setRights(params) {
	let user = params.user;
	let rule = params.right;
	setTimeout(function () {
		let rulevalue = qs(`#rule_${rule}`).checked ? 1 : 0;
		fetch('personal_IO.php', {
			body: JSON.stringify({
				user: user,
				rule: rule,
				rulevalue: rulevalue
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

			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});//fetch
	}, 10);


}