function showTooltip(inputdata) {
	let box = el('div', {className: 'modal neutral'});
	box.style.whiteSpace = 'nowrap';
	box.style.fontSize = '0.7em';
	box.appendChild(el('h2', {innerHTML: `Подробно`}));
	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);
	var wrapper = el('div', {className: 'lightGrid'});
	wrapper.style.display = 'grid';
	if ((inputdata || {}).column === 'marketing') {
		wrapper.style.gridTemplateColumns = 'repeat(2, auto)';
		wrapper.innerHTML = `<div class = "C B" style = "display: contents;">
				 <div>Клиент</div>
					<div>Процедура</div>
						</div>`;
		//							console.log('marketing', tooltipData.marketing[inputdata.date]);
		if (tooltipData.marketing[inputdata.date].length > 0) {
			let Info = tooltipData.marketing[inputdata.date];
			for (let clientid in Info) {
				console.log('Info[clientid]', Info[clientid]);
				wrapper.innerHTML += `<div style = "display: contents;" ><div class="L"><a href="/pages/offlinecall/schedule.php?client=${Info[clientid].idclients}" target="_blank">${Info[clientid].clientsLName} ${Info[clientid].clientsFName} ${Info[clientid].clientsMName}</a><div class="C">${Info[clientid].scoreMarket == 1 ? 'Зачтён' : 'Не зачтён'}</div></div>
						<div class="L">${(function () {
					let poo = '<div class="lightGrid" style = "display:   grid; grid-template-columns: repeat(6, auto);">' +
							'<div style="display: contents;" class="C B"><div>Дата внесения</div><div>Процедура</div><div>Статус</div><div>Добавлена</div><div>Удалена</div><div>Причина</div></div>';
					if (Info[clientid].servicesApplied.length > 0) {
						for (let serviceApplied of Info[clientid].servicesApplied) {
//											console.log(serviceApplied);
							poo += `<div style="display: contents;"><div>${serviceApplied.servicesAppliedAt}</div><div>${serviceApplied.servicesName}</div><div>${serviceApplied.daleteReasonsName ? `<i class="fas fa-times-circle" style="color: red;"></i> Удалена ${serviceApplied.servicesAppliedDeleted}` : (serviceApplied.servicesAppliedFineshed ? '<i class="fas fa-check-square" style="color: green;"></i> Завершена' : '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> Не завершена')}</div><div>${serviceApplied.SABYusersLastName}</div><div>${serviceApplied.SADelusersLastName || ''}</div><div>${serviceApplied.daleteReasonsName || ''}</div></div>`;
						}
					}
					return poo;
				})()}`;
				wrapper.innerHTML += `</div></div>`;
			}


		}
	}

//////////////////////////////////////////////////////////SERVICE




	if ((inputdata || {}).column == 'service') {
		console.log('service');
		wrapper.style.gridTemplateColumns = 'repeat(2, auto)';
		wrapper.innerHTML = `<div class="C B" style="display: contents;">
			<div>Клиент</div>
			<div>Процедура</div>
			</div>`;
		//							console.log('marketing', tooltipData.marketing[inputdata.date]);
		console.log('tooltipData.service[inputdata.date].length > 0', tooltipData.service[inputdata.date].length > 0);
		if (1) {
//					let Info = ;
//'diagnostics' => $serviceClientsWithDiagnostics,
//'servicesApplied' => $serviceClientsWithoutDiagnostics

			wrapper.innerHTML += `<div style="display: contents;"><div style="font-size: 2em; grid-column: span 2;">Все операции</div></div>`;
			for (let client of tooltipData.service[inputdata.date].services) {
				wrapper.innerHTML += `<div style="display: contents;">
			<div class="L"><a href="/pages/offlinecall/schedule.php?client=${client.info.idclients}" target="_blank">${client.info.clientsLName} ${client.info.clientsFName} ${client.info.clientsMName}</a><div class="C">${client.info.scoreMarket == 1 ? 'Зачтён' : 'Не зачтён'}<br>${((client.services || [])[0] || {}).clientsVisitsTime || 'нет визита'}</div>
			<a href="/pages/reception/?client=${client.info.idclients}&date=${client.info.date}" target="_blank">Регистратура</a></div>
			<div class="L">${(function () {
					let poo = '<div class="lightGrid" style="display: grid; grid-template-columns: repeat(5, auto);">' +
							'<div style="display: contents;" class="C B"><div>Дата внесения</div><div>Добавлена / Процедура</div><div>Статус</div><div>Удалена</div><div>Причина</div></div>';
					if (client.services.length > 0) {
						for (let serviceApplied of client.services) {
//											console.log(serviceApplied);
							poo += `<div style="display: contents; color: ${serviceApplied.daleteReasonsName ? 'gray' : 'black'};"><div>${serviceApplied.servicesAppliedAt}</div><div>${serviceApplied.servicesName} / ${serviceApplied.SABYusersLastName}</div><div>${serviceApplied.daleteReasonsName ? `<i class="fas fa-times-circle" style="color: red;"></i> Удалена ${serviceApplied.servicesAppliedDeleted}` : (serviceApplied.servicesAppliedFineshed ? '<i class="fas fa-check-square" style="color: green;"></i> Завершена' : '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> Не завершена')}</div><div>${serviceApplied.SADelusersLastName || ''}</div><div>${serviceApplied.daleteReasonsName || ''}</div></div>`;
						}
					}
					return poo;
				})()}`;
				wrapper.innerHTML += `</div></div>`;
			}


			wrapper.innerHTML += `<div style="display: contents;"><div style="font-size: 2em; grid-column: span 2;">Диагностики</div></div>`;
			for (let client of tooltipData.service[inputdata.date].diagnostics) {
				wrapper.innerHTML += `<div style="display: contents;">
			<div class="L"><a href="/pages/offlinecall/schedule.php?client=${client.info.idclients}" target="_blank">${client.info.clientsLName} ${client.info.clientsFName} ${client.info.clientsMName}</a><div class="C">${client.info.scoreMarket == 1 ? 'Зачтён' : 'Не зачтён'}</div></div>
			<div class="L">${(function () {
					let poo = '<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6, auto);">' +
							'<div style="display: contents;" class="C B"><div>Дата внесения</div><div>Процедура</div><div>Статус</div><div>Добавлена</div><div>Удалена</div><div>Причина</div></div>';
					if (client.diagnostics.length > 0) {
						for (let serviceApplied of client.diagnostics) {
//											console.log(serviceApplied);
							poo += `<div style="display: contents; color: ${serviceApplied.daleteReasonsName ? 'gray' : 'black'};"><div>${serviceApplied.servicesAppliedAt}</div><div>${serviceApplied.servicesName}</div><div>${serviceApplied.daleteReasonsName ? `<i class="fas fa-times-circle" style="color: red;"></i> Удалена ${serviceApplied.servicesAppliedDeleted}` : (serviceApplied.servicesAppliedFineshed ? '<i class="fas fa-check-square" style="color: green;"></i> Завершена' : '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> Не завершена')}</div><div>${serviceApplied.SABYusersLastName}</div><div>${serviceApplied.SADelusersLastName || ''}</div><div>${serviceApplied.daleteReasonsName || ''}</div></div>`;
						}
					}
					return poo;
				})()}`;
				wrapper.innerHTML += `</div></div>`;
			}
			wrapper.innerHTML += `<div style="display: contents;"><div style="font-size: 2em; grid-column: span 2;">Процедуры</div></div>`;
			for (let client of tooltipData.service[inputdata.date].servicesApplied) {
				wrapper.innerHTML += `<div style="display: contents;">
			<div class="L"><a href="/pages/offlinecall/schedule.php?client=${client.idclients}" target="_blank">${client.info.clientsLName} ${client.info.clientsFName} ${client.info.clientsMName}</a><br>
${((client.services || [])[0] || {}).clientsVisitsTime || 'нет визита'}
			<!-- <div class="C">${client.info.scoreMarket == 1 ? 'Зачтён' : 'Не зачтён'}</div> -->
			</div>
			<div class="L">${(function () {
					let poo = '<div class="lightGrid" style="display: grid; grid-template-columns: repeat(6, auto);">' +
							'<div style="display: contents;" class="C B"><div>Дата внесения</div><div>Процедура</div><div>Статус</div><div>Добавлена</div><div>Удалена</div><div>Причина</div></div>';
					if (client.procedures.length > 0) {
						for (let serviceApplied of client.procedures) {
//											console.log(serviceApplied);
							poo += `<div style="display: contents;"><div>${serviceApplied.servicesAppliedAt}</div><div>${serviceApplied.servicesName}</div><div>${serviceApplied.daleteReasonsName ? `<i class="fas fa-times-circle" style="color: red;"></i> Удалена ${serviceApplied.servicesAppliedDeleted}` : (serviceApplied.servicesAppliedFineshed ? '<i class="fas fa-check-square" style="color: green;"></i> Завершена' : '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> Не завершена')}</div><div>${serviceApplied.SABYusersLastName}</div><div>${serviceApplied.SADelusersLastName || ''}</div><div>${serviceApplied.daleteReasonsName || ''}</div></div>`;
						}
					}
					return poo;
				})()}`;
				wrapper.innerHTML += `</div></div>`;
			}
		}
	}




	boxBody.appendChild(wrapper);
	document.body.appendChild(box);
	let cancelBtn = el('button', {innerHTML: `Закрыть`});
	cancelBtn.style.margin = '0px 10px';
	box.appendChild(cancelBtn);
	cancelBtn.addEventListener('click', function () {
		box.parentNode.removeChild(box);
	});
}