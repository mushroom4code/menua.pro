var app = new Vue({
    el: '#app',
    data: {
        services: services,
        poolArray: [],
        apiendpoint: 'IO.php',
        appointments: [],
        schedule: [],
        date: date,
        selectedService: null,
        selectedServicesApplied: [],
        selectedPrice: null,
        selectedPersonnel: null,
        selectedTimestamp: null,
        URLdatabase: null,
        database: null,
        RCC_phoneDatabase: '',
        databaseLock: (clientLoad || false) ? true : false,
        smstemplates: {},

        loaded: false,
        loading: false,
        callComment: '',
        client: {
            'idclients': (((clientLoad || {}).idclients) || ''),
            'idclientsPhones': (((phoneLoad || {}).idclientsPhones) || ''),
            'clientsPhonesPhone': (((phoneLoad || {}).clientsPhonesPhone) || ''),
            'clientsLName': (((clientLoad || {}).clientsLName) || ''),
            'clientsFName': (((clientLoad || {}).clientsFName) || ''),
            'clientsMName': (((clientLoad || {}).clientsMName) || ''),
            'clientsBDay': (((clientLoad || {}).clientsBDay) || ''),
            'url': null,
            'calls': []
        },
        call: {
            VOIP: null,
            result: null,
            comment: '',
            recallDate: '',
            recallTime: '12:00',
            smsTemplate: null
        },
        data: {
            lastSearchContract: null,
            appointmentDate: null,
            personnel: [],
            client: {
                idclients: null,
                contracts: [],
                servicesApplied: [],

            }
        }
    },
    watch: {
        "call.VOIP": function (newValue) {
            localStorage.setItem(`call.VOIP`, newValue);
        },
        "RCC_phoneDatabase": function (newValue) {
            localStorage.setItem(`RCC_phoneDatabase`, newValue);
        },
        database: function (newValue) {
            console.log('redirect', this.URLdatabase, newValue);
            if (this.URLdatabase && newValue !== this.URLdatabase) {

                window.location.href = `${this.redirectURL}`;
            } else {
                this.appointments = this.appointments.filter(appointment => {
                    return appointment.database === newValue;
                });
                this.scheduleRender();
            }

        }
    },
    computed: {
        poolRender: function () {
            console.log('this.poolArray', this.poolArray);
            let out = [];
            for (let noodle of this.poolArray) {

                let index = this.services.indexOf(this.services.find(service => {
                    return service.id === noodle;
                }));
                console.log('index', index);
                if (index > -1) {
                    out.push(this.services[index]);
                } else {
                    this.deleteNoodle(noodle);
                    console.error('noodle', noodle);
                }

            }
            console.log('out', out);
            return out;
        },
        redirectURL: function () {
            let host = {'1': '', '2': 'vita.'}[this.database];
            //const params = new URLSearchParams();
            return `https://${host}menua.pro/pages/remotecall/call.php?${JSON.stringify({client: this.client})}`;
            ;
        },
        currentDatabase: function () {
            return (((new URL(window.location.href)).hostname.match(/vita/) || []).length ? '2' : '1');
        },
        appRender: {
            get: function () {
                return JSON.stringify({
                    call: this.call,
                    client: this.client,
                    appointments: this.appointments,
                    redirectURL: this.redirectURL,
                    urlsearch: this.urlsearch,
                    currentDatabase: this.currentDatabase,
                    RCC_phoneDatabase: this.RCC_phoneDatabase
                }, null, 2);
            }
            ,
            set: function (newValue) {
                let data = JSON.parse(newValue);
                this.call = data.call;
                this.client = data.client;
                this.appointments = data.appointments;
            }


        }
    },
    methods: {
        servicesAppliedTotal: function (servicesApplied) {
//			console.log("servicesApplied", JSON.parse(JSON.stringify(servicesApplied)));


            let reservedArrSum = servicesApplied.reduce(function (a, b) {
                return a + b.reservedArr.reduce(function (c, d) {
                    return c + d['servicesAppliedQty'] * d['servicesAppliedPrice'];
                }, 0);
                //b['reservedArr']['servicesAppliedQty'] * b['servicesAppliedPrice'];
            }, 0);

            let doneArrSum = servicesApplied.reduce(function (a, b) {
                return a + b.doneArr.reduce(function (c, d) {
                    return c + d['servicesAppliedQty'] * d['servicesAppliedPrice'];
                }, 0);
                //b['reservedArr']['servicesAppliedQty'] * b['servicesAppliedPrice'];
            }, 0);

            return reservedArrSum + doneArrSum;
        },
        getContracts: async function () {
            if (this.client.idclients) {
                let result = await getFetch('/pages/offlinecall/IO.php', {action: 'getContracts', client: this.client.idclients});
                this.data.client.contracts = (result.contracts || []);
            }

        },
        duration: function (servicesDuration) {
            if (servicesDuration < 60) {
                return ` (${servicesDuration}м.)`;
            } else {
                return ` (${servicesDuration / 60}ч.)`;
            }

        },
        saveVisit: function () {
            let client = this.client;
            let call = this.call;
            let appointments = this.appointments;
            if (document.querySelector('#VOIP') && !document.querySelector('#VOIP').value) {
                MSG('Укажите номер IP-телефона с которого вы звоните');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }

            if (client.clientsPhonesPhone === '' || client.clientsPhonesPhone.length < 11) {
                MSG('Укажите номер телефона');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }
            if (!call.result) {
                MSG('Укажите результат звонка');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }


            if (call.result === '5' && !call.smsTemplate) {
                MSG('Укажите шаблон СМС');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }
            if (call.result === '4' && !(call.recallDate && call.recallTime)) {
                MSG('Укажите дату звонка');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }

            if (!client.clientsLName && !client.clientsFName) {
                MSG('Укажите фамилию и/или имя клиента');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }
            if (!client.idclients && !client.clientsSource) {
                MSG('Укажите источник клиента');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }

            if (call.result === '5' && !appointments.length) {
                MSG('Укажите процедуру и время');
                document.querySelector('#saveCallBtn').disabled = false;
                return false;
            }


            this.loaded = false;
            this.loading = true;
            fetch(this.apiendpoint, {
                body: JSON.stringify({
                    action: 'saveCall',
                    database: this.database,
                    call: this.call,
                    client: this.client,
                    appointments: this.appointments,
                    RCC_phoneDatabase: this.RCC_phoneDatabase
                }),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
                        MSG({type: 'success', text: 'Успешно записано', autoDismiss: 1500});
                        document.querySelector('#saveCallBtn').disabled = false;
                        app.resetClientData();
                        app.client.clientsPhonesPhone = '';
                        app.appointments = [];
                        app.call.result = null;
                        app.call.comment = '';
                        app.call.recallDate = null;
                        app.call.recallTime = '12:00';
                        app.loaded = true;
                        app.loading = false;
                        if (clientLoad || false) {
                            GR({client: null});
                        }
                    }
                } catch (e) {
                    console.log('no');
                    app.schedule = [];
                    console.log(e);
                }
            });
        },
        checkInput: function (event) {
            //		
            //																console.log(event);
            console.log('checkInput');
            if (app.client.clientsPhonesPhone.length === 11) {
                app.client.clientsPhonesPhone = '8' + app.client.clientsPhonesPhone.slice(1);
            }
            if (event.keyCode === 13) {
                this.getPhoneInfo();
            }
        },
        resetClientData: function () {
            this.client.idclients = null;
            this.client.idclientsPhones = null;
            this.client.clientsLName = '';
            this.client.clientsFName = '';
            this.client.clientsMName = '';
            this.client.clientsBDay = '';
            document.querySelectorAll('.disableable').forEach(elem => {
                elem.readOnly = false;
            });
            //									console.log(this.appointments[index].expand);
        },
        resetClientId: function (index) {
            event.stopPropagation();
            this.client.idclients = null;
            document.querySelectorAll('.disableable').forEach(elem => {
                elem.readOnly = false;
            });
            //									console.log(this.appointments[index].expand);
        },
        expand: function (index) {
            event.stopPropagation();
            this.appointments[index].expand = !this.appointments[index].expand;
            //									console.log(this.appointments[index].expand);
        },
        deleteNoodle: function (id) {
//			event.stopPropagation();
            console.log('deleteNoodle', id);
            let index = this.poolArray.indexOf(id);
            this.poolArray.splice(index, 1);
            console.log(this.poolArray);
            window.localStorage.setItem('poolArray', JSON.stringify(app.poolArray));
        },
        addAppointment: function () {
            if (!(this.selectedService && this.selectedTimestamp)) {
                return false;
            }
            event.stopPropagation();
            this.appointments.push(
                    {
                        time: this.selectedTimestamp,
                        price: this.selectedPrice,
                        database: this.database,
                        comment: '',
                        service: this.selectedService,
                        personnel: this.selectedPersonnel,
                        expand: false
                    }

            );
            this.selectedService = null;
            this.selectedPrice = null;
            this.selectedPersonnel = null;
            this.selectedTimestamp = null;
            this.schedule = [];
            this.selectedServicesApplied = [];
        },
        deleteAppointment: function (n) {
            event.stopPropagation(); //
            this.appointments.splice(n, 1);
        },
        time: function (timestamp, long = false) {
            let date = new Date(timestamp * 1000);
            let H = date.getHours() > 9 ? date.getHours() : '0' + date.getHours();
            let i = date.getMinutes() > 9 ? date.getMinutes() : '0' + date.getMinutes();
            return H + ':' + i;
        },
        mydate: function (timestamp, long = false) {
            //									console.log(timestamp);
            let date = new Date(timestamp * 1000);
            var year = date.getFullYear();
            var month = ("0" + (date.getMonth() + 1)).substr(-2);
            var day = ("0" + date.getDate()).substr(-2);
            if (long) {
                return day + "." + month + "." + year;
            } else {
                return day + "." + month;
        }

        },
        scheduleRender: function () {
            console.log('scheduleRender');
            if (!(this.selectedService || {}).id) {
                return false;
            }
            this.loaded = false;
            this.loading = true;
            fetch('IO.php', {
                body: JSON.stringify({action: 'getAvailableTime', database: this.database, date: this.date, service: this.selectedService.id}),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    app.schedule = jsn.sort((a, b) => {
                        console.log('a.usersDate != b.usersDate', a.usersDate != b.usersDate);
                        if (a.usersDate != b.usersDate) {
                            if (a.usersDate > b.usersDate) {
                                return 1;
                            }
                            if (a.usersDate < b.usersDate) {
                                return -1;
                            }
                        }
                        if (a.idusers != b.idusers) {
                            if (a.idusers > b.idusers) {
                                return 1;
                            }
                            if (a.idusers < b.idusers) {
                                return -1;
                            }
                        }
                        return 0;
                    });
                    app.loaded = true;
                    app.loading = false;
                } catch (e) {
                    console.log('no');
                    app.schedule = [];
                    console.log(e);
                }
            });
        },

        getAvailableTimeUNUSED: async function (params) {
            console.log('getAvailableTime params', JSON.parse(JSON.stringify(params)));
            this.data.lastSearchContract = params.info.idf_sales;
            this.data.lastSearchQty = params.remains;
            this.data.f_salesContentPrice = params.info.f_salesContentPrice;
            if (this.date && params.info.idservices) {
                this.loading = true;
                this.data.personnel = [];
                let result = await getFetch('/pages/remotecall/IO.php',
                        {
                            action: 'getAvailableTime',
                            date: this.date,
                            service: params.info.idservices,
                            idf_subscriptions: params.info.idf_subscriptions
                        }
                );
                this.loading = false;

                if (result) {
                    app.schedule = result.sort((a, b) => {
                        console.log('a.usersDate != b.usersDate', a.usersDate != b.usersDate);
                        if (a.usersDate != b.usersDate) {
                            if (a.usersDate > b.usersDate) {
                                return 1;
                            }
                            if (a.usersDate > b.usersDate) {
                                return -1;
                            }
                        }
                        if (a.idusers != b.idusers) {
                            if (a.idusers > b.idusers) {
                                return 1;
                            }
                            if (a.idusers > b.idusers) {
                                return -1;
                            }
                        }
                        return 0;
                    });
                    app.loaded = true;
                    app.loading = false;
                    console.log('getAvailableTime result: ', result);
                }
            } else {
                MSG('Укажите дату');
            }

        },

        loadCall: function (call) {
            this.call.idOCC_calls = call;
            console.log('loadCall: function (call)', call);


            fetch('IO2.php', {
                body: JSON.stringify({
                    action: 'loadCall',
                    call: call
                }),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let data = JSON.parse(text);
                    let clients = data.clients || [];
                    if (clients.length === 1) {
                        if (0 && clients[0].clientsOldSince) {
                            MSG({text: 'Это вторичный клиент, у Вас нет прав для работы с ним.'});
                            app.resetClientData();
                            app.client.clientsPhonesPhone = '';
                            app.phoneInfo = '';
                        } else {
                            app.client = clients[0];
                            app.database = app.currentDatabase;
                            app.client.database = app.currentDatabase;
                            app.databaseLock = (clients[0].idclients > 0);
                            document.querySelectorAll('.disableable').forEach(elem => {
                                elem.readOnly = true;
                            });
                        }

                    } else if (clients.length > 1) {
                        let clientsText = '';
                        let clonesText = extractColumn(clients, 'idclients');
                        clients.forEach(client => {
                            clientsText += `<a target="_blank" href="https://menua.pro/pages/offlinecall/schedule.php?client=${client.idclients}">${client.clientsLName} ${client.clientsFName} ${client.clientsMName}</a><br>`;
                        });
                        clientsText += `<br><a target="_blank" href="/sync/utils/clones/?clones=[${clonesText}]">Редактировать совпадения</a>`;
                        MSG({type: 'neutral', text: 'Найдено больше 1го клиента<br>с таким номером телефона:<br><div class="L">' + clientsText + '</div>'});
                        app.resetClientData();
                        app.client.clientsPhonesPhone = '';
                        app.phoneInfo = '';
                    } else {
                        MSG({type: 'neutral', text: 'Клиент не найден. <br> Заполните данные клиента.'});
                    }
                } catch (e) {
                    console.log('no');
                    app.schedule = [];
                    console.log(e);
                }
            });
        },
        getPhoneInfo: function (canigo = false) {
            console.log('getPhoneInfo', this.client.clientsPhonesPhone);
            if (this.client.clientsPhonesPhone.length !== 11 && !canigo) {
                return false;
            }
            document.querySelectorAll('.disableable').forEach(elem => {
                elem.readOnly = false;
            });
            this.resetClientData();
            fetch(this.apiendpoint, {
                body: JSON.stringify({
                    action: 'getPhoneInfo',
                    phone: this.client.clientsPhonesPhone,
                    RCC_phoneDatabase: this.RCC_phoneDatabase
                }),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let data = JSON.parse(text);
                    let clients = data.clients || [];
                    if (clients.length === 1) {
                        console.log(clients);
                        if (clients[0].sales && clients[0].sales.qty > 0) {
                            MSG({text: `У этого клиента есть купленные <b>абонементы</b> (${clients[0].sales.qty }шт).`});
                            app.resetClientData();
                            app.client.clientsPhonesPhone = '';
                            app.phoneInfo = '';
                        }
                        if (clients[0].lastSale3) {
                            MSG({text: `У этого клиента есть купленные <b>процедуры</b>, последняя (${clients[0].lastSale3} назад).`});
                            app.resetClientData();
                            app.client.clientsPhonesPhone = '';
                            app.phoneInfo = '';
                        }

                        if (clients[0].fromLastVisit) {
                            MSG({text: `Клиент был в клинике ${clients[0].fromLastVisit} назад`});
                        }

                        if (0 && clients[0].clientsOldSince) {
                            MSG({text: 'Это вторичный клиент, у Вас нет прав для работы с ним.'});
                            app.resetClientData();
                            app.client.clientsPhonesPhone = '';
                            app.phoneInfo = '';
                        } else {
                            app.client = clients[0];
                            app.database = app.currentDatabase;
                            app.client.database = app.currentDatabase;
                            app.databaseLock = (clients[0].idclients > 0);
                            document.querySelectorAll('.disableable').forEach(elem => {
//								elem.readOnly = true;
                            });
                        }

                    } else if (clients.length > 1) {
                        let clientsText = '';
                        let clonesText = extractColumn(clients, 'idclients');
                        clients.forEach(client => {
                            clientsText += `<a target="_blank" href="https://menua.pro/pages/offlinecall/schedule.php?client=${client.idclients}">${client.clientsLName} ${client.clientsFName} ${client.clientsMName}</a><br>`;
                        });
                        clientsText += `<br><a target="_blank" href="/sync/utils/clones/?clones=[${clonesText}]">Редактировать совпадения</a>`;
                        MSG({type: 'neutral', text: 'Найдено больше 1го клиента<br>с таким номером телефона:<br><div class="L">' + clientsText + '</div>'});
                        app.resetClientData();
                        app.client.clientsPhonesPhone = '';
                        app.phoneInfo = '';
                    } else {
                        MSG({type: 'neutral', text: 'Клиент не найден. <br> Заполните данные клиента.'});
                    }
                } catch (e) {
                    console.log('no');
                    app.schedule = [];
                    console.log(e);
                }
            });
        },
        dialPhone: function () {
            let src = this.call.VOIP;
            let dist = this.client.clientsPhonesPhone;
            let viopserver = 5;
            if (!dist) {
                MSG('Укажите номер телефона');
//				fetch('/sync/api/icq/jse.php', {body: JSON.stringify({
//						errorMessage: 'Звонок вникуда'
//					}), credentials: 'include', method: 'POST', headers: new Headers({'Content-Type': 'application/json'})});
                return false;
            }
            fetch(`/sync/utils/voip/call3.php?src=${src}&dist=${dist}&viopserver=${viopserver}`).then(result => result.text()).then(async function (text) {
                try {
                    let jsn = JSON.parse(text);
                    ///////////////////////////
                    //																					console.error(jsn);
                    if (!(jsn.connected || {}).success) {
                        MSG(rt(
                                'Ошибка соединения,<br>попробуйте ещё раз.',
                                'У меня не получилось,<br>попробуйте ещё раз.',
                                'Тупит связь,<br>попробуйте ещё раз.',
                                'Не соединяется,<br>попробуйте ещё раз.',
                                'Ох... мне тоже надоело,<br>но надо пытаться...<br>Давайте ещё разок.',
                                'Когда-нибудь это починят, <br>а пока попробуйте ещё раз.',
                                ));
                    } else {
                        if (!(jsn.dial || {}).success) {
                            MSG(`Ошибка<br>${(jsn.dial || {}).error}`);
                        } else {
                            MSG({type: 'success', text: rt(
                                        'Звоню',
                                        'Набираю',
                                        'Звонок пошёл',
                                        'Ура, есть контакт!',
                                        'Ало-ало? ',
                                        'Успех!',
                                        ), autoDismiss: 2000});
                        }
                    }

                    /*
                     connected: Object { success: true, time: 0.005635976791381836 }
                     dial: Object { success: true, time: 0.2061021327972412 }
                     */
                    ///////////////////////////


                } catch (e) {
                    MSG("Ошибка ответа сервера. <br><br><i>" + e + "</i>");
                }
            });
            ;
        },
        urlsearch: function () {

        }
    },
    mounted: function () {
        //URL SEARCH
        let url = (new URL(window.location.href)).search.substring(1);
        if (url) {
            console.log('url', url);
            console.log('decode url', decodeURI(url));
            let data = {};
            try {
                data = JSON.parse(decodeURI(url));

                console.log('data', data);
//idRCC_phones=1353811&clientsPhonesPhone=89219222868&clientsLName=Очнева&clientsFName=Кристина&clientsMName=Николаевна&database=1			
                if (data.client || false) {
                    app.databaseLock = true;
                    this.client = data.client;
                    history.pushState("", document.title, window.location.pathname);
                }
                if (data.idOCC_calls || false) {
                    this.loadCall(data.idOCC_calls);
//				history.pushState("", document.title, window.location.pathname);
                }

            } catch (e) {
                console.log('cannot parse URI as JSON');
            }
        }

//database defoult
        this.URLdatabase = (((new URL(window.location.href)).hostname.match(/vita/) || []).length ? '2' : '1');
        this.database = this.URLdatabase;
        if (localStorage.getItem(`call.VOIP`) && localStorage.getItem(`call.VOIP`) !== 'undefined') {
            try {
                this.call.VOIP = localStorage.getItem(`call.VOIP`);
            } catch (e) {
                localStorage.removeItem(`call.VOIP`);
            }
        }

        if (localStorage.getItem(`RCC_phoneDatabase`) && localStorage.getItem(`RCC_phoneDatabase`) !== undefined) {
            try {
                this.RCC_phoneDatabase = localStorage.getItem(`RCC_phoneDatabase`);
            } catch (e) {
                localStorage.removeItem(`RCC_phoneDatabase`);
            }
        }

        this.getContracts();

        this.$nextTick(function () {
            this.poolArray = (JSON.parse(window.localStorage.getItem('poolArray')) || []);
//			this.call.smsTemplate = (JSON.parse(window.localStorage.getItem('smsTemplate')) || '');
        });
    }
});


function getFetch(url, params) {
    return fetch(url, {
        body: JSON.stringify(params),
        credentials: 'include',
        method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
    }).then(result => result.text()).then(function (text) {
        try {
            let jsn = JSON.parse(text);
            return jsn;
        } catch (e) {
            console.error(e);
        }
    });
}