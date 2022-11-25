let IDLE_TIMER = 0;
setInterval(function () {
	IDLE_TIMER++;
//	console.log(IDLE_TIMER);
	if (IDLE_TIMER >= 160) {
		document.location.reload();
	}
}, 1000);

document.addEventListener('mousemove', function () {
	IDLE_TIMER = 0;
});