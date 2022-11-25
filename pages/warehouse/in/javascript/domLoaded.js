document.addEventListener("DOMContentLoaded", function () {
	console.log('loaded');
	loadUnits();
	let hiddeninput = el('input');
	hiddeninput.type = 'hidden';
	hiddeninput.id = 'hiddeninput';
	document.body.appendChild(hiddeninput);

	let AFEL = qs('#hiddeninput');
	let start = performance.now();
	let  intId = false;
	document.addEventListener('keypress', async function (e) {
		let interval = performance.now() - start;
		console.log('keypress');

		start = performance.now();

		if (document.activeElement === document.body) {
			if (interval > 70) {
				AFEL.value = '';
				AFEL.value = AFEL.value + filterKeys(e.key);
			} else {
				if (e.keyCode !== 13) {
					AFEL.value = AFEL.value + filterKeys(e.key);
					if (intId) {
						clearTimeout(intId);
					} else {

					}
					intId = setTimeout(function () {
						makeRequest(AFEL);
					}, 100);

				} else {
					if (intId) {
						clearTimeout(intId);
					}
					makeRequest(AFEL);
				}
			}
		}

	});

	if (qs('#date')) {
		loadReportForDate(qs('#date').value);
	}
	;
});