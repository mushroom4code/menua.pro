window.addEventListener('DOMContentLoaded', function () {
	var app = new Vue({
		el: '#vueapp',
		data: {
			servicesSearchText: '',
			suggestions: [],
			suggestionsIndex: 0
		},
		methods: {
			summByService: function (service) {
				return (service.qty || 0) * (service.price || 0);
			},
			confirmSearch: function (n) {
				delete(this.suggestions[n].servicesNameHighlighted);
				delete(this.suggestions[n].servicesDuration);
				window.location.href = `/pages/price/index.php?service=${this.suggestions[n].idservices}`;
				console.log(this.suggestions[n]);
				this.resetSearch();
			},
			resetSearch: function () {
				this.servicesSearchText = '';
				this.suggestions = [];
				this.lastSuccessSearchLength = 0;
			},

			searchServices: function (event) {
				if (event.keyCode === 8) {
					this.suggestions = [];
				}
				if (event.keyCode === 27) {
					this.resetSearch();
					return false;
				}
				if (event.keyCode === 38) {
					event.stopPropagation();
					event.preventDefault();
					if (this.suggestionsIndex > 0) {
						this.suggestionsIndex--;
					} else {
						this.suggestionsIndex = 0;
					}
					return false;
				}
				if (event.keyCode === 40) {
					event.stopPropagation();
					event.preventDefault();
					if (this.suggestionsIndex < this.suggestions.length - 1) {
						this.suggestionsIndex++;
					} else {
						this.suggestionsIndex = this.suggestions.length - 1;
					}
					return false;
				}
				if (event.keyCode === 13) {
					event.stopPropagation();
					event.preventDefault();
					this.confirmSearch(this.suggestionsIndex);
					return false;
				}

				if (event.target.value.length < 3) {
					this.suggestions = [];
					return false;
				}

				this.suggestionsIndex = 0;
				fetch('/sync/api/local/services/suggestions.php', {
					body: JSON.stringify({search: event.target.value, newonly: true}),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(function (text) {
					try {
						let jsn = JSON.parse(text);
						if (jsn.success) {
							app.lastSuccessSearchLength = event.target.value.length;
							app.suggestions = jsn.services;
						} else {

						}
					} catch (e) {
						console.log('no');
						app.schedule = [];
						console.log(e);
					}
				});
				console.log(event.target.value, event.keyCode);
			}

		},
		mounted: function () {
			this.$nextTick(function () {
				//			this.poolArray = (JSON.parse(window.localStorage.getItem('poolArray')) || []);
				//			this.call.smsTemplate = (JSON.parse(window.localStorage.getItem('smsTemplate')) || '');
			});
		}
	});


});
