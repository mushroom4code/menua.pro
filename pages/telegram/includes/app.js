var app = new Vue({
	el: '#vueapp',
	data: {
		selectedClient: null,
		clients: [],
		message: '',
		lastmessage: null
	},
	watch: {
		selectedClient: function () {
			window.requestAnimationFrame(function () {
				document.querySelector('.messages').scrollTop = document.querySelector('.messagesContent').offsetHeight;
			});
			fetch('IO.php', {
				body: JSON.stringify({
					action: 'markasread',
					client: this.currentClient.id
				}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);
					if (jsn.clients) {
						jsn.clients.forEach(jsonClient => {
							window.requestAnimationFrame(function () {
								let index = app.clients.findIndex(client => {
									return client.id === jsonClient.id;
								});

								Vue.set(app.clients, index, jsonClient);

							});
						});
					}
				} catch (e) {
					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
			});
		}
	},
	computed: {
		currentClient: function () {
			return this.clients.find(client => {
				return client.id === this.selectedClient;
			}) || false;
		},
		clientsSorted: function () {
			return this.clients.sort(function (a, b) {
				return new Date(b.messages[b.messages.length - 1].time) - new Date(a.messages[a.messages.length - 1].time);
			});
		}

	},
	methods: {
		send: function () {
			let self = this;
			fetch('IO.php', {
				body: JSON.stringify({
					action: 'sendmessage',
					client: this.currentClient.id,
					clientsTG: this.currentClient.clientsTG,
					message: this.message
				}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);
					if (jsn.clients) {
						jsn.clients.forEach(jsonClient => {
							window.requestAnimationFrame(function () {
								let index = app.clients.findIndex(client => {
									return client.id === jsonClient.id;
								});
								Vue.set(app.clients, index, jsonClient);

							});
						});
						window.requestAnimationFrame(function () {
							document.querySelector('.messages').scrollTop = document.querySelector('.messagesContent').offsetHeight;
						});
					}
				} catch (e) {
					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
			});
			this.message = '';
			//fetch




		},
		getupdates: function () {
			fetch('IO.php', {
				body: JSON.stringify({
					action: 'geupdates',
					lastmessage: this.lastmessage
				}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);
					if (jsn.lastmessage) {
						app.lastmessage = jsn.lastmessage;
					}
					if (jsn.clients) {
						jsn.clients.forEach(jsonClient => {
							window.requestAnimationFrame(function () {
								let index = app.clients.findIndex(client => {
									return client.id === jsonClient.id;
								});
								if (index > -1) {
									Vue.set(app.clients, index, jsonClient);
								} else {
									app.clients.push(jsonClient);
								}
							});
						});
						if (document.querySelector('.messages')) {
							window.requestAnimationFrame(function () {
								document.querySelector('.messages').scrollTop = document.querySelector('.messagesContent').offsetHeight;
							});
						}
					}
				} catch (e) {
//					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
			});
		}
	},
	mounted: function () {

		setInterval(this.getupdates, 5000);


		fetch('IO.php', {
			body: JSON.stringify({action: 'loadmessages'}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if (jsn.lastmessage) {
					app.lastmessage = jsn.lastmessage;
				}
				if (jsn.clients) {
					app.clients = jsn.clients;
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		}); //fetch


		this.$nextTick(function () {
			//			this.poolArray = (JSON.parse(window.localStorage.getItem('poolArray')) || []);
			//			this.call.smsTemplate = (JSON.parse(window.localStorage.getItem('smsTemplate')) || '');
		});
	}
}
);
