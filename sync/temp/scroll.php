<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(27)) {
	?>E403R27<?
} else {
	?>
	<div style="border: 1px solid red; width: 100px; height: 100px; background-color: white;" draggable="true" ondragstart="void(0);"></div>



<? }
?>
<div style="position: fixed; background-color: white; padding: 20px; top: 0px; right: 0px; border: 3px  solid silver; margin: 20px; color: black;">
	<div id="X"></div>
	<div id="Y"></div>
	<div id="WH"></div>
	<div id="dir"></div>
	<div id="speed"></div>

</div>

<script>
	let _mouseX = null;
	let _mouseY = null;
	document.addEventListener('mousemove', function (evt) {
		evt = (evt || event);
		_mouseX = evt.clientX;
		_mouseY = evt.clientY;
	});
	function scrollToPlace() {
		let wh = window.innerHeight;
		let speed = 0;
		if (_mouseY > wh * 0.75) {
			speed = (_mouseY - wh * 0.75);
			window.scrollBy(0, speed / 10);
		} else if (_mouseY < wh * 0.25) {
			speed = (_mouseY - wh * 0.25);
			window.scrollBy(0, speed / 10);
		}
		requestAnimationFrame(scrollToPlace);
	}
	scrollToPlace();
</script>
<?
for ($n = 0; $n <= 100; $n++) {
	?>

	<?= $n; ?><br>
	<?
}
?>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
