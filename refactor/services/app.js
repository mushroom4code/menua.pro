var servicesApp = new Vue({
  el: '#vueapp',
  data: {
	 renderArray: [],
	 initService: [],
	 activeService: [], // ""
	 activeProcedure: [],
	 pageStatus: 0,
	 tree: [{label: 'test'}],
	 loading: true,
	 loadingImage: false, // for loader
	 flatTree: [],
	 breadcrumbs: [],
	 draggedService: 0,
	 displayDeletedServices: false,
	 serviceAddModal: {},
	 timer: 0,
	 Interval: null,
	 servicesEntryTypes: [],
	 serviceTypes: [],
	 servicesMotivations: [],
	 equipment: [],
	 testsReferrals: []
  },
  watch: {
	 displayDeletedServices(value, oldQuestion) {
		let url = new URL(window.location.href);
		let params = url.searchParams;
		if (params.get("service") == '0') {
			servicesApp.getBy_idservices(null, 0);	
		} else {
		  servicesApp.getBy_idservices(servicesApp.activeService, servicesApp.activeService['idservices']);
		}
		servicesApp.getDirectoriesTree();
	 },
	 loading() {
		if(servicesApp.loading == true) {
			if(document.querySelector('table.highlight')) {
				document.querySelector('table.highlight').classList.add('muted');
			}
		} else {
			if(document.querySelector('table.highlight')) {
				document.querySelector('table.highlight').classList.remove('muted');
			}
		}
	 },
	 activeService: {
		handler() {
			console.log(servicesApp.activeService.servicesURL);
			console.log(servicesApp.initService.servicesURL);
		},
		deep: true
	},
	 timer(value) {
		if (value >= 0.2) {
			servicesApp.loadingImage = true;
		}
	 }
  },
  methods: {
	 changeShortName: function (evt) {
		console.log(evt.target.value);
		if (evt.target.value && servicesApp.activeService['idservices']) {
			fetch('IO.php', {
				body: JSON.stringify({action: "changeShortName", newShortName: evt.target.value, activeService: servicesApp.activeService['idservices']}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(function (text) {
				try {
				let jsn = JSON.parse(text);
				if (jsn['result'] === true) {
					M.toast({html: 'Сохранено'});
				} else {
					// servicesApp.activeService.serviceNameShort = servicesApp.initService.serviceNameShort;
				}

				servicesApp.getBy_idservices(null, servicesApp.activeService['idservices']);
				servicesApp.getDirectoriesTree();
				//    servicesApp.tree = jsn;
				//    servicesApp.flatTree = [];
				console.log(jsn);
				//    servicesApp.flatten(jsn);
				//		  console.log(jsn);
				} catch (e) {
				console.log('no');
				console.log(e);
				}
			});
		}
	 },
	 changeName: function (evt) {
		console.log(evt.target.value);
		if(evt.target.value && servicesApp.activeService['idservices']) {
			fetch('IO.php', {
				body: JSON.stringify({action: "changeName", newName: evt.target.value, activeService: servicesApp.activeService['idservices']}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(function (text) {
				try {
				let jsn = JSON.parse(text);
				servicesApp.getBy_idservices(null, servicesApp.activeService['idservices']);
				servicesApp.getDirectoriesTree();
				//    servicesApp.tree = jsn;
				//    servicesApp.flatTree = [];
				console.log(jsn);
				//    servicesApp.flatten(jsn);
				//		  console.log(jsn);
				} catch (e) {
				console.log('no');
				console.log(e);
				}
			});
		}
	 },
	 changeEntryType: function (evt) {
		console.log(evt.target.value);
		fetch('IO.php', {
			body: JSON.stringify({action: "changeEntryType", newServicesEntryType: evt.target.value, activeService: servicesApp.activeService['idservices']}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		  }).then(result => result.text()).then(function (text) {
			try {
			   let jsn = JSON.parse(text);
			   servicesApp.getBy_idservices(null, servicesApp.activeService['idservices']);
			   servicesApp.getDirectoriesTree();
			//    servicesApp.tree = jsn;
			//    servicesApp.flatTree = [];
			   console.log(jsn);
			//    servicesApp.flatten(jsn);
			   //		  console.log(jsn);
			} catch (e) {
			   console.log('no');
			   console.log(e);
			}
		  });

	 },
	 addService: function(servicesNameAdd, servicesEntryTypeAdd) {
		if(servicesNameAdd && servicesEntryTypeAdd) {
			// servicesApp.serviceAddModal.close();
			fetch('IO.php', {
				body: JSON.stringify({action: "addService", newServicesName: servicesNameAdd, newServicesEntryType: servicesEntryTypeAdd, activeService: servicesApp.activeService['idservices']}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			  }).then(result => result.text()).then(function (text) {
				try {
				   let jsn = JSON.parse(text);
				   servicesApp.serviceAddModal.close();
				   servicesApp.getBy_idservices(null, servicesApp.activeService['idservices']);
				   servicesApp.getDirectoriesTree();
				//    servicesApp.tree = jsn;
				//    servicesApp.flatTree = [];
				   console.log(jsn);
				//    servicesApp.flatten(jsn);
				   //		  console.log(jsn);
				} catch (e) {
				   console.log('no');
				   console.log(e);
				}
			  });
		}
		// console.log(servicesEntryTypeAdd, servicesNameAdd);
	 },
	 getDirectoriesTree() {
		fetch('IO.php', {
		  body: JSON.stringify({action: "getItemsTree", displayDeletedServices: this.displayDeletedServices}),
		  credentials: 'include',
		  method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(function (text) {
		  try {
			 let jsn = JSON.parse(text);
			 servicesApp.tree = jsn['tree'];
			 servicesApp.flatTree = [];
			 console.log(jsn);
			 servicesApp.flatten(jsn['tree']);
			 		//   console.log(servicesApp.flatTree);
		  } catch (e) {
			 console.log('no');
			 console.log(e);
		  }
		});
	 },

	 startDrag(evt, service) {
		evt.dataTransfer.dropEffect = 'move';
		evt.dataTransfer.effectAllowed = 'move';
		this.draggedService = service;
		evt.dataTransfer.setData('drag_idservices', service.idservices);
		evt.dataTransfer.setData('drag_servicesParent', service.servicesParent);
	 },

	 onDrop(evt, li) {
		if (evt.target.parentElement.nodeName == "TR") {
		  evt.target.parentElement.classList.remove("dragover");
		  evt.target.parentElement.classList.remove("dragovern")
		}
		evt.target.classList.remove("dragover");
		evt.target.classList.remove("dragovern");
		if(li.servicesEntryType == '1') {
			const drag_idservices = evt.dataTransfer.getData('drag_idservices');
			const drag_servicesParent = evt.dataTransfer.getData('drag_servicesParent');
			fetch('IO.php', {
			body: JSON.stringify({drag_idservices: drag_idservices, drop_idservices: li.idservices}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(function (text) {
			try {
				let jsn = JSON.parse(text);
				console.log(jsn);
				this.servicesApp.getDirectoriesTree();
				this.servicesApp.getBy_idservices(null, this.servicesApp._data.activeService.idservices);
				//    var cancelDropHtml = '<span>Отменить перенос</span><button class="btn-flat toast-action">Отменить</button>';
				M.toast({html: '<span>Отменить перенос</span><button onclick="servicesApp.cancelDrop()" class="btn-flat toast-action">Отменить</button>', classes: 'dropToast'});
			} catch (e) {
				console.log('no');
				console.log(e);
			}
			});
		}
	 },
	 cancelDrop() {
		// alert(servicesApp.draggedService.idservices);
		fetch('IO.php', {
		  body: JSON.stringify({drag_idservices: servicesApp.draggedService.idservices, drop_idservices: servicesApp.draggedService.servicesParent}),
		  credentials: 'include',
		  method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(r => r.text()).then(function (text) {
		  try {
			 let jsn = JSON.parse(text);
			 //    console.log('sver');
			 //    console.log(jsn);
			 this.servicesApp.getDirectoriesTree();
			 this.servicesApp.getBy_idservices(null, this.servicesApp._data.activeService.idservices);
			 M.Toast.dismissAll();
		  } catch (e) {
			 console.log('no');
			 console.log(e);
		  }
		});
		
	 },
	 onDragEnter(evt) {
		console.log(evt.target.parentElement.nodeName);
		if (evt.target.classList.contains("dropzone") || evt.target.parentElement.classList.contains("dropzone")) {
		  if (evt.target.parentElement.nodeName == "TR") {
			 evt.target.parentElement.classList.add("dragover");
		  } else {
			 evt.target.classList.add("dragover");
		  }
		}
		if (evt.target.classList.contains("dropzonen") || evt.target.parentElement.classList.contains("dropzonen")) {
		  if (evt.target.parentElement.nodeName == "TR") {
			 evt.target.parentElement.classList.add("dragovern");
		  } else {
			 evt.target.classList.add("dragovern");
		  }
		}
	 },
	 onDragLeave(evt) {
		if (evt.target.parentElement.nodeName == "TR") {
		  evt.target.parentElement.classList.remove("dragover");
		  evt.target.parentElement.classList.remove("dragovern")
		}
		evt.target.classList.remove("dragover");
		evt.target.classList.remove("dragovern");
	 },
	 flatten: function (data, level = 0) {
		console.log('flat', level);
		//   this.flatTree = [];
		data.forEach(element => {
		  if (element.descendants) {
			 element.level = level;
			 let {descendants, ...y} = element;
			 this.flatTree.push(y);
			 this.flatten(descendants, level + 1);
		  } else {
			 element.level = level;
			 this.flatTree.push(element);
		  }
		});
	 },
	 getBy_idservices: function (section, id) {

		let reqId;
		if (section) {
		  reqId = section.idservices;
		} else {
		  reqId = id;
		}
		
		fetch('IO.php', {
		  body: JSON.stringify({getBy_idservices: reqId, displayDeletedServices: this.displayDeletedServices}),
		  credentials: 'include',
		  method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(function (text) {
		  try {
			 let jsn = JSON.parse(text);
			 console.log(jsn);
			 this.servicesApp._data.activeService = jsn['service'];
			//  console.log(typeof this.servicesApp._data.activeService);
			 this.servicesApp._data.initService = {...this.servicesApp._data.activeService};
			//  console.log('init short name:  '+this.servicesApp._data.initService.servicesShortName+'    active short name   '+this.servicesApp._data.activeService.servicesShortName);
			 this.servicesApp._data.breadcrumbs = jsn['breadcrumbs'];
			 this.servicesApp._data.renderArray = jsn['services'];
			 this.servicesApp._data.renderArray.sort(function (a, b) {
				return a['servicesName'].localeCompare(b['servicesName']);
			 });
			 this.servicesApp._data.renderArray.forEach(element => {
				if (typeof element['servicesDeleted'] === 'string') {
					element['deletedService'] = 1;
				} else {
					element['deletedService'] = 0;
				}
			 });
			 this.servicesApp._data.serviceTypes = jsn['serviceTypes'];
			 this.servicesApp._data.servicesEntryTypes = jsn['servicesEntryTypes'];
			//  console.log(servicesApp.servicesEntryTypes);
			 this.servicesApp._data.servicesMotivations = jsn['servicesMotivations'];
			 this.servicesApp._data.equipment = jsn['equipment'];
			 this.servicesApp._data.testsReferrals = jsn['testsReferrals'];
			 console.log(this.servicesApp._data.renderArray);
			//  console.log('active service length    '+this.servicesApp._data.activeService.length);
			//  console.log(this.servicesApp._data.activeService.pricesList[3]);
			//  console.log(this.servicesApp._data.activeService.length);
			 if (this.servicesApp._data.activeService['servicesEntryType'] == 1) {
				this.servicesApp._data.pageStatus = 1;
			 } else if (this.servicesApp._data.activeService.length == 0) {
				this.servicesApp._data.pageStatus = 1;
			 } else {
				this.servicesApp._data.pageStatus = 2;
			 }
			 clearInterval(servicesApp.Interval);
			 servicesApp.timer = 0;
			 this.servicesApp._data.loadingImage = false;
			 this.servicesApp._data.loading = false;
		  } catch (e) {
			 console.log('no');
			 console.log(e);
		  }
		});
	 },
	 fetchAllServicesBySearch: function (search) {
		fetch('IO.php', {
		  body: JSON.stringify({allType: true}),
		  credentials: 'include',
		  method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(function (text) {
		  try {
			 let jsn = JSON.parse(text);
			 this.servicesApp._data.loading = false;
			 this.servicesApp._data.activeService = [];
			 this.servicesApp._data.pageStatus = 0;
			 this.servicesApp._data.renderArray = jsn;
		  } catch (e) {
			 console.log('no');
			 console.log(e);
		  }
		});
	 },
	 
	 renderSearchResults: function (services) {
		servicesApp.pageStatus = 1;
		servicesApp.renderArray = services;
		console.log(services);
		//   fetch('/sync/api/local/services/suggestions.php', {
		// 	body: JSON.stringify({search: text}),
		// 	credentials: 'include',
		// 	method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		//   }).then(result => result.text()).then(function (text) {
		// 	try {
		// 	   let jsn = JSON.parse(text);
		// 	   if (jsn.success) {
		// 		  this.servicesApp._data.loading = false;
		// 		  this.servicesApp._data.renderArray = jsn.services;
		// 		  this.servicesApp._data.pageStatus = 3;
		// 	   } else {

		// 	   }
		// 	} catch (e) {
		// 	   console.log('no');
		// 	   console.log(e);
		// 	}
		//   });
	 },
	 setPageStatus: function (status, obj) {
		console.log(status);
		console.log(obj);
		this.$data.pageStatus = status;
		servicesApp.loading = true;
		clearInterval(servicesApp.Interval);
     	servicesApp.Interval = setInterval(function () {
			servicesApp.timer += 0.2;
			// console.log(servicesApp.timer);
		}, 200);
		// this.$data.renderArray = [];
		// this.$data.activeProcedure = [];
		let url = new URL(window.location.href);
		let params = url.searchParams;
		let serviceId = params.get("service");
		if (obj == '0') {
		  this.getBy_idservices(null, 0);
		  return;
		}
		if (this.$data.pageStatus === 0) {
		  this.getBy_idservices(null, 1);
		  return;
		}
		//   console.log(obj);
		if (this.$data.pageStatus === 1) {
		  return obj ? this.getBy_idservices(obj) : this.getBy_idservices(null, serviceId);
		}

		if (this.$data.pageStatus === 2) {
		  return obj ? this.getBy_idservices(obj) : this.getBy_idservices(null, serviceId);
		}
	 },
	 checkPageStatus: function () {
		this.$data.loading = true;
		let url = new URL(window.location.href);
		let params = url.searchParams;
		let typeId = params.get("type");
		let serviceId = params.get("service");
		//   if (typeId && serviceId) {
		// 	this.fetchById(null, serviceId);
		// 	return;
		//   }

		if (serviceId) {
		  console.log(serviceId);
		  if (serviceId == '0') {
			 this.getBy_idservices(null, 0);
			 return;
		  } else {
			 this.getBy_idservices(null, serviceId);
			 return;
		  }
		} else {
		  this.getBy_idservices(null, 1);
		  return;
		  // this.fetchAllType();
		}
	 },
	 priceSave: function priceSave(button) {
		//						button.disabled = true;
		let priceForm = document.querySelector(`#prices`);
		let inputs = priceForm.querySelectorAll('input');
		let dataToSend = {p: {}};
		let length = 0;
		inputs.forEach(input => {
		   if (input.dataset.prevvalue !== input.value && input.dataset.type) {
			  console.log(input.dataset.type, input.value);
			  dataToSend.p[input.dataset.type] = input.value;
			  length++;
		   }
		});
		if (length > 0) {
		   console.log(dataToSend);
		   dataToSend['action'] = 'saveprice';
		   dataToSend['service'] = servicesApp.activeService.idservices;
		   fetch('IO.php', {
			  body: JSON.stringify(dataToSend),
			  credentials: 'include',
			  method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		   }).then(result => result.text()).then(async function (text) {
			  try {
				let jsn = JSON.parse(text);
				if (jsn.success) {
				   inputs.forEach(input => {
					  if (input.dataset.type) {
						input.dataset.prevvalue = input.value;
						input.value = input.value;
						console.log(input.dataset.prevvalue, input.value);
						input.style.backgroundColor = 'white';
					  }
				   });
				}
				if ((jsn.msgs || []).length) {
				   for (let msge of jsn.msgs) {
					  await MSG(msge);
				   }
				}
			  } catch (e) {
				MSG(`Ошибка парсинга ответа сервера. <br><br><i>${e}</i><br>${text}`);
			  }
		   }); //fetch
		}
	  }
  },
  computed: {
	 sortedServices: () => {  // <-- со стрелочной функцией работать не будет
		let sortedServices = [];
		servicesApp.renderArray.sort(function (a,b) {
			if ((a.servicesEntryType == null) && (b.servicesEntryType == null)) {
				return 0;
			} else if (a.servicesEntryType == b.servicesEntryType) {
				return 0;
			} else if ((a.servicesEntryType == 1) && (b.servicesEntryType == null)) {
				return -1;
			} else if ((a.servicesEntryType == 1) && [2,3,4].includes(b.servicesEntryType)) {
				return -1;
			} else if ((a.servicesEntryType == null) && [2,3,4].includes(b.servicesEntryType)) {
				return 1;
			} else if ((a.servicesEntryType == null) && (b.servicesEntryType == 1)) {
				return 1;
			} else if ([2,3,4].includes(a.servicesEntryType) && (b.servicesEntryType == null)) {
				return -1;
			} else if([2,3,4].includes(a.servicesEntryType) && (b.servicesEntryType == 1)) {
				return 1
			}
		});
		sortedServices = servicesApp.renderArray;
		// servicesApp.renderArray.forEach(element => {
		//   if (element.servicesEntryType == '1') {
		// 	 sortedServices.push(element);
		//   }
		// });
		// servicesApp.renderArray.forEach(element => {
		//   if (element.servicesEntryType != '1') {
		// 	 sortedServices.push(element);
		//   }s
		// });
		console.log(sortedServices);
		return sortedServices;
	 },
	 timeago() {     // <-- а с сокращённой записью метода будет
		return moment(this.birthday).fromNow();
	 }
  },
  mounted: function () {
	 this.checkPageStatus();
	 this.getDirectoriesTree();
  }
});