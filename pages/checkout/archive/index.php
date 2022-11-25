<?php
$pageTitle = 'Оформление договора';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(26)) {

}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(26)) {
	?>E403R26<?
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
			left: 95%;
			top: 0px;
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

		.displayContents{
			display: contents;
		}
		.salesWindows {
			vertical-align: top;
			width: 100%;
			margin: 20px auto;
			text-align: left;
		}
		#subscriptions {
			white-space: nowrap;
		}
		.box-body {
			/*display: inline-block;*/
		}
	</style>
	<? include 'menu.php'; ?>
	<div style="padding: 10px; text-align: center;">

		<div class="box neutral salesWindows">
			<div class="box-body">
				<h2>Клиент</h2>
				<div style="padding:  20px 0px; display: grid; grid-template-columns: auto auto;"><span>Номер амб.карты</span><div style="text-align: right;"><input style="font-size: 2em; width: 200px;" type="text" autocomplete="off"  id="clientsAKNum" placeholder="№ карты"></div></div>
				<div style="display:none;"><div>client</div><input type="text" id="idclients" placeholder="id" autocomplete="off"></div>
				<div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;">
					<div style="display: contents;"><span>Дата</span><input type="date" autocomplete="off" onchange="sale.date = this.value;" id="date" value="<?= ($_SESSION['salesDate'] ?? date("Y-m-d")); ?>"></div>
					<? $saleTypes = query2array(mysqlQuery("SELECT * FROM `f_salesTypes`")); ?>
					<div style="display: contents;"><span>Тип договора</span>
						<select id="saleType" onchange="sale.type = this.value;" autocomplete="off">
							<option value=""></option>
							<? foreach ($saleTypes as $saleType) {
								?><option value="<?= $saleType['idf_salesTypes']; ?>"><?= $saleType['f_salesTypesName']; ?></option><? }
							?>
						</select></div>
					<div style="display: contents;"><span>Юр.лицо</span>
						<select id="saleEntity" onchange="sale.entity = this.value;" autocomplete="off">
							<option value=""></option>
							<? foreach (query2array(mysqlQuery("SELECT * FROM `entities`")) as $saleEntity) {
								?><option value="<?= $saleEntity['identities']; ?>"><?= $saleEntity['entitiesName']; ?></option><? }
							?>
						</select></div>


					<div style="display: contents;"><span>Фамилия <i class="fas fa-search"></i></span><div><input type="text" autocomplete="off" id="clientsLname"  placeholder="фамилия" oninput="searchClientByLastName(this.value);" onblur="setTimeout(function () {
						qs('#clientSuggestions').innerHTML = '';
						}, 300);"><ul id="clientSuggestions" class="suggestions"></ul></div></div>
					<div style="display: contents;"><span>Имя</span><input type="text" autocomplete="off"  id="clientsFname" placeholder="Имя"></div>
					<div style="display: contents;"><span>Отчество</span><input type="text" autocomplete="off"  id="clientsMname" placeholder="Отчество"></div>
					<div style="display: contents;"><span>Пол</span><div>
							<input type="radio" name="gender" id="genderF"><label for="genderF" style="font-size: 10pt;">Жен.</label>
							<input type="radio" name="gender" id="genderM"><label for="genderM" style="font-size: 10pt;">Муж.</label>
						</div></div>
					<div style="display: contents;"><span>Дата рождения</span><input type="date" autocomplete="off"  id="bday"></div>
					<div style="display: contents;"><span>Место рождения</span><input type="text" autocomplete="off"  id="birthplace" placeholder="населенный пункт"></div>

					<div style="display: contents;"><span>Паспорт №</span><input type="text" autocomplete="off"  id="passportnumber" placeholder="хххх хххххх"></div>
					<div style="display: contents;"><span>Выдан</span><input type="date" autocomplete="off"  id="passportdate"></div>
					<div style="display: contents;"><span>Кем</span><input type="text" autocomplete="off"  id="department" placeholder="наименование организации выдавшей паспорт"></div>
					<div style="display: contents;"><span>Код подразделения</span><input type="text" autocomplete="off"  id="passportcode" placeholder="Код подразделения"></div>
					<div style="display: contents;"><span>Зарегистрирован</span><textarea id="registration" placeholder="по адресу"></textarea></div>
					<div style="display: contents;"><span>Фактическое проживание</span><textarea id="residence" placeholder="по адресу"></textarea></div>
					<div style="display: contents;"><span>Номер телефона</span><input type="text" autocomplete="off"  id="phone" placeholder="89211234567"></div>
				</div>
			</div>
		</div>
		<br>
		<!--<input type="button" value="Сохранить" onclick="saveSale();">-->
		<br>
		<br>
		<div class="box neutral salesWindows" style="vertical-align: top;">
			<div class="box-body">
				<h2>Абонемент</h2>
				<?
				$newServices = query2array(mysqlQuery("SELECT `idservices` as `id`, `servicesName` as `name`,"
								. "ifnull((SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `id` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `id`)),0) as `priceMin`,"
								. "ifnull((SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `id` AND `servicesPricesType`='2') AND `servicesPricesType`='2'  AND `servicesPricesService` = `id`)),0) as `priceMax`,"
								. "`servicesTypesName` as `typeName` FROM `services` LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) WHERE isnull(`servicesDeleted`)"));

				$services = query2array(mysqlQuery("SELECT (SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`) AS `p2s`,`idservices` as `id`, `servicesName` as `name`,ifnull((SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `id` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `id`)),0) as `price`,`servicesTypesName` as `typeName` FROM `services` LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) WHERE isnull(`servicesDeleted`)"
//								. "  AND (SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`)>0 "
								. ""));
				?>
				<script>
					//					let services2 = <? json_encode($newServices, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
					let services = <?= json_encode($services, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
				</script>
				<div style="display: grid; grid-template-columns: auto auto 80px 80px 120px 120px 60px; grid-gap: 5px; ">
					<div style="display: contents;">
						<div style="grid-column: 1/-1;">
							<div style="display: inline-block;">
								<div style="display: grid; grid-template-columns: auto auto;">
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
								</div>
							</div>

						</div>

					</div>
					<div id="subscriptions" style="display: contents;"></div>
					<div id="subscriptionTotal" style="display: contents;">
						<div style="display: contents; white-space: nowrap;">
							<div style="text-align: right; grid-column: 1/-4;">Итого стоимость абонемента:</div>
							<div style="grid-column: -4/-3;"><input style="text-align: right;" type="text" readonly autocomplete="off"  id="subscriptionTotalValue" placeholder="сумма"></div>
						</div>
						<div style="display: contents;">
							<div style="text-align: right; grid-column: 1/-4;">С учётом скидок:</div>
							<div style="grid-column: -4/-3;"><input style="text-align: right;" type="text" readonly autocomplete="off"  id="subscriptionTotalPay" placeholder="сумма" oninput="digon(); sale.payment.summ=+this.value;"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<br>
		<!--<input type="button" value="Сохранить" onclick="saveSale();">-->
		<br>
		<br>
		<div class="box neutral salesWindows" style="vertical-align: top;">
			<div class="box-body">
				<h2>Оплата</h2>
			</div>
			<div style="padding: 5px;">
				<div style="margin: 0px auto 20px auto; display: inline-block;">
					<h3 style="margin: 10px;">Оплата на месте</h3>
					<div style="display: grid; grid-template-columns: auto 100px auto; grid-gap: 7px;">
						<div>Наличными</div><input type="text" autocomplete="off"  oninput="digon(); sale.payment.instant.cash=+this.value;"><span>р.</span>
						<div>Картой</div><input type="text" autocomplete="off" oninput="digon(); sale.payment.instant.bankcard=+this.value;"><span>р.</span>
					</div>
				</div>

				<br>
				<!--<input type="button" value="Сохранить" onclick="saveSale();">-->
				<br>
				<br>

				<div style="margin: 20px auto 20px auto;display: inline-block;">
					<h3 style="margin: 10px;">Банковский кредит</h3>
					<div style="display: grid; grid-template-columns: auto auto; grid-gap: 7px;">
						<?
						$banks = query2array(mysqlQuery("SELECT * FROM `RS_banks` WHERE NOT isnull(`RS_banksShort`)"));
						usort($banks, function ($a, $b) {
							return mb_strtolower($a['RS_banksShort']) <=> mb_strtolower($b['RS_banksShort']);
						});
						?>
						<div style="display: grid; grid-template-columns: auto auto auto; grid-gap: 7px;">
							<div>Банк:</div>
							<select autocomplete="off"  style="grid-column: -3/-1" name="bank" oninput="sale.payment.bank.id=this.value;">
								<option>Выбрать Банк</option>
								<?
								foreach ($banks as $bank) {
									?>
									<option value="<?= $bank['idRS_banks']; ?>"><?= $bank['RS_banksShort']; ?></option>
									<?
								}
								?></select>
							<div>№ договора:</div><div  style="grid-column: -3/-1"><div style="display: inline-block;"><input type="text" autocomplete="off"  oninput="sale.payment.bank.agreementnumber=this.value;"></div></div>
							<div>Сумма</div><div style="grid-column: -3/-1"><div style="display: inline-block;"><input type="text" autocomplete="off"   oninput="digon();sale.payment.bank.summ=+this.value;"></div></div>
	<!--							<div>Сумма с %:</div><div style="grid-column: -3/-1"><div style="display: inline-block;"><input type="text" autocomplete="off"   oninput="digon();sale.payment.bank.summincinterest=+this.value;"></div></div>-->
							<div>Срок:</div><div  style="grid-column: -3/-1"><div style="display: inline-block;"><input type="text" autocomplete="off"  value="24" oninput="digon();sale.payment.bank.period=+this.value;" size="3"></div> <span>мес.</span></div>
						</div>
					</div>
				</div>

				<br>

				<div style="margin: 20px auto 20px auto;display: inline-block;">
					<h3 style="margin: 10px;">Внутренняя рассрочка</h3>
					<div style="display: grid; grid-template-columns: auto auto auto; grid-gap: 7px;">
						<div>Сумма</div><div style="grid-column: -3/-1"><div style="display: inline-block;"><input type="text" autocomplete="off"   oninput="digon();sale.payment.installment.summ=+this.value;"></div></div>
						<div>Срок:</div><div  style="grid-column: -3/-1"><div style="display: inline-block;"><input type="text" autocomplete="off" value="1"  oninput="digon();sale.payment.installment.period=this.value;" size="3"></div> <span>мес.</span></div>
					</div>
				</div>
			</div>
		</div>
		<br>
		<!--<input type="button" value="Сохранить" onclick="saveSale();">-->
		<br>
		<br>
		<div class="box neutral salesWindows" style="vertical-align: top;">
			<div class="box-body">
				<h2>Участники</h2>
				<div style="padding: 5px;">
					<div style="margin: 0px auto 20px auto; display: inline-block;">
						<h3 style="margin: 10px;">Кредитный менеджер</h3>
						<div>
							<?= $_USER['lname']; ?>
							<?= $_USER['fname']; ?>
							<?= $_USER['mname']; ?>
						</div>
					</div>
					<br>
					<div style="margin: 0px auto 20px auto; display: inline-block;">
						<h3 style="margin: 10px;">Координаторы</h3>
						<div style="display: contents;"><div style="padding: 15px;"><input type="text" autocomplete="off"  id="coordinatorsLname"  placeholder="фамилия" oninput="searchCoordsByLastName(this.value);" onblur="setTimeout(function () {
							qs('#coordsSuggestions').innerHTML = '';
							}, 300);"><ul id="coordsSuggestions" class="suggestions"></ul>


							</div>
							<div id="coordinators" style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;"></div>

						</div>
						<script>

							function coordinatorsRender() {
							let coordinatorsContainer = qs('#coordinators');
							clearElement(coordinatorsContainer);
							sale.coordinators.forEach((element, index) => {
							coordinatorsContainer.appendChild(el('div', {
							className: 'displayContents',
									innerHTML: `<div style="display: contents;"><div style="padding-right: 30px;">${element.lname || ''} ${element.fname || ''} ${element.mname || ''}</div>
										<div><input type="button" value="X" style="color: red;" onclick="sale.coordinators.splice(${index},1);coordinatorsRender();"></div></div>`
							}));
							});
							}
							async function searchCoordsByLastName(lastname) {
							qs('#coordsSuggestions').innerHTML = '';
							fetch('IO.php', {
							body: JSON.stringify({
							action: 'coordsSuggestions',
									lastname: lastname
							}),
									credentials: 'include',
									method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
							}).then(result => result.text()).then(async function (text) {
							try {
							let jsn = JSON.parse(text);
							if ((jsn.coords || []).length) {
							jsn.coords.forEach(coord => {
							let li = el('li', {innerHTML: `<div class="mask"></div><span>${coord.lname || ''} ${coord.fname || ''} ${coord.mname || ''}</span>`});
							li.addEventListener('click', function () {
							addCoord(coord);
							qs('#coordsSuggestions').innerHTML = '';
							qs('#coordinatorsLname').value = '';
							});
							qs('#coordsSuggestions').appendChild(li);
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
							function addCoord(coord) {
							sale.coordinators.push(coord);
							coordinatorsRender();
							}
						</script>
					</div>
					<br>
					<!--<input type="button" value="Сохранить" onclick="saveSale();">-->
					<br>
					<br>
					<div style="margin: 0px auto 20px auto; display: inline-block;">
						<h3 style="margin: 10px;">Участники</h3>
						<div style="display: contents;"><div style="padding: 15px;"><input type="text" autocomplete="off"  id="participantsLname"  placeholder="фамилия" oninput="searchParticipantsByLastName(this.value);" onblur="setTimeout(function () {
							qs('#participantsSuggestions').innerHTML = '';
							}, 300);"><ul id="participantsSuggestions" class="suggestions"></ul>


							</div>
							<div id="participants" style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;"></div>

						</div>
						<script>

							function participantsRender() {
							let participantsContainer = qs('#participants');
							clearElement(participantsContainer);
							sale.participants.forEach((element, index) => {
							participantsContainer.appendChild(el('div', {
							className: 'displayContents',
									innerHTML: `<div style="display: contents;"><div style="padding-right: 30px;">${element.lname || ''} ${element.fname || ''} ${element.mname || ''}</div>
										<div><input type="button" value="X" style="color: red;" onclick="sale.participants.splice(${index},1);participantsRender();"></div></div>`
							}));
							});
							}
							async function searchParticipantsByLastName(lastname) {
							qs('#participantsSuggestions').innerHTML = '';
							fetch('IO.php', {
							body: JSON.stringify({
							action: 'coordsSuggestions',
									lastname: lastname
							}),
									credentials: 'include',
									method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
							}).then(result => result.text()).then(async function (text) {
							try {
							let jsn = JSON.parse(text);
							if ((jsn.coords || []).length) {
							jsn.coords.forEach(coord => {
							let li = el('li', {innerHTML: `<div class="mask"></div><span>${coord.lname || ''} ${coord.fname || ''} ${coord.mname || ''}</span>`});
							li.addEventListener('click', function () {
							addParticipant(coord);
							qs('#participantsSuggestions').innerHTML = '';
							qs('#participantsLname').value = '';
							});
							qs('#participantsSuggestions').appendChild(li);
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
							function addParticipant(coord) {
							sale.participants.push(coord);
							participantsRender();
							}
						</script>
					</div>



				</div>
			</div>
		</div>

		<br>
		<input type="button" id="savebutton" value="Сохранить" onclick="saveSale();">
		<br>
		<br>

		<textarea id='sale' style="width: 500px; height: 700px; display: none;"></textarea>

		<script>
			let sale = {
			date: '<?= ($_SESSION['salesDate'] ?? date("Y-m-d")); ?>',
					type: '',
					client: {},
					subscriptions: [],
					payment: {
					summ: 0,
							instant: {},
							bank: {
							period: 24
							},
							installment: {
							period: 1
							}
					},
					coordinators: JSON.parse('<?
						if (1 && isset($_SESSION['coords'])) {
							print json_encode($_SESSION['coords'], 288);
						} else {
							?>[]<?
						}
						?>'),
									participants: []
							};
							async function saveSale() {


							if (0 && (
									sale?.payment?.bank?.id ||
									sale?.payment?.bank?.summ ||
									sale?.payment?.bank?.agreementnumber
									)
									&&
									!(sale?.payment?.bank?.id &&
											sale?.payment?.bank?.summ &&
											sale?.payment?.bank?.agreementnumber)
									){
							MSG('По банку там чего-то не то...');
							return false;
							}


							if (sale.type == '') {
							MSG('Укажите тип абонемента');
							return false;
							}
							if ((sale.entity || '') == '') {
							MSG('Укажите юр.лицо');
							return false;
							}



							let a = ( + sale.payment.summ);
							let b = (0
									+ ((sale.payment.bank || {}).summ || 0)
									+ ((sale.payment.installment || {}).summ || 0)
									+ ((sale.payment.instant || {}).bankcard || 0)
									+ ((sale.payment.instant || {}).cash || 0)
									);
							if (a != b)
							{
							MSG('Давай на секундочку остановимся<br> и проверим платежи. <br>Банк, нал или рассрочка.<br> Точно всё заполнено?');
							fetch('/sync/api/icq/jse.php', {body: JSON.stringify({
							e: 'Чуть не отправила продажу с кривыми данными по оплате. Аллилуя я спасла 😂',
									payment: sale.payment,
									b: `${(sale.payment.bank.summ || 0)} + ${((sale.payment.installment || {}).summ || 0)} + ${((sale.payment.instant || {}).bankcard || 0)} + ${((sale.payment.instant || {}).cash || 0)}`,
									a: `${( + sale.payment.summ)}`
							}), credentials: 'include', method: 'POST', headers: new Headers({'Content-Type': 'application/json'}
							)}
							);
							return false;
							}
							//								return false;
							document.querySelector('#savebutton').disabled = true;
							fetch('IO.php', {
							body: JSON.stringify({action: 'saveSale', sale: sale}),
									credentials: 'include',
									method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
							}).then(result => result.text()).then(async function (text) {
							try {
							let jsn = JSON.parse(text);
							if (jsn.success && jsn.idfsale && jsn.idclients) {
							window.location.href = `/pages/checkout/payments.php?client=${jsn.idclients}&contract=${jsn.idfsale}`; //'/pages/checkout/';
							} else {
							await MSG('Чё-та не так.');
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

							async function searchClientByLastName(lastname) {

							qs('#clientSuggestions').innerHTML = '';
							fillClientFields({lname: lastname});
							if (lastname.length <= 3) {
							return [];
							}
							fetch('IO.php', {
							body: JSON.stringify({
							action: 'searchClientByLastName',
									lastname: lastname
							}),
									credentials: 'include',
									method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
							}).then(result => result.text()).then(async function (text) {
							try {
							let jsn = JSON.parse(text);
							if ((jsn.clients || []).length) {
							jsn.clients.forEach(client => {
							let li = el('li', {innerHTML: `<div class="mask"></div><span>${client.lname || ''} ${client.fname || ''} ${client.mname || ''}  ${client.bday ? `(${client.bday})` : ''}</span>`});
							li.addEventListener('click', function () {
							fillClientFields(client);
							qs('#clientSuggestions').innerHTML = '';
							});
							qs('#clientSuggestions').appendChild(li);
							});
							}
							if (jsn.success) {

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





							setInterval(function () {
							qs('#sale').value = JSON.stringify(sale, null, 2);
							}, 100);

		</script>


	</div>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
