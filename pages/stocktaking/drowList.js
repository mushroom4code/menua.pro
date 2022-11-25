function drowList(date, jsn) {
	let list = (jsn.entries || []);
	console.log(list);
	qs('#dayReport').innerHTML = '';
	qs('#dayReport').appendChild(el('h2', {innerHTML: `${jsn.stocktaking ? 'Инвентаризация за ' : 'Приход за '}${date}`}));

	let table = el('table', {className: 'btmdashTable'});
	let n = 0;
	for (let element of list) {
		n++;
		let tr = el('tr');
		tr.appendChild(el('td', {className: 'R', innerHTML: n}));
		tr.appendChild(el('td', {className: 'L', innerHTML: element.name}));
		tr.appendChild(el('td', {className: 'R', innerHTML: element.qty}));
		tr.appendChild(el('td', {className: 'L', innerHTML: element.unitsName}));


		table.appendChild(tr);

	}


	qs('#dayReport').appendChild(table);
}