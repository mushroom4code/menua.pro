/* global qs, getDataAttributes */

document.addEventListener('click', function (e) {
	let data = getDataAttributes(e.target);
	if (data.function) {
		console.log('data.function', data.function);
		if (typeof (window[data.function]) === "function") {
			let myfunc = window[data.function];
			data.DOM = e.target;
			delete(data.function);
			myfunc.apply(null, [data]);
			//console.log(data.function, data);
		} else {
			MSG({type: 'neutral', text: `Данный функционал ещё<br> не реализован :( <br> Функция <i>${data.function}</i> <br>всё ещё в разработке.`});
		}
	} else {
		//console.log('no data.function');
	}
});



