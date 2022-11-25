<?php
$load['title'] = $pageTitle = 'Оформление договора';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(26)) {
    
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<style>
    .suggestions {
        position: absolute;
        width: auto;
        background-color: white;
        border: none;
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

<?
if (!R(26)) {
    ?>E403R26<?
} else {
    ?>
    <? include 'menu.php'; ?>
    <? $GUID = GUID(); ?>
    <div id="vueapp" style="background-color: white; border-radius: 8px;">

        <div style=" padding: 20px;">
            <ul class="horisontalMenu">
                <li v-on:click="Tab('client');"><a :style="{backgroundColor: (tab=='client'?`#FFF`:`#f5f8f9`),top:(tab=='client'?`2px`:`0px`),boxShadow: (tab=='client'?`0px 0px 3px rgba(80,80,80,0.6)`:`0.3em 0.3em 7px rgba(122,122,122,0.5)`)}">Клиент <span v-if="clientOk">️✅</span><span v-else>❗</span></a></li>
                <li v-on:click="Tab('sale');"><a :style="{backgroundColor: (tab=='sale'?`#FFF`:`#f5f8f9`),top:(tab=='sale'?`2px`:`0px`),boxShadow: (tab=='sale'?`0px 0px 3px rgba(80,80,80,0.6)`:`0.3em 0.3em 7px rgba(122,122,122,0.5)`)}">Абонемент <span v-if="saleOk">️✅</span><span v-else>❗</span></a></li>
                <li v-on:click="Tab('payments');"><a :style="{backgroundColor: (tab=='payments'?`#FFF`:`#f5f8f9`),top:(tab=='payments'?`2px`:`0px`),boxShadow: (tab=='payments'?`0px 0px 3px rgba(80,80,80,0.6)`:`0.3em 0.3em 7px rgba(122,122,122,0.5)`)}">Оплата <span v-if="paymentsOk">️✅</span><span v-else>❗</span></a></li>
                <li v-on:click="Tab('personnel');"><a :style="{backgroundColor: (tab=='personnel'?`#FFF`:`#f5f8f9`),top:(tab=='personnel'?`2px`:`0px`),boxShadow: (tab=='personnel'?`0px 0px 3px rgba(80,80,80,0.6)`:`0.3em 0.3em 7px rgba(122,122,122,0.5)`)}">Персонал <span v-if="personnelOk">️✅</span><span v-else>❗</span></a></li>
                <li v-on:click="saveSale" v-if="clientOk && saleOk && paymentsOk && personnelOk"><a style=" background-color: lightgreen;">{{savingSale?(data.sale.id?'Сохраняю':'Оформляю'):(data.sale.id?'Сохранить':'Оформить')}}</a></li>
            </ul>
        </div>


        <div v-if="tab=='client'" class="box neutral">
            <div class="box-body">
                <h2>Клиент</h2>
                <div v-if="data.client.id" style="text-align: right;"><a href="<?= GR2(['client' => null]); ?>"><div style="border: 1px solid red; border-radius: 15px;  line-height: 16px; display: inline-block; padding: 3px 15px; margin: 5px; background: pink; font-weight: bold;">Сбросить</div></a></div>
                <div class="lightGrid" style="display: grid; grid-template-columns: repeat(3, auto);">
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Номер амб.карты</div>
                        <div><input type="text" :readonly="data.client.id"  v-model="data.client.aknum" data-searchby="clientsAKNum" v-on:keypress="searchClient"></div>
                        <div><span style="cursor: pointer;" data-searchby="clientsAKNum" v-on:click="searchClient">🔎</span></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;"><span>Номер телефона
                                        <!--<i class="fas fa-plus" style="color: green; cursor: pointer;"v-on:click="addPhone();"></i>-->
                            </span></div>
                        <div style=" background-color: white;">
                            <div style="display: grid; grid-template-columns: repeat(2, auto); grid-gap: 4px;">
                                <div style="display: contents;" v-for="(phone,index) in data.client.phones">
                                    <div>
                                        <input type="text" v-model="phone.number" :readonly="phone.id" data-searchby="clientsPhone"  v-on:keypress="searchClient">
                                    </div>
                                    <div class="C" style="display: flex; align-items: center; justify-content: center; padding: 0px 10px;">
    <!--										<i class="far fa-times-circle" style="color: red; cursor: pointer;"
                                               v-on:click="deletePhone(index);"
                                               v-if="data.client.phones.length>1"
                                               ></i>-->
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div><span style="cursor: pointer;" data-searchby="clientsPhone" v-on:click="searchClient">🔎</span></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Фамилия</div>
                        <div><input type="text" :readonly="data.client.id"  v-model="data.client.lname" data-searchby="name" v-on:keypress="searchClient"></div>
                        <div style=" grid-row: span 3; display: flex; align-items: center;"><span data-searchby="name" v-on:click="searchClient" style="cursor: pointer;">🔎</span></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Имя</div>
                        <div><input type="text" :readonly="data.client.id"  v-model="data.client.fname" data-searchby="name" v-on:keypress="searchClient"></div>

                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Отчество</div>
                        <div><input type="text" :readonly="data.client.id"  v-model="data.client.mname" data-searchby="name" v-on:keypress="searchClient"></div>
                    </div>

                    <div style="display: contents;" v-if="clients.length>1">
                        <div style="grid-column: span 3; padding: 10px;">
                            <h3 style="padding: 15px 10px; text-align: center;">Найдено более 1 клиента, укажите нужного!</h3>
                            <div class="lightGrid" style="display: grid; grid-template-columns: repeat(3, auto); outline: 2px solid red;">
                                <div style="display: contents; cursor: pointer;" v-on:click="selectAclient(index);" v-for="(client,index) in clients">
                                    <div>{{client.clientsAKNum||'без № карты'}}</div>
                                    <div>{{client.clientsLName}} {{client.clientsFName}} {{client.clientsMName}}</div>
                                    <div>{{client.clientsBDayTS?(`${date(client.clientsBDayTS,1)}р.`):'д.р. не указана'}}</div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Пол</div>
                        <div>
                            <input disabled type="radio" name="gender" id="genderF" value="0" v-model="data.client.gender" ><label for="genderF" style="font-size: 10pt;">Жен.</label>
                            <input disabled type="radio" name="gender" id="genderM" value="1" v-model="data.client.gender" ><label for="genderM" style="font-size: 10pt;">Муж.</label>
                        </div>
                        <div><span style="cursor: pointer;"></span></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Дата рождения</div>
                        <div><input readonly v-model="data.client.bday" type="date"></div>
                        <div></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Место рождения</div>
                        <div><input readonly v-model="data.client.passport.bplace" type="text"></div>
                        <div></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Паспорт №</div>
                        <div><input readonly v-model="data.client.passport.number" type="text"></div>
                        <div></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Выдан</div>
                        <div><input readonly v-model="data.client.passport.date" type="text"></div>
                        <div></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Код подразделения</div>
                        <div><input readonly v-model="data.client.passport.code"  type="text"></div>
                        <div></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Кем</div>
                        <div><textarea readonly v-model="data.client.passport.department"  type="text" style="resize: none; border-radius: 2px; width: 100%;"></textarea></div>
                        <div></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Зарегистрирован</div>
                        <div><textarea readonly v-model="data.client.passport.registration" style="resize: none; border-radius: 2px; width: 100%;"></textarea></div>
                        <div></div>
                    </div>
                    <div style="display: contents;">
                        <div style="display: flex; align-items: center;">Фактическое проживание</div>
                        <div><textarea readonly v-model="data.client.passport.residence" style="resize: none; border-radius: 2px; width: 100%;"></textarea></div>
                        <div></div>
                    </div>

                </div>

            </div>
        </div>

        <div v-if="tab=='sale'" class="box neutral" style="min-width: 1000px;">
            <div class="box-body">
                <h2>Абонемент</h2>
                <table  style="margin: 0px auto;">
                    <tr>
                        <td style="vertical-align: top; padding: 20px;">
                            <h4 style="margin: 10px;margin-top: 20px;">Данные абонемента:</h4>
                            <div style=" display: inline-block;">
                                <div class="lightGrid" style="display: grid; grid-template-columns: repeat(2, auto);">
                                    <div style="display: contents;">
                                        <div style="display: flex; align-items: center;">Дата <span v-if="!data.sale.date" style="color: red; font-weight: bold;">*</span></div>
                                        <div><input type="date" v-model="data.sale.date"></div>
                                    </div>
                                    <div style="display: contents;">
                                        <div style="display: flex; align-items: center;">Тип договора</div>
                                        <div> 
                                            <select id="saleType" v-model="data.sale.type" autocomplete="off">
                                                <option value=""></option>
                                                <?
                                                foreach (query2array(mysqlQuery("SELECT * FROM `f_salesTypes`")) as $saleType) {
                                                    if ($saleType['idf_salesTypes'] != 3) {
                                                        ?><option value="<?= $saleType['idf_salesTypes']; ?>"><?= $saleType['f_salesTypesName']; ?></option><?
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <span v-if="!data.sale.type" style="color: red; font-weight: bold;">*</span>
                                        </div>
                                    </div>
                                    <div style="display: contents;">
                                        <div style="display: flex; align-items: center;">Разовая процедура</div>
                                        <div>
                                            <input type="checkbox" id="issmall" v-model="data.sale.issmall" >
                                            <label for="issmall"></label>
                                        </div>
                                    </div>

                                    <div style="display: contents;">
                                        <div style="display: flex; align-items: center;">Юр.лицо <span v-if="!data.sale.entity" style="color: red; font-weight: bold;">*</span></div>
                                        <div>
                                            <select id="saleEntity" v-model="data.sale.entity" autocomplete="off">
                                                <option value=""></option>
                                                <? foreach (query2array(mysqlQuery("SELECT * FROM `entities` WHERE isnull(`entitiesDeleted`)")) as $saleEntity) {
                                                    ?><option value="<?= $saleEntity['identities']; ?>"><?= $saleEntity['entitiesName']; ?></option><? }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <h4 style="margin: 10px;margin-top: 20px;">Поиск процедур:</h4>
                            <div style=" display: inline-block; padding: 10px; background-color: white; border-radius: 10px;">
                                <div>
                                    <input type="text" v-model="servicesSearchText" v-on:keyup="searchServices"  autocomplete="off"  placeholder="Поиск" id="serviceSearch" style="display: inline; width: auto;">
                                    <ul class="suggestions">
                                        <li v-for="(suggestion,index) in suggestions" v-on:click="confirmSearch(index);">
                                            <span v-html="suggestion.servicesNameHighlighted"></span>
                                            <div v-bind:class="[{ 'pointed': suggestionsIndex==index }, 'mask']"></div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </td>
                        <td style=" vertical-align: top; padding: 20px;">
                            <h4 v-if="plans.length>0" style="margin: 10px;margin-top: 20px;">Планы лечения:</h4>
                            <div style=" display: inline-block;">
                                <div v-if="plans.length>0" class="lightGrid" style="display: grid;grid-template-columns: repeat(4, auto);">
                                    <div style="display: contents;">
                                        <div class="B C">Дата</div>
                                        <div class="B C">Номер</div>
                                        <div class="B C">Автор</div>
                                        <div class="B C">Услуг всего / <br>реализовано</div>
                                        <!--<div class="B C"></div>-->
                                    </div>
                                    <div v-if="plan.f_subscriptionsDraft.length" v-for="(plan,index) in plans" v-on:click="planView(index);" style="display: contents; cursor: pointer; ">
                                        <div class="C">{{date(plan.f_salesDraftDate,1)}}</div>
                                        <div class="C">{{plan.f_salesDraftNumber}}</div>
                                        <div class="C">{{plan.usersLastName}}</div>
                                        <div class="C">{{plan.f_subscriptionsDraft.length}} / {{plan.f_subscriptionsDraft.filter((subscriptionDraft=>{return subscriptionDraft.f_subscriptionsDraftSalesSale;})).length}}</div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
                <br>
                <h4 style="margin: 10px; margin-top: 20px;" v-if="plan.idf_salesDraft">План лечения №{{plan.idf_salesDraft}}:</h4>
                <div class="lightGrid" v-if="plan.idf_salesDraft" style="display: grid; grid-template-columns: auto 1fr repeat(4, auto);">
                    <div style="display: contents;">
                        <div class="B C">№<br>п.п.</div>
                        <div class="B C">Услуга</div>
                        <div class="B C">Кол-во<br>опл/всег</div>
                        <div class="B C">Цена</div>
                        <div class="B C">Стоимость</div>
                        <div class="B C"></div>
                    </div>
                    <div v-for="(subscriptionDraft, index) in plan.f_subscriptionsDraft" style="display: contents;" :style="{color: ((subscriptionDraft.qty-(subscriptionDraft.f_subscriptionsDraftSalesQty||0))==0?`gray`:`black`)}">
                        <div class="R">{{index+1}}.</div>
                        <div>{{subscriptionDraft.servicesName}}</div>
                        <div class="C">{{subscriptionDraft.f_subscriptionsDraftSalesQty||0}}/{{subscriptionDraft.qty}}</div>
                        <div class="R">{{subscriptionDraft.price}}р.</div>
                        <div class="R">{{(subscriptionDraft.qty-(subscriptionDraft.f_subscriptionsDraftSalesQty||0))*subscriptionDraft.price}}р.</div>
                        <div class="C">
                            <i v-if="(subscriptionDraft.qty-(subscriptionDraft.f_subscriptionsDraftSalesQty||0))==0" class="fas fa-check-circle" style="color: silver; box-shadow: 0px 0px 5px 1px hsla(0,0%,0%,0.3); border-radius: 50%;"></i>

                            <i v-if="(subscriptionDraft.qty-(subscriptionDraft.f_subscriptionsDraftSalesQty||0))!=0 && !data.sale.subscriptions.find(o => o.idf_subscriptionsDraft === subscriptionDraft.idf_subscriptionsDraft)" v-on:click="planServiceMove(index);" class="fas fa-arrow-circle-down" style="color: green; cursor: pointer;"></i>
                            <i v-if="(subscriptionDraft.qty-(subscriptionDraft.f_subscriptionsDraftSalesQty||0))!=0 && data.sale.subscriptions.find(o => o.idf_subscriptionsDraft === subscriptionDraft.idf_subscriptionsDraft)" class="fas fa-check-circle" style="color: greenyellow; box-shadow: 0px 0px 5px 1px hsla(0,0%,0%,0.3); border-radius: 50%;"></i>
                        </div>
                    </div>
                    <div style="display: contents;">
                        <div class="B R"  style="grid-column: span 4;">Итого<span v-if="planTotalRemains!=planTotal"> остаток</span>:</div>
                        <div class="B C"><span>{{nf(planTotalRemains)}}р.</div>
                        <div class="B C"><i v-on:click="planMove();" class="fas fa-arrow-circle-down" style="color: green; cursor: pointer; font-size: 1.2em;"></i></div>
                    </div>
                </div>




                <h4 v-if="data.client.servicesApplied.length>0" style="margin: 10px;margin-top: 20px;">Процедуры к оплате: <span v-if="servicesAppliedTotal" style="color: red; font-weight: bold;">*</span></h4>

                <div class="lightGrid" v-if="data.client.servicesApplied.length>0" style="display: grid;grid-template-columns: auto 1fr repeat(5, auto);">
                    <div style="display: contents;">
                        <div class="B C">№<br>п.п.</div>
                        <div class="B C">Услуга</div>
                        <div class="B C">Статус</div>
                        <div class="B C">Кол-во</div>
                        <div class="B C">Цена</div>
                        <div class="B C">Стоимость</div>
                        <div class="B C"></div>
                    </div>
                    <div v-for="(serviceApplied, index) in data.client.servicesApplied" style="display: contents;">
                        <div class="R">{{index+1}}.</div>
                        <div>{{serviceApplied.servicesName}}</div>
                        <div class="B C">
                            <span v-if="serviceApplied.servicesAppliedFineshed" style="color: green; font-size: 0.6em;">Завершена</span>
                            <span v-if="!serviceApplied.servicesAppliedFineshed" style="color: red; font-size: 0.6em;">Не завершена</span>
                        </div>
                        <div class="C">{{serviceApplied.qty}}</div>
                        <div class="R">{{serviceApplied.price}}р.</div>
                        <div class="R">{{serviceApplied.qty*serviceApplied.price}}р.</div>
                        <div class="C"><i v-on:click="serviceAppliedMove(index);" class="fas fa-arrow-circle-down" style="color: green; cursor: pointer;"></i></div>
                    </div>
                    <div style="display: contents;">
                        <div class="B R"  style="grid-column: span 5;">Итого:</div>
                        <div class="B C">{{nf(servicesAppliedTotal)}}р.</div>
                        <div class="B C"><i v-on:click="serviceAppliedMoveAll();" class="fas fa-arrow-circle-down" style="color: green; cursor: pointer; font-size: 1.2em;"></i></div>
                    </div>
                </div>





                <h4 style="margin: 10px;margin-top: 20px;">Состав абонемента:<span v-if="!data.sale.subscriptions.length" style="color: red; font-weight: bold;">*</span></h4>
                <div class="lightGrid"  style="display: grid;grid-template-columns: auto 1fr repeat(8, auto);">
                    <div style="display: contents;">
                        <div class="B C">№<br>п.п.</div>
                        <div class="B C">Наименование</div>
                        <div class="B C">Комментарий</div>
                        <div class="B C">Основание</div>
                        <div class="B C">кол-во</div>
                        <div class="B C">цена</div>
                        <div class="B C">НДС</div>
                        <div class="B C">стоимость</div>
                        <div class="B C">годен до</div>
                        <div class="B C"><i class="far fa-times-circle" title="удалить"></i></div>
                    </div>
                    <div v-for="(subscription, index) in data.sale.subscriptions" style="display: contents;">
                        <div class="R">{{index+1}}.</div>
                        <div>{{subscription.servicesName}}</div>
                        <div><input type="text" v-model="subscription.comment"></div>
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <span v-if="subscription.idservicesApplied">Пройдена</span>
                            <span v-if="subscription.idf_salesDraft">План №{{subscription.idf_salesDraft}}</span>

                        </div>
                        <div class="C" style="display: flex; align-items: center; justify-content: center;">
                            <input style="width: 5em; text-align: center;"  :readonly="subscription.idservicesApplied" type="number" :max="subscription.maxQty" v-model="subscription.qty" min="1"  @input="if(subscription.maxQty||false){subscription.qty=Math.max(1,Math.min(subscription.maxQty,event.target.value))}"> 
                        </div>
                        <div class="C" style="display: flex; align-items: center; justify-content: center;"><input style="width:  6em; text-align: center;" :readonly="subscription.idservicesApplied || subscription.idf_subscriptionsDraft" type="text" v-on:blur="checkMin(subscription)" v-model="subscription.price"></div>
                        <div class="C">{{subscription.servicesVat}}</div>
                        <div class="R" style="display: flex; align-items: center; justify-content: center;">{{subscription.qty*subscription.price}}р.</div>
                        <div style="display: flex; align-items: center; justify-content: center;"><input :readonly="subscription.idservicesApplied" v-model="subscription.validBefore" type="date" style="text-align: center;"></div>
                        <div class="C" style="display: flex; align-items: center; justify-content: center;"><i  v-on:click="deleteSubscription(index);" class="far fa-times-circle" style="color: red; cursor: pointer;"></i></div>
                    </div>

                    <div style="display: contents;">
                        <div style="grid-column: span 6; text-align: right; font-weight: bold;">Итого:</div>
                        <div class="C B">{{nf(saleTotal)}}р.</div>
                        <div></div>
                        <div></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="tab=='payments'" class="box neutral" style="min-width: 1000px;">
            <div class="box-body">
                <h2>Оплата</h2>
                <div style="padding: 5px;  display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto; background-color: white; padding: 15px 40px;">
                        <h3>К оплате {{nf(saleTotal)}}р.</h3>
                    </div>
                </div>
                <div style="padding: 5px;  display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto; padding: 15px 40px;" :style="{ backgroundColor: (paymentsTotal>0)?(( paymentsTotal==saleTotal)?`#EAFFEA`:`#FFEAEA`):`#FFF`}">
                        <h3>Платежей на сумму {{nf(paymentsTotal)}}р. <span v-if="paymentsTotal!=saleTotal">
                                {{paymentsTotal>saleTotal?(`излишек ${nf(paymentsTotal-saleTotal)}`):(`доплатить осталось ${saleTotal-paymentsTotal}`)}}р.
                            </span></h3>
                    </div>
                </div>
                <div style="padding: 5px;  display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto; padding: 15px 40px; background-color: white;">
                        <h4><input type="checkbox" id="advancePayment" v-model="data.payments.advancePayment"><label for="advancePayment">Авансовый платёж</label></h4>
                    </div>
                </div>
                <br>
                <br>


                <div style="padding: 5px;  display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto;" :style="{ backgroundColor: (data.payments.cash.enabled)?(( data.payments.cash.value<=0)?`#FFEAEA`:`#EAFFEA`):`#FFF`}">
                        <h4><input type="checkbox" id="cash" v-model="data.payments.cash.enabled"><label for="cash">Наличные</label></h4>
                        <div style="padding: 5px;"><input type="text" oninput="digon();" v-model="data.payments.cash.value" style="width: auto; text-align: right;"> р. <span v-if="data.payments.cash.enabled&&!data.payments.cash.value" style="color: red; font-weight: bold;">*</span></div>
                    </div>
                </div>
                <div style="padding: 5px;  display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto;" :style="{ backgroundColor: (data.payments.card.enabled)?(( !data.payments.card.value)?`#FFEAEA`:`#EAFFEA`):`#FFF`}">
                        <h4><input type="checkbox" id="card" v-model="data.payments.card.enabled"><label for="card">Оплата картой</label></h4>
                        <div style="padding: 5px;"><input type="text" oninput="digon();" v-model="data.payments.card.value" style="width: auto; text-align: right;"> р. <span v-if="data.payments.card.enabled&&!data.payments.card.value" style="color: red; font-weight: bold;">*</span></div>
                    </div>
                </div>
                <div style="padding: 5px;  display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto;" :style="{ backgroundColor: (data.payments.balance.enabled)?(( !data.payments.balance.value)?`#FFEAEA`:`#EAFFEA`):`#FFF`}">
                        <h4><input type="checkbox" id="balance" v-model="data.payments.balance.enabled"><label for="balance"><span v-if="data.payments.balance.enabled">Доступно {{(data.payments.balance.available-data.payments.balance.value)}}</span><span v-else>Списать с баланса</span></label></h4>

                        <div style="padding: 5px;"><input type="text" oninput="digon();" v-model="data.payments.balance.value" style="width: auto; text-align: right;"> р. <span v-if="data.payments.balance.enabled&&!data.payments.balance.value" style="color: red; font-weight: bold;">*</span></div>
                    </div>
                </div>
                <div style="padding: 5px; display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto;" :style="{ backgroundColor: (data.payments.installment.enabled)?(( data.payments.installment.value<=0)?`#FFEAEA`:`#EAFFEA`):`#FFF`}">
                        <h4><input type="checkbox" id="installment" v-model="data.payments.installment.enabled"><label for="installment">Рассрочка</label></h4>
                        <div style="padding: 5px;"><input type="text"  oninput="digon();" v-model="data.payments.installment.value" style="width: auto; text-align: right;"> р. <span v-if="data.payments.installment.enabled&&!data.payments.installment.value" style="color: red; font-weight: bold;">*</span></div>
                    </div>
                </div>
                <br>
                <br>
                <div v-for="(bank,index) in data.payments.banks" style="padding: 5px; display: inline-block;">
                    <div style="border: 1px solid silver; border-radius: 10px; width: auto;" :style="{ backgroundColor: (bank.enabled)?((!bank.idbank || !bank.agreementNumber || !bank.value || !bank.creditsMonthes)?`#FFEAEA`:`#EAFFEA`):`#FFF`}">
                        <h4 style="display: flex; align-items: center; width: 100%;"><input type="checkbox" :id="'bank'+index" v-model="bank.enabled"><label :for="'bank'+index">Банковский кредит</label>
                            <i v-if="index==0" class="fas fa-plus-square" style="float: right; font-size: 1.2em; color: green; cursor: pointer;" v-on:click="addBank"></i>&nbsp;
                            <i v-if="data.payments.banks.length>1" class="far fa-times-circle" style="float: right; font-size: 1.2em; color: red; cursor: pointer;" v-on:click="removeBank(index);"></i>

                        </h4>
                        <div style="padding: 10px;">
                            <div class="lightGrid" style="display: grid; grid-template-columns: repeat(2,auto);">
                                <div style=" display: contents;">
                                    <div style="display: flex; align-items: center;">Банк: <span v-if="bank.enabled&&!bank.idbank" style="color: red; font-weight: bold;">*</span></div>
                                    <div style="display: flex; align-items: center;">
                                        <select autocomplete="off" v-model="bank.idbank">
                                            <option value="">Выбрать Банк </option>
                                            <?
                                            foreach (query2array(mysqlQuery("SELECT * FROM `RS_banks` WHERE NOT isnull(`RS_banksShort`) ORDER BY `RS_banksShort`")) as $bank) {
                                                ?>
                                                <option value="<?= $bank['idRS_banks']; ?>"><?= $bank['RS_banksShort']; ?></option>
                                                <?
                                            }
                                            ?></select>
                                    </div>
                                </div><!-- comment -->
                                <div style=" display: contents;">
                                    <div style="display: flex; align-items: center;">№ договора: <span v-if="bank.enabled&&!bank.agreementNumber" style="color: red; font-weight: bold;">*</span></div>
                                    <div>
                                        <input type="text" v-model="bank.agreementNumber"  autocomplete="off">
                                    </div>
                                </div>

                                <div style=" display: contents;">
                                    <div style="display: flex; align-items: center;">Сумма: <span v-if="bank.enabled&&!bank.value" style="color: red; font-weight: bold;">*</span></div>
                                    <div>
                                        <input type="text" oninput="digon();" autocomplete="off" v-model="bank.value" style="width: auto; text-align: right;"> р.
                                    </div>
                                </div>
                                <div style=" display: contents;">
                                    <div style="display: flex; align-items: center;">Срок: <span v-if="bank.enabled&&!bank.creditsMonthes" style="color: red; font-weight: bold;">*</span></div>
                                    <div>
                                        <input type="text" autocomplete="off" oninput="digon();" v-model="bank.creditsMonthes"  size="4" style="width: auto; text-align: center;"> мес.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--				<br>
                                                <div style="padding: 5px; display: inline-block;">
                                                        <div style="border: 1px solid silver; border-radius: 10px; width: auto; padding: 10px; background-color: white; text-align: center;">
                                                                <h4>Отправить на кассу</h4>
                                                                <div style="padding: 5px;">
                                                                        <select v-model="data.payments.indexkkt">
                                                                                <option value=""></option>
                                                                                <option v-for="(kkt,index) in data.payments.kkts" :value="index">{{kkt.evotorKKTSname}}</option>
                                                                        </select>
                                                                </div>
                                                        </div>
                                                </div>				--> 
            </div>

        </div>



        <div v-if="tab=='personnel'" class="box neutral">
            <div class="box-body">
                <h2>Персонал</h2>
                <table>
                    <tr>
                        <? foreach (query2array(mysqlQuery("SELECT * FROM `f_roles` WHERE isnull(`f_rolesDeleted`)")) AS $role) {
                            ?>
                            <td style="vertical-align: top;"><h3 class="C" style=" margin: 10px;"><?= $role['f_rolesNameShort']; ?> <span v-if="data.personnel['<?= $role['idf_roles']; ?>'].required&&!data.personnel['<?= $role['idf_roles']; ?>'].users.length" style="color: red; font-weight: bold;">*</span></h3>
                                <div style=" text-align: center;">
                                    <input type="text" v-model="personnelSearch[<?= $role['idf_roles']; ?>]" v-on:keyup="searchUsers(event,<?= $role['idf_roles']; ?>)"  autocomplete="off"  placeholder="Поиск"  style="display: inline; width: auto;">
                                    <ul class="suggestions">
                                        <li v-for="(suggestion,index) in personnelSuggestions[<?= $role['idf_roles']; ?>]" v-on:click="confirmPersonnelSearch(index,<?= $role['idf_roles']; ?>);">
                                            <span>{{suggestion.usersLastName}} {{suggestion.usersFirstName}} {{suggestion.usersMiddleName}}</span>
                                            <div v-bind:class="[{ 'pointed': personnelSuggestionsIndex==index }, 'mask']"></div>
                                        </li>
                                    </ul>
                                </div>
                                <div style="padding: 10px;">
                                    <div v-if="data.personnel[<?= $role['idf_roles']; ?>].users.length>0" class="lightGrid" style="display: grid; grid-template-columns: 1fr auto;">
                                        <div v-for="(user,index) in data.personnel[<?= $role['idf_roles']; ?>].users" style="display: contents;">
                                            <div style="display: flex; align-items: center;">{{user.usersLastName}} {{user.usersFirstName}}</div>
                                            <div style="display: flex; align-items: center;">
                                                <i v-if="data.personnel[<?= $role['idf_roles']; ?>].editable"  v-on:click="removePersonnel(index,<?= $role['idf_roles']; ?>);" class="far fa-times-circle" style="color: red; cursor: pointer;"></i>
                                                <i v-else class="far fa-times-circle" style="color: silver;"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <?
                        }
                        ?>


                    </tr>
                </table>
            </div>
        </div>
        <br>
        <?
        if ($_USER['id'] == 176) {
            ?><textarea v-model="appRender" style="width: 600px; height: 600px;"></textarea><?
        }
        ?>

    </div>


    <script src="jsinclude/app.js?<?= RDS(); ?>" type="text/javascript"></script>
    <? if ($_GET['client'] ?? false) {
        ?>
        <script>
        app.fetchClient({searchby: 'idclients', idclients: <?= $_GET['client']; ?>});
        </script>
    <? } ?>

    <? if ($_GET['sale'] ?? false) {
        ?>
        <script>
            app.fetchSale({action: 'getSale', idf_sale: <?= $_GET['sale']; ?>});
        </script>
    <? } ?>


    <script>
        app.data.personnel['5'].users = [
            {
                "idusers": "<?= $_USER['id']; ?>",
                "f_salesGUID": "<?= $GUID; ?>",
                "usersLastName": "<?= $_USER['lname'] ?? ''; ?>",
                "usersFirstName": "<?= $_USER['fname'] ?? ''; ?>",
                "usersMiddleName": "<?= $_USER['mname'] ?? ''; ?>"
            }
        ];
    </script>



<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
