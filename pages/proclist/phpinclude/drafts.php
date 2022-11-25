<?
if ($_GET['saleDraft'] ?? $_GET['saleDraftTemplate'] ?? false) {

	if ($_GET['saleDraftTemplate'] ?? false) {
		$saleDraftTemplate = mfa(mysqlQuery("SELECT * FROM `f_salesDraftTemplates` WHERE `idf_salesDraftTemplates` = '" . mres($_GET['saleDraftTemplate']) . "'"));
	}
	?>
	<div id="vueapp">
		<h3><?= htmlspecialchars($saleDraftTemplate['f_salesDraftTemplatesName'] ?? ''); ?></h3>
		<div class="lightGrid" style=" display: grid; grid-template-columns: repeat(6, auto);">
			<div style="display: contents;">
				<div class="B C">Атикул</div>
				<div class="B C">Наименование</div>
				<div class="B C">Цена</div>
				<div class="B C">Кол-во</div>
				<div class="B C">Сумма</div>
				<div class="B C">х</div>
			</div>
			<div v-for="(subscriptionDraft, index) in subscriptionsDraft" style="display: contents;">
				<div style="display: flex; align-items: center; justify-content: center;">{{subscriptionDraft.servicesCode}}</div>
				<div style="display: flex; align-items: center;">{{subscriptionDraft.servicesName}}</div>
				<div style="display: flex; align-items: center; justify-content: center;" :title="subscriptionDraft.priceMin">
					<input v-if="editable==1"  style="width: 60px; text-align: center;" type="text" oninput="digon(); this.value=this.value==''?0:parseInt(this.value,10);" v-model="subscriptionDraft.price">
					<span v-else>{{subscriptionDraft.price}}</span>

				</div>
				<div style="display: flex; align-items: center; justify-content: center;">
					<input v-if="editable==1" style="width: 60px; text-align: center;" type="text" oninput="digon(); this.value=this.value==''?0:parseInt(this.value,10);" v-model="subscriptionDraft.qty">
					<span v-else>{{subscriptionDraft.qty}}</span>
				</div>
				<div  style="display: flex; align-items: center; justify-content: flex-end; padding-right: 5px;">{{summByService(subscriptionDraft)}}р.</div>
				<div  style="display: flex; align-items: center; justify-content: center;">
					<span v-if="editable==1" style="color: red; cursor: pointer;" v-on:click="deleteSubscriptionsDraft(index);"><i class="far fa-times-circle"></i></span>

				</div>
			</div>
			<div style="display: contents;">
				<div class="C"> </div>
				<div>
					<input v-if="editable" type="text" v-model="servicesSearchText" v-on:keyup="searchServices"  autocomplete="off"  placeholder="Поиск" id="serviceSearch" style="display: inline; width: auto;">
					<ul class="suggestions">
						<li v-for="(suggestion,index) in suggestions" v-on:click="confirmSearch(index);">
							<span v-html="suggestion.servicesNameHighlighted"></span>
							<div v-bind:class="[{ 'pointed': suggestionsIndex==index }, 'mask']"></div>
						</li>
					</ul>
				</div>

				<div class="R B" style=" grid-column: span 2;">Итого:</div>
				<div class="R B" style=" padding-right: 5px;">{{totalSumm}}р.</div>
				<div class="C"></div>
			</div>
		</div>

		<div style="margin: 20px; display: grid; grid-template-columns: repeat(2,auto);">
			<div>
				<input v-if="editable" type="button" v-on:click="saveDraft(true);" value="Сохранить как новый шаблон">
				<?
				if ($_GET['saleDraftTemplate'] ?? false) {
					?><input v-if="editable" type="button" v-on:click="saveDraft(true,{id:'<?= $saleDraftTemplate['idf_salesDraftTemplates']; ?>',name: '<?= htmlspecialchars($saleDraftTemplate['f_salesDraftTemplatesName']); ?>'});" value="Сохранить шаблон"><? } ?>
			</div>
			<div class="R">
				<input v-if="editable && cansave" type="button" v-on:click="saveDraft(); " value="Сохранить план лечения клиенту">
				<input v-else type="button" style="background: pink;" value="Сохранить план лечения клиенту">

			</div>
		</div>

		<? if (0 && $_USER['id'] == 176) { ?>
			<textarea v-model="appRender" style="width: 100%; border-radius: 3px; padding: 4px; height: 400px; grid-column: span 2;"></textarea>
		<? } ?>

	</div>

	
	<?
	if ($_GET['saleDraft'] ?? false) {
		$f_subscriptionsDraft = query2array(mysqlQuery(""
						. " SELECT "
						. " `idservices`,"
						. " `servicesParent`,"
						. " `servicesCode`,"
						. " `servicesName`,"
						. " `serviceNameShort`,"
						. " `servicescolN804`,"
						. " `f_salesDraftAuthor`='" . mres($_GET['personal'] ?? $_USER['id']) . "' as `editable`,"
						. " `f_subscriptionsDraftPrice` AS `price`,"
						. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`)) as `priceMin`,"
						. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<=NOW() AND `servicesPricesType`='2' AND `servicesPricesService` = `idservices`)) as `priceMax`,"
						. " `f_subscriptionsDraftQty` AS `qty`"
						. " FROM `f_subscriptionsDraft` "
						. " LEFT JOIN `services` ON (`idservices` = `f_subscriptionsDraftService`)"
						. " LEFT JOIN `f_salesDraft` ON (`idf_salesDraft` = `f_subscriptionsDraftSaleDraft`)"
						. " WHERE `f_subscriptionsDraftSaleDraft` = '" . intval($_GET['saleDraft']) . "' AND isnull(`servicesDeleted`)"));
	}
	if ($_GET['saleDraftTemplate'] ?? false) {
		//idf_subscriptionsDraftTemplates, , , , 
		//idf_salesDraftTemplates, f_salesDraftTemplatesDate, f_salesDraftTemplatesAuthor, f_salesDraftTemplatesName
		$f_subscriptionsDraft = query2array(mysqlQuery(""
						. " SELECT "
						. " `idservices`,"
						. " `servicesParent`,"
						. " `servicesCode`,"
						. " `servicesName`,"
						. " `serviceNameShort`,"
						. " `servicescolN804`,"
						//. " `f_salesDraftAuthor`='" . $_USER['id'] . "' as `editable`,"
						. " `f_subscriptionsDraftTemplatesPrice` AS `price`,"
						. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`)) as `priceMin`,"
						. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<=NOW() AND `servicesPricesType`='2' AND `servicesPricesService` = `idservices`)) as `priceMax`,"
						. " `f_subscriptionsDraftTemplatesQty` AS `qty`"
						. " FROM `f_subscriptionsDraftTemplates` "
						. " LEFT JOIN `services` ON (`idservices` = `f_subscriptionsDraftTemplatesService`)"
						. " LEFT JOIN `f_salesDraftTemplates` ON (`idf_salesDraftTemplates` = `f_subscriptionsDraftTemplatesSaleDraftTemplate`)"
						. " WHERE `f_subscriptionsDraftTemplatesSaleDraftTemplate` = '" . intval($_GET['saleDraftTemplate']) . "' AND isnull(`servicesDeleted`)"));
	}
//										printr($f_subscriptionsDraft);

	foreach ($f_subscriptionsDraft as &$f_subscriptionDraft) {
		$n = 0;
		$f_subscriptionDraft['price'] = round($f_subscriptionDraft['price'], 2);
		$f_subscriptionDraft['priceMin'] = round($f_subscriptionDraft['priceMin'], 2);
		$f_subscriptionDraft['priceMax'] = round($f_subscriptionDraft['priceMax'], 2);
		while ($f_subscriptionDraft['servicesParent'] !== '1' && $n < 10) {
			$n++;
			$parent = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . $f_subscriptionDraft['servicesParent'] . "'"));
			if ($parent) {
				$f_subscriptionDraft['servicesParent'] = $parent['servicesParent'];
				$f_subscriptionDraft['servicesCode'] = (string) $parent['servicesCode'] . (string) $f_subscriptionDraft['servicesCode'];
			} else {
				break;
			}
		}
	}
	?>
	<pre>	</pre>
	<script>
	let saleDraft = <?= ($_GET['saleDraft'] ?? false) ? intval($_GET['saleDraft']) : 'null'; ?>;
	let saleDraftTemplate = <?= ($_GET['saleDraftTemplate'] ?? false) ? intval($_GET['saleDraftTemplate']) : 'null'; ?>;
	let subscriptionsDraft = <?= json_encode($f_subscriptionsDraft, JSON_UNESCAPED_UNICODE); ?>;

	var app = new Vue({
	el: '#vueapp',
	data: {
		saleDraft,
		editable: <?=
	( (($_GET['saleDraftTemplate'] ?? false) || ((mfa(mysqlQuery("SELECT * FROM `f_salesDraft` WHERE `idf_salesDraft` = '" . intval($_GET['saleDraft']) . "'"))['f_salesDraftAuthor'] ?? false) == mres($_GET['personal'] ?? $_USER['id']))) ? 'true' : 'false');
	?>,
		client: <?= $_GET['client'] ?? 'null' ?>,
		servicesSearchText: '',
		lastSuccessSearchLength: 0,
		suggestions: [],
		//													cansave: true,
		suggestionsIndex: 0,
		subscriptionsDraft: subscriptionsDraft
	},
	computed: {
		cansave: function () {
			let rs = Math.random();
			let out = true;
			this.subscriptionsDraft.forEach(element => {
				if (parseInt(element.price) > 0 && parseInt(element.price) < parseInt(element.priceMin)) {
					out = false;
				}

			});
			console.log('cansave', rs, out);
			return out;
		},
		totalSumm: function () {
			let summ = 0;
			this.subscriptionsDraft.forEach(element => {
				summ += this.summByService(element);
			});
			return summ;
		},
		appRender: {
			get: function () {
				return JSON.stringify(
						{
							saleDraft: this.saleDraft,
							subscriptionsDraft: this.subscriptionsDraft,
							servicesSearchText: this.servicesSearchText,
							lastSuccessSearchLength: this.lastSuccessSearchLength,
							suggestions: this.suggestions,
							suggestionsIndex: this.suggestionsIndex
						}
				, null, 2);
			},
			set: function (newValue) {
				let data = JSON.parse(newValue);
				this.subscriptionsDraft = data.subscriptionsDraft;
			}
		}
	},
	methods: {
		summByService: function (service) {
			return (service.qty || 0) * (service.price || 0);
		},
		confirmSearch: function (n) {
			this.suggestions[n].qty = 1;
			delete(this.suggestions[n].servicesNameHighlighted);
			delete(this.suggestions[n].servicesDuration);
			this.subscriptionsDraft.push({...this.suggestions[n]});
			this.resetSearch();
		},
		resetSearch: function () {
			this.servicesSearchText = '';
			this.suggestions = [];
			this.lastSuccessSearchLength = 0;
		},
		saveDraft: function (asTemplate = false, params = {}) {
			let templateName = '';
			if (asTemplate) {
				templateName = prompt('Название шаблона плана лечения:', params.name || '');
				if (!templateName) {
					return false;
				}
			}
			fetch('IO.php', {
				body: JSON.stringify({action: 'saveDraft', data: {
						idtemplate: params.id || null,
						asTemplate: asTemplate,
						client: this.client,
						templateName: templateName,
						saleDraft: this.saleDraft,
						subscriptionsDraft: this.subscriptionsDraft
					}}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(function (text) {
				try {
					let jsn = JSON.parse(text);
					if (jsn.success) {
						MSG({type: 'success', text: 'Сохранено!', autoDismiss: 1000});
						console.log();
					} else {
						console.error('not success');
					}
				} catch (e) {
					console.error('error catched');
					console.error(e);
				}
			});
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

			if (event.target.value.length < 4) {
				this.suggestions = [];
				return false;
			}

			this.suggestionsIndex = 0;
			fetch('/sync/api/local/services/suggestions.php', {
				body: JSON.stringify({search: event.target.value, newonly: false}),
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
					app.schedule = [];
					console.log(e);
				}
			});
			console.log(event.target.value, event.keyCode);
		},
		deleteSubscriptionsDraft: function (n) {
			event.stopPropagation(); //
			this.subscriptionsDraft.splice(n, 1);
		}
	},
	mounted: function () {
		this.$nextTick(function () {
			//			this.poolArray = (JSON.parse(window.localStorage.getItem('poolArray')) || []);
			//			this.call.smsTemplate = (JSON.parse(window.localStorage.getItem('smsTemplate')) || '');
		});
	}
	});
	</script>


	<?
}