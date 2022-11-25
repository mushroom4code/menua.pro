let loadGoods = function (parent = null)
{
	console.log('loadGoods');
	currentLevel = parent === 'null' ? null : parent;
	fetch('goods_IO.php', {
		body: JSON.stringify({
			loadGoods: true,
			parent: parent
		}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			if ((jsn.msgs || []).length) {
				for (let msg of jsn.msgs) {
					let data = await MSG(msg);
					if (data === true) {
					}
					console.log(data);
				}
			}





			let goodsTable = qs('#goodsTable');
			goodsTable.innerHTML = '';
			if (typeof (jsn.parentLVL) !== "undefined") {
				let tr = el('tr');
				tr.appendChild(el('th', {innerHTML: `<i data-function="openDir" data-dir="${jsn.parentLVL}" class="fas fa-chevron-left btn"></i>`}));
				tr.appendChild(el('td', {innerHTML: ``}));
				tr.appendChild(el('td', {innerHTML: ``}));
				tr.appendChild(el('td', {innerHTML: `<div data-function="openDir" class="btn" data-dir="${jsn.parentLVL}">...На уровень выше</div>`}));
				tr.appendChild(el('td', {className: 'btn', innerHTML: ``}));
				goodsTable.appendChild(tr);
			}
			if ((jsn.goods || []).length) {
				//console.log(jsn.goods);
				jsn.goods.sort(function (a, b) {
					if (a.goodsEntryType != b.goodsEntryType) {
						return a.goodsEntryType - b.goodsEntryType;
					}
					if (a.goodsName.toLowerCase() < b.goodsName.toLowerCase()) {
						return -1;
					}
					if (a.goodsName.toLowerCase() > b.goodsName.toLowerCase()) {
						return 1;
					}
					return 0;
				});
				for (let item of jsn.goods) {
					let tr = el('tr');
					//	console.log(item);
					let icon = el('th');
					if (item.goodsEntryType === '1') {
						icon.innerHTML = `<i data-function="openDir" data-dir="${item.idgoods}" class="fas fa-folder btn"></i>`;


						//
					} else {
						if (item.goodsMinAmnt !== undefined && item.goodsMaxAmnt !== undefined) {



							if (Math.round(item.qty * 1000) / 1000 <= 0) {
								icon.innerHTML = `<i class="fas fa-cart-arrow-down" style="color: #E22;"></i>`;
							} else if (+item.qty <= +item.goodsMinAmnt) {
								icon.innerHTML = `<i class="fas fa-cart-arrow-down" style="color: #fefe54;"></i>`;
							} else {
								icon.innerHTML = `<i class="far fa-file-alt"></i>`;
							}


						} else {
							icon.innerHTML = `<i class="far fa-file-alt" style="color: #555;"></i>`;
						}


					}

					tr.appendChild(icon);
					tr.appendChild(el('td', {className: 'R', innerHTML: `${item.idgoods}`}));
					let barcodeDIV = el('td', {className: 'C'});
					if (item.goodsEntryType === '1') {
						//apply nothing
					} else {

						if ((item.goodsBarcode || []).length > 0) {
							let aA = el('a');
							aA.href = `/sync/plugins/barcodePrint.php?item=${item.idgoods}`;
							aA.target = '_blank';
							aA.innerHTML = `<i class="fas fa-barcode"></i>`;
							let barcodeDiv = el('div');
							barcodeDiv.style.backgroundColor = 'white';
							aA.appendChild(barcodeDiv);
							for (let barcode of item.goodsBarcode) {
								//	console.log(item.goodsBarcode);
								barcodeDiv.innerHTML += `<svg style="border: 1px solid black; margin: 30px;" class="barcode"
						 jsbarcode-value="${item.goodsBarcode}"
						 jsbarcode-width="1"
						 jsbarcode-height="40" 
						 jsbarcode-fontSize="12" 
						 jsbarcode-font="Arial" 
						 ></svg>`;
							}

							if (item.goodsBarcode.length > 1) {
								aA.appendChild(el('span', {className: 'BCmult', innerHTML: `${item.goodsBarcode.length}x`}));

							}
							barcodeDIV.appendChild(aA);
						}

					}
					tr.appendChild(barcodeDIV);
					tr.appendChild(el('td', {innerHTML: `<div${item.goodsEntryType == 1 ? (`
data-function="openDir" class="btn" data-dir="${item.idgoods}"`) : ''}>${item.goodsEntryType != 1 ? `<a href="/pages/warehouse/goods/item/?item=${item.idgoods}" data-scroll="true">` : ''}${item.goodsName || 'ОТСУТСВУЕТ'}</a></div>`}));

					tr.appendChild(el('td', {className: 'R', innerHTML: `${item.goodsEntryType === '1' ? ('') : (Math.round(item.qty * 1000) / 1000 || `0`)}`}));
					tr.appendChild(el('td', {innerHTML: `${item.goodsEntryType === '1' ? ('') : (item.unitsName || `...`)}`}));

					tr.appendChild(el('td', {className: 'btn', innerHTML: `<a href="/pages/warehouse/goods/item/?item=${item.idgoods}"><i data-edit-item="${item.idgoods}" class="fas fa-edit"></i></a>`}));
					goodsTable.appendChild(tr);

				}
				JsBarcode(".barcode").init();
				let url = new URL(window.location.href);
				if (url.searchParams.get('scrollback')) {
					window.scrollTo(0, url.searchParams.get('scrollback'));
				}
			}
		} catch (e) {
			MSG("102Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	}).then(() => {
		setTimeout(function () {
			history.pushState(null, null, '/pages/warehouse/goods/?dir=' + parent);
		}, 100);
	});

};