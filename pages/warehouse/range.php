<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!isset($_GET['from']) || !isset($_GET['to'])) {
    ?>
    <script src="/sync/js/basicFunctions.js" type="text/javascript"></script>
    c <input type="date" onchange="GR({from: this.value})" value="<?= $_GET['from'] ?? ''; ?>">
    по <input type="date" onchange="GR({to: this.value})" value="<?= $_GET['to'] ?? ''; ?>">
    <?
} else {
    $units = query2array(mysqlQuery("SELECT * FROM `units`"));
    $spendsSQL = "SELECT 
     `idWH_goods`,`WH_goodsName`, `WH_goodBarCode`, `WH_goodsOutUnits`, SUM(`WH_goodsOutQty`) AS `qty`, `WH_goodsPrice`
FROM
    `WH_goodsOut`
        LEFT JOIN
    `WH_goods` ON (`idWH_goods` = `WH_goodsOutItem`)
    
WHERE
    `WH_goodsOutDate` >= '" . $_GET['from'] . " 00:00:00'
        AND `WH_goodsOutDate` <= '" . $_GET['to'] . " 23:59:59'
        AND ISNULL(`WH_goodsOutDeleted`)
GROUP BY `idWH_goods`, `WH_goodsOutUnits`;";
//	print $spendsSQL;
    $spends = query2array(mysqlQuery($spendsSQL));
//	print mysqli_error($link);
    usort($spends, function ($a, $b) {
        return mb_strtolower($a['WH_goodsName']) <=> mb_strtolower($b['WH_goodsName']);
    });
    ?>
    <table border='1' cellpadding="5" style="border-collapse: collapse;">
        <tr>
            <th>#</th>
            <th>Наименование</th>
            <th>ШК</th>
            <th>кол-во</th>
            <th>ед.изм</th>
            <th>цена</th>
            <th>стоимость</th>
        </tr>
        <?
        $total = 0;
        $n = 0;
        foreach ($spends as $spend) {
            if (!$spend['WH_goodsPrice']) {
                $n++;
            }
            ?>
            <tr>
                <td style="text-align: right;"><?= $spend['idWH_goods']; ?>]</td>
                <td> <?= $spend['WH_goodsName']; ?></td>
                <td style="text-align: cener;"><?= ($spend['WH_goodBarCode']); ?></td>
                <td style="text-align: right;"><?= round($spend['qty'], 3); ?></td>
                <td style="text-align: center;"><?= array_search_2d($spend['WH_goodsOutUnits'], $units, 'idunits')['unitsName'] ?? 'Не указана'; ?></td>
                <td style="width: 150px; text-align: right;"><?= $spend['WH_goodsPrice'] ? nf($spend['WH_goodsPrice'] ?? 0, 2) : ''; ?></td>
                <td style="width: 150px; text-align: right;"><?= $spend['WH_goodsPrice'] ? (nf($spend['qty'] * ($spend['WH_goodsPrice'] ?? 0), 2)) : ''; ?></td>
            </tr>
            <?
            $total += ($spend['qty'] ?? 0) * ($spend['WH_goodsPrice'] ?? 0);
        }
        ?>
        <tr>
            <td colspan="6" style="text-align: right;">Итого:</td>
            <td style="text-align: right;"><?= nf($total, 2); ?></td>
        </tr>
        <? if ($n) { ?>
            <tr>
                <td colspan="6" style="text-align: right;">Не внесено (<?= $n ?>):</td>
                <td style="text-align: right;"></td>
            </tr>
        <? } ?>


        <tr>
            <td colspan="6" style="text-align: right;">Всё вместе:</td>
            <td></td>
        </tr>


    </table>
    <?
}


