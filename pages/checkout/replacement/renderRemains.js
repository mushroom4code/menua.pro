function renderRemains() {
	let remainsWrapper = qs('#remainsWrapper');
	clearElement(remainsWrapper);
	let fullSumm = 0;
	let remainsSumm = 0;

	/*(
	 [idf_subscriptions] => 42773
	 [f_subscriptionsContract] => 19893
	 [f_salesContentService] => 204
	 [f_salesContentPrice] => 800
	 [f_salesContentQty] => -4
	 [f_subscriptionsDate] => 2020-07-30 22:59:17
	 [f_subscriptionsUser] => 176
	 [idservices] => 204
	 [servicesCode] => 000000040
	 [servicesName] => Маникюр классический 
	 [servicesBasePrice2] => 800
	 [servicesCost2] => 0
	 [servicesType] => 2
	 [servicesDeleted] => 
	 [servicesEquipment] => 
	 )*/

	for (let remain of _remains) {
		remainsSumm += remain.f_salesContentQty * remain.f_salesContentPrice;
		remainsWrapper.appendChild(el('div', {innerHTML: `${remain.servicesName}`}));
		let countWrapper = el('div', {className: 'C B'});
		let subBtn = el('span', {className: 'smallBtn', innerHTML: `<i class="fas fa-minus-circle"></i>`});
		countWrapper.appendChild(subBtn);
		subBtn.addEventListener('click', function (event) {
			event.preventDefault();
			if (remain.f_salesContentQty > 0) {
				let filtered = _toRemove.filter((el) => {
					return el.f_salesContentPrice === remain.f_salesContentPrice && el.idservices === remain.idservices;
				});

				if (filtered.length) {
					filtered[0].f_salesContentQty -= 1;
				} else {
					_toRemove.push({
						f_salesContentPrice: remain.f_salesContentPrice,
						f_salesContentQty: -1,
						idservices: remain.idservices,
						servicesName: remain.servicesName
					});
				}
				remain.f_salesContentQty--;
			}
			renderToRemove();
			renderRemains();
		});
		let counter = el('span', {innerHTML: `${remain.f_salesContentQty}`});
		countWrapper.appendChild(counter);
		let addBtn = el('span', {className: 'smallBtn', innerHTML: `<i class="fas fa-plus-circle"></i>`});
		addBtn.addEventListener('click', function (event) {
			event.preventDefault();
			let filtered = _toRemove.filter((el) => {
				return el.f_salesContentPrice === remain.f_salesContentPrice && el.idservices === remain.idservices;
			});
			if (filtered.length) {
				remain.f_salesContentQty++;
				if (filtered.length) {
					filtered[0].f_salesContentQty += 1;
					if (filtered[0].f_salesContentQty >= 0) {
						_toRemove.splice(_toRemove.indexOf(filtered[0]), 1);
					}
				}

			}
			renderToRemove();
			renderRemains();
		});
		countWrapper.appendChild(addBtn);
		remainsWrapper.appendChild(countWrapper);
		remainsWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(remain.f_salesContentPrice)}`}));
		remainsWrapper.appendChild(el('div', {className: 'R', innerHTML: `${nf(remain.f_salesContentQty * remain.f_salesContentPrice)}`}
		));
	}

	renderTotal();
	renderToRemove();
}