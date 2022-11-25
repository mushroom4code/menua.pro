<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(35) || array_search_2d(32, ($_USER['positions'] ?? []), 'id')) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!(R(35) || array_search_2d(32, ($_USER['positions'] ?? []), 'id'))) {
	?>E403P32<?
} else {
	?>
	<style>
		.suggestions {
			position: absolute;
			width: auto;
			background-color: white;
			border: 1px solid silver;
			box-shadow: 0px 0px 10px hsla(0,0%,0%,0.3);
			border-radius: 4px;
			z-index: 10;
			list-style: none;
			white-space: nowrap;
			left: 0px;
			top: 25px;
		}
		.suggestions .red {
			color: red;
		}
		.suggestions span {
			color: gray;
		}
		.suggestions li {
			font-size: 0.8em;
			padding: 2px 10px;
			cursor: pointer;
		}
		.suggestions li .mask{
			position: absolute;
			top: 0px;
			left: 0px;
			width: 100%;
			height: 100%;
			z-index: 10;
		}

		.suggestions li .mask:hover{
			background-color:  hsla(0,0%,0%,0.1);
		}

		.suggestions li .pointed{
			background-color:  hsla(0,0%,0%,0.1);
		}
		.scheduleTable {
			max-width: 500px;
		}
		.pool {
			background-color: #fff;
			border: 1px solid silver;
			border-radius: 5px;
			margin-top: 10px;
			max-width: 500px;

		}.noodle:hover{
			background-color: #e0FFe0;
		}

		.noodle {
			display: inline-block;
			padding: 3px 6px;
			border: 1px solid #eee;
			margin: 5px;
			border-radius: 5px;
			background-color: #eaeaea;
			cursor: pointer;
		}
		.isActive {
			background-color: #d0FFd0 !important;
		}
		.personnel {
			display: inline-block;
			border: 1px solid gray;
			border-radius: 4px;
			padding: 3px 6px;
			background-color: white;
			margin: 4px 4px;
		}
		.personnel.head {
			border-bottom: 1px solid silver;
		}

	</style>
	<?
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<div style="vertical-align: top;">
		<div class="box neutral" style="vertical-align: top;">
			<div class="box-body" style="min-width: 330px;">
				<h2>НОМЕР АППАРАТА <input type="text"  id="callSrc" oninput="digon(); localStorage.setItem('callSrc', this.value);" size="3" style="display: inline-block; text-align: center; width: auto;"></h2>
				<script>
					callSrc.value = localStorage.getItem('callSrc');</script>
				<div style="text-align: center; padding: 20px;"><input id="searchBTN" type="button" value="Найти телефон" onclick="if (!this.disabled) {
							if (!callSrc.value) {
								alert('Укажите номер вашшего аппарата');
								return false;
							}
							getPhone();
						}"></div>

				<div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;">
					<div id="phoneNumber" style="text-align: center; font-size: 30pt; line-height: 40pt; grid-column: 1/-1;">...</div>
					<input type="hidden" id="idRCC_phone">
					<div style="display: contents;">
						<div>Фамилия:</div>
						<input type="text" id="clientLName" autocomplete="off">
					</div>
					<div style="display: contents;">
						<div>Имя:</div>
						<input type="text" id="clientFName" autocomplete="off">
					</div>
					<div style="display: contents;">
						<div>Отчество:</div>
						<input type="text" id="clientMName" autocomplete="off">
					</div>
					<div style="display: contents;">
						<div>Результат звонка:</div>
						<select autocomplete="off" id="callResult" onchange="if (this.value == 4) {
									dateRecall.style.display = 'contents';
								} else {
									dateRecall.style.display = 'none';
								}">
							<option value="">Результат звонка</option>
							<?
							foreach (query2array(mysqlQuery("SELECT * FROM `OCC_callTypes` WHERE NOT `idOCC_callTypes` in  (7,8) ORDER BY `OCC_callTypesName`")) as $callType) {
								?><option value="<?= $callType['idOCC_callTypes']; ?>"><?= $callType['OCC_callTypesName']; ?></option>
							<? } ?>
						</select>
					</div>
					<div style="display: none;" id="dateRecall">
						<div>Дата:</div>
						<input type="date" id="dateRecallValue" autocomplete="off">
					</div>

					<div style="display: contents;">
						<div>Комментарий:</div>
						<textarea style="resize: none; height: 80px;"  id="clientComment"></textarea>
					</div>

					<div style="display: none;">
						<div style="grid-column: span 2;">
							<input type="text" id="pdb" disabled autocomplete="off">
						</div>
					</div>
					<div style="display: none;">
						<div style="grid-column: span 2;">
							<input type="text" id="call" disabled autocomplete="off">
						</div>
					</div>


					<div style="display: contents;">
						<div style="grid-column: 1/-1; text-align: center;"><input type="button" value="Сохранить" onclick="saveVisit();"></div>
					</div>

				</div>
			</div>
		</div>

		<div class="box neutral" style="vertical-align: top;">
			<div class="box-body" style="min-width: 430px;" id="app">
				<h2>Процедуры</h2>
				<ul class="horisontalMenu">
					<li><a href="#" v-bind:class="{ isActive:(date=='<?= date("Y-m-d"); ?>')}" v-on:click="date = '<?= date("Y-m-d"); ?>';scheduleRender();" >Сегодня (<?= date("d.m"); ?>)</a></li>
					<li><a href="#" v-bind:class="{ isActive:(date=='<?= date("Y-m-d", time() + 24 * 60 * 60); ?>')}" v-on:click="date = '<?= date("Y-m-d", time() + 24 * 60 * 60); ?>';scheduleRender();">Завтра (<?= date("d.m", time() + 24 * 60 * 60); ?>)</a></li>
					<li><a href="#" v-bind:class="{ isActive:(date=='<?= date("Y-m-d", time() + 2 * 24 * 60 * 60); ?>')}" v-on:click="date = '<?= date("Y-m-d", time() + 2 * 24 * 60 * 60); ?>';scheduleRender();">Послезавтра (<?= date("d.m", time() + 2 * 24 * 60 * 60); ?>)</a></li>

					<li><a href="#" v-bind:class="{ isActive:(date=='<?= date("Y-m-d", time() + 3 * 24 * 60 * 60); ?>')}" v-on:click="date = '<?= date("Y-m-d", time() + 3 * 24 * 60 * 60); ?>';scheduleRender();">(<?= date("d.m", time() + 3 * 24 * 60 * 60); ?>)</a></li>

					<li><a href="#" v-bind:class="{ isActive:(date=='<?= date("Y-m-d", time() + 4 * 24 * 60 * 60); ?>')}" v-on:click="date = '<?= date("Y-m-d", time() + 4 * 24 * 60 * 60); ?>';scheduleRender();">(<?= date("d.m", time() + 4 * 24 * 60 * 60); ?>)</a></li>

				</ul>
				<div style="display: grid; grid-template-columns: auto auto; margin: 10px;">
					<div>Шаблон СМС</div>
					<div><select name="smsTemplate" id="smsTemplate"><?
							foreach (query2array(mysqlQuery("SELECT `idsmsTemplates` ,`smsTemplatesName`  FROM `smsTemplates` WHERE isnull(`smsTemplatesDeleted`) AND `smsTemplatesGroup`='12'")) as $template) {
								?><option value="<?= $template['idsmsTemplates']; ?>"><?= $template['smsTemplatesName']; ?></option><?
							}
							?></select></div>
				</div>

				<div style="align-self: center;">
					<input type="text" autocomplete="off"  placeholder="Поиск" id="serviceSearch" onkeydown="
							if (event.keyCode === 38) {
								pointer--;
							} else if (event.keyCode === 40) {
								pointer++;
							}
							let confirm = false;
							if (event.keyCode === 13) {
								confirm = true;
							}
							suggest(this.value, confirm);
						   " oninput="pointer = 0; suggest(this.value);" style="display: inline; width: auto;">
					<ul id="suggestions" class="suggestions" style="">
					</ul>
				</div>
				<div class="pool">
					<div class="noodle" v-on:click="selectedService = item.id; selectedPrice = item.price; scheduleRender();" v-for="item in poolRender" v-bind:class="{ isActive:(selectedService==item.id)}">{{item.name}} <span style="color: red; cursor: pointer;" v-on:click="deleteNoodle(item.id);"><i class="far fa-times-circle"></i></span></div>
				</div>
				<div style="text-align: center; padding: 20px; ">
					<input type="text" id="servicesAppliedPrice"  v-model="selectedPrice" style="display: inline-block; width: auto; text-align: center;" placeholder="ЦЕНА">
					<input type="button" value="Бесплатно" onclick="app.selectedPrice = 0;">
				</div>
				<div class="scheduleTable">
					<div class="personnel" v-for="personnel in schedule">
						<div class="head">{{personnel.usersLastName}} {{personnel.usersFirstName}}</div>
						<div class="time">
							<div class="pill noodle"
								 v-for="pill in personnel.pills"
								 v-on:click="selectedPersonnel=pill.personnel; selectedTimestamp=pill.time; scheduleRender();"
								 v-bind:class="{isActive: (selectedTimestamp==pill.time && selectedPersonnel==pill.personnel)}"
								 >{{time(pill.time)}}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


	<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
	<script src="jsinclude/callfunctions.js" type="text/javascript"></script>

	<script>
	<?
	$services = query2array(mysqlQuery("SELECT `idservices` as `id`, `servicesName` as `name`,ifnull((SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `id` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `id`)),0) as `price`,`servicesTypesName` as `typeName` FROM `services` LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) WHERE isnull(`servicesDeleted`) AND"
					. " (SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`)>0 "
					. ""));
	?>



						var app = new Vue({
							el: '#app',
							data: {
								services: <?= json_encode($services, 288); ?>,
								poolArray: [],
								schedule: [],
								date: null,
								selectedService: null,
								selectedPrice: null,
								selectedPersonnel: null,
								selectedTimestamp: null

							},
							computed: {
								poolRender: function () {
									let out = [];
									for (let noodle of this.poolArray) {
										let index = this.services.indexOf(this.services.find(service => {
											return service.id === noodle;
										}));
										out.push(this.services[index]);
									}
									return out;
								}
							},
							methods: {
								deleteNoodle: function (id) {
									event.stopPropagation();
									console.log('deleteNoodle', id);
									let index = this.poolArray.indexOf(id);
									this.poolArray.splice(index, 1);
									console.log(this.poolArray);
									window.localStorage.setItem('poolArray', JSON.stringify(app.poolArray));
								},
								time: function (timestamp, long = false) {
									let date = new Date(timestamp * 1000);
									let H = date.getHours() > 9 ? date.getHours() : '0' + date.getHours();
									let i = date.getMinutes() > 9 ? date.getMinutes() : '0' + date.getMinutes();
									return H + ':' + i;
								},
								scheduleRender: function (params) {
									console.log('scheduleRender');
									fetch('IO.php', {
										body: JSON.stringify({action: 'getAvailableTime', date: this.date, service: this.selectedService}),
										credentials: 'include',
										method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
									}).then(result => result.text()).then(function (text) {
										try {
											let jsn = JSON.parse(text);
											app.schedule = jsn;
										} catch (e) {
											console.log('no');
											app.schedule = [];
											console.log(e);
										}
									});
								},
								saveVisit: function () {


									console.log('saveVisit');
									if (!qs('#idRCC_phone').value && !qs('#call').value && !qs('#clientPhoneNumber').value) {
										MSG('Ошибка определения телефонного номера');
										return false;
									}
									if (!callResult.value) {
										MSG('Укажите результат звонка');
										return false;
									}
									if (callResult.value === '4' && !dateRecallValue.value) {
										MSG('Укажите дату звонка');
										return false;
									}



									let call = {
										client: {
											clientLName: clientLName.value.trim(),
											clientFName: clientFName.value.trim(),
											clientMName: clientMName.value.trim(),
											clientDatabase: pdb.value.trim(),
											clientPhone: qs('#clientPhoneNumber').value.trim()
										},
										smsTemplate: smsTemplate.value,
										callResult: callResult.value,
										dateRecall: dateRecallValue.value || null,
										callSrc: callSrc.value,
										comment: clientComment.value.trim(),
										idRCC_phone: idRCC_phone.value,
										callid: qs('#call').value,
										servicesApplied: app.appointments
									};

									if (!clientLName.value && !clientFName.value) {
										MSG('Укажите фамилию и/или имя клиента');
										return false;
									}


									if (!app.selectedService || !app.selectedTimestamp) {
										MSG('Укажите процедуру и время');
										return false;
									}


									fetch('IO.php', {
										body: JSON.stringify({action: 'saveCall', call: call}),
										credentials: 'include',
										method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
									}).then(result => result.text()).then(function (text) {
										try {
											let jsn = JSON.parse(text);
	//											app.schedule = jsn;
	//											qs('#phoneNumber').innerHTML = '...';
	//											idRCC_phone.value = '';
	//											callResult.value = '';
	//											clientComment.value = '';
	//											app.date = null;
	//											app.selectedService = null;
	//											app.selectedPrice = null;
	//											app.selectedPersonnel = null;
	//											app.selectedTimestamp = null;
	//											dateRecall.style.display = 'none';
	//											qs('#clientLName').value = '';
	//											qs('#clientFName').value = '';
	//											qs('#clientMName').value = '';
	//											qs('#searchBTN').disabled = false;
											GR({call: null});
										} catch (e) {
											console.log('no');
											console.log(e);
											qs('#searchBTN').disabled = false;
										}
									});


								}
							},
							mounted: function () {
								this.$nextTick(function () {
									this.poolArray = (JSON.parse(window.localStorage.getItem('poolArray')) || []);
								});
							}
						});


	</script>
	<script>
	<? if ($_GET['call'] ?? false) { ?>
			getPhone(<?= $_GET['call']; ?>);
	<? } ?>
	</script>
	<!--<a href="https://vita.menua.pro/sync/utils/voip/call3.php?src=160&dist=89052084769" target="_blank">Нажми меня нежно</a>-->
	<?
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
