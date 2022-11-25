<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (isset($_GET['client'])) {
    $clientSQL = "SELECT *, (SELECT `usersGroup` FROM `users` WHERE `idusers`=`clientsAddedBy`) as `clientsAddedByUsersGroup` FROM "
            . " `clients` LEFT JOIN (SELECT * FROM `clientStatus` LEFT JOIN `clientsStatuses` ON (`idclientsStatuses` = `clientStatusStatus`) WHERE `idclientStatus` = (SELECT MAX(`idclientStatus`)  FROM `clientStatus` WHERE `clientStatusClient` = '" . FSI($_GET['client']) . "')) as `status` ON (`clientStatusClient` = `idclients`)"
            . " LEFT JOIN `clientsSources` ON (`idclientsSources` = `clientsSource`) "
            . " LEFT JOIN `RCC_phonesBases` ON (`idRCC_phonesBases` = `clientsDatabase`)"
            . " WHERE `idclients`='" . FSI($_GET['client']) . "'";
    $client = mfa(mysqlQuery($clientSQL));

    $clientsSourcesRights = query2array(mysqlQuery("SELECT * FROM `clientsSourcesRights` WHERE `clientsSourcesRightsUser` = '" . $_USER['id'] . "'"));
    if (!$client
//			|| !in_array($client['clientsSource'], array_column($clientsSourcesRights, 'clientsSourcesRightsSource'))
    ) {
        header("Location: /pages/offlinecall/");
        die();
    }
}

if ($_GET['TG_refuse'] ?? false) {
    mysqlQuery("UPDATE `clients` SET "
            . " `clientsTGrefuse`=" . sqlVON($_GET['TG_refuse']) . ","
            . " `clientsTGset` = NOW()"
            . " WHERE `idclients` = " . $client['idclients'] . " ");
    header("Location: " . GR2(['TG_refuse' => null]));
    exit('ok');
}

if (($_GET['action'] ?? '') == 'saveAppliedServices') {
    $servicesApplied = query2array(mysqlQuery("SELECT `servicesAppliedDate`,`servicesName`,`servicesAppliedQty` FROM `servicesApplied`"
                    . " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
                    . " WHERE "
                    . " NOT isnull(`servicesName`) "
                    . " AND NOT isnull(`servicesAppliedFineshed`) "
                    . " AND isnull(`servicesAppliedDeleted`) "
                    . " AND `servicesAppliedDate`>='" . mres(min($_GET['dateFrom'], $_GET['dateTo'])) . "'  "
                    . " AND `servicesAppliedDate`<='" . mres(max($_GET['dateFrom'], $_GET['dateTo'])) . "'  "
                    . " AND `servicesAppliedClient` = " . $client['idclients']
                    . ""));

    if (count($servicesApplied)) {
        $passport = mfa(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "')"));
        require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';

        $file = 'spravka-027.docx';
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($file); //$_SERVER['DOCUMENT_ROOT'] . '/templates/' .

        $n = 0;
        $rows = [];
        foreach ($servicesApplied as $service) {
            $n++;
            $rows[] = [
                'arn' => $n,
                'serviceDate' => date('d.m.Y', mystrtotime($service['servicesAppliedDate'])),
                'serviceName' => $service['servicesName'],
                'serviceQty' => ($service['servicesAppliedQty'] ?? 0)
            ];
        }



        $data = [
            'fioPokupatelya' => implode(' ', array_filter([$client['clientsLName'], $client['clientsFName'], $client['clientsMName']])),
            'ndog' => $client['idclients'],
            'from' => date('d.m.Y', mystrtotime(min($_GET['dateFrom'], $_GET['dateTo']))),
            'to' => date('d.m.Y', mystrtotime(max($_GET['dateFrom'], $_GET['dateTo']))),
            'clientBD' => ($client['clientsBDay'] ? date('d.m.Y', mystrtotime($client['clientsBDay'])) : 'Не указана'),
            'clientsPassportsResidence' => ($passport['clientsPassportsRegistration'] ?? $passport['clientsPassportsRegistration'] ?? 'Не указан'),
            'year' => date('y'),
            'month' => date('m'),
            'day' => date('d'),
        ];

        foreach (($data ?? []) as $variable => $value) {
            $templateProcessor->setValue($variable, $value);
        }
        $templateProcessor->cloneRowAndSetValues('arn', $rows);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . date("Y.m.d") . ' - ' . $data['fioPokupatelya'] . '.docx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        $templateProcessor->saveAs('php://output');
    }




    printr($servicesApplied, 1);
    die();
}


if (R(157) && ($_GET['client'] ?? false) && isset($_POST['targetdatabase'])) {
    include 'clientmove.php';
}


if (R(47)) {
    if (($_POST['action'] ?? false) == 'savePassportData') {
        if (mysqlQuery("INSERT INTO `clientsPassports` SET "
                        . " `clientsPassportsClient`='" . mres($_GET['client']) . "',"
                        . " `clientsPassportNumber` ='" . mres($_POST['clientsPassportNumber']) . "',"
                        . (($_POST['clientsPassportsResidence'] ?? false) ? " `clientsPassportsResidence` ='" . mres($_POST['clientsPassportsResidence']) . "'," : '')
                        . (($_POST['clientsPassportsRegistration'] ?? false) ? " `clientsPassportsRegistration` ='" . mres($_POST['clientsPassportsRegistration']) . "'," : '')
                        . (($_POST['clientsPassportsDate'] ?? false) ? " `clientsPassportsDate` ='" . mres($_POST['clientsPassportsDate']) . "'," : '')
                        . (($_POST['clientsPassportsBirthPlace'] ?? false) ? " `clientsPassportsBirthPlace` ='" . mres($_POST['clientsPassportsBirthPlace']) . "'," : '')
                        . (($_POST['clientsPassportsDepartment'] ?? false) ? " `clientsPassportsDepartment` ='" . mres($_POST['clientsPassportsDepartment']) . "'," : '')
                        . " `clientsPassportsAdded` ='" . date('Y-m-d') . "',"
                        . (($_POST['clientsPassportsCode'] ?? false) ? " `clientsPassportsCode` ='" . mres($_POST['clientsPassportsCode']) . "'," : '')
                        . " `clientsPassportsAddedBy`='" . $_USER['id'] . "'"
                        . "ON DUPLICATE KEY UPDATE "
                        . " `clientsPassportsClient`='" . mres($_GET['client']) . "',"
                        . " `clientsPassportNumber` ='" . mres($_POST['clientsPassportNumber']) . "',"
                        . (($_POST['clientsPassportsResidence'] ?? false) ? " `clientsPassportsResidence` ='" . mres($_POST['clientsPassportsResidence']) . "'," : '')
                        . (($_POST['clientsPassportsRegistration'] ?? false) ? " `clientsPassportsRegistration` ='" . mres($_POST['clientsPassportsRegistration']) . "'," : '')
                        . (($_POST['clientsPassportsDate'] ?? false) ? " `clientsPassportsDate` ='" . mres($_POST['clientsPassportsDate']) . "'," : '')
                        . (($_POST['clientsPassportsBirthPlace'] ?? false) ? " `clientsPassportsBirthPlace` ='" . mres($_POST['clientsPassportsBirthPlace']) . "'," : '')
                        . (($_POST['clientsPassportsDepartment'] ?? false) ? " `clientsPassportsDepartment` ='" . mres($_POST['clientsPassportsDepartment']) . "'," : '')
                        . " `clientsPassportsAdded` ='" . date('Y-m-d') . "',"
                        . (($_POST['clientsPassportsCode'] ?? false) ? " `clientsPassportsCode` ='" . mres($_POST['clientsPassportsCode']) . "'," : '')
                        . " `clientsPassportsAddedBy`='" . $_USER['id'] . "'"
                )) {
            header("Location: " . GR());
            die();
        } else {
            print_r($_POST);
            print_r(mysqli_error($link));
            die();
        }
    }
    if (isset($_GET['deletePhone'])) {
        if (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) {
            mysqlQuery("UPDATE `clientsPhones` SET "
                    . "`clientsPhonesDeleted` = CURRENT_TIMESTAMP,"
                    . "`clientsPhonesDeletedBy` = '" . $_USER['id'] . "'"
                    . " WHERE `idclientsPhones` =  '" . intval($_GET['deletePhone']) . "'");
        }
        header("Location: " . GR('deletePhone'));
        die();
    }



    if (isset($_GET['tgsms'])) {
        $phone = mfa(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `idclientsPhones`='" . mres($_GET['tgsms']) . "'"));
        $QR = RDS(5);
        mysqlQuery("UPDATE `clients` SET `clientscQR` = '" . $QR . "', `clientscQRset`=NOW() WHERE `idclients`= " . $client['idclients'] . " ");
        sendSms($phone['clientsPhonesPhone'], $client['clientsFName'] . ' ' . $client['clientsMName'] . ' Ваша ссылка для подключения Телеграм: ' . "\nhttps://t.me/infinitimedbot?start=" . $QR);
        header("Location: " . GR('tgsms'));
        exit();
    }



    if (
            isset($_POST['clientsAKNum']) &&
            isset($_POST['clientsLName']) &&
            isset($_POST['clientsFName']) &&
            isset($_POST['clientsMName']) &&
            isset($_POST['clientsGender'])
    ) {





        //idclients, GUID, clientsLName, clientsFName, clientsMName, clientsBDay, clientsAKNum, clientsAddedBy, clientsAddedAt, clientsGender, clientsIsNew, clientsCallerId, clientsCallerAdmin, clientsSource, clientsOldSince, clientsControl
        $updateQuery = "UPDATE `clients` SET "
                . " `clientsAKNum` = '" . mysqli_real_escape_string($link, trim($_POST['clientsAKNum'])) . "',"
                . " `clientsLName` = '" . mysqli_real_escape_string($link, trim($_POST['clientsLName'])) . "',"
                . " `clientsFName` = '" . mysqli_real_escape_string($link, trim($_POST['clientsFName'])) . "',"
                . " `clientsMName` = '" . mysqli_real_escape_string($link, trim($_POST['clientsMName'])) . "',"
                . " `clientsGender` = " . ($_POST['clientsGender'] !== '' ? intval($_POST['clientsGender']) : 'null') . ","
                . ((isset($_POST['idclientsSources'])) ? ((($_POST['idclientsSources'] ?? '') != '' ) ? ( "`clientsSource` = '" . intval($_POST['idclientsSources']) . "',") : "`clientsSource` = null,") : '')
                . (isset($_POST['clientsOldSince']) ? (" `clientsOldSince` = " . ($_POST['clientsOldSince'] !== '' ? ("'" . $_POST['clientsOldSince'] . "'") : 'null') . ",") : '')
                . " `clientsBDay` = " . ($_POST['clientsBDay'] !== '' ? ("'" . $_POST['clientsBDay'] . "'") : 'null') . ""
                . " WHERE `idclients` = '" . intval($_GET['client']) . "'";
//		if ($_USER['id'] == 176) {
//			print $updateQuery;
//			die();
//		}
        mysqlQuery($updateQuery);

        if (isset($_POST['newPhone']) && $_POST['newPhone'] != '') {
            mysqlQuery("INSERT INTO `clientsPhones` SET"
                    . " `clientsPhonesClient` = '" . intval($_GET['client']) . "',"
                    . " `clientsPhonesPhone` = '" . intval($_POST['newPhone']) . "'");
        }
        header("Location: " . GR());
        die();
    }



    if (isset($_POST['callPhone']) && trim($_POST['callPhone']) !== '' && isset($_POST['callType']) && trim($_POST['callType']) !== '') {

        printr($_POST);

        if (isset($_GET['call']) && intval(trim($_GET['call']))) {

            mysqlQuery("UPDATE `OCC_calls` SET"
                    . " `OCC_callsPhone` = '" . $_POST['callPhone'] . "',"
                    . " `OCC_callsType` = '" . $_POST['callType'] . "',"
                    . " `OCC_callsTime` = NOW(),"
                    . " `OCC_callsUser` = '" . $_USER['id'] . "'"
                    . "WHERE `idOCC_calls` = '" . intval(trim($_GET['call'])) . "'"
                    . "");
            if (isset($_POST['callComment']) && trim($_POST['callComment']) !== '') {
                mysqlQuery("DELETE FROM `OCC_callsComments` WHERE  `OCC_callsCommentsCall`='" . intval(trim($_GET['call'])) . "'");
                mysqlQuery("INSERT INTO `OCC_callsComments` SET "
                        . ""
                        . "`OCC_callsCommentsComment`='" . mysqli_real_escape_string($link, trim($_POST['callComment'])) . "'"
                        . ", `OCC_callsCommentsCall`='" . intval(trim($_GET['call'])) . "'");
            }




            if (($_POST['callType'] ?? false) == 4 && isset($_POST['recallDate']) && trim($_POST['recallDate']) !== '') {
                //idOCC_callsComments, OCC_callsCommentsCall, OCC_callsCommentsCaooment
                mysqlQuery("INSERT INTO `OCC_calls` SET "
                        . " `OCC_callsPhone`='" . $_POST['callPhone'] . "',"
                        . " `OCC_callsType` = '7',"
                        . " `OCC_callsUser` = " . sqlVON($_POST['iduserscall'] ? $_POST['iduserscall'] : $_USER['id'], 1) . ","
                        . " `OCC_callsTime` = '" . $_POST['recallDate'] . " 12:00:00',"
                        . " `OCC_callsClient` = '" . mres($_GET['client']) . "'"
                        . "");
            }
        } else {
            mysqlQuery("INSERT INTO `OCC_calls` SET"
                    . " `OCC_callsPhone` = '" . $_POST['callPhone'] . "',"
                    . " `OCC_callsType` = '" . $_POST['callType'] . "',"
                    . " `OCC_callsClient` = '" . mres($_GET['client']) . "',"
                    . " `OCC_callsUser` = '" . $_USER['id'] . "'");
            $idcalls = mysqli_insert_id($link);

            if (isset($_POST['callComment']) && trim($_POST['callComment']) !== '') {
                //idOCC_callsComments, OCC_callsCommentsCall, OCC_callsCommentsCaooment
                mysqlQuery("INSERT INTO `OCC_callsComments` SET "
                        . "`OCC_callsCommentsCall`='" . $idcalls . "',"
                        . "`OCC_callsCommentsComment`='" . mres(trim($_POST['callComment'])) . "'");
            }
            if (($_POST['callType'] ?? false) == 8 && isset($_POST['recallDate']) && trim($_POST['recallDate']) !== '') {
                mysqlQuery("INSERT INTO `OCC_callsConfirm` SET "
                        . " `OCC_callsConfirmCall`='" . $idcalls . "',"
                        . " `OCC_callsConfirmDate` = '" . mres($_POST['recallDate']) . "'"
                        . "");
            }
            if (($_POST['callType'] ?? false) == 4 && isset($_POST['recallDate']) && trim($_POST['recallDate']) !== '') {
                //idOCC_callsComments, OCC_callsCommentsCall, OCC_callsCommentsCaooment

                mysqlQuery("INSERT INTO `OCC_calls` SET "
                        . " `OCC_callsPhone`='" . $_POST['callPhone'] . "',"
                        . " `OCC_callsType` = '7',"
                        . " `OCC_callsUser` = " . sqlVON($_POST['iduserscall'] ? $_POST['iduserscall'] : $_USER['id'], 1) . ","
                        . " `OCC_callsClient` = '" . mres($_GET['client']) . "',"
                        . " `OCC_callsTime` = '" . $_POST['recallDate'] . " 12:00:00'"
                        . "");
                $idcalls = mysqli_insert_id($link);

                if (isset($_POST['callComment']) && trim($_POST['callComment']) !== '') {
                    //idOCC_callsComments, OCC_callsCommentsCall, OCC_callsCommentsCaooment
                    mysqlQuery("INSERT INTO `OCC_callsComments` SET "
                            . "`OCC_callsCommentsCall`='" . $idcalls . "',"
                            . "`OCC_callsCommentsComment`='" . mres(trim($_POST['callComment'])) . "'");
                }
            }
        }




        header("Location: " . GR('call'));
        die();
    }
}



$load['title'] = $pageTitle = 'КЦ ' . $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName'];
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] == 176) {
//	print $clientSQL;
//	printr($client);
}
?>

<style>
    .invalidPill {
        outline: 4px solid red;
    }
    .hidden {
        display: none;
    }
    .lightGrid {
        border-left: 1px solid silver;
        border-top:  1px solid silver;
        background-color: white;
    }

    .lightGrid>div:hover div {
        background-color: hsl(180, 80%, 95%);
        ;
    }

    .lightGrid>div>div {
        padding: 3px;
        border-right: 1px solid silver;
        border-bottom:  1px solid silver;
    }
    .serviceDescription {
        padding: 5px 10px;
        border: 1px solid red;
        background-color: white;
        font-style: italic;
        font-size: 14px;
        text-align: left;
    }
    .serviceDescription>p {
        color: black;
        margin: 0px;
        text-indent: 30px;
        padding: 4px;
    }

</style>
<?
if (!R(47)) {
    ?>E403R47<?
} else {
    include 'menu.php';

    function drowCalendar($date, $data) {
        global $_USER;
        $monthBegin = mystrtotime($date . '-01');
        $monthBeginWeekDay = date("N", $monthBegin);
        $monthLength = date('t', $monthBegin);
        $monthEnd = mystrtotime($date . '-' . $monthLength);
        $monthEndWeekDay = date("N", $monthEnd);
        print '<div style="display: inline-block;"><div class="calendar01">';
        if ($monthBeginWeekDay !== 1) {
            for ($n = 1; $n < $monthBeginWeekDay; $n++) {
                print '<div class="calendar01emptyCell"></div>';
            }
        }





        for ($d = 1; $d <= $monthLength; $d++) {
            $toDay = $date . '-' . ($d < 10 ? ('0' . $d) : $d);
            $todayData = $data[$toDay] ?? null;
            if ($_USER['id'] == 176) {
                
            }
            print '<div ' . (in_array(date("N", mystrtotime($toDay)), [6, 7]) ? ' style="color: pink;"' : '') . ' class="calendar01dateCell' . ($toDay == date('Y-m-d') ? ' today' : '') . ($toDay == ($_GET['date'] ?? date('Y-m-d')) ? ' thisDay' : '') . '" onclick="GETreloc(\'date\',\'' . $toDay . '\')">';

            if (($todayData['schedule'] ?? [])) {
                print '<div class="H' . $todayData['schedule'] . '' . (($todayData['dutes'][0] ?? false) ? ' duty' : '') . '"></div>';
            }
            print "<div>$d</div>";

            print '<div class="calendar01dateInnerCell' . ((($todayData['calls'] ?? 0) || ($todayData['services'] ?? 0)) ? ' calendar01dateInnerCellnotEmpty' : '') . '">';

            if (($todayData['calls'] ?? [])) {
                print '<div style="white-space: nowrap; display: block;"><i class="fas fa-phone-alt"></i>x' . ($todayData['calls']) . '</div><br>';
            }
            if (($todayData['services'] ?? 0)) {
                print '<div style="white-space: nowrap; display: block;"><i class="fas fa-notes-medical"></i>x' . ($todayData['services']) . '</div><br>';
            }
            if (($todayData['visit'] ?? false)) {
                print '<div style="position: absolute; border: 1px solid green; top: 0px; left: 0px; width: 100%; height: 100%; border-radius: 50%; display: block;"></div>';
            }
            print '</div></div>';
        }





        if ($monthEndWeekDay !== 7) {
            for ($n = $monthEndWeekDay; $n < 7; $n++) {
                print '<div class="calendar01emptyCell"></div>';
            }
        }
        print '</div></div>';
    }
    ?>
    <? ?>

    </div>

    </div>
    <? $clientsPhones = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' AND isnull(`clientsPhonesDeleted`)")); ?>
    <div class="box" style="display: block;">
        <div class="box-body">
            <?
            if ($client ?? 0) {
//				printr($_POST);
                ?>
                <h2 style="background-color: white;">
                    <?= $client['clientsLName']; ?>
                    <?= $client['clientsFName']; ?>
                    <?= $client['clientsMName']; ?>
                    <?
                    if ($client['clientsBDay']) {
                        ?>
                        <?= human_plural_form(secondsToTimeObj(time() - mystrtotime($client['clientsBDay']))->format('%y'), ['год', 'года', 'лет'], true); ?>

                        <?
                    }
                    ?>
                </h2>

                <div style="text-align: center;">
                    <div style="display: inline-block; text-align: left; border: 0px solid red;">
                        <div style="display: inline-block;">
                            <div style="border: 0px solid red; display: grid; grid-template-columns: auto auto auto;">

                                <?
                                if (isset($_GET['client']) && validateDate($_GET['date'] ?? date("Y-m-d"))) {

//
//								if ($_GET['service'] ?? 0) {
//									$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . $_GET['service'] . "'"));
////						printr($service);
//								}

                                    $clientProcedures = query2array(mysqlQuery("SELECT "
                                                    . " * FROM `servicesApplied` "
                                                    . " LEFT JOIN `services` on (`idservices` = `servicesAppliedService`) "
                                                    . " WHERE `servicesAppliedClient`='" . FSI($_GET['client']) . "' AND `servicesAppliedDate` = '" . ($_GET['date'] ?? date("Y-m-d")) . "'"));
//					printr($clientProcedures[0]);

                                    usort($clientProcedures, function ($a, $b) {
                                        return mystrtotime($a['servicesAppliedTimeBegin']) <=> mystrtotime($b['servicesAppliedTimeBegin']);
                                    });
//					printr($clientProcedures);
                                    ?>


                                    <div style=" border: 0px solid orange;">
                                        <div class = "box neutral" style="vertical-align: top;">
                                            <div class = "box-body">
                                                <h2>
                                                    <a href="#" onclick="document.querySelector('#personalData').style.display = 'block'; document.querySelector('#passportData').style.display = 'none';">Личные</a>/<a href="#" onclick="document.querySelector('#personalData').style.display = 'none';document.querySelector('#passportData').style.display = 'block';">паспортные данные</a>
                                                </h2>
                                                <div id="personalData">

                                                    <? if ((R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id'])) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) { ?>
                                                        <form action="<?= GR(); ?>" method="POST" id="form">
                                                        <? } ?>
                                                        <div style="display: inline-block; padding: 10px;">

                                                            <div style="display: grid; grid-gap: 5px; grid-template-columns: auto auto;">
                                                                <div>№ карты</div>
                                                                <div><input<?= (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) ? '' : ' readonly' ?> type="text" name="clientsAKNum" value="<?= $client['clientsAKNum']; ?>"></div>
                                                                <div>Фамилия</div>
                                                                <div><input<?= (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) ? '' : ' readonly' ?> type="text" name="clientsLName" value="<?= $client['clientsLName']; ?>"></div>

                                                                <div>Имя</div>
                                                                <div><input<?= (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) ? '' : ' readonly' ?> type="text" name="clientsFName" value="<?= $client['clientsFName']; ?>"></div>

                                                                <div>Отчество</div>
                                                                <div><input<?= (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) ? '' : ' readonly' ?> type="text" name="clientsMName" value="<?= $client['clientsMName']; ?>"></div>
                                                                <div>Дата рождения:</div>
                                                                <div><input<?= (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) ? '' : ' readonly' ?> type="date" name="clientsBDay" value="<?= $client['clientsBDay']; ?>"></div>

                                                                <div>Пол</div>
                                                                <div>
                                                                    <select<?= (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) ? '' : ' disabled' ?> type="text" name="clientsGender">
                                                                        <option value=""></option>
                                                                        <option value="0"<?= $client['clientsGender'] == '0' ? ' selected' : ''; ?>>Женский</option>
                                                                        <option value="1"<?= $client['clientsGender'] == '1' ? ' selected' : ''; ?>>Мужской</option>
                                                                    </select>
                                                                </div>



                                                                <div>Телефон<?= count($clientsPhones) > 1 ? 'ы' : ''; ?></div>
                                                                <div>
                                                                    <? if (count($clientsPhones)) { ?>
                                                                        <div style="display: grid; grid-template-columns: 1fr auto auto;" class="lightGrid">
                                                                            <?
                                                                            foreach ($clientsPhones as $phone) {
//	 printr($phone);
                                                                                ?>
                                                                                <div style="display: contents;">
                                                                                    <div><?= R(147) ? $phone['clientsPhonesPhone'] : '...' . substr($phone['clientsPhonesPhone'], -4); ?></div>
                                                                                    <div>
                                                                                        <? if (!$client['clientsTG']) {
                                                                                            ?><i class="fab fa-telegram-plane" style="color: lightblue; cursor: pointer;" onclick="GR({'tgsms':<?= $phone['idclientsPhones']; ?>})"></i><? }
                                                                                        ?>

                                                                                    </div>
                                                                                    <div><i class="far fa-times-circle" style="color: red; cursor: pointer;" onclick="GR({'deletePhone':<?= $phone['idclientsPhones']; ?>})"></i></div>
                                                                                </div>
                                                                            <? }
                                                                            ?>

                                                                        </div>
                                                                    <? } ?>
                                                                    <input type="text"<?= (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) ? '' : ' readonly' ?> oninput="digon();" name="newPhone" placeholder="добавить телефон">
                                                                </div>
                                                                <div style="padding: 10px;">
                                                                    Телеграм
                                                                </div>
                                                                <div style=" text-align: center; padding: 10px;">
                                                                    <? if ($client['clientsTG']) {
                                                                        ?>Подключён<?
                                                                    } else {
                                                                        ?>Отказ: 
                                                                        <?
                                                                        if ($client['clientsTGrefuse']) {
                                                                            ?>
                                                                            <span style="color: red;"><?= htmlentities($client['clientsTGrefuse']); ?></span>
                                                                            <?
                                                                        } else {
                                                                            ?>
                                                                            <span style="cursor: pointer;" onclick="if (reason = prompt('Причина отказа')) {
                                                                                                            GR({TG_refuse: reason});
                                                                                                        }">Указать причину отказа</span>
                                                                              <? }
                                                                              ?>
                                                                          <? } ?>
                                                                </div>

                                                                <div>Дата перехода<br>в статус<br>вторичного клиента:</div>
                                                                <div style="align-self: center;"><input<?= R(75) ? '' : ' readonly' ?> type="date" name="clientsOldSince" value="<?= $client['clientsOldSince']; ?>"></div>
                                                                <div>Добавлен:</div>
                                                                <div><?
                                                                    $addedBy = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . $client['clientsAddedBy'] . "'"));
                                                                    ?><?= $addedBy['usersLastName'] ?? 'Не известно'; ?> <?= $addedBy['usersFirstName'] ?? ''; ?></div>


                                                                <div>Источник:</div>
                                                                <div style="align-self: center;">
                                                                    <?
                                                                    if (!$client['clientsSource'] || $client['clientsAddedBy'] == $_USER['id'] || $_USER['id'] == 176 || R(187)) {
                                                                        ?>
                                                                        <select name="idclientsSources" autocomplete="off"
                                                                        <? if (!$client['clientsSource'] || $client['clientsAddedBy'] == $_USER['id'] || $_USER['id'] == 176 || R(187)) {
                                                                            ?>
                                                                                    onchange="fetch('IO.php', {
                                                                                                                    body: JSON.stringify(
                                                                                                                            {
                                                                                                                                action: 'changesource',
                                                                                                                                idclients: <?= $client['idclients']; ?>,
                                                                                                                                source: this.value
                                                                                                                            }
                                                                                                                    ),
                                                                                                                    credentials: 'include',
                                                                                                                    method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                                                                                                                }).then(result => result.text()).then(async function (text) {
                                                                                                                    try {
                                                                                                                        let jsn = JSON.parse(text);
                                                                                                                        if (jsn.success) {
                                                                                                                            MSG({type: 'success', text: 'ok'});
                                                                                                                        }
                                                                                                                    } catch (e) {

                                                                                                                    }
                                                                                                                });
                                                                                    "
                                                                                <? }
                                                                                ?>
                                                                                >
                                                                            <option></option>
                                                                            <?
                                                                            foreach (query2array(mysqlQuery("SELECT * FROM `clientsSources` ORDER BY `clientsSourcesDeleted`, `clientsSourcesName`")) as $source) {
                                                                                ?>
                                                                                <option <?= $client['clientsSource'] == $source['idclientsSources'] ? 'selected' : ''; ?> <?= $source['clientsSourcesDeleted'] ? 'disabled' : ''; ?> value="<?= $source['idclientsSources'] ?>"><?= $source['clientsSourcesName'] ?></option>
                                                                                <?
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                        <?
                                                                    } else {
                                                                        ?><?= $client['clientsSourcesName']; ?><?
                                                                    }
                                                                    ?>

                                                                </div>
                                                                <div>Телефонная база:</div>
                                                                <div><?= $client['RCC_phonesBasesNameShort'] ?? 'не указана'; ?><?= ($client['RCC_phonesBasesAdded'] ?? false) ? date(" от d.m.Yг", mystrtotime($client['RCC_phonesBasesAdded'])) : '' ?></div>
                                                                <script>
                                                                    function changeStatus(client, status) {
                                                                        fetch('IO.php', {
                                                                            body: JSON.stringify({idclientStatus: status, client: client}),
                                                                            credentials: 'include',
                                                                            method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                                                                        });
                                                                    }
                                                                </script>

                                                                <div>Статус клиента:</div>
                                                                <div style="align-self: center;">
                                                                    <?
                                                                    if (R(109)) {
                                                                        ?><select onchange="changeStatus(<?= $client['idclients']; ?>, this.value);">
                                                                            <option></option>
                                                                            <?
                                                                            foreach (query2array(mysqlQuery("SELECT * FROM `clientsStatuses`")) as $status) {
                                                                                ?>
                                                                                <option <?= ($client['clientStatusStatus'] ?? null) == $status['idclientsStatuses'] ? 'selected' : ''; ?>  value="<?= $status['idclientsStatuses'] ?>"><?= $status['clientsStatusesName'] ?></option>
                                                                                <?
                                                                            }
                                                                            ?>
                                                                        </select><?
                                                                    } else {
                                                                        ?><?= $client['clientsStatusesName']; ?><?
                                                                    }
                                                                    ?>

                                                                </div>

                                                                <?
                                                                if (R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id']) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) {
                                                                    ?>
                                                                    <div style="grid-column: span 2; padding-top: 50px; text-align: center;"><input type="button" value="Сохранить" onclick="qs('#form').submit();"></div>
                                                                <? } ?>
                                                            </div>
                                                        </div>
                                                        <? if ((R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id'])) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) { ?>
                                                        </form>
                                                    <? } ?>

                                                </div>
                                                <div id="passportData" style=" display: none;">
                                                    <?
                                                    $passports = query2array(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `clientsPassportsClient`='" . $client['idclients'] . "' ORDER BY `clientsPassportsAdded`,`idclientsPassports`"));

                                                    if (count($passports)) {
                                                        $passport = $passports[count($passports) - 1];
                                                    } else {
                                                        $passport = null;
                                                    }
//													printr($passport);
                                                    ?>

                                                    <form action="<?= GR(); ?>" method="POST" id="formPassport">
                                                        <input type="hidden" name="action" value="savePassportData">
                                                        <div style="display: inline-block; padding: 10px;">
                                                            <div class="lightGrid" style="display: grid; grid-template-columns: auto auto; min-width: 500px;">
                                                                <div style="display: contents;">
                                                                    <div>Серия номер</div>
                                                                    <div><input type="text" name="clientsPassportNumber"  value="<?= htmlentities($passport['clientsPassportNumber'] ?? ''); ?>"></div>
                                                                </div>
                                                                <div style="display: contents;">
                                                                    <div>Код подразделения</div>
                                                                    <div><input type="text" name="clientsPassportsCode" id="clientsPassportsCode" oninput="checkPassportCode(this.value);"  value="<?= htmlentities($passport['clientsPassportsCode'] ?? ''); ?>"><ul class="suggestions" id="clientsPassportsCodeSuggestion"></ul>
                                                                    </div>
                                                                </div>
                                                                <div style="display: contents;">
                                                                    <div>Кем выдан</div>
                                                                    <div><textarea style="width: 100%; resize: none; height: 4rem; border-radius: 0px; padding: 3px; border: 1px solid silver;" id="clientsPassportsDepartment" name="clientsPassportsDepartment"><?= htmlentities($passport['clientsPassportsDepartment'] ?? ''); ?></textarea></div>
                                                                </div>

                                                                <div style="display: contents;">
                                                                    <div>Дата выдачи</div>
                                                                    <div><input type="date" name="clientsPassportsDate"  value="<?= htmlentities($passport['clientsPassportsDate'] ?? ''); ?>"></div>
                                                                </div>
                                                                <div style="display: contents;">
                                                                    <div>Место рождения</div>
                                                                    <div><textarea style="width: 100%; resize: none; height: 4rem; border-radius: 0px; padding: 3px; border: 1px solid silver;" name="clientsPassportsBirthPlace"><?= htmlentities($passport['clientsPassportsBirthPlace'] ?? ''); ?></textarea></div>
                                                                </div>
                                                                <div style="display: contents;">
                                                                    <div>Адрес регистрации:</div>
                                                                    <div><textarea style="width: 100%; resize: none; height: 4rem; border-radius: 0px; padding: 3px; border: 1px solid silver;"  type="text" name="clientsPassportsRegistration"><?= htmlentities($passport['clientsPassportsRegistration'] ?? ''); ?></textarea></div>
                                                                </div>
                                                                <div style="display: contents;">
                                                                    <div>Адрес факт. проживания:</div>
                                                                    <div><textarea style="width: 100%; resize: none; height: 4rem; border-radius: 0px; padding: 3px; border: 1px solid silver;"  type="text" name="clientsPassportsResidence"><?= htmlentities($passport['clientsPassportsResidence'] ?? ''); ?></textarea></div>
                                                                </div>
                                                            </div>
                                                            <?
                                                            foreach (query2array(mysqlQuery("SELECT * FROM `entities` WHERE `identities`=1")) as $entity) {
                                                                ?>
                                                                <a href="/sync/utils/word/baseContract.php?client=<?= $client['idclients']; ?>&entity=<?= $entity['identities']; ?>" target="_blank">Печать договора (<?= $entity['entitiesName']; ?>) <?= ($client['clientsContractDate'] ? (' от ' . date("d.m.Yг", mystrtotime($client['clientsContractDate']))) : '<b>Ещё не распечатан</b>'); ?></a><br>
                                                                <?
                                                            }
                                                            ?>
                                                            <?
                                                            foreach (query2array(mysqlQuery("SELECT * FROM `entities` WHERE `identities`=1")) as $entity) {
                                                                ?>
                                                                <a href="/sync/utils/word/card20220330.php?client=<?= $client['idclients']; ?>&entity=<?= $entity['identities']; ?>" target="_blank">Печать медкарты нов. (<?= $entity['entitiesName']; ?>) <?= ($client['clientsContractDate'] ? (' от ' . date("d.m.Yг", mystrtotime($client['clientsContractDate']))) : '<b>Ещё не распечатана</b>'); ?></a><br>
                                                                <?
                                                            }
                                                            ?>
                                                            <?
                                                            foreach (query2array(mysqlQuery("SELECT * FROM `entities` WHERE `identities`=1")) as $entity) {
                                                                ?>
                                                                <a href="/sync/utils/word/card20220801.025.php?client=<?= $client['idclients']; ?>&entity=<?= $entity['identities']; ?>" target="_blank">Печать медкарты 025 (Амбулаторная)</a><br>
                                                                <?
                                                            }
                                                            ?>
                                                            <?
                                                            foreach (query2array(mysqlQuery("SELECT * FROM `entities` WHERE `identities`=1")) as $entity) {
                                                                ?>
                                                                <a href="/sync/utils/word/card20220801.043.php?client=<?= $client['idclients']; ?>&entity=<?= $entity['identities']; ?>" target="_blank">Печать медкарты 043 (Стоматология)</a><br>
                                                                <?
                                                            }
                                                            ?>
                                                            <a href="/sync/utils/word/dentalface.php?client=<?= $client['idclients']; ?>" target="_blank">Печать обложки карты стоматология</a><br>
                                                        </div>

                                                        <div style="grid-column: span 2; padding-top: 30px; text-align: center;"><input type="button" value="Сохранить" onclick="qs('#formPassport').submit();"></div>
                                                    </form>
                                                    <? if ((R(73) || (R(94) && $client['clientsAddedBy'] == $_USER['id'])) || (R(113) && $client['clientsAddedByUsersGroup'] == 12)) { ?>	<? } ?>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        function checkPassportCode(query) {

                                            let list = document.querySelector('#clientsPassportsCodeSuggestion');
                                            clear(list);
                                            if (query.length >= 6) {
                                                let url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/fms_unit";
                                                let token = "d6bafcbd7bc2b219d5718701e479f5f6363ca047";
                                                let options = {
                                                    method: "POST",
                                                    mode: "cors",
                                                    headers: {
                                                        "Content-Type": "application/json",
                                                        "Accept": "application/json",
                                                        "Authorization": "Token " + token
                                                    },
                                                    body: JSON.stringify({query: query})
                                                };
                                                fetch(url, options)
                                                        .then(response => response.json())
                                                        .then(result => {
                                                            //															console.log(result.suggestions);

                                                            if (((result || {}).suggestions || []).length > 0) {
                                                                result.suggestions.forEach(suggestion => {
                                                                    let li = el('li', {innerHTML: `<span class="mask"></span>${suggestion.value}`});
                                                                    li.addEventListener('click', function () {
                                                                        document.querySelector('#clientsPassportsDepartment').value = suggestion.value;
                                                                        document.querySelector('#clientsPassportsCode').value = suggestion.data.code;
                                                                        clear(list);
                                                                    });
                                                                    list.appendChild(li);
                                                                });
                                                            }
                                                        })
                                                        .catch(error => console.log("error", error));
                                            }



                                        }

                                    </script>





                                    <div style="border: 0px solid blue;">
                                        <? if (R(82)) { ?>
                                            <div class = "box neutral" style="vertical-align: top;">
                                                <div class = "box-body">
                                                    <h2>
                                                        История звонков
                                                    </h2>
                                                    <div id="callsWrapper" style="overflow-y: scroll; max-height: 300px;">
                                                        <div class="lightGrid" id="callsWrapperContent" style="display: grid; grid-template-columns: auto auto auto auto auto auto;">
                                                            <div style="display: contents;">
                                                                <div></div>
                                                                <div class="B C">дата</div>
                                                                <div class="B C">оператор</div>
                                                                <div class="B C">Результат звонка</div>
                                                                <div class="B C">комментарий</div>
                                                                <div class="B C"></div>
                                                            </div>

                                                            <?
                                                            $smses = query2array(mysqlQuery("SELECT "
                                                                            . " `smsTime` AS `OCC_callsTime`,"
                                                                            . " DATE(`smsTime`) AS `OCC_callsDate` ,"
                                                                            . " `usersLastName`,"
                                                                            . " `usersFirstName`,"
                                                                            . " `smsText`,"
                                                                            . " `smsState`"
                                                                            . ""
                                                                            . " FROM `sms` "
                                                                            . " LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `smsPhone`)"
                                                                            . " LEFT JOIN `users` ON (`idusers` = `smsUser`)"
                                                                            . " WHERE `clientsPhonesClient` = '" . $client['idclients'] . "';"));

                                                            $calls = query2array(mysqlQuery(""
                                                                            . "SELECT *, DATE(`OCC_callsTime`) AS `OCC_callsDate` "
                                                                            . "FROM `OCC_calls` "
                                                                            . "LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`)"
                                                                            . "LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
                                                                            . "LEFT JOIN `OCC_callTypes` ON (`idOCC_callTypes` = `OCC_callsType`)"
                                                                            . "LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
                                                                            . "LEFT JOIN `OCC_callsConfirm` ON (`OCC_callsConfirmCall` = `idOCC_calls`)"
                                                                            . "WHERE `clientsPhonesClient` = '" . $client['idclients'] . "'"));

                                                            if (1) {
                                                                $CDR_phones7 = array_map(function ($elem) {
                                                                    $elem[0] = '7';
                                                                    return $elem;
                                                                }, array_column($clientsPhones, 'clientsPhonesPhone'));

                                                                $CDR_phones = array_map(function ($elem) {
                                                                    $elem[0] = '8';
                                                                    return $elem;
                                                                }, array_column($clientsPhones, 'clientsPhonesPhone'));

                                                                $CDR_calls = $CDR_link ? query2array(mysqlQuery("SELECT 
																	`calldate` AS `OCC_callsTime`,
																	DATE(`calldate`) AS `OCC_callsDate`,
																	`src`,
																	`dst`,
																	`channel`,
																	`uniqueid`
																	FROM `asterisk`.`cdr` WHERE 
																	`calldate` > '2021-01-01 00:00:00' AND 
																(`dst` IN ('" . implode("','", array_merge(($CDR_phones7 ?? []), ($CDR_phones ?? []))) . "') OR `src` IN ('" . implode("','", array_merge(($CDR_phones7 ?? []), ($CDR_phones ?? []))) . "')) order by id;", $CDR_link)) : [];
                                                            }
                                                            if ($_USER['id'] == 176) {
//																print implode("','", $CDR_phones);
//																printr($CDR_calls);
                                                            }
                                                            if (1) {
                                                                $calls = array_merge($calls, $CDR_calls);
                                                            }



                                                            if (($_GET['showsms'] ?? false)) {
                                                                $calls = array_merge($calls, $smses);
                                                            }


                                                            if (!count($calls)) {
                                                                ?>
                                                                <div style="display: contents;">
                                                                    <div style="grid-column: 1/-1;" class="C B">Нет информации о звонках</div>
                                                                </div>
                                                                <?
                                                            } else {
                                                                $serviceAppliedAt = query2array(mysqlQuery("SELECT *, DATE(`servicesAppliedAt`) AS `servicesAppliedAtDate` FROM `servicesApplied` WHERE `servicesAppliedClient` = '" . $client['idclients'] . "' AND isnull(`servicesAppliedDeleted`)"));
                                                                usort($calls, function ($a, $b) {
                                                                    return $a['OCC_callsTime'] <=> $b['OCC_callsTime'];
                                                                });
                                                                if ($_USER['id'] == 176) {
//																	printr($calls);
                                                                }
                                                                foreach ($calls as $call) {
                                                                    if (($_GET['call'] ?? '') == ($call['idOCC_calls'] ?? false)) {
                                                                        $color = 'red';
                                                                    } else {
                                                                        $color = 'black';
                                                                    }
                                                                    ?>
                                                                    <div style="grid-column: 1/-1;" class="C B"><?
//														printr($call);
                                                                        ?></div>
                                                                    <div style="display: contents;">

                                                                        <?
                                                                        if (($call['uniqueid'] ?? false) && R(180) && DBNAME == 'warehouse') {
                                                                            $ch = curl_init('http://192.168.128.100/ivr_stat/audio/' . $call['uniqueid'] . '.mp3');
                                                                            curl_setopt($ch, CURLOPT_NOBODY, true);
                                                                            curl_exec($ch);
                                                                            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                                                            curl_close($ch);
                                                                            if ($retcode == 200) {
                                                                                $cdrtext = '<a target="_blank" href="http://192.168.128.100/ivr_stat/audio/' . $call['uniqueid'] . '.mp3' . '">Послушать звонок</a>';
                                                                            } else {
                                                                                $cdrtext = 'Нет записи звонка';
                                                                            }
                                                                            ?>
                                                                            <div style="text-align: center;"></div>
                                                                            <div><?= date("d.m.Y", mystrtotime($call['OCC_callsTime'])); ?></div>
                                                                            <div>IP <?
                                                                                preg_match('/(\d{3})(?:-)/', $call['channel'], $matches);
                                                                                print $matches[1] ?? 'нет оператора';
                                                                                ?>
                                                                                <span style="font-size: 10px;"><?= $call['uniqueid'] ?? ''; ?></span>
                                                                            </div>
                                                                            <div style="grid-column: span 3; "><?= $cdrtext ?? 'ОШИБКА'; ?></div>
                                                                            <?
                                                                        }


                                                                        if ($call['smsText'] ?? false) {
                                                                            ?>
                                                                            <div style="text-align: center;">
                                                                                <i class="fas fa-envelope" style=" color: #444;"></i><br>
                                                                                <? if ($call['smsState'] == 'delivered') {
                                                                                    ?><i class="fas fa-check-square" style="color: green;" title="Доставлено" ></i><?
                                                                                } elseif ($call['smsState'] == 'rejected') {
                                                                                    ?><i class="fas fa-exclamation-circle" style="color: red;" title="Отклонено провайдером"></i><?
                                                                                } else {
                                                                                    ?><i class="fas fa-exclamation-triangle" style="color: orange;" title="Не доставлено"></i><? } ?>
                                                                            </div>
                                                                            <div><?= date("d.m.Y", mystrtotime($call['OCC_callsTime'])); ?></div>
                                                                            <div><?= $call['usersLastName'] ?? 'Рассылка'; ?></div>
                                                                            <div style="grid-column: span 3; "><?= htmlentities($call['smsText']); ?></div>
                                                                            <?
                                                                        }

                                                                        if ($call['idOCC_calls'] ?? false) {
                                                                            ?>
                                                                            <div></div>
                                                                            <div style="color: <?= $color; ?>; display: flex; align-items: center;"><?= date("d.m.Y", mystrtotime($call['OCC_callsTime'])); ?></div>
                                                                            <div style="color: <?= $color; ?>; display: flex; align-items: center;"><?= $call['usersLastName']; ?>
                                                                            </div>
                                                                            <div style="color: <?= $color; ?>; display: flex; align-items: center;"><span><?= $call['OCC_callTypesName']; ?>
                                                                                    <? if ($call['OCC_callsConfirmDate'] ?? false) {
                                                                                        ?> на <?= date('d.m', mystrtotime($call['OCC_callsConfirmDate'])); ?><? }
                                                                                    ?>
                                                                                    <?
                                                                                    if (($countserviceAppliedAt = count(array_filter($serviceAppliedAt, function ($el) {
                                                                                                global $call;
                                                                                                return $el['servicesAppliedAtDate'] === $call['OCC_callsDate'];
                                                                                            })))) {
                                                                                        ?> <span style="color: gray;"> +  <i class="fas fa-notes-medical"></i>x<?= $countserviceAppliedAt; ?></span>
                                                                                        <?
                                                                                    }
                                                                                    ?></span></div>
                                                                            <div style="color: <?= $color; ?>; display: flex; align-items: center;"><?= ($call['OCC_callsCommentsComment']); ?></div>
                                                                            <div class="C">
                                                                                <?
                                                                                if (date("Y-m-d") == date("Y-m-d", mystrtotime($call['OCC_callsTime'])) || $call['idOCC_callTypes'] == 7) {

                                                                                    if (($call['idOCC_calls'] ?? false)) {
                                                                                        if (($_GET['call'] ?? '') == ($call['idOCC_calls'] ?? false)) {
                                                                                            ?>
                                                                                            <a href="<?= GR('call'); ?>"><input type="button" value="Отмена"  style="font-size: 1.5em; color: red;"></a>
                                                                                        <? } else {
                                                                                            ?>
                                                                                            <a href="<?= GR('call', $call['idOCC_calls']); ?>"><input type="button" value="Редактировать"  style="color: green;"></a>
                                                                                            <?
                                                                                        }
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        <? }
                                                                        ?>

                                                                    </div>
                                                                    <?
                                                                }
                                                            }
//                                                            logTG("\$strtotime:\nLength: " . count($strtotime) . "\nCount: " . $strtotime_cnt);
                                                            ?>
                                                        </div>

                                                    </div>
                                                    <input type="checkbox" <?= ($_GET['showsms'] ?? false) ? 'checked' : ''; ?> id="showsms" onclick="GR({showsms: this.checked || null});"><label for="showsms">Показать смс</label>

                                                    <script>
                                                        window.requestAnimationFrame(function () {
                                                            qs('#callsWrapper').scrollTop = qs('#callsWrapperContent').offsetHeight;
                                                        });
                                                    </script>
                                                    <? //printr($calls[0]);                                                        ?>
                                                    <div style="margin-top: 10px; border-top: 3px solid gray; padding-top: 10px;">
                                                        <?
                                                        $loadedPhone = '';
                                                        $loadedType = '';
                                                        $loadedComment = '';
                                                        if ($_GET['call'] ?? false) {
                                                            $loadedCall = mfa(mysqlQuery("SELECT * "
                                                                            . "FROM `OCC_calls` "
                                                                            . "LEFT JOIN `OCC_callsComments` ON (`OCC_callsCommentsCall` = `idOCC_calls`)"
                                                                            . "WHERE `idOCC_calls` ='" . intval($_GET['call']) . "'"));
//													printr($loadedCall);
                                                            $loadedPhone = $loadedCall['OCC_callsPhone'];
                                                            $loadedType = $loadedCall['OCC_callsType'];
                                                            $loadedComment = $loadedCall['OCC_callsCommentsComment'];
                                                        }
                                                        ?>
                                                        <form action="<?= GR(); ?>" method="post">
                                                            <div style="display: grid; grid-gap: 10px;">
                                                                <div style="display: inline-block;">
                                                                    <input type="text" id="callSrc" oninput="digon(); localStorage.setItem('callSrc', this.value);" size="3" style="display: inline-block; width: auto;">
                                                                    <script>
                                                                        callSrc.value = localStorage.getItem('callSrc');
                                                                    </script>
                                                                    <select autocomplete="off"  id="callDist" name="callPhone"  style="width: auto;">
                                                                        <?
                                                                        foreach ($clientsPhones as $phone) {
                                                                            ?><option <?= $loadedPhone == $phone['idclientsPhones'] ? ' selected' : '' ?> value="<?= $phone['idclientsPhones']; ?>">
                                                                                <?= R(147) ? $phone['clientsPhonesPhone'] : '...' . substr($phone['clientsPhonesPhone'], -4); ?></option>
                                                                        <? } ?>
                                                                    </select>
                                                                    <select style="width: auto;" autocomplete="off"  id="voipserver" onchange="localStorage.setItem('voipserver', this.value);">
                                                                        <option value="">Сервер телефонии</option>
                                                                        <?
                                                                        foreach (query2array(mysqlQuery("SELECT * FROM `voipservers`")) as $voipserver) {
                                                                            ?><option value="<?= $voipserver['idvoipservers']; ?>"><?= $voipserver['voipserversName']; ?></option><?
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                    <script>
                                                                        voipserver.value = localStorage.getItem('voipserver') || '';
                                                                    </script>
                                                                    <input type="button" onclick="if (!document.querySelector(`#voipserver`).value) {
                                                                                                MSG('Укажите сервер телефонии.');
                                                                                                return false;
                                                                                            }
                                                                                            ;
                                                                                            dialPhone({src: callSrc.value, dist: (callDist.value || ''), viopserver: document.querySelector(`#voipserver`).value});" value="Набрать">
                                                                </div>
                                                                <script>
                                                                    function dialPhone(data) {
                                                                        let {src, dist, viopserver} = data;
                                                                        if (!dist) {
                                                                            MSG('Укажите номер телефона');
                                                                            //																			fetch('/sync/api/icq/jse.php', {body: JSON.stringify({
                                                                            //																					errorMessage: 'Звонок вникуда'
                                                                            //																				}), credentials: 'include', method: 'POST', headers: new Headers({'Content-Type': 'application/json'})});
                                                                            return false;
                                                                        }
                                                                        fetch(`/sync/utils/voip/call3.php?src=${src}&idclientsPhones=${dist}&viopserver=${viopserver}`).then(result => result.text()).then(async function (text) {
                                                                            try {
                                                                                let jsn = JSON.parse(text);
                                                                                ///////////////////////////
                                                                                //																					console.error(jsn);
                                                                                if (!(jsn.connected || {}).success) {
                                                                                    MSG(rt(
                                                                                            'Ошибка соединения,<br>попробуйте ещё раз.',
                                                                                            'У меня не получилось,<br>попробуйте ещё раз.',
                                                                                            'Тупит связь,<br>попробуйте ещё раз.',
                                                                                            'Не соединяется,<br>попробуйте ещё раз.',
                                                                                            'Ох... мне тоже надоело,<br>но надо пытаться...<br>Давайте ещё разок.',
                                                                                            'Когда-нибудь это починят, <br>а пока попробуйте ещё раз.',
                                                                                            ));
                                                                                } else {
                                                                                    if (!(jsn.dial || {}).success) {
                                                                                        MSG(`Ошибка в телефонном номере <br>"${dist}"<br>Проверьте правильность и повторите.`);
                                                                                    } else {
                                                                                        MSG({type: 'success', text: rt(
                                                                                                    'Звоню',
                                                                                                    'Набираю',
                                                                                                    'Звонок пошёл',
                                                                                                    'Ура, есть контакт!',
                                                                                                    'Ало-ало? ',
                                                                                                    'Успех!',
                                                                                                    ), autoDismiss: 2000});
                                                                                    }
                                                                                }

                                                                                /*
                                                                                 connected: Object { success: true, time: 0.005635976791381836 }
                                                                                 dial: Object { success: true, time: 0.2061021327972412 }
                                                                                 */
                                                                                ///////////////////////////


                                                                            } catch (e) {
                                                                                MSG("Ошибка ответа сервера. <br><br><i>" + e + "</i>");
                                                                            }
                                                                        });
                                                                        ;
                                                                    }
                                                                </script>
                                                                <select autocomplete="off" name="callType" onchange="if (this.value == '4' || this.value == '8') {//4 перезвонить, 8 подтверждение.
                                                                                            qs('#recall').style.display = 'grid';
                                                                                        } else {
                                                                                            qs('#recall').style.display = 'none';
                                                                                        }
                                                                                        //																		console.log(this.value);">
                                                                    <option value="">Результат звонка</option>
                                                                    <?
                                                                    foreach (query2array(mysqlQuery("SELECT * FROM `OCC_callTypes` WHERE NOT `idOCC_callTypes` = 7 ORDER BY `OCC_callTypesName`")) as $callType) {
                                                                        ?><option <?= $loadedType == $callType['idOCC_callTypes'] ? ' selected' : '' ?>  value="<?= $callType['idOCC_callTypes']; ?>"><?= $callType['OCC_callTypesName']; ?></option>
                                                                    <? } ?>
                                                                </select>


                                                                <div style="display: none; grid-template-columns: auto auto; grid-gap: 5px; margin: 10px;" id="recall">
                                                                    <div>Перезвонить/подтверждение</div>
                                                                    <div><input type="date" name="recallDate" value="<?= $_GET['date'] ?? date('Y-m-d'); ?>"></div>


                                                                    <? if (1 || R(72)) { ?>

                                                                        <div>
                                                                            Оператор:
                                                                        </div>

                                                                        <div>111
                                                                            <select id="iduserscall"  name="iduserscall" autocomplete="off">
                                                                                <option></option>
                                                                                <?
                                                                                foreach (query2array(mysqlQuery("SELECT * FROM `usersPositions` left join `users` ON (`idusers`=`usersPositionsUser`) WHERE isnull(`usersDeleted`) AND `usersPositionsPosition`  in (32,79)  ORDER BY `usersPositionsPosition`,`usersLastName`, `usersFirstName`")) as $user) {
                                                                                    ?><option value="<?= $user['idusers']; ?>"><?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?></option><?
                                                                                }
                                                                                ?>
                                                                            </select>
                                                                        </div>

                                                                    <? } ?>




                                                                </div>


                                                                <textarea name="callComment" style="display: inline-block;
                                                                          font-size: 15px;
                                                                          padding: 0.2em 0.5em;
                                                                          background-color: white;
                                                                          color: #000000b0;
                                                                          border: 0;
                                                                          border-radius: 10px;
                                                                          box-shadow: 0.2em 0.2em 7px rgba(122,122,122,0.5);
                                                                          width: 100%;
                                                                          font-weight: 600; resize: none;" placeholder="Комментарий к звонку"><?= $loadedComment; ?></textarea>
                                                                <input type="submit" value="Сохранить звонок">

                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <? } ?>
                                    </div>


                                    <div style="border: 0px solid green;">
                                        <div class = "box neutral" style="vertical-align: top;">
                                            <div class = "box-body C">

                                                <h2><input type="date" onchange="GETreloc('date', this.value);" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>"></h2>
                                                <?
                                                $usersGroups = query2array(mysqlQuery("SELECT * FROM `usersGroups` WHERE `idusersGroups` IN (1,2,3,4,5,7,10,11)"));
                                                usort($usersGroups, function ($a, $b) {
                                                    return intval($a['usersGroupsSort']) <=> intval($b['usersGroupsSort']);
                                                });
                                                ?>

                                                <select style="display: inline-block; width: auto;" onchange="GR({usersGroup: this.value, personnel: null});" autocomplete="off">
                                                    <option value=""></option>
                                                    <option value="all" <?= ($_GET['usersGroup'] ?? '') == 'all' ? ' selected' : ''; ?>>Все</option>
                                                    <?
                                                    foreach ($usersGroups as $usersGroup) {
                                                        ?><option <?= ($_GET['usersGroup'] ?? '') == $usersGroup['idusersGroups'] ? ' selected' : ''; ?> value="<?= $usersGroup['idusersGroups']; ?>"><?= $usersGroup['usersGroupsName']; ?></option><?
                                                    }
                                                    ?>
                                                </select><br>
                                                <?
                                                if (isset($_GET['usersGroup'])) {
                                                    if ($_GET['usersGroup'] == 'all') {
                                                        $_GET['usersGroup'] = "1,2,3,4,5,7";
                                                    }
                                                    $personnel = query2array(mysqlQuery("SELECT * "
                                                                    . " FROM `users` "
                                                                    . " LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`)"
                                                                    . " WHERE"
                                                                    . " `usersGroup` IN (" . $_GET['usersGroup'] . ")"
                                                                    . " AND isnull(`usersDeleted`);"));
                                                    usort($personnel, function ($a, $b) {
                                                        if (mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName'])) {
                                                            return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
                                                        }
                                                    });
                                                    ?>
                                                    <select style="display: inline-block; width: auto; margin-top: 10px;" onchange="GR({personnel: this.value});" autocomplete="off">
                                                        <option value=""></option>
                                                        <?
                                                        foreach ($personnel as $user) {
                                                            ?><option <?= ($_GET['personnel'] ?? '') == $user['idusers'] ? ' selected' : ''; ?> value="<?= $user['idusers']; ?>"><?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?> </option><?
                                                        }
                                                        ?>
                                                    </select>


                                                    <br>

                                                    <?
                                                }
                                                ?>



                                                <?
                                                $cdate = date("Y-m", mystrtotime($_GET['date'] ?? date("Y-m-d"))) . '-01';
                                                $monthBegin = mystrtotime($cdate);
                                                $monthLength = date('t', $monthBegin);
                                                $monthEnd = mystrtotime($cdate . '-' . $monthLength);

                                                $data = [];

                                                $servicesApplied = query2array(mysqlQuery("SELECT `servicesAppliedDate`,count(1) as `count` FROM `servicesApplied` WHERE"
                                                                . " `servicesAppliedClient` = '" . mres($_GET['client']) . "'"
                                                                . "AND isnull(`servicesAppliedDeleted`)"
                                                                . "GROUP BY `servicesAppliedDate`"));

                                                foreach ($servicesApplied as $serviceApplied) {
                                                    $data[$serviceApplied['servicesAppliedDate']]['services'] = $serviceApplied['count'];
                                                }



//						printr($data);
                                                $date = date("Y-m", mystrtotime($_GET['date'] ?? date("Y-m-d")));
                                                $monthBegin = mystrtotime($date . '-01');
                                                $monthBeginWeekDay = date("N", $monthBegin);
                                                $monthLength = date('t', $monthBegin);
                                                $monthEnd = mystrtotime($date . '-' . $monthLength);

                                                if ($_GET['personnel'] ?? false) {
                                                    $scheduleSQL = "SELECT * FROM `usersSchedule` WHERE"
                                                            . " `usersScheduleDate`>='" . date("Y-m-d", $monthBegin) . "'"
                                                            . " AND `usersScheduleDate`<='" . date("Y-m-d", $monthEnd) . "'"
                                                            . " AND `usersScheduleUser` = '" . intval($_GET['personnel']) . "'"
                                                            . "";
                                                } elseif ($_GET['usersGroup'] ?? false) {
                                                    $scheduleSQL = "SELECT * FROM `usersSchedule`"
                                                            . "LEFT JOIN `users` ON (`idusers` = `usersScheduleUser`) WHERE"
                                                            . " `usersScheduleDate`>='" . date("Y-m-d", $monthBegin) . "'"
                                                            . " AND `usersScheduleDate`<='" . date("Y-m-d", $monthEnd) . "'"
                                                            . " AND `usersGroup` = '" . intval($_GET['usersGroup']) . "'"
                                                            . "";
                                                }
                                                if ($scheduleSQL ?? false) {
                                                    $schedule = query2array(mysqlQuery($scheduleSQL));
//												printr($schedule[0]);
                                                    foreach ($schedule as $scheduleEntry) {
                                                        $data[$scheduleEntry['usersScheduleDate']]['schedule'] = ($data[$scheduleEntry['usersScheduleDate']]['schedule'] ?? '00') | $scheduleEntry['usersScheduleHalfs'];
                                                        $data[$scheduleEntry['usersScheduleDate']]['dutes'][] = $scheduleEntry['usersScheduleDuty'];

                                                        if (count($schedule) == 1) {
//															$data[$scheduleEntry['usersScheduleDate']]['usersScheduleDuty'] = $scheduleEntry['usersScheduleDuty'];
                                                        }
                                                    }

//												printr($data);
                                                }
                                                foreach (query2array(mysqlQuery("SELECT * FROM `clientsVisits` WHERE `clientsVisitsClient` = '" . mres($_GET['client']) . "'")) as $visit) {
                                                    $data[$visit['clientsVisitsDate']]['visit'] = 1;
                                                }
                                                if ($_USER['id'] == 176) {
//													printr($data);
                                                }
                                                drowCalendar($date, $data);
                                                ?>
                                                <div style=" display: flex; align-items: center;" ><span style="border: 2px solid red; display: inline-block; width: 16px; height: 16px;"></span>&nbsp;- выбранная дата</div>
                                                <div style=" display: flex; align-items: center;" ><span style="background-color: hsla(0,0%,0%,0.1); display: inline-block; width: 16px; height: 16px;"></span>&nbsp;- сегодня</div>
                                                <div style=" display: flex; align-items: center;" ><span style="border: 1px solid green; border-radius: 50%; display: inline-block; width: 16px; height: 16px;"></span>&nbsp;- визит клиента</div>
                                                <div style=" display: flex; align-items: center;" ><span class="duty" style="width: 16px; height: 16px;"></span>&nbsp;- дежурная смена</div>
                                                <div style=" display: flex; align-items: center;" ><div style="width: 16px; height: 16px; border: 1px solid silver;"><span class="H10"></span></div>&nbsp;- утренняя смена</div>
                                                <div style=" display: flex; align-items: center;" ><div style="width: 16px; height: 16px; border: 1px solid silver;"><span class="H01"></span></div>&nbsp;- вечерняя смена</div>
                                                <div style=" display: flex; align-items: center;" ><div style="width: 16px; height: 16px; border: 1px solid silver;"><span class="H11"></span></div>&nbsp;- полная смена</div>

                                                <?
                                                if (1) {
//										printr($servicesApplied);
                                                    $servicesAppliedToday = array_filter($servicesApplied, function ($el) {
                                                        global $_GET;
                                                        return $el['servicesAppliedDate'] === ($_GET['date'] ?? date("Y-m-d"));
                                                    });
                                                    ?>
                                                    <? if (count($servicesAppliedToday)) {
                                                        ?>
                                                        <br>
                                                        <a style="cursor: pointer;" onclick="editField({moveFrom: '<?= ($_GET['date'] ?? date("Y-m-d")) ?>', servicesAppliedClient: <?= $client['idclients']; ?>, mindate: '<?= date("Y-m-d", mystrtotime(EDGEDATE . ' +1day')); ?>'});">Перенести на другую дату</a> <a href="/sync/utils/visits.php?client=<?= $_GET['client']; ?>" target="_blank">*</a>
                                                    <? } ?>


                                                <? } ?>

                                            </div>
                                        </div>
                                        <? if (R(157)) { ?>
                                            <br>
                                            <div class="box neutral">
                                                <div class="box-body">
                                                    <h2>Перенос клиента</h2>
                                                    <form action="<?= GR(); ?>"  method="POST" onsubmit="if (document.querySelector('#targetdatabases').value !== '' && confirm('ВЫ АБСОЛЮТНО УВЕРЕНЫ ЧТО ДЕЛАЕТЕ????\nЭТО ДЕЙСТВИЕ НЕ ОТМЕНИТЬ!\nОПЕРАЦИЯ БУДЕТ ЗАПИСАНА НА ВАШЕ ИМЯ!')) {
                                                                                MSG('ОЖИДАЙТЕ!!!');
                                                                                return true;
                                                                            } else {
                                                                                void(0);
                                                                                return false;
                                                                            }" style="display: inline-block;">
                                                        <select name="targetdatabase" id="targetdatabases" style="width: auto;">
                                                            <option value="">выбрать базу</option>
                                                            <option value="0">Московские ворота</option>
                                                            <option value="1">Чкаловская</option>
                                                        </select>
                                                        <br>
                                                        <input type="submit" value="перенести клиента">
                                                    </form>
                                                </div>
                                            </div>
                                        <? } ?>
                                    </div>
                                </div>
                            </div>



                            <div style="border: 0px solid red; display: grid; grid-template-columns: auto auto auto;">
                                <div style="border: 0px solid violet;">
                                    <? if (R(71)) { ?>

                                        <div class = "box neutral" style="vertical-align: top;" id="contractsWindow">
                                            <div class = "box-body"  id="totalContracts">
                                                <h2 style="cursor: pointer;"><a onclick="qs('#totalContracts').classList.toggle('hidden');
                                                                        qs('#totalRemains').classList.toggle('hidden');"><b>Планы лечения</b> / Остатки</a></h2>
                                                <div>
                                                    <input type="checkbox" id="showempty" onclick="renderContracts(contracts);"><label for="showempty">Показывать пустые</label>
                                                </div>
                                                <div style="overflow-y: scroll; max-height: 500px; border: 1px solid silver;" id="scrollableContractsWrapper">
                                                    <div id="remains" style="min-height: 300px; display: block;">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class = "box-body hidden" id="totalRemains">
                                                <h2 style="cursor: pointer;"><a  onclick="qs('#totalContracts').classList.toggle('hidden');
                                                                        qs('#totalRemains').classList.toggle('hidden');">Абонементы / <b>Остатки</b></a> <a   onclick="CallPrint(<?= $client['idclients']; ?>);"><i class="fas fa-receipt"></i></a></h2>
                                                <div style="overflow-y: auto; max-height: 500px; border: 1px solid silver;">
                                                    <?
                                                    if ($client['idclients'] ?? false) {
                                                        $totalRemainsFlat = getRemainsByClient($client['idclients']);
                                                        $totalRemainsOUT = [];
                                                        $reserved = query2array(mysqlQuery(""
                                                                        . "SELECT SUM(`servicesAppliedQty`) AS `qty`,"
                                                                        . "`servicesAppliedService` FROM `servicesApplied` WHERE"
                                                                        . " `servicesAppliedClient`='" . $client['idclients'] . "'"
                                                                        . " AND `servicesAppliedDate`>=CURDATE()"
                                                                        . " AND isnull(`servicesAppliedDeleted`)"
                                                                        . " AND isnull(`servicesAppliedFineshed`)"
                                                                        . " AND NOT isnull(`servicesAppliedContract`)"
                                                                        . " GROUP BY `servicesAppliedService`;"));

                                                        foreach ($totalRemainsFlat as $remain) {
                                                            $reservedService = obj2array(array_filter($reserved, function ($el) use ($remain) {
//																printr($remain);
                                                                        return $el['servicesAppliedService'] == $remain['f_salesContentService'];
                                                                    }));
//															printr($reservedService);
                                                            if (($remain['f_salesContentQty'] ?? 0) > 0 || count($reservedService)) {
                                                                $totalRemainsOUT[$remain['f_salesContentService']]['each'][] = $remain;
                                                                $totalRemainsOUT[$remain['f_salesContentService']]['reserved'] = $reservedService[0]['qty'] ?? null;

                                                                $totalRemainsOUT[$remain['f_salesContentService']]['name'] = $remain['servicesName'];
                                                                $totalRemainsOUT[$remain['f_salesContentService']]['qty'] = ($totalRemainsOUT[$remain['f_salesContentService']]['qty'] ?? 0) + $remain['f_salesContentQty'];
                                                            }
                                                        }
                                                        ?><div style="display: grid; grid-template-columns: auto 1fr auto;" class="lightGrid"><?
                                                        usort($totalRemainsOUT, function ($a, $b) {
                                                            return mb_strtolower($a['name']) <=> mb_strtolower($b['name']);
                                                        });
                                                        foreach ($totalRemainsOUT as $remain) {
                                                            ?>
                                                                <div style="display: contents;" id="OLOLO<?= $divRand = RDS(20); ?>">

                                                                    <div><input type="checkbox" data-voice="<?= $remain['qty'] + ($remain['reserved'] ?? 0); ?>" autocomplete="off" id="<?= $rand = rand(); ?>"><label for="<?= $rand; ?>"><?= $remain['name']; ?></label><br>
                                                                        <? foreach ($remain['each'] as $service) { ?>
                                                                            <a target="_blank" href="/pages/checkout/replacement/?sale=<?= $service['f_subscriptionsContract']; ?>"><?= date("d.m.Y", mystrtotime($service['f_salesDate'])); ?> (<?= $service['f_salesContentQty']; ?>)</a>
                                                                        <? }
                                                                        ?>
                                                                    </div>
                                                                    <div style="padding: 10px; font-size: 1.5em; text-align: center;"><?= $remain['qty']; ?><?= $remain['reserved'] ? ('+' . $remain['reserved']) : ''; ?></div> 
                                                                    <div style="padding: 10px; font-size: 1.5em; font-weight: bolder; color: red;"><? if (R(165)) { ?><span style="cursor: pointer;" onclick='doReturn(<?= json_encode($remain['each'], 288); ?>, "OLOLO<?= $divRand; ?>");'>&cross;</span><? } ?></div>

                                                                </div>
                                                                <?
                                                            }
                                                            ?>

                                                        </div><?
                                                    }
                                                    ?>
                                                    <br>
                                                </div>
                                            </div>
                                        </div>

                                        <script>



                                            var yes = new Audio('/pages/offlinecall/voice/yes.mp3');
                                            yes.volume = 1;
                                            var aud = new Audio('/pages/offlinecall/voice/matchdone_01.mp3');
                                            aud.volume = 1;
                                            let digits = [];
                                            for (let i = 1; i < 12; i++) {
                                                digits[i] = new Audio(`/pages/offlinecall/voice/${i}.mp3`);
                                                digits[i].volume = 1;
                                            }

                                            document.addEventListener('DOMContentLoaded', async () => {

                                                let checkboxes = qsa(`[data-voice]`);
                                                for (let checkbox of checkboxes) {
                                                    checkbox.addEventListener('click', function () {
                                                        if (this.checked) {
                                                            let done = true;
                                                            for (let checkbox of checkboxes) {
                                                                if (!checkbox.checked) {
                                                                    done = false;
                                                                }
                                                            }
                                                            if (done) {
                                                                aud.currentTime = 0;
                                                                aud.play();
                                                            } else {
                                                                let number = parseInt(checkbox.dataset.voice);
                                                                if (number > 0 && number < 12) {
                                                                    digits[number].currentTime = 0;
                                                                    digits[number].play();
                                                                    //																	console.log(number);
                                                                } else if (number >= 12) {
                                                                    yes.currentTime = 0;
                                                                    yes.play();
                                                                }
                                                            }
                                                        }


                                                        //														console.log(done);


                                                    });
                                                }
                                                //												console.log(checkboxes);
                                            });
                                            function doReturn(data, div) {

                                                fetch('IO.php', {
                                                    body: JSON.stringify({action: 'doreturn', data: data}),
                                                    credentials: 'include',
                                                    method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                                                }).then(result => result.text()).then(async function (text) {
                                                    try {
                                                        let jsn = JSON.parse(text);
                                                        if (jsn.success) {
                                                            qs(`#${div}`).style.display = 'none';
                                                        }

                                                    } catch (e) {
                                                        MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
                                                    }
                                                });
                                            }
                                        </script>

                                    <? } ?>
                                </div>

                                <div style="border: 0px solid deepskyblue;">
                                    <div class = "box neutral" style="vertical-align: top;">
                                        <div class = "box-body">
                                            <h2>Процедуры <a href="#"  onclick="CallPrint2(<?= $client['idclients']; ?>);"><i class="fas fa-receipt"></i></a></h2>
                                            <ul class="horisontalMenu">
                                                <li><a <?= (($_GET['unsortedFilter'] ?? false) == 'nocontract') ? ' style="background-color: lightgreen;"' : '' ?>  onclick="GR({unsortedFilter: 'nocontract'});">Без абонемента</a></li>
                                                <li><a <?= (($_GET['unsortedFilter'] ?? false) == 'diagnostics') ? ' style="background-color: lightgreen;"' : '' ?>  onclick="GR({unsortedFilter: 'diagnostics'});">Диагностики</a></li>
                                                <li><a <?= (!($_GET['unsortedFilter'] ?? false)) ? ' style="background-color: lightgreen;"' : '' ?> onclick="GR({unsortedFilter: null});" >Вся история</a></li>
                                                <li><a onclick="editField({action: 'saveAppliedServices'});"><i class="fa fa-save"></i></a></li>
                                            </ul>
                                            <div style="max-height: 450px; overflow-y: scroll; border: 1px solid silver;" id="unsortedWrapper">
                                                <div id="unsortedWrapperContent" style="padding: 10px; display: grid; grid-template-columns: auto auto auto auto auto; grid-gap: 0px 10px;">
                                                    <div style="display: contents; position: sticky; top: 0px;">
                                                        <span class="C B">Дата<br>Оператор</span>
                                                        <span class="C B">К-во</span>
                                                        <span class="C B">Процедура</span>
                                                        <span class="C B">Специалист</span>
                                                        <span class="C B">Абонемент</span>
                                                    </div>
                                                    <div id="unsorted" style="display: contents;">

                                                    </div>
                                                </div></div></div>
                                    </div>
                                </div>
                                <div style="border: 0px solid orange;">
                                    <div class = "box neutral" style="vertical-align: top;">
                                        <div class = "box-body">
                                            <h2>Добавить процедуру</h2>
                                            <? if (R(72)) { ?>
                                                <div style="padding: 10px;">
                                                    <div>
                                                        Оператор:
                                                    </div>

                                                    <div>
                                                        <select id="idusersSA"  name="idusersSA" autocomplete="off">
                                                            <option></option>
                                                            <?
                                                            $lp = 32;
                                                            foreach (query2array(mysqlQuery("SELECT * FROM `usersPositions` left join `users` ON (`idusers`=`usersPositionsUser`) WHERE isnull(`usersDeleted`) AND `usersPositionsPosition` IN(32,79) ORDER BY `usersPositionsPosition`,`usersLastName`, `usersFirstName`")) as $user) {
                                                                if ($lp != $user['usersPositionsPosition']) {
                                                                    $lp = $user['usersPositionsPosition'];
                                                                    ?><option value=""> - - - - -</option><?
                                                                }
                                                                ?><option value="<?= $user['idusers']; ?>"><?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?></option><?
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            <? } ?>
                                            <div style="padding: 10px;">
                                                <div>
                                                    Добавить процедуру:
                                                </div>
                                                <div>
                                                    <div style="align-self: center; position: relative;">
                                                        <input type="text" placeholder="Поиск процедур" id="serviceSearch" onkeydown="
                                                                            if (event.keyCode === 38) {
                                                                                pointer--;
                                                                            } else if (event.keyCode === 40) {
                                                                                pointer++;
                                                                            }
                                                                            let confirm = false;
                                                                            if (event.keyCode === 13) {
                                                                                confirm = true;
                                                                            }
                                                                            suggest(this.value, confirm);" oninput="pointer = 0; suggest(this.value);" style="display: inline; width: auto;">
                                                        <ul id="suggestions">
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="procedurePicker" style="padding: 10px; margin-top: 10px; border: 0px solid red;">
                                                <div style="display: grid; grid-template-columns: 40px auto;">
                                                    <div id="unsortedPillsAddWrapper"></div>
                                                    <div id="unsortedPillsAddName"></div>
                                                </div>
                                            </div>
                                            <div style="text-align: center;">
                                                часто используемые:
                                            </div>
                                            <div style="padding: 10px;">
                                                <div id="recentWrapper"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box neutral" style="display: block;">
                        <div class="box-body">
                            <div style="padding: 0px 60px;"><b><?= date("d.m.Y", mystrtotime(($_GET['date'] ?? date("Y-m-d")))); ?></b> <span style="cursor: pointer;" onclick="getAvailablePersonnel({date: '<?= ($_GET['date'] ?? date("Y-m-d")); ?>'});">Загрузить расписание</span> | <a target="_blank" href="/pages/reception/index.php?bypersonal&date=<?= ($_GET['date'] ?? date("Y-m-d")); ?>"><i class="fas fa-stream"></i> Регистратура</a>
                                <?
                                if (1 || $_USER['id'] == 176) {

                                    $phones = array_combine(array_column($clientsPhones, 'idclientsPhones'), array_column($clientsPhones, 'clientsPhonesPhone'));
                                    ?><i onclick='sendSMS(<?=
                                        json_encode(
                                                [
                                                    'phones' => $phones,
                                                    'date' => ($_GET['date'] ?? date("Y-m-d")),
                                                    'client' => $client['idclients'],
                                                    'templates' => query2array(mysqlQuery("SELECT `idsmsTemplates` as `id`,`smsTemplatesName` as `name` FROM `smsTemplates` WHERE isnull(`smsTemplatesDeleted`) AND (`smsTemplatesGroup` in (9,12) OR `idsmsTemplates`=9)")),
                                                    'showPhoneNumbers' => R(147) ? true : false
                                                ]
                                        )
                                        ?>);' class="fas fa-envelope" style="float: right; cursor: pointer;"></i><? } ?>
                            </div>
                            <div id="monitor"><br></div>
                            <div id="scheduleGrid" style="border-top: 1px solid silver; border-left: 1px solid silver; display: grid; grid-template-columns: auto 1fr;">
                                <div style="display: contents;" id="scheduleHeader"></div>
                                <div style="display: contents;" id="scheduleContent"></div>
                                <div style="display: contents;" id="schedulePersonnel"></div>

                            </div>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <div class="box neutral" style="text-align: left;">
                            <div class="box-body">
                                <h2>Описание значков:</h2>
                                <div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px 8px;">
                                    <div style="text-align: center;"><i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i></div><div> - ПЕРВИЧНЫЙ КЛИЕНТ, ВНИМАНИЕ!</div>
                                    <div style="text-align: center;"><i class="fas fa-info-circle" style="color:  hsl(220,100%,78%); display: inline;"></i></div><div> - к процедуре есть дополнительная информация</div>
                                    <div style="text-align: center;"><i class="fas fa-lock" style="color:  hsl(0,100%,78%); display: inline;"></i></div><div> - процедура закреплена за специалистом</div>
                                    <div style="text-align: center;"><i class="fas fa-gift" style="color:  hsl(15,100%,50%); display: inline;"></i></div><div> - подарочная процедура</div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                let _date = '<?= $_GET['date'] ?? date("Y-m-d"); ?>';
                let _client = '<?= FSI($_GET['client']); ?>';
                let _trackLimits = {from: `${_date} 10:00:00`, to: `${_date} 21:00:00`};
                let contracts = [];
                async function init() {
                    let data = await getContracts(<?= FSI($_GET['client']); ?>);
                    contracts = data.contracts;
                    let unsorted = data.unsorted;
                    contracts.sort((a, b) => {
                        return (new Date(a.f_salesDate)).getTime() - (new Date(b.f_salesDate)).getTime();
                    });
                    renderUnsorted(unsorted);
                    renderContracts(contracts);
                    let schedule = await loadSchedule({date: _date, client:<?= $_GET['client']; ?>});
                    //					console.log("schedulescheduleschedule", schedule);
                    qs('#scheduleContent').innerHTML = ``;
                    qs('#schedulePersonnel').innerHTML = ``;
                    qs('#scheduleContent').appendChild(track({title: '<?= $client['clientsLName']; ?> <?= $client['clientsFName']; ?> <?= $client['clientsMName']; ?>'}, ((schedule || {}).schedule)));
                            fetch('IO.php', {
                                body: JSON.stringify({action: 'getRecentServices'}),
                                credentials: 'include',
                                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                            }).then(result => result.text()).then(async function (text) {
                                try {
                                    let jsn = JSON.parse(text);
                                    if (jsn.msgs) {
                                        jsn.msgs.forEach(msg => {
                                            MSG(msg);
                                        });
                                    }
                                    renderRecents(jsn.services);
                                } catch (e) {
                                    MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
                                }
                            });
                        }

                        window.addEventListener('DOMContentLoaded', function () {
                            init();
                        });
                        function renderRecents(recents) {
                            let recentWrapper = qs('#recentWrapper');
                            clearElement(recentWrapper);
                            if ((recents || []).length > 0) {
                                for (let recent of recents) {
                                    let pillWrapper = el('div');
                                    recent.idclients = _client;
                                    pillWrapper.appendChild(pill(recent));
                                    recentWrapper.appendChild(pillWrapper);
                                    let pillNameWrapper = el('div');
                                    pillNameWrapper.appendChild(el('div', {innerHTML: `${recent.name}`}));
                                    recentWrapper.appendChild(pillNameWrapper);
                                }
                            }
                        }
            </script>
            <!--<div onclick="init();">init</div>-->
        <? } ?>
        <?
    }
    ?>




    <?
}
?>
<script>
    function CallPrint(idusers) {
        //		console.log('CallPrint');
        var WinPrint = window.open('/sync/utils/printRemains/?client=' + idusers, '', 'left=50,top=50,width=400,height=640,toolbar=0,scrollbars=1,status=0');
        WinPrint.addEventListener(
                'afterprint', () => {
            setTimeout(function () {
                WinPrint.close();
                //				console.log('after print event!2');
            }, 110);
        }
        );
    }
    function CallPrint2(idusers) {
        //		console.log('CallPrint');
        var WinPrint = window.open('/sync/utils/printUpcomming/?client=' + idusers, '', 'left=50,top=50,width=400,height=640,toolbar=0,scrollbars=1,status=0');
        WinPrint.addEventListener(
                'afterprint', () => {
            setTimeout(function () {
                WinPrint.close();
                //				console.log('after print event!2');
            }, 110);
        }
        );
    }
</script>
<!--<a href="https://vita.menua.pro/sync/utils/voip/call3.php?src=169&dist=89052084769" target="_blank">Нажми меня нежно</a>-->
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
