var sound, sound2;
document.addEventListener("DOMContentLoaded", function () {
	document.body.focus();
	sound = qs('#Magic_Chime');
	if (!sound) {
		console.log('no sound');
		sound = el('audio');
		sound.id = 'Magic_Chime';
		sound.src = '/css/sounds/Beep_Short.mp3';
		document.body.appendChild(sound);
	}
	sound2 = qs('#honk');
	if (!sound2) {
		console.log('no sound2');
		sound2 = el('audio');
		sound2.id = 'honk';
		sound2.src = '/css/sounds/honk.mp3';
		document.body.appendChild(sound2);
	}
	//			console.log('autofocus.js');
	let hiddeninput = el('input');
	hiddeninput.type = 'hidden';
	hiddeninput.id = 'hiddeninput';
	document.body.appendChild(hiddeninput);

	let AFEL = qs('#hiddeninput');
	let start = performance.now();
	let  intId = false;
	document.addEventListener('keypress', async function (e) {
		let interval = performance.now() - start;
		start = performance.now();
		if (document.activeElement === document.body) {
			if (interval > 70) {
				AFEL.value = '' + filterKeys(e.key);
//				AFEL.value = AFEL.value + filterKeys(e.key);
			} else {
				if (e.keyCode !== 13) {
					AFEL.value = AFEL.value + filterKeys(e.key);
					if (intId) {
						clearTimeout(intId);
					} else {
//					console.log(typeof (intId));
					}
					intId = setTimeout(function () {
						console.log('timeout reached', AFEL.value);
						makeRequest(AFEL);
					}, 100);

				} else {
					console.log('enter pressed', AFEL.value);
					if (intId) {
						clearTimeout(intId);
					}
					makeRequest(AFEL);
				}
			}
		}

	});
});


async function getWithdrawal(user = null, date = null, params = null) {

	let result = [];
	result = await fetch('IO.php', {
		body: JSON.stringify(
				{
					action: 'getWithdrawal',
					user: user,
					date: date,
					params: params
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
			return jsn;
		} catch (e) {
			MSG("Ошибка сервера. <br><br><i>" + e + "</i>");
			return [];
		}
	}); //fetch
	return result;

}


async function deleteOutEntry(param) {
	console.log(param);


	let wda = await getWithdrawal(_personal.id, qs('#DATE').value, {deleteWithdrawEntry: param.withdrawentry});
	renderWithdrawal(wda);

}


function renderPersonal(personal) {
	_personal.id = personal.idusers;
	qs('#personalInfo').innerHTML = `${personal.usersLastName} ${personal.usersFirstName} ${personal.usersMiddleName}`;
}
function renderBack(date) {

	qs('#back').innerHTML = `<a href="/pages/warehouse/out/?date=${date}" style="display: block; margin: 20px 0px;">...назад</a> `;
}

function renderWithdrawal(jsn) {
	clear(qs('#withdrawal'));
	console.log('renderWithdrawal');
	if (jsn.withdrawal) {
		let withdrawal = jsn.withdrawal;
		console.log('withdrawal', withdrawal);
//WH_goodBarCode: "4607085860114"
//WH_goodsDeleted: null
//WH_goodsName: "Гель для рук Sanitelle"
//WH_goodsNomenclature: "979"
//WH_goodsNomenclatureQty: null
//WH_goodsOutDate: "2020-05-08 11:53:06"
//WH_goodsOutDeleted: null
//WH_goodsOutItem: "1051"
//WH_goodsOutQty: "1.000"
//WH_goodsOutTime: null
//WH_goodsOutUnits: "5"
//WH_goodsOutUser: "176"
//WH_goodsPrice: null
//WH_goodsUnits: null
//WH_goodsWHQty: null
//WH_goodsWHUnits: null
//WH_nomenclatureEntryType: "2"
//WH_nomenclatureMax: null
//WH_nomenclatureMin: null
//WH_nomenclatureName: "Антисептик гель"
//WH_nomenclatureParent: null
//WH_nomenclatureType: "1"
//WH_nomenclatureUnits: "5"
//idWH_goods: "1051"
//idWH_goodsOut: "2"
//idWH_nomenclature: "979"
//idunits: "5"
//outDate: "2020-05-08"
//unitsCode: "6"
//unitsFullName: "миллилитр"
//unitsName: "мл."
//unitsOKEI: "111"
		qs('#withdrawal').appendChild(el('tr', {innerHTML: `<td style="color: silver;">#</td><td style="color: silver;">Наименование</td><td style="color: silver;"></td><td style="color: silver;"></td><td></td>`}));
		let n = 0;
		withdrawal.forEach(withdraw => {
			n++;
//			console.log(withdraw);
			qs('#withdrawal').appendChild(el('tr', {innerHTML: `<td class="R">${n}.</td><td><a target="_blank" href="/pages/warehouse/goods/item/?item=${withdraw.idWH_nomenclature}">${withdraw.WH_goodsName}</a></td><td class="R">${Math.round(withdraw.WH_goodsOutQty * 1000) / 1000}</td><td>${withdraw.unitsName || '<b style="color: red;">??</b>'}</td><td style="color: red; cursor: pointer;" data-function="deleteOutEntry" data-withdrawEntry="${withdraw.idWH_goodsOut}">&Cross;</td>`}
			));
			qs('#DATE').value = withdraw.outDate;
//			console.log(withdraw.outDate);
		});
	}
	if (jsn.summary) {
		let summary = jsn.summary;
		qs('#DATE').value = jsn.date;
		qs('#withdrawal').appendChild(el('tr', {innerHTML: `<td>#</td><td style="color: silver;">Сотрудник</td><td style="color: silver;"></td>`}));
		let n = 0;
		summary.forEach(summ => {
			n++;
			qs('#withdrawal').appendChild(el('tr', {innerHTML: `<td class="R">${n}</td><td><a href="/pages/warehouse/out/?date=${jsn.date}&user=${summ.user}">${summ.name}</a></td><td class="R">${Math.round(summ.qty * 1000) / 1000 }</td>`}));

		});

	}


}
var withdrawal = [];
var currentItem = {};





async function makeRequest(AFEL) {
	if (AFEL.value.length > BCminLength) {
		if ((AFEL.value == currentItem.WH_goodBarCode || AFEL.value == `SET${currentItem.idWH_nomenclature}`) && qs('#withdrawQty')) {
			qs('#withdrawQty').value++;
//			console.log('currentItem', currentItem);
			if (qs('#overdraft')) {
				if (+qs('#withdrawQty').value > +currentItem.balance) {
					qs('#withdrawWindow').classList.add('error');
					qs('#withdrawWindow').classList.remove('neutral');
					sound2.currentTime = 0;
					sound2.play();
					qs('#overdraft').style.display = 'block';
				} else {
					qs('#withdrawWindow').classList.remove('error');
					qs('#withdrawWindow').classList.add('neutral');
					qs('#overdraft').style.display = 'none';
					//	sound.currentTime = 0;
					//	sound.play();
				}
			}


		} else {
			if (AFEL.value != currentItem.WH_goodBarCode && qs('#withdrawQty')) {
				let data = {
					action: 'makeWithdraw',
					date: qs('#DATE').value,
					user: _personal.id,
					item: (currentItem.idWH_goods || `SET${currentItem.idWH_nomenclature}`),
					qty: qs('#withdrawQty').value
				};
				console.log(data);
				fetch('/pages/warehouse/out/IO.php', {
					body: JSON.stringify(data),
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
						if (jsn.success) {

							clear(qs('#withdrawWindow'));
							qs('#withdrawWindow').remove();
							getWithdrawal(_personal.id, qs('#DATE').value);
						}

					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				}); //fetch



			}


			let check = await checkBarcode(AFEL.value);
			if (!check.personal && !check.item && !check.msgs && !check.SET) {
				MSG('Ничего не найдено');
			}
			if (((check || {}).personal || {}).idusers) {
				console.log(check.personal);
				GETreloc('user', check.personal.idusers);
//				renderPersonal(check.personal);
//				withdrawal = await getWithdrawal(check.personal.idusers);
//				renderWithdrawal(withdrawal);
				//	sound.currentTime = 0;
				//	sound.play();
			}
			if ((check || {}).item || (check || {}).SET) {
				if (_personal.id !== null) {
					if ((check || {}).item) {
						currentItem = check.item;
						withdrawWindow(check.item);

//						WH_goodBarCode: 4690471021091
//						WH_goodsDeleted: null
//						WH_goodsName: "Зубочистки №600"
//						WH_goodsNomenclature: null
//						WH_goodsNomenclatureQty: null
//						WH_goodsPrice: null
//						WH_goodsUnits: 8
//						WH_goodsWHQty: null
//						WH_goodsWHUnits: null
//						WH_nomenclatureEntryType: null
//						WH_nomenclatureMax: null
//						WH_nomenclatureMin: null
//						WH_nomenclatureName: null
//						WH_nomenclatureParent: null
//						WH_nomenclatureType: null
//						WH_nomenclatureUnits: null
//						balance: 0
//						idWH_goods: 1932
//						idWH_nomenclature: null
//						idunits: null
//						unitsCode: null
//						unitsFullName: null
//						unitsName: null
//						unitsOKEI: null

					}
					if ((check || {}).SET) {
						currentItem = check.SET;
						withdrawWindow(check.SET);
//						WH_nomenclatureEntryType: 3
//						WH_nomenclatureMax: null
//						WH_nomenclatureMin: null
//						WH_nomenclatureName: "Набор массажиста"
//						WH_nomenclatureParent: 1219
//						WH_nomenclatureType: 1
//						WH_nomenclatureUnits: null
//						idWH_nomenclature: 1300
					}


					//sound.currentTime = 0;
					//sound.play();
				} else {
					MSG('Сначала надо указать сотрудника');
				}
			}
		}
	} else {
		MSG('Короткий штрихкод');
	}
}


async function checkBarcode(BC) {
	let result = await fetch('IO.php', {
		body: JSON.stringify({action: 'checkBC', BC: BC}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if ((jsn.msgs || []).length) {
				jsn.msgs.forEach(async msg => {
					await MSG(msg);
				});
			}
			return jsn;
		} catch (e) {
			return false;//	MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}); //fetch
	return result;
}