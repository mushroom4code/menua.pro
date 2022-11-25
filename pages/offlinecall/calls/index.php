<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(47)) {
    
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(47)) {
    ?>E403R47<?
} else {
    include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/menu.php';
    $start = microtime(1);

    function printList($list = []) {
        global $_USER;
        $users = query2array(mysqlQuery("SELECT * FROM `users`"), 'idusers');
        $operators = array_values(array_unique(array_column($list, 'OCC_callsUser')));
//				ФИО	Телефон	Остатки проц	Дни с послед звонка	Посл визит 	Оператор
        foreach ($operators as $n => $operator) {
            if (($n ?? false) && ($operator ?? false)) {
                $operators[$n] = $users[$operator];
            } else {
                unset($operators[$n]);
            }
        }
        ?>
        Всего на сегодня было отложено <? print human_plural_form(count($list), ['звонок', 'звонка', 'звонков'], 1); ?>
        <div style="display: grid; grid-template-columns: auto auto auto auto auto auto auto auto auto auto " class="lightGrid">
            <div style="display: contents;">
                <div style="grid-row: span 2;" class="C B">#</div>
                <div style="grid-row: span 2;" class="C B">ФИО</div>
                <div style="grid-row: span 2;" class="C B">Дата<br>рождения</div>
                <div style="grid-row: span 2;" class="C B">Остатки<br>процедур</div>
                <div style="grid-column: span 4;" class="C B">Последний звонок</div>
                <div style="grid-row: span 2;" class="C B">Последний<br>визит</div>
                <div style="grid-row: span 2;" class="C B">Ближайшая<br>запись</div>
            </div>
            <div style="display: contents;">
                <div class="C B">Дата</div>
                <div class="C B">Результат</div>
                <div class="C B">Комментарий</div>
                <div class="C B">Оператор<br>
                    <select onchange="GR({operator: this.value});">
                        <option></option>
                        <option value="">Все</option>

                        <?
                        usort($operators, function ($a, $b) {
                            return $a['usersLastName'] <=> $b['usersLastName'];
                        });
                        foreach ($operators as $n => $operator) {
                            ?><option value="<?= $operator['idusers']; ?>" <?= ($_GET['operator'] ?? '') == $operator['idusers'] ? 'selected' : ''; ?>><?= $operator['usersLastName'] ?> <?= mb_substr($operator['usersFirstName'], 0, 1) ?>.</option><?
                        }
                        ?>
                    </select>
                </div>
            </div>



            <?
//(
//    [idclients] => 10675
//    [GUID] => 
//    [clientsLName] => Разуваева
//    [clientsFName] => Зоя
//    [clientsMName] => Тимофеевна
//    [clientsBDay] => 1955-04-24
//    [clientsAKNum] => 102543
//    [clientsAddedBy] => 288
//    [clientsAddedAt] => 2020-09-30 10:44:39
//    [clientsGender] => 0
//    [clientsCallerId] => 
//    [clientsCallerAdmin] => 
//    [clientsSource] => 5
//    [clientsOldSince] => 2020-09-23
//    [clientsControl] => 1
//    [clientsPassedAway] => 
//    [clientsPassedAwayBy] => 
//    [idclientsVisits] => 12178
//    [clientsVisitsClient] => 10675
//    [clientsVisitsTime] => 2020-11-13 15:58:19
//    [clientsVisitsPersonal] => 338
//    [idclientsVisitsMAX] => 12178
//    [idOCC_calls] => 9107
//    [OCC_callsType] => 8
//    [OCC_callsUser] => 228
//    [OCC_callTypesName] => Подтверждение
//    [clientsPhonesClient] => 10675
//    [OCC_callsTime] => 2020-11-15 12:51:04
//    [usersLastName] => Егорова
//    [usersFirstName] => Анастасия
//    [servicesAppliedClient] => 10675
//    [servicesAppliedTimeBegin] => 2020-11-16 14:30:00
//)


            $n = 0;
            usort($list, function ($a, $b) {
                global $_USER;
                if ($a['OCC_callsUser'] != $b['OCC_callsUser']) {
                    if ($a['OCC_callsUser'] == $_USER['id']) {
                        return -1;
                    } elseif ($b['OCC_callsUser'] == $_USER['id']) {
                        return 1;
                    } else {
                        return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
                    }
                }



                return $a['OCC_callsTime'] <=> $b['OCC_callsTime'];
            });

            if ($_USER['id'] == 176) {
//				printr($list[50]);
            }
            $nrow = 0;

            foreach ($list as $client) {
                if ($nrow >= 20) {
                    break;
                }
                $age = 0;
                if ($client['clientsBDay'] ?? false) {
                    $origin = date_create($client['clientsBDay']);
                    $target = date_create(date("Y-m-d"));
                    $interval = date_diff($origin, $target);
                    $age = $interval->format('%y');
                }
                if ($age < 18) {
                    $ageSign = '<i class="fas fa-exclamation-circle" style="color: red;"></i> ';
                } elseif ($age <= 35) {
                    $ageSign = '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> ';
                } else {
                    $ageSign = '';
                }

//						$lastVisit = mfa(mysqlQuery("SELECT * FROM `clientsVisits` WHERE `idclientsVisits` = (SELECT MAX(`idclientsVisits`) FROM `clientsVisits` WHERE `clientsVisitsClient`='" . $client['idclients'] . "')"));

                if (($client['servicesAppliedTimeBegin'] ?? false) && date("d.m.Y", strtotime($client['servicesAppliedTimeBegin'])) === date("d.m.Y")) {
                    continue;
                }

                if (($client['OCC_callsTime'] ?? false) && date("d.m.Y", strtotime($client['OCC_callsTime'])) === date("d.m.Y") && in_array($client['OCC_callsType'], [5, 8])) {
                    continue;
                }
                if (clientIsNew($client['idclients']) && $client['clientsSource'] != '13') {
                    continue;
                }


                $n++;
                $nrow++;
                ?>
                <div style="display: contents;">
                    <div class="C"><?= $n; ?></div>
                    <div style="white-space: nowrap;"><? if (clientIsNew($client['idclients'])) { ?><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i><? } ?><a href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>"><?= $client['clientsLName']; ?> <b><?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?></a></b></div>

                    <div class="C"><?= $client['clientsBDay'] ? (date("d.m.Y", strtotime($client['clientsBDay']))) : ''; ?><?= $ageSign; ?></div>
                    <div class="C"><?
                        $remains = $client['remainsLeft'] ?? getRemainsCountByClient($client['idclients']);
                        if ($remains) {
                            ?><?= $remains; ?><? } else { ?>
                            -	<? } ?></div>
                    <div class="C" title="<?= $client['idOCC_calls']; ?>"><?= $client['OCC_callsTime'] ? (date("d.m.Y H:i", strtotime($client['OCC_callsTime'])) . ' (' . round((time() - strtotime($client['OCC_callsTime'])) / (60 * 60 * 24)) . 'дн.)' ) : '-'; ?></div>
                    <div><?= $client['OCC_callTypesName'] ?? ''; ?></div>
                    <div><?= $client['OCC_callsCommentsComment'] ?? ''; ?></div>
                    <div><?= $client['usersLastName'] ?? ''; ?> <? if ($client['usersFirstName'] ?? false) { ?><?= mb_substr($client['usersFirstName'] ?? '', 0, 1); ?>.<? } ?></div>
                    <div class="C"><?= $client['clientsVisitsTime'] ? (date("d.m.Y", strtotime($client['clientsVisitsTime'])) . ' (' . round((time() - strtotime($client['clientsVisitsTime'])) / (60 * 60 * 24)) . 'дн.)' ) : '-'; ?></div>
                    <div class="C"><?= ($client['servicesAppliedTimeBegin'] ?? false) ? (date("d.m.Y", strtotime($client['servicesAppliedTimeBegin']))) : '-'; ?></div>

                </div>
                <?
            }
            ?>
        </div>
        <?
    }

//
    $lastVisitsSQL = "(SELECT * FROM  `clientsVisits` AS `a` INNER JOIN (SELECT  MAX(`idclientsVisits`) AS `idclientsVisitsMAX` FROM `clientsVisits` GROUP BY `clientsVisitsClient`) AS `b` ON (`a`.`idclientsVisits` = `b`.`idclientsVisitsMAX`))";
//	$lastCallSQL = "("
//			. "SELECT idOCC_calls,OCC_callsType,OCC_callsUser, OCC_callTypesName, clientsPhonesClient, OCC_callsTime,usersLastName,usersFirstName"
//			. " FROM `OCC_calls` AS `a`"
//			. " INNER JOIN (SELECT MAX(`idOCC_calls`) AS `idOCC_callsMAX` FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE (NOT `OCC_callsType` = 7) AND `OCC_callsTime`<=NOW() GROUP BY `clientsPhonesClient`) AS `b` ON (`a`.`idOCC_calls` = `b`.`idOCC_callsMAX`)"
//			. " LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`)"
//			. " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
//			. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
//			. ")";
//	
    $lastCallSQL = "("
            . "SELECT idOCC_calls,OCC_callsType,OCC_callsUser, OCC_callTypesName, clientsPhonesClient, OCC_callsTime,usersLastName,usersFirstName"
            . " FROM `OCC_calls` AS `a`"
            . " INNER JOIN (SELECT MAX(`idOCC_calls`) AS `OCC_callsMAX` FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE `OCC_callsType` <> '7' AND `OCC_callsTime`<=NOW() GROUP BY `clientsPhonesClient`) AS `b` ON (`a`.`idOCC_calls` = `b`.`OCC_callsMAX`)"
            . " LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`)"
            . " LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
            . " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
            . " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
            . " WHERE `OCC_callsType` <> '7')";

    $closestSASQL = "(SELECT `servicesAppliedClient`, MIN(`servicesAppliedTimeBegin`) as `servicesAppliedTimeBegin` FROM `servicesApplied` WHERE `servicesAppliedDate` >= CURDATE() AND isnull(`servicesAppliedDeleted`) GROUP BY `servicesAppliedClient`)";
//	printr($lastCallSQL);  
    ?>




    <div class="box neutral">
        <div class="box-body">
            <? include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/calls/callsmenu.php'; ?>
            <!--			<ul class="horisontalMenu">
                                            <li><a href="/pages/offlinecall/calls/?filter=nocalls"<?= (($_GET['filter'] ?? '') == 'nocalls') ? ' style="background-color: lightgreen;"' : '' ?>>Не обзвонены</a></li>
                                            <li><a href="/pages/offlinecall/calls/?filter=today"<?= (($_GET['filter'] ?? '') == 'today') ? ' style="background-color: lightgreen;"' : '' ?>>Перезвонить сегодня</a></li>
                                                    <li><a href="/pages/offlinecall/calls/?filter=confirm"<?= (($_GET['filter'] ?? '') == 'confirm') ? ' style="background-color: lightgreen;"' : '' ?>>Подтвержение</a></li>
                                            <li><a href="/pages/offlinecall/calls/?filter=fresh"<?= (($_GET['filter'] ?? '') == 'fresh') ? ' style="background-color: lightgreen;"' : '' ?>>Свежие абонементы</a></li>
                                            <li><a href="/pages/offlinecall/calls/?filter=bdays"<?= (($_GET['filter'] ?? '') == 'bdays') ? ' style="background-color: lightgreen;"' : '' ?>>Дни рождения</a></li>
                                            <li><a href="/pages/offlinecall/calls/index.php?filter=diagnostics"<?= (($_GET['filter'] ?? '') == 'diagnostics') ? ' style="background-color: lightgreen;"' : '' ?>>Диагностики</a></li>
                                    </ul>-->
            <?
//			printr($clients['bday']);



            if (($_GET['filter'] ?? '') == 'diagnostics') {
                $date = ($_GET['date'] ?? date("Y-m-d"));
//				$date = (date("Y-m-d"));
                ?>
                                                                                                                                                                                                                <!--<input type="date" style="width: auto;" onchange="GR({date: this.value});" value="<?= $date; ?>">-->
                <?
                $diagnosticsSQL = "SELECT * FROM `clients`"
                        . " LEFT JOIN $lastVisitsSQL AS LCVT ON (LCVT.clientsVisitsClient = idclients)"
                        . " LEFT JOIN $lastCallSQL AS LCT ON (LCT.clientsPhonesClient = idclients) "
                        . " LEFT JOIN $closestSASQL AS CSA ON (CSA.servicesAppliedClient = idclients)"
                        . " LEFT JOIN `OCC_callsClaims` AS СС ON (СС.OCC_callsClaimsClient = idclients) "
                        . " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`) "
                        . ""
                        . " WHERE `idclients` IN ("
                        . " SELECT `servicesAppliedClient` "
                        . " FROM `servicesApplied` "
                        . " LEFT JOIN `serviceMotivation` ON (`serviceMotivationService` = `servicesAppliedService`)"
                        . "WHERE "
//						. " (`servicesAppliedService` = '362' OR `serviceMotivationMotivation` = 5)"
                        . " NOT ISNULL(`servicesAppliedIsDiagnostic`)"
                        . " AND `servicesAppliedDate`= '" . mysqli_real_escape_string($link, $date) . "'"
                        . " AND isnull(`servicesAppliedDeleted`)"
                        . " GROUP BY `servicesAppliedClient`)"
                        . "";
//                printr($diagnosticsSQL);
                $clients = query2array(mysqlQuery($diagnosticsSQL));
                usort($clients, function ($a, $b) {
                    return mb_strtolower($a['servicesAppliedTimeBegin']) <=> mb_strtolower($b['servicesAppliedTimeBegin']);
                });
                ?>
                <div style="display: inline-block; padding: 10px;">
                    <input type="date" onchange="GR({date: this.value});" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>">
                </div>
                <div style="display: grid; grid-template-columns: auto auto auto auto auto auto auto auto auto auto " class="lightGrid">
                    <div style="display: contents;">
                        <div style="grid-row: span 2;" class="C B">#</div>
                        <div style="grid-row: span 2;" class="C B">ФИО</div>
                        <div style="grid-row: span 2;" class="C B">Дата<br>рождения</div>
                        <div style="grid-row: span 2;" class="C B">Остатки<br>процедур</div>
                        <div style="grid-column: span 4;" class="C B">Последний звонок</div>
                        <div style="grid-row: span 2;" class="C B">Последний<br>визит</div>
                        <div style="grid-row: span 2;" class="C B">Ближайшая<br>запись</div>
                    </div>
                    <div style="display: contents;">
                        <div class="C B">Дата</div>
                        <div class="C B">Результат</div>
                        <div class="C B">Комментарий</div>
                        <div class="C B">Оператор</div>
                    </div>
                    <?
                    foreach ($clients as $client) {
                        $age = 0;
                        if (clientIsNew($client['idclients'])) {
                            continue;
                        }
                        if ($client['clientsBDay'] ?? false) {
                            $origin = date_create($client['clientsBDay']);
                            $target = date_create(date("Y-m-d"));
                            $interval = date_diff($origin, $target);
                            $age = $interval->format('%y');
                        }
                        if ($age < 18) {
                            $ageSign = '<i class="fas fa-exclamation-circle" style="color: red;"></i> ';
                        } elseif ($age <= 36) {
                            $ageSign = '<i class="fas fa-exclamation-triangle" style="color: orange;"></i> ';
                        } else {
                            $ageSign = '';
                        }
                        ?>
                        <div style="display: contents;">
                            <div class="C"><? //$n;                                                                                                                                                                                            ?></div>
                            <div><? if (clientIsNew($client['idclients'])) { ?><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i><? } ?><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>"><?= $client['clientsLName']; ?> <b><?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?></a></b></div>

                            <div class="C"><?= $client['clientsBDay'] ? (date("d.m.Y", strtotime($client['clientsBDay']))) : ''; ?><?= $ageSign; ?></div>
                            <div class="C"><?
                                $remains = $client['remainsLeft'] ?? getRemainsCountByClient($client['idclients']);
                                if ($remains) {
                                    ?><?= $remains; ?><? } else { ?>
                                    -	<? } ?></div>
                            <div class="C" title="<?= $client['idOCC_calls']; ?>"><?= $client['OCC_callsTime'] ? (date("d.m.Y H:i", strtotime($client['OCC_callsTime'])) . ' (' . round((time() - strtotime($client['OCC_callsTime'])) / (60 * 60 * 24)) . 'дн.)' ) : '-'; ?></div>
                            <div><?= $client['OCC_callTypesName'] ?? ''; ?></div>
                            <div><?= $client['OCC_callsCommentsComment'] ?? ''; ?></div>

                            <div><?= $client['usersLastName'] ?? ''; ?> <? if ($client['usersFirstName'] ?? false) { ?><?= mb_substr($client['usersFirstName'] ?? '', 0, 1); ?>.<? } ?></div>
                            <div class="C"><?= $client['clientsVisitsTime'] ? (date("d.m.Y", strtotime($client['clientsVisitsTime'])) . ' (' . round((time() - strtotime($client['clientsVisitsTime'])) / (60 * 60 * 24)) . 'дн.)' ) : '-'; ?></div>
                            <div class="C"><?= ($client['servicesAppliedTimeBegin'] ?? false) ? (date("H:i", strtotime($client['servicesAppliedTimeBegin']))) : '-'; ?></div>

                        </div>
                        <?
                    }
                    ?>
                </div>

                <?
//				printr($clients);
                ?>



                <?
            }




            if (($_GET['filter'] ?? '') == 'confirm') {
                ?><h3 style="margin: 30px 0 10px 0;">Подтверждение</h3><?
                printList(query2array(mysqlQuery("SELECT * FROM `clients` "
                                        . " LEFT JOIN $lastVisitsSQL AS LCVT ON (LCVT.clientsVisitsClient = idclients)"
                                        . " LEFT JOIN $lastCallSQL AS LCT ON (LCT.clientsPhonesClient = idclients) "
                                        . " LEFT JOIN $closestSASQL AS CSA ON (CSA.servicesAppliedClient = idclients) "
                                        . " WHERE  `idclients` IN "
                                        . "(SELECT `servicesAppliedClient` FROM  `servicesApplied`"
                                        . " WHERE `servicesAppliedDate` = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND isnull(`servicesAppliedDeleted`)"
                                        . ")")));
            }



            if (($_GET['filter'] ?? '') == 'today') {
                ?><h3 style="margin: 30px 0 10px 0;">Перезвонить сегодня</h3><?
                $calls = query2array(mysqlQuery("SELECT *"
                                . " FROM `OCC_calls`"
                                . " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
                                . " LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE `OCC_callsType`='7' AND DATE(`OCC_callsTime`)<=curdate();"));
                if ($calls) {
                    $clients = query2array(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` IN (" . implode(',', array_unique(array_column($calls, 'clientsPhonesClient'))) . ")"), 'idclients');
//					printr($calls);
//					printr($clients);
                    $list = [];
                    foreach ($calls as $call) {
                        $list[$call['clientsPhonesClient']]['client'] = $clients[$call['clientsPhonesClient']];
                        $list[$call['clientsPhonesClient']]['calls'][] = $call;
                    }
                    usort($list, function ($a, $b) use ($_USER) {
                        $aCalls = array_filter($a['calls'], function ($call) use ($_USER) {
                            return $call['idusers'] == $_USER['id'];
                        });
                        $bCalls = array_filter($b['calls'], function ($call) use ($_USER) {
                            return $call['idusers'] == $_USER['id'];
                        });
                        if ((count($aCalls) > 0) <=> (count($bCalls) > 0)) {
                            return (count($bCalls) > 0) <=> (count($aCalls) > 0);
                        }

                        return min(array_column(($bCalls ? $bCalls : $b['calls']), 'OCC_callsTime')) <=> min(array_column(($aCalls ? $aCalls : $a['calls']), 'OCC_callsTime'));
                    });
//					printr($list[0]);
                    $n = 0;
                    ?>
                    <div style="display: inline-block">
                        <div class=" lightGrid" style="display: grid; grid-template-columns: repeat(3, auto);">
                            <?
                            foreach ($list as $row) {
                                $n++;
                                ?>
                                <div style=" display: contents;">
                                    <div><?= $n; ?></div>
                                    <div>
                                        <a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $row['client']['idclients']; ?>">
                                            <?= $row['client']['clientsLName']; ?>
                                            <?= $row['client']['clientsFName']; ?>
                                            <?= $row['client']['clientsMName']; ?>
                                        </a>
                                    </div>

                                    <div>
                                        <?
                                        foreach ($row['calls'] as $call) {
//										printr($call);
                                            ?>
                                            <div <?= date("Ymd", strtotime($call['OCC_callsTime'])) < date("Ymd") ? ' style="color: red;"' : '' ?>>
                                                <?= date("d.m.Y", strtotime($call['OCC_callsTime'])); ?>
                                                <?= $call['usersLastName']; ?>
                                                (<?= ($days = round((time() - strtotime($call['OCC_callsTime'])) / (60 * 60 * 24 ))) > 0 ? $days . 'дн.' : 'сегодня'; ?>)
                                            </div>
                                            <?
                                        }
                                        ?>
                                    </div>

                                </div>
                                <?
                            }
                            ?>
                        </div>
                    </div>
                    <?
//					printr($list);
                } else {
                    ?>НЕТ ОТЛОЖЕННЫХ ЗВОНКОВ<?
                }
            }



            if (($_GET['filter'] ?? '') == 'fresh') {
                ?><h3 style="margin: 30px 0 10px 0;">Свежие абонементы</h3><?
                printList(query2array(mysqlQuery("SELECT *,(SELECt `remainsQty` FROM `remains` WHERE `remainsClient` = idclients and `remainsDate` = (SELECT MAX(`remainsDate`) FROM `remains` WHERE `remainsClient` = idclients)) AS `remainsLeft` FROM clients "
                                        . " LEFT JOIN $lastVisitsSQL AS LCVT ON (LCVT.clientsVisitsClient = idclients) "
                                        . " LEFT JOIN $lastCallSQL AS LCT ON (LCT.clientsPhonesClient = idclients) "
                                        . " LEFT JOIN $closestSASQL AS CSA ON (CSA.servicesAppliedClient = idclients) "
                                        . " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`) "
                                        . "WHERE idclients IN (SELECT f_salesClient FROM f_sales WHERE f_salesDate > DATE_SUB(NOW(), INTERVAL 3 MONTH)) ")));
            }

            if (($_GET['filter'] ?? '') == 'bdays') {
                ?><h3 style="margin: 30px 0 10px 0;">Дни рождения сегодня/завтра</h3><?
                $query = "SELECT "
                        . "*"
                        . " ,DATE(`clientsBDay` + INTERVAL (YEAR(NOW()) - YEAR(`clientsBDay`)) YEAR) as `sortDate` "
                        . " FROM `clients`"
                        . " LEFT JOIN $lastVisitsSQL AS LCVT ON (LCVT.clientsVisitsClient = idclients) "
                        . " LEFT JOIN $lastCallSQL AS LCT ON (LCT.clientsPhonesClient = idclients) "
                        . " LEFT JOIN $closestSASQL AS CSA ON (CSA.servicesAppliedClient = idclients) "
                        . " LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`) "
                        . " WHERE"
                        . " (SELECT count(1) FROM `clientsPhones`  WHERE `clientsPhonesClient` = `idclients`)>0 "
                        . " AND DATE(`clientsBDay` + INTERVAL (YEAR(NOW()) - YEAR(`clientsBDay`)) YEAR)"
                        . " BETWEEN DATE(NOW() - INTERVAL 0 DAY) AND DATE(NOW() + INTERVAL 1 DAY)"
                        . " and not isnull(`clientsBDay`)"
                        . " and not isnull(`clientsOldSince`)"
                        . " ORDER BY `sortDate`,`clientsLName`,`clientsFName`;"
                        . " ";
//				printr($query);
                $clients = query2array(mysqlQuery($query));
//				printr($clients);
                printList($clients);
            }
            ?>


        </div>
    </div>



    <br>
    <br>
    <br>

    <br>
    <?
    print microtime(1) - $start;
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
