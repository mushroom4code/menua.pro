<ul class="horisontalMenu">
	<li><a href="/pages/checkout/">Договор</a></li>
	<li><a href="/pages/checkout/payments.php">Клиенты</a></li>
	<li><a href="" onclick="fetch('IO.php', {
				body: JSON.stringify({action: 'plusOne'}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			});
			MSG({type: 'success', text: 'Отправила', autoDismiss: 800});
			void(0);
			return false;">( +1 )</a></li>

</ul>
