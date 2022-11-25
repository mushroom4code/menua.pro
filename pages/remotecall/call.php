<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(35) || array_search_2d(32, ($_USER['positions'] ?? []), 'id')) {
    
}
if ($_USER['id'] != 176) {
//	die();
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
            right: 0px;
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

        }
        .noodle:hover{
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
        .hiddencomment {
            max-height: 0px !important;
            padding: 0px !important;
            overflow: hidden;
            transition: 0.2s ease-out all;
        }
        .unhiddencomment {
            max-height: 100px !important;
            padding: 0px !important;
            overflow: hidden;
            transition: 0.2s ease-out all;
        }
        .pink {
            background-color: pink;
        }
        .lemonchiffon {
            background-color: lemonchiffon;
        }

    </style>
    <?
    include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
    ?>
    <div style="vertical-align: top;" id="app">
        <div style="text-align: center;" >
            <div style=" display: inline-block; text-align: left;" >
                <div class="box neutral" style="vertical-align: top; display: inline-block;">
                    <div class="box-body" style="width: 460px;">
                        <h2>Запись</h2>
                        <div style="padding: 10px; background-color: #eaeaea; border-radius: 5px; margin: 10px auto;">
                            <h3>Клиент</h3>
                            <div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px; margin: 10px auto;">
                                <input type="hidden" id="idRCC_phone">
                                <? if (1) { ?>
                                    <div style="display: contents;">
                                        <div>Номер IP-телефона:</div>
                                        <div><input type="text" id="VOIP" v-model="call.VOIP" oninput="digon();" size="3" style="display: inline-block; text-align: center; width: auto;"> <button v-on:click="dialPhone" :style="{ backgroundColor: (client.clientsPhonesPhone.length==11?'lightgreen':'silver')}"><i class="fas fa-phone"></i></button></div>
                                    </div>
                                <? } ?>

                                <div style="display: contents;">
                                    <div>Номер телефона:</div>
                                    <div style=" display: grid; grid-template-columns: repeat(2,auto); grid-gap:10px;">
                                        <!-- -->
                                        <input type="text" id="clientPhoneNumber" v-on:keyup="checkInput" oninput="digon();"  v-model="client.clientsPhonesPhone" autocomplete="off" placeholder="89991112233">
                                        <button v-on:click="getPhoneInfo(1);" v-bind:style="{ backgroundColor: client.clientsPhonesPhone.length==11?'lightgreen':'silver'}"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                                <?
                                if (1) {//выбор базы данных
                                    $nobdcnt = (mfa(mysqlQuery("SELECT count(1) as `cnt` FROM `RCC_phones` WHERE isnull(`RCC_phonesBase`) AND isnull(`RCC_phonesClaimedBy`)"))['cnt'] ?? 0);
                                    ?>
                                    <div style="display: contents;">
                                        <div>Телефонная база:</div>
                                        <div style="display: grid; grid-template-columns: repeat(2,auto); grid-gap:10px;">
                                            <select v-model="RCC_phoneDatabase">
                                                <option value="">Любая</option>
                                                <? if ($nobdcnt) { ?><option value="null">Без указания БД (<?= $nobdcnt; ?>)</option><? } ?>

                                                <?
                                                foreach (query2array(mysqlQuery("SELECT *, RCC_phonesBasesFresh  AS `cnt` FROM `RCC_phonesBases`")) as $phoneBase) {
                                                    if (!$phoneBase['cnt']) {
                                                        continue;
                                                    }
                                                    ?>
                                                    <option value="<?= $phoneBase['idRCC_phonesBases']; ?>"><?= $phoneBase['RCC_phonesBasesNameShort']; ?> (<?= $phoneBase['cnt']; ?>)</option>
                                                    <?
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                <? }
                                ?>



                                <div style="display: contents;">
                                    <div>Фамилия:</div>
                                    <input type="text" id="clientsLName"  onkeydown="if (event.keyCode == 32) {
                                            qs('#clientsFName').focus();
                                            void(0);
                                            return false;
                                            }" v-model="client.clientsLName" v-on:input="resetClientId();" class="disableable" autocomplete="off">
                                </div>
                                <div style="display: contents;">
                                    <div>Имя:</div>
                                    <input type="text" id="clientsFName"  onkeydown="if (event.keyCode == 32) {
                                            qs('#clientsMName').focus();
                                            void(0);
                                            return false;
                                            }" v-model="client.clientsFName" v-on:input="resetClientId();" class="disableable" autocomplete="off">
                                </div>
                                <div style="display: contents;">
                                    <div>Отчество:</div>
                                    <input type="text" id="clientsMName"  onkeydown="if (event.keyCode == 32) {
                                            qs('#clientsBDay').focus();
                                            void(0);
                                            return false;
                                            }" v-model="client.clientsMName" v-on:input="resetClientId();" class="disableable" autocomplete="off">
                                </div>

                                <div style="display: contents;">
                                    <div>Дата рождения:</div>
                                    <input type="date" id="clientsBDay" v-model="client.clientsBDay" v-on:input="resetClientId();" class="disableable" autocomplete="off">
                                </div>


                                <div style="display: contents;">
                                    <div>Источник клиента:</div>
                                    <select :disabled="(!!client.idclients)" v-model="client.clientsSource" autocomplete="off">
                                        <option></option>
                                        <?
                                        foreach (query2array(mysqlQuery("SELECT * FROM `warehouse`.`clientsSources` ORDER BY `clientsSourcesDeleted`, `clientsSourcesName`")) as $source) {
                                            if ($source['clientsSourcesDeleted']) {
//												continue;
                                            }
                                            ?>
                                            <option <?= $source['clientsSourcesDeleted'] ? 'disabled' : ''; ?> value="<?= $source['idclientsSources'] ?>"><?= $source['clientsSourcesName'] ?></option>
                                            <?
                                        }
                                        ?>
                                    </select>
                                </div>


                            </div>
                        </div>

                        <div style="padding: 10px; background-color: #eaeaea; border-radius: 5px; margin: 10px auto;">
                            <h3>Услуги</h3>
                            <div style="">
                                <div class="lightGrid" style="margin: 10px auto; display: grid; grid-template-columns: repeat(6,auto);">
                                    <div style="display: contents;">
                                        <div class="C B">Дата</div>
                                        <div class="C B">Время</div>
                                        <div class="C B">Услуга</div>
                                        <div class="C B" style="cursor: pointer; padding: 0px 10px; display: flex; align-items: center; justify-content: center; color: gray;"><i class="fas fa-comment-alt"></i></div>
                                        <div class="C B">Цена</div>
                                        <div class="C B">&Cross;</div>
                                    </div>
                                    <!--[{date: '2021-07-02', time: '12:00', service: {id: 361, name: "Консультация", price: 500}, personnel: {id: 130, name: "Ломакина"}}],-->
                                    <div v-for="(appointment,index) in appointments" style="display: contents;">
                                        <div style=" display: flex; align-items: center; justify-content: center;">{{mydate(appointment.time)}}</div>
                                        <div style=" display: flex; align-items: center; justify-content: center;">{{time(appointment.time)}}</div>
                                        <div style=" display: flex; align-items: center;">{{(appointment.service||{}).name}}</div>
                                        <div class="C" v-on:click="expand(index);" style="cursor: pointer; padding: 0px 10px; display: flex; align-items: center; justify-content: center; color: gray;" v-bind:style="{ color: (appointment.comment.length>0)?'black':'lightgray'}" ><i class="fas fa-comment-alt"></i></div>
                                        <div style=" display: flex; align-items: center; justify-content: center;"><input style="width: 60px; text-align: center;" type="text" oninput="digon(); this.value=this.value==''?0:parseInt(this.value,10);" v-model="appointments[index].price"></div>

                                        <div class="C" style=" display: flex; align-items: center; justify-content: center;">
                                            <span style="color: red; cursor: pointer;" v-on:click="deleteAppointment(index);"><i class="far fa-times-circle"></i></span>
                                        </div>
                                        <div style="grid-column:span 6;" v-bind:class="[appointment.expand ? 'unhiddencomment' : 'hiddencomment']"><textarea  rows="3" style="resize: none; width: 100%;  border-radius: 5px; padding: 4px;" v-model="appointments[index].comment"></textarea></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="client.calls"  style="padding: 10px; background-color: white; border-radius: 5px; margin: 10px auto; max-height: 100px; overflow-y: auto;">
                            История звонков:
                            <div class="lightGrid" style="display: grid; grid-template-columns: repeat(2,auto); ">
                                <div v-for="call in client.calls" style=" display: contents;">
                                    <div>{{call.date}}<br>{{call.usersLastName}}</div>
                                    <div>{{call.OCC_callsCommentsComment}}</div>
                                </div>
                            </div>
                        </div>
                        <div style="padding: 10px; background-color: #eaeaea; border-radius: 5px; margin: 10px auto;">
                            <h3>Звонок</h3>
                            <div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px; margin: 10px auto;">
                                <div style="display: contents;">
                                    <div>Результат звонка:</div>
                                    <select autocomplete="off" v-model="call.result">
                                        <option value="">Результат звонка</option>
                                        <?
                                        foreach (query2array(mysqlQuery("SELECT * FROM `OCC_callTypes` WHERE NOT `idOCC_callTypes` in  (7,8) AND isnull(`OCC_callTypesDeleted`) ORDER BY `OCC_callTypesName`")) as $callType) {
                                            ?><option value="<?= $callType['idOCC_callTypes']; ?>"><?= $callType['OCC_callTypesName']; ?></option>
                                        <? } ?>
                                    </select>
                                </div>




                                <div v-if="call.result==='4'" id="dateRecall"  style="display: contents;">
                                    <div>Дата/время:</div>
                                    <div style="">
                                        <input style="display: inline-block; width: auto;" type="date" v-model="call.recallDate" min="<?= date("Y-m-d"); ?>" autocomplete="off">
                                        <input style="display: inline-block; width: auto;" type="time" v-model="call.recallTime" value="12:00" autocomplete="off">
                                    </div>
                                </div>


                                <div v-if="call.result==='5'" style="display: contents;">
                                    <div>Шаблон СМС</div>
                                    <div><select v-model="call.smsTemplate" onchange="window.localStorage.setItem('smsTemplate', this.value);">
                                            <option value="">Выбрать шаблон СМС</option>
                                            <option v-for="smstemplate in smstemplates[database]" :value="smstemplate.idsmsTemplates">{{smstemplate.smsTemplatesName}}</option>
                                            <option value="-1">Без СМС</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display: contents;">
                                    <div><b>О чём говорили с клиентом?</b></div>
                                    <textarea style="resize: none; height: 80px; border-radius: 5px; padding: 4px;"  id="clientComment" v-model="call.comment"></textarea>
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
                                    <div style="grid-column: 1/-1; text-align: center;"><input type="button" onclick="this.disabled = true;" id="saveCallBtn" value="Сохранить" v-on:click="saveVisit"></div>

                                </div>
                                <? if ($_USER['id'] == 176) { ?>
                                    <textarea v-model="appRender" autocomplete="off" style="width: 100%; border-radius: 3px; padding: 4px; height: 400px; grid-column: span 2;"></textarea>
                                <? } ?>
                                <!--
                                <textarea style="width: 100%; border-radius: 3px; padding: 4px; grid-column: span 2;">{ "call": { "result": "4", "recallDate": "2021-07-06", "comment": "комментарий к звонку" }, "client": { "idclients": 112, "idclientsPhones": 8090, "clientsPhonesPhone": 89052084769, "clientsLName": "Тестовый", "clientsFName": "Тест", "clientsMName": "Тестович", "clientsBDay": "1983-05-12" }, "appointments": [ { "time": 1627628400, "price": 1990, "comment": "Комментарий к КТ челюстей", "service": { "id": 187, "name": "КТ 2-х челюстей", "price": 1990, "typeName": "Стоматология" }, "personnel": 427, "expand": false }, { "time": 1627628400, "price": 0, "comment": "Комментарий к Анализам", "service": { "id": 87, "name": "Анализы", "price": 0, "typeName": "Диетология" }, "personnel": 680, "expand": false } ] }</textarea>-->
                            </div>
                        </div>
                    </div>
                </div>


                <div class="box neutral" style="vertical-align: top; display: inline-block;">
                    <div class="box-body" style="width: 460px;">
                        <h2>Услуги</h2>
                        <div style="display: grid; grid-template-columns: repeat(2,auto); grid-gap: 18px;">
                            <input type="date" style="width: auto;" v-model="date" v-on:change="scheduleRender();"  min="<?= date('Y-m-d'); ?>" value="<?= date('Y-m-d'); ?>"  autocomplete="off"  max="<?= date('Y-m-d', time() + 30 * 24 * 60 * 60); ?>" >
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
                                       " oninput="pointer = 0; suggest(this.value);">
                                <ul id="suggestions" class="suggestions" style="">
                                </ul>
                            </div>
                            <div style="display: contents;">
                                <div style=" text-align: right;">Мед.центр:</div>
                                <select autocomplete="off" v-model="database" :disabled="databaseLock">
                                    <option value="1">Моссковские ворота</option>
                                    <!--<option value="2">Чкаловская</option>-->
                                </select>
                            </div>
                        </div>



                        <div class="pool">
                            <div class="noodle" v-on:click="selectedService = item; selectedPrice = item.price; scheduleRender();" v-for="item in poolRender" v-bind:class="{ isActive:(selectedService==item)}">{{(item||{}).name}}{{duration((item||{}).servicesDuration)}} <span style="color: red; cursor: pointer;" v-on:click="deleteNoodle(item.id);"><i class="far fa-times-circle"></i></span></div>
                        </div>
                        <div v-if="selectedServicesApplied.length>0" style="text-align: center; padding: 20px; border: 1px solid red; font-weight: bold; background-color: pink; ">
                            На данное время уже записаны процедуры:
                            <div class="L" v-for="selectedServiceApplied, selectedServiceAppliedIndex in selectedServicesApplied">
                                {{selectedServiceAppliedIndex+1}}. {{selectedServiceApplied.servicesName}}
                            </div>

                        </div>
                        <div style="text-align: center; padding: 20px; ">
                            <input type="button" value="&DoubleLeftArrow; Добавить"  v-bind:style="{ backgroundColor: (selectedService&&selectedTimestamp)?'lightgreen':'pink'}" v-on:click="addAppointment();">
                            <input type="text" id="servicesAppliedPrice"  v-model="selectedPrice" style="display: inline-block; width: 120px; text-align: center;" placeholder="ЦЕНА">
                            <input type="button" value="Бесплатно" onclick="app.selectedPrice = 0;">
                        </div>
                        <div class="scheduleTable" v-if="loading">
                            <h3 style="text-align: center;">...загружаю</h3>
                        </div>
                        <div class="scheduleTable" v-if="loaded">
                            <div class="personnel" v-for="personnel in schedule">
                                <div class="head">{{personnel.usersLastName}} {{personnel.usersFirstName}} <span style="color: gray; font-size: 0.8em;">({{personnel.positions||''}})</span><span v-if="date!=personnel.usersDate" style="color: red; font-weight: bolder;"> ({{mydate(personnel.usersTime)}})</span></div>
                                <div class="time">
                                    <div class="pill noodle"
                                         v-for="pill in personnel.pills"
                                         v-on:click="
                                         selectedPersonnel=pill.personnel;
                                         selectedTimestamp=pill.time;
                                         selectedServicesApplied=pill.data
                                         "
                                         v-bind:class="{
                                         pink: pill.color=='pink',
                                         lemonchiffon: pill.color=='lemonchiffon',
                                         isActive: (selectedTimestamp==pill.time && selectedPersonnel==pill.personnel)}"
                                         v-bind:title="pill.qty>0?`На это время уже записано ${pill.qty} чел.`:''"
                                         >{{time(pill.time)}}{{pill.qty>0?` (${pill.qty})`:''}}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="schedule.length==0 && selectedService && loaded"><h3 style="text-align: center; background-color: white; border-radius: 20px; padding: 20px; color: red;">На {{mydate((new Date('2021-01-01')).getTime())}} и следующие 14 дней нет специалистов на эту процедуру.</h3></div>
                    </div>
                    <?
                    if (1) {
                        ?>
                        <div v-if="contract.subscriptions && contract.subscriptions.reduce(function (a, b) {
                             if(b['remains']>0){
                             return a + b['remains'];
                             }
                             return a;
                             }, 0)>0" v-for="(contract,index) in data.client.contracts" style="border: 1px solid silver; margin: 10px; border-radius: 4px; padding: 5px 15px; background-color: white;">
                            <div>Абонемент ({{contract.idf_sales}}) от {{contract.f_salesDateHuman}}
                                <!--Остаток средств ({{(contract.payments+contract.credits)-servicesAppliedTotal(contract.subscriptions)}})-->
                            </div>
                            <div class=" lightGrid" style="display: grid; grid-template-columns: 1fr auto; font-size: 1em; line-height: 1em;">
                                <div style=" display: contents;">
                                    <div class="B C">Услуга</div>
                                    <div class="B C">Ост</div>
                                    <!--									<div class="B C"></div>-->
                                </div>
                                <div v-if="subscription.remains>0" v-for="(subscription, index) in contract.subscriptions" style=" display: contents;">
                                    <div style=" display: flex; align-items: center;">{{subscription.info.serviceNameShort||subscription.info.servicesName||'Название не указано'}} {{subscription.info.servicesDuration?` ${duration(subscription.info.servicesDuration)}`:''}}
                                        <!--{{subscription.info.f_salesContentPrice}}р.-->
                                    </div>
                                    <div style=" display: flex; align-items: center; justify-content: center;">{{subscription.remains}}</div>
                                    <!--									<div style=" display: flex; align-items: center; justify-content: center;">
                                                                                                                                    <input type="button" v-if="subscription.info.f_salesContentPrice<=(contract.payments+contract.credits)-servicesAppliedTotal(contract.subscriptions)" v-on:click="getAvailableTime(subscription);" value="Найти окна" style=" padding: 0px 10px; font-weight: normal; font-size: 0.9em; border-radius: 3px; box-shadow: 0px 0px 3px gray; background-color: lightgreen; ">
                                                                                                                                    <input type="button" v-else value="недостаточно средств" style=" padding: 0px 10px; font-weight: normal; font-size: 0.9em; border-radius: 3px; box-shadow: 0px 0px 3px gray; background-color: pink; ">
                                                                                                                            </div>-->
                                </div>

                            </div>
                        </div>

                        <?
                    }
                    ?>

                </div>
            </div>
        </div>
    </div>
    <!--<script>callSrc.value = localStorage.getItem('callSrc');</script>-->

                                                                                                                                                                                                                                                        <!--<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>-->
    <script src="/sync/3rdparty/vue.min.js" type="text/javascript"></script>
    <script src="jsinclude/callfunctions.js" type="text/javascript"></script>


    <?
//	die("SELECT "
//			. " `idservices` as `id`,"
//			. " ifnull(`serviceNameShort`,`servicesName`) as `name`,"
//			. " ifnull((SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `id` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `id`)),0) as `price`,"
//			. "`servicesDuration` as `servicesDuration`,"
//			. "`servicesTypesName` as `typeName`"
//			. " FROM `services` LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) WHERE isnull(`servicesDeleted`) AND"
//			. " (SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`)>0 "
//			. "");
    /* --`serviceNameShort` */
    $services = query2array(mysqlQuery("WITH `prices` AS (
  SELECT *, ROW_NUMBER() OVER (PARTITION BY `servicesPricesService` ORDER BY `servicesPricesDate` DESC) AS `rowNumber`
  FROM `servicesPrices` where `servicesPricesType` = 1
)
SELECT `idservices` AS `id`,
 IFNULL(null, `servicesName`) AS `name`,
 `servicesPricesPrice` as `price`,
    `servicesDuration` AS `servicesDuration`,
    `servicesTypesName` AS `typeName`
 FROM `prices`
     LEFT JOIN `services` ON (`idservices` = `servicesPricesService`)
        LEFT JOIN    `servicesTypes` ON (`idservicesTypes` = `servicesType`)
  WHERE   ISNULL(`servicesDeleted`)
        AND (SELECT
            COUNT(1)
        FROM
            `positions2services`
        WHERE
            `positions2servicesService` = `idservices`) > 0 AND `rowNumber` = 1;"));
    if (($_GET['client'] ?? false)) {
        $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . mres($_GET['client']) . "'"));
        $phone = mfa(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`) ORDER BY `idclientsPhones` DESC"));
        ?>
        <script>
                                let clientLoad = <?= json_encode($client, 288); ?>;
                                let phoneLoad = <?= json_encode($phone, 288); ?>;
        </script>
        <?
    } else {
        ?>
        <script>
            let clientLoad = null;
            let phoneLoad = null;
        </script>
        <?
    }
    ?>
    <script>
        var services = <?= json_encode($services, 288); ?>;
        var date = '<?= date('Y-m-d'); ?>';
        function extractColumn(arr, column) {
        return arr.map(x => x[column]);
        }
    </script>
    <script src="jsinclude/app.js?<?= date("YmdHi", filemtime(dirname($_SERVER['SCRIPT_FILENAME']) . '/jsinclude/app.js')); ?>" type="text/javascript"></script>
    <script>
    <? if (1) { ?>
            app.apiendpoint = 'IO2.php';
    <? } ?>
    <? if ($_GET['call'] ?? false) { ?>
            getPhone(<?= $_GET['call']; ?>);
    <? } ?>
    <?
    $group = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers`= " . $_USER['id']))['usersGroup'];
    ?>
        app.smstemplates = <?=
    json_encode([
        '1' => query2array(mysqlQuery("SELECT `idsmsTemplates`,`smsTemplatesName` FROM `warehouse`.`smsTemplates` WHERE isnull(`smsTemplatesDeleted`) AND `smsTemplatesGroup` = '$group';")),
        '2' => query2array(mysqlQuery("SELECT `idsmsTemplates`,`smsTemplatesName` FROM `vita`.`smsTemplates` WHERE isnull(`smsTemplatesDeleted`) AND `smsTemplatesGroup` = '$group';"))
            ], 288);
    ?>;
    </script>

    <!--<a href="https://vita.menua.pro/sync/utils/voip/call3.php?src=160&dist=89052084769" target="_blank">Нажми меня нежно</a>-->
    <?
}
//printr($_QUERIES ?? '');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
