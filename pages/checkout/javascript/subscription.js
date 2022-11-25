function addToSubscriptionContent(content) {
	clearElement(qs('#suggestions'));
	qs('#serviceSearch').value = '';
	if (content.service) {
		let service = services.find(element => {
			return element.id === content.service;
		});
		if (service) {

			let price = service.price
			let qty = 1;
			if (typeof (content.price) !== 'undefined') {
				price = content.price;
			}

			if (typeof (content.qty) !== 'undefined') {
				qty = content.qty;
			}

			sale.subscriptions.push(
					{service: {
							id: service.id,
							name: service.name,
							price: price},
						qty: qty,
						idservicesApplied: (content.idservicesApplied || null)
					});
		}
	}

	subscriptionsRender();
}
let pointer = 0;
function suggest(value, confirm = false) {
	clearElement(qs('#suggestions'));
	if (value !== '') {
		let n = 0;
		services.sort((a, b) => {
			if (a.p2s != b.p2s) {
				return b.p2s - a.p2s;
			}
			return a.name.localeCompare(b.name);
		});
		let result = recursiveReduce(services, value);
		if (result.length > 1) {
			result.forEach(
					element => {
						n++;
						if (n <= 10) {
							if (confirm && pointer === n) {
								addToSubscriptionContent({service: element.id});
								clearElement(qs('#suggestions'));
								return;
							}
							if (element.p2s) {
								let li = el('li', {innerHTML: `<div data-function="addToSubscriptionContent" data-service="${element.id}" class="mask${pointer === (n) ? ' pointed' : ''}"></div><span>${element.typeName || 'Без типа'} ${element.p2s ? '' : '<i class="fas fa-exclamation-triangle" style="color: orange;"></i>'} </span>${element.r}`});
								qs('#suggestions').appendChild(li);
							}

						}
					});
		} else if (result.length === 1) {
			addToSubscriptionContent({service: result[0].id});
		}

	}
	if (confirm) {
		clearElement(qs('#suggestions'));
}
}

function calculateSubscriptionsTotal() {
	qs('#subscriptionTotalValue').value = 0;
	let total = 0;
	sale.subscriptions.forEach((element, index) => {
		let subTotal = +Math.round((element.service.price || 0) * (element.qty || 0));
		qs(`#subTotal_${index}`).value = subTotal;
		total += subTotal;
	});
	qs('#subscriptionTotalValue').value = total;
	qs('#subscriptionTotalPay').value = total;
	sale.payment.summ = total;

}