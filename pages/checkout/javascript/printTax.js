async function printTax(idf_sales) {



	console.log('idf_sales', idf_sales);

	let box = el('div', {className: 'modal neutral'});
	box.appendChild(el('h2', {innerHTML: `Выбрать налогоплательщика`}));

	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);

	var wrapper = el('div');
	wrapper.style.display = 'grid';
	wrapper.style.gridTemplateColumns = 'auto auto';
	wrapper.style.gridGap = '10px';


	boxBody.appendChild(wrapper);

	let ddssName = el('div');
	ddssName.innerHTML = `Налогоплательщик:`;
	wrapper.appendChild(ddssName);

	let taxPersonSelect = el('select');

	wrapper.appendChild(taxPersonSelect);

	taxPersonSelect.appendChild(new Option('Сам клиент', '', false, false));
	clientsTaxPersons.forEach(element => {
		taxPersonSelect.appendChild(new Option(element.clientsTaxPersonsFULLName, element.idclientsTaxPersons, false, false));
	});


	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let cancelBtn = el('button', {innerHTML: `Отмена`});
		cancelBtn.style.margin = '0px 10px';
		box.appendChild(cancelBtn);
		cancelBtn.addEventListener('click', function () {
			box.parentNode.removeChild(box);
			resolve(false);
		});
		let addBtn = el('button', {innerHTML: `Печать`});
		box.appendChild(addBtn);
		addBtn.style.margin = '0px 10px';
		addBtn.addEventListener('click', async function () {
			window.location.href = `/sync/utils/word/tax.php?sale=${idf_sales}&taxPerson=${taxPersonSelect.value}`;
		});
	});

	document.body.appendChild(box);
	taxPersonSelect.focus();
	return promise;







	MSG(idf_sales);
}