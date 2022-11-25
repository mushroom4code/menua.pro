<?php
$pageTitle = $load['title'] = 'Оформление платежа';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (R(190)) {
    if (
            ($_GET['action'] ?? '') === 'updatePaymentType' &&
            isset($_GET['payment']) &&
            isset($_GET['type'])
    ) {
        mysqlQuery("UPDATE `f_payments` SET `f_paymentsType` = " . sqlVON($_GET['type'], 1) . " WHERE `idf_payments`= " . sqlVON($_GET['payment'], 1) . "  ");

        header("Location: " . GR2([
                    'action' => null,
                    'payment' => null,
                    'type' => null
        ]));
        exit();
    }

    //3&action=updatePaymentSumm&payment=15216&summ=19001
    if (
            ($_GET['action'] ?? '') === 'updatePaymentSumm' &&
            isset($_GET['payment']) &&
            isset($_GET['summ'])
    ) {
        mysqlQuery("UPDATE `f_payments` SET `f_paymentsAmount` = " . sqlVON($_GET['summ'], 1) . " WHERE `idf_payments`= " . sqlVON($_GET['payment'], 1) . "  ");

        header("Location: " . GR2([
                    'action' => null,
                    'payment' => null,
                    'summ' => null
        ]));
        exit();
    }


    if (
            ($_GET['action'] ?? '') === 'updateBalanceSumm' &&
            isset($_GET['balance']) &&
            isset($_GET['summ'])
    ) {
        mysqlQuery("UPDATE `f_balance` SET `f_balanceAmount` = " . sqlVON($_GET['summ'], 1) . " WHERE `idf_balance`= " . sqlVON($_GET['balance'], 1) . "  ");

        header("Location: " . GR2([
                    'action' => null,
                    'balance' => null,
                    'summ' => null
        ]));
        exit();
    }





    if (
            ($_GET['action'] ?? '') === 'updatePaymentDate' &&
            isset($_GET['payment']) &&
            isset($_GET['date'])
    ) {
        if ($_GET['date'] > EDGEDATE) {
            mysqlQuery("UPDATE `f_payments` SET `f_paymentsDate` = '" . mres($_GET['date']) . " 12:34:56' WHERE `idf_payments`= " . sqlVON($_GET['payment'], 1) . "  ");

            header("Location: " . GR2([
                        'action' => null,
                        'payment' => null,
                        'date' => null
            ]));
            exit();
        } else {
            die('DATE ERROR');
        }
    }
}
if (R(26)) {

    $searchby = [];
    if ((($_POST['TIN'] ?? '') !== '') && ($_GET['client'] ?? false)) {
        mysqlQuery("UPDATE `clients` SET `clientsTIN` = '" . mres($_POST['TIN']) . "' WHERE `idclients` = '" . mres($_GET['client']) . "'");
        header("Location: " . GR());
        die();
    }


    if (($_GET['saveEntity'] ?? false) && ($_GET['contract'] ?? false)) {
        mysqlQuery("UPDATE `f_sales` SET `f_salesEntity` = '" . mres($_GET['saveEntity']) . "' WHERE `idf_sales`='" . mres($_GET['contract']) . "'");
        header("Location: " . GR2(['saveEntity' => null]));
        die();
    }

    if (
            (($_POST['clientsTaxPersonsFULLName'] ?? '') !== '') &&
            (($_POST['clientsTaxPersonsTIN'] ?? '') !== '') &&
            ($_GET['client'] ?? false)) {
        mysqlQuery("INSERT INTO `clientsTaxPersons` SET"
                . " `clientsTaxPersonsFULLName` = '" . mres($_POST['clientsTaxPersonsFULLName']) . "'"
                . " ,`clientsTaxPersonsTIN` = '" . mres($_POST['clientsTaxPersonsTIN']) . "'"
                . " ,`clientsTaxPersonsAddedBy` = '" . $_USER['id'] . "'"
                . ", `clientsTaxPersonsClient` = '" . mres($_GET['client']) . "'"
                . ""
        );
//		printr($_POST);
        header("Location: " . GR());
        die();
    }


    if (($_GET['clientsTaxPersonsDelete'] ?? '') !== '') {
        mysqlQuery("UPDATE `clientsTaxPersons` SET"
                . " `clientsTaxPersonsDeleted` = NOW(),"
                . " `clientsTaxPersonsDeletedBy` = '" . $_USER['id'] . "'"
                . " WHERE `idclientsTaxPersons` = '" . mres($_GET['clientsTaxPersonsDelete']) . "'"
        );
        header("Location: " . GR2(['clientsTaxPersonsDelete' => null]));
        die();
    }

    if (!empty($_GET['lname'])) {
        $searchby[] = "`clientsLName` like '%" . mysqli_real_escape_string($link, FSS(trim($_GET['lname']))) . "%'";
    }
    if (!empty($_GET['fname'])) {
        $searchby[] = "`clientsFName` like '%" . mysqli_real_escape_string($link, FSS(trim($_GET['fname']))) . "%'";
    }
    if (!empty($_GET['mname'])) {
        $searchby[] = "`clientsMName` like '%" . mysqli_real_escape_string($link, FSS(trim($_GET['mname']))) . "%'";
    }

    if (count($searchby)) {
        $clients = query2array(mysqlQuery("SELECT *, (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` = `idclients`) AS `contracts` FROM `clients` where " . implode(' AND ', $searchby)));
        if (count($clients) == 1) {
            header("Location: /pages/checkout/payments.php?client=" . $clients[0]['idclients']);
            die();
        }
    }




    if (($_GET['ttl'] ?? '') == 'now' && ($_GET['contract'] ?? false)) {

        $contractinfo = contractInfo($_GET['contract']);
        mysqlQuery("UPDATE `f_sales` SET "
                . " `f_salesSumm` = '" . $contractinfo['paymentsSumm'] . "',"
                . " `f_salesComment` = CONCAT(`f_salesComment`, ' Аннулирование (" . date("d.m.Y") . ") " . $_USER['id'] . "')"
                . " WHERE `idf_sales` = '" . $contractinfo['contract']['idf_sales'] . "'");
        header("Location: " . GR2(['ttl' => null]));
        die();
    }


    if (($_GET['equal'] ?? '') == 'now' && ($_GET['contract'] ?? false)) {

        $contractinfo = contractInfo($_GET['contract']);
        mysqlQuery("UPDATE `f_sales` SET "
                . " `f_salesSumm` = '" . $contractinfo['calculatedSumm'] . "',"
                . " `f_salesComment` = CONCAT(`f_salesComment`, ' ВЫРАВНИВАНИЕ (" . date("d.m.Y") . ") " . $_USER['id'] . "')"
                . " WHERE `idf_sales` = '" . $contractinfo['contract']['idf_sales'] . "'");
        header("Location: " . GR2(['equal' => null]));
        die();
    }


    if (($_GET['payme'] ?? '') == 'now' && ($_GET['contract'] ?? false)) {

        $contractinfo = contractInfo($_GET['contract']);
        mysqlQuery("INSERT INTO `f_credits` SET "
                . "`f_creditsBankAgreementNumber` = '" . $_USER['id'] . "',"
                . "`f_creditsSumm` = '" . (($contractinfo['f_salesSumm'] ?? 0) - ($contractinfo['paymentsSumm'] ?? 0)) . "',"
                . "`f_creditsMonthes` = 0,"
                . "`f_creditsSalesID` = '" . $contractinfo['contract']['idf_sales'] . "',"
                . "`f_creditsBankID` = 24");
        header("Location: " . GR2(['payme' => null]));
        die();
    }

    if (
            (!empty($_POST['date'] ?? '')) &&
            (!empty($_POST['summ'] ?? '')) &&
            (!empty($_POST['method'] ?? '')) &&
            (!empty($_GET['contract'] ?? ''))
    ) {
        if (in_array($_POST['method'], [1, 2])) {
            if (mysqlQuery("INSERT INTO `f_payments` SET "
                            . "`f_paymentsSalesID` = '" . FSI($_GET['contract']) . "', "
                            . "`f_paymentsType` = '" . FSI($_POST['method']) . "', "
                            . "`f_paymentsAmount` = '" . mres($_POST['summ']) . "', "
                            . "`f_paymentsUser` = '" . $_USER['id'] . "', "
                            . "`f_paymentsClient` = (SELECT `f_salesClient` FROM `f_sales` WHERE `idf_sales` = '" . FSI($_GET['contract']) . "' ), "
                            . "`f_paymentsDate` = " . ($_POST['date'] == date("Y-m-d") ? 'NOW()' : ("'" . $_POST['date'] . " 04:00:00'")) . " ")) {
                header("Location: " . GR());
                die();
            } else {
                print mysqli_error($link);
            }
        } elseif ($_POST['method'] == 3) {
            if (mysqlQuery("INSERT INTO `f_balance` SET "
                            . "`f_balanceSalesID` = '" . FSI($_GET['contract']) . "', "
                            . "`f_balanceAmount` = '" . mres($_POST['summ']) . "', "
                            . "`f_balanceUser` = '" . $_USER['id'] . "', "
                            . "`f_balanceClient` = (SELECT `f_salesClient` FROM `f_sales` WHERE `idf_sales` = '" . FSI($_GET['contract']) . "' ), "
                            . "`f_balanceTime` = " . ($_POST['date'] == date("Y-m-d") ? 'NOW()' : ("'" . $_POST['date'] . " 04:00:00'")) . " ")) {
                header("Location: " . GR());
                die();
            } else {
                print mysqli_error($link);
            }
        }
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(26)) {
    ?>E403R26<?
} else {
    ?>

    <? include 'menu.php'; ?>
    <div style="padding: 10px;">


        <?
        if (!isset($_GET['client'])) {
            ?>

            <div class="box neutral">
                <div class="box-body">
                    <h2>Поиск клиента</h2>
                    <form action="/pages/checkout/payments.php" method="GET">
                        <div style="padding: 3px;"><input name="lname" type="text" placeholder="Фамилия" value="<?= $_GET['lname'] ?? ''; ?>"></div>
                        <div style="padding: 3px;"><input name="fname" type="text" placeholder="Имя" value="<?= $_GET['fname'] ?? ''; ?>"></div>
                        <div style="padding: 3px;"><input name="mname" type="text" placeholder="Отчество" value="<?= $_GET['mname'] ?? ''; ?>"></div>
                        <input type="submit" value="<?= rt(['Найти', 'Выполнить поиск', 'Поискать', 'Найти в базе данных', 'Проверить', 'да']); ?>">
                    </form>
                    <h3 style="margin-top: 20px;">Результаты поиска</h3>
                    <?
                    if (!count($clients ?? [])) {
                        ?>Нет данных<?
                    } else {
                        ?>
                        <div style=" text-align: left; display: grid; grid-template-columns: auto auto auto auto auto auto;">
                            <div>#</div>
                            <div>Фамилия</div>
                            <div>Имя</div>
                            <div>Отчество</div>
                            <div>№ карты</div>
                            <div>Абонементы</div>

                            <?
                            $n = 0;
                            usort($clients, function ($a, $b) {
                                if (strtolower($a['clientsLName']) <=> strtolower($b['clientsLName'])) {
                                    return strtolower($a['clientsLName']) <=> strtolower($b['clientsLName']);
                                } elseif (strtolower($a['clientsFName']) <=> strtolower($b['clientsFName'])) {
                                    return strtolower($a['clientsFName']) <=> strtolower($b['clientsFName']);
                                } elseif (strtolower($a['clientsMName']) <=> strtolower($b['clientsMName'])) {
                                    return strtolower($a['clientsMName']) <=> strtolower($b['clientsMName']);
                                }
                            });

                            foreach ($clients as $client) {
                                $n++;
                                $a = '/pages/checkout/payments.php?client=' . $client['idclients'];
                                ?>
                                <div style="padding: 0px 5px; text-align: right;"><a href="<?= $a; ?>"><?= $n; ?></a></div>
                                <div style="padding: 0px 5px; "><a href="<?= $a; ?>"><?= mb_ucfirst($client['clientsLName']); ?></a></div>
                                <div style="padding: 0px 5px; "><a href="<?= $a; ?>"><?= mb_ucfirst($client['clientsFName']); ?></a></div>
                                <div style="padding: 0px 5px; "><a href="<?= $a; ?>"><?= mb_ucfirst($client['clientsMName']); ?></a></div>
                                <div style="padding: 0px 5px; text-align: center;"><a href="<?= $a; ?>"><?= ($client['clientsAKNum']); ?></a></div>
                                <div style="padding: 0px 5px; text-align: center;"><a href="<?= $a; ?>"><?= $client['contracts'] ? $client['contracts'] : ''; ?></a></div>
                                <?
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?
        }

        if (isset($_GET['client'])) {
            $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . FSI($_GET['client']) . "'"));
            ?>
            <div class="box neutral">
                <div class="box-body">
                    <h2>
                        <?= clientIsNew($client['idclients']) ? '<i class="fas fa-angle-double-up" style="color: hsl(0,100%,50%);"></i>' : '' ?>
                        <?= $client['clientsLName'] ?? ''; ?>
                        <?= $client['clientsFName'] ?? ''; ?>
                        <?= $client['clientsMName'] ?? ''; ?>
                    </h2>

                    <div style="display: inline-block;">
                        <div style="display: grid; grid-template-columns: auto auto; grid-gap: 0px 10px; padding: 10px;">
                            <div>Фамилия</div><div><?= $client['clientsLName'] ?? ''; ?></div>
                            <div>Имя</div><div><?= $client['clientsFName'] ?? ''; ?></div>
                            <div>Отчество</div><div><?= $client['clientsMName'] ?? ''; ?></div>
                            <div>Дата рождения</div><div><?= $client['clientsBDay'] ? date("d.m.Y", strtotime($client['clientsBDay'])) : 'Не указана'; ?></div>
                            <div>Номер амбулаторной карты</div><div><?= $client['clientsAKNum'] ?? 'Не указан'; ?></div>
                            <div>ИНН</div><form action="<?= GR(); ?>" method="post"><input name="TIN" type="text" value="<?= $client['clientsTIN']; ?>" style="width: auto;"><input type="submit" value="сохранить" style="height: 16px; width: auto; line-height: 1em; padding: 0px 10px; color: green; font-weight: bolder;"></form>
                        </div>
                    </div><br>
                    <?
                    $clientsTaxPersons = query2array(mysqlQuery("SELECT `idclientsTaxPersons`,`clientsTaxPersonsFULLName`,`clientsTaxPersonsTIN` FROM `clientsTaxPersons` WHERE `clientsTaxPersonsClient` = '" . $client['idclients'] . "' AND isnull(`clientsTaxPersonsDeleted`)"));
//					var_dump($clientsTaxPersons);
                    ?>
                    <div style="display: block;">
                        <h3>Налоговые представители</h3>
                        <form method="post" action="<?= GR(); ?>" style="display: contents;">
                            <div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto;">

                                <div style="display: contents;">
                                    <div>#</div>
                                    <div>Ф.И.О. налогоплательщика</div>
                                    <div>ИНН налогоплательщика</div>
                                    <div></div>
                                    <?
                                    $n = 0;
                                    foreach ($clientsTaxPersons as $clientsTaxPerson) {
                                        $n++;
                                        ?>
                                        <div><?= $n; ?></div>
                                        <div><?= $clientsTaxPerson['clientsTaxPersonsFULLName']; ?></div>
                                        <div class="C"><?= $clientsTaxPerson['clientsTaxPersonsTIN']; ?></div>
                                        <div class="C"><input type="button" onclick="GR({clientsTaxPersonsDelete:<?= $clientsTaxPerson['idclientsTaxPersons']; ?>});" value="х" style="height: 16px; width: 16px; line-height: 1em; padding: 0px; color: red; font-weight: bolder;"></div>
                                        <?
                                    }
                                    ?>

                                </div>

                                <div style="display: contents;">
                                    <div></div>
                                    <div><input type="text" name="clientsTaxPersonsFULLName" maxlength="128"></div>
                                    <div><input type="text" name="clientsTaxPersonsTIN" maxlength="45"></div>
                                    <div><input type="submit" value="+" style="height: 16px; width: 16px; line-height: 1em; padding: 0px; color: green; font-weight: bolder;"></div>
                                </div>

                            </div>
                        </form>
                    </div>

                    <?
                    if ($_GET['contract'] ?? 0) {
                        ?><div style="padding: 10px;"><a href="<?= GR('contract', null); ?>">&lt;&lt; Назад</a></div><?
                        $contract = mfa(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`f_salesDate`) AS `f_salesDateTS`"
                                        . " FROM `f_sales`"
                                        . " LEFT JOIN `users` ON (`idusers` = `f_salesCreditManager`)"
                                        . " LEFT JOIN `entities` ON (`identities`=`f_salesEntity`)"
                                        . " WHERE `idf_sales` = '" . FSI($_GET['contract']) . "'"));
                        if ($contract) {
                            ?>
                            <h3 style="margin-top: 30px; text-align: center;">Договор от <?= date("d.m.Y", $contract['f_salesDateTS']); ?><?= $contract['f_salesAdvancePayment'] ? ' (АВАНСОВЫЙ)' : ''; ?></h3>
                            <div style="display: grid; grid-template-columns: auto auto; grid-gap: 10px 10px; padding: 10px;">

                                <div>Тип договора</div>
                                <div><?= [null => 'Не указан', '1' => 'Первичный', '2' => 'Вторичный', '3' => 'On-line/разовая процедура'][$contract['f_salesType']]; ?></div>


                                <div>Исполнитель</div>
                                <div><? if ($contract['f_salesEntity'] && !R(140)) { ?>
                                        <?= $contract['entitiesName']; ?>
                                    <? } else { ?>
                                        <select id="saleEntity" onchange="GR({saveEntity: this.value});" autocomplete="off">
                                            <option value="">Выбрать</option>
                                            <? foreach (query2array(mysqlQuery("SELECT * FROM `entities`")) as $saleEntity) {
                                                ?><option <?= $saleEntity['identities'] == ($contract['f_salesEntity'] ?? false) ? ' selected' : ''; ?> value="<?= $saleEntity['identities']; ?>"><?= $saleEntity['entitiesName']; ?></option><? }
                                            ?>
                                        </select>
                                    <? } ?></div>



                                <div>Кредитный менеджер</div>
                                <div style="color: silver;"><?= $contract['usersLastName'] ?? ''; ?> <?= $contract['usersFirstName'] ?? ''; ?></div>
                                <? $f_salesToCoord = query2array(mysqlQuery("SELECT * FROM `f_salesToCoord` LEFT JOIN `users` ON (`idusers` = `f_salesToCoordCoord`)  WHERE `f_salesToCoordSalesID` = '" . FSI($_GET['contract']) . "'")); ?>
                                <div>Координатор<?= count($f_salesToCoord) > 1 ? 'ы' : ''; ?></div>
                                <div style="color: silver;"><?
                                    foreach ($f_salesToCoord as $salesCoord) {
                                        ?><div><?= $salesCoord['usersLastName'] ?? ''; ?> <?= $salesCoord['usersFirstName'] ?? ''; ?></div><?
                                    }
                                    ?></div>
                                <?
                                $f_salesToPersonal = query2array(mysqlQuery("SELECT * FROM `f_salesToPersonal` LEFT JOIN `users` ON (`idusers` = `f_salesToPersonalUser`)  WHERE `f_salesToPersonalSalesID` = '" . FSI($_GET['contract']) . "'"));
                                ?>
                                <div>Участник<?= count($f_salesToPersonal) > 1 ? 'и' : ''; ?> продажи</div>
                                <div style="color: silver;"><?
                                    foreach ($f_salesToPersonal as $salesPerson) {
                                        ?><div><?= $salesPerson['usersLastName'] ?? ''; ?> <?= $salesPerson['usersFirstName'] ?? ''; ?></div><?
                                    }
                                    ?></div>

                            </div>

                            <table>

                                <?
                                foreach (query2array(mysqlQuery("SELECT *"
                                                . " FROM `f_salesRoles`"
                                                . " LEFT JOIN `users` ON (`idusers` = `f_salesRolesUser`)"
                                                . " LEFT JOIN `f_roles` ON (`idf_roles` = `f_salesRolesRole`)"
                                                . " WHERE `f_salesRolesSale`='" . FSI($_GET['contract']) . "'"
                                                . " ORDER BY `f_salesRolesRole`,`usersLastName`")) as $participant) {
                                    ?>
                                    <tr>
                                        <td><?= $participant['f_rolesName']; ?></td>
                                        <td>
                                            <?= $participant['usersLastName']; ?>
                                            <?= $participant['usersFirstName']; ?>
                                            <?= $participant['usersMiddleName']; ?>
                                        </td>
                                    </tr>
                                    <?
                                }
                                ?>

                            </table>

                            <?
//            [idf_subscriptions] => 42653
//            [f_subscriptionsContract] => 19893
//            [f_salesContentService] => 324
//            [f_salesContentPrice] => 4000
//            [f_salesContentQty] => -4
//            [f_subscriptionsDate] => 2020-07-27 13:50:00
//            [f_subscriptionsUser] =>
//            [idf_sales] => 19893
//            [f_salesNumber] => 112.202007211534
//            [f_salesCreditManager] => 274
//            [f_salesClient] => 112
//            [f_salesSumm] => 20000
//            [f_salesComment] =>
//            [f_salesTime] => 2020-07-21 15:34:09
//            [f_salesDate] => 2020-07-21
//            [f_salesType] => 2
//            [f_salesCancellationDate] =>
//            [f_salesCancellationSumm] =>
//            [f_salesEntity] => 1
//            [idservices] => 324
//            [servicesCode] => 000000136
//            [servicesName] => Фармакопунктура
//            [servicesBasePrice2] => 5000
//            [servicesCost2] => 300
//            [servicesType] => 1
//            [servicesDeleted] =>
//            [servicesEquipment] =>

                            $replacenets = query2array(mysqlQuery(""
                                            . " SELECT"
                                            . " *,"
                                            . " `f_subscriptionsDate` as `f_subscriptionsTime`,"
                                            . " DATE(`f_subscriptionsDate`) as `f_subscriptionsDate`, "
                                            . " UNIX_TIMESTAMP(`f_subscriptionsDate`) as `f_subscriptionsDateTS` "
                                            . " FROM `f_subscriptions`"
                                            . " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
                                            . " LEFT JOIN `users` ON (`idusers` = `f_subscriptionsUser`)"
                                            . " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
                                            . " LEFT JOIN `f_salesReplacementComments` ON (`f_salesReplacementCommentsContract` = `f_subscriptionsContract` AND `f_salesReplacementCommentsDate`=DATE(`f_subscriptionsDate`))"
                                            . " WHERE (`f_subscriptionsContract` = '" . FSI($_GET['contract']) . "'"
                                            . " AND NOT DATE(`f_salesDate`) = DATE(`f_subscriptionsDate`))"));
                            usort($replacenets, function ($a, $b) {
                                return $a['f_subscriptionsDateTS'] <=> $b['f_subscriptionsDateTS'];
                            });

//						printr($replacenets);

                            $services = query2array(mysqlQuery(""
                                            . " SELECT"
                                            . " `f_salesContentService`, "
                                            . " `f_salesContentPrice`, "
                                            . " `servicesVat`, "
                                            . " `idservices`, "
                                            . " `servicesName`, "
                                            . " SUM(`f_salesContentQty`) AS `f_salesContentQty`"
                                            . " FROM `f_subscriptions`"
                                            . " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
                                            . " WHERE `f_subscriptionsContract` = '" . FSI($_GET['contract']) . "'"
                                            . " GROUP BY `f_salesContentService`,`f_salesContentPrice`"));

                            $payments = query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`f_paymentsDate`) AS `f_paymentsDateTS`  FROM `f_payments`"
                                            . "LEFT JOIN `f_paymentsTypes` ON (`idf_paymentsTypes` = `f_paymentsType`) WHERE `f_paymentsSalesID` = '" . FSI($_GET['contract']) . "'"));

                            $balances = query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`f_balanceTime`) AS `f_paymentsDateTS`  FROM `f_balance`"
                                            . " WHERE `f_balanceSalesID` = '" . FSI($_GET['contract']) . "'"));

                            $f_credits = query2array(mysqlQuery("SELECT * FROM `f_credits` LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`) WHERE `f_creditsSalesID` = '" . FSI($_GET['contract']) . "'"));
                            ?>
                            <?
                            if (count($replacenets)) {


                                $coords = query2array(mysqlQuery("SELECT * FROM `f_salesReplacementsCoordinator`"
                                                . "LEFT JOIN `users` ON (`idusers` = `f_salesReplacementsCoordinatorCurator`) "
                                                . "WHERE `f_salesReplacementsCoordinatorContract` = '" . FSI($_GET['contract']) . "'"
                                                . ";"));
                                ?>
                                <h3 style="margin-top: 30px; text-align: center;">Замены</h3>

                                <?
                                if (1) {

                                    $replacenetsSummary2 = [];
                                    foreach ($replacenets as $replacenet) {
//									printr($replacenet);
                                        $replacenetsSummary2[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['comment'] = $replacenet['f_salesReplacementCommentsText'];
                                        $replacenetsSummary2[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['datephp'] = date("Y-m-d", $replacenet['f_subscriptionsDateTS']);
                                        $replacenetsSummary2[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['date'] = date("d.m.Y", $replacenet['f_subscriptionsDateTS']);
                                        $replacenetsSummary2[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['coord'] = array_filter($coords, function ($elem) {
                                            global $replacenet;
                                            return $elem['f_salesReplacementsCoordinatorDate'] == date("Y-m-d", $replacenet['f_subscriptionsDateTS']);
                                        });
                                        if ($replacenet['f_salesContentQty'] > 0) {
                                            $replacenetsSummary2[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['appended'][] = $replacenet;
                                        } elseif ($replacenet['f_salesContentQty'] < 0) {
                                            $replacenetsSummary2[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['removed'][] = $replacenet;
                                        }
                                    }
                                    ?>


                                    <?
                                    // printr($replacenetsSummary2);
                                    foreach ($replacenetsSummary2 as $replacenetsSummary2arr) {
//									printr($replacenetsSummary2arr);
                                        ?>
                                        <div style="background-color: white;border-radius: 10px; padding: 10px; box-shadow: 0px 0px 10px hsla(0,0%,0%,0.2); font-size: 0.8em; margin: 10px auto;">

                                            <div style="display: inline-block; max-width: 600px; ">
                                                <div style="display: grid; grid-template-columns: auto auto; margin: 10px;">
                                                    <div class="B">
                                                        <a href="/sync/utils/word/replacement/index.php?contract=<?= FSI($_GET['contract']); ?>&date=<?= $replacenetsSummary2arr['datephp']; ?>"><i class="fas fa-print"></i></a>

                                                        <?= $replacenetsSummary2arr['date']; ?>
                                                        <? foreach ($replacenetsSummary2arr['coord'] as $coord) {
                                                            ?><?= $coord['usersLastName']; ?> <?= $coord['usersFirstName']; ?> <?= $coord['usersMiddleName']; ?>,
                                                            <?
                                                        }
                                                        ?><?= $replacenetsSummary2arr['comment'] ? $replacenetsSummary2arr['comment'] : 'Причина не указана.'; ?></div>
                                                </div>
                                            </div>
                                            <table>
                                                <tr>
                                                    <td style="vertical-align: top;">
                                                        <? if (count(($replacenetsSummary2arr['removed'] ?? []))) { ?>
                                                            <div class="B C">Удалено</div>
                                                            <div style="display: grid; grid-template-columns: auto auto auto auto auto; background-color: hsla(0,100%,95%,1);" class="lightGrid">
                                                                <div style="display: contents;">
                                                                    <div class="B C">Наименование</div>
                                                                    <div class="B C">Кол-во</div>
                                                                    <div class="B C">Цена</div>
                                                                    <div class="B C">Стоимость</div>
                                                                    <div class="B C">Сотрудник</div>
                                                                </div>
                                                                <?
                                                                foreach (($replacenetsSummary2arr['removed'] ?? []) as $removed) {
                                                                    ?>
                                                                    <div style="display: contents;">
                                                                        <div><?= $removed['servicesName']; ?></div>
                                                                        <div class="C"><?= abs($removed['f_salesContentQty']); ?></div>
                                                                        <div class="R"><?= nf(floatval($removed['f_salesContentPrice'] ?? 0), 2); ?>р.</div>
                                                                        <div class="R"><?= nf(abs($removed['f_salesContentQty'] * $removed['f_salesContentPrice'])); ?>р.</div>
                                                                        <div><?= $removed['usersLastName'] ?? '-'; ?></div>
                                                                    </div>
                                                                    <?
                                                                }
                                                                ?>
                                                            </div>

                                                        <? }
                                                        ?>

                                                    </td>

                                                    <td style="vertical-align: top;">
                                                        <? if (count(($replacenetsSummary2arr['appended'] ?? []))) { ?>
                                                            <div class="B C">Добавлено</div>
                                                            <div style="display: grid; grid-template-columns: auto auto auto auto auto; background-color: hsla(120,100%,95%,1);" class="lightGrid">
                                                                <div style="display: contents;">
                                                                    <div>Наименование</div>
                                                                    <div>Кол-во</div>
                                                                    <div>Цена</div>
                                                                    <div>Стоимость</div>
                                                                    <div class="B C">Сотрудник</div>
                                                                </div>
                                                                <?
                                                                foreach (($replacenetsSummary2arr['appended'] ?? []) as $appended) {
                                                                    ?>
                                                                    <div style="display: contents;">
                                                                        <div><?= $appended['servicesName']; ?></div>
                                                                        <div class="C"><?= abs($appended['f_salesContentQty']); ?></div>
                                                                        <div class="R"><?= nf(floatval($appended['f_salesContentPrice'] ?? 0), 2); ?>р.</div>
                                                                        <div class="R"><?= nf(abs($appended['f_salesContentQty'] * $appended['f_salesContentPrice'])); ?>р.</div>
                                                                        <div><?= $removed['usersLastName'] ?? '-'; ?></div>
                                                                    </div>
                                                                    <?
                                                                }
                                                                ?>
                                                            </div>
                                                        <? } ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <?
                                    }
                                    ?>





                                <? } ?>





                                <!--							<div style="display: grid; grid-template-columns: auto auto auto auto auto; grid-gap: 4px 10px; padding: 10px;">
                                                                                                <div style="text-align: center; font-weight: bolder;">Дата</div>
                                                                                                <div style="text-align: center; font-weight: bolder;">Координатор</div>
                                                                                                <div style="text-align: center; font-weight: bolder;">Списано</div>
                                                                                                <div style="text-align: center; font-weight: bolder;">добавлено</div>
                                                                                                <div style="text-align: center; font-weight: bolder;"><i class="fas fa-print"></i></div>
                                <?
                                $replacenetsSummary = [];

//								printr($coords);
                                foreach ($replacenets as $replacenet) {
//									printr($replacenet);
                                    $replacenetsSummary[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['datephp'] = date("Y-m-d", $replacenet['f_subscriptionsDateTS']);
                                    $replacenetsSummary[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['date'] = date("d.m.Y", $replacenet['f_subscriptionsDateTS']);
                                    $replacenetsSummary[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['coord'] = array_filter($coords, function ($elem) {
                                        global $replacenet;
                                        return $elem['f_salesReplacementsCoordinatorDate'] == date("Y-m-d", $replacenet['f_subscriptionsDateTS']);
                                    });
                                    if ($replacenet['f_salesContentQty'] > 0) {
                                        $replacenetsSummary[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['append'] = ($replacenetsSummary[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['append'] ?? 0) + 1;
                                    } elseif ($replacenet['f_salesContentQty'] < 0) {
                                        $replacenetsSummary[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['remove'] = ($replacenetsSummary[date("d.m.Y", $replacenet['f_subscriptionsDateTS'])]['remove'] ?? 0) + 1;
                                    }
                                }


                                foreach ($replacenetsSummary as $replacenet) {
                                    ?>

                                                                                                                                                                                                                                <div class="C"><?= $replacenet['date']; ?></div>
                                                                                                                                                                                                                                <div><? foreach ($replacenet['coord'] as $coord) {
                                        ?><div><?= $coord['usersLastName']; ?> <?= $coord['usersFirstName']; ?> <?= $coord['usersMiddleName']; ?> </div>
                                        <?
                                    }
                                    ?>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="C"><?= $replacenet['remove'] ?? '-'; ?></div>
                                                                                                                                                                                                                                <div class="C"><?= $replacenet['append'] ?? '-'; ?></div>
                                                                                                                                                                                                                                <div><a href="/sync/utils/word/replacement/index.php?contract=<?= FSI($_GET['contract']); ?>&date=<?= $replacenet['datephp']; ?>"><i class="fas fa-print"></i></a></div>

                                    <?
                                }
                                ?>
                                                                                        </div>-->


                            <? }
                            ?>





                            <h3 style="margin-top: 30px; text-align: center;">Платежи</h3>
                            <div style="display: grid; grid-template-columns: auto auto auto; grid-gap: 4px 10px; padding: 10px;">
                                <div style="text-align: center; font-weight: bolder;">Дата</div>
                                <div style="text-align: center; font-weight: bolder;">Сумма</div>
                                <div style="text-align: center; font-weight: bolder;">Метод</div>
                                <?
                                $ttl = 0;

                                foreach (($f_credits ?? []) as $f_credit) {
                                    ?>
                                    <div><?= date("d.m.Y", $contract['f_salesDateTS']); ?></div>
                                    <div style="text-align: right;"><?= nf($f_credit['f_creditsSumm'], 2); ?>р.</div>
                                    <div style="text-align: center;"><?= $f_credit['RS_banksName'] ?? 'Банк не указан'; ?></div>
                                    <?
                                    $ttl += $f_credit['f_creditsSumm'];
                                }

                                foreach ($payments as $payment) {
                                    ?>
                                    <div>
                                        <? if (R(190)) { ?>
                                            <input autocomplete="off" type="date" min="<?= date("Y-m-d", strtotime(EDGEDATE . ' +1day')); ?>" onblur="GR({action: 'updatePaymentDate', payment: <?= $payment['idf_payments']; ?>, date: this.value});" value="<?= $payment['f_paymentsDateTS'] ? date("Y-m-d", $payment['f_paymentsDateTS']) : ''; ?>">
                                        <? } else { ?>
                                            <?= $payment['f_paymentsDateTS'] ? date("d.m.Y", $payment['f_paymentsDateTS']) : 'Не помню...'; ?>
                                        <? } ?>


                                    </div>
                                    <div style="text-align: right;">
                                        <? if (R(190)) { ?>
                                            <input type="text" autocomplete="off" oninput="digon()" value="<?= round($payment['f_paymentsAmount'], 2); ?>" onblur="if (<?= round($payment['f_paymentsAmount'], 2); ?> != this.value) {
                                                                                GR({action: 'updatePaymentSumm', payment: <?= $payment['idf_payments']; ?>, summ: this.value});
                                                                            }">
                                               <? } else { ?>
                                            <?= nf($payment['f_paymentsAmount'], 2); ?>р.
                                        <? } ?>

                                    </div>
                                    <div style="text-align: center;">
                                        <?
                                        if (R(190)) {
                                            ?>
                                            <div>
                                                <select autocomplete="off" style="width: auto;" onchange="GR({action: 'updatePaymentType', payment: <?= $payment['idf_payments']; ?>, type: this.value});">
                                                    <option></option>
                                                    <? foreach ((query2array(mysqlQuery("SELECT * FROM `f_paymentsTypes`")) ?? []) as $f_paymentsType) { ?>
                                                        <option <?= $payment['f_paymentsType'] == $f_paymentsType['idf_paymentsTypes'] ? ' selected' : ''; ?> value="<?= $f_paymentsType['idf_paymentsTypes']; ?>"><?= $f_paymentsType['f_paymentsTypesName']; ?></option>
                                                    <? }
                                                    ?>
                                                </select>
                                            </div>
                                            <?
                                        } else {
                                            ?>
                                            <?= $payment['f_paymentsTypesName'] ?? 'Не знаю...'; ?>
                                            <?
                                        }
                                        ?>

                                    </div>
                                    <?
                                    $ttl += $payment['f_paymentsAmount'];
                                }

                                foreach ($balances as $balance) {
                                    ?>
                                    <div>
                                        <? if (R(190)) { ?>
                                            <input autocomplete="off" type="date" onblur="GR({action: 'updateBalanceDate', balance: <?= $balance['idf_balance']; ?>, date: this.value});" value="<?= $balance['f_paymentsDateTS'] ? date("Y-m-d", $balance['f_paymentsDateTS']) : ''; ?>">
                                        <? } else { ?>
                                            <?= $balance['f_paymentsDateTS'] ? date("d.m.Y", $balance['f_paymentsDateTS']) : 'Не помню...'; ?>
                                        <? } ?>


                                    </div>
                                    <div style="text-align: right;">
                                        <? if (R(190)) { ?>
                                            <input type="text" autocomplete="off" oninput="digon()" value="<?= round($balance['f_balanceAmount'], 2); ?>" onblur="if (<?= round($balance['f_balanceAmount'], 2); ?> != this.value) {
                                                                                GR({action: 'updateBalanceSumm', balance: <?= $balance['idf_balance']; ?>, summ: this.value});
                                                                            }">
                                               <? } else { ?>
                                            <?= nf($balance['f_balanceAmount'], 2); ?>р.
                                        <? } ?>

                                    </div>
                                    <div style="text-align: center;">
                                        Баланс
                                    </div>
                                    <?
                                    $ttl += $balance['f_balanceAmount'];
                                }
                                ?>

                                <?
                                if (!$contract['f_salesCancellationDate']) {
                                    if ($ttl < $contract['f_salesSumm'] || R(190)) {
                                        ?>
                                        <form action="<?= GR(); ?>" method="POST" style=" display: contents;"
                                              >
                                            <div><input type="date" readonly name="date" value="<?= date("Y-m-d"); ?>"></div>
                                            <div><input type="text" name="summ" oninput="digon();" style="text-align: right;"></div>
                                            <div>
                                                <select name="method">
                                                    <option value=""></option>
                                                    <? foreach (query2array(mysqlQuery("SELECT * FROM `f_paymentsTypes`")) as $method) { ?>
                                                        <option value="<?= $method['idf_paymentsTypes']; ?>"><?= $method['f_paymentsTypesName']; ?></option>
                                                    <? } ?>
                                                </select>
                                            </div>
                                            <div style="grid-column: 1/-1; text-align: center; padding: 10px; background: <?= $ttl < $contract['f_salesSumm'] ? 'pink' : 'lightgreen' ?>; border-radius: 10px;"><input type="submit" value="Добавить платёж <?= $ttl < $contract['f_salesSumm'] ? '' : ' (оплачен полностью)' ?>"></div>
                                        </form>
                                    <? } else { ?>
                                        <div style="grid-column: 1/-1; text-align: center; padding: 10px; font-weight: bold; color: darkgreen; background-color: lightgreen;">Оплачен полностью</div>
                        <!--										<div><input type="date" name="date" value="<?= date("Y-m-d"); ?>"></div>
                                        <div><input type="text" name="summ" oninput="digon();" style="text-align: right;"></div>
                                        <div>
                                                <select name="method">
                                                        <option value=""></option>
                                        <? foreach (query2array(mysqlQuery("SELECT * FROM `f_paymentsTypes`")) as $method) { ?>
                                                                                                                                                                                                                                    <option value="<?= $method['idf_paymentsTypes']; ?>"><?= $method['f_paymentsTypesName']; ?></option>
                                        <? } ?>
                                                </select>
                                        </div>
                                        <div style="grid-column: 1/-1; text-align: center; padding: 10px; background: pink; border-radius: 10px;"><input type="submit" value="Добавить платёж"></div>-->

                                        <?
                                    }
                                } else {
                                    ?>
                                    <div style="grid-column: 1/-1; text-align: center; padding: 10px; font-weight: bold; color: darkred; background-color: pink;">Расторгнут <?= date("d.m.Y", strtotime($contract['f_salesCancellationDate'])); ?><br>Сумма к возврату <?= nf($contract['f_salesCancellationSumm'], 2); ?>р.</div>
                                    <?
                                }
                                ?>
                                <div style="text-align: right; grid-column: span 3;">Итого платежей на сумму: <?= nf($ttl, 2); ?>р.</div>
                            </div>


                            <?
                            if (!count($services)) {
                                ?>Процедуры отсутствуют<?
                            } else {
                                ?>
                                <h3 style="margin-top: 30px; text-align: center;">Состав абонемента</h3>
                                <div style="display: grid; grid-template-columns: auto auto auto auto auto auto; grid-gap: 0px 10px; padding: 10px;">
                                    <div></div>
                                    <div style="text-align: center; font-weight: bolder;">Наименование</div>
                                    <div style="text-align: center; font-weight: bolder;">кол-во</div>
                                    <div style="text-align: center; font-weight: bolder;">цена</div>
                                    <div style="text-align: center; font-weight: bolder;">НДС</div>
                                    <div style="text-align: center; font-weight: bolder;">стоимость</div>
                                    <?
                                    $n = 0;
                                    $total = 0;
                                    usort($services, function ($a, $b) {
                                        return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
                                    });

                                    foreach ($services as $service) {
                                        if (!$service['f_salesContentQty']) {
                                            continue;
                                        }
                                        $n++;
                                        ?>
                                        <div><?= $n; ?>.</div>
                                        <div><?= $service['servicesName']; ?></div>
                                        <div style="text-align: center;"><?= $service['f_salesContentQty']; ?></div>
                                        <div style="text-align: right;"><?= nf($service['f_salesContentPrice'], 2); ?>р.</div>
                                        <div class="C"><a target="_blank" href="/pages/services/index.php?service=<?= $service['idservices']; ?>"><?= $service['servicesVat'] === null ? '<span style="color: red;">НЕ УКАЗАН</span>' : ($service['servicesVat'] ? $service['servicesVat'] : 'БЕЗ НДС'); ?></a></div>
                                        <div style="text-align: right;"><?= nf($service['f_salesContentPrice'] * $service['f_salesContentQty'], 2); ?>р.</div>
                                        <?
                                        $total += $service['f_salesContentPrice'] * $service['f_salesContentQty'];
                                    }
                                    ?>
                                    <div style="grid-column: 1/-2; text-align: right; font-weight: bold;">Итого:</div>
                                    <div style="text-align: right; font-weight: bold;"><?= nf($total); ?>р.</div>

                                    <div style="grid-column: 1/-2; text-align: right; font-weight: bold;">Сумма с учётом скидок:</div>
                                    <div style="text-align: right; font-weight: bold;"><?= nf($contract['f_salesSumm']); ?>р.</div>

                                </div>

                                <div><?
                                    if ($contract['f_salesEntity']) {
                                        if ($contract['f_salesIsAppendix']) {
                                            ?><a href="/sync/utils/word/appendix.php?sale=<?= $contract['idf_sales']; ?>">Печать приложеия №<?= $contract['f_salesNumber']; ?></a><?
                                        } else {
                                            ?><a href="/sync/utils/word/?contract=<?= $contract['idf_sales'] ?>">Печать договора</a><?
                                        }
                                        ?>

                                    <? } else { ?>Печать договора невозможна<? } ?></div>

                                <? if (!$contract['f_salesCancellationDate']) {
                                    ?>

                                    <div style="text-align: right;">
                                        <input type="button" value="Аннулирование / замена" style="background-color: pink; margin: 10px;" onclick="window.location.href = '/pages/checkout/replacement/?sale=<?= $contract['idf_sales']; ?>'">
                                    </div>
                                    <div style="text-align: right;">
                                        <input type="button" value="Оформить возврат" style="background-color: pink; margin: 10px;" onclick="returnWindow({idfsale:<?= $contract['idf_sales']; ?>, summ:<?= $contract['f_salesSumm'] - $ttl; ?>});">
                                    </div>
                                    <?
                                    $contractInfoPayments = contractInfo($contract['idf_sales']);
//								printr($contractInfoPayments);
                                    $contractInfoPaymentsDebt = $contractInfoPayments['f_salesSumm'] - $contractInfoPayments['paymentsSumm'];
                                    if (($contractInfoPaymentsDebt ?? 0) > 0) {
                                        ?>

                                        <? if (R(140)) { ?>
                                            <div style="padding: 20px; border: 1px solid silver; background: pink; display: inline-block; text-align: center; margin: 10px;">
                                                <span style="cursor: pointer;" onclick="GR({payme: 'now'})">Оплатить полностью<br>ТОЛЬКО КОРРЕКТИРОВКА<br><?= nf($contractInfoPaymentsDebt); ?>р. <br>Только если уже не стоит БАНК</span>
                                            </div>
                                            <br>
                                            <div style="padding: 20px; border: 1px solid silver; background: yellow; display: inline-block; text-align: center; margin: 10px;">
                                                <span style="cursor: pointer;" onclick="GR({equal: 'now'})">Стоимость аб. = сумма проц.<br><?= nf($contractInfoPayments['calculatedSumm']); ?>р.</span>
                                            </div>
                                            <br>
                                            <div style="padding: 20px; border: 1px solid silver; background: red; display: inline-block; text-align: center; margin: 10px;">
                                                <span style="cursor: pointer;" onclick="GR({ttl: 'now'})">Аннулирование<br>Стоимость аб. = сумма платежей.<br><?= nf($ttl); ?>р.</span>
                                            </div>
                                        <? } ?>

                                        <?
                                    }
                                    ?>

                                <? } else {
                                    ?>
                                    <div><a href="/sync/utils/word/cancelation.php?contract=<?= $contract['idf_sales'] ?>">Печать заявления о расторжении</a></div>

                                    <?
                                }
                                ?>

                                <?
                            }
                        } else {//valid contract
                            ?><h3 class="C">Несуществующий абонемент</h3><?
                        }
//						printr($payments);
//						printr($services);
                    } else {//no $_GET['contract']
                        $contracts = query2array(mysqlQuery("SELECT *,"
                                        . "("
                                        . "if("
                                        . "(SELECT count(1) FROM `f_sales` as `s1` WHERE `s1`.`f_salesClient` = `s`.`f_salesClient` AND `s1`.`f_salesType` in (1,2) AND `s1`.`f_salesDate`<=`s`.`f_salesDate` AND `s1`.`idf_sales`<>`s`.`idf_sales`),2,1)) as `saleTypeAuto`  FROM `f_sales` as `s` LEFT JOIN `entities` ON (`identities` = `f_salesEntity`)  WHERE `f_salesClient`='" . $client['idclients'] . "'"));
//						printr($contracts); 
                        usort($contracts, function ($a, $b) {
                            return $a['f_salesDate'] <=> $b['f_salesDate'];
                        });
                        ?>
                        <div>
                            <? if (count($contracts)) { ?>
                                <div>Договоры:</div>
                                <div style="display: inline-block; padding: 10px;">
                                    <div class="lightGrid" style="display: grid; grid-template-columns: repeat(11,auto);">
                                        <div style=" display: contents;">
                                            <div style="text-align: center; font-weight: bold;">ibdb</div>
                                            <div style="text-align: center; font-weight: bold;">№ договора</div>
                                            <div style="text-align: center; font-weight: bold;">ST/STA</div>
                                            <div style="text-align: center; font-weight: bold;">Тип абонемента</div>
                                            <div style="text-align: center; font-weight: bold;">Исполнитель</div>
                                            <div style="text-align: center; font-weight: bold;">Дата заключения</div>
                                            <div style="text-align: center; font-weight: bold;">Сумма</div>
                                            <div style="text-align: center; font-weight: bold;">Оплата</div>
                                            <div style="text-align: center; font-weight: bold;">%</div>
                                            <div style="text-align: center; font-weight: bold;">Договор</div>
                                            <div style="text-align: center; font-weight: bold;">Вычет</div>
                                        </div>
                                        <?
                                        foreach ($contracts as $contract) {

                                            $a = '/pages/checkout/payments.php?client=' . $client['idclients'] . '&contract=' . $contract['idf_sales'];
//
                                            ?>
                                            <div style=" display: contents;">

                                                <div><a href="<?= $a; ?>"><?= $contract['idf_sales']; ?>]</a></div>
                                                <div><a href="<?= $a; ?>"><?= $contract['f_salesNumber']; ?></a></div>
                                                <div class="C"><a href="<?= $a; ?>"><?= $contract['f_salesType']; ?>/<?= $contract['saleTypeAuto']; ?></a></div>
                                                <div><?= implode('/', array_unique(array_column(query2array(mysqlQuery("SELECT `servicesTypesName` FROM `f_subscriptions` LEFT JOIN `services` ON (`idservices` = `f_salesContentService`) LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) WHERE `f_subscriptionsContract` = '" . $contract['idf_sales'] . "' AND NOT isnull(`servicesTypesName`)")), 'servicesTypesName'))); ?></div>
                                                <div style="text-align: center;"><?= $contract['entitiesName'] ? $contract['entitiesName'] : 'Не указан'; ?></div>
                                                <div style="text-align: center;"><?= $contract['f_salesDate'] ? date("d.m.Y", strtotime($contract['f_salesDate'])) : 'Не указана'; ?></div>
                                                <div style="text-align: center;"><?= nf($contract['f_salesSumm']); ?>р.</div>
                                                <div style="text-align: center;">
                                                    <?
                                                    $credit = mfa(mysqlQuery("SELECT * FROM `f_credits` WHERE `f_creditsSalesID` = '" . $contract['idf_sales'] . "'")) ?? [];

                                                    if (count($credit)) {
                                                        ?><i class="fas fa-university" title="Кредит <?= number_format($credit['f_creditsSumm'], 0, ',', ' '); ?>"></i><? } ?>
                                                    <?
                                                    $installment = mfa(mysqlQuery("SELECT * FROM `f_installments` WHERE `f_installmentsSalesID` = '" . $contract['idf_sales'] . "'")) ?? [];

                                                    if (count($installment)) {
                                                        ?><i class="fas fa-file-signature" title="Рассрочка <?= number_format($installment['f_installmentsSumm'], 0, ',', ' '); ?>р."></i><? } ?>

                                                    <?
                                                    $payments = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID`='" . $contract['idf_sales'] . "'"));
                                                    if (count($payments)) {
                                                        foreach ($payments as $payment) {
                                                            ?>
                                                            <i class="far <?= $payment['f_paymentsType'] == 1 ? 'far fa-money-bill-alt' : 'fa-credit-card' ?>" title="<?= number_format($payment['f_paymentsAmount'], 0, ',', ' '); ?>р."></i><?
                                                        }
                                                    }
                                                    if ($contract['f_salesCancellationDate']) {
                                                        ?>
                                                        <i class="fas fa-times-circle" style="color: red;" title="Возврат"></i>
                                                        <?
                                                    }
                                                    ?>

                                                </div>
                                                <div style="text-align: right;">

                                                    <?
                                                    if (round($contract['f_salesSumm'], 2)) {
                                                        print round((((($credit['f_creditsSumm'] ?? 0) + array_sum(array_column($payments, 'f_paymentsAmount'))) / $contract['f_salesSumm']) * 100)) . '%';
                                                    } else {
                                                        ?>???<? }
                                                    ?>

                                                </div>

                                                <div class="C"><a href="/sync/utils/word/?contract=<?= $contract['idf_sales']; ?>"><i class="fas fa-print"></i></a></div>

                                                <div class="C">
                                                    <a onclick="printTax(<?= $contract['idf_sales']; ?>);"><i class="fas fa-print"></i></a>
                                                </div>
                                            </div>
                                            <?
                                        }
                                        ?>
                                        <div style=" display: contents;">
                                            <div style="grid-column: span 5;" class="B R">Итого абонементов на сумму:</div>
                                            <div class="B R"><?= nf(array_sum(array_column($contracts, 'f_salesSumm'))) ?>р.</div>
                                            <div style="grid-column: span 4;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                let clientsTaxPersons = <?= json_encode($clientsTaxPersons, 288); ?>;

                            </script>
                            <?
                        } else {
                            ?>
                            <h3 style="text-align: center; padding: 20px;">Договоры не внесены</h3>
                            <?
                        }
                        ?>
                    </div>

                <? } ?>

            </div>
        </div>

        <?
    }
    ?>

    </div>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
