function recursiveReduce(inputArray = [], str = '') {
	//	console.log('recursiveReduce.js');
	str = str.trim();
	let strArr = str.toString().split(" ".toString());
	let res = [];
	for (let bit of strArr) {
		res[res.length] = "(" + bit + ")";
	}

	for (let item of inputArray) {
		if (str !== '' && res.length > 0) {
			let skip = false;
			item.r = '';
			//	console.log('item', item);
			for (let bit of res) {
				//	console.log(bit);
				let reg = new RegExp(bit, 'gi');
				if (!item.hasOwnProperty('name') || !item.name || !item.name.toString().match(reg)) {
					skip = true;
				} else {
					item.r = (item.r || item.name).toString().replace(reg, function (str) {//itemsName
						return '<b class="red">' + str + '</b>';
					});
				}
			}
			if (!skip) {
				item.marked = true;
			} else {
				item.marked = false;
			}
		}

	}

	return inputArray.filter(el => {
		return el.marked == true;
	});
}