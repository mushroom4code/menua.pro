/* global _client, _date */
let schedule = {};
function trackTitle(data) {
    let title = el('div', {className: 'trackTitle', innerHTML: `${data.title}`});
    return title;
}

function time2percent(time, limits = {from:'0001-01-01 10:00:00', to:'0001-01-01 21:00:00'}) {
//	console.log('time2percent');
    let start = (new Date(limits.from)).getTime();
    let end = (new Date(limits.to)).getTime();
    let current = (new Date(time)).getTime();
    if (current >= start && current <= end) {
        let rate = 100 / (end - start);
        let relative = current - start;
        let percent = rate * relative;
        percent = Math.round(percent * 1000) / 1000;
//		console.log('percent', percent);
        return percent;
    } else {
//		console.log('percent is undefined');
        return false;
}
}
function time2percentWidth(time = {from:'0001-01-01 10:00:00', to:'0001-01-01 15:00:00'}, limits = {from:'0001-01-01 10:00:00', to:'0001-01-01 21:00:00'}) {
//	console.log('time2percentWidth', time, limits);
    let start = (new Date(limits.from)).getTime();
    let end = (new Date(limits.to)).getTime();
    let currentFrom = (new Date(time.from)).getTime();
    let currentTo = (new Date(time.to)).getTime();
    if (currentFrom >= start && currentFrom <= end) {
        let rate = 100 / (end - start);
        let widthSec = Math.max((currentTo - currentFrom), 10 * 60 * 1000);
        let percent = Math.round(rate * widthSec * 1000) / 1000;
//		console.log('percent', percent);
        return percent;
    } else {
//		console.log('percent is undefined');
        return false;
}
}


function timeline(data) {
    let pills = (data.pills || []);
//	console.log('pills', pills);
    let availability = (data.data.availability || {});
    let info = (data.info || {});
//	{pills:pills, availability = {}}
//	console.log('timeline(data)', data);
    let trackTime = _trackLimits;
    let timelineDiv = el('div', {className: 'timelineDiv'});
    let trackTimeBegin = (new Date(trackTime.from)).getTime();
    let trackTimeEnd = (new Date(trackTime.to)).getTime();
    let n = 0;
    let timeWidth = 30 * 60 * 1000;
    for (let time = trackTimeBegin; time2percent(time, trackTime) < 100; time += timeWidth) {
        n++;
        let line = el('div');
        line.className = 'line';
        line.innerHTML = `${_0(new Date(time).getHours())}:${_0(new Date(time).getMinutes())}`;
        line.style.left = `${time2percent(time, trackTime)}%`;
        line.style.width = `${time2percentWidth({from: time, to: time + timeWidth}, trackTime)}%`;
        if (
                typeof (availability.from) !== 'undefined'
                && typeof (availability.to) !== 'undefined'
                ) {
            if (time >= new Date(availability.from).getTime() && time < new Date(availability.to).getTime()) {
                line.classList.add('aviable');
            } else {
                line.classList.add('unaviable');
            }
        }
        timelineDiv.appendChild(line);
    }

    let equipmentWrapper = el('div', {className: 'equipmentWrapper'});
    timelineDiv.appendChild(equipmentWrapper);
    let equipment = ((((data || {}).data || {}).equipment || {}).time || []);
    if (equipment.length) {
        for (let equipmentData of equipment) {
            let equipmentDiv = el('div', {className: 'equipUsed'});
//			equipmentDiv.style.width = '100px';
            equipmentDiv.style.left = `${time2percent(equipmentData.from, trackTime)}%`;
//			console.log(`${time2percent(equipmentData.from, trackTime)}%`);
            equipmentDiv.style.width = `${time2percentWidth({from: equipmentData.from, to: equipmentData.to}, trackTime)}%`;
            equipmentWrapper.appendChild(equipmentDiv);
        }
    }







    let pillsWrapper = el('div', {className: 'pillsWrapper'});
    timelineDiv.appendChild(pillsWrapper);
    pillsWrapper.addEventListener('dragenter', function (event) {
        return dragEnter(event);
    });
    pillsWrapper.addEventListener('dragover', function (event) {
        return dragOver(event);
    });
    pillsWrapper.addEventListener('drop', function (event) {
        data.zoneType = 'timeline';
        return dragDrop(event, data);
    });
    if (pills.length) {
        let tracks = [[]];
        pills.sort((a, b) => {
            return (new Date(a.servicesAppliedTimeBegin).getTime()) - (new Date(b.servicesAppliedTimeBegin).getTime());
        });
        for (let pillData of pills) {
            let start = new Date(pillData.servicesAppliedTimeBegin).getTime();
            let T = 0;
            while (typeof (tracks[T]) !== 'undefined' && (tracks[T].filter((element) => {
                return (
                        start >= new Date(element.servicesAppliedTimeBegin).getTime()) &&
                        (
                                start < Math.max(new Date(element.servicesAppliedTimeEnd).getTime(), 10 * 60 * 1000 + new Date(element.servicesAppliedTimeBegin).getTime()));
            })).length) {
                T++;
            }
            pillData.track = T;
            if (typeof (tracks[T]) === 'undefined') {
                tracks[T] = [];
            }
            tracks[T].push(pillData);
        }
        let numTracks = tracks.length;
        timelineDiv.style.height = `${30 + 25 * (Math.max(numTracks, 0))}px`;
        for (let pillData of pills) {
            pillsWrapper.appendChild(pill(pillData));
        }
    }
    return timelineDiv;
}



function pill(data) {
//	console.log('function pill(data)', data);
    let pillDiv = el('div', {className: data.idservicesApplied ? 'timelinePillDiv' : 'inlinePillDiv'});
    if (data.confirmed) {
        pillDiv.classList.add('confirmed');
    }
    data.date = _date;
    pillDiv.id = 'i' + RDS(20);
    if (typeof (data.servicesAppliedTimeBegin) !== 'undefined' && typeof (data.servicesAppliedTimeEnd) !== 'undefined') {
        let commonStyle = `display: inline; padding: 1px; margin: 0px 1px; font-size: 12px; background-color: white; width: 16px; height: 16px; border-radius: 4px;`;
        pillDiv.innerHTML = ``;
        if (data.clientsIsNew) {
            pillDiv.innerHTML += `<i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);${commonStyle}"></i>`;
        }
        if (data.servicesAppliedLocked) {
            pillDiv.innerHTML += `<i class="fas fa-lock" style="color:  hsl(0,100%,78%); ${commonStyle}"></i>`;
        }
        if (data.servicesAppliedCommentText) {
            pillDiv.innerHTML += `<i class="fas fa-info-circle" style="color:  hsl(220,100%,78%); ${commonStyle}"></i>`;
        }
        if (!data.servicesAppliedPrice && !data.servicesAppliedContract && !data.servicesAppliedIsDiagnostic) {
            pillDiv.innerHTML += `<i class="fas fa-gift" style="color:  hsl(15,100%,50%); ${commonStyle}"></i>`;
        }
        if (data.servicesAppliedIsDiagnostic) {
            pillDiv.innerHTML += `<i class="fas fa-stethoscope" title="Диагностика" style="color:  hsl(15,100%,50%); ${commonStyle}"></i>`;
        }
        pillDiv.innerHTML += ` ${data.servicesName || ''} ${data.servicesAppliedQty ? '(' + data.servicesAppliedQty + ')<br>' : ''}
		${data.usersLastName ? ('' + data.usersLastName) : ''} ${data.usersFirstName ? ('' + data.usersFirstName) : ''}<br>
		${_0(new Date(data.servicesAppliedTimeBegin).getHours())}:${_0(new Date(data.servicesAppliedTimeBegin).getMinutes())} - ${_0(new Date(data.servicesAppliedTimeEnd).getHours())}:${_0(new Date(data.servicesAppliedTimeEnd).getMinutes())}<div style="font-size: 0.8em;">Клиент: <a href="/pages/offlinecall/schedule.php?client=${data.idclients}&date=${data.servicesAppliedDate}" target="_blank">${data.clientsLName || ''}  ${data.clientsFName || ''}  ${data.clientsMName || ''}</a><br>
Запись от: ${data.recordSource || 'Не указан'}</div>`;
        pillDiv.innerHTML += data.servicesAppliedCommentText ? `<i style="font-size: 0.8em;">${data.servicesAppliedCommentText || ''}</i>` : ``;
        pillDiv.innerHTML += data.f_salesDate ? `<br><i style="font-size: 0.8em;">абон.${data.f_salesDate}</i>` : `<br><i style="font-size: 0.8em; color: red;">БЕЗАБОН</i> (${data.servicesAppliedPrice ? data.servicesAppliedPrice + 'р.' : 'Подарочная'})`;
        pillDiv.innerHTML += `id ${data.idservicesApplied}`;
    }


    if (data.idclients == _client) {
        pillDiv.draggable = true;
        pillDiv.addEventListener('click', function () {
            this.classList.toggle('higlighted');
        });
        if ((data || {}).noservice || !((data || {}).idservices)) {//услуга не указана
            pillDiv.classList.add('inlinePillDivNoService');
        } else {//услуга указана
            if ((data || {}).idusers) {
                pillDiv.classList.add('clientsPills');
            } else {
                pillDiv.classList.add('clientsPillsNoPersonnel');
            }
        }


//		console.error(data);
        if ((data || {}).valid === false && (data.idservices)) {
            pillDiv.classList.add('invalidPill');
        }

    }


    let left = time2percent(data.servicesAppliedTimeBegin, _trackLimits);
    let width = time2percentWidth({from: data.servicesAppliedTimeBegin, to: data.servicesAppliedTimeEnd}, _trackLimits);
//	console.log('data.track', data.track);
    if (typeof (data.track) !== 'undefined') {
        pillDiv.style.left = `${left}%`;
        pillDiv.style.top = `${3 + data.track * 26}px`;
        pillDiv.style.width = width ? (`${width}%`) : '30px';
    }

    pillDiv.addEventListener('dragstart', function () {
        drgThis(event, data);
    });
    pillDiv.addEventListener('dragend', function () {
    });
    pillDiv.addEventListener('dblclick', async function () {
        if ((data || []).idservicesApplied) {
            let deleteReason = null;
            if (data.deleteable) {
                deleteReason = await deleteServicesApplied('Пожалуйста', data.idservicesApplied);
            } else {
                MSG('Вы не можете удалить эту процедуру.');
            }
            if (deleteReason) {
                let confirmData = {
                    deleteReason: (deleteReason || null),
                    idservicesApplied: (data.idservicesApplied || null),
//				idclients: (data.idclients || null),
//				idservices: (data.idservices || null),
                    action: "placePill",
                    date: _date
                };
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
            }


        }


        console.log(data);
    });
    pillDiv.addEventListener('click', function () {
        if (data.idservicesApplied) {
            pillDiv.classList.toggle('opened');
            if (pillDiv.initialWidth) {
                pillDiv.style.width = pillDiv.initialWidth;
                pillDiv.initialWidth = null;
            } else {
                pillDiv.initialWidth = pillDiv.style.width;
                pillDiv.style.width = 'auto';
            }
        }
    });
    return pillDiv;
}



function track(data, pills) {
//	console.log('function track(data, pills)', data);
    let trackDiv = el('div', {className: 'trackDiv'});
    let trackTitleDiv = trackTitle({title: data.title});
    trackDiv.appendChild(trackTitleDiv);
    let timelineDiv = timeline(
            {
                pills: pills,
                data: data,
                date: _date,
                trackLimits: _trackLimits
            });
    timelineDiv.addEventListener('dragover', function () {
        trackTitleDiv.classList.add('trackTitleHighlited');
    });
    timelineDiv.addEventListener('dragleave', function () {
        trackTitleDiv.classList.remove('trackTitleHighlited');
    });
    trackDiv.appendChild(timelineDiv);
    return trackDiv;
}



async function getContracts(client, params = {}) {
    let data = {};
    let url = new URL(window.location.href);
    data.unsortedFilter = url.searchParams.get('unsortedFilter') || null;
    data.action = 'getContracts';
    data.client = client;
    data.params = params;
//	console.log(data);
    return fetch('IO.php', {
        body: JSON.stringify(data),
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
    }); //fetch

}


function renderUnsorted(unsorted) {

    let unsortedDiv = qs('#unsorted');
    unsortedDiv.innerHTML = ``;
    if (unsorted.length) {
        unsorted.sort((a, b) => {
            if ((new Date(a.servicesAppliedDate)) - (new Date(b.servicesAppliedDate)) != 0) {
                return (new Date(a.servicesAppliedDate)) - (new Date(b.servicesAppliedDate));
            }
            return (new Date(a.servicesAppliedAt)) - (new Date(b.servicesAppliedAt));
        });
        for (let service of unsorted) {
            let medrecords = '';
            if (service.medrecords && service.medrecords.length) {
                console.log(service.medrecords);
                service.medrecords.forEach(medrecord => {
                    medrecords += `<a href="/pages/proclist/printmedrecord.php?record=${medrecord.idmedrecords}" target="_blank"><i class="far fa-file-alt"></i></a>`;
                });
            }


//			console.log('service', service);
            let color = (service.servicesAppliedDeleted) ? 'red' : 'black';
//			console.log(service);
            unsortedDiv.appendChild(el('span', {className: 'C', innerHTML: `<a style="color: ${color};" href="${HREFreloc(null, 'date', service.servicesAppliedDate)}">${service.servicesAppliedDateHR}</a>
<br><span style="color: ${color}; font-size: 0.7em; line-height: 0.7em; margin-top: -0.3em; display: inline-block;" title="${service.servicesAppliedAt}">	${service.operatorLastName || 'Нет данных'}</span>`}));
            unsortedDiv.appendChild(el('span', {className: 'C', innerHTML: `	<span style="color: ${color};">${service.servicesAppliedQty}</span>`}));
            unsortedDiv.appendChild(el('div', {innerHTML: `${medrecords} <span style="color: ${color};">	${service.servicesNameShort || service.servicesName}</span>${(service.servicesAppliedDeleted) ? ('. <br><span style="font-size: 0.6em; line-height: 0.6em;">Удалена ' + (service.servicesAppliedDeleted) + ' ' + (service.daleteReasonsName || '') + ' (' + (service.usersLastNameDelete || '') + ' ' + (service.usersFirstNameDelete || '') + ')</span>') : ''}`}));
            unsortedDiv.appendChild(el('span', {innerHTML: `<span style="color: ${color};">	${service.usersLastName || 'Без специалиста'}</span>`}));
            unsortedDiv.appendChild(el('span', {className: 'C', innerHTML: (!service.servicesAppliedPrice && !service.servicesAppliedContract) ? `	<i style="color: ${color};" class="fas fa-gift" title="Подарочная процедура"></i>` : `	<span style="color: ${color};">${service.servicesAppliedContract || '-'}</span>`}));
        }
    } else {
        console.log('unsorted', unsorted);
    }

    window.requestAnimationFrame(function () {
        qs('#unsortedWrapper').scrollTop = qs('#unsortedWrapperContent').offsetHeight;
    });
}

function renderPersonnel(data) {
    console.log('renderPersonnel(data)', data);
    let schedulePersonnel = qs('#schedulePersonnel');
    schedulePersonnel.innerHTML = '';
    for (let person of data.personnel) {
        let trackData = {
            info: {
                idusers: person.idusers,
                date: _date
            },
            title: `${person.usersLastName} ${person.usersFirstName} ${person.usersMiddleName} ${person.usersScheduleDuty ? '(ДС)' : ''}<br><span style="font-size: 0.8em; color: gray;">${person.positions || '-'}</span>`,
            availability: {from: person.usersScheduleFrom, to: person.usersScheduleTo},
            equipment: data.equipment,
            dateValid: data.dateValid
        };
        schedulePersonnel.appendChild(track(trackData, person.services));
    }
    let scrollto = qs('#scheduleGrid').getBoundingClientRect().bottom + document.documentElement.scrollTop - window.innerHeight + 30;
    let scrollto2 = qs('#scheduleGrid').getBoundingClientRect().top + document.documentElement.scrollTop - 30;
    console.log('scrollto', scrollto);
    console.log('scrollto2', scrollto2);
    window.scrollTo(0, Math.min(scrollto, scrollto2));
}


































async function getAvailablePersonnel(data) {
//										console.log('data', data);
    data.action = 'getAvailablePersonnel';
    fetch('IO.php', {
        body: JSON.stringify(data),
        credentials: 'include',
        method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
    }).then(result => result.text()).then(async function (text) {
        try {
            let jsn = JSON.parse(text);
            renderPersonnel(jsn);
            if ((jsn.msgs || []).length) {
                for (let msge of jsn.msgs) {
                    await MSG(msge);
                }
            }

        } catch (e) {
            MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
        }
    }); //fetch

}





async function loadSchedule(data) {
//	console.log('loadSchedule', data);
    data.action = 'loadSchedule';
    let result = fetch('IO.php', {
        body: JSON.stringify(data),
        credentials: 'include',
        method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
    }).then(result => result.text()).then(async function (text) {
        try {
//			console.log('text', text);
            let jsn = JSON.parse(text);
            if ((jsn.msgs || []).length) {
                for (let msge of jsn.msgs) {
                    await MSG(msge);
                }
            }
            return jsn;
        } catch (e) {
            MSG("Ошибка ответа сервера. <br><br><i>" + e + "</i>");
        }
    }); //fetch
//	console.log('result', result);
    return result;
}