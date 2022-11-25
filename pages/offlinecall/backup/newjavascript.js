
function renderSchedule() {
	let scheduleHeader = qs('#scheduleHeader');
	let scheduleContent = qs('#scheduleContent');
	scheduleHeader.appendChild(el('div', {innerHTML: '&nbsp;'}));
	let timeTitleContainder = el('div');
	timeTitleContainder.style.borderRight = '1px solid silver';
	scheduleHeader.appendChild(timeTitleContainder);
	for (let n = s; n < e; n++) {
		let line = el('div');
		line.className = 'lineHeader';
		line.style.left = k * (n - s) + '%';
		line.style.width = k + '%';
		line.innerHTML = `${n}:00 - ${n}:59`;
		timeTitleContainder.appendChild(line);
	}





}
renderSchedule();

