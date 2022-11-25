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
    $groups = [
        1 => "BETWEEN 13 AND 1000",
        2 => "BETWEEN 9 AND 12",
        3 => "BETWEEN 6 AND 8",
        4 => "BETWEEN 3 AND 5"
    ];
    $clients = query2array(mysqlQuery("select *,
        TIMESTAMPDIFF(MONTH,(select max(clientsVisitsDate) FROM `" . ($_GET['db'] ?? 'warehouse') . "`.`clientsVisits` WHERE clientsVisitsClient = idclients),CURDATE()) as `monthes`,
        TIMESTAMPDIFF(YEAR, clientsBDay,CURDATE()) as `age`,
        (SELECT MAX(`f_salesSumm`) FROM `" . ($_GET['db'] ?? 'warehouse') . "`.`f_sales` WHERE `f_salesClient` = `idclients`) as `maxSumm`
        FROM `" . ($_GET['db'] ?? 'warehouse') . "`.`clients`
        HAVING `monthes` " . $groups[$_GET['group']] . ""
                    . " AND `maxSumm` >= 15000"
                    . " AND `age` >= 35"
                    . " ORDER BY `monthes` DESC, `clientsLName`,`clientsFName`,`clientsMName` ;"));
    $n = 0;
    ?>


    <div class="box neutral">
        <div class="box-body">
            <? include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/calls/callsmenu.php'; ?>
            <?= count($clients); ?>
            <table border="1" style=" border-collapse: collapse;">
                <? foreach ($clients as $client) {
                    ?>
                    <tr>
                        <td><?= ++$n; ?></td>
                        <td>
                            <a target="_blank" href="https://<?= ($_GET['db'] ?? false) ? 'flash.' : ''; ?>menua.pro/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>">
                                <?= $client['clientsLName'] . ' ' . $client['clientsFName'] . ' ' . $client['clientsMName']; ?>
                            </a>
                        </td>
                        <td>
                            <?= $client['monthes']; ?> мес.
                        </td>
        <!--                        <td>
                        <?= $client['maxSumm']; ?>
                        </td>
                        <td>
                        <?= $client['age']; ?>
                        </td>-->
                    </tr>
                <? }
                ?>
            </table>
        </div>
    </div>
    <?
    print microtime(1) - $start;
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
