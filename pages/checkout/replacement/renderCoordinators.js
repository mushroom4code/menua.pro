function renderCoordinators() {
	let coordinatorsContainer = qs('#coordinators');
	clearElement(coordinatorsContainer);
	_coordinators.forEach((element, index) => {
		coordinatorsContainer.appendChild(el('div', {
			className: 'displayContents',
			innerHTML: `<div style="display: contents;"><div style="padding-right: 30px;">${element.lname || ''} ${element.fname || ''} ${element.mname || ''}</div>
									<div class="smallBtn"><i class="fas fa-times-circle" style="color: red;" class="" onclick="_coordinators.splice(${index},1);renderCoordinators();"></i></div></div>`
		}));
	});

}