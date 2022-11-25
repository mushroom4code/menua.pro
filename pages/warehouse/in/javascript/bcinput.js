function bcinput() {
	console.log('bcinput');
	let temp = filterKeys(qs('#newItemBarcode').value);
	clearForm();
	qs('#newItemBarcode').value = temp;
//	qs('#INitemName').value = '';
//	qs('#itemUnit').value = '';
	//currentItem = {};
}
function nameinput() {
	console.log('nameinput');
	let temp = (qs('#newItemName').value);
	clearForm();
	qs('#newItemName').value = temp;
//	qs('#INitemName').value = '';
//	qs('#itemUnit').value = '';
	//currentItem = {};
}