var app = new Vue({
	el: '#vueapp',
	data: {

	},
	watch: {},
	computed: {},
	methods: {},
	mounted: function () {

	}
}
);


//fetch('/pages/admin/disciplines/IO.php', {
//				body: JSON.stringify(options),
//				credentials: 'include',
//				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
//			}).then(result => result.text()).then(function (text) {
//				try {
//					let jsn = JSON.parse(text);
//					console.log(jsn);
//					if (jsn.success && (jsn.themes || []).length) {
//						app.themes = jsn.themes;
//						setTimeout(function () {
//							M.updateTextFields();
//							let elems = document.querySelectorAll('.materialize-textarea');
//							elems.forEach(elem => {
//								M.textareaAutoResize(elem);
////								console.log();
//							});
//						}, 100);
//					}
//					if (jsn.saved) {
//						M.toast({html: '<b>Сохранено</b>', classes: 'mytoast success'});
//					}
//				} catch (e) {
////					MSG(e);
//					console.log('no');
//					console.log(e);
//				}
//			});