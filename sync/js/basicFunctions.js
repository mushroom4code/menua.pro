
function el(elem, options = {}) {
	let element = document.createElement(elem);
	if (options.hasOwnProperty('innerHTML')) {
		element.innerHTML = options.innerHTML;
	}
	if (options.hasOwnProperty('className')) {
		element.className = options.className;
	}

	return element;
}


function date(timestamp, long = false) {
	let date = new Date(timestamp * 1000);
	let monthNames = {
		"01": "января",
		"02": "февраля",
		"03": "марта",
		"04": "апреля",
		"05": "мая",
		"06": "июня",
		"07": "июля",
		"08": "авгнуста",
		"09": "сентября",
		"10": "октября",
		"11": "ноября",
		"12": "декабря"
	};

	let day = date.getDate() > 9 ? date.getDate() : '0' + date.getDate();
	let month = date.getMonth() + 1;
	let monthIndex = month > 9 ? month : '0' + month;
	let year = date.getFullYear();
	if (long) {
		return day + ' ' + (monthNames[monthIndex]) + ' ' + year + 'г.';
	} else
	{
		return year + '.' + (monthIndex) + '.' + day;

}
}

let qs = (selector, where = document) => {
	return where.querySelector(selector);
};

function qsa(selector) {
//	console.log("qsa:", selector);
	return document.querySelectorAll(selector) || [];
}

function RDS(length = 10, intOnly = false) {
	let characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	if (intOnly) {
		characters = '0123456789';
	}
	let charactersLength = characters.length;
	let randomString = '';
	for (let i = 0; i < length; i++) {
		randomString += characters[Math.floor(Math.random() * charactersLength)];
	}
	return randomString;
}



let MSG = async function (msg) {
	let type,
			data,
			text;
	if (typeof (msg) === 'object') {
		type = msg.type || 'error';
		data = msg.data || null;
		text = msg.text || 'отсутствует текст сообщения';
	} else {
		type = msg.type || 'error';
		data = msg.data || null;
		text = msg.text || msg;
	}

	let titles = {
		error: ['Ошибка', 'Error', 'Проблема', 'Тут что-то не так', '"%(*!№SDf%HAS!d', 'Примите меры!', 'Так быть не должно', 'Achtung!', 'Всё пропало...', 'Беда'],
		success: ['Успех!', 'Потрясающе!', 'Великолепно!', 'Восхитилельно!', 'Вот это да!', 'Так держать', 'Это здорово!', 'Умничка!', 'Победа!', 'Ты лучше всех!', 'Замечательно!', 'Всегда бы так!', 'Получилось!'],
		neutral: ['Так...']
	}
	;
	let submits = {
		error: ['Ok', 'Да', 'Хорошо', 'Ладно', 'Понятно', 'Я не специально', '...ээээ...', 'Мне жаль', 'Это в последний раз', 'я передам кому надо', 'Я не трогала, оно само'],
		success: ['ok', 'Да', 'Спасибо', 'Да не за что', 'Угу', 'Ага', 'Конечно', 'Ну тк'],
		neutral: ['ok']
	};

	let box = el('div', {className: 'modal ' + type});
	box.style.position = 'fixed';
	box.appendChild(el('h2', {innerHTML: titles[type][Math.floor(Math.random() * titles[type].length)]}));
	box.appendChild(el('div', {className: 'box-body', innerHTML: text}));


	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it
		if ((msg.options || []).length) {

			for (let option of msg.options) {
				let btn = el('button', {innerHTML: option.text[Math.floor(Math.random() * option.text.length)]});
				box.appendChild(btn);
				btn.addEventListener('click', function () {
					if (box) {
						box.parentNode.removeChild(box);
					}
					resolve(option.value);
				});
			}

		} else {
			let btn = el('button', {innerHTML: submits[type][Math.floor(Math.random() * submits[type].length)]});
			box.appendChild(btn);
			btn.addEventListener('click', function () {
				if (box) {
					box.parentNode.removeChild(box);
				}

				resolve(data);
			});
		}


	});
	if (msg.autoDismiss) {
		setTimeout(function () {
			if (box.parentNode) {
				box.parentNode.removeChild(box);
			}
		}, msg.autoDismiss);
	}
	document.body.appendChild(box);
	return promise;
};

//let idle = 0;
//setInterval(function () {
//	idle++;
//	if (idle > 360) {
//		qs('#world').style.display = 'block';
//	}
//}, 1000);
//document.addEventListener('mousemove', function () {
//	idle = 0;
//	qs('#world').style.display = 'none';
//});
//document.addEventListener('keydown', function (e) {
//	if (e.ctrlKey && e.keyCode === 32) {
//		idle = 400;
//		qs('#world').style.display = 'block';
//	}
//});


function getDataAttributes(el) {
	var data = {};
	[].forEach.call(el.attributes, function (attr) {
		if (/^data-/.test(attr.name)) {
			var camelCaseName = attr.name.substr(5).replace(/-(.)/g, function ($0, $1) {
				return $1.toUpperCase();
			});
			data[camelCaseName] = ((attr.value == Number(attr.value) && attr.value != '') ? Number(attr.value) : attr.value);
		}
	});
	return data;
}


function setDataAttributes(el, data) {
	Object.keys(data).forEach(function (key) {
		var attrName = "data-" + key.replace(/[A-Z]/g, function ($0) {
			return "-" + $0.toLowerCase();
		});
		el.setAttribute(attrName, data[key]);
	});
}


let rt = function () {
	return arguments[Math.floor(Math.random() * arguments.length)];
};



function filterKeys(key) {
	let keymap = {'й': 'q', 'ц': 'w', 'у': 'e', 'к': 'r', 'е': 't', 'н': 'y', 'г': 'u', 'ш': 'i', 'щ': 'o', 'з': 'p', 'х': '[', 'ъ': ']', 'ф': 'a', 'ы': 's', 'в': 'd', 'а': 'f', 'п': 'g', 'р': 'h', 'о': 'j', 'л': 'k', 'д': 'l', 'ж': ';', 'э': '\'', 'я': 'z', 'ч': 'x', 'с': 'c', 'м': 'v', 'и': 'b', 'т': 'n', 'ь': 'm', 'б': ',', 'ю': '.', '.': '/', 'Й': 'Q', 'Ц': 'W', 'У': 'E', 'К': 'R', 'Е': 'T', 'Н': 'Y', 'Г': 'U', 'Ш': 'I', 'Щ': 'O', 'З': 'P', 'Х': '{', 'Ъ': '}', 'Ф': 'A', 'Ы': 'S', 'В': 'D', 'А': 'F', 'П': 'G', 'Р': 'H', 'О': 'J', 'Л': 'K', 'Д': 'L', 'Ж': ':', 'Э': '\"', 'Я': 'Z', 'Ч': 'X', 'С': 'C', 'М': 'V', 'И': 'B', 'Т': 'N', 'Ь': 'M', 'Б': '<', 'Ю': '>', ',': '?',

		'ё': '`',
		'Ё': '~',
		'"': '@',
		'№': '#',
		';': '$',
		':': '^',
		'?': '&'



	};

	return key.split('').map(function (char) {
		return keymap[char] || char;
	}).join("");

}


function clear(element) {
//	console.log('clear', element);
//	let cnt = 0;
	while (element.firstChild) {
		if (typeof (element.firstChild) === 'object') {
//			console.log('recursion');
			clear(element.firstChild);
		}
		element.firstChild.remove();
//		cnt++;
	}
//	console.log(`cleared ${cnt} elements`);
}
function clearElement(element) {
//	console.log('clear', element);
//	let cnt = 0;
	while (element.firstChild) {
		if (typeof (element.firstChild) === 'object') {
//			console.log('recursion');
			clear(element.firstChild);
		}
		element.firstChild.remove();
//		cnt++;
	}
//	console.log(`cleared ${cnt} elements`);
}

function GETreloc(name, value) {
	var url = new URL(window.location.href);
	if (value === null || value === undefined || value === '') {
		url.searchParams.delete(name);
	} else {
		url.searchParams.set(name, value);
	}
	window.location.href = `${url.pathname}?${url.searchParams.toString()}`;
}

function GR(params = {}) {
	var url = new URL(window.location.href);
	for (let param in params) {
		let value = params[param];
		let name = param;
		if (value === null || value === undefined || value === '') {
			url.searchParams.delete(name);
		} else {
			url.searchParams.set(name, value);
		}
	}
	window.location.href = `${url.pathname}?${url.searchParams.toString()}`;
}

function HREFreloc(href, name, value) {
	if (href) {
		var url = new URL(href);
	} else {
		var url = new URL(window.location.href);
	}

	if (value === null || value === undefined || value === '') {
		url.searchParams.delete(name);
	} else {
		url.searchParams.set(name, value);
	}
	return `${url.pathname}?${url.searchParams.toString()}`;
}


function onlyDigits(value) {
	value = value.replace(/[\,]/g, ".");
	value = value.replace(/[^\d|^\.|^\-]/g, "");
	return value;
}


function digon() { 
//	console.log(event);
	let event = window.event;
	let target = event.originalTarget || event.target;
	target.value = onlyDigits(target.value);
}

var decodeHtmlEntity = function (str) {
	return str.replace(/&#(\d+);/g, function (match, dec) {
		return String.fromCharCode(dec);
	});
};

var _0 = (val) => val.toString().length > 1 ? val : `0${val}`;

function dec2bin(dec, lngt = 2) {
	let str = (dec >>> 0).toString(2);
	if (str.length < lngt) {
		for (let n = str.length; n < lngt; n++) {
			str = '0' + str;
		}
	}
	return str;
}

function nf(number) {
	return new Intl.NumberFormat({maximumSignificantDigits: 3}).format(number);
}



function human_plural_form(count, words, returnNum = false) {
	var cases = [2, 0, 1, 1, 1, 2];
	return  returnNum ? (count + ' ') : '' + words[ (count % 100 > 4 && count % 100 < 20) ? 2 : cases[ Math.min(count % 10, 5)] ];
}

function selectElementContents(el) {
	var body = document.body, range, sel;
	if (document.createRange && window.getSelection) {
		range = document.createRange();
		sel = window.getSelection();
		sel.removeAllRanges();
		try {
			range.selectNodeContents(el);
			sel.addRange(range);
		} catch (e) {
			range.selectNode(el);
			sel.addRange(range);
		}
	} else if (body.createTextRange) {
		range = body.createTextRange();
		range.moveToElementText(el);
		range.select();
	}
}


function serializeFormData(formData) {
	return Array.from(formData.entries())
			.reduce((data, [field, value]) => {
				let [_, prefix, keys] = field.match(/^([^\[]+)((?:\[[^\]]*\])*)/);

				if (keys) {
					keys = Array.from(keys.matchAll(/\[([^\]]*)\]/g), m => m[1]);
					value = updateFormData(data[prefix], keys, value);
				}
				data[prefix] = value;
				return data;
			}, {});
}

function updateFormData(data, keys, value) {
	if (keys.length === 0) {
		return value;
	}
	let key = keys.shift();
	if (!key) {
		data = data || [];
		if (Array.isArray(data)) {
			key = data.length;
		}
	}
	let index = +key;
	if (!isNaN(index)) {
		data = data || {};
		key = index;
	}
	data = data || {};
	let val = updateFormData(data[key], keys, value);
	data[key] = val;
	return data;
}
function JJ(input) {
	return JSON.parse(JSON.stringify(input));
}