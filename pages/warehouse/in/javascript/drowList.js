function drowList(date, jsn) {
	let list = (jsn.entries || []);
	console.log(list);
	qs('#dayReport').innerHTML = '';
	qs('#dayReport').appendChild(el('h2', {innerHTML: `${jsn.stocktaking ? 'Инвентаризация за ' : 'Приход за '}${date}`}));

	let table = el('table', {className: 'btmdashTable'});
	let n = 0;
	for (let element of list) {
		console.log(element);
		n++;
		let tr = el('tr');
		tr.appendChild(el('td', {className: 'R', innerHTML: n}));
		tr.appendChild(el('td', {className: 'L', innerHTML: `${element.WH_goodsNomenclature ? '<a target="_blank" href="/pages/warehouse/goods/item/?item=' + element.WH_goodsNomenclature + '">' : '<b style="color: red;">'} ${element.name} ${element.WH_goodsNomenclature ? '</a>' : ' (Не привязан)</b>'}`}));
		tr.appendChild(el('td', {className: 'R', innerHTML: Math.round((element.qty || 0) * 1000) / 1000}));
		tr.appendChild(el('td', {className: 'L', innerHTML: element.unitsName}));


		table.appendChild(tr);

	}


	qs('#dayReport').appendChild(table);
}