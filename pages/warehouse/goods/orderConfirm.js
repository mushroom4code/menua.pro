async function orderConfirm(order) {

	let takeAction = await MSG({type: 'neutral', text: 'Закрываем заказ?', options: [{text: ['Да'], value: true}, {text: ['Нет'], value: false}]});

	if (takeAction) {
		fetch('/pages/warehouse/goods/goods_IO.php', {
			body: JSON.stringify({action: 'orderConfirm', order: order}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(() => {
			window.location.reload();
		});
	}
}