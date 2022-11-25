<?php
$load['title'] = $pageTitle = 'Процедурный лист';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!($client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . mres($_GET['client']) . "'")))) {
	header("Location: /pages/proclist/");
	die('no such client');
}



include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
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

	.isActive {
		background-color: #d0FFd0 !important;
	}
</style>


<script src="/sync/3rdparty/vue.min.js" type="text/javascript"></script>
<div>
	<div class="box neutral">
		<div class="box-body" style="">
			<h2><?= mb_ucfirst($client['clientsLName']); ?> <?= mb_ucfirst($client['clientsFName']); ?> <?= mb_ucfirst($client['clientsMName']); ?> </h2>
			<?
			include 'clientsmenu.php';
			?>


			<div id="vueapp">
				<div style="text-align: center; border: 1px solid silver; margin: 5px 20px; background-color: white; padding: 20px;">
					<div style="text-align: left; display: inline-block;">
						<h3>Назначения</h3>
						<div style="display: inline-block;">
							<div class=" lightGrid" style="display: grid; grid-template-columns: repeat(7, auto); font-size: 1em; line-height: 1em;">
								<div style=" display: contents;">
									<div class="C B">Дата</div>
									<div class="C B">Время</div>
									<div class="C B">Статус</div>
									<div class="C B">Абонемент</div>
									<div class="C B">Услуга</div>
									<div class="C B">Специалист</div>
									<div class="C B"><i class="far fa-times-circle"></i></div>
								</div>
								<div v-for="(serviceApplied,index) in data.client.servicesApplied" style=" display: contents;">
									<div class="C">{{mydate(serviceApplied.servicesAppliedTimeBegin)}}</div>
									<div class="C">{{mytime(serviceApplied.servicesAppliedTimeBegin)}}</div>
									<div>{{status(serviceApplied)}}</div>
									<div class="C" v-html="(serviceApplied.servicesAppliedContract||(serviceApplied.servicesAppliedPrice?`<b style='color: red;'>Б/А  ${serviceApplied.servicesAppliedPrice}р.</b>`:`<b style='color: red;'>Б/А 0р.</b>`))"></div>
									<div v-html="(serviceApplied.servicesAppliedService)?(serviceApplied.serviceNameShort||serviceApplied.servicesName||'Название услуги не указано'):`<b>Процедура не указана (резерв времени)<b>`">
									</div>
									<div class="C">{{(serviceApplied.idusers)?((serviceApplied.usersLastName)+' '+(serviceApplied.usersFirstName.charAt(0))+'.'):'Без специалиста'}}</div>
									<div><i v-on:click="deleteServiceApplied(serviceApplied);" v-if="serviceApplied.deleteable" class="far fa-times-circle" style="color: red; cursor: pointer;"></i></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div style="display: grid; grid-template-columns: repeat(2,auto); border: 0px solid red;">
					<div style=" display: contents;">
						<div>
							<h3 style="margin: 10px;">Абонементы</h3>
							<div style="display: inline-block;">
								<div v-if="contract.subscriptions && contract.subscriptions.reduce(function (a, b) {
									 if(b['remains']>0){
									 return a + b['remains'];
									 }
									 return a;
									 }, 0)>0" v-for="(contract,index) in data.client.contracts" style="border: 1px solid silver; margin: 10px; border-radius: 4px; padding: 5px 15px; background-color: white;">
									<div>Абонемент ({{contract.idf_sales}}) от {{contract.f_salesDateHuman}} 
										<!--Остаток средств ({{(contract.payments+contract.credits)-servicesAppliedTotal(contract.subscriptions)}})-->
									</div>
									<div class=" lightGrid" style="display: grid; grid-template-columns: 1fr auto auto; font-size: 1em; line-height: 1em;">
										<div style=" display: contents;">
											<div class="B C">Услуга</div>
											<div class="B C">Ост</div>
											<div class="B C"></div>
										</div>
										<div v-if="subscription.remains>0" v-for="(subscription, index) in contract.subscriptions" style=" display: contents;">
											<div style=" display: flex; align-items: center;">{{subscription.info.serviceNameShort||subscription.info.servicesName||'Название не указано'}} {{subscription.info.servicesDuration?` ${duration(subscription.info.servicesDuration)}`:''}}
												<!--{{subscription.info.f_salesContentPrice}}р.-->
											</div>
											<div style=" display: flex; align-items: center; justify-content: center;">{{subscription.remains}}</div>
											<div style=" display: flex; align-items: center; justify-content: center;"> 
												<input type="button" v-if="subscription.info.f_salesContentPrice<=(contract.payments+contract.credits)-servicesAppliedTotal(contract.subscriptions)" v-on:click="getAvailableTime(subscription);" value="Найти окна" style=" padding: 0px 10px; font-weight: normal; font-size: 0.9em; border-radius: 3px; box-shadow: 0px 0px 3px gray; background-color: lightgreen; ">
												<input type="button" v-else value="недостаточно средств" style=" padding: 0px 10px; font-weight: normal; font-size: 0.9em; border-radius: 3px; box-shadow: 0px 0px 3px gray; background-color: pink; ">
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>
						<div>

							<div style=" display: inline-block; text-align: center;">
								<h3 style=" margin: 10px;">Свободные окна</h3>
								<input type="date" min="<?= date("Y-m-d"); ?>" v-model="data.appointmentDate">
								<div v-for="(user,index) in data.personnel" v-if="user.pills.length>0" style="border: 1px solid silver; margin: 10px; border-radius: 4px; padding: 5px 5px; background-color: white;">
									<div>{{user.usersLastName}} {{user.usersFirstName}} (<b>{{mydate(user.usersTime*1000)}}</b>)</div>
									<div style="font-size: 0.8em; line-height: 0.8em;">
										<div v-if="pill.qty==0" v-for="(pill, index) in user.pills" style=" display: inline-block; border: 1px solid silver; margin: 4px; padding: 5px; border-radius: 10px;">
											<i class="fas fa-syringe" style="cursor: pointer; border: 1px solid silver; background-color: hsla(30,100%,60%,1); padding: 5px; border-radius: 5px;" v-on:click="makeAnAppointment(pill);"></i> 
											<span style=" font-size: 1.2em;">{{mytime(pill.time*1000)}}</span>
											<i class="fas fa-user-md" style="cursor: pointer; border: 1px solid silver; background-color: hsla(0,0%,80%,1); padding: 5px; border-radius: 5px;" v-on:click="makeAnAppointment({...pill,...{options:'noservice'}});"></i>

										</div>
									</div>
								</div>
								<div style="text-align: center;" v-if="loading">
									<img  src="/css/images/Infinity-1.1s-201px.svg"><br>
									Ищу уже...
								</div>
								<h3 v-if="!data.personnel.length && !loading" style=" margin: 10px;">
									Нет свободных окон
								</h3>

							</div>
							<div>
								<i class="fas fa-syringe" style="cursor: pointer; border: 1px solid silver; background-color: hsla(30,100%,60%,1); padding: 5px; border-radius: 5px;"></i> - Назначить процедуру<br> 
								<i class="fas fa-user-md" style="cursor: pointer; border: 1px solid silver; background-color: hsla(0,0%,80%,1); padding: 5px; border-radius: 5px;"></i> - Назначить только специалиста
							</div>
						</div>


					</div>
				</div>

				<textarea v-model="appRender" autocomplete="off" style="width: 100%; border-radius: 3px; padding: 4px; height: 400px; grid-column: span 2; display: none;"></textarea>
			</div>

			<script src="jsinclude/app.js" type="text/javascript"></script>

		</div>




	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
