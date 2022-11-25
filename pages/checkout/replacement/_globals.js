let _remainsSummLeft = 0;
let _candidate = {};
let _totalSummToAppend = 0;
let _totalSummToRemove = 0;

//let _remains = [];
//let _contract = new URL(window.location.href).searchParams.get("sale");
//(async function () {
//	_remains = await fetch('/pages/checkout/IO.php', {
//		body: JSON.stringify({action: 'getRemains', contract: _contract}),
//		credentials: 'include',
//		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
//	}).then(result => result.text()).then(async function (text) {
//		try {
//			let jsn = JSON.parse(text);
//			if (jsn.msgs) {
//				jsn.msgs.forEach(msg => {
//					MSG(msg);
//				});
//			}
//			return (jsn.remains || []);
//		} catch (e) {
//			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
//		}
//	});
//	//renderRemains(_remains);
//})();


