async function makeRequest(AFEL) {
	console.log('makeRequest');
	let BCminLength = 5;
	if (AFEL.value.length > BCminLength) {
		let check = await checkBarcode(AFEL.value);
		console.log('check', check);
		if (check === false) {
			MSG(rt('Ошибка', 'Что-то пошло не так', 'Надо разобраться, есть проблемы...'));
		} else
		if (check.length === 0) {
			await MSG(rt('Я не знаю что это', 'Нет в базе данных', 'Надо бы добавить в базу данных'));
			let result = await addItemWindow(AFEL.value);
			if (result) {
				check = await checkBarcode(AFEL.value);
			}
		} else
		if (check.length === 1) {
			fillForm(check[0]);
			currentItem = check[0];
			qs('#qtyFactual').focus();
		} else {
			MSG(`Найдено больше 1 позиции (${check.length}) с таким штрихкодом. Это плохо.`);
		}
	} else {
		MSG('Странный штрихкод, короткий какой-то...');
	}
}