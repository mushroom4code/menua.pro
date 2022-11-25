window.addEventListener('scroll', function () {
	//console.log(qsa(`[data-scroll]`));
	qsa(`a[data-scroll="true"]`).forEach(element => {
		element.href = HREFreloc(element.href, 'scroll', Math.round(pageYOffset));
	});
});