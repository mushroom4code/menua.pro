<script>
	let ws;
	document.addEventListener("DOMContentLoaded", function () {

		let preloadedUsers = <?= json_encode($users, JSON_UNESCAPED_UNICODE); ?>;

		function makeFingerRow(data) {
			return el('div', {className: 'fingerLogRow', innerHTML: `<div><i class="fas fa-user"></i></div><div><a target="_blank" href="/pages/personal/?employee=${data.user.id}">${data.user.name}</a></div><div>${data.time}</div>`});
		}

		async function connect() {
			ws = new WebSocket("wss://192.168.23.100/sync/api/finger/");
			ws.onopen = function () {
				console.log('opened');
			};
			ws.onmessage = function (e) {
				console.log('message');
				try {
					let socketData = JSON.parse(e.data);
					console.log('PARSED:', socketData);
					if (socketData) {

					}
				} catch (e) {
				}
			};
			ws.onclose = function (e) {
				console.log('Socket is closed. Reconnect will be attempted in 1 second.', e.reason);
				setTimeout(function () {
					connect();
				}, 5000);
			};
			ws.onerror = function (err) {
				console.log('Socket encountered error: ', err.message, 'Closing socket');
				setTimeout(function () {
					ws.close();
				}, 1000);
			};
		}
		connect();
	});
</script>
