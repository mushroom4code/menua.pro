
function renderContracts(remains) {
    console.log('remains', remains);
    let remainsDiv = qs('#remains');
//	console.log('remains', remains);
    if (!remainsDiv) {
        return null;
    }
    remainsDiv.innerHTML = ``;
    if (remains.length) {
        for (let contract of remains) {
            if ((contract.subscriptions || []).length) {
                let rems = 0;
                for (let subscription of contract.subscriptions) {
                    rems += subscription.remains + subscription.reserved;
                }
                if (!document.querySelector('#showempty').checked && rems == 0) {
//					console.error('SKIPPED CONTRACT', contract);
                    continue;
                }
            } else {
                if (!document.querySelector('#showempty').checked) {
//					console.error('SKIPPED CONTRACT', contract);
                    continue;
                }
//				console.error('SKIPPED CONTRACT', contract);
            }




            let contractWrapper = el('div', {className: 'contractWrapper'});
            let contractHeader = el('div', {className: 'contractHeader', innerHTML: `<a target="_blank" href="/pages/checkout/replacement/?sale=${contract.idf_sales}">${contract.idf_sales}</a> (№${contract.f_salesNumber}) (${contract.payments + contract.credits} из ${contract.f_salesSumm}) от <a href="${HREFreloc(null, 'date', contract.f_salesDate)}">${contract.f_salesDateHuman}</a>${contract.f_salesCancellationDate ? ', <b style="color: red;">РАСТОРГНУТ ' + contract.f_salesCancellationDate + '</b>' : ''}`});
            contractWrapper.appendChild(contractHeader);
            let infoBtn = el('i', {className: 'fas fa-info-circle'});
            contractHeader.appendChild(infoBtn);
            infoBtn.addEventListener('click', function () {
                let text = 'Участники продажи:<br>';
                if (contract.personnel.length > 0) {
                    contract.personnel.forEach(user => {
                        text += `${user.usersLastName} ${user.usersFirstName} (${user.f_rolesNameShort})<br>`;
                    });
                } else if (contract.personnelOld.length > 0) {
                    contract.personnelOld.forEach(user => {
                        text += `<b>${user.usersLastName} ${user.usersFirstName}</b><br>`;
                    });
                } else {
                    text += `отсутствуют<br>`;
                }

                if (contract.creditsArray.length > 0) {
                    text += `<br>Кредиты:<br>`;
                    contract.creditsArray.forEach(credit => {
                        text += `Банк ${credit.RS_banksShort || 'не указан'} (${credit.f_creditsSumm}) ${credit.f_creditsPayed || 'нет поступлений'} ${credit.f_creditsCanceled ? 'аннулирован' : ''}<br>`;
                    });
                }

                if (contract.paymentsArray.length > 0) {
                    text += `<br>Платежи:<br>`;
                    contract.paymentsArray.forEach(payment => {

                        text += `${{'1': 'Наличными', '2': 'Картой', '3': 'Баланс'}[payment.f_paymentsType]} (${payment.f_paymentsAmount})<br>`;
                    });
                }


                MSG({title: 'Информация о продаже', text: `<div style="text-align: left;">${text}</div>`, type: 'neutral'});
            });
            //<i class="" onclick=""></i>
            if (contract.f_salesSumm > (contract.payments + contract.credits)) {
                contractWrapper.style.backgroundColor = 'hsl(0,100%,90%)';
            } else {
                contractWrapper.style.backgroundColor = 'hsl(120,100%,90%)';
            }

            let alrtBtn = el('i', {className: 'fas fa-exclamation-triangle alrtBtn'});
            if (contract.f_salesAlert) {
                alrtBtn.style.color = 'red';
            }
            alrtBtn.addEventListener('click', function () {
                fetch('IO.php', {
                    body: JSON.stringify({action: 'toggleAlert', contract: contract.idf_sales}),
                    credentials: 'include',
                    method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                }).then(function () {
                    if (alrtBtn.style.color == 'red') {
                        alrtBtn.style.color = 'silver';
                    } else {
                        alrtBtn.style.color = 'red';
                    }
                }
                );
            });


//			contractWrapper.appendChild(alrtBtn);
            contractWrapper.style.border = '1px solid #444';
            contractWrapper.style.boxShadow = '1px 1px 5px hsla(0,0%,0%,0.4)';
            remainsDiv.appendChild(contractWrapper);
            let subscriptionsWrapper = el('div', {className: 'subscriptionsWrapper'});
            contractWrapper.appendChild(subscriptionsWrapper);
            if ((contract.subscriptions || []).length) {
                subscriptionsWrapper.appendChild(el('div', {innerHTML: ``}));
                subscriptionsWrapper.appendChild(el('div', {className: 'C B', innerHTML: `Процедура`}));
                subscriptionsWrapper.appendChild(el('div', {className: 'C B', innerHTML: `куп.`}));
                subscriptionsWrapper.appendChild(el('div', {className: 'C B', innerHTML: `цена.`}));
                subscriptionsWrapper.appendChild(el('div', {className: 'C B', innerHTML: `Ост.`}));
                subscriptionsWrapper.appendChild(el('div', {className: 'C B', innerHTML: `Рез.`}));
                subscriptionsWrapper.appendChild(el('div', {className: 'C B', innerHTML: `Пройд.`}));
                for (let subscription of contract.subscriptions) {
                    let pillWrapepr = el('div', {className: 'pillWrapepr'});
                    subscriptionsWrapper.appendChild(pillWrapepr);
                    if (subscription.remains < 0) {
//						subscriptionsWrapper.style.backgroundColor = 'red';
                    }


                    let expire = ``;
                    let expired = false;
                    if (subscription.info.f_subscriptionsExpDate) {
//						moment(subscription.info.f_subscriptionsExpDate).format('MM/DD/YYYY');
                        let color = 'black';
                        if (new Date(`${subscription.info.f_subscriptionsExpDate}`) < Date.now()) {
                            color = 'red';
                            expired = true;
                        }
                        subscription.info.f_subscriptionsExpDateHR = subscription.info.f_subscriptionsExpDate.replace(/(\d{4})\-(\d{2})\-(\d{2}).*/, '$3.$2.$1');
                        expire = ` до <b style="color: ${color}">${subscription.info.f_subscriptionsExpDateHR}</b>`;
                    }

                    if (
                            subscription.remains > 0 &&
                            !contract.f_salesCancellationDate &&
                            !expired

                            ) {
                        let info = subscription.info;
                        info.date = _date;
                        info.remains = `${subscription.remains}`;
                        let pillDiv = pill(info);
                        pillWrapepr.appendChild(pillDiv);
                        let pillDiv2 = pill({...{noservice: true}, ...info});
                        pillWrapepr.appendChild(pillDiv2);
                    }

                    let rndtxt = RDS();
                    //<input type="checkbox" id="A${rndtxt}">
                    let subscriptionName = el('div', {innerHTML: `<span ${subscription.info.servicesDeleted ? (' style="color: red;" title="Выведена из номенклатуры"') : ''} for="A${rndtxt}" title="${subscription.info.idservices}">${subscription.info.servicesName}  ${expire}</span>${subscription.info.servicesDeleted ? (' [id:' + subscription.info.idservices + ']') : ''}`});
                    if (subscription.info.comments.length) {
                        let infoBtn = el('i', {className: 'far fa-question-circle'});
                        infoBtn.style.cursor = 'pointer';
                        infoBtn.addEventListener('click', function () {
                            showSubscriptionComment(subscription.info.comments);
                        });
                        subscriptionName.appendChild(infoBtn);

                    }

                    subscriptionsWrapper.appendChild(subscriptionName);

                    subscriptionsWrapper.style.hyphens = 'auto';
                    subscriptionsWrapper.appendChild(el('div', {className: 'C', innerHTML: `${subscription.info.f_salesContentQty}`}));
                    subscriptionsWrapper.appendChild(el('div', {className: 'C', innerHTML: `${subscription.info.f_salesContentPrice}`}));
                    subscriptionsWrapper.appendChild(el('div', {className: 'C', innerHTML: `<b ${subscription.remains < 0 ? ' style="background-color: red; padding: 10px; border-radius: 50%;"' : ''}>${subscription.remains}</b>`}));
                    let popUpReserved = '';
                    if ((subscription.reservedArr || []).length > 0) {
                        popUpReserved = `<div class="datesPopUp">`;
                        for (let reserved of subscription.reservedArr) {
                            let alternativesHTML;
                            let options = ``;
                            let variants = contracts.filter(contr => {
                                if (contr.idf_sales == contract.idf_sales || !contr.subscriptions) {
                                    return false;
                                }
                                let filtered = contr.subscriptions.filter(subscript => {
//									console.log(subscript.info.idservices, subscription.info.idservices, subscript.remains, (subscript.info.idservices == subscription.info.idservices && subscript.remains > 0));
                                    return (subscript.info.idservices == subscription.info.idservices && subscript.remains > 0)
                                });
                                if (filtered.length > 0) {
//									console.log('filtered', filtered);
                                    return true;
                                } else {
                                    return false;
                                }

                            });
                            options = `<option></option>`;
                            if (variants.length) {

                                for (let variant of variants) {
                                    options += `<option value="${variant.idf_sales}">${variant.f_salesDateHuman}</option>`;
                                }

                            } else {
                                alternativesHTML = `Только&nbsp;тут`;
                            }
                            options += `<option value="">Открепить</option>`;
                            alternativesHTML = `<select onchange="moveServiceApplied(${reserved.idservicesApplied},this.value);" style="width: auto;">${options}</select>`;
                            popUpReserved += `<div onclick="GR({date:'${reserved.servicesAppliedDate}'});">${reserved.servicesAppliedDate}</div><div>${alternativesHTML}</div>`;
                        }
                        popUpReserved += `</div>`;
                    }

                    let resCounter = el('div', {className: 'C', innerHTML: `<span>${subscription.reserved}</span>${popUpReserved}`});
                    resCounter.style.cursor = 'pointer';
                    resCounter.addEventListener('click', function (event) {
                        if (!event.target.closest(`.datesPopUp`)) {
                            this.classList.toggle('showPopup');
                        }

                    });
                    subscriptionsWrapper.appendChild(resCounter);
                    let popUpDone = '';
                    if ((subscription.doneArr || []).length > 0) {












                        popUpDone = `<div class="datesPopUp">`;
                        for (let done of subscription.doneArr) {




////////////////////////////////////// DONE
                            let alternativesHTML;
                            let options = ``;
                            let variants = contracts.filter(contr => {
                                if (contr.idf_sales == contract.idf_sales || !contr.subscriptions) {
                                    return false;
                                }
                                let filtered = contr.subscriptions.filter(subscript => {
//									console.log(subscript.info.idservices, subscription.info.idservices, subscript.remains, (subscript.info.idservices == subscription.info.idservices && subscript.remains > 0));
                                    return (subscript.info.idservices == subscription.info.idservices && subscript.remains > 0)
                                });
                                if (filtered.length > 0) {
//									console.log('filtered', filtered);
                                    return true;
                                } else {
                                    return false;
                                }

                            });
                            options = `<option></option>`;
                            if (variants.length) {

                                for (let variant of variants) {
                                    options += `<option value="${variant.idf_sales}">${variant.f_salesDateHuman}</option>`;
                                }

                            }
                            options += `<option value="">Открепить</option>`;
                            alternativesHTML = `<select onchange="moveServiceApplied(${done.idservicesApplied},this.value);" style="width: auto;">${options}</select>`;
                            popUpDone += `<div onclick="GR({date:'${done.servicesAppliedDate}'});">${done.servicesAppliedDate}</div><div>${alternativesHTML}</div>`;
                            //////////////////////////////////////




//							popUpDone += `<span onclick="GR({date:'${done.servicesAppliedDate}'});">${done.servicesAppliedDate} ${done.usersLastName} </span>${popUpReserved}`;
                        }
                        popUpDone += `</div>`;
                    }

                    let doneCounter = el('div', {className: 'C', innerHTML: `<span>${subscription.done}</span>${popUpDone}`});
                    doneCounter.style.cursor = 'pointer';
                    doneCounter.addEventListener('click', function () {
                        if (!event.target.closest(`.datesPopUp`)) {
                            this.classList.toggle('showPopup');
                        }
                    });
                    subscriptionsWrapper.appendChild(doneCounter);
                }
            }
        }
    }

    qs('#scrollableContractsWrapper').scrollTop = remainsDiv.offsetHeight;
}

async function moveServiceApplied(idSA, idcontract) {
    let newContracts = await getContracts(_client, {moveSA: idSA, TOcontract: idcontract});
    renderContracts(newContracts.contracts);
}


function showSubscriptionComment(comments) {
    console.log(comments);
    let text = '';
    comments.forEach(comment => {
        text = `${comment.f_subscriptionsCommentsComment}<hr>`;
    });
    MSG({title: 'Информация о процедуре', text: `<div style="text-align: left;">${text}</div>`, type: 'neutral'});
}