/* global currentItem, qs */

async function sendData() {
	currentItem.action = 'addNewItem';
	currentItem.qty = parseFloat(qs('#qty').value);
	currentItem.date = qs('#date').value;
	currentItem.idsuppliers = qs('#idsuppliers').value;
	currentItem.idnomenclature = qs('#newNomenclatureID').value;
	currentItem.idWH_goods = qs('#idgoods').value;
	currentItem.price = qs('#price').value;

	if (qs('#stocktaking')) {
		currentItem.stocktaking = qs('#stocktaking').checked === true;
	}


	let errors = [];
	if (!currentItem.idWH_goods) {
		errors.push('Неизвестная позиция');
	}
	if (!currentItem.idnomenclature) {
		errors.push('Необходимо привязать товар к номенклатуре');
	}
	if (!currentItem.qty) {
		errors.push('Укажите количество');
	}

	if (!currentItem.date) {
		errors.push('Укажите дату прихода');
	}

	if (errors.length) {
		for (let error of errors) {
			await MSG(error);
		}
	} else {


		fetch('/pages/warehouse/in/IO.php', {
			body: JSON.stringify(currentItem),
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
				if (jsn.success) {
					qs('#sbmbtn').blur();
					MSG({type: 'success', text: rt(
								'ok<br>Я сам закроюсь.',
								'Отлично!<br>Я сам закроюсь.',
								'Внесена запись<br>Я сам закроюсь.',
								'Так точно!<br>Я сам закроюсь.',
								'Так точно!<br>Я сам закроюсь.',
								), autoDismiss: 1500});
//					loadReportForDate(currentItem.date);
					GETreloc('date', currentItem.date);
					clearForm();

				} else {
					MSG(jsn.errors[0]);
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});


	}

	console.log(currentItem);
}