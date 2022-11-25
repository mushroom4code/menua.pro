function clearForm() {
	console.log('clearForm');
	qs('#INitemName').value = '';
	qs('#itemUnit').value = '';
	qs('#itemBarcode').value = '';
	qs('#qtyCount').value = '';
	qs('#qtyFactual').value = '';
	currentItem = {};
}