<ul class="horisontalMenu">
    <? if (R(150)) { ?><li><a href="/pages/offlinecall/calls/cacheremains/">Остатки кэш</a></li><? } ?>
    <? if (R(98)) { ?><li><a href="/pages/offlinecall/calls/nocalls/">Без аб. Не обзвонены</a></li><? } ?>
    <? if (R(99)) { ?><li><a href="/pages/offlinecall/calls/abandoned/">Без аб.+дата звонка</a></li><? } ?>
    <? if (R(100)) { ?><li><a href="/pages/offlinecall/calls/abonlastcall/">Аб+дата звонка</a></li><? } ?>
    <? if (R(104)) { ?><li><a href="/pages/offlinecall/calls/?filter=today"<?= (($_GET['filter'] ?? '') == 'today') ? ' style="background-color: lightgreen;"' : '' ?>>Перезвонить сегодня</a></li><? } ?>
    <? if (R(105)) { ?><li><a href="/pages/offlinecall/calls/?filter=fresh"<?= (($_GET['filter'] ?? '') == 'fresh') ? ' style="background-color: lightgreen;"' : '' ?>>Свежие абонементы</a></li><? } ?>
    <? if (R(106)) { ?><li><a href="/pages/offlinecall/calls/?filter=bdays"<?= (($_GET['filter'] ?? '') == 'bdays') ? ' style="background-color: lightgreen;"' : '' ?>>Дни рождения</a></li><? } ?>
    <? if (R(107)) { ?><li><a href="/pages/offlinecall/calls/index.php?filter=diagnostics"<?= (($_GET['filter'] ?? '') == 'diagnostics') ? ' style="background-color: lightgreen;"' : '' ?>>Диагностики</a></li><? } ?>
    <li><a href="/pages/offlinecall/calls/nophones">Нет № телефона</a></li>
    <? if (R(199)) { ?><li><a href="/pages/offlinecall/calls/byclass/admin.php">По типу клиента (админ)</a></li><? } ?>
</ul>

<? if (R(200)) { ?>
    <ul class="horisontalMenu">
        <?
        foreach (query2array(mysqlQuery("SELECT * FROM `OCC_tasks`")) as $task) {
            ?>
            <li><a href="/pages/offlinecall/calls/byclass/?class=<?= $task['idOCC_tasks']; ?>"><?= $task['OCC_tasksName']; ?></a></li>
            <?
        }
        ?>
    </ul>
<? } ?>

<? if (1) { ?>
    <ul class="horisontalMenu" style="padding: 10px;">
        <li>Инфинити</li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=1">&GT;12</a></li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=2">12-9</a></li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=3">9-6</a></li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=4">6-3</a></li>
    </ul>
<? } ?>

<? if (1) { ?>
    <ul class="horisontalMenu" style="padding: 10px;">
        <li>Вита</li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=1&db=vita">&GT;12</a></li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=2&db=vita">12-9</a></li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=3&db=vita">9-6</a></li>
        <li><a href="/pages/offlinecall/calls/byvisitdate/?group=4&db=vita">6-3</a></li>
    </ul>
<? } ?>