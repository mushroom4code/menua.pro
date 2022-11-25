<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/sync/3rdparty/materializecss/css/materialize.css">
        <link rel="stylesheet" href="/sync/3rdparty/materializecss/css/materialize_custom.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <script src="https://kit.fontawesome.com/02e7eb7723.js" crossorigin="anonymous"></script>
        <script src="/sync/js/basicFunctions.js?<?=date("YmdHi", filemtime($_SERVER['DOCUMENT_ROOT'] . '/sync/js/basicFunctions.js'));?>"></script>
        <title>Document</title>
    </head>

    <body>
        <div>
            <div class="maingrid">
                <ul id="slide-out" class="sidenav sidenav-fixed">
                    <li>
                        <div class="user-view">
                            <div class="background">
                                <img src="/css/images/bg2.jpg">
                            </div>
                            <div style="display: grid; grid-template-columns: 75px auto;">
                                <a href="#user">
                                    <div class="circle">
                                        <div class="circle" style="border: 2px solid #ddd;">

                                        </div>
                                    </div>
                                </a>
                                <a class="white-text" style="font-size: 1.3em;"><?=$_USER['lname'] ?? '';?> <?=$_USER['fname'] ?? '';?></a>
                            </div>
                        </div>
                    </li>
                    <? if (1 || R(78)) { ?><li><a href="/pages/mobile/"><i class="fas fa-mobile-alt"></i> Моб. офис</a></li><? } ?>
                    <? if (1 || R(8) || R(39)) { ?><li><a href="/pages/personal/"><i class="fas fa-user-tie"></i> Персонал</a></li><? } ?>
                    <? if (1 || R(43)) { ?><li><a href="/pages/positions/"><i class="fas fa-users-cog"></i> Должности</a></li><? } ?>
                    <? if (1 || R(35) || array_search_2d(32, ($_USER['positions'] ?? []), 'id')) { ?><li><a href="/pages/remotecall/"><i class="fas fa-house-user"></i> Маркетинг</a></li><? } ?>
                    <? if (1 || R(47)) { ?><li><a href="/pages/offlinecall/"><i class="fas fa-phone-alt ICOfirst"></i> Коллцентр</a></li><? } ?>
                    <? if (1 || R(52)) { ?><li><a href="/pages/service/"><i class="fas fa-phone-alt ICOsecondary"></i> Сервис</a></li><? } ?>
                    <? if (1 || R(32)) { ?><li><a href="/pages/clients/"><i class="far fa-address-book"></i> Клиенты</a></li><? } ?>
                    <? if (1 || R(9)) { ?><li><a href="/pages/warehouse/goods/"><i class="fas fa-dolly"></i> Склад</a></li><? } ?>
                    <? if (1 || R(42)) { ?><li><a href="/pages/reception/"><i class="fas fa-stream"></i> Регистратура</a></li><? } ?>

                    <? if (1 || R(138)) { ?><li><a href="/pages/recruiting/"><i class="far fa-address-card"></i> Рекрутинг</a></li><? } ?>

                    <? if (1 || R(45)) { ?><li><a href="/pages/proclist/"><i class="fas fa-clipboard-list"></i> Проц.лист</a></li><? } ?>
                    <? if (1 || R(45) || mfa(mysqlQuery("SELECT * FROM `f_planUsers` WHERE "
                                            . " `f_planUsersYear` = '" . date("Y") . "' "
                                            . " AND `f_planUsersMonth` = '" . date("n") . "' "
                                            . " AND `f_planUsersUser` = " . $_USER['id'] . " "
                                            . ""))) {
                    ?><li><a href="/pages/statistic/"><i class="fas fa-chart-line"></i> Статистика</a></li><? } ?>
                    <? if (1 || R(51)) { ?><li><a href="/pages/schedule/"><i class="fas fa-calendar-alt"></i> График работы</a></li><? } ?>
                    <? if (1 || R(10)) { ?><li><a href="/pages/suppliers/"><i class="fas fa-boxes"></i> Поставщики</a></li><? } ?>
                    <? if (1 || R(25)) { ?><li><a href="/pages/files/"><i class="far fa-file-word"></i> Документы</a></li><? } ?>
                    <? if (1 || R(46)) { ?><li><a href="/pages/analyzes/"><i class="fas fa-flask"></i> Обследования</a></li><? } ?>
                    <? if (1 || R(26)) { ?><li><a href="/pages/checkout/"><i class="fas fa-cash-register"></i> Оплата</a></li><? } ?>
                    <? if (1 || R(48)) { ?><li><a href="/pages/reports/"><i class="fas fa-chart-area"></i> Отчёты</a></li><? } ?>
                    <? if (1 || R(92)) { ?><li><a href="/pages/equipment/"><i class="fas fa-tools"></i> Оборудование</a></li><? } ?>

                    <? if (1 || R(171)) { ?><li><a href="/pages/price/"><i class="fas fa-dollar-sign"></i> Прайс</a></li><? } ?>

                    <? if (1 || R(28)) { ?><li><a href="/pages/services/"><i class="fas fa-book-medical"></i> Услуги</a></li><? } ?>
                    <? if (1 || R(191)) { ?><li><a href="/pages/payments/"><i class="fas fa-dollar-sign"></i> Начисления</a></li><? } ?>
                    <? if (1 || R(196)) { ?><li><a href="/pages/telegram/"><i class="fab fa-telegram-plane"></i> Телеграм БОТ</a></li><? } ?>
                    <? if (1 || (0 && R(28))) { ?><li><a href="/pages/services2/"><i class="fas fa-book-medical"></i> Услуги 2</a></li><? } ?>
                    <? if (1 || R(31)) { ?><li><a href="/pages/timetracking/"><i class="fas fa-stopwatch"></i> Учёт времени</a></li><? } ?>
                    <? if (1 || R(44)) { ?><li><a href="/pages/salesbook/">Книга продаж</a></li><? } ?>
                    <? if (1 || R(95)) { ?><li><a href="/pages/salesbook2/">Книга продаж МВ</a></li><? } ?>
                    <li class="divider"></li>
                    <? if (1 || R(146)) { ?><li><a href="/pages/cashflow/">Касса</a></li><? } ?>
                    <? if (1 || R(142)) { ?><li><a target="_blank" href="/pages/reports/servicessales/">Продажи услуг</a></li><? } ?>
                    <? if (in_array($_USER['id'], [176, 199])) { ?><li><a href="/pages/rs/">РС</a></li><? } ?>
                    <? if (in_array($_USER['id'], [176, 199, 135])) { ?><li><a href="/pages/banks/">Банки</a></li><? } ?>
                    <? if (1 || R(134)) { ?><li><a href="/pages/stat/">Стат</a></li><? } ?>
                    <? if (1 || R(136)) { ?><li><a href="/pages/bigone/">Сводная</a></li><? } ?>

                    <? if (1 || in_array($_USER['id'], [176, 199])) { ?><li class="divider"></li><? } ?>
                    <? if (1 || 0) { ?><li><a href="/pages/settings/">Настройки</a></li><? } ?>


                    <? if (1 || !(mfa(mysqlQuery("SELECT `usersTG` FROM `users` WHERE `idusers`='" . $_USER['id'] . "'"))['usersTG'] ?? false)) { ?><li style="white-space: nowrap;"><a href="/pages/tgconnect/"><i class="fab fa-telegram-plane"></i>&nbsp;Подключить</a></li><? } ?>

                    <li><a href="/?logout">Выход</a></li>
                </ul>
            </div>

            <main>
                <nav style="background-color: rgb(239 235 235);">
                    <div class="nav-wrapper">
                        <a href="#" data-target="slide-out" class="sidenav-trigger"><i class="material-icons black-text">menu</i></a>
                        <a href="#" class="brand-logo center black-text"><img class="logo_img" src="/css/images/logo2.png" alt=""></a>
                        <ul class="right logout-div">
                            <li><a class="black-text" style="font-size: 1.3em;"><i class="fa-solid fa-right-from-bracket"></i>Выйти</a></li>
                        </ul>
                    </div>
                </nav>
