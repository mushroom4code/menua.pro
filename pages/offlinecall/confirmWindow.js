
/* global qs, currentLevel, measuringUnits */

async function confirmWindow(pillData, dropZoneData) {
//	console.log('pillData', pillData);
	console.log('dateValid', dropZoneData.data.dateValid);
	let box = el('div', {className: 'modal neutral'});
	box.style.position = 'fixed';
	box.appendChild(el('h2', {innerHTML: `Подтвердить`}));

	let boxBody = el('div', {className: 'box-body'});
	box.appendChild(boxBody);

	var wrapper = el('div');
	wrapper.style.display = 'grid';
	wrapper.style.gridTemplateColumns = 'auto auto';
	wrapper.style.gridGap = '10px';


	boxBody.appendChild(wrapper);


//

	let qtyName = el('div', {className: 'R', innerHTML: `Количество:`});


	let qtyValue = el('select');
	qtyValue.readOnly = !(pillData || {}).servicesAppliedQty;

	for (let n = 1; n <= ((pillData || {}).remains || 1); n++) {
		qtyValue.appendChild(new Option(n, n));
	}
	qtyValue.value = (pillData.servicesAppliedQty || 1);


	let priceName = el('div', {className: 'R', innerHTML: `Цена за 1:`});




	let priceValue = el('input');
	priceValue.type = 'text';
	priceValue.addEventListener('input', digon);
	priceValue.addEventListener('input', async function () {
		if (priceValue.value == 0 || priceValue.value == '') {
//			priceValue.value = 0;
		} else {
			if (priceValue.value < (pillData.priceMin || pillData.priceMax)) {
//				document.querySelector(`#addBtn`).disabled = true;
//				document.querySelector(`#addBtn`).style.backgroundColor = 'pink';
			} else {
//				document.querySelector(`#addBtn`).style.backgroundColor = '';
//				document.querySelector(`#addBtn`).disabled = false;
			}

		}
	});

	priceValue.readOnly = !!(
			(typeof ((pillData || {}).f_salesContentPrice) !== 'undefined'
					&& (pillData || {}).f_salesContentPrice !== null) ||
			(typeof ((pillData || {}).servicesAppliedPrice) !== 'undefined'
					&& (pillData || {}).servicesAppliedPrice !== null)
			);


	priceValue.value = (pillData.servicesAppliedPrice || pillData.f_salesContentPrice || pillData.servicesPrice || '0');


	if (!pillData.noservice) {
		console.error('!pillData.noservice');
		wrapper.appendChild(qtyName);
		wrapper.appendChild(qtyValue);
		wrapper.appendChild(priceName);
		wrapper.appendChild(priceValue);
	}// с процедурой








	let timeName = el('div', {className: 'R', innerHTML: `Время начала:`});
	wrapper.appendChild(timeName);

	let timeValue = el('input');
	timeValue.style.display = 'inline';
	timeValue.style.width = 'auto';
	timeValue.type = 'time';
	timeValue.value = `${("0" + pillData.time.getHours()).slice(-2)}:${("0" + pillData.time.getMinutes()).slice(-2)}`;
	console.log(`${pillData.time.getHours()}:${pillData.time.getMinutes()}`);
	wrapper.appendChild(timeValue);

	let durationName = el('div', {className: 'R', innerHTML: `Продолжительность:`});
	wrapper.appendChild(durationName);

	let durationValue = el('select');
//	durationValue.readonly = true;
	wrapper.appendChild(durationValue);
	let duration = (pillData.servicesDuration || 60);
	if (pillData.servicesAppliedTimeBegin && pillData.servicesAppliedTimeEnd) {
		duration = (new Date(pillData.servicesAppliedTimeEnd).getTime() - new Date(pillData.servicesAppliedTimeBegin).getTime()) / 60000;
	}
	for (let d = duration; d < 240; d += 30) {
		durationValue.appendChild(new Option(`${Math.floor(d / 60)}:${_0(d % 60)}`, d, ));

	}



	let canBeDiagnostic = el('div', {className: 'R', innerHTML: ``});

	let canBeDiagnosticValue = el('input');
	canBeDiagnosticValue.type = 'checkbox';
	canBeDiagnosticValue.id = 'canBeDiagnosticCheckbox';
	canBeDiagnosticValue.autocomplete = 'off';

	if (pillData.servicesAppliedIsDiagnostic) {
		canBeDiagnosticValue.checked = true;
	}

	let canBeDiagnosticWrapper = el('div');
	let canBeDiagnosticLabel = el('label', {innerHTML: `Диагностика`});
	canBeDiagnosticLabel.htmlFor = 'canBeDiagnosticCheckbox';



	if (pillData.canBeDiagnostic) {
		canBeDiagnosticWrapper.appendChild(canBeDiagnosticValue);
		canBeDiagnosticWrapper.appendChild(canBeDiagnosticLabel);
		wrapper.appendChild(canBeDiagnostic);
		wrapper.appendChild(canBeDiagnosticWrapper);
	}






/////////////
	let lockName = el('div', {className: 'R', innerHTML: `Закрепить:`});
	wrapper.appendChild(lockName);

	let lockValueWrapper = el('div');
	wrapper.appendChild(lockValueWrapper);



	let lockValue = el('input', {className: 'lock'});
	lockValue.type = 'checkbox';
	lockValue.id = 'lockCheckbox';
	lockValue.autocomplete = 'off';
	lockValueWrapper.appendChild(lockValue);

	let lockLabel = el('label', {innerHTML: `${dropZoneData.data.title}`});
	lockLabel.htmlFor = 'lockCheckbox';

	lockValueWrapper.appendChild(lockLabel);

	//<input type="checkbox" autocomplete="off" id="mylock"><label for="mylock"></label>


	if (pillData.serviceDescription) {
		var serviceDescriptionName = el('div', {className: 'C', innerHTML: `Описание услуги`});
		serviceDescriptionName.style.display = 'flex';
		serviceDescriptionName.style.alignItems = 'center';
		wrapper.appendChild(serviceDescriptionName);
		var serviceDescriptionValue = el('div', {className: 'serviceDescription', innerHTML: '<p>' + pillData.serviceDescription.replace(/(?:\r\n|\r|\n)/g, '</p><p>') + '</p>'});

		wrapper.appendChild(serviceDescriptionValue);
	}



	let commentName = el('div', {className: 'C', innerHTML: `<b style="color: red;"><span style="font-size: 1.6em;">ВАЖНАЯ</span><br><span style="font-size: 1.1em;">информация</span><br>по процедуре<br>ДЛЯ ВРАЧА!!!!</b>`});
	commentName.style.display = 'flex';
	commentName.style.alignItems = 'center';
	wrapper.appendChild(commentName);


	let commentValue = el('textarea');
	commentValue.style.width = '250px';
	commentValue.style.height = '100px';
	commentValue.style.resize = 'none';
	commentValue.style.padding = '7px';
	commentValue.value = (pillData.servicesAppliedCommentText || '');
	wrapper.appendChild(commentValue);
//	priceValue.type = 'text';
//	priceValue.addEventListener('input', digon);
//	priceValue.readOnly = !!(typeof ((pillData || {}).f_salesContentPrice) !== 'undefined' && (pillData || {}).f_salesContentPrice !== null);





	let promise = new Promise(function (resolve, reject) {
		// create popup close handler, and call  resolve in it

		let cancelBtn = el('button', {innerHTML: `Отмена`});
		cancelBtn.style.margin = '0px 10px';
		box.appendChild(cancelBtn);
		cancelBtn.addEventListener('click', function () {
			box.parentNode.removeChild(box);
			resolve(false);
		});
		let addBtn = el('button', {innerHTML: `Сохранить`});
		addBtn.id = 'addBtn';
		if (dropZoneData.data.dateValid) {
			box.appendChild(addBtn);
			addBtn.style.margin = '0px 10px';
			addBtn.addEventListener('click', function () {
				if (timeValue.value) {
					box.parentNode.removeChild(box);



					if (!pillData.noservice) {// с процедурой
						console.log('с процедурой', typeof (canBeDiagnosticValue));
						resolve(
								{
									qty: qtyValue.value || null,
									duration: (durationValue.value || 0),
									servicesAppliedPrice: priceValue.value,
									comment: commentValue.value.trim(),
									locked: lockValue.checked,
									diagnostic: (typeof (canBeDiagnosticValue) !== 'undefined' && canBeDiagnosticValue.checked) ? true : false,
									time: timeValue.value
								}
						);
					} else {//без процедуры
//					console.error('без процедуры');
						resolve(
								{
									noservice: true,
									duration: (durationValue.value || 0),
									comment: commentValue.value.trim(),
									locked: lockValue.checked,
									time: timeValue.value,
									diagnostic: (typeof (canBeDiagnosticValue) !== 'undefined' && canBeDiagnosticValue.checked) ? true : false
								}
						);
					}


				} else {
					MSG('А что там со временем???');
				}

			});
		}

	}
	);
	document.body.appendChild(box);
	return promise;
}
