
var app = new Vue({
    el: '#vueapp',
    data: {
        tab: 'client', //sale,client,payments,personnel
        clients: [],
        servicesSearchText: '',
        suggestions: [],
        suggestionsIndex: 0,
        savingSale: false,
        personnelSearch: {
            '1': [],
            '2': [],
            '3': [],
            '4': [],
            '5': []
        },
        personnelSuggestions: {
            '1': [],
            '2': [],
            '3': [],
            '4': [],
            '5': []
        },
        personnelSuggestionsIndex: 0,
        plans: [],
        plan: {},
        data: {
            client: {
                id: null,
                aknum: null,
                phones: [{id: null, number: ''}],
                lname: '', //Ежов
                fname: '', //Владимир
                mname: '',
                gender: null,
                bday: null,
                clientsOldSince: null,
                servicesApplied: [],
                passport: {
                    bplace: null,
                    number: null,
                    date: null,
                    code: null,
                    department: null,
                    registration: null,
                    residence: null
                }

            },
            sale: {
                id: null,
                type: '',
                entity: '1',
                issmall: false,
                date: new Date().toJSON().slice(0, 10),
                subscriptions: [],
            },
            payments: {
                kkts: [],
                indexkkt: '',
                advancePayment: false,
                kkt: null,
                card: {
                    enabled: false,
                    value: 0
                },
                cash: {
                    enabled: false,
                    value: 0
                },
                balance: {
                    enabled: false,
                    value: 0,
                    available: 0
                },
                banks: [{
                        enabled: false,
                        idbank: '',
                        value: 0,
                        agreementNumber: '',
                        creditsMonthes: 24
                    }],
                installment: {
                    enabled: false,
                    value: 0
                }
            },
            personnel: {
                '1': {
                    limit: 1,
                    required: false,
                    editable: true,
                    users: []
                },
                '2': {
                    required: false,
                    editable: true,
                    users: []
                },
                '3': {
                    limit: false,
                    required: false,
                    editable: true,
                    users: []
                },
                '4': {
                    required: false,
                    editable: true,
                    users: []
                },
                '5': {
                    limit: 1,
                    editable: false,
                    required: true,
                    users: []
                }

            }
        }

    },
    watch: {

        "data.payments.indexkkt": function (newValue) {
            if (newValue !== '' && this.data.payments.kkts.length) {
                this.data.payments.kkt = this.data.payments.kkts[newValue];
            } else {
                this.data.payments.kkt = null;
            }

        },
        "data.sale.issmall": function () {
            this.data.personnel['2'].required = ((this.data.sale.type === '1' || this.data.sale.type === '2') && !this.data.sale.issmall);
        },
        "data.sale.type": function () {
            this.data.personnel['2'].required = ((this.data.sale.type === '1' || this.data.sale.type === '2') && !this.data.sale.issmall);
        },
        "data.sale.entity": function (newValue) {
            if (newValue) {
                this.getKKTS(newValue);
            }
            this.data.personnel['3'].limit = (this.data.sale.entity === '2') ? null : 1;
        },
        "data.payments.card.enabled": function (newValue) {
            if (newValue) {
                if (this.saleTotal - this.paymentsTotal > 0) {
                    this.data.payments.card.value = this.saleTotal - this.paymentsTotal;
                }
            } else {
                this.data.payments.card.value = 0;
            }

        },
        "data.payments.cash.enabled": function (newValue) {
            if (newValue) {
                if (this.saleTotal - this.paymentsTotal > 0) {
                    this.data.payments.cash.value = this.saleTotal - this.paymentsTotal;
                }
            } else {
                this.data.payments.cash.value = 0;
            }

        },
        "data.payments.installment.enabled": function (newValue) {
            if (newValue) {
                if (this.saleTotal - this.paymentsTotal > 0) {
                    this.data.payments.installment.value = this.saleTotal - this.paymentsTotal;
                }
            } else {
                this.data.payments.installment.value = 0;
            }
        },
    },
    computed: {
        /*
         saleType: function () {
         if (this.data.client.clientsOldSince && this.data.client.clientsOldSince == this.data.sale.date) {
         this.data.sale.type = '1';
         return '1';
         }
         if (this.data.client.clientsOldSince && this.data.client.clientsOldSince < this.data.sale.date) {
         this.data.sale.type = '2';
         return '2';
         }
         if (!this.data.client.clientsOldSince) {
         MSG('Не зафиксировано ни одного визита в клинику у данного клиента. На него невозможно оформить договор.');
         this.data.sale.type = '';
         return '';
         }
         if (this.data.client.clientsOldSince > this.data.sale.date) {
         MSG('Дата (' + this.data.sale.date + ') договора не может быть раньше, чем дата (' + this.data.client.clientsOldSince + ') первого визита');
         this.data.sale.type = '';
         return '';
         }
         
         },
         */
        servicesAppliedTotal: function () {
            return this.data.client.servicesApplied.reduce(function (a, b) {
                return a + b['qty'] * b['price'];
            }, 0);
        },
        saleTotal: function () {
            return this.data.sale.subscriptions.reduce(function (a, b) {
                return a + b['qty'] * b['price'];
            }, 0);
        },
        planTotal: function () {
            return this.plan.f_subscriptionsDraft.reduce(function (a, b) {
                return a + b['qty'] * b['price'];
            }, 0);
        },
        planTotalRemains: function () {
            return this.plan.f_subscriptionsDraft.reduce(function (a, b) {
                if (b['qty'] - (b['f_subscriptionsDraftSalesQty'] || 0) > 0) {
                    return a + (b['qty'] - (b['f_subscriptionsDraftSalesQty'] || 0)) * b['price'];
                } else {
                    return a;
                }

            }, 0);
        },
        saleOk: function () {
            return (
                    this.data.sale.subscriptions.length > 0 &&
                    this.data.sale.type &&
                    this.data.sale.entity &&
                    !(app.servicesAppliedTotal > 0)
                    );
        }
        ,
        clientOk: function () {
            return (
                    this.data.client.id
                    );
        },
        paymentsTotal: function () {
            return Number(this.data.payments.cash.enabled ? this.data.payments.cash.value : 0) +
                    Number(this.data.payments.card.enabled ? this.data.payments.card.value : 0) +
                    Number(this.data.payments.balance.enabled ? this.data.payments.balance.value : 0) +
                    Number(this.data.payments.installment.enabled ? this.data.payments.installment.value : 0) +
                    this.data.payments.banks.reduce(function (a, b) {
                        return a + (b.enabled ? Number(b.value) : 0);
                    }, 0);
        },
        paymentsOk: function () {
            return Number(this.saleTotal) > 0
                    && Number(this.saleTotal) === this.paymentsTotal
                    && this.cashOk
                    && this.cardOk
                    && this.installmentOk
                    && this.banksOk;
        },
        cashOk: function () {
            return !this.data.payments.cash.enabled || this.data.payments.cash.value > 0;
        },
        cardOk: function () {
            return !this.data.payments.card.enabled || this.data.payments.card.value > 0;
        },
        installmentOk: function () {
            return !this.data.payments.installment.enabled || this.data.payments.installment.value > 0;
        },
        banksOk: function () {
            let ok = true;
            this.data.payments.banks.forEach(bank => {
                if (bank.enabled && (!bank.idbank || !bank.agreementNumber || !bank.value || !bank.creditsMonthes)) {
                    ok = false;
                }
            });
            return ok;
        },
        personnelOk: function () {
            return (
                    (!this.data.personnel['1'].required || this.data.personnel['1'].users.length) &&
                    (!this.data.personnel['2'].required || this.data.personnel['2'].users.length) &&
                    (!this.data.personnel['3'].required || this.data.personnel['3'].users.length) &&
                    (!this.data.personnel['4'].required || this.data.personnel['4'].users.length) &&
                    (!this.data.personnel['5'].required || this.data.personnel['5'].users.length)
                    );
        },
        appRender: {
            get: function () {
                return JSON.stringify(
                        this.data
                        , null, 2);
            },
            set: function (newValue) {
                this.data = JSON.parse(newValue);
            }
        },
        personnelSuggestionsRender: {
            get: function () {
                return JSON.stringify(
                        this.personnelSuggestions
                        , null, 2);
            }
        }
    },
    methods: {
        checkMin: function (subscription) {
            console.log(subscription.price);
            if (parseInt(subscription.price)) {
                subscription.price = Math.max(subscription.priceMin, subscription.price);
            } else {
                subscription.price = 0;
            }

        },
        limitMaxMinValue: function (event, maxValue) {

            console.log(event.target.value, maxValue);


            event.target.value = Math.max(1, Math.min(event.target.value, maxValue));
            console.log('result', event.target.value);

            this.forceUpdate();
        },
        Tab: function (tab) {
            this.tab = tab;
        },
        addBank: function () {
            this.data.payments.banks.push({
                enabled: false,
                idbank: '',
                value: 0,
                creditsMonthes: 24
            });
        },
        selectAclient: function (n) {
//			console.log(n, JSON.parse(JSON.stringify(this.clients[n])));
            let client = this.clients[n];
            this.data.client.id = client.idclients;
            this.data.client.aknum = client.clientsAKNum;
            this.data.client.lname = client.clientsLName;
            this.data.client.fname = client.clientsFName;
            this.data.client.mname = client.clientsMName;
            this.data.client.clientsOldSince = client.clientsOldSince;
            this.data.payments.balance.available = client.balance;
            this.data.client.gender = client.clientsGender;
            this.data.client.bday = client.clientsBDay;
            this.data.client.passport.bplace = (client.passport.clientsPassportsBirthPlace || '');
            this.data.client.passport.number = (client.passport.clientsPassportNumber || '');
            this.data.client.passport.date = (client.passport.clientsPassportsDate || '');
            this.data.client.passport.code = (client.passport.clientsPassportsCode || '');
            this.data.client.passport.department = (client.passport.clientsPassportsDepartment || '');
            this.data.client.passport.registration = (client.passport.clientsPassportsRegistration || '');
            this.data.client.passport.residence = (client.passport.clientsPassportsResidence || '');
            this.data.client.servicesApplied = client.servicesApplied;
            this.data.client.phones = [];
            client.phones.forEach(phone => {
                this.data.client.phones.push({id: phone.idclientsPhones, number: phone.clientsPhonesPhone});
            });
            client.f_salesDraft.forEach(f_saleDraft => {
                this.plans.push(f_saleDraft);
            });
            this.clients = [];
        }
        ,
        planView: function (n) {
//			console.log(JSON.parse(JSON.stringify(this.plans[n])));
            if (this.plans[n].idf_salesDraft === this.plan.idf_salesDraft) {
                this.plan = {};
            } else {
                this.plan = this.plans[n];
            }

        },
        planServiceMove: function (n) {
//			console.log(JSON.parse(JSON.stringify(this.plan)));
            if (!this.data.personnel['2'].users.find(o => o.idusers === this.plan.f_salesDraftAuthor)) {
                this.data.personnel['2'].users.push({idusers: this.plan.f_salesDraftAuthor,
                    usersLastName: this.plan.usersLastName,
                    usersFirstName: this.plan.usersFirstName,
                    usersMiddleName: this.plan.usersMiddleName});
            }

            let service = this.plan.f_subscriptionsDraft[n];
            this.data.sale.subscriptions.push({
                idservices: service.idservices,
                idservicesApplied: (service.idservicesApplied || null),
                idf_salesDraft: this.plan.idf_salesDraft,
                idf_subscriptionsDraft: (service.idf_subscriptionsDraft || null),
                servicesName: service.servicesName,
                price: service.price,
                servicesVat: service.servicesVat,
                qty: service.qty - (service.f_subscriptionsDraftSalesQty || 0),
                maxQty: service.qty - (service.f_subscriptionsDraftSalesQty || 0),
                validBefore: (service.validBefore || null),
                comment: (service.comment || null)

            });
            this.data.client.servicesApplied.splice(n, 1);
        },
        planMove: function () {
            let services = this.plan.f_subscriptionsDraft;

            for (let index in services) {
                this.planServiceMove(index);
            }

            this.plan = {};
        },
        serviceAppliedMoveAll: function () {
            let services = this.data.client.servicesApplied;
            services.forEach((service, n) => {
                this.data.sale.subscriptions.push({
                    idservices: service.idservices,
                    idservicesApplied: (service.idservicesApplied || null),
                    servicesName: service.servicesName,
                    price: service.price,
                    servicesVat: service.servicesVat,
                    qty: service.qty,
                    validBefore: (service.validBefore || null),
                    comment: (service.comment || null)
                });
            });
            this.data.client.servicesApplied = [];
        },
        serviceAppliedMove: function (n) {
            let service = this.data.client.servicesApplied[n];
            this.data.sale.subscriptions.push({
                idservices: service.idservices,
                idservicesApplied: (service.idservicesApplied || null),
                servicesAppliedFineshed: (service.servicesAppliedFineshed || null),
                servicesName: service.servicesName,
                price: service.price,
                servicesVat: service.servicesVat,
                qty: service.qty,
                validBefore: (service.validBefore || null),
                comment: (service.comment || null)
            });
            this.data.client.servicesApplied.splice(n, 1);
        },
        removeBank: function (n) {
            this.data.payments.banks.splice(n, 1);
        },
        removePersonnel: function (n, id) {

            this.data.personnel[id].users.splice(n, 1);
            if (id === 4 || id === 5) {
                localStorage.setItem(`personnel${id}`, JSON.stringify(this.data.personnel[id].users));
            }

        },
        deleteSubscription: function (n) {
//			event.stopPropagation(); //
            let subscription = this.data.sale.subscriptions[n];
            if (subscription.idservicesApplied) {
                this.data.client.servicesApplied.push({
                    "idservices": subscription.idservices,
                    "idservicesApplied": (subscription.idservicesApplied || null),
                    "servicesName": subscription.servicesName,
                    "price": subscription.price,
                    "servicesVat": subscription.servicesVat,
                    "qty": subscription.qty,
                    "servicesAppliedFineshed": (subscription.servicesAppliedFineshed || null)
                });
            }
            this.data.sale.subscriptions.splice(n, 1);
        }
        ,
        addPhone: function (n) {
            this.data.client.phones.push({id: null, number: ''});
        }
        ,
        deletePhone: function (n) {
            console.log(app.data.client.phones);
            app.data.client.phones.splice(n, 1);
        }
        ,
        searchClient: function (event) {
            let dataToSend;
            if ((event.type === 'keypress' && event.key === 'Enter') || event.type === 'click') {
                console.log(event.target.dataset.searchby);
                switch (event.target.dataset.searchby) {
                    case 'name':
                        dataToSend = {
                            searchby: event.target.dataset.searchby,
                            lname: app.data.client.lname,
                            fname: app.data.client.fname,
                            mname: app.data.client.mname
                        };
                        break;
                    case 'clientsPhone':
                        dataToSend = {
                            searchby: event.target.dataset.searchby,
                            phone: app.data.client.phone.number
                        };
                        break;
                    case 'clientsAKNum':
                        dataToSend = {
                            searchby: event.target.dataset.searchby,
                            aknum: app.data.client.aknum
                        };
                        break;
                }
                this.fetchClient(dataToSend);
            }
        },
        sendKKTS: async function () {

            fetch('/pages/checkout/kkt.php', {
                body: JSON.stringify({action: 'saveToKKT', data: this.data}),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
                        app.data.payments.kkts = jsn.KKTS;
                        console.log(jsn);
                    }
                } catch (e) {
                    MSG(e);
                    console.log('no');
                    console.log(e);
                }
            });
        },
        getKKTS: async function (entity) {
            if (!entity) {
                return [];
            }
            fetch('/pages/checkout/kkt.php', {
                body: JSON.stringify({action: 'getKKTS', entity: entity}),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
                        app.data.payments.kkts = jsn.KKTS;
                        console.log(jsn);
                    }
                } catch (e) {
                    MSG(e);
                    console.log('no');
                    console.log(e);
                }
            });
        },
        saveSale: async function () {
            if (this.savingSale) {
                MSG('Сохраняю уже');
                return false;
            }

            this.savingSale = true;
            fetch('/pages/checkout/IO2.php', {
                body: JSON.stringify(this.data),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
                        MSG({type: 'success', text: 'Успешно сохранено'});
                        window.location.href = `/sync/utils/word/appendix.php?sale=${jsn.sale}`;
                        setTimeout(function () {
                            window.location.href = `/pages/checkout/payments.php?client=${jsn.client}&contract=${jsn.sale}`;
                        }, 1000);
                        console.log(jsn);
                    }
                } catch (e) {
                    MSG(e);
                    console.log('no');
                    console.log(e);
                }
            });
        },
        fetchClient: function (dataToSend) {
            fetch('/pages/checkout/IO2.php', {
                body: JSON.stringify(dataToSend),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
                        if (jsn.clients) {
                            app.clients = jsn.clients;
                            if ((jsn.clients || []).length === 1) {
                                app.selectAclient(0);
                                app.clients = [];
                            }
                        }

//						console.log(jsn);
                    }
                } catch (e) {
                    console.log('no');
                    console.log(e);
                }
            });
        },
        fetchSale: function (dataToSend) {
            fetch('/pages/checkout/IO2.php', {
                body: JSON.stringify(dataToSend),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {

                        if (jsn.sale && jsn.sale.client) {
                            app.data.client = jsn.sale.client;
                        }

                        if (jsn.sale && jsn.sale.sale) {
                            app.data.sale = jsn.sale.sale;
                        }

                        if (jsn.sale && jsn.sale.personnel) {
                            for (let role in jsn.sale.personnel) {
                                if (jsn.sale.personnel.hasOwnProperty(role)) {
                                    app.data.personnel[role].users = jsn.sale.personnel[role].users;
                                }
                            }
                        }

                        if (jsn.sale && jsn.sale.payments) {
                            for (let payment in jsn.sale.payments) {
                                if (jsn.sale.payments.hasOwnProperty(payment)) {
                                    app.data.payments[payment] = jsn.sale.payments[payment];
                                }
                            }
                        }


                        console.log(jsn);
                    }
                } catch (e) {
                    console.log('no');
                    console.log(e);
                }
            });
        },
        searchUsers: function (event, id) {
            console.log(event, id);
            if (event.keyCode === 8) {
                this.personnelSuggestions[id] = [];
            }
            if (event.keyCode === 27) {
                this.resetSearch();
                return false;
            }
            if (event.keyCode === 38) {
                event.stopPropagation();
                event.preventDefault();
                void(0);
                if (this.personnelSuggestionsIndex > 0) {
                    this.personnelSuggestionsIndex--;
                } else {
                    this.personnelSuggestionsIndex = 0;
                }
                return false;
            }
            if (event.keyCode === 40) {
                event.stopPropagation();
                event.preventDefault();
                void(0);
                if (this.personnelSuggestionsIndex < this.personnelSuggestions[id].length - 1) {
                    this.personnelSuggestionsIndex++;
                } else {
                    this.personnelSuggestionsIndex = this.personnelSuggestions[id].length - 1;
                }
                return false;
            }


            if (event.target.value.length < 2) {
                this.personnelSuggestions[id] = [];
                return false;
            }
            if (event.keyCode === 13) {
                event.stopPropagation();
                event.preventDefault();
                this.confirmPersonnelSearch(this.personnelSuggestionsIndex, id);
                return false;
            }

            this.personnelSuggestionsIndex = 0;
            fetch('/sync/api/local/users/suggestions.php', {
                body: JSON.stringify({search: event.target.value}),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
//						app.lastSuccessSearchLength = event.target.value.length;
                        app.personnelSuggestions[id] = jsn.users;
                        console.log(jsn.users);
                    } else {
                        //app.servicesSearchText = app.servicesSearchText.substring(0, app.lastSuccessSearchLength);
                    }
                } catch (e) {
                    console.log('no');
                    console.log(e);
                }
            });
            console.log(event.target.value, event.keyCode);
        },
        confirmPersonnelSearch: function (n, id) {
            if (
                    !this.data.personnel[id].users.find(o => o.idusers === this.personnelSuggestions[id][n].idusers) &&
                    (!this.data.personnel[id].limit || this.data.personnel[id].users.length < this.data.personnel[id].limit)
                    ) {
                this.data.personnel[id].users.push(this.personnelSuggestions[id][n]);
                if (id === 4 || id === 5) {
                    localStorage.setItem(`personnel${id}`, JSON.stringify(this.data.personnel[id].users));
                }
            }
            this.personnelSuggestions[id] = [];
            this.personnelSearch[id] = '';
//			this.resetSearch();
        },
        searchServices: function (event) {
            if (event.keyCode === 8) {
                this.suggestions = [];
            }
            if (event.keyCode === 27) {
                this.resetSearch();
                return false;
            }
            if (event.keyCode === 38) {
                event.stopPropagation();
                event.preventDefault();
                if (this.suggestionsIndex > 0) {
                    this.suggestionsIndex--;
                } else {
                    this.suggestionsIndex = 0;
                }
                return false;
            }
            if (event.keyCode === 40) {
                event.stopPropagation();
                event.preventDefault();
                if (this.suggestionsIndex < this.suggestions.length - 1) {
                    this.suggestionsIndex++;
                } else {
                    this.suggestionsIndex = this.suggestions.length - 1;
                }
                return false;
            }
            if (event.keyCode === 13) {
                event.stopPropagation();
                event.preventDefault();
                this.confirmSearch(this.suggestionsIndex);
                return false;
            }

            if (event.target.value.length < 3) {
                this.suggestions = [];
                return false;
            }

            this.suggestionsIndex = 0;
            fetch('/sync/api/local/services/suggestions.php', {
                body: JSON.stringify({search: event.target.value}), //, newonly: false
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.success) {
                        app.lastSuccessSearchLength = event.target.value.length;
                        app.suggestions = jsn.services;
                    } else {
                        app.servicesSearchText = app.servicesSearchText.substring(0, app.lastSuccessSearchLength);
                    }
                } catch (e) {
                    console.log('no');
                    console.log(e);
                }
            });
            console.log(event.target.value, event.keyCode);
        },
        confirmSearch: function (n) {
            delete(this.suggestions[n].servicesNameHighlighted);
            delete(this.suggestions[n].servicesDuration);
            this.data.sale.subscriptions.push({
                idservices: this.suggestions[n].idservices,
                servicesName: this.suggestions[n].servicesName,
                price: this.suggestions[n].priceMin || this.suggestions[n].priceMax,
                priceMin: this.suggestions[n].priceMin || this.suggestions[n].priceMax,
                servicesVat: this.suggestions[n].servicesVat,
                qty: 1,
                validBefore: null,
                comment: null
            });
            this.resetSearch();
        },
        resetSearch: function () {
            this.servicesSearchText = '';
            this.suggestions = [];
            this.lastSuccessSearchLength = 0;
        }
    },
    mounted: function () {
//localStorage.setItem(`personnel[${id}]`, JSON.stringify(this.data.personnel[id].users));
        if (localStorage.getItem(`personnel4`)) {
            try {
                this.data.personnel['4'].users = JSON.parse(localStorage.getItem(`personnel4`));
            } catch (e) {
                localStorage.removeItem(`personnel4`);
            }
        }
        if (localStorage.getItem(`personnel5`)) {
            try {
                this.data.personnel['5'].users = JSON.parse(localStorage.getItem(`personnel5`));
            } catch (e) {
                localStorage.removeItem(`personnel5`);
            }
        }


        this.$nextTick(function () {
            //			this.poolArray = (JSON.parse(window.localStorage.getItem('poolArray')) || []);
            //			this.call.smsTemplate = (JSON.parse(window.localStorage.getItem('smsTemplate')) || '');
        });
    }
}
);