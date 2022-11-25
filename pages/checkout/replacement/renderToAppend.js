function renderToAppend() {
	_totalSummToAppend = 0;
	let toAppendWrapper = qs('#toAppendWrapper');
	clearElement(toAppendWrapper);
	for (let toAppend of _toAppend) {
		_totalSummToAppend += toAppend.f_salesContentQty * toAppend.f_salesContentPrice;
		toAppendWrapper.appendChild(el('div', {innerHTML: `${toAppend.servicesName}`}));
		let countWrapper = el('div', {className: 'C B'});
		let subBtn = el('span', {className: 'smallBtn', innerHTML: `<i class="fas fa-minus-circle"></i>`});
		countWrapper.appendChild(subBtn);
		subBtn.addEventListener('click', function (event) {
			event.preventDefault();
			if (toAppend.f_salesContentQty > 0) {
				toAppend.f_salesContentQty--;
				renderToAppend();
			} else {
				_toAppend.splice(_toAppend.indexOf(toAppend), 1);
				renderToAppend();
			}
		});
		let counter = el('span', {innerHTML: `${toAppend.f_salesContentQty}`});
		countWrapper.appendChild(counter);
		let addBtn = el('span', {className: 'smallBtn', innerHTML: `<i class="fas fa-plus-circle"></i>`});
		addBtn.addEventListener('click', function (event) {
			event.preventDefault();
			toAppend.f_salesContentQty++;
			renderToAppend();
		});
		countWrapper.appendChild(addBtn);
		toAppendWrapper.appendChild(countWrapper);
		toAppendWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(toAppend.f_salesContentPrice)}`}));
		toAppendWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(toAppend.f_salesContentQty * toAppend.f_salesContentPrice)}`}
		));
	}
	let remainsTitle = el('div', {className: 'R B', innerHTML: `На сумму:`});
	remainsTitle.style.gridColumn = '1/-2';
	toAppendWrapper.appendChild(remainsTitle);
	toAppendWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(_totalSummToAppend)}`}));
	renderTotal();
}