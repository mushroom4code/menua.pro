<?php
$load['title'] = $pageTitle = 'Регистратура';
$PAGEstart = microtime(1);

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (1) {
    error_reporting(E_ALL); //
    ini_set('display_errors', 1);
}
if (!empty($_POST)) {
    foreach ($_POST as &$pdata) {
        $pdata = trim($pdata);
    }
}
$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);

$_TICK = [];

$allPersonal = query2array(mysqlQuery("SELECT * FROM `users`"), 'idusers');
if (isset($_GET['findClient'])) {

    $GET = [];
    if (isset($_POST['firstname']) && FSS(trim($_POST['firstname'])) !== '') {
        $GET[] = "`clientsFName` like '%" . FSS(trim($_POST['firstname'])) . "%'";
    }
    if (isset($_POST['lastname']) && FSS(trim($_POST['lastname'])) !== '') {
        $GET[] = "`clientsLName` like '%" . FSS(trim($_POST['lastname'])) . "%'";
    }
    if (isset($_POST['middlename']) && FSS(trim($_POST['middlename'])) !== '') {
        $GET[] = "`clientsMName` like '%" . FSS(trim($_POST['middlename'])) . "%'";
    }

    if (isset($_POST['acardnumber']) && FSS(trim($_POST['acardnumber'])) !== '') {
        $GET[] = "`clientsAKNum` = '" . FSS(trim($_POST['acardnumber'])) . "'";
    }

    if (isset($_POST['birthday']) && validateDate(FSS(trim($_POST['birthday'])))) {
        $GET[] = "`clientsBDay` = '" . FSS(trim($_POST['birthday'])) . "'";
    }

    if (count($GET)) {
        $selectSQL = "SELECT * FROM `clients` WHERE "
                . implode(" AND ", $GET);
//					print $selectSQL;
        $clients = query2array(mysqlQuery($selectSQL));
        if (count($clients) == 1) {
            header("Location: /pages/offlinecall/schedule.php?client=" . $clients[0]['idclients']);
//			print "ОСТАНЕТСЯ ТОЛЬКО ОДИН!";
            die();
        }
    }
}

if (!empty($_GET['finalise'])) {
    $serviceApplied = mfa(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `idservicesApplied` = '" . intval($_GET['finalise']) . "'"));

    $started = $serviceApplied['servicesAppliedStarted'] ?? $serviceApplied['servicesAppliedTimeBegin'] ?? date("Y-m-d H:i:s");
    $finished = $serviceApplied['servicesAppliedFineshed'] ?? $serviceApplied['servicesAppliedTimeEnd'] ?? date("Y-m-d H:i:s");
    if (mysqlQuery("UPDATE `servicesApplied` SET "
                    . "`servicesAppliedStarted` = '" . $started . "',"
                    . ($serviceApplied['servicesAppliedStartedBy'] ? '' : "`servicesAppliedStartedBy` = '" . $_USER['id'] . "',")
                    . ($serviceApplied['servicesAppliedFinishedBy'] ? '' : "`servicesAppliedFinishedBy` = '" . $_USER['id'] . "',")
                    . "`servicesAppliedFineshed` = '" . $finished . "'"
//					. "`servicesAppliedFinishedBy` = " . ($serviceApplied['servicesAppliedFineshed'] ? 'null' : $_USER['id']) . ""
                    . " WHERE `idservicesApplied` = '" . FSI($_GET['finalise']) . "'")) {
        header("Location: " . GR('finalise', null));
        die();
    } else {
        print 'error: ' . mysqli_error($link);
        die();
    }
}$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
if (!empty($_GET['deleteServicesApplied'])) {
    if (mysqlQuery("UPDATE `servicesApplied` SET "
                    . " `servicesAppliedDeleted` = NOW(), "
                    . " `servicesAppliedDeletedBy` = '" . $_USER['id'] . "'"
                    . " WHERE `idservicesApplied` = '" . FSI($_GET['deleteServicesApplied']) . "'")) {
        header("Location: " . GR('deleteServicesApplied', null));
        die();
    } else {
        print 'error: ' . mysqli_error($link);
        die();
    }
}$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
if (!empty($_GET['deleteServicesAppliedWithReason']) && !empty($_GET['reason'])) {
    if (mysqlQuery("UPDATE `servicesApplied` SET "
                    . " `servicesAppliedDeleted` = NOW(), "
                    . " `servicesAppliedDeleteReason` = '" . $_GET['reason'] . "', "
                    . " `servicesAppliedDeletedBy` = '" . $_USER['id'] . "'"
                    . " WHERE `idservicesApplied` = '" . FSI($_GET['deleteServicesAppliedWithReason']) . "'")) {

        foreach (getUsersByRights([133]) as $user) {
            if ($user['usersTG'] ?? false) {
                $procedure = mfa(mysqlQuery("SELECT * "
                                . "FROM `servicesApplied`"
                                . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                                . " LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`)"
                                . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)"
                                . " WHERE `idservicesApplied`='" . mres($_GET['deleteServicesAppliedWithReason']) . "'"));

                sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => '🗑️ ' . ($_USER['lname'] ?? '') . ' ' . ($_USER['fname'] ?? '') . ": удалена процедура \"" . ($procedure['servicesName'] ?? '??') . "\".\r\n" . ($procedure['servicesAppliedDate'] == date("Y-m-d") ? 'На сегодня' : ('на ' . date("d.m.Y", strtotime($procedure['servicesAppliedDate'])))) . "\r\nКлиент: " . (($procedure['clientsLName'] ?? '??') ) . ' ' . (($procedure['clientsFName'] ?? '??') ) . ' ' . (($procedure['clientsMName'] ?? '??') ) . ' ' . "\r\nПричина: " . ($procedure['daleteReasonsName'] ?? '??') . "\n" . 'http://' . SUBDOMEN . 'menua.pro/pages/offlinecall/schedule.php?client=' . $procedure['idclients']]);
            }
        }



        header("Location: " . GR('deleteServicesAppliedWithReason', null));
        die();
    } else {
        print 'error: ' . mysqli_error($link);
        die();
    }
}
$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);

if (R(42)) {
    if (isset($_GET['client']) && isset($_GET['add'])) {

        $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . FSI($_GET['client']) . "'"));
        if (!$client) {
            die('Клиент не существует');
        }
        if ($client['idclients'] && mysqlQuery("INSERT INTO `servicesApplied` SET "
                        . "`servicesAppliedService`='" . FSI($_GET['add']) . "',"
                        . "`servicesAppliedClient` = '" . FSI($_GET['client']) . "',"
                        . "`servicesAppliedBy` = '" . $_USER['id'] . "',"
                        . "`servicesAppliedByReal` = '" . $_USER['id'] . "',"
//						. "`servicesAppliedIsNew` = " . ($client['clientsIsNew'] ? '1' : 'null') . ","
                        . "`servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'")) {

            header("Location: /pages/reception/?client=" . FSI($_GET['client']) . '&date=' . ($_GET['date'] ?? date("Y-m-d")));
            die();
        } else {
            die(mysqli_error($link));
        }
    }

    if (isset($_GET['visit'])) {
        if (mysqlQuery("INSERT IGNORE INTO `clientsVisits`"
                        . " SET "
                        . "`clientsVisitsClient`='" . FSI($_GET['visit']) . "',"
                        . "`clientsVisitsPersonal` = '" . $_USER['id'] . "' "
                        . "on duplicate key update "
                        . "`clientsVisitsClient`='" . FSI($_GET['visit']) . "',"
                        . "`clientsVisitsPersonal` = '" . $_USER['id'] . "' "
                        . "")) {
            sendVisitsSales();
            $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` =  '" . FSI($_GET['visit']) . "'"));
            if (!$client['clientsOldSince']) {
                mysqlQuery("UPDATE `clients` SET `clientsOldSince`='" . date("Y-m-d") . "' WHERE `idclients`='" . $client['idclients'] . "'");
            }
            $servicesApplied = query2array(mysqlQuery("SELECT *"
                            . " FROM `servicesApplied`"
                            . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                            . " LEFT JOIN `servicesAppliedComments` ON (`servicesAppliedCommentsSA` = `servicesAppliedService`)"
                            . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedDeletedBy`)"
                            . " WHERE"
                            . " `servicesAppliedClient` = '" . FSI($_GET['visit']) . "' "
                            . " AND isnull(`servicesAppliedDeleted`)"
                            . " AND `servicesAppliedDate` = CURDATE()"));
            $_I = (!($client['clientsOldSince'] ?? false) && $client['clientsOldSince'] < date("Y-m-d"));

            if ($_I || in_array('362', array_column($servicesApplied, 'servicesAppliedService'))) {
                $message = '🔔';
                if ($_I) {
                    $message .= urldecode("1%EF%B8%8F%E2%83%A3") . ' ';
                }
                $message .= 'Клиент: ' . ($client['clientsLName'] ?? '') . ' ' . ($client['clientsFName'] ?? '') . ' ' . ($client['clientsMName'] ?? '') . "";
                if (count($servicesApplied)) {
                    uasort($servicesApplied, function ($a, $b) {
                        return $a['servicesAppliedTimeBegin'] <=> $b['servicesAppliedTimeBegin'];
                    });
                    $message .= ", на: ";
                    foreach ($servicesApplied as $serviceApplied) {
                        $comment = (($serviceApplied['servicesAppliedCommentText'] ?? false) ? ', ' . $serviceApplied['servicesAppliedCommentText'] : '');
                        $message .= "\r\n" . date("H:i", strtotime($serviceApplied['servicesAppliedTimeBegin'])) . ' - ' . $serviceApplied['servicesName'];

                        if ($serviceApplied['servicesAppliedContract']) {
                            $message .= " (по абонементу" . $comment . ")";
                        } else {
                            if (intval($serviceApplied['servicesAppliedPrice']) > 0) {
                                $message .= " (" . round($serviceApplied['servicesAppliedPrice']) . "р." . $comment . ")";
                            } else {
                                $message .= " (без оплаты" . $comment . ")";
                            }
                        }
                    }

                    $operatorsIds = implode(',', array_unique(array_column($servicesApplied, 'servicesAppliedBy')));
                    if ($operatorsIds) {
                        $operators = query2array(mysqlQuery("SELECT * FROM `users` WHERE `idusers` in(" . $operatorsIds . ")"));
                        $message .= "\r\nОператор";
                        if (count($operators) > 1) {
                            $message .= "ы";
                        }
                        $message .= ": ";
                        foreach ($operators as $operator) {
                            $message .= ( $operator['usersLastName'] ?? '') . ' ' . ( $operator['usersFirstName'] ?? '') . "\r\n";
                        }
                    }
                } else {
                    $message .= ". Без процедур.\r\n";
                }

                if ($_I) {
                    $users = getUsersByRights([114]);
                    foreach ($users as $user) {
                        if ($user['usersTG'] ?? false) {
                            sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => $message]);
                        }
                    }
                } else {
                    $users = getUsersByRights([116]);
                    foreach ($users as $user) {
                        if ($user['usersTG'] ?? false) {
                            sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => $message]);
                        }
                    }
                }
            }


            header("Location: " . GR('visit', null));
        } else {
            print 'error';
        }

        die();
    }
    $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
    /*
      [editidservicesApplied] => 7
      [servicesAppliedPersonal] =>
      [servicesAppliedTimeBegin] =>
      [servicesAppliedTimeEnd] =>
      [servicesAppliedIsFree] => 0
     */
//	printr($_POST);
//	printr($_GET);

    if (isset($_POST['editidservicesApplied'])) {
//idservicesApplied, servicesAppliedService, servicesAppliedClient, servicesAppliedBy, , servicesAppliedDate, servicesAppliedTimeBegin, servicesAppliedTimeEnd, servicesAppliedIsFree, servicesAppliedDeleted, servicesAppliedAt, servicesAppliedStarted, servicesAppliedFineshed

        $servicesAppliedTimeBegin = empty($_POST['servicesAppliedTimeBegin']) ? 'null' : "'" . date("Y-m-d H:i:s", mystrtotime(($_GET['date'] ?? date("Y-m-d")) . ' ' . FSS($_POST['servicesAppliedTimeBegin']) . ':00')) . "'";
        $servicesAppliedTimeEnd = empty($_POST['servicesAppliedTimeEnd']) ? 'null' : "'" . date("Y-m-d H:i:s", mystrtotime(($_GET['date'] ?? date("Y-m-d")) . ' ' . FSS($_POST['servicesAppliedTimeEnd']) . ':00')) . "'";

        $servicesAppliedSQL = ("UPDATE `servicesApplied` SET "
                . "`servicesAppliedPersonal` = " . (empty($_POST['servicesAppliedPersonal']) ? 'null' : FSI($_POST['servicesAppliedPersonal'])) . ","
                . "`servicesAppliedQty` = " . intval($_POST['servicesAppliedQty']) . ","
                . "`servicesAppliedTimeBegin` = " . $servicesAppliedTimeBegin . ","
                . "`servicesAppliedTimeEnd` = " . $servicesAppliedTimeEnd . ""
//				. "`servicesAppliedIsFree` = " . ($_POST['servicesAppliedIsFree'] == 1 ? 1 : 'null' ) . ""
                . " WHERE `idservicesApplied` = '" . FSI($_POST['editidservicesApplied']) . "'");

        if (mysqlQuery($servicesAppliedSQL)) {
            header("Location: " . GR('edit', null));
            print 'Перенаправление';
        } else {
            print mysqli_error($link);
        }
        die();
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>
    .hovered {
        border: 2px solid blue;
    }
    .hide {
        visibility: hidden;
    }
</style>
<?
if (!R(42)) {
    ?>E403R42<?
} else {
    $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
    include 'menu.php';
    ?>
                                                                                                                                                                                                                                <!--<script src="/sync/js/idle.js" type="text/javascript"></script>-->
    <?
    if (isset($_GET['client'])) {
        $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . FSI($_GET['client']) . "'"));
        if (!$client) {
            die('Клиент не существует');
        }
        $clientVisit = mfa(mysqlQuery("SELECT * "
                        . " FROM `clientsVisits`"
                        . " WHERE `clientsVisitsClient`='" . $client['idclients'] . "'"
                        . " AND `clientsVisitsTime`>='" . ($_GET['date'] ?? date("Y-m-d")) . " 00:00:00'"
                        . " AND `clientsVisitsTime`<='" . ($_GET['date'] ?? date("Y-m-d")) . " 23:59:59'"
                        . ""));
//		printr($clientVisit);
        ?>
        <?
        $services = query2array(mysqlQuery("SELECT "
                        . "`idservices` as `idservices`, "
                        . "`servicesName` as `name`,"
                        . "`servicesTypesName` as `typeName` "
                        . "FROM `services` "
                        . "LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) "
                        . "WHERE isnull(`servicesDeleted`)"));
        ?>
        <script>
                    let services = <?= json_encode($services, 288); ?>;</script>
        <?
        $subscriptions = query2array(mysqlQuery("SELECT * FROM"
                        . " `servicesApplied` "
                        . "LEFT JOIN `services` ON (`idservices`=`servicesAppliedService`)"
                        . "LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`)"
                        . "LEFT JOIN `users` ON (`idusers` = `servicesAppliedDeletedBy`)"
                        . "WHERE "
                        . " `servicesAppliedClient` = '" . $client['idclients'] . "'"
                        . " AND `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"
                        . (!R(86) ? " AND isnull(`servicesAppliedDeleted`)" : '')
                        . ""));
//		printr($subscriptions);
//		  [servicesAppliedTimeBegin] => 2020-06-01 19:30:00
//            [servicesAppliedStarted]
        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
        usort($subscriptions, function ($a, $b) {
            return ($a['servicesAppliedStarted'] ?? $a['servicesAppliedTimeBegin'] ?? null) <=> ($b['servicesAppliedStarted'] ?? $b['servicesAppliedTimeBegin'] ?? null);
        });
        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
        ?>
        <div class="box neutral">
            <div class="box-body">
                <h2><input type="date" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>" onchange="GETreloc('date', this.value);"></h2>
            </div>
            <div style="padding: 10px;">
                <div style="display: inline-block; font-size: 1.5em; padding: 10px; margin-bottom: 20px;">
                    <? if (R(47)) { ?><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>"><i class="fas fa-external-link-alt"></i></a><? } ?>
                    <?= clientIsNew($client['idclients'], ($_GET['date'] ?? date("Y-m-d"))) ? '<i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i>' : ''; ?>
                    <?= $client['clientsLName']; ?>
                    <?= $client['clientsFName']; ?>
                    <?= $client['clientsMName']; ?>
                    (№<?= $client['clientsAKNum']; ?>)
                    <a href="#" onclick="editField({moveFrom: '<?= ($_GET['date'] ?? date("Y-m-d")); ?>', servicesApplied:<?= json_encode(array_column($subscriptions, 'idservicesApplied'), 288); ?>});" >
                        <i class="fas fa-calendar-alt"></i>
                    </a>
                </div>

                <div style="margin: 5px 20px; display: grid; grid-template-columns: auto auto;">
                    <div>

                        <div style="display: grid; grid-template-columns: auto auto;">
                            <div style="align-self: center; position: relative;">
                            </div>
                            <div style="text-align: right;"><? if ($clientVisit) { ?>Визит зафиксирован в <?= date("H:i", mystrtotime($clientVisit['clientsVisitsTime'])); ?><? } else { ?><input type="button" onclick="GETreloc('visit',<?= $client['idclients']; ?>)" style="background-color: lightgreen;" value="Зафиксировать визит"><? } ?></div>
                        </div>


                    </div>
                    <div style="text-align: right;">
                        <div style="color: gray;">

                        </div>
                    </div>

                </div>
                <div style="padding: 20px;">

                    <?
//					printr($subscriptions);

                    $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                    foreach ($subscriptions as &$subscript) {
                        $personalSQL = "SELECT `idusers`, `usersLastName`, `usersFirstName`,`usersDeleted` "
                                . "FROM `positions2services` "
                                . "LEFT JOIN `usersPositions` ON (`usersPositionsPosition` = `positions2servicesPosition`) "
                                . "LEFT JOIN `users` ON (`idusers` = `usersPositionsUser`) "
                                . "WHERE `positions2servicesService` = '" . $subscript['idservices'] . "' "
                                . "AND (isnull(`usersDeleted`) OR `usersDeleted`> '" . ($_GET['date'] ?? date("Y-m-d")) . "')"
                                . "AND NOT isnull(`idusers`) "
                                . "GROUP BY `idusers`";

                        $petsonal = query2array(mysqlQuery($personalSQL));
                        usort($petsonal, function ($a, $b) {
                            return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
                        });
//						printr($petsonal);
                        $subscript['allpersonal'] = $petsonal;
                        foreach ($petsonal as $petson) {
                            if (isset($petson['usersDeleted'])) {
                                continue;
                            }
                            $subscript['personal'] = ($subscript['personal'] ?? '') .
                                    '<option value="' . $petson['idusers'] . '"' . ($subscript['servicesAppliedPersonal'] == $petson['idusers'] ? ' selected' : '') . '>' . $petson['usersLastName'] . ' ' . mb_substr($petson['usersFirstName'], 0, 1) . '.</option>';
                        }
                    }
                    ?>


                    <?
                    if (isset($_GET['edit'])) {
                        ?>
                        <form action="<?= GR('edit', null); ?>" method="post">
                            <input type="hidden" name="editidservicesApplied" value="<?= $_GET['edit']; ?>">
                        <? } ?>

                        <div style="display: grid; grid-template-columns: auto auto auto auto auto auto 50px 50px 50px auto auto ; grid-gap: 5px;">
                            <div style="display: contents;">
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;">Процедура</div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;">Количество</div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;">Специалист</div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;">Начало<br>План (факт)</div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;">Окончание<br>План (факт)</div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;"><i class="fas fa-gift" title="Подарочная процедура"></i></div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;"></div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center;"></div>
                                <div style="padding: 12px 10px; font-weight: bold; text-align: center; color: red;"></div>
                                <div></div>
                                <div></div>
                            </div>
                            <div id="subscriptions" style="display: contents;">
                                <?
                                $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);

                                function getRemains($data) {
//									$data['servicesAppliedContract'];
//									$data['servicesAppliedService'];
//									$data['servicesAppliedPrice'];
                                    $serviceAppliedSum = mfa(mysqlQuery("SELECT"
                                                            . " sum(servicesAppliedQty) as `summ`"
                                                            . " FROM `servicesApplied`"
                                                            . " WHERE `servicesAppliedContract`='" . $data['servicesAppliedContract'] . "'"
                                                            . " AND `servicesAppliedService`='" . $data['servicesAppliedService'] . "'"
                                                            . " AND `servicesAppliedPrice`='" . $data['servicesAppliedPrice'] . "'"
                                                            . " AND isnull(`servicesAppliedDeleted`)"))['summ'];

//									$subscriptions = mfa(mysqlQuery("SELECT * FROM `f_subscriptions` WHERE"
////											. " `idf_subscriptions` = '" . $idf_subscriptions . "'"
//													. " `f_subscriptionsContract`='" . $data['servicesAppliedContract'] . "'"
//													. " AND `f_salesContentService`='" . $data['servicesAppliedService'] . "'"
//													. " AND `f_salesContentPrice`'" . $data['servicesAppliedPrice'] . "'"
//													. ""));

                                    $subscriptionsSum = mfa(mysqlQuery("SELECT sum(`f_salesContentQty`) as `summ` FROM `f_subscriptions` WHERE"
                                                            . " `f_subscriptionsContract` = '" . $data['servicesAppliedContract'] . "'"
                                                            . "AND `f_salesContentService` = '" . $data['servicesAppliedService'] . "'"
                                                            . "AND `f_salesContentPrice`  = '" . $data['servicesAppliedPrice'] . "'"))['summ'];

                                    return($subscriptionsSum - $serviceAppliedSum);
//									return 0;
                                }

                                $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                foreach ($subscriptions as $subscription) {
                                    ?>

                                    <div style="display: contents;
                                    <?= $subscription['servicesAppliedDeleted'] ? ' color: silver;' : ''; ?>
                                    <?= ($_GET['highlight'] ?? false) == $subscription['idservicesApplied'] ? 'font-weight: bolder; color: blue;' : ''; ?>

                                         ">
                                        <div style="">
                                            <? if (R(169) && $subscription['servicesVat'] === null) { ?><a target="_blank" style="color: red;" href="https://menua.pro/pages/services/index.php?service=<?= $subscription['idservices']; ?>"><? } ?>
                                            <?= $subscription['servicesName'] ?? 'Не указана'; ?>
                                                <? if (R(169) && $subscription['servicesVat'] === null) { ?></a><? } ?>
                                        </div>
                                        <div class="C">

                                            <?
                                            if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) {

                                                $remainsAmnt = getRemains(
                                                                [
                                                                    'servicesAppliedContract' => $subscription['servicesAppliedContract'],
                                                                    'servicesAppliedService' => $subscription['servicesAppliedService'],
                                                                    'servicesAppliedPrice' => $subscription['servicesAppliedPrice']
                                                                ]
                                                        ) + $subscription['servicesAppliedQty'];
                                                ?>

                                                <select name="servicesAppliedQty" id="servicesAppliedQty">
                                                    <? for ($n = 1; $n <= $remainsAmnt; $n++) {
                                                        ?><option value="<?= $n; ?>"<?= $n == $subscription['servicesAppliedQty'] ? ' selected' : ''; ?>><?= $n; ?></option><?
                                                    }
                                                    ?>
                                                </select>
                                                дог.<?= $subscription['servicesAppliedContract']; ?>
                                            <? } else { ?>
                                                <?= $subscription['servicesAppliedQty'] ? $subscription['servicesAppliedQty'] : '--'; ?>
                                            <? } ?>

                                        </div>
                                        <div class="C">
                                            <? if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) { ?>
                                                <? if (isset($subscription['personal'])) { ?><select name="servicesAppliedPersonal" id="servicesAppliedPersonal"><option></option><?= $subscription['personal']; ?></select><? } else { ?><b style="color: red;">Нет доступных</b><? } ?>
                                            <? } else { ?>
                                                <? if ($subscription['servicesAppliedPersonal']) { ?>
                                                    <? $appliedPersonal2 = array_search_2d($subscription['servicesAppliedPersonal'], $subscription['allpersonal'], 'idusers') ?? null; ?>

                                                    <a href="/pages/reception/?personal=<?= $subscription['servicesAppliedPersonal']; ?>&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>">
                                                        <?= $allPersonal[$subscription['servicesAppliedPersonal']]['usersLastName']; ?>
                                                        <?= mb_substr($allPersonal[$subscription['servicesAppliedPersonal']]['usersFirstName'], 0, 1); ?>.
                                                    </a><? } else { ?>
                                                    ---
                                                <? } ?>
                                            <? } ?>
                                        </div>
                                        <div style="text-align: center;">
                                            <?
                                            $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                            if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) {
                                                ?>
                                                <input name="servicesAppliedTimeBegin" id="servicesAppliedTimeBegin" type="time"<?= $subscription['servicesAppliedTimeBegin'] ? (' value="' . date("H:i", mystrtotime($subscription['servicesAppliedTimeBegin'])) . '"') : '' ?>>
                                            <? } else { ?>
                                                <?= $subscription['servicesAppliedTimeBegin'] ? date("H:i", mystrtotime($subscription['servicesAppliedTimeBegin'])) : '--:--'; ?>
                                                <? if ($subscription['servicesAppliedStarted'] ?? false) {
                                                    ?>
                                                    (<?= date("H:i", mystrtotime($subscription['servicesAppliedStarted'])); ?>)
                                                <? } ?>
                                            <? } ?>
                                        </div>
                                        <div style="text-align: center;">
                                            <? if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) { ?><input name="servicesAppliedTimeEnd" id="servicesAppliedTimeEnd" type="time"<?= $subscription['servicesAppliedTimeEnd'] ? (' value="' . date("H:i", mystrtotime($subscription['servicesAppliedTimeEnd'])) . '"') : '' ?>>
                                            <? } else { ?>
                                                <?= $subscription['servicesAppliedTimeEnd'] ? date("H:i", mystrtotime($subscription['servicesAppliedTimeEnd'])) : '--:--'; ?>
                                                <? if ($subscription['servicesAppliedFineshed'] ?? false) {
                                                    ?>
                                                    (<?= date("H:i", mystrtotime($subscription['servicesAppliedFineshed'])); ?>)
                                                <? } ?>
                                            <? } ?>
                                        </div>

                                        <div style="text-align: center;">
                                            <?
                                            $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                            if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) {
                                                ?><input type="hidden" name="servicesAppliedIsFree" value="0">

                                                <? if (($_USER['lname'] == 'Меркулова' && $_USER['fname'] == 'Валерия') || $_USER['id'] == 176) {
                                                    ?>
                                                    <i class="fas fa-hand-middle-finger showOnHover"></i>
                                                    <?
                                                }
                                                ?>
                                            <? } else { ?>

                                                <?
                                                if (
                                                        $subscription['servicesAppliedContract'] == null &&
                                                        round($subscription['servicesAppliedPrice'] ?? 0 ) == 0
                                                ) {
                                                    ?>
                                                    <i class="fas fa-gift"></i>
                                                <? } ?>
                                                <?
                                                if (
                                                        $subscription['servicesAppliedContract'] == null &&
                                                        round($subscription['servicesAppliedPrice'] ?? 0 ) > 0
                                                ) {
                                                    ?>
                                                    <b style="color: red;">ОПЛАТИТЬ!</b>
                                                <? } ?>
                                            <? } ?>
                                        </div>
                                        <!--<div style="text-align: center;"><input type="button" value="X" style="color: red;"></div>-->
                                    </div>
                                    <?
                                    if ($subscription['servicesAppliedDeleted']) {
                                        ?><div style="grid-column: span 4; color: silver; white-space: nowrap;"><?= $subscription['daleteReasonsName'] ?? ''; ?> (<?= $subscription['usersLastName'] ?? ''; ?> <?= date("d.m H:i", strtotime($subscription['servicesAppliedDeleted'])); ?>)</div><?
                                    } else {
                                        ?>
                                        <div style="text-align: center;"><a href="#" onclick="GETreloc('edit', <?= $subscription['idservicesApplied']; ?>);"><i class="fas fa-edit"></i></a></div>
                                        <?
                                    }
                                    ?>

                                    <?
                                    $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                    if (isset($_GET['edit']) && $_GET['edit'] == $subscription['idservicesApplied']) {
                                        $strHeight = 25;
                                        ?>
                                        <div style="grid-column: -4/-1;">
                                            <div>
                                                <input type="submit" value="Сохранить" style="height: 20px; line-height: 20px; padding: 0px 20px; margin: 0px;">
                                                <input type="submit" style="height: 20px; line-height: 20px; padding: 0px 20px; margin: 0px;" onclick="GETreloc('edit', null);
                                                                        void(0);
                                                                        return false;" value="Отмена">
                                            </div>

                                        </div>

                                        <div style="grid-column: 1/-1;"><?
                                            $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                            $thisServiceAvailibility = query2array(mysqlQuery("SELECT *,"
                                                            . " (UNIX_TIMESTAMP(`servicesAppliedTimeEnd`) - UNIX_TIMESTAMP(`servicesAppliedTimeBegin`)) AS `servicesAppliedDuration`"
                                                            . " FROM `servicesApplied`"
                                                            . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
                                                            . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`) "
                                                            . "WHERE `servicesAppliedService` = '" . $subscription['servicesAppliedService'] . "'"
                                                            . "AND `servicesAppliedDate` = '" . $subscription['servicesAppliedDate'] . "'"
                                                            . " AND isnull(`servicesAppliedDeleted`)"));

                                            usort($thisServiceAvailibility, function ($a, $b) {
                                                return $a['servicesAppliedTimeBegin'] <=> $b['servicesAppliedTimeBegin'];
                                            });
                                            if (count($subscription['allpersonal'])) {
                                                $allpersonalSQL = implode(',', array_filter(array_column($subscription['allpersonal'], 'idusers')));
                                                $allpersonalSQLavailibility = "SELECT *,"
                                                        . " (UNIX_TIMESTAMP(`servicesAppliedTimeEnd`) - UNIX_TIMESTAMP(`servicesAppliedTimeBegin`)) AS `servicesAppliedDuration`"
                                                        . " FROM `servicesApplied`"
                                                        . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
                                                        . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`) "
                                                        . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) "
                                                        . " WHERE `servicesAppliedPersonal` IN (" . $allpersonalSQL . ")"
                                                        . " AND `servicesAppliedDate` = '" . $subscription['servicesAppliedDate'] . "'"
                                                        . " AND isnull(`servicesAppliedDeleted`)"
                                                        . "";
                                                $allpersonalAvailibility = query2array(mysqlQuery($allpersonalSQLavailibility));
                                            }
                                            ?>

                                            <script>

                                                function drgSrt(e, data) {
                                                    e.dataTransfer.setData("text/plain", JSON.stringify(data));
                                                    qs(`#SA${data.idservicesApplied}`).style.display = 'none';
                                                    console.log(data);
                                                }

                                                function makeDroppable(e, data) {
                                                    e.preventDefault();
                                                    let rdata = e.dataTransfer.getData("text/plain");
                                                    rdata = JSON.parse(rdata);
                                                    let startDate = new Date(data.timeStart);
                                                    let endDate = new Date(data.timeStart);
                                                    endDate.setSeconds(endDate.getSeconds() + rdata.duration);
                                                    qs('#servicesAppliedPersonal').value = data.personal;
                                                    qs('#servicesAppliedTimeBegin').value = `${startDate.getHours()}:${_0(startDate.getMinutes())}`;
                                                    qs('#servicesAppliedTimeEnd').value = `${endDate.getHours()}:${_0(endDate.getMinutes())}`;
                                                }

                                                function dropImg(e, data) {
                                                    e.preventDefault();
                                                    var rdata = e.dataTransfer.getData("text/plain");
                                                    rdata = JSON.parse(rdata);
                                                    console.log('data', data);
                                                    console.log('rdata', rdata);
                                                    qs(`#SA${rdata.idservicesApplied}`).style.display = 'block';
                                                    qs(`#SA${rdata.idservicesApplied}`).style.top = '0px';
                                                    e.target.appendChild(qs(`#SA${rdata.idservicesApplied}`));
                                                    e.target.style.backgroundColor = '';
                                                    //if (confirm('Перенести?')) {}
                                                }
                                            </script>

                                            <div style="display: inline-block; background-color: white; border: 1px solid red;">
                                                <div style="display: grid; grid-template-columns: auto auto <?
                                                for ($n = 0; $n < count($subscription['allpersonal']); $n++) {
                                                    if (!in_array($subscription['allpersonal'][$n]['idusers'], array_column($allpersonalAvailibility, 'servicesAppliedPersonal'))) {
                                                        continue;
                                                    }
                                                    print 'auto ';
                                                }
                                                ?>; border-top: 1px solid silver; border-left: 1px solid silver;">

                                                    <div style="border-bottom: 2px solid gray; border-right: 1px solid silver;"></div>
                                                    <div style="padding: 0px 10px;  border-bottom: 2px solid gray; border-right: 1px solid silver;"><?= $subscription['servicesName']; ?></div>




                                                    <?
                                                    $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                                    for ($n = 0; $n < count($subscription['allpersonal']); $n++) {
                                                        if (!in_array($subscription['allpersonal'][$n]['idusers'], array_column($allpersonalAvailibility, 'servicesAppliedPersonal'))) {
                                                            continue;
                                                        }
                                                        ?><div style="padding: 0px 10px; border-right: 1px solid silver;  border-bottom: 2px solid gray; font-size: 0.8em; cursor: pointer;" onclick="qs('#servicesAppliedPersonal').value =<?= $subscription['allpersonal'][$n]['idusers'] ?>"><?= $subscription['allpersonal'][$n]['usersLastName']; ?> <?= mb_substr($subscription['allpersonal'][$n]['usersFirstName'], 0, 1); ?>.</div><?
                                                    }
                                                    ?>




                                                    <?
                                                    for ($time = mystrtotime($subscription['servicesAppliedDate'] . " 10:00:00"); $time <= mystrtotime($subscription['servicesAppliedDate'] . " 20:00:00"); $time += 30 * 60) {
                                                        ?>

                                                        <div style="border-bottom: 1px solid silver; border-right: 1px solid silver; padding: 0px 10px; height: 25px; align-items: center; display: flex;"><?= date("H:i", $time); ?></div>
                                                        <div style="border-bottom: 1px solid silver; border-right: 1px solid silver;">


                                                            <?
                                                            $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                                            $curtime = $subscription['servicesAppliedDate'] . ' ' . date("H:i:s", $time);
                                                            $currentServices = array_filter($thisServiceAvailibility, function ($element) use ($curtime) {

                                                                return (
                                                                mystrtotime($element['servicesAppliedTimeBegin']) >= mystrtotime($curtime) &&
                                                                mystrtotime($element['servicesAppliedTimeBegin']) < mystrtotime($curtime) + 1800);
                                                            });
                                                            foreach ($currentServices as $currentService) {
                                                                ?> <div style="
                                                                     justify-content: center;
                                                                     align-items: center;
                                                                     border: 1px solid black;
                                                                     position: absolute;
                                                                     top: <?= ($strHeight * (mystrtotime($currentService['servicesAppliedTimeBegin']) - mystrtotime($curtime)) / 1800) ?>px;
                                                                     left: 0px;
                                                                     width: 100%;
                                                                     height: <?= ($currentService['servicesAppliedDuration'] / 1800) * 25; ?>px;
                                                                     z-index: 1;
                                                                     background-color: hsla(220,50%,90%,0.7);
                                                                     font-size: 0.7em;
                                                                     line-height: 1em;
                                                                     display: flex;">
                                                                     <? if ($currentService['idusers']) { ?>
                                                                        <?= $currentService['usersLastName']; ?> <?= mb_substr($currentService['usersFirstName'], 0, 1); ?>.
                                                                    <? } else { ?>Без спец-та.<? }
                                                                    ?><br>
                                                                    <?= $currentService['clientsLName']; ?> <?= mb_substr($currentService['clientsFName'], 0, 1); ?>. <?= $currentService['clientsMName'] ? (mb_substr($currentService['clientsMName'], 0, 1) . '.') : '' ?>
                                                                </div><?
                                                            }
                                                            ?>

                                                        </div>
                                                        <?
                                                        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
//														printr($subscription);
                                                        for ($n = 0; $n < count($subscription['allpersonal']); $n++) {
                                                            if (!in_array($subscription['allpersonal'][$n]['idusers'], array_column($allpersonalAvailibility, 'servicesAppliedPersonal'))) {
                                                                continue;
                                                            }
                                                            ?><div
                                                                ondragleave="this.style.backgroundColor='';"
                                                                ondragover="makeDroppable(event,{timeStart:'<?= $curtime; ?>',personal:<?= $subscription['allpersonal'][$n]['idusers']; ?>},this.style.backgroundColor='pink');"
                                                                ondrop="dropImg(event, {personal:<?= $subscription['allpersonal'][$n]['idusers']; ?>})"
                                                                style ="border-bottom: 1px solid silver; border-right: 1px solid silver;">

                                                                <?
                                                                if ($_USER['id'] == 176) {

//																	printr($subscription['allpersonal'][$n]['idusers']);
                                                                }
                                                                ?>

                                                                <?
                                                                $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                                                $currentServices = array_filter($allpersonalAvailibility, function ($element) {
                                                                    global $curtime, $subscription, $n;
                                                                    return (
                                                                    (
                                                                    mystrtotime($element['servicesAppliedTimeBegin']) >= mystrtotime($curtime) &&
                                                                    mystrtotime($element['servicesAppliedTimeBegin']) < mystrtotime($curtime) + 1800
                                                                    ) &&
                                                                    $subscription['allpersonal'][$n]['idusers'] == $element['servicesAppliedPersonal']
                                                                    );
                                                                });
                                                                $m = 0;
                                                                foreach ($currentServices as $currentService) {
                                                                    ?> <div
                                                                        id="SA<?= $currentService['idservicesApplied']; ?>"
                                                                        <? if ($currentService['idservicesApplied'] == ($_GET['edit'])) { ?>

                                                                            draggable="true"
                                                                            ondragstart="drgSrt(event,{idservicesApplied:<?= $currentService['idservicesApplied']; ?>,duration:<?= $currentService['servicesAppliedDuration']; ?>});"
                                                                        <? } ?>

                                                                        style="
                                                                        cursor: pointer;
                                                                        border: 1px solid black;
                                                                        position: absolute;
                                                                        top: <?= ($strHeight * (mystrtotime($currentService['servicesAppliedTimeBegin']) - mystrtotime($curtime)) / 1800) ?>px;
                                                                        left:<?= (100 / count($currentServices)) * $m; ?>%;
                                                                        width: <?= 100 / count($currentServices); ?>%;
                                                                        height: <?= ($currentService['servicesAppliedDuration'] / 1800) * 25; ?>px;
                                                                        z-index: 10; background-color: hsla(<?= $currentService['idservicesApplied'] == ($_GET['edit'] ?? 0) ? '120' : '220'; ?>,50%,90%,0.7);
                                                                        font-size: 0.7em;
                                                                        line-height: 1em;
                                                                        overflow: hidden;">
                                                                        <? if ($currentService['idservicesApplied'] != ($_GET['edit'])) { ?><a href="/pages/reception/?client=<?= $currentService['idclients']; ?>&date=<?= $currentService['servicesAppliedDate']; ?>&edit=<?= $currentService['idservicesApplied']; ?>"><? } ?>
                                                                            <?= $currentService['servicesName']; ?>

                                                                            <?= $currentService['clientsLName']; ?> <?= mb_substr($currentService['clientsFName'], 0, 1); ?>. <?= $currentService['clientsMName'] ? (mb_substr($currentService['clientsMName'], 0, 1) . '.') : '' ?>
                                                                            <? if ($currentService['idservicesApplied'] != ($_GET['edit'])) { ?></a><? } ?>
                                                                    </div><?
                                                                    $m++;
                                                                }
                                                                ?>
                                                            </div><?
                                                        }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                                        ?>
                                                        <?
                                                    }
                                                    ?>
                                                </div>


                                            </div>
                                        </div>
                                        <?
                                        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                    } else {
                                        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                        if (!$subscription['servicesAppliedDeleted']) {
                                            ?>

                                            <div style="text-align: center;">
                                                <a href="#" onclick="editField({moveFrom: '<?= ($_GET['date'] ?? date("Y-m-d")); ?>', servicesApplied: [<?= $subscription['idservicesApplied']; ?>]});" ><i class="fas fa-calendar-alt"></i></a>
                                            </div>
                                            <div style="text-align: center;">
                                                <? if ($subscription['servicesAppliedDeleted']) { ?><i class="far fa-times-circle" style="position: absolute; top: 1.5px; transform: translateX(-50%); color: red; background-color: pink; border-radius: 50%;" title="Удалено"></i><?
                                                } elseif ($subscription['servicesAppliedFineshed']) {
                                                    $color = 'silver';
                                                    if ($subscription['servicesAppliedStartedBy'] == $subscription['servicesAppliedPersonal'] && $subscription['servicesAppliedFinishedBy'] == $subscription['servicesAppliedPersonal']) {
                                                        $color = 'darkgreen';
                                                    }

                                                    if ($subscription['servicesAppliedStartedBy'] != $subscription['servicesAppliedPersonal'] && $subscription['servicesAppliedFinishedBy'] != $subscription['servicesAppliedPersonal']) {
                                                        $color = 'orange';
                                                    }

                                                    if ($subscription['servicesAppliedStartedBy'] == $subscription['servicesAppliedPersonal'] && $subscription['servicesAppliedFinishedBy'] != $subscription['servicesAppliedPersonal']) {
                                                        $color = 'red';
                                                    }
                                                    ?><i class="fas fa-clipboard-check" title="Выполнено" style="color: <?= $color; ?>;"></i><?
                                                } elseif (!$subscription['servicesAppliedFineshed'] && mystrtotime($subscription['servicesAppliedTimeEnd']) < time()) {
                                                    ?>
                                                    <i class="far fa-clock" style="color: red; background-color: pink; border-radius: 50%;" title="Не выполнено, опоздание"></i>
                                                <? } elseif (mystrtotime($subscription['servicesAppliedTimeBegin']) > time()) { ?>
                                                    <i class="fas fa-hourglass-half" style="color: gray;" title="Ожидание"></i>

                                                    <?
                                                }
                                                ?>
                                            </div>
                                            <div style="text-align: center;">
                                                <? if (((R(141) || $subscription['servicesAppliedBy'] == $_USER['id']) && (R(168) || strtotime($subscription['servicesAppliedDate']) >= strtotime(date("Y-m-d"))))) {
                                                    ?>	<span style="color: red; cursor: pointer;" onclick="deleteServicesApplied('<?= $_USER['fname'] ?>',<?= $subscription['idservicesApplied']; ?>);" ><i class="far fa-times-circle"></i></span><? } else { ?><i class="far fa-times-circle" style="color: silver;"></i><? } ?>

                                            </div>
                                            <?
                                        }
                                        ?>



                                        <div></div>
                                    <? } ?>

                                <? }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart); ?>
                            </div>
                        </div>
                        <script>
                            async function deleteServicesApplied(name, service) {
                                let box = el('div', {className: 'modal neutral'});
                                box.appendChild(el('h2', {innerHTML: `${name}, укажите причину удаления`}));
                                let boxBody = el('div', {className: 'box-body'});
                                box.appendChild(boxBody);
                                document.body.appendChild(box);


                                let variantsDiv = el('div', {innerHTML: `Загружаю варианты...`});
                                boxBody.appendChild(variantsDiv);

                                let cancelBtn = el('button', {innerHTML: rt(`Отмена`, `Хотя не...`, `В другой раз`, `Я ещё подумаю`)});
                                box.appendChild(cancelBtn);
                                cancelBtn.addEventListener('click', function () {
                                    box.parentNode.removeChild(box);
                                });

                                let variants = await fetch('IO.php', {
                                    body: JSON.stringify({action: 'getDeleteReasons'}),
                                    credentials: 'include',
                                    method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                                }).then(result => result.text()).then(async function (text) {
                                    try {
                                        let jsn = JSON.parse(text);
                                        return jsn;
                                    } catch (e) {
                                        MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
                                    }
                                }); //fetch
                                if (variantsDiv) {
                                    variantsDiv.innerHTML = '';
                                    if (variants.length) {
                                        for (let variant of variants) {
                                            let btn = el('button', {className: 'buttonVariant', innerHTML: `${variant.name}`});
                                            variantsDiv.appendChild(btn);
                                            btn.addEventListener('click', function () {
                                                GR({deleteServicesAppliedWithReason: service, reason: variant.id});
                                            });

                                        }
                                        //										let btn = el('button', {className: 'buttonVariant', innerHTML: `Расподарочить`});
                                        //										variantsDiv.appendChild(btn);
                                        //										btn.addEventListener('click', function () {
                                        //											GR({deleteServicesAppliedWithReason: service, reason: 'notFree'});
                                        //										});
                                    } else {
                                        variantsDiv.innerHTML = 'Не загрузилось...';
                                    }
                                }




                                console.log(variants);

                            }
                        </script>
                        <?
                        if (isset($_GET['edit'])) {
                            ?>
                        </form>
                        <?
                    }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                    ?>


                </div>



                <?
            } elseif (isset($_GET['personal'])) {
                if (validateDate($_GET['date'])) {
//					print
                    $personalSQL = "SELECT "
                            . "`servicesApplied`.*,"
                            . " `clients`.*, "
                            . " `services`.*, "
                            . " `daleteReasons`.*,"
                            . "`deluser`.`usersLastName` as `delUsersLastName` "
                            . " FROM `servicesApplied`"
                            . " LEFT JOIN `users` AS `SAusers` ON (`SAusers`.`idusers` = `servicesAppliedPersonal`) "
                            . " LEFT JOIN `users` AS `deluser` ON (`deluser`.`idusers` = `servicesAppliedDeletedBy`) "
                            . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`) "
                            . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) "
                            . " LEFT JOIN `daleteReasons` ON (`iddaleteReasons` = `servicesAppliedDeleteReason`)"
                            . " WHERE "
                            . " `servicesAppliedDate` = '" . $_GET['date'] . "'"
                            . " AND " . ($_GET['personal'] == "" ? "isnull(`servicesAppliedPersonal`)" : "`servicesAppliedPersonal` = '" . FSI($_GET['personal']) . "'"
                            . (!R(86) ? " AND isnull(`servicesAppliedDeleted`)" : '') );
                    $personal = query2array(mysqlQuery($personalSQL));
                    $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                    usort($personal, function ($a, $b) {
                        return ($a['servicesAppliedStarted'] ?? $a['servicesAppliedTimeBegin']) <=> ($b['servicesAppliedStarted'] ?? $b['servicesAppliedTimeBegin']);
                    });
//					printr($personal[0]);
                    $user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . FSI($_GET['personal']) . "'"));
                    ?>
                    <div class="box neutral">
                        <div class="box-body">
                            <h2>
                                <? if ($user['idusers'] ?? false) { ?>
                                    <? if (R(8)) { ?><a href="/pages/personal/info.php?employee=<?= $user['idusers']; ?>" target="_blank"><? } ?>
                                        <?= $user['usersLastName'] ?? 'Без специалиста' ?> <?= $user['usersFirstName'] ?? '' ?>
                                        <? if (R(8)) { ?></a><? } ?>
                                <? } else {
                                    ?>Без специалиста<? }
                                ?>
                                / <span style="display: inline-block;"><input type="date" value="<?= validateDate($_GET['date']) ? $_GET['date'] : date("Y-m-d"); ?>" onchange="GETreloc('date', this.value || '<?= date("Y-m-d"); ?>');"></span>
                            </h2>
                            <div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto  auto auto auto auto;">
                                <div style="display: contents;">
                                    <div class="B C">Клиент</div>
                                    <div class="B C">Процедура</div>
                                    <div class="B C">Кол-во</div>
                                    <div class="B C">Начало<br>План (факт)</div>
                                    <div class="B C">Окончание<br>План (факт)</div>
                                    <div class="B C"><i class="fas fa-gift" title="Подарочная процедура"></i></div>
                                    <div class="B C">Отметка</div>
                                    <!--<div class="B C">ЗП</div>-->
                                </div>
                                <?
                                foreach ($personal as $serviceApplied) {
                                    ?>
                                    <div style="display: contents; <?= ($serviceApplied['servicesAppliedDeleted'] ?? false) ? ' color: silver;' : ''; ?>">
                                        <div>
                                            <? if (clientIsNew($serviceApplied['idclients'], ($_GET['date'] ?? date("Y-m-d")))) { ?><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i><? } ?>
                                            <a href="/pages/reception/?client=<?= $serviceApplied['idclients'] ?>&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>">
                                                <?= $serviceApplied['clientsLName'] ?>
                                                <?= $serviceApplied['clientsFName'] ?>
                                                <?= $serviceApplied['clientsMName'] ?>
                                            </a>
                                        </div>
                                        <div>
                                            <a href="/pages/services/index.php?service=<?= $serviceApplied['idservices'] ?>" target="_blank"><?= $serviceApplied['servicesName'] ?></a>
                                        </div>
                                        <div class="C">
                                            <?= $serviceApplied['servicesAppliedQty'] ?>
                                        </div>

                                        <div style="text-align: center;">
                                            <?= $serviceApplied['servicesAppliedTimeBegin'] ? date("H:i", mystrtotime($serviceApplied['servicesAppliedTimeBegin'])) : '--:--'; ?>
                                            <? if ($serviceApplied['servicesAppliedStarted'] ?? false) {
                                                ?>
                                                (<?= date("H:i", mystrtotime($serviceApplied['servicesAppliedStarted'])); ?>)
                                            <? } ?>
                                        </div>
                                        <div style="text-align: center;">
                                            <?= $serviceApplied['servicesAppliedTimeEnd'] ? date("H:i", mystrtotime($serviceApplied['servicesAppliedTimeEnd'])) : '--:--'; ?>
                                            <? if ($serviceApplied['servicesAppliedFineshed'] ?? false) {
                                                ?>
                                                (<?= date("H:i", mystrtotime($serviceApplied['servicesAppliedFineshed'])); ?>)
                                            <? }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart); ?>
                                        </div>
                                        <div style="text-align: center;">
                                            <? // printr($serviceApplied);      ?>
                                            <?
                                            if (
                                                    $serviceApplied['servicesAppliedContract'] == null &&
                                                    round($serviceApplied['servicesAppliedPrice'] ?? 0 ) == 0) {
                                                ?><i class="fas fa-gift" title="Подарочная процедура"></i><? } ?>
                                        </div>


                                        <? if (($serviceApplied['servicesAppliedDeleted'] ?? false)) { ?>
                                            <div><?= $serviceApplied['daleteReasonsName'] ?? ''; ?> (<?= $serviceApplied['delUsersLastName'] ?? ''; ?>)</div>
                                        <? } else {
                                            ?>
                                            <div class="C">
                                                <? if ($serviceApplied['servicesAppliedDeleted']) { ?><i class="far fa-times-circle" style="position: absolute; top: 1.5px; transform: translateX(-50%); color: red; background-color: pink; border-radius: 50%;" title="Удалено"></i><?
                                                } elseif ($serviceApplied['servicesAppliedFineshed']) {

                                                    $color = 'silver';
                                                    if ($serviceApplied['servicesAppliedStartedBy'] == $serviceApplied['servicesAppliedPersonal'] && $serviceApplied['servicesAppliedFinishedBy'] == $serviceApplied['servicesAppliedPersonal']) {
                                                        $color = 'darkgreen';
                                                    }

                                                    if ($serviceApplied['servicesAppliedStartedBy'] != $serviceApplied['servicesAppliedPersonal'] && $serviceApplied['servicesAppliedFinishedBy'] != $serviceApplied['servicesAppliedPersonal']) {
                                                        $color = 'orange';
                                                    }

                                                    if (($serviceApplied['servicesAppliedStartedBy'] == $serviceApplied['servicesAppliedPersonal'] || !$serviceApplied['servicesAppliedStartedBy']) && $serviceApplied['servicesAppliedFinishedBy'] != $serviceApplied['servicesAppliedPersonal']) {
                                                        $color = 'red';
                                                    }
                                                    ?><i class="fas fa-clipboard-check" title="Выполнено <?= getFIO($serviceApplied['servicesAppliedStartedBy']) . ' ' . getFIO($serviceApplied['servicesAppliedFinishedBy']) ?>" style="color: <?= $color; ?>;"></i><?
                                                } elseif (!$serviceApplied['servicesAppliedFineshed'] && mystrtotime($serviceApplied['servicesAppliedTimeEnd']) < time()) {
                                                    ?>
                                                    <i class="far fa-clock" style="color: red; background-color: pink; border-radius: 50%;" title="Не выполнено, опоздание"></i>
                                                <? } elseif (mystrtotime($serviceApplied['servicesAppliedTimeBegin']) > time()) { ?>
                                                    <i class="fas fa-hourglass-half" style="color: gray;" title="Ожидание"></i>

                                                    <?
                                                }
                                                if (R(81) && !$serviceApplied['servicesAppliedFineshed']) {
                                                    ?>
                                                    <input type="button" value="Завершить" onclick="GR({finalise:<?= $serviceApplied['idservicesApplied']; ?>});">

                                                    <?
                                                }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                                ?>

                                            </div>
                                            <?
                                        }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                        ?>





                                    </div>
                                    <?
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <?
                }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
            } elseif (isset($_GET['findClient'])) {
//				printr($_POST);
                $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                ?>

                <div class="box neutral">
                    <div class="box-body">
                        <h2>Найти</h2>
                    </div>
                    <form action="/pages/reception/?findClient&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>" method="post">
                        <div style="padding: 10px;">
                            <div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;">
                                <span>Фамилия</span><input type="text" id="lastname" onkeydown="if (event.keyCode == 32) {
                                                    qs('#firstname').focus();
                                                    void(0);
                                                    return false;
                                                }" name="lastname" autocomplete="off" value="<?= $_POST['lastname'] ?? '' ?>">
                                <span>Имя</span><input type="text" onkeydown="if (event.keyCode == 32) {
                                                    qs('#middlename').focus();
                                                    void(0);
                                                    return false;
                                                }" id="firstname" name="firstname" autocomplete="off" value="<?= $_POST['firstname'] ?? '' ?>">
                                <span>Отчество</span><input onkeydown="if (event.keyCode == 32) {
                                                    qs('#gender').focus();
                                                    void(0);
                                                    return false;
                                                }" type="text" id="middlename" name="middlename" autocomplete="off" value="<?= $_POST['middlename'] ?? '' ?>">
                                <span>Пол</span><select name="gender" id="gender" onchange="qs('#phoneNumber').focus();">
                                    <option value="">Выбрать</option>
                                    <option value="0">Женский</option>
                                    <option value="1">Мужской</option>
                                </select>
                                <span>№ карты</span><input type="text" id="acardnumber" oninput="digon();" name="acardnumber" autocomplete="off" value="<?= $_POST['acardnumber'] ?? '' ?>">
                                <span>Дата рождения</span><input type="date" id="birthday" name="birthday" autocomplete="off" value="<?= $_POST['birthday'] ?? '' ?>">
                                <span>Номер телефона</span><input type="text" id="phoneNumber" placeholder="89211234567" name="phoneNumber" oninput="digon();" autocomplete="off" value="<?= $_POST['phoneNumber'] ?? '' ?>">
                                <span>Новый клиент</span>
                                <span><input type="checkbox" name="isNew" id="isNew"><label for="isNew">Новый клиент</label></span>
                                <span>Откуда клиент?</span>
                                <span>
                                    <select name="clientsSource" id="clientsSource">
                                        <option value=""></option>
                                        <?
                                        $clientsSources = query2array(mysqlQuery("SELECT * FROM `clientsSources`"));
                                        foreach ($clientsSources as $clientsSource) {
                                            ?><option value="<?= $clientsSource['idclientsSources']; ?>"><?= $clientsSource['clientsSourcesName']; ?></option><?
                                        }$PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                        ?>
                                    </select>

                                </span>

                                <? // printr($administrationslist);                                                                ?>
                            </div>

                            <div style="text-align: right;margin-top: 20px;">
                                <input type="submit" value="Найти">
                                <input type="submit" onclick="addClient();
                                                void(0);
                                                return false;" value="Добавить">
                            </div>

                        </div>
                    </form>
                    <script>
                        async function addClient() {
                            if (qs('#clientsSource').value == '') {
                                await MSG('Указать откуда клиент');
                                return false;
                            }
                            let data = {
                                action: 'addNewClient',
                                lastname: qs('#lastname').value.trim(),
                                firstname: qs('#firstname').value.trim(),
                                middlename: qs('#middlename').value.trim(),
                                acardnumber: qs('#acardnumber').value.trim(),
                                gender: qs('#gender').value,
                                birthday: qs('#birthday').value.trim(),
                                clientsPhone: qs('#phoneNumber').value.trim(),
                                isNew: qs('#isNew').checked,
                                callerID: qs('#callerID').value,
                                //								callerName: qs('#callerName').value.trim(),
                                callerAdmin: qs('#callerAdmin').value,
                                clientsSource: qs('#clientsSource').value
                            };
                            console.log(data);
                            if (data.lastname !== '' && data.firstname !== '') {// && data.acardnumber !== ''

                                fetch('IO.php', {
                                    body: JSON.stringify(data),
                                    credentials: 'include',
                                    method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                                }).then(result => result.text()).then(async function (text) {
                                    try {
                                        let jsn = JSON.parse(text);
                                        if (jsn.success && jsn.client) {
                                            window.location.href = `/pages/reception/?client=${jsn.client}&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>`;
                                        }
                                        if (jsn.msgs) {
                                            jsn.msgs.forEach(msg => {
                                                MSG(msg);
                                            });
                                        }
                                    } catch (e) {
                                        MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
                                    }
                                }); //fetch

                            } else {
                                MSG('Должны быть указаны <br><br><div style="text-align: left;">1.Фамилия <br>2.Имя <br>3.номер амб. карты.</div>');
                            }
                            console.log(data);
                        }
                    </script>


                    <div style="display: inline-block; padding: 10px;">
                        <div style="display: grid; grid-template-columns: auto auto auto auto auto; grid-gap: 0px; border-left: 1px solid silver; border-top: 1px solid silver;">
                            <div style="padding: 5px 20px; font-weight: bold; border-right: 1px solid silver; border-bottom: 1px solid silver;">Фамилия</div>
                            <div style="padding: 5px 20px; font-weight: bold; border-right: 1px solid silver; border-bottom: 1px solid silver;">Имя</div>
                            <div style="padding: 5px 20px; font-weight: bold; border-right: 1px solid silver; border-bottom: 1px solid silver;">Отчество</div>
                            <div style="padding: 5px 20px; font-weight: bold; border-right: 1px solid silver; border-bottom: 1px solid silver;">Дата рожд.</div>
                            <div style="padding: 5px 20px; font-weight: bold; border-right: 1px solid silver; border-bottom: 1px solid silver;">№ амб.карты</div>

                            <?
                            if (isset($clients) && is_array($clients) && count($clients)) {
                                $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                                foreach ($clients as $client) {
                                    ?>
                                    <div style="padding: 3px 10px; border-right: 1px solid silver; border-bottom: 1px solid silver;"><a href="/pages/reception/?client=<?= $client['idclients']; ?>"><?= $client['clientsLName']; ?></a></div>
                                    <div style="padding: 3px 10px; border-right: 1px solid silver; border-bottom: 1px solid silver;"><a href="/pages/reception/?client=<?= $client['idclients']; ?>"><?= $client['clientsFName']; ?></a></div>
                                    <div style="padding: 3px 10px; border-right: 1px solid silver; border-bottom: 1px solid silver;"><a href="/pages/reception/?client=<?= $client['idclients']; ?>"><?= $client['clientsMName']; ?></a></div>
                                    <div style="padding: 3px 10px; border-right: 1px solid silver; border-bottom: 1px solid silver;"><a href="/pages/reception/?client=<?= $client['idclients']; ?>"><?= $client['clientsBDay']; ?></a></div>
                                    <div style="padding: 3px 10px; border-right: 1px solid silver; border-bottom: 1px solid silver;text-align: center;"><a href="/pages/reception/?client=<?= $client['idclients']; ?>"><?= $client['clientsAKNum']; ?></a></div>
                                    <?
                                }
                            }
                            ?>
                        </div>
                    </div>

                </div>
                <?
            } else {
                ?>

                <div class="box neutral">
                    <div class="box-body">
                        <h2><input type="date" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>" onchange="GETreloc('date', this.value);"></h2>
                        <?
                        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                        $allServicesSQL = "SELECT *,"
                                . "(UNIX_TIMESTAMP(`servicesAppliedTimeEnd`) - UNIX_TIMESTAMP(`servicesAppliedTimeBegin`)) AS `servicesAppliedDuration`, "
                                . " (SELECT COUNT(1)FROM `usersSchedule` WHERE `usersScheduleUser` = `idusers` AND `usersScheduleDate` = `servicesAppliedDate` AND NOT isnull(`usersScheduleFrom`) AND NOT isnull(`usersScheduleTo`)) AS `schedule`"
                                . " FROM `servicesApplied`"
                                . " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
                                . " LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`) "
                                . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`) "
                                . " WHERE  `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"
                                . " AND isnull(`servicesAppliedDeleted`)"
                                . "";
                        $allServices = query2array(mysqlQuery($allServicesSQL));
                        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);

                        if (isset($_GET['bypersonal'])) {
                            include 'bypersonal.php';
                        } else {
                            include 'byclient.php';
                        }
                        $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);
                        ?>
                    <? } ?>
                </div>
            </div>
        </div>
        <?
    }
    ?>
    <div><?= round(microtime(1) - $PAGEstart, 4); ?></div>
    <?
    $PGT[__FILE__][__LINE__] = (microtime(1) - $PAGEstart);

    if (0) {
        foreach ($PGT as $fileName => $file) {
            foreach ($file as $line => $PGTtime) {

                mysqlQuery("INSERT INTO `PGT` "
                        . " SET "
                        . " `PGTdate`='" . date("Y-m-d") . "',"
                        . " `PGTfile`='" . (preg_replace('|' . addslashes($_SERVER['DOCUMENT_ROOT']) . '|i', '', $fileName)) . "',"
                        . " `PGTline`='" . $line . "',"
                        . " `PGTtime` = '" . $PGTtime . "'"
                        . " ON DUPLICATE KEY UPDATE "
                        . " `PGTtime` = `PGTtime` + " . $PGTtime . "");
            }
        }
    }

//	print($allServicesSQL ?? '');
//	printr($PGT);
    include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
    