function clearForm() {
	console.log('clearForm');
	qs('#idgoods').value = '';
	qs('#newItemName').value = '';
	qs('#newItemBarcode').value = '';
	qs('#newNomenclatureID').value = '';
	qs('#newNomenclatureName').value = '';
	qs('#Nunits').value = '';
	qs('#qty').value = '';
	qs('#price').value = '';
	qs('#unitsname').innerHTML = '';
	qs('#unitsname2').innerHTML = '';
	currentItem = {};
}