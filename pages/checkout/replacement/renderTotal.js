function renderTotal() {
	let toAppendTotalWrapper = qs('#toAppendTotalWrapper');

	toAppendTotalWrapper.innerHTML = _toRemove.length ? `Удалить ${_toRemove.length} ${human_plural_form(_toRemove.length, ['позицию', 'позиции', 'позиций'])} на сумму
	${nf(Math.abs(_totalSummToRemove))} рублей.<br>` : ``;

//	_totalSummToAppend positive
//	_totalSummToRemove negative


	toAppendTotalWrapper.innerHTML += _toAppend.length ? `Добавить ${_toAppend.length} ${human_plural_form(_toAppend.length, ['позицию', 'позиции', 'позиций'])} на сумму
	${nf(_totalSummToAppend)} рублей.<br>` : ``;




	if ((_totalSummToRemove + _totalSummToAppend) < 0) {
		toAppendTotalWrapper.innerHTML += `<span style="color: green;">Остаток средств ${nf(Math.abs(_totalSummToAppend + _totalSummToRemove))} рублей.</span>`;
	} else
	if ((_totalSummToRemove + _totalSummToAppend) > 0) {
		toAppendTotalWrapper.innerHTML += `<span style="color: red;">Необходимо доплатить ${nf(_totalSummToAppend + _totalSummToRemove)} рублей.</span>`;
	} else
	if ((_remainsSummLeft = _totalSummToAppend)) {
		toAppendTotalWrapper.innerHTML += `Доплата не требуется.`;
	}
}