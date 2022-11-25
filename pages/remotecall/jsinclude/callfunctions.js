function addToPool(obj) {
	let index = app.poolArray.indexOf(obj.service);
	if (index === -1) {//order[1]-kitchen
		app.poolArray.push(obj.service);
	}
	clearElement(qs('#suggestions'));
	app.selectedService = obj.service;
	window.localStorage.setItem('poolArray', JSON.stringify(app.poolArray));
	document.querySelector('#serviceSearch').value = '';
}

let pointer;

function suggest(value, confirm = false) {
	console.log(value, pointer, confirm);
	clearElement(qs('#suggestions'));
	if (value !== '') {
		let n = 0;
		let result = recursiveReduce(app.services, value);
		if (result.length > 0) {
			result.forEach(
					element => {
						n++;
						if (n <= 40) {
							if (confirm && pointer === n) {
								addToPool({service: element.id});
								//	addToSubscriptionContent({service: element.id});
								clearElement(qs('#suggestions'));
								return;
							}
							let li = el('li', {innerHTML: `<div data-function="addToPool" data-service="${element.id}" class="mask${pointer === (n) ? ' pointed' : ''}"></div><span>${element.typeName || ''} </span>${element.r}`});
							qs('#suggestions').appendChild(li);
						}
					});
		}

	}
	if (confirm) {

		clearElement(qs('#suggestions'));
}
}



//						function dial(from, to, id = null) {
//							fetch(`/sync/utils/voip/call3.php?src=${from}&dist=${to}&idRCC_phones=${id}`);
//						}
function dial(params) {
//	src, dist, id = null
//				src: qs('#callSrc').value,
//				dist: client.phoneNumber.toString(),
//				idrcc: qs('#idRCC_phone').value.toString(),
//				callid: qs('#pdb').value


	fetch(`/sync/utils/voip/call3.php?src=${params.src}&dist=${params.dist}&idRCC_phones=${params.idrcc}`).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);

			///////////////////////////
			//																					console.error(jsn);
			if (!(jsn.connected || {}).success) {
				MSG(rt(
						'Ошибка соединения,<br>попробуйте ещё раз.',
						'У меня не получилось,<br>попробуйте ещё раз.',
						'Тупит связь,<br>попробуйте ещё раз.',
						'Не соединяется,<br>попробуйте ещё раз.',
						'Ох... мне тоже надоело,<br>но надо пытаться...<br>Давайте ещё разок.',
						'Когда-нибудь это починят, <br>а пока попробуйте ещё раз.',
						));
			} else {
				if (!(jsn.dial || {}).success) {
					MSG(`Ошибка в телефонном номере <br>"${dist}"<br>Проверьте правильность и повторите.`);
				} else {
					qs('#searchBTN').disabled = true;
					MSG({type: 'success', text: rt(
								'Звоню',
								'Набираю',
								'Звонок пошёл',
								'Ура, есть контакт!',
								'Ало-ало? ',
								'Успех!',
								), autoDismiss: 1500});
				}
			}

			/*
			 connected: Object { success: true, time: 0.005635976791381836 }
			 dial: Object { success: true, time: 0.2061021327972412 }
			 */
			///////////////////////////


		} catch (e) {
			MSG("Ошибка ответа сервера. <br><br><i>" + e + "</i>");
		}
	});
	;
}




async function getPhone(call = null) {
	if (call) {
		qs('#call').value = call;
	}
	let client = await fetch('IO.php', {
		body: JSON.stringify({action: 'getPhone', call: call}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if (jsn.phoneNumber) {
				return jsn;
			} else {
				return null;
			}
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			return null;
		}
	}); //fetch
	if ((client || {}).phoneNumber) {
		qs('#idRCC_phone').value = client.idRCC_phone || '';
		if (client.lname) {
			qs('#clientLName').value = client.lname || '';
			qs('#clientFName').value = client.fname || '';
			qs('#clientMName').value = client.mname || '';
			qs('#pdb').value = client.db || '';
			dial({
				src: qs('#callSrc').value,
				dist: client.phoneNumber.toString(),
				idrcc: qs('#idRCC_phone').value.toString(),
				callid: qs('#call').value
			});
		}
	} else {
		qs('#phoneNumber').innerHTML = 'Ошибка базы';
}

}