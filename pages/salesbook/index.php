<?php
$pageTitle = 'Книга продаж';
mb_internal_encoding("UTF-8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(44)) {
    
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(44)) {
    ?>E403R27<?
} else {
    ?>
    <style>
        .active>div{
            background-color: wheat;
        }
    </style>
    <div style="display: inline-block;">
        <div style="padding: 20px  20px 0px  20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 10px;">
                <div><input type="date" value="<?= $_GET['statFrom'] ?? date("Y-m-01"); ?>" onchange="GETreloc('statFrom', this.value);"></div>
                <div><input type="date" value="<?= $_GET['statTo'] ?? date("Y-m-d"); ?>" onchange="GETreloc('statTo', this.value);"></div>
            </div>
        </div>
    </div>
    <?
    $dateFrom = $_GET['statFrom'] ?? date("Y-m-01");
    $datTo = $_GET['statTo'] ?? date("Y-m-d");

    $salesSQL = "SELECT * FROM `f_sales` WHERE `f_salesDate`>='" . $dateFrom . "' AND `f_salesDate`<='" . $datTo . "'"; // WHERE `f_salesCreditManager` ='111'

    $sales = query2array(mysqlQuery($salesSQL));

    $f_payments = query2array(mysqlQuery("SELECT * FROM `f_payments` "
                    . "left join `f_sales` on (`idf_sales`= `f_paymentsSalesID`)"
                    . "left join `clients` on (`idclients` = `f_salesClient`)"
                    . " WHERE `f_paymentsDate`>='" . $dateFrom . " 00:00:00' AND `f_paymentsDate`<='" . $datTo . " 23:59:59' ;"));

    $f_credits = query2array(mysqlQuery("SELECT * FROM `f_credits` left join `f_sales` on (`idf_sales`= `f_creditsSalesID`)"
                    . "left join `clients` on (`idclients` = `f_salesClient`)"
                    . " WHERE `f_salesDate`>='" . $dateFrom . "' AND  `f_salesDate`<='" . $datTo . "';"));
    $payments = [];

    foreach ($f_payments as $f_payment) {
        $payments[] = [
            'date' => date("d.m.Y", strtotime($f_payment['f_paymentsDate'])),
            'name' => $f_payment['clientsLName'] . ' ' . $f_payment['clientsFName'] . ' ' . $f_payment['clientsMName'],
            'summ' => $f_payment['f_paymentsAmount'],
            'f_salesClient' => $f_payment['f_salesClient'],
            'idf_sales' => $f_payment['idf_sales'],
            'f_salesCreditManager' => $f_payment['f_salesCreditManager'],
            'f_salesAdvancePayment' => $f_payment['f_salesAdvancePayment'],
            'f_salesDate' => $f_payment['f_salesDate'],
            'f_salesType' => $f_payment['f_salesType'],
            'f_salesSumm' => $f_payment['f_salesSumm'],
            'type' => ['1' => 'кэш', '2' => 'экв', '3' => 'бал'][$f_payment['f_paymentsType']],
        ];
    }

    foreach ($f_credits as $f_credit) {
        $payments[] = [
            'date' => date("d.m.Y", strtotime($f_credit['f_salesDate'])),
            'name' => $f_credit['clientsLName'] . ' ' . $f_credit['clientsFName'] . ' ' . $f_credit['clientsMName'],
            'summ' => $f_credit['f_creditsSumm'],
            'idf_sales' => $f_credit['idf_sales'],
            'f_salesClient' => $f_credit['f_salesClient'],
            'f_salesCreditManager' => $f_credit['f_salesCreditManager'],
            'f_salesAdvancePayment' => $f_credit['f_salesAdvancePayment'],
            'f_salesDate' => $f_credit['f_salesDate'],
            'f_salesType' => $f_credit['f_salesType'],
            'idf_credits' => $f_credit['idf_credits'],
            'f_salesSumm' => $f_credit['f_creditsSumm'],
            'type' => 'банк',
        ];
    }


    usort($sales, function ($a, $b) {
        return $a['f_salesDate'] <=> $b['f_salesDate'];
    });

    usort($payments, function ($a, $b) {
        if ($a['date'] <=> $b['date']) {
            return $a['date'] <=> $b['date'];
        }
        return $a['idf_sales'] <=> $b['idf_sales'];
    });
    ?>
    <div class="box neutral">
        <div class="box-body">
            <div style="display: inline-block">
                <div class="lightGrid" style="display: grid; white-space: nowrap; font-size: 8pt; line-height: 8pt; grid-template-columns: auto auto auto auto auto  auto auto auto auto auto auto auto auto auto auto auto auto auto auto;">
                    <div style="display: contents; font-weight: bold; text-align: center;">
                        <div>IDDB</div>
                        <div>Дата</div>
                        <div>Кредитный</div>
                        <div>Координатор</div>
                        <div>коэф<br>продажи</div>
                        <div>Первичка</div>
                        <div>Вторичка</div>
                        <div>Клиент</div>
                        <div>Cумма<br>по<br>договору</div>
                        <div>Абонемент</div>
                        <div>КЭШ<br>упало<br>банк</div>
                        <div>Банк</div>
                        <div>Cумма</div>
                        <div>АП</div>
                        <div>Первы<br>й<br>взнос</div>
                        <div>Кол-<br>во<br>месяц</div>
                        <div>Дата<br>расторжен<br>ия</div>
                        <div>№ КД</div>
                        <div>ЗП</div>
                    </div>
                    <?
                    foreach ($payments as $pSale) {
                        if (!($pSale['f_salesClient'] ?? false)) {
//							printr($pSale);
                            continue;
                        }
                        $pSale['client'] = mfa(mysqlQuery("SELECT * FROM `clients` where `idclients` = '" . $pSale['f_salesClient'] . "' "));
                        $pSale['client']['clientsLName'] = mb_ucfirst($pSale['client']['clientsLName']);
                        $pSale['client']['clientsFName'] = mb_ucfirst($pSale['client']['clientsFName']);
                        $pSale['client']['clientsMName'] = mb_ucfirst($pSale['client']['clientsMName']);

                        $pSale['subscriptions'] = query2array(mysqlQuery(""
                                        . "SELECT `idservicesTypes`,`servicesTypesName`"
                                        . "FROM `f_subscriptions` "
                                        . "LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
                                        . "LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"
                                        . "WHERE `f_subscriptionsContract`=  '" . $pSale['idf_sales'] . "'"
                                        . "GROUP BY `idservicesTypes`"));

                        $pSale['payments'] = query2array(mysqlQuery(""
                                        . "SELECT * "
                                        . "FROM `f_payments` "
                                        . "WHERE `f_paymentsSalesID` = '" . $pSale['idf_sales'] . "'"));

                        if ($pSale['idf_credits'] ?? false) {
                            $pSale['credit'] = mfa(mysqlQuery(""
                                            . "SELECT * "
                                            . "FROM `f_credits` "
                                            . "LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`)"
                                            . "WHERE `idf_credits` = '" . $pSale['idf_credits'] . "' "));
                        } else {
                            $pSale['credit'] = [];
                        }


                        $pSale['manager'] = mfa(mysqlQuery("SELECT * FROM `users` where `idusers` = '" . $pSale['f_salesCreditManager'] . "' "));
                        $pSale['curators'] = query2array(mysqlQuery("SELECT *, CONCAT(`usersLastName`, ' ', `usersFirstName`) as `fUname` FROM `f_salesToCoord` LEFT JOIN `users` ON (`idusers` = `f_salesToCoordCoord`) WHERE `f_salesToCoordSalesID`  = '" . $pSale['idf_sales'] . "'"));
                        $pSale['participants'] = query2array(mysqlQuery("SELECT *, CONCAT(`usersLastName`, ' ', `usersFirstName`) as `fUname`  FROM `f_salesToPersonal` LEFT JOIN `users` ON (`idusers` = `f_salesToPersonalUser`) WHERE `f_salesToPersonalSalesID`  = '" . $pSale['idf_sales'] . "' "));
                        ?>
                        <div style="display: contents;" onclick="this.classList.toggle('active');">
                            <div><?= $pSale['idf_sales']; ?></div>
                            <div><?= date("d.m.Y", strtotime($pSale['f_salesDate'])); ?></div>
                            <div style="text-align: center;"><?= ($pSale['manager']['usersLastName'] ?? '--') . ' ' . ($pSale['manager']['usersFirstName'] ?? '--'); ?></div>
                            <div style="text-align: center;">
                                <?= implode('/', array_column($pSale['curators'], 'fUname')); ?>
                            </div>
                            <div style="text-align: center;"><?= count($pSale['participants']) ? round(1 / count($pSale['participants']), 2) : 'Без участников'; ?> </div>
                            <div>

                                <?= $pSale['f_salesType'] == '1' ? implode(' / ', array_column($pSale['participants'], 'fUname')) : ''; ?>
                            </div>
                            <div>
                                <?= $pSale['f_salesType'] == '2' ? implode(' / ', array_column($pSale['participants'], 'fUname')) : ''; ?>
                            </div>
                            <div>
                                <?= $pSale['client']['clientsLName'] . ' ' . $pSale['client']['clientsFName'] . ' ' . $pSale['client']['clientsMName']; ?>
                            </div>
                            <div style="text-align: right;">
                                <?= isset($pSale['credit']['f_creditsSummIncInterest']) ? nf($pSale['credit']['f_creditsSummIncInterest'], 2) : nf($pSale['f_salesSumm'], 2); ?>
                            </div>
                            <div>
                                <?= implode(' / ', array_unique(array_filter(array_column($pSale['subscriptions'], 'servicesTypesName')))); ?>
                            </div>

                            <div><?= $pSale['summ'] ?? '??' ?></div>
                            <div><?= $pSale['credit']['RS_banksName'] ?? $pSale['type']; ?></div>

                            <div style="text-align: right;">
                                <?= nf($pSale['f_salesSumm'], 2); ?>
                            </div>
                            <div><?= ($pSale['f_salesAdvancePayment'] ?? false) ? 'АП' : '';
                                ?></div>
                            <div style="text-align: right;"><?= nf(array_sum(array_column($pSale['payments'], 'f_paymentsAmount')), 2); ?></div>
                            <div style="text-align: center;"><?= $pSale['credit']['f_creditsMonthes'] ?? '' ?></div>
                            <div></div>
                            <div><?= $pSale['credit']['f_creditsBankAgreementNumber'] ?? '' ?></div>
                            <div></div>
                        </div>
                        <?
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <?
//	printr($sales);
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
