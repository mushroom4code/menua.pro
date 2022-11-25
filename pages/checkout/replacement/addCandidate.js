function addCandidate() {
	_toAppend.push(_candidate);
	_candidate = {};
	renderCandidate();
	renderToAppend();
	renderToRemove();
}