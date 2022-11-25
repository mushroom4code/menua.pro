function renderSuppliersTable(suppliers) {
//	console.log('renderSuppliersTable', suppliers);
	let suppliersBody = qs('#suppliers').tBodies[0];

	clear(suppliersBody);

	suppliers.sort((a, b) => {
		if (a.name.toLowerCase() > b.name.toLowerCase()) {
			return 1;
		}
		if (a.name.toLowerCase() < b.name.toLowerCase()) {
			return -1;
		}
		return 0;
	});

	if (suppliers.length > 0) {
		suppliers.forEach((supplier, index) => {
			suppliersBody.appendChild(el('tr', {innerHTML: `
<td>${index + 1}</td>
<td>${supplier.code ? `<a href="/sync/plugins/barcodePrint.php?supplier=${supplier.id}" target="_blank"><i class="fas fa-barcode"></i></a>` : ''}</td>
<td><a href="/pages/suppliers/?supplier=${supplier.id}">${supplier.name || 'Не указано'}</a></td>
<td><button style="color: red;" data-function="deleteSupplier" data-supplier="${supplier.id}">X</button></td>				
`}));
//			console.log(index);
		});


	}
}
;