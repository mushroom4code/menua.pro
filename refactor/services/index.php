<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html2/top.php';
?>

<style>
	.dragover > *{
		background-color: hsl(120, 100%, 90%);
	}
	.dragover ~ td {
		background-color: hsl(120, 100%, 90%);
	}
	.dragover {
		background-color: hsl(120, 100%, 90%);
	}
	.dragovern {
		background-color: pink;
	}
	.dragovern > * {
		background-color: pink;
	}
	.dragover ~ td {
		background-color: pink;
	}
	.dropToast {
		background-color: grey;
	}
	.dropToast button {
		color: dodgerblue;
	}
	.dropzone > a {
		pointer-events: none;
	}

	.deleted {
		background-color: pink;
	}
	
	.result-box > div {
		grid-row-start: 1;
		grid-column-start: 1;
	}


	.row > label.active {
		background-color: inherit !important;
	}

	.muted {
		opacity: 0.3;
	}

	.hideEditIcon {
		display: none;
	}

	.input-width {
		width: 95%;
		max-width: 500px;
	}

	#tree > div:hover {
		background-color: rgba(0, 0, 0, 0.1);
		transition: background-color 0.20s ease;
	}


	.changeSuccess {
		background-color: hsl(120, 100%, 90%);
	}

	@media only screen and (max-width: 1325px) {
		.services-tree {
			display: none;
		}
		div.content-box-with-border.services-table {
			width: 100%;
			margin-left: 0 !important;
		}
	}

	@media only screen and (max-width: 1520px) {
		div.content-box-with-border.services-table {
			min-width: 67% !important;
			/* margin-left: 0 !important; */
		}
	}
</style>
<!-- CONTENT -->




<div class="container" style="justify-content: left; margin-top: 2%; max-width: none; display: flex; width: 95%;" id="vueapp">
  <div class="content-box-with-border services-tree" style=" padding: 20px">
	 <h3 id="tree_label">
		Услуги
	 </h3>
	 <div id="tree">
		<div style="white-space: nowrap; cursor: pointer" v-for="li in flatTree" v-on:click.prevent="HPS({'service': li.idservices}); setPageStatus(1, li)" class="dropzone" draggable @dragstart="startDrag($event, li)" @drop="onDrop($event, li)"  @dragover.prevent @dragenter.prevent="onDragEnter($event)" @dragleave.prevent="onDragLeave($event)" :style="{'padding-left': li.level*10+'px'}">
            <a href="#"><i class="fas fa-folder" style="color: silver; margin-right: 5px;"></i>{{li.servicesName}} ({{li.count}})</a>
		</div>
		<div v-if="flatTree[0]" style="white-space: nowrap; cursor: pointer" v-on:click.prevent="HPS({'service': '0'}); setPageStatus(1, 0)" :style="{'padding-left': 0*10+'px'}">
            <a href="#" ><i class="fas fa-folder" style="color: silver; margin-right: 5px;"></i>Без категории ({{flatTree[0].countWithoutParent}})</a>
		</div>
	 </div>
  </div>

  <div class="content-box-with-border services-table" style="margin-left: 2%; min-width: 73%;">
	 <!-- Search -->
	 <div class="table-navbar">
		<div id="" style="background-color: rgb(239 235 235); padding: 5px; box-shadow: 0px 2px 4px -2px black;">
		  <!-- <? if (R(155)) { ?><div class="inline-block" style=" margin: 0 10px;"><a href="?add" <?= (isset($_GET['add'])) ? ' class="activeButton"' : ''; ?>>Добавить</a></div><? } ?> -->
		  <button v-if="activeService.length != '0'" data-target="modal1" class="btn modal-trigger">Добавить</button>
		  <!-- <div class="inline-block" style=" margin: 0 10px;"><a class="waves-effect waves-light btn modal-trigger" href="#modal1">Добавить</a></div> -->
		  <div class="inline-block" style="padding: 10px; background-color: white; border-radius: 3px; margin: 0 10px;">
			 <div>
				<a href="<?= GR2(['save' => true]); ?>"><i class="fas fa-save"></i></a>
			 </div>
		  </div>
		  <div class="search-container">
			 <div>
				<input 
				  data-debounce="500"
				  data-search="true"
				  data-restarget="result_ul"
				  data-callback="getBy_idservices"
				  data-callback2="renderSearchResults"
				  data-application="servicesApp"
				  class="validate" type="text"  autocomplete="off"  placeholder="Поиск" id="serviceSearch" style="display: inline; border: none; height: unset; margin: auto;">
				<ul id="result_ul" class="suggestions collection" style="margin: 0 0 0 -10px; position: absolute; background-color: rgb(239, 235, 235); max-width: 50%; box-shadow: 0px 0px 10px hsl(0deg 0% 0% / 30%); border: none;">

				</ul>
			 </div>
		  </div>
		  <div style="display: inline-block;">
		  	<input style="position: inherit; opacity: inherit; pointer-events: all;" type="checkbox" id="deleted" name="deleted" v-model="displayDeletedServices">
      		<label for="deleted">Отобразить удаленные услуги</label>
		  </div>

		  <div class="breadcrumbs" id="breadcrumbs">
		  <a class="breadcrumb cursor" v-for="breadcrumb in breadcrumbs" v-on:click.prevent="HPS({'service': breadcrumb.idservices}); setPageStatus(1, breadcrumb)">{{breadcrumb.servicesName}}</a>
		  </div>
		</div>
	 </div>

	 <!-- Loader -->
	 


		<!-- byId active service -->
		<div class="result-box" v-if="pageStatus === 2">
		  <div>
			 <h4>{{ activeService.servicesName }}</h4>
			 <form method="post">  <!-- action="/pages/refactor_services/index.php?service=" -->
			 	<div class="input-width">
				 	
					<div class="input-field">
						<div v-if="(initService.servicesName != activeService.servicesName) && !((initService.servicesName === null) && (activeService.servicesName === ''))" style="position: relative; width: 0; height: 0; left: 95%; top: 20px;">
							<i aria-hidden="true" class="fas fa-pencil-alt" style="position: absolute;"></i>
						</div>
						<input type="text" id="servicesName2" :data-initial="initService.servicesName" name="servicesName" @change="changeName($event)"  v-model="activeService.servicesName">
						
						<label :class="{active: activeService.servicesName}" for="servicesName2">Наименование услуги:</label>
					</div>
				</div>
				<div class="input-width">
					<div class="input-field">
						<div v-if="(initService.serviceNameShort != activeService.serviceNameShort) && !((initService.serviceNameShort === null) && (activeService.serviceNameShort === ''))" style="position: relative; width: 0; height: 0; left: 95%; top: 20px;">
							<i aria-hidden="true" class="fas fa-pencil-alt" style="position: absolute;"></i>
						</div>
						<input type="text" id="serviceNameShort" :data-initial="initService.serviceNameShort" name="serviceNameShort" @change="changeShortName($event)" v-model="activeService.serviceNameShort">
						<label :class="{active: activeService.serviceNameShort}" for="serviceNameShort">Сокращенное наименование услуги:</label>
					</div>
				</div>
				<div class="input-width">
					<div class="input-field">
						<div v-if="(initService.servicesURL != activeService.servicesURL) && !((initService.servicesURL === null) && (activeService.servicesURL === ''))" style="position: relative; width: 0; height: 0; left: 95%; top: 20px;">
							<i aria-hidden="true" class="fas fa-pencil-alt" style="position: absolute;"></i>
						</div>
						<input  type="text" id="servicesURL" :data-initial="initService.servicesURL" name="servicesURL" v-model="activeService.servicesURL">
						<label :class="{active: activeService.servicesURL}" for="servicesURL">Ссылка на услугу на сайте infinity:</label>
					</div>
				</div>
				<div class="input-width">
					<div class="input-field">
						<div v-if="(initService.serviceDescription != activeService.serviceDescription) && !((initService.serviceDescription === null) && (activeService.serviceDescription === ''))" style="position: relative; width: 0; height: 0; left: 95%; top: 20px;">
							<i aria-hidden="true" class="fas fa-pencil-alt" style="position: absolute;"></i>
						</div>
						<textarea class="materialize-textarea" type="text" id="serviceDescription" name="serviceDescription" v-model="activeService.serviceDescription"></textarea>
						<label :class="{active: activeService.serviceDescription}" for="serviceDescription">Описание услуги:</label>
					</div>
				</div>
				<div class="input-width" style="display: flex; gap: 6%;">
					<div style="min-width: 176px;">Проц.лист:</div>
					<div>
						<label style="color: inherit;">
							<input type="checkbox" name="servicesNewPlan" id="servicesNewPlan" value="1" :checked="activeService.servicesNewPlan == '1'">
							<span for="servicesNewPlan">Формировать план лечения</span>
						</label>
					</div>
				</div>
				<div class="input-width" style="display: flex; gap: 6%;">
					<div style="min-width: 176px;">Мотивационная система:</div>
					<div>
						<div v-for="servicesMotivation in servicesMotivations">
							<label style="color: inherit;">
								<input type="checkbox" :name="'servicesMotivations'+servicesMotivation.idservicesMotivations" :id="'servicesMotivations'+servicesMotivation.idservicesMotivations" value="1">
								<span :for="'servicesMotivations'+servicesMotivation.idservicesMotivations">{{servicesMotivation.servicesMotivationsName}}</span>
							</label>
						</div>
					</div>
				</div>
				<div class="input-width">
					<div>Тип услуги:</div> 
					<select @change="changeEntryType($event)" style="display: block" :value="activeService.servicesEntryType" id="servicesEntryTypeEdit" name="servicesEntryType" autocomplete="off">
						<option v-for="servicesEntryType in servicesEntryTypes" v-if="servicesEntryType.idservicesEntryTypes != '1'" :value="servicesEntryType.idservicesEntryTypes" >{{servicesEntryType.servicesEntryTypesName}}</option>
					</select>
				</div>
				<div class="input-width">
					<div>Аппарат:</div>
					<div>
						<select name="servicesEquipment" style="display: block;" :value="activeService.servicesEquipment" autocomplete="off">
							<option value="">Без аппарата</option>
							<option v-for="item in equipment" :value="item.idequipment">{{item.equipmentName}}</option>
						</select>
					</div>
				</div>
				<div class="input-width">
					<div>Направление:</div>
					<div>
						<select name="testsReferrals" style="display: block;" :value="activeService.servicesTestsReferral" autocomplete="off">
						<option value="">Без направления</option>
						<option v-for="item in testsReferrals" value="item.idtestsReferrals">{{item.testsReferralsName}}</option>
						</select>
					</div>
				</div>
				<div class="input-width">
					<div>Продолжительность (мин.):</div>
					<div>
						<div>
						<select name="servicesDuration" style="display: block;" :value="activeService.servicesDuration" autocomplete="off">
							<option></option>
							<? for ($time = 15; $time <= 300; $time += 15) {
								?>
								<option value="<?= $time; ?>"><?= floor($time / 60); ?>:<?= ($time % 60) ? ($time % 60) : '00' ?></option>
								<?
							}
							?>
						</select>
						</div>
					</div>
				</div>
				<div class="input-width">
					<div>НДС</div>
					<div>
						<div>
						<select name="servicesVat" style="display: block;" autocomplete="off" :value="activeService.servicesVat">
							<option value="">НДС не указан</option>
							<option value="0">без НДС</option>
							<option value="20">20%</option>
						</select>
						</div>
					</div>
				</div>

				<div id="prices" style="max-width: 500px; width: 95%; border: 1px solid silver; padding: 10px; background-color: #FFF; display: inline-block; margin: 20px 0px;">
					<div>Цены:</div>
					<div>
						<div><table>
							<tr>
								<td></td>
								<th>min</th><th>max</th></tr>
								<? if (true) { ?><tr>
								<tr>
									<td>По абонементу</td>
									<td>
										<input type="text" autocomplete="off" data-type="1" style="width: 70px;" onblur="priceDeHighlight(this)" oninput="digon(); priceHighlight(this);"
											:data-prevvalue="activeService.pricesList['1'] ? (activeService.pricesList['1'].servicesPricesPrice ?? '' ) : ''"
											:value="activeService.pricesList['1'] ? (activeService.pricesList['1'].servicesPricesPrice ?? '' ) : ''">
									</td>
									<td>
										<input type="text" autocomplete="off" data-type="2" style="width: 70px;" onblur="priceDeHighlight(this)" oninput="digon(); priceHighlight(this);"
											:data-prevvalue="activeService.pricesList['2'] ? (activeService.pricesList['2'].servicesPricesPrice ?? '' ) : ''"
											:value="activeService.pricesList['2'] ? (activeService.pricesList['2'].servicesPricesPrice ?? '' ) : ''">
									</td>
								</tr>
								<? } else {
								?>
								<tr>
									<td>По абонементу</td>
									<td>{{activeService.pricesList['1'] ? (activeService.pricesList['1'].servicesPricesPrice ?? 'Не Указана' ) : 'Не Указана'}}</td>
									<td>{{activeService.pricesList['2'] ? (activeService.pricesList['2'].servicesPricesPrice ?? 'Не Указана' ) : 'Не Указана'}}</td>
								</tr>
								<?
								}
								?>

								<? if (true) { ?>
								<tr>
									<td>Зп специалиста</td>
									<td>
										<input type="text" autocomplete="off" data-type="3"style="width: 70px;" onblur="priceDeHighlight(this)" oninput="digon(); priceHighlight(this);" 
											:data-prevvalue="activeService.pricesList['3'] ? (activeService.pricesList['3'].servicesPricesPrice ?? '' ) : ''" 
											:value="activeService.pricesList['3'] ? (activeService.pricesList['3'].servicesPricesPrice ?? '' ) : ''">
									</td>
									<td>
										<input type="text" autocomplete="off" data-type="4" style="width: 70px;" onblur="priceDeHighlight(this)" oninput="digon(); priceHighlight(this);"
											:data-prevvalue="activeService.pricesList['4'] ? (activeService.pricesList['4'].servicesPricesPrice ?? '' ) : ''"
											:value="activeService.pricesList['4'] ? (activeService.pricesList['4'].servicesPricesPrice ?? '' ) : ''">
									</td>
								</tr>
								<? } else { ?>
								<tr>
									<td>Зп специалиста</td>
									<td>{{activeService.pricesList['3'] ? (activeService.pricesList['3'].servicesPricesPrice ?? 'Не Указана' ) : 'Не Указана'}}</td>
									<td>{{activeService.pricesList['4'] ? (activeService.pricesList['4'].servicesPricesPrice ?? 'Не Указана' ) : 'Не Указана'}}</td>
								</tr>
								<? } ?>
							<tr>
								<td>Автоцена</td>
								<td>{{activeService.pricesList['5'] ? (activeService.pricesList['5'].servicesPricesPrice ?? 'Не Указана' ) : 'Не Указана'}}</td>
								<td>{{activeService.pricesList['6'] ? (activeService.pricesList['6'].servicesPricesPrice ?? 'Не Указана' ) : 'Не Указана'}}</td>
							</tr>
							<tr style="border-bottom: none;">
								<td>Аутсорс</td>
								<td>
									<input type="text" autocomplete="off" data-type="7" style="width: 70px;" onblur="priceDeHighlight(this)" oninput="digon(); priceHighlight(this);" 
										:data-prevvalue="activeService.pricesList['7'] ? (activeService.pricesList['7'].servicesPricesPrice ?? '' ) : ''" 
										:value="activeService.pricesList['7'] ? (activeService.pricesList['7'].servicesPricesPrice ?? '' ) : ''">
								</td>
								<td></td>
							</tr>

							<tr style="border-bottom: none;"><td colspan="3" class=" C"><input class="waves-effect waves-light btn" type="button" @click="priceSave(this);" value="Сохранить цену"></td></tr>
							</table>
						</div>
					</div>
	 			</div>
				

				<!-- <table class="highlight">
				  <thead>
					 <tr>
						<th>

						</th>
					 </tr>
				  </thead>

				  <tbody>
					 <tr>
						<td>

						</td>
					 </tr>
				  </tbody>
				</table> -->
			 </form>
		  </div>
		</div>

		<!-- all services byType -->
		<div class="result-box"  style="display: grid; grid-template-columns: 1 fr" v-if="pageStatus === 1">
			<div v-if="loadingImage" style="z-index: 10; width: 100%; margin-top: 4%">
				<img  src="/css/images/Infinity-1.1s-201px.svg" width="100%;">
	 		</div>
		  <div>
			 <table class="highlight">
			 	<caption style="margin-bottom: 5px;"><h5 style="margin-top: 0;">{{activeService.servicesName}}</h5></caption>
				<thead>
				  <tr>
					 <th>
						#
					 </th>
					 <th>
						id
					 </th>
					 <th style="width: 75%;">
						Название
					 </th>
					 <th>
						Цена
					 </th>
					 <th>
						З. П.
					 </th>
					 <th>
						Иконки
					 </th>
				  </tr>
				</thead>

				<tbody>
				  <tr class="cursor" v-bind:class="{deleted: service.deletedService, dropzonen: service.servicesEntryType != '1', dropzone: service.servicesEntryType == '1'}" v-for="(service, index) in sortedServices" draggable
  @dragstart="startDrag($event, service)" @drop="onDrop($event, service)" @dragover.prevent @dragenter.prevent="onDragEnter($event)" @dragleave.prevent="onDragLeave($event)" v-on:click="HPS({'service': service.idservices}); (service.servicesEntryType == 1) ? setPageStatus(1, service) : setPageStatus(2, service)">
					 <td >{{index+1}}</td>
					 <td >{{service.idservices}}]</td>
					 <td ><i v-if="service.servicesEntryType == '1'" class="fa fa-folder" style="margin-right: 4px; color: silver;"></i>{{service.servicesName}}</td>
					 <td  v-if="service.minPrice && service.maxPrice">{{service.minPrice}}...{{service.maxPrice}}</td>
					 <td  v-else-if="service.minPrice">{{service.minPrice}}</td>
					 <td  v-else-if="service.maxPrice">{{service.maxPrice}}</td>
					 <td  v-else></td>
					 <td  v-if="service.minCost && service.maxCost">{{service.minCost}}...{{service.maxCost}}</td>
					 <td  v-else-if="service.minCost">{{service.minCost}}</td>
					 <td  v-else-if="service.maxCost">{{service.maxCost}}</td>
					 <td  v-else></td>
					 <td v-if="service.servicesEntryType != '1'"  style="color: silver;">
					 	<i v-if="service.servicesEquipment" class="fas fa-bed" title="Есть аппарат"></i>
						<i v-if="service.servicesEquipmentQty" class="fas fa-cog" title="Есть аппарат или оборудование"></i>
						<a v-if="service.servicesURL" href="{{servicesURL}}" target="_blank" title="Ссылка на сайт"><i class="fab fa-internet-explorer"></i></a>
						<i v-if="service.servicesEquipped" style="color: green;" class="far fa-check-circle" title="Процедура укомплектована"></i>
						<i v-if="service.serviceDescription" class="fas fa-info-circle" title="Есть информацияя по процедуре"></i>
						<i v-if="service.personal == 0" style="color: orange;" class="fas fa-exclamation-triangle" title="Не назначен специалист"></i>
						<i v-if="service.servicesEntryType == null" class="far fa-question-circle" style="color: red;" title="Не назначен тип записи"></i>
						<i v-if="service.servicesDeleted" style="color: red;" class="fas fa-exclamation-triangle" title="Удалена"></i>
					</td>
					<td v-if="service.servicesEntryType == '1'"  style="color: silver;">
						<i v-if="service.servicesDeleted" style="color: red;" class="fas fa-exclamation-triangle" title="Удалена"></i>
					</td>
				</tr>
				<!-- <tr class="cursor dropzonen" v-for="(service, index) in renderArray" v-if="service.servicesEntryType != '1'" draggable
  @dragstart="startDrag($event, service)" @dragenter.prevent="onDragEnter($event)" @dragleave.prevent="onDragLeave($event)" v-on:click="HPS({'service': service.idservices}); service.servicesEntryType == 1 ? setPageStatus(1, service) : setPageStatus(2, service)">
					 <td class="dropzonen">{{index+1}}</td>
					 <td class="dropzonen">{{service.idservices}}]</td>
					 <td class="dropzonen"><i v-if="service.servicesEntryType == '1'" class="fa fa-folder" style="margin-right: 4px; color: silver;"></i>{{service.servicesName}}</td>
					 <td class="dropzonen" v-if="service.minPrice && service.maxPrice">{{service.minPrice}}...{{service.maxPrice}}</td>
					 <td class="dropzonen" v-else-if="service.maxPrice">{{service.maxPrice}}</td>
					 <td class="dropzonen" v-else-if="service.minPrice">{{service.minPrice}}</td>
					 <td class="dropzonen" v-else></td>
					 <td class="dropzonen" v-if="service.minCost && service.maxCost">{{service.minCost}}...{{service.maxCost}}</td>
					 <td class="dropzonen" v-else-if="service.minCost">{{service.minCost}}</td>
					 <td class="dropzonen" v-else-if="service.maxCost">{{service.maxCost}}</td>
					 <td class="dropzonen" v-else></td>
					 <td class="dropzonen" style="color: silver;">
						<i v-if="service.servicesEquipment" class="fas fa-bed" title="Есть аппарат"></i>
						<i v-if="service.servicesEquipmentQty" class="fas fa-cog" title="Есть аппарат или оборудование"></i>
						<a v-if="service.servicesURL" href="{{servicesURL}}" target="_blank" title="Ссылка на сайт"><i class="fab fa-internet-explorer"></i></a>
						<i v-if="service.servicesEquipped" style="color: green;" class="far fa-check-circle" title="Процедура укомплектована"></i>
						<i v-if="service.serviceDescription" class="fas fa-info-circle" title="Есть информацияя по процедуре"></i>
						<i v-if="service.personal == 0" style="color: orange;" class="fas fa-exclamation-triangle" title="Не назначен специалист"></i>
						<i v-if="service.servicesEntryType == null" class="far fa-question-circle" style="color: red;" title="Не назначен тип записи"></i>
					</td>
				</tr> -->
				</tbody>
			 </table>
		  </div>
		</div>
	 </div>
	 <div id="modal1" class="modal">
    <div class="modal-content">
		<div class="row">
			<h2>Создать услугу</h1>
		</div>
		<div class="row">
			<input type="text" id="servicesName" name="servicesName" required>
			<label for="servicesName">Название</label>
		</div>
    	<div class="row">
			<select style="display: block" id="servicesEntryType" name="servicesEntryType" autocomplete="off">
				<option value="1">Папка</option>
				<option value="2" selected>Услуга</option>
				<option value="3">Услуга сторонней организации</option>
				<option value="4">Товар</option>
			</select>
			<label for="servicesEntryType">Тип</label>
		</div>
		</form>
    </div>
    <div class="modal-footer">
		<button class="modal-close waves-effect waves-green btn-flat">Отменить</button>
      	<button type="submit" form="addServicesForm" class="waves-effect waves-green btn-flat" @click.prevent="addService(document.getElementById('servicesName').value, document.getElementById('servicesEntryType').value)">Сохранить</button>
    </div>
</div>
  </div>
</div>

<script src="/sync/3rdparty/vue.min.js" type="text/javascript"></script>
<script src="app.js?<?= date("YmdHi", filemtime(__DIR__ . '/app.js')); ?>" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var elems = document.querySelectorAll('.sidenav');
	var instances = M.Sidenav.init(elems, {});
});
document.addEventListener('DOMContentLoaded', function() {
    var elem = document.querySelector('.modal');
	var instance = M.Modal.init(elem);
	servicesApp.serviceAddModal = instance;
	// servicesApp.serviceAddModal.open();
	// console.log(instances);
	console.log(instance);
  });

function HPS(params = {}) {
  var url = new URL(window.location.href);
  for (let param in params) {
	 let value = params[param];
	 let name = param;
	 if (value === null || value === undefined || value === '') {
		url.searchParams.delete(name);
	 } else {
		url.searchParams.set(name, value);
	 }
  }
  history.pushState({}, '', `${url.pathname}?${url.searchParams.toString()}`);
}

function priceDeHighlight(elem) {
	console.log("priceDeHighlight " + elem);
	elem.style.backgroundColor = 'inherit';
}

function priceHighlight(elem) {
	 		  if (elem.dataset.prevvalue != elem.value) {
	 			 elem.style.backgroundColor = 'lightgoldenrodyellow';
	 		  } else {
	 			 elem.style.backgroundColor = 'white';
	 		  }
	 		}

window.onpopstate = (event) => {
  window.servicesApp.checkPageStatus();
}

</script>




<!-- CONTENT  ENDS-->
<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html2/bottom.php';
?>