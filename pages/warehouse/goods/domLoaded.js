window.addEventListener('DOMContentLoaded', function () {
	console.log('DOM Loaded');
	if (window.dir !== undefined) {
		loadGoods(dir);
	}
	loadUnits();
});