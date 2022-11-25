
async function dragDrop(event, dropData) {
	let data = JSON.parse(event.dataTransfer.getData("data"));
//	console.log('data', data);
	let duration = (data.duration || 60);
	let deleteReason = null;
	let leftRatio = event.target.offsetWidth / (11 * 60); //(width px/length minutes) px/min
	let lefMin = event.layerX / leftRatio; // положение слева в минутах
	lefMin = Math.floor(lefMin / 30) * 30;

	let time = (dropData || {}).trackLimits ? (new Date(new Date(dropData.trackLimits.from).getTime() + lefMin * 60000)) : null;
	data.time = time;
//	console.log('date---------------->>>>', date);
	let	confirmWindowData = {};

	if (dropData.zoneType === "timeline") {
		confirmWindowData = await confirmWindow(data, dropData);

		console.log(confirmWindowData);

		if (!confirmWindowData) {
			return false;
		}
	}


	if (qs('#idusersSA') && !qs('#idusersSA').value) {
		MSG('Укажите оператора.');
		return false;
	}



	if (dropData.zoneType === "contract" && (data.idservicesApplied || null)) {
		if (data.deleteable) {
			deleteReason = await deleteServicesApplied('Пожалуйста', data.idservicesApplied);
		} else {
			deleteReason = null;
			MSG('Вы не можете удалить эту процедуру.');
		}

	}

//	alert(data.divId);
	let pill = qs(`#${data.divId}`);
	pill.style.top = '3px';
	if (pill !== event.target) {
		pill.style.position = 'absolute';

		pill.style.width = Math.floor(duration * leftRatio) + 'px';



//		console.log('dropData', dropData);
		let leftPerc = lefMin / (event.target.offsetWidth / leftRatio);
		pill.style.left = (leftPerc) * 100 + '%';
		event.target.appendChild(pill);
		event.stopPropagation();


		let confirmData = {
			deleteReason: (deleteReason || null),
			idf_subscriptions: (data.idf_subscriptions || null),
			idservicesApplied: (data.idservicesApplied || null),
			idclients: (data.idclients || null),
			idservices: (data.idservices || null),
			time: (confirmWindowData.time || null),
			duration: (confirmWindowData.duration || null),
			comment: (confirmWindowData.comment || null),
			locked: (confirmWindowData.locked || false),
			qty: (confirmWindowData.qty || 1),
			noservice: (confirmWindowData.noservice || false),
			diagnostic: (confirmWindowData.diagnostic || false),
			servicesAppliedPrice: (confirmWindowData.servicesAppliedPrice || 0),
			idusers: ((((dropData || {}).data || {}).info || {}).idusers || null),
			action: "placePill",
			date: (dropData || {}).trackLimits ? (new Date(new Date(dropData.trackLimits.from).getTime() + lefMin * 60000)) : null
		};

		if (qs('#idusersSA')) {
			confirmData.idusersSA = qs('#idusersSA').value;
		}


		let result = await fetch('IO.php', {
			body: JSON.stringify(confirmData),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if ((jsn.msgs || []).length) {
					for (let msge of jsn.msgs) {
						await MSG(msge);
					}
				}
//			console.log('getContracts', jsn);
				return jsn;
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		}); //fetch;
		if ((result || {}).success) {
			init();
		}

		return false;
	}
	_autoscroll = false;
}

function drgThis(event, data) {
//	console.log('data', data);
	event.dataTransfer.effectAllowed = 'copy';
	data.divId = event.target.getAttribute('id');
	event.dataTransfer.setData("data", JSON.stringify(data));
	event.dataTransfer.setDragImage(event.target, 0, 10);
	requestAnimationFrame(function () {
		//											event.target.style.transform = 'translate(-100000px)';
	});
//											console.log(event, data);
	getAvailablePersonnel(data);
	return true;
}


function dragEnter(event) {
	event.preventDefault();
	event.stopPropagation();
	return true;
}

function dragOver(event) {
//	console.log(event);
//	let data = JSON.parse(event.dataTransfer.getData("data"));
//	let pill = qs(`#${data.divId}`);
//	pill.style.transform = '';
	event.preventDefault();
	_autoscroll = false;
	return false;
}
