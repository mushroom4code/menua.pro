async function confirmItem(ioi, oiqty) {
	fetch('/pages/warehouse/goods/goods_IO.php', {
		body: JSON.stringify({action: 'confirmItem', item: ioi, quantity: oiqty}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(() => {
		window.location.reload();
	});
	console.log(ioi, parseFloat(oiqty));
}