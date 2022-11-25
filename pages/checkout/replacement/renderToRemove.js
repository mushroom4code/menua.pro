function renderToRemove() {
	_totalSummToRemove = 0;
	let toRemoveWrapper = qs('#toRemoveWrapper');
	clearElement(toRemoveWrapper);
	for (let toRemove of _toRemove) {
		_totalSummToRemove += toRemove.f_salesContentQty * toRemove.f_salesContentPrice;
		toRemoveWrapper.appendChild(el('div', {innerHTML: `${toRemove.servicesName}`}));
		let countWrapper = el('div', {className: 'C B'});
		let subBtn = el('span', {className: 'smallBtn', innerHTML: `<i class="fas fa-minus-circle"></i>`});
		countWrapper.appendChild(subBtn);
		subBtn.addEventListener('click', function (event) {
			event.preventDefault();
			if (toRemove.f_salesContentQty < 0) {
				toRemove.f_salesContentQty++;

				let filtered = _remains.filter((el) => {
					return el.f_salesContentPrice === toRemove.f_salesContentPrice && el.idservices === toRemove.idservices;
				});
				if (filtered.length) {
					filtered[0].f_salesContentQty += 1;
				} else {
					_remains.push({
						f_salesContentPrice: toRemove.f_salesContentPrice,
						f_salesContentQty: 1,
						idservices: toRemove.idservices,
						servicesName: toRemove.servicesName
					});
				}

				if (toRemove.f_salesContentQty >= 0) {
					_toRemove.splice(_toRemove.indexOf(toRemove), 1);
				}

				renderToRemove();
				renderRemains();

			}
		});
		let counter = el('span', {innerHTML: `${toRemove.f_salesContentQty}`});
		countWrapper.appendChild(counter);
		let addBtn = el('span', {className: 'smallBtn', innerHTML: `<i class="fas fa-plus-circle"></i>`});
		addBtn.addEventListener('click', function (event) {
			event.preventDefault();
			let filtered = _remains.filter((el) => {
				return el.f_salesContentPrice === toRemove.f_salesContentPrice && el.idservices === toRemove.idservices;
			});

			if (filtered.length) {
				if (filtered[0].f_salesContentQty > 0) {
					filtered[0].f_salesContentQty -= 1;
					toRemove.f_salesContentQty--;
					if (filtered[0].f_salesContentQty <= 0) {
						_remains.splice(_remains.indexOf(filtered[0]), 1);
					}
				}
			}

			renderRemains();
			renderToRemove();
		});
		countWrapper.appendChild(addBtn);
		toRemoveWrapper.appendChild(countWrapper);
		toRemoveWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(toRemove.f_salesContentPrice)}`}));
		toRemoveWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(toRemove.f_salesContentQty * toRemove.f_salesContentPrice)}`}
		));
	}
	let remainsTitle = el('div', {className: 'R B', innerHTML: `На сумму:`});
	remainsTitle.style.gridColumn = '1/-2';
	toRemoveWrapper.appendChild(remainsTitle);
	toRemoveWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(_totalSummToRemove)}`}));

	renderTotal();
}