async function calendarChedule(data) {
	console.log(data);
}

async function cicle(dir, data) {

	if (!qs('#edit').checked) {
		return false;
	}
	let domElement = qs(`#r${data.r}_c${data.c}`);
	console.log(data);
	let states = ['00', '11', '10', '01', 'NA', 'SD', 'V'];
	let classes = states.map(el => 'H' + el); //['H00', 'H01', 'H10', 'H11'];
	let stateCount = classes.length;
	let state = states.indexOf(domElement.dataset.state);
	console.log('state', state);
	let fallback = state;

	if (dir != 0) {
		state++;
	} else {
		state = 0;
	}

	if (state > stateCount - 1) {
		state = 0;
	}
	if (state < 0) {
		state = stateCount - 1;
	}



	for (let cls of classes) {
		domElement.classList.remove(cls);
	}
	domElement.dataset.state = states[state];
	domElement.classList.add(classes[states.indexOf(domElement.dataset.state)]);
	if (event.altKey) {
		domElement.classList.add('duty');
	} else {
		domElement.classList.remove('duty');
	}

	let result = await fetch('IO.php', {
		body: JSON.stringify({
			user: data.user,
			date: data.date,
			halfs: states[state],
			duty: event.altKey,
			from: (qs(`#H${states[state]}start`) || {}).value,
			to: (qs(`#H${states[state]}end`) || {}).value
		}),
		credentials: 'include',
		method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
	}).then(result => result.text()).then(async function (text) {
		try {
			let jsn = JSON.parse(text);
			return jsn;
		} catch (e) {
			MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
		}
	});//fetch

	if ((result || {}).success) {

	} else {
		for (let cls of classes) {
			domElement.classList.remove(cls);
		}
		domElement.dataset.state = fallback;
		domElement.classList.add(classes[fallback]);
	}
}