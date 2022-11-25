<?php
$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);

$clientsSQL = "SELECT "
        . " `credentialsLogin`,"
        . " `servicesAppliedPersonal`,"
        . " `servicesAppliedDate`,"
        . " `usersLastName`,"
        . " `usersFirstName`,"
        . " `usersMiddleName`,"
        . " `usersICQ`,"
        . " COUNT(1) as `AScount`,"
        . " `usersScheduleFrom`,"
        . " `usersScheduleTo`,"
        . " (SELECT COUNT(1)FROM `usersSchedule` WHERE `usersScheduleUser` = `idusers` AND `usersScheduleDate` = `servicesAppliedDate` AND NOT isnull(`usersScheduleFrom`) AND NOT isnull(`usersScheduleTo`)) AS `schedule`"
        . " FROM `servicesApplied`"
        . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
        . " LEFT JOIN `credentials` ON (`credentialsUser` = `idusers`)"
        . " LEFT JOIN `usersSchedule` ON (`usersScheduleUser` = `idusers` AND `usersScheduleDate` = `servicesAppliedDate` AND NOT isnull(`usersScheduleFrom`) AND NOT isnull(`usersScheduleTo`))"
        . "  "
        . " WHERE `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"
        . " AND isnull(`servicesAppliedDeleted`)"
        . " GROUP BY `servicesAppliedPersonal`";

$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
?>
<div class="scheduleTable" style="grid-template-columns: auto auto auto auto auto; background-color: white; border-radius: 8px;">
    <div>#</div>
    <div></div>
    <div></div>
    <div style="text-align: center;">Ф.И.О.</div>
    <div class="leftRightBorder">
        <?
        for ($time = mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 09:00:00"); $time <= mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 21:00:00"); $time += 60 * 60) {
            ?><span style="width: 50px;"><?= date("H:i", $time); ?></span><?
        }
        ?>
    </div>

    <?
    $clients = query2array(mysqlQuery($clientsSQL));
    if ($_USER['id'] == 176) {
//		printr($clients);
    }
    $n = 0;

    usort($clients, function ($a, $b) {
        return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
    });

    foreach ($clients as $client) {
        $n++;
        $filtered = array_filter($allServices, function ($element) {
            global $client;
            return ($element['servicesAppliedTimeBegin'] ?? $service['servicesAppliedStarted'] ?? 0) && ($element['servicesAppliedTimeEnd'] ?? $service['servicesAppliedFineshed'] ?? 0) && $client['servicesAppliedPersonal'] == $element['servicesAppliedPersonal'];
        });
        $countServices = count($filtered);

        usort($filtered, function ($a, $b) {
            return ($a['servicesAppliedStarted'] ?? $a['servicesAppliedTimeBegin']) <=> ($b['servicesAppliedStarted'] ?? $b['servicesAppliedTimeBegin']);
        });
        $tracks = [[]];
        foreach ($filtered as &$procedure) {
            $start = $procedure['servicesAppliedStarted'] ?? $procedure['servicesAppliedTimeBegin'];
            $T = 0;
            while (isset($tracks[$T]) && count(array_filter($tracks[$T], function ($element) {
                        global $start;
                        return (
                        mystrtotime($start) >= mystrtotime($element['servicesAppliedStarted'] ?? $element['servicesAppliedTimeBegin'])) &&
                        (
                        mystrtotime($start) < max(mystrtotime($element['servicesAppliedFineshed'] ?? $element['servicesAppliedTimeEnd']), mystrtotime($element['servicesAppliedStarted'] ?? $element['servicesAppliedTimeBegin']) + 60 * (15 / 25) * 30));
                    }))) {
                $T++;
            }
            $procedure['track'] = $T;
            $tracks[$T][] = $procedure;
        }

//		printr($filtered);
        ?>
        <div class="nameTimelineRow">
            <div style="
					  display: flex;
					  border-bottom: 1px solid silver;
					  justify-content: center; align-items: center; height: <?
            if (count($filtered)) {
                print max(array_column($filtered, 'track')) * 15 + 25;
            } else {
                print '0';
            }
            ?>px;"><?= $n; ?></div>
            <div style="width: 25px; border-bottom: 1px solid silver;"></div>
            <div style="border-bottom: 1px solid silver; color: <?= $countServices == $client['AScount'] ? 'gray' : 'red'; ?>;"><div style="top: 50%; transform: translateY(-50%);"><i class="fas fa-notes-medical"></i><span style="font-size: 0.7em; color: black;"> x<?= $client['AScount']; ?></span></div></div>
            <div style="border-bottom: 1px solid silver;"><div style="top: 50%; transform: translateY(-50%);"><?
                    if (!$client['schedule']) {
                        ?>
                        <span style="color: red;" title="Не установлена смена!">🛑</span>
                        <?
                    }
                    if ($_USER['id'] == 176) {
//						printr($client['usersScheduleFrom']);
//						printr($client['usersScheduleTo']);
                        //
                    }
                    ?><a href="/pages/reception/?personal=<?= $client['servicesAppliedPersonal']; ?>&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>" style="<? if (empty($client['credentialsLogin'])) { ?>color: red;<? } ?>"><?= $client['usersLastName'] ?? 'Без специалиста'; ?> <?= $client['usersFirstName']; ?> <?= $client['usersMiddleName']; ?></a></div></div>

            <div class="leftRightBorder" style="border-bottom: 1px solid silver;">
                <?
                for ($time = mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 09:00:00"); $time <= mystrtotime(($_GET['date'] ?? date("Y-m-d")) . " 21:59:59"); $time += 30 * 60) {
                    $curtime = $client['servicesAppliedDate'] . ' ' . date("H:i:s", $time);
                    ?><span style="width: 25px; <?= (strtotime($curtime) < strtotime($client['usersScheduleFrom']) || strtotime($curtime) >= strtotime($client['usersScheduleTo'])) ? ' background-color: hsla(0,0%,50%,0.1);' : ''; ?>">


                        <?
                        if ($_USER['id'] == 176) {
//							print strtotime($curtime) < strtotime($client['usersScheduleFrom'])||strtotime($curtime) >= strtotime($client['usersScheduleTo']);
                        }

                        $currentServices = array_filter($filtered, function ($element) use ($curtime) {
                            global $client;
                            return (
                            mystrtotime($element['servicesAppliedTimeBegin']) >= mystrtotime($curtime) &&
                            mystrtotime($element['servicesAppliedTimeBegin']) < mystrtotime($curtime) + 1800) &&
                            $client['servicesAppliedPersonal'] == $element['servicesAppliedPersonal'];
                        });

                        if (count($currentServices)) {
                            foreach ($currentServices as $service) {

                                $sStartTime = mystrtotime($service['servicesAppliedStarted'] ?? $service['servicesAppliedTimeBegin']);
                                $sDurationTime = mystrtotime($service['servicesAppliedFineshed'] ?? $service['servicesAppliedTimeEnd']) - $sStartTime;

                                /// COLOR
                                $bgcolor = 'hsla(202,50%,70%,0.6)';
                                if (!$service['servicesAppliedPersonal']) {
                                    $bgcolor = 'hsla(30,100%,60%,0.6)';
                                }
                                if ($service['servicesAppliedStarted']) {
                                    $bgcolor = 'hsla(106,100%,60%,0.6)';
                                }
                                if ($service['servicesAppliedFineshed']) {
                                    $bgcolor = 'hsla(30,0%,60%,0.6)';
                                }
                                if (mystrtotime($service['servicesAppliedTimeBegin']) < time() && !$service['servicesAppliedStarted']) {
                                    $bgcolor = 'hsla(0,100%,50%,0.5)';
                                }
                                $usersPositions = query2array(mysqlQuery("SELECT * FROM `usersPositions` WHERE `usersPositionsUser`='" . $service['servicesAppliedBy'] . "'"));
//															servicesAppliedIsNew



                                if (!$client['schedule']) {
                                    $border = 'border: 2px solid red;';
                                } else {
                                    $border = '';
                                }
                                /// COLOR
                                ?>

                                <div class="timelinePiece" onclick="this.classList.toggle('showTooltip');" style="
                                     font-size: 9px;
                                     /*overflow: hidden;*/
                                     display: flex;
                                     justify-content: center;
                                     align-items: center;
                                     top:<?= 13 + 15 * $service['track'] ?>px;
                                     <?= $border; ?>
                                     background-color: <?= $bgcolor; ?>;
                                     left: <?= (25 * ($sStartTime - mystrtotime($curtime)) / 1800) ?>px;
                                     width: <?= max(15, ($sDurationTime / 1800) * 25 - 1); ?>px;
                                     color: #333;
                                     "
                                     data-client="<?= $service['idclients']; ?>"
                                     ><?= (clientIsNew($service['idclients'], ($_GET['date'] ?? date("Y-m-d"))) ? '1' : ''); ?><?= ($service['servicesAppliedIsDiagnostic'] ? 'д' : ''); ?><?= ($service['servicesAppliedService'] == 361 ? 'к' : ''); ?><?= ((!round($service['servicesAppliedPrice']) && !$service['servicesAppliedContract']) ? 'п' : ''); ?><?= ((round($service['servicesAppliedPrice']) && !$service['servicesAppliedContract']) ? '<b style="color: red;">$</b>' : ''); ?><div class="tooltiptext">
                                             <? if ($service['servicesAppliedDeleted']) { ?>
                                            <b style="color: red;">Удалено <?= date("d.m.Y в H:i", mystrtotime($service['servicesAppliedDeleted'])) ?></b><br><? } ?>
                                        Клиент: <?= (clientIsNew($service['idclients'], ($_GET['date'] ?? date("Y-m-d")))) ? '<i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i>' : ''; ?><a href="/pages/reception/?client=<?= $service['idclients']; ?>&date=<?= ($_GET['date'] ?? date("Y-m-d")); ?>"><?= $service['clientsLName']; ?> <?= $service['clientsFName']; ?> <?= $service['clientsMName']; ?></a>

                                        <?
                                        $clientVisit = mfa(mysqlQuery("SELECT * "
                                                        . " FROM `clientsVisits`"
                                                        . " WHERE `clientsVisitsClient`='" . $service['idclients'] . "'"
                                                        . " AND `clientsVisitsTime`>='" . ($_GET['date'] ?? date("Y-m-d")) . " 00:00:00'"
                                                        . " AND `clientsVisitsTime`<='" . ($_GET['date'] ?? date("Y-m-d")) . " 23:59:59'"
                                                        . ""));
                                        $servicesAppliedTimeBeginSQL = "SELECT UNIX_TIMESTAMP(MIN(`servicesAppliedTimeBegin`)) AS `servicesAppliedTimeBeginTS` FROM `servicesApplied` WHERE `servicesAppliedClient` = '" . $service['idclients'] . "' AND `servicesAppliedDate`='" . ($_GET['date'] ?? date("Y-m-d")) . "' AND NOT isnull(`servicesAppliedTimeBegin`) AND isnull(`servicesAppliedDeleted`)";
                                        $servicesAppliedTimeBegin = mfa(mysqlQuery($servicesAppliedTimeBeginSQL));
                                        $color = 'blue';
                                        if (($clientVisit['clientsVisitsTime'] ?? null)) {
                                            $color = 'green';
                                        } else {

                                            if ($servicesAppliedTimeBegin['servicesAppliedTimeBeginTS'] > time()) {
                                                $color = 'gray';
                                            } else {
                                                $color = 'red';
                                            }
                                        }
                                        ?>

                                        <i class="fas fa-walking" title="<?= ($clientVisit['clientsVisitsTime'] ?? false) ? date("H:i", mystrtotime($clientVisit['clientsVisitsTime'])) : 'Визит не зафиксирован'; ?>" style="color: <?= $color; ?>;"></i>


                                        <br>
                                        Процедура: <?= $service['servicesName']; ?><br>
                                        Начало: <?= $service['servicesAppliedStarted'] ? (date("H:i", mystrtotime($service['servicesAppliedStarted'])) . " (по плану " . date("H:i", mystrtotime($service['servicesAppliedTimeBegin'])) . ")") : ('запланировано на ' . date("H:i", mystrtotime($service['servicesAppliedTimeBegin']))); ?><br>
                                        Окончание: <?= $service['servicesAppliedFineshed'] ? (date("H:i", mystrtotime($service['servicesAppliedFineshed'])) . " (по плану " . date("H:i", mystrtotime($service['servicesAppliedTimeEnd'])) . ")") : ('запланировано на ' . date("H:i", mystrtotime($service['servicesAppliedTimeEnd']))); ?><br>
                                    </div>
                                    <?
                                    if ($color == 'red') {
                                        ?>
                                        <i class="fas fa-walking" title="Визит не зафиксирован" style="color: red; position: absolute;top: 50%; left: 50%; transform: translate(-50%,-50%);"></i>
                                        <?
                                    }
                                    ?>
                                    <? if (0 && $service['servicesAppliedStarted']) {
                                        ?><i class="far fa-arrow-alt-circle-right" style="position: absolute; left: 1px; top: 1px; background-color: deepskyblue; border-radius: 50%;"></i><? }
                                    ?>
                                    <? if (0 && $service['servicesAppliedFineshed']) {
                                        ?><i class="far fa-check-circle" style="position: absolute; right: 1px; top: 1px; background-color: greenyellow; border-radius: 50%;"></i><? }
                                    ?>
                                    <? if (0 && !$service['servicesAppliedPersonal']) {
                                        ?><i class="fas fa-exclamation-triangle" style="position: absolute; top: 1px; transform: translateX(-50%); color: red;"></i><? }
                                    ?>
                                    <? if ($service['servicesAppliedDeleted']) {
                                        ?><i class="far fa-times-circle" style="position: absolute; top: 1.5px; transform: translateX(-50%); color: red; background-color: pink; border-radius: 50%;"></i><? }
                                    ?>

                                </div>
                            <? }
                            ?>

                            <?
                        }
                        ?>
                        <? if (time() >= mystrtotime($curtime) && time() < mystrtotime($curtime) + 30 * 60) {
                            ?><div class="timelineCursor" style=" left: <?= round(1 + ( 25 * ((time() - mystrtotime($curtime)) / (60 * 30)))); ?>px;"></div><? } ?>
                    </span><?
                }
                ?>

            </div>
        </div>
    <? } ?>
    <script>
        let clients = document.querySelectorAll(`[data-client]`);
        console.log(clients);

        function checkHover(event) {
            if (event.target.dataset.client) {
                clients.forEach(client => {
                    if (client.dataset.client === event.target.dataset.client) {
                        client.classList.add('hovered');
                    } else {
                        client.classList.remove('hovered');
                    }
                });
            }
        }

        document.addEventListener('mousemove', function (event) {
            checkHover(event);
        });

    </script>        

