
/* global qs, measuringUnits */

function choeseItem(item) {
	console.log('choeseItem', item);
//					idwh_goods: 1027
//					wh_goodbarcode: 4987480010100
//					wh_goodsdeleted: "null"
//					wh_goodsname: "Лаеннек р-р д/ин. амп. 2 мл №10"
//					wh_goodsnomenclature: 934
//					wh_goodsnomenclatureqty: 20
//					wh_goodstype: 1
//					wh_goodsunits: 8
//					wh_goodswhqty: 2
//					wh_goodswhunits: 10

	if (item.wh_goodsname && qs('#goodsName')) {
		qs('#goodsName').value = item.wh_goodsname;
	}
	if (item.wh_goodbarcode && qs('#goodsBarCode')) {
		qs('#goodsBarCode').value = item.wh_goodbarcode;
	}
	if (item.idwh_goods && qs('#idgoods')) {
		qs('#idgoods').value = item.idwh_goods;
	}

	if (item.wh_goodsunits && qs('#itemUnits')) {
		qs('#itemUnits').value = item.wh_goodsunits;
	}
	if (item.wh_goodsunits && qs('#WH_nomenclatureUnits')) {
		qs('#WH_nomenclatureUnits').value = item.wh_goodsunits;
	}
	if (item.wh_goodsnomenclatureqty && item.wh_goodsnomenclatureqty !== 'null' && qs('#wh_goodsnomenclatureqty')) {
		qs('#wh_goodsnomenclatureqty').value = item.wh_goodsnomenclatureqty;
	}
	if (item.wh_goodswhunits && qs('#WH_goodsWHUnits') && qs('#WH_goodsWHUnits2')) {
		qs('#WH_goodsWHUnits').value = item.wh_goodswhunits;
		qs('#WH_goodsWHUnits2').value = item.wh_goodswhunits;
	}
	if (item.wh_nomenclatureunits && qs('#setGoodsUnit')) {
		qs('#setGoodsUnit').innerHTML = measuringUnits.find(elem => {
			return elem.id == item.wh_nomenclatureunits;
		}).name;
	}
	if (item.wh_goodswhqty && item.wh_goodswhqty !== 'null' && qs('#wh_goodswhqty')) {
		qs('#wh_goodswhqty').value = item.wh_goodswhqty;
	}
	if (item.clear) {
		clear(qs(`#${item.clear}`));
	}

}
function searchGoodsByName(inputElement, resultsDiv) {
//					console.log(inputElement.value);
	clear(resultsDiv);
	if (inputElement.value.trim().length >= 3) {
		fetch('/pages/warehouse/goods/goods_IO.php', {
			body: JSON.stringify({action: 'searchGoods', search: inputElement.value.trim()}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if ((jsn.items || []).length > 0) {
					jsn.items.forEach((item) => {
						let reg = new RegExp("(" + inputElement.value.trim() + ")", 'gi');
						let html = item.WH_goodsName.replace(reg, function (str) {//itemsName
							return '<b style="color: pink;">' + str + '</b>';
						});

						let params = '';

						for (let elem in item) {
							params += `data-${elem.toString().toLowerCase()}="${item[elem]}"`;
						}
						resultsDiv.appendChild(el('div', {innerHTML: `<div style="background-color: white; padding: 3px 10px; border: 1px solid silver; border-radius: 0px; white-space: nowrap;">${item.parentsName || ''} ${html || ''}<div style="position: absolute; width: 100%; height: 100%; cursor: pointer; top: 0px; left: 0px; z-index: 10;" data-clear="${resultsDiv.id}"  data-function="choeseItem" ${params}></div></div>`}));
					});
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});
	}
}


function searchGoodsByBC(inputElement) {
	if (inputElement.value.trim().length >= 3) {
		fetch('/pages/warehouse/goods/goods_IO.php', {
			body: JSON.stringify({action: 'searchGoodsBC', search: inputElement.value.trim()}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if ((jsn.items || []).length == 1) {
					console.log('jsn.items[0]', jsn.items[0]);
					let lcItem = {};
					for (let elem in jsn.items[0]) {
						lcItem[elem.toString().toLowerCase()] = jsn.items[0][elem];
					}
					choeseItem(lcItem);
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера: <br><br><i>" + e + "</i>");
			}
		});
	}
}


function saveGoodsToNomenclature() {
	let item = {
		id: qs('#idgoods').value.trim() || null,
		goodsName: qs('#goodsName').value.trim() || null,
		goodsBarCode: qs('#goodsBarCode').value.trim() || null,
		itemUnits: ((qs('#itemUnits') || {}).value || '').trim() || null,
		wh_goodsnomenclatureqty: ((qs('#wh_goodsnomenclatureqty') || {}).value || '').trim() || null,
		WH_goodsWHUnits: ((qs('#WH_goodsWHUnits') || {}).value || '').trim() || null,
		wh_goodswhqty: ((qs('#wh_goodswhqty') || {}).value || '').trim() || null,
		idWH_nomenclature: ((qs('#idWH_nomenclature') || {}).value || '').trim() || null,
		ballance: qs('#goodsQty').value.trim() || null
	};
	console.log(item);
	fetch('/pages/warehouse/goods/goods_IO.php', {
		body: JSON.stringify({action: 'saveGoodsToNomenclature', item: item}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if (jsn.success) {
				document.location.reload(true);
			}
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера: <br><br><i>" + e + "</i>");
		}
	});
}