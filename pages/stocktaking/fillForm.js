function fillForm(data) {
	console.log('fillForm');
	qs('#INitemName').value = data.name;
	qs('#qtyCount').value = data.qty;
	qs('#itemBarcode').value = data.barcode;
	qs('#itemUnit').value = data.unit.fname;
//		console.log(data);
}