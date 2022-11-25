async function suggestRemains(value, confirm = false) {
	clearElement(qs('#suggestions'));
	if (value !== '' && value.length > 2) {
		let n = 0;
		let services = await fetch('/pages/checkout/IO.php', {
			body: JSON.stringify({action: 'getServices', serviceName: value}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);


				if (jsn.msgs) {
					jsn.msgs.forEach(msg => {
						MSG(msg);
					});
				}
				return (jsn.services || []);
			} catch (e) {
				MSG(`Ошибка парсинга ответа сервера. <br><br><i>${e}</i><br>${text}`);
			}
		});


		let result = recursiveReduce(services, value);
		if (result.length === 1) {


			_candidate.idservices = result[0].idservices;
			_candidate.servicesName = result[0].name;

			_candidate.f_salesContentPrice = result[0].price;
			_candidate.f_salesContentPriceMin = result[0].priceMin || result[0].priceMax || result[0].price;
			_candidate.f_salesContentQty = 1;
			renderCandidate();
			clearElement(qs('#suggestions'));
			qs('#serviceSearch').value = '';
			return;
		} else if
				(result.length > 1) {
			clearElement(qs('#suggestions'));

			result.forEach(
					element => {
						n++;
						if (n <= 10) {
							if (confirm && pointer === n) {
//								GETreloc('service', element.idservices);
								_candidate.idservices = element.idservices;
								_candidate.servicesName = element.name;
								_candidate.f_salesContentPrice = element.price;
								_candidate.f_salesContentPriceMin = element.priceMin;
								_candidate.f_salesContentQty = 1;
								renderCandidate();
								clearElement(qs('#suggestions'));
								qs('#serviceSearch').value = '';
								return;
							}

							let li = el('li', {innerHTML: `<div class="mask${pointer === (n) ? ' pointed' : ''}"></div><span>${element.typeName || ''} </span>${element.r}`});
							li.addEventListener('click', function (event) {
//								console.log('element', element);
								_candidate.idservices = element.idservices;
								_candidate.servicesName = element.name;
								_candidate.f_salesContentPrice = element.price;
								_candidate.f_salesContentPriceMin = element.priceMin;
								_candidate.f_salesContentQty = 1;
								renderCandidate();
								clearElement(qs('#suggestions'));
								qs('#serviceSearch').value = '';
							});
							qs('#suggestions').appendChild(li);
						}
					});
		} else if (result.length === 1) {

		}

	}
	if (confirm) {
		clearElement(qs('#suggestions'));
}
}