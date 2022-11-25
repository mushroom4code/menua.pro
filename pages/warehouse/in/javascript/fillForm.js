/* global qs, measuringUnits */

function fillForm(data) {
	console.log('fillForm', data);
	qs('#idgoods').value = data.idWH_goods;
	qs('#newItemName').value = data.WH_goodsName;
	qs('#newItemBarcode').value = data.WH_goodBarCode;
	if (data.WH_nomenclatureUnits) {
		qs('#Nunits').value = (measuringUnits.find((elem) => {
			return data.WH_nomenclatureUnits === elem.id;
		})).fname || 'Не указаны';
	}
	if (data.WH_nomenclatureName) {
		qs('#newNomenclatureName').value = data.WH_nomenclatureName;
	}

	if (data.idWH_nomenclature) {
		qs('#newNomenclatureID').value = data.idWH_nomenclature;
		qs('#unitsname').innerHTML = ', ' + measuringUnits.find(elem => {
			return elem.id == data.WH_nomenclatureUnits;
		}).name || '???';



		qs('#unitsname2').innerHTML = ', руб. за 1' + measuringUnits.find(elem => {
			return elem.id == data.WH_nomenclatureUnits;
		}).name || '???';


	}



	if (!data.idWH_nomenclature) {
		MSG('Привяжите товар к номенклатуре');
	}


//		console.log(data);
//WH_goodBarCode: 4602547000022
//WH_goodsDeleted: null
//WH_goodsName: "Сливки 10% клевер 500мл"
//WH_goodsNomenclature: 1054
//WH_goodsNomenclatureQty: null
//WH_goodsPrice: null
//WH_goodsUnits: 5
//WH_goodsWHQty: null
//WH_goodsWHUnits: null
//WH_nomenclatureEntryType: 2
//WH_nomenclatureMax: null
//WH_nomenclatureMin: null
//WH_nomenclatureName: "Сливки 10%"
//WH_nomenclatureParent: 655
//WH_nomenclatureType: 1
//WH_nomenclatureUnits: 5
//idWH_goods: 2093
//idWH_nomenclature: 1054
}