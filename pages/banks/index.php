<?php
$load['title'] = $pageTitle = 'Банковские переводы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (in_array($_USER['id'], [176, 199])) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (in_array($_USER['id'], [176, 199, 135])) {
//	printr($_POST);
	?>

	<div class="box neutral">
		<div class="box-body">
			<div style="padding: 10px;">
				<select id="idbanks" onchange="GR({bank: this.value});">
					<option value="">Выбрать банк</option>
					<?
					foreach (query2array(mysqlQuery("SELECT * FROM `RS_banks` WHERE NOT isnull(`RS_banksShort`) ORDER BY `RS_banksShort`")) as $bank) {
						?><option <?= ($_GET['bank'] ?? '') == $bank['idRS_banks'] ? 'selected' : '' ?> value="<?= $bank['idRS_banks']; ?>"><?= $bank['RS_banksShort']; ?></option><?
					}
					?>
				</select>
			</div>

			<div style="padding: 10px;">
				<input type="text" id="clientName" placeholder="Ф.И.О." oninput="searchContracts();">
			</div>
			<div id="searchResults" class="lightGrid" style="display: grid; grid-template-columns: repeat(9,auto); "></div>

			<?
			$to = date("Y-m-d");
			$unpayedCreditsSQL = "SELECT *"
					. " FROM `f_credits`"
					. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
					. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
					. " LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`)"
					. " LEFT JOIN `users` ON (`idusers` = `f_salesCreditManager`)"
					. " WHERE"
					. " isnull(`f_creditsPayed`) or `f_creditsPayed` >= DATE_ADD('$to 00:00:00', INTERVAL 0 DAY)"
					. " AND NOT isnull(`f_creditsBankID`)"
					. "";
			$unpayedCreditsSQL = "SELECT * "
					. " FROM `f_credits` "
					. " LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`) "
					. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`)"
					. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
					. " LEFT JOIN `users` ON (`idusers` = `f_salesCreditManager`)"
					. "WHERE"
					. " isnull(`f_creditsPayed`)"
					. " AND isnull(`f_creditsCanceled`)"
					. " AND `f_salesDate` >= '2022-01-01'"
					. " AND `f_salesDate` <= '" . $to . "'"
					. " AND NOT isnull(`f_creditsBankID`)"
					. (($_GET['bank'] ?? false) ? (" AND `f_creditsBankID`='" . mres($_GET['bank']) . "'" ) : "");
			$unpayedCredits = query2array(mysqlQuery($unpayedCreditsSQL));
			?>
			<hr style="display: block; margin: 30px;">
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(7,auto);">
				<div style="display: contents;" class="C B">
					<div>Банк</div>
					<div>ФИО клиента</div>
					<div>Номер договора</div>
					<div>Дата оформления</div>
					<div>Комментарий</div>
					<div>Кредитный</div>
					<div>Сумма</div>
				</div>



				<?
				$total = 0;
				foreach ($unpayedCredits as $unpayedCredit) {
					$total += $unpayedCredit['f_creditsSumm'];
					?>
					<div style="display: contents;">
						<div><?= $unpayedCredit['RS_banksShort']; ?></div>
						<div onclick="document.querySelector('#clientName').value = '<?= $unpayedCredit['clientsLName']; ?>';searchContracts();">
							<?= $unpayedCredit['clientsLName']; ?>
							<?= $unpayedCredit['clientsFName']; ?>
							<?= $unpayedCredit['clientsMName']; ?>
						</div>
						<div><?= $unpayedCredit['f_creditsBankAgreementNumber']; ?></div>
						<div><?= date("d.m.Y", strtotime($unpayedCredit['f_salesDate'])); ?></div>
						<div><?= $unpayedCredit['f_salesComment']; ?></div>
						<div><?= $unpayedCredit['usersLastName'] ?? 'Не укзан'; ?> <?= $unpayedCredit['usersFirstName'] ?? ''; ?></div>
						<div class="R"><?= $unpayedCredit['f_creditsSumm']; ?></div>
					</div>
					<?
				}
				?>				<div style="display: contents;" class="C B">
					<div></div>
					<div></div>
					<div></div>
					<div></div>
					<div></div>
					<div></div>
					<div class="R B"><?= $total; ?></div>
				</div>
			</div>
			<? // printr($unpayedCredits);  ?>


			<script>

				let brokers = <?= json_encode(query2array(mysqlQuery("SELECT * FROM `RS_brokers`")), 1); ?>;
				function searchContracts() {

					fetch('IO.php', {
						body: JSON.stringify(
								{
									action: 'searchContracts',
									clientName: qs('#clientName').value,
									idbanks: qs('#idbanks').value
								}),
						credentials: 'include',
						method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
					}).then(result => result.text()).then(async function (text) {
						try {
							let jsn = JSON.parse(text);
							let searchResults = qs('#searchResults');
							searchResults.innerHTML = `	<div style="display: contents;">
	<div>Клиент</div>
	<div class="С">Наименование банка</div>
	<div class="С">Дата</div>
	<div>№ банковского договора</div>
	<div class="С">Сумма кредита</div>
	<div class="с">Погашено</div>
	<div class="С">Остаток</div>
	<div class="С">Внести</div>
	<div class="С">Удалить</div>
	</div>`;
							if ((jsn.contracts || []).length > 0) {


								jsn.contracts.forEach(contract => {
									searchResults.innerHTML += `
	<div style="display: contents;">
	<div>${contract.clientsLName} ${contract.clientsFName} ${contract.clientsMName}</div>
	<div>${contract.RS_banksShort || contract.RS_banksName}</div>
	<div>${contract.f_salesDate}</div>
	<div>${contract.f_creditsBankAgreementNumber}</div>
	<div class="R">${nf(contract.f_creditsSumm)}р.</div>
	<div class="R">${nf(contract.creditsPayedAmount)}р.</div>
	<div class="R">${nf(contract.f_creditsSumm - contract.creditsPayedAmount)}р.</div>
	<div onclick="addTransaction(${contract.idf_credits},${contract.f_creditsSumm - contract.creditsPayedAmount})" style="text-align: center; color: green; cursor: pointer;"><i class="fas fa-plus-circle"></i></div>
	<div onclick="addRemove(${contract.idf_credits})" style="text-align: center; color: red; cursor: pointer;"><i class="fas fa-times-circle"></i></div>
	</div>
	`;
								});
							}
						} catch (e) {
							MSG("Ошибка ответа сервера. <br><br><i>" + e + "</i>");
						}
					}); //fetch
				}
				async function addRemove(idcredit) {
					await fetch('IO.php', {
						body: JSON.stringify({action: 'cancelCredit', creditId: idcredit}),
						credentials: 'include',
						method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
					}).then(result => result.text()).then(async function (text) {
						try {
							let jsn = JSON.parse(text);
							if ((jsn.msgs || []).length) {
								jsn.msgs.forEach(async msg => {
									await MSG(msg);
								});
							}

							if (jsn.success) {
								searchContracts();
							}
						} catch (e) {
							MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
						}
					});//fetch
				}
				function addTransaction(idcredit, summ) {
					let box = el('div', {className: 'modal neutral'});
					box.style.position = 'fixed';
					let boxBody = el('div', {className: 'box-body'});
					box.appendChild(boxBody);
					var wrapper = el('div');
					wrapper.style.display = 'grid';
					wrapper.style.gridTemplateColumns = 'auto auto auto';
					wrapper.style.gridGap = '10px';
					boxBody.appendChild(wrapper);

					let valueInput = el('input');
					valueInput.type = 'text';
					valueInput.value = summ;
					wrapper.appendChild(valueInput);

					let broker = el('select');
					broker.name = 'broker';
					let option = new Option('', '');
					broker.appendChild(option);
					brokers.forEach(elem => {
						let option = new Option(elem.RS_brokersName, elem.idRS_brokers);
						broker.appendChild(option);
					});

					wrapper.appendChild(broker);





					let dateInput = el('input');
					dateInput.type = 'date';
					let date = new Date();
					date.setDate(date.getDate() - 1);
					dateInput.valueAsDate = date;
					wrapper.appendChild(dateInput);


					let promise = new Promise(function (resolve, reject) {
						let cancelBtn = el('button', {innerHTML: `Отмена`});
						cancelBtn.style.margin = '0px 10px';
						box.appendChild(cancelBtn);
						cancelBtn.addEventListener('click', function () {
							box.parentNode.removeChild(box);
							resolve(false);
						});
						let addBtn = el('button', {innerHTML: `Сохранить`});
						box.appendChild(addBtn);
						addBtn.style.margin = '0px 10px';
						addBtn.addEventListener('click', async function () {
							let data = {
								action: 'addTransaction',
								idcredit,
								summ: valueInput.value,
								broker: broker.value,
								date: dateInput.value
							};
							console.log(data);
							await fetch('IO.php', {
								body: JSON.stringify(data),
								credentials: 'include',
								method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
							}).then(result => result.text()).then(async function (text) {
								try {
									let jsn = JSON.parse(text);
									if ((jsn.msgs || []).length) {
										jsn.msgs.forEach(async msg => {
											await MSG(msg);
										});
									}

									if (jsn.success) {
										box.parentNode.removeChild(box);
										searchContracts();
										resolve(false);
									}
								} catch (e) {
									MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
								}
							});//fetch

						});
					});
					document.body.appendChild(box);
					return promise;

				}
			</script>

		</div>
	</div>
<? } else {
	?>
	<div>Нет доступа</div>
<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
