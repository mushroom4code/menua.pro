
let _MYmouseY = null;
let _autoscroll = false;

//document.addEventListener('mousemove', function (evt) {
//	evt = (evt || event);
//	_MYmouseY = evt.clientY;
//});


document.addEventListener("dragover", function (evt) {
	evt = (evt || event);
	_MYmouseY = evt.clientY;
	_autoscroll = true;
}, false);

document.addEventListener("dragend", function () {
	_autoscroll = false;
	console.log('_autoscroll = false');
}, false);




function scrollToPlace() {
	let wh = window.innerHeight;
	let speed = 0;
	if (_autoscroll) {
		if (_MYmouseY > wh * 0.80) {
			speed = (_MYmouseY - wh * 0.80);
			window.scrollBy(0, speed / 10);
		} else if (_MYmouseY < wh * 0.20) {
			speed = (_MYmouseY - wh * 0.20);
			window.scrollBy(0, speed / 10);
		}
	}

	requestAnimationFrame(scrollToPlace);
}
scrollToPlace();