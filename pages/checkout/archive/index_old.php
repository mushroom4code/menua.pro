<?php
$pageTitle = 'Оплата';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(26)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(26)) {
	?>E403R26<?
} else {
	?>
	<style>
		.paycheckTable {
			display: grid; grid-template-columns: auto auto;
			grid-gap: 8px;
		}
		.paycheckTable>div {
			display: contents;
		}
		#instantPaymentDrawer {
			display: none;
		}
		#bankPaymentDrawer {
			display: none;
		}
		#internalPaymentDrawer {
			display: none;
		}
	</style>
	<div class="box neutral">
		<div class="box-body">
			<div class="paycheckTable">
				<div>
					<div>Клиент: </div>
					<div>
						<input type="text" placeholder="Поиск">
						<div>Фамилия:</div>
						<div>Имя:</div>
						<div>Отчество:</div>
					</div>
				</div>
				<div>
					<div></div>
				</div>
				<div>
					<div style="grid-column: 1/-1">
						<hr>
					</div>

				</div>
				<?
				$services = query2array(mysqlQuery("SELECT `idservices` as `id`, `servicesName` as `name`,`servicesBasePrice` as `price`,`servicesTypesName` as `typeName` FROM `services` LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) WHERE isnull(`servicesDeleted`)"));
				?>
				<script>
					let services = <?= json_encode($services, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
					function recursiveReduce(inputArray = [], str = '') {
						//	console.log('recursiveReduce.js');
						str = str.trim();
						let strArr = str.toString().split(" ".toString());
						let res = [];
						for (let bit of strArr) {
							res[res.length] = "(" + bit + ")";
						}

						for (let item of inputArray) {
							if (str !== '' && res.length > 0) {
								let skip = false;
								item.r = '';
								//	console.log('item', item);
								for (let bit of res) {
									//	console.log(bit);
									let reg = new RegExp(bit, 'gi');
									if (!item.hasOwnProperty('name') || !item.name || !item.name.toString().match(reg)) {
										skip = true;
									} else {
										item.r = (item.r || item.name).toString().replace(reg, function (str) {//itemsName
											return '<b class="red">' + str + '</b>';
										});
									}
								}
								if (!skip) {
									item.marked = true;
								} else {
									item.marked = false;
								}
							}

						}

						return inputArray.filter(el => {
							return el.marked == true;
						});
					}

					let subscriptionContent = [];
					function addToSubscriptionContent(content) {
						clearElement(qs('#suggestions'));
						qs('#serviceSearch').value = '';
						if (content.service) {
							let service = services.find(element => {
								return element.id === content.service;
							});
							if (service) {
								subscriptionContent.push({service: service, qty: 1});
							}
						}

						subscriptionsRender();
					}
					let pointer = 0;
					function suggest(value, confirm = false) {
						clearElement(qs('#suggestions'));
						if (value !== '') {
							let n = 0;
							let result = recursiveReduce(services, value);
							if (result.length > 1) {
								result.forEach(
										element => {
											n++;
											if (n <= 10) {
												if (confirm && pointer === n) {
													addToSubscriptionContent({service: element.id});
													clearElement(qs('#suggestions'));
													return;
												}
												let li = el('li', {innerHTML: `<div data-function="addToSubscriptionContent" data-service="${element.id}" class="mask${pointer === (n) ? ' pointed' : ''}"></div><span>${element.typeName || ''} </span>${element.r}`});
												qs('#suggestions').appendChild(li);
											}
										});
							} else if (result.length === 1) {
								addToSubscriptionContent({service: result[0].id});
							}

						}
						if (confirm) {
							clearElement(qs('#suggestions'));
					}
					}

					function calculateSubscriptionsTotal() {
						qs('#subscriptionTotalValue').value = 0;

						let total = 0;
						subscriptionContent.forEach((element, index) => {
							let subTotal = +(element.service.price || 0) * (element.qty || 0);
							qs(`#subTotal_${index}`).value = subTotal;
							total += subTotal;
						});
						qs('#subscriptionTotalValue').value = total;

					}
				</script>
				<style>
					#suggestions {
						position: absolute;
						width: auto;
						background-color: white;
						border: 1px solid silver;
						box-shadow: 0px 0px 10px hsla(0,0%,0%,0.3);
						border-radius: 4px;
						z-index: 10;
						list-style: none;
						white-space: nowrap;
					}
					#suggestions .red {
						color: red;
					}
					#suggestions span {
						color: gray;
					}
					#suggestions li {
						font-size: 0.8em;
						padding: 2px 10px;
						cursor: pointer;
					}
					#suggestions li .mask{
						position: absolute;
						top: 0px;
						left: 0px;
						width: 100%;
						height: 100%;
						z-index: 10;
					}

					#suggestions li .mask:hover{
						background-color:  hsla(0,0%,0%,0.1);
					}

					#suggestions li .pointed{
						background-color:  hsla(0,0%,0%,0.1);
					}

					.displayContents{
						display: contents;
					}




				</style>
				<div>
					<div>Абонемент: </div>
					<div></div>
				</div>

				<div>
					<div style="grid-column: 1/-1; text-align: center;">
						<div class="box success" style="text-align: left; padding: 10px;">
							<div class="box-body">
								<h2>Состав абонемента:</h2>
							</div>
							<div style="display: grid; grid-template-columns: auto 60px 60px 60px 60px; grid-gap: 5px; ">
								<div style="display: contents;">
									<div style="grid-column: 1/-1;">
										<div style="display: inline-block;">
											<div style="display: grid; grid-template-columns: auto auto;">
												<div style="align-self: center;">
													<input type="text" placeholder="Поиск" id="serviceSearch" onkeydown="
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
													<ul id="suggestions" style="">
													</ul>
												</div>
											</div>
										</div>

									</div>

								</div>
								<div id="subscriptions" style="display: contents;">

								</div>
								<div id="subscriptionTotal" style="display: contents;">
									<div style="display: contents;">
										<div></div>
										<div style="text-align: right;">Итого:</div>
										<div style="grid-column: -3/-1;"><input type="text" id="subscriptionTotalValue" placeholder="сумма"></div>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div>
					<div>Сумма: </div>
					<div>0.00р</div>
				</div>
				<div>
					<div>Способ оплаты: </div>
					<div>
						<div><input type="checkbox" id="instantPayment" onclick="qs('#instantPaymentDrawer').style.display = this.checked ? 'contents' : 'none';"><label for="instantPayment">Оплата на месте</label></div>
						<div><input type="checkbox" id="bankPayment" onclick="qs('#bankPaymentDrawer').style.display = this.checked ? 'contents' : 'none';"><label for="bankPayment">Оформление кредита</label></div>
						<div><input type="checkbox" id="internalPayment" onclick="qs('#internalPaymentDrawer').style.display = this.checked ? 'contents' : 'none';"><label for="internalPayment">Оформление внутренней рассрочки</label></div>

					</div>
				</div>

				<div id="instantPaymentDrawer">
					<div>Оплата на месте: </div>
					<div>
						<div style="display: grid; grid-template-columns: auto 100px auto; grid-gap: 7px;">
							<div>Наличными</div><input type="text"><span>р.</span>
							<div>Банковской картой</div><input type="text"><span>р.</span>
						</div>
					</div>
				</div>
				<div id="bankPaymentDrawer2">
					<div style="grid-column: 1/-1;  border-radius: 10px; padding: 5px;">



					</div>

					<div>Кредит:</div>
					<div>
						<div style="display: grid; grid-template-columns: auto auto; grid-gap: 7px;">

							<div style="display: grid; grid-template-columns: auto auto auto; grid-gap: 7px;">
								<div>Банк:</div>
								<select style="grid-column: -3/-1">
									<option>Выбрать Банк</option>
									<?
									$banks = query2array(mysqlQuery("SELECT * FROM `f_banks`"));
									foreach ($banks as $bank) {
										?>
										<option value="<?= $bank['idf_banks']; ?>"><?= $bank['f_banksName']; ?></option>
										<?
									}
									?></select>

								<div>Срок:</div><input type="text" oninput="digon();" size="3"><span>мес.</span>
								<div>№ договора:</div><input type="text"  style="grid-column: -3/-1">
								<div>Сумма с %:</div><input type="text"  oninput="digon();" style="grid-column: -3/-1">
							</div>
						</div>
					</div>
				</div>


				<div id="internalPaymentDrawer">
					<div>Рассрочка:</div>
					<div>
						<div style="display: grid; grid-template-columns: auto auto; grid-gap: 7px;">
							<div style="display: grid; grid-template-columns: auto auto auto; grid-gap: 7px;">
								<div>Срок:</div><input type="text" oninput="digon();" size="3"><span>мес.</span>
							</div>
						</div>
					</div>
				</div>


			</div>
		</div>
	</div>


<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
