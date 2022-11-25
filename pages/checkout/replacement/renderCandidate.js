function renderCandidate() {
	let candidateWrapper = qs('#candidateWrapper');
	clearElement(candidateWrapper);

	if (!_candidate.idservices) {
		return false;
	} else {
		console.log('candidate', _candidate);
	}

	candidateWrapper.appendChild(el('div', {className: '', innerHTML: `${_candidate.servicesName}`}));


	let candidateSumm = el('div');

	let candidateQty = el('input');
	candidateQty.style.width = '80px';
	candidateQty.type = 'text';
	candidateQty.value = _candidate.f_salesContentQty;
	candidateQty.addEventListener('input', function () {
		digon();
		_candidate.f_salesContentQty = candidateQty.value;
		candidateSumm.innerHTML = `${nf(_candidate.f_salesContentPrice * _candidate.f_salesContentQty)}`;
	});
	candidateWrapper.appendChild(candidateQty);

	let candidatePrice = el('input');
	candidatePrice.type = 'text';
	candidatePrice.style.width = '80px';
	candidatePrice.value = _candidate.f_salesContentPrice;
	candidatePrice.placeholder = _candidate.f_salesContentPriceMin;

	candidatePrice.addEventListener('input', function () {
		digon();
		if (candidatePrice.value == '') {
//			candidatePrice.value = 0;
		}
		document.querySelector(`#addCandidate`).disabled = parseInt(candidatePrice.value||0) < parseInt(_candidate.f_salesContentPriceMin);
		_candidate.f_salesContentPrice = candidatePrice.value;
		candidateSumm.innerHTML = `${nf(_candidate.f_salesContentPrice * _candidate.f_salesContentQty)}`;
	});
	candidateWrapper.appendChild(candidatePrice);
	candidateSumm.innerHTML = `${nf(_candidate.f_salesContentPrice * _candidate.f_salesContentQty)}`;
	candidateWrapper.appendChild(candidateSumm);//el('div', {className: '', innerHTML: `${_candidate.price * _candidate.qty}`}));

	renderTotal();
}