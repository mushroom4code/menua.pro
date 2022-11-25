document.addEventListener("DOMContentLoaded", function () {

	let socket = new WebSocket("wss://192.168.128.100:5038");

	socket.onopen = () => {
		console.log('Соединились');
	};

	socket.onerror = (error) => {
		console.log('Ошибка: ', error.message || 'без описания');
	};


	socket.onclose = () => {
		console.log('Соединение закрыто');
	};

	socket.onmessage = (event) => {
		console.log('Данные', event.data);

	};
});