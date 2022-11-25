<div style="border: 0px solid blue; padding: 13px;">
	<?
	$medrecords = mfa(mysqlQuery("SELECT COUNT(1) as `cnt` FROM `medrecords` WHERE `medrecordsServiceApplied` = '" . $serviceApplied['idservicesApplied'] . "'"))['cnt'];

	$formReqired = ($serviceApplied['servicesAppliedStarted'] && array_search_2d(1, $serviceApplied['serviceMotivation'], 'serviceMotivationMotivation'));
//	printr($serviceApplied);
	if ($formReqired) {
		?>
		<h3>Форма первичного приёма</h3>
		<?
		$medrecordsForms = query2array(mysqlQuery("SELECT * FROM `medrecordsForms`"));
		foreach ($medrecordsForms as $medrecordsFormIndex => $medrecordsForm) {
			$medrecordsForms[$medrecordsFormIndex]['medrecordsFormsData'] = json_decode($medrecordsForms[$medrecordsFormIndex]['medrecordsFormsData'], 1);
		}
		?>
		<?
		$filledForms = query2array(mysqlQuery("SELECT"
						. " `idmedrecords`, `medrecordsClient`, `medrecordsUser`, `medrecordsForm`, `medrecordsFormData`, `medrecordsTime`,"
						. " `medrecordsFormsName`, CONCAT_WS(' ',`usersLastName`,`usersFirstName`,`usersMiddleName`) as `userName`"
						. " FROM `medrecords`"
						. " LEFT JOIN `medrecordsForms` ON (`idmedrecordsForms` = `medrecordsForm`)"
						. " LEFT JOIN `users` ON (`idusers` = `medrecordsUser`)"
						. " WHERE `medrecordsClient` =  '" . mres($_GET['client']) . "'"));
		foreach ($filledForms as $filledFormIndex => $filledForm) {
			$filledForms[$filledFormIndex]['medrecordsFormData'] = json_decode($filledForms[$filledFormIndex]['medrecordsFormData'], 1);
			$filledForms[$filledFormIndex]['medrecordsTime'] = date("d.m.Y", strtotime($filledForm['medrecordsTime']));
		}
		?>
		<div id="formapp">
			Ранее заполненные осмотры: 
			<select v-model="prewfilledFormID" style="margin: 10px;">
				<option value="">Новый осмотр</option>
				<option v-for="filledForm in filledForms" :value="filledForm.idmedrecords">{{filledForm.medrecordsFormsName}} от {{filledForm.medrecordsTime}} ({{filledForm.userName}})</option>
			</select>

			<input type="checkbox" v-model="req" style="display: <?= $_USER['id'] == 176 ? 'inline' : 'none' ?>;">
			<form  autocomplete="off" id="theform"  oninput="formRender()" method="post" action="<?= GR(); ?>">
				<input type="hidden" name="serviceApplied" value="<?= $serviceApplied['idservicesApplied']; ?>">
				<select autocomplete="off" v-model="currentFormId" name="form" style="margin: 10px;">
					<option value="">Выбрать форму</option>
					<option v-for="form in formsdata" :value="form.idmedrecordsForms">{{form.medrecordsFormsName}}</option>
				</select>
				<div v-if="prewfilledFormID">
					<div><a :href="'/pages/proclist/printmedrecord.php?record='+prewfilledFormID" target="_blank" style="font-size: 1.5em; padding: 20px; display: inline-block"><i class="fas fa-print"></i> Распечатать</a></div>
					<div v-for="value,key in prewfilledForm" style="margin-bottom: 10px; border-bottom: 1px solid silver;">
						<b>{{key}}: </b> {{typeof(value)=== 'object'?Object.values(value).join(', '):value}}
					</div>
				</div>

				<div v-if="currentForm" v-for="field,fieldindex in currentForm.medrecordsFormsData.fields" style="border: 1px solid silver; background-color: #FAFAFA; padding: 10px; display: grid; grid-template-columns:20px 1fr 50px; margin: 10px;">
					<div style="display: flex">{{fieldindex+1}}</div>
					<div style="">
						<div style="font-size: 1.2em; font-weight: bold;">{{field.name}}
							<span v-if="field.required && req" style="color: red;">*</span>
						</div>
						<input autocomplete="off" :name="'formdata['+field.name+']'" v-if="field.type=='text'" type="text" :placeholder="field.name" :required="field.required && req">
						<input autocomplete="off" :name="'formdata['+field.name+']'" v-if="field.type=='date'" type="date" :required="field.required && req">
						<textarea autocomplete="off" style="width: 100%; border-radius: 0px; resize: none;" :name="'formdata['+field.name+']'" v-if="field.type=='textarea'" :placeholder="field.name" :required="field.required && req"></textarea>
						<div v-if="field.type=='radio'">
							<div v-for="option,index of field.options" style="padding: 5px;">
								<label>	<input autocomplete="off" type="radio"  :name="'formdata['+field.name+']'" style="display: inline;" :value="field.options[index]" :required="field.required && req">
									{{field.options[index]}}
								</label>
							</div>
						</div>
						<div v-if="field.type=='checkbox'">
							<div class="checkbox-group required">
								<div v-for="option,index of field.options" style="padding: 5px;">
									<label>
										<input autocomplete="off" type="checkbox" :name="'formdata['+field.name+']'+'['+index+']'" :value="field.options[index]" style="display: inline;"> {{field.options[index]}}
									</label>
								</div>
							</div>	
						</div>	
					</div>
				</div>

				<div style=" text-align: center; padding: 20px;">
					<input v-if="!prewfilledFormID" type="submit" value="Сохранить данные осмотра">
					<input v-if="prewfilledFormID" type="button" @click="prewfilledFormID=''" value="Заполнить новую форму">
				</div>


			</form>


			<table>
				<tr>
					<th><textarea id="formRender" style="height: 400px; width: 400px; display: none;"></textarea></th>
				</tr>
			</table>
		</div>

		<script>

			let formsdata = <?= json_encode($medrecordsForms ?? [], 288); ?>;
			let filledForms = <?= json_encode($filledForms ?? [], 288); ?>;
			function formRender() {
				document.querySelector(`#formRender`).value = JSON.stringify(serializeFormData(new FormData(document.querySelector(`#theform`))), null, 4);
			}
			var appForms = new Vue({
				el: '#formapp',
				data: {
					req: true,
					prewfilledFormID: '',
					prewfilledForm: {},
					currentFormId: '',
					formsdata: formsdata,
					filledForms: filledForms
				},
				watch: {
					currentFormId: function () {
						this.prewfilledFormID = '';
					},
					prewfilledFormID: function (newValue) {
						if (newValue) {
							this.prewfilledForm = this.filledForms.find(elem => {
								return elem.idmedrecords == newValue;
							}).medrecordsFormData || {};
							console.log(this.prewfilledForm);
						} else {
							this.prewfilledForm = {};
						}

					}
				},
				computed: {
					currentForm: function () {
						return this.formsdata.find(form => form.idmedrecordsForms == this.currentFormId) || {medrecordsFormsData: {fields: []}};
					},
					//					appRender: {
					//						get: function () {
					//							return JSON.stringify(this.form, null, 2);
					//						}
					//						,
					//						set: function (newValue) {
					//							let data = JSON.parse(newValue);
					//							this.form = data;
					//						}
					//					}


				},
				methods: {
				},
				mounted: function () {
				}
			});
		</script>
	<? } ?>
</div>