/* global qs, sale */

window.addEventListener('DOMContentLoaded', function () {
	if (qs('#idclients')) {
		qs('#idclients').addEventListener('input', readClientFields);
		qs('#clientsLname').addEventListener('input', readClientFields);
		qs('#clientsFname').addEventListener('input', readClientFields);
		qs('#clientsMname').addEventListener('input', readClientFields);
		qs('#bday').addEventListener('input', readClientFields);
		qs('#clientsAKNum').addEventListener('input', readClientFields);
		qs('#passportnumber').addEventListener('input', readClientFields);
		qs('#passportdate').addEventListener('input', readClientFields);
		qs('#passportcode').addEventListener('input', readClientFields);
		qs('#registration').addEventListener('input', readClientFields);
		qs('#residence').addEventListener('input', readClientFields);
		qs('#genderF').addEventListener('click', readClientFields);
		qs('#genderM').addEventListener('click', readClientFields);
		qs('#birthplace').addEventListener('input', readClientFields);
		qs('#department').addEventListener('input', readClientFields);
		qs('#phone').addEventListener('input', readClientFields);
		qs('#date').addEventListener('input', readClientFields);
		coordinatorsRender();
		subscriptionsRender();
	}
});
function readClientFields() {
	sale.client.id = qs('#idclients').value || null;
	sale.client.lname = qs('#clientsLname').value || null;
	sale.client.fname = qs('#clientsFname').value || null;
	sale.client.mname = qs('#clientsMname').value || null;
	sale.client.bday = qs('#bday').value || null;
	sale.client.aknum = qs('#clientsAKNum').value || null;
	sale.client.passportnumber = qs('#passportnumber').value || null;
	sale.client.passportdate = qs('#passportdate').value || null;
	sale.client.passportcode = qs('#passportcode').value || null;
	sale.client.registration = qs('#registration').value || null;
	sale.client.residence = qs('#residence').value || null;
	sale.client.birthplace = qs('#birthplace').value || null;
	sale.client.department = qs('#department').value || null;
	sale.client.phone = qs('#phone').value || null;
	sale.date = qs('#date').value || null;
	if (qs('#genderF').checked) {
		sale.client.gender = 0;
	} else if (qs('#genderM').checked) {
		sale.client.gender = 1;
	} else {
		sale.client.gender = null;
	}

}

function fillClientFields(client = {}) {
	sale.client = client;
	qs('#idclients').value = client.id || '';
	qs('#clientsLname').value = client.lname || '';
	qs('#clientsFname').value = client.fname || '';
	qs('#clientsMname').value = client.mname || '';
	qs('#bday').value = client.bday || '';
	qs('#clientsAKNum').value = client.aknum || '';
	qs('#passportnumber').value = client.passportnumber || '';
	qs('#passportdate').value = client.passportdate || '';
	qs('#passportcode').value = client.passportcode || '';
	qs('#registration').value = client.registration || '';
	qs('#residence').value = client.residence || '';
	qs('#genderF').checked = client.gender === 0;
	qs('#genderM').checked = client.gender === 1;
	qs('#birthplace').value = client.birthplace || '';
	qs('#department').value = client.department || '';
	qs('#phone').value = client.phone || '';
	sale.subscriptions = [];
	if (((sale.client || {}).salesDraft || []).length) {
		document.querySelector('#salesDraft').style.display = 'block';
		let salesDraftDiv = document.querySelector('#salesDraftContent');
		salesDraftDiv.innerHTML = '';
		salesDraftDiv.style.display = 'grid';
		salesDraftDiv.style.gridTemplateColumns = 'repeat(5, auto)';
		let header = el('div', {innerHTML: `<div class="B C">Дата</div>
														<div class="B C">Номер</div>
														<div class="B C">Автор</div>
														<div class="B C">Услуг</div>
														<div class="B C"><i class="fas fa-file-upload"></i></div>
`});
		header.style.display = 'contents';
		salesDraftDiv.appendChild(header);
		sale.client.salesDraft.forEach((saleDraft, i) => {
			let row = el('div', {innerHTML: `<div>${saleDraft.f_salesDraftDate}</div>
				<div>${saleDraft.f_salesDraftNumber}</div>
<div>${saleDraft.usersLastName} ${saleDraft.usersFirstName} ${saleDraft.usersMiddleName}</div>
<div class="C">${saleDraft.f_subscriptionsDraftCount}</div>
<div class="C"><a><i class="fas fa-file-download" data-function="loaddrafts" data-index="${i}" style="cursor: pointer;"></i></a></div>
`});
			row.style.display = 'contents';
//			row.dataset.function = 'loaddrafts';
//			row.dataset.index = i;
			salesDraftDiv.appendChild(row);
		});
		console.log('sale.client.salesDraft', sale.client.salesDraft);
	} else {

		document.querySelector('#salesDraft').style.display = 'none';
	}
	if (((client || {}).servicesApplied || []).length) {
		console.log("client.servicesApplied", client.servicesApplied);
		for (let service of client.servicesApplied) {
			addToSubscriptionContent(
					{
						service: service.servicesAppliedService,
						price: service.servicesAppliedPrice,
						qty: service.servicesAppliedQty,
						idservicesApplied: service.idservicesApplied
					});
		}

	}
	subscriptionsRender();
	console.log("client", client);
}
function loaddrafts(data) {
	console.log(data.index, sale.client.salesDraft[data.index]);
	let draft = sale.client.salesDraft[data.index].subscriptionsDraft;
	sale.subscriptions = [];
	draft.forEach(service => {
		sale.subscriptions.push({
			idservicesApplied: null,
			qty: service.qty,
			service: {
				id: service.idservices,
				name: service.servicesName,
				price: service.price
			}
		});
	});
	subscriptionsRender();




}

