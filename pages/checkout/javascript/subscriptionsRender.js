/* global sale */

function subscriptionsRender() {
	let subscriptionsContainer = qs('#subscriptions');
	clear(subscriptionsContainer);

	subscriptionsContainer.appendChild(el('div', {
		className: 'displayContents',
		innerHTML: `
						<div></div>
						<div style="padding-right: 30px;">Наименование</div>
						<div style="text-align: center; font-weight: bold;">кол-во</div>			 
						<div style="text-align: center; font-weight: bold;">цена</div>
						<div style="text-align: center; font-weight: bold;">стоимость</div>
						<div style="text-align: center; font-weight: bold;">годен до</div>
						<div></div>
						</div>`}));

	sale.subscriptions.forEach((element, index) => {

		subscriptionsContainer.appendChild(el('div', {
			className: 'displayContents',
			innerHTML: `
						<div style="display: flex; align-items: center; justify-content: flex-end;">${index + 1}.</div>
						<div style="display: flex; align-items: center;">${element.service.name}</div>
						<div style="display: flex; align-items: center; justify-content: center;"><input type="text" placeholder="количество" style="text-align: center; width: 40px;" ${element.idservicesApplied ? 'readonly' : ''} value="${element.qty}" oninput="digon();sale.subscriptions[${index}].qty=this.value;calculateSubscriptionsTotal();"></div>			 
						<div style="display: flex; align-items: center; justify-content: center;"><input type="text" style="text-align: right;" placeholder="цена" value="${element.service.price}" oninput="digon(); sale.subscriptions[${index}].service.price=this.value;calculateSubscriptionsTotal();"></div>
						<div style="display: flex; align-items: center; justify-content: center;"><input type="text" style="text-align: right;" readonly id="subTotal_${index}" placeholder="сумма"></div>
						<div style="display: flex; align-items: center; justify-content: center;"><input type="date" oninput="sale.subscriptions[${index}].expDate=this.value;" id="date_${index}"></div>
						<div style="display: flex; align-items: center; justify-content: center;"><input type="button" value="X" style="color: red;" onclick="sale.subscriptions.splice(${index},1);subscriptionsRender();"></div>
						`
		}));
	});

	calculateSubscriptionsTotal();
}