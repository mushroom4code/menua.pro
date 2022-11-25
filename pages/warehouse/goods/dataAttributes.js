function getDataAttributes(el) {
	var data = {};
	[].forEach.call(el.attributes, function (attr) {
		if (/^data-/.test(attr.name)) {
			var camelCaseName = attr.name.substr(5).replace(/-(.)/g, function ($0, $1) {
				return $1.toUpperCase();
			});
			data[camelCaseName] = (attr.value == Number(attr.value) ? Number(attr.value) : attr.value);
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