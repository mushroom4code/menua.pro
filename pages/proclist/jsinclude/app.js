var app = new Vue({
	el: '#vueapp',
	data: {
		loading: false,
		data: {
			lastSearchContract: null,
			appointmentDate: null,
			personnel: [],
			client: {
				idclients: null,
				contracts: [],
				servicesApplied: [],

			}
		}
	},
	watch: {},
	computed: {
		appRender: {
			get: function () {
				return JSON.stringify({
					data: this.data
				}, null, 2);
			},
			set: function (newValue) {
				let data = JSON.parse(newValue);
				this.data = data.data;
			}
		}
	},
	methods: {
		servicesAppliedTotal: function (servicesApplied) {
//			console.log("servicesApplied", JSON.parse(JSON.stringify(servicesApplied)));
 

			let reservedArrSum = servicesApplied.reduce(function (a, b) {
				return a + b.reservedArr.reduce(function (c, d) {
					return c + d['servicesAppliedQty'] * d['servicesAppliedPrice'];
				}, 0);
				//b['reservedArr']['servicesAppliedQty'] * b['servicesAppliedPrice'];
			}, 0);

			let doneArrSum = servicesApplied.reduce(function (a, b) {
				return a + b.doneArr.reduce(function (c, d) {
					return c + d['servicesAppliedQty'] * d['servicesAppliedPrice'];
				}, 0);
				//b['reservedArr']['servicesAppliedQty'] * b['servicesAppliedPrice'];
			}, 0);

			return reservedArrSum + doneArrSum;
		},
		deleteServiceApplied: async function (serviceApplied) {
			let result = await getFetch('/pages/proclist/appIO.php', {action: 'deleteServiceApplied', idservicesApplied: serviceApplied.idservicesApplied});
			this.getServicesApplied();

		},
		status: function (serviceApplied) {
			if (serviceApplied.servicesAppliedDeleted) {
				return 'Удалена';
			}
			if (serviceApplied.servicesAppliedFineshed) {
				return 'Завершена';
			}
			if (serviceApplied.servicesAppliedStarted) {
				return 'Начата';
			}

			return '';
		},
		duration: function (servicesDuration) {
			if (servicesDuration < 60) {
				return ` (${servicesDuration}м.)`;
			} else {
				return ` (${servicesDuration / 60}ч.)`;
			}
		},

		makeAnAppointment: async function (params) {
			let qty = 1;
			if (!this.data.lastSearchQty) {
				MSG('Процедуры закончились');
				return false;
			}
			if (this.data.lastSearchQty > 1) {
				console.log("SELECT QTY!!!!!");
				let ptqty = prompt(`Укажите количество (доступно ${this.data.lastSearchQty})`, 1);
				qty = Math.min(ptqty, this.data.lastSearchQty);
			}
			if (!qty) {
				MSG('Что-то не так с количеством');
				return false;
			}
			this.data.lastSearchQty -= qty;
			let dataToSend = {
				action: 'makeAnAppointment',
				service: params.service,
				personal: params.personnel,
				qty: qty,
				client: this.data.client.idclients,
				timeBegin: params.time,
				contract: this.data.lastSearchContract,
				price: this.data.f_salesContentPrice,
				options: params.options || null
			};
			console.log(JSON.parse(JSON.stringify(dataToSend)));
			let result = await getFetch('/pages/proclist/appIO.php', dataToSend);
			if (result.success) {
				this.getServicesApplied();
				this.getContracts();
				this.data.personnel = [];
			}
			console.log(result);

		},
		getServicesApplied: async function () {
			let result = await getFetch('/pages/proclist/appIO.php', {action: 'getServicesApplied', idclients: this.data.client.idclients});

			this.data.client.servicesApplied = (result.servicesApplied.sort(function (a, b) {
				return new Date(a.servicesAppliedTimeBegin) - new Date(b.servicesAppliedTimeBegin);
			}) || []);
		},
		getContracts: async function () {
			let result = await getFetch('/pages/offlinecall/IO.php', {action: 'getContracts', client: this.data.client.idclients});
			this.data.client.contracts = (result.contracts || []);
		},
		//{"action":"getAvailableTime","database":"1","":"2021-10-21","service":319}
		getAvailableTime: async function (params) {
			console.log(JSON.parse(JSON.stringify(params)));
			this.data.lastSearchContract = params.info.idf_sales;
			this.data.lastSearchQty = params.remains;
			this.data.f_salesContentPrice = params.info.f_salesContentPrice;
			if (this.data.appointmentDate && params.info.idservices) {
				this.loading = true;
				this.data.personnel = [];
				let result = await getFetch('/pages/remotecall/IO.php',
						{
							action: 'getAvailableTime',
							date: this.data.appointmentDate,
							service: params.info.idservices
						}
				);
				this.loading = false;
				if (result) {
					this.data.personnel = result;
					console.log(result);
				}
			} else {
				MSG('Укажите дату');
			}

		}
	},
	mounted: function () {
		this.data.client.idclients = (new URLSearchParams(decodeURI((new URL(window.location.href)).search.substring(1)))).get("client");
		this.getServicesApplied();
		this.getContracts();
	}
}
);

function getFetch(url, params) {
	return fetch(url, {
		body: JSON.stringify(params),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(function (text) {
		try {
			let jsn = JSON.parse(text);
			return jsn;
		} catch (e) {
			console.error(e);
		}
	});
}

function mydate(date) {
	let newDate = new Date(date);
//	console.log(newDate);
	return newDate.toLocaleString('ru-RU', {day: 'numeric', month: 'short', year: '2-digit', weekday: 'short'});
}
function mytime(date) {
	let newDate = new Date(date);
//	console.log(newDate);
	return newDate.toLocaleString('ru-RU', {hour: 'numeric', minute: 'numeric'});
}
