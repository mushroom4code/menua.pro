function startdrag(event, data) {
	for (let div of qsa('[data-service]')) {
		if (div.dataset.service != data.servicesAppliedService) {
			div.classList.add('unavaileble');
			div.classList.remove('availeble');
		} else {
			div.classList.add('availeble');
			div.classList.remove('unavaileble');
		}
	}
	event.dataTransfer.setData("data", JSON.stringify(data));
}
function drgover(event, data) {
	let tData = JSON.parse(event.dataTransfer.getData("data"));
	if (data.f_salesContentService == (tData.servicesAppliedService || false) && (tData.servicesAppliedQty <= data.f_salesContentQty - data.done) && (data.f_salesContentQty > 0)) {
		event.target.classList.add('highlighted');
	} else {
		event.target.classList.add('highlightedRed');
	}

	event.stopPropagation();
	event.preventDefault();
	return false;


}
function drop(event, data) {
	event.target.classList.remove('highlighted');
	let source = JSON.parse(event.dataTransfer.getData("data"));
	let target = data;

	console.log(source, target);
	if ((target || {}).action === 'makeitfree') {
		fetch('IO.php', {
			body: JSON.stringify({source: source, target: target}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if (jsn.success) {
					GR({empty: null});
				} else {
					MSG('Ошибка...');
				}
			} catch (e) {
				//	MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		}); //fetch

	} else if (data.f_salesContentService == (source.servicesAppliedService || false) && (source.servicesAppliedQty <= data.f_salesContentQty - data.done) && (data.f_salesContentQty > 0)) {
//		console.log(source, target);
		fetch('IO.php', {
			body: JSON.stringify({source: source, target: target}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if (jsn.success) {
					GR({empty: null});
				} else {
					MSG('Ошибка...');
				}
			} catch (e) {
				//	MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		}); //fetch
	} else {
		console.log('Not available');
	}
}