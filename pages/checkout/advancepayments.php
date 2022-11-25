<?php
$load['title'] = $pageTitle = 'Авансовые платежи';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(26)) {
    
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$advancepayments = query2array(mysqlQuery("SELECT 
  *
FROM
    `servicesApplied`
        LEFT JOIN `f_sales` ON (`idf_sales` = `servicesAppliedContract`)
        LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)
    
WHERE
    NOT ISNULL(`servicesAppliedContract`)
        AND NOT ISNULL(`f_salesAdvancePayment`)
        AND NOT ISNULL(`servicesAppliedFineshed`)
        AND NOT ISNULL(`servicesAppliedService`)
        AND  ISNULL(`servicesAppliedDeleted`)"));
?>
<div class="box neutral">
    <div class="box-body">
        <select onchange="GR({entity: this.value, confirm: null});" autocomplete="off">
            <option value="">Выбрать</option>
            <? foreach (query2array(mysqlQuery("SELECT * FROM `entities`")) as $saleEntity) {
                ?><option value="<?= $saleEntity['identities']; ?>" <?= ($_GET['entity'] ?? null) == $saleEntity['identities'] ? 'selected' : '' ?>><?= $saleEntity['entitiesName']; ?></option><? }
            ?>
        </select>
        <?
        if ($_GET['entity'] ?? false) {
            $advancepayments = query2array(mysqlQuery("SELECT 
  *
FROM
    `servicesApplied`
        LEFT JOIN `f_sales` ON (`idf_sales` = `servicesAppliedContract`)
        LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)
        LEFT JOIN `clients` ON (`idclients` = `servicesAppliedClient`)
    
WHERE
        `f_salesEntity`='" . $_GET['entity'] . "'
        AND NOT ISNULL(`servicesAppliedContract`)
        AND NOT ISNULL(`f_salesAdvancePayment`)
        AND NOT ISNULL(`servicesAppliedFineshed`)
        AND NOT ISNULL(`servicesAppliedService`)
        AND  ISNULL(`servicesAppliedDeleted`)
        AND  ISNULL(`servicesAppliedAdvancePayment`)
        ORDER BY `servicesAppliedClient`
		"));
            ?>
            <table>
                <?
                $clients = [];
                $prewClient = null;
                $clientsIndex = -1;
                foreach ($advancepayments as $advancepayment) {
                    if ($prewClient !== $advancepayment['idclients']) {
                        $clientsIndex++;
                        $prewClient = $advancepayment['idclients'];
                        $clients[$clientsIndex]['idclients'] = $advancepayment['idclients'];
                        $clients[$clientsIndex]['name'] = $advancepayment['clientsLName'] . ' ' . $advancepayment['clientsFName'] . ' ' . $advancepayment['clientsMName'];
                        ?>
                        <tr>
                            <th colspan="3" class="L">
                                <?= $advancepayment['clientsLName'] ?? '--'; ?>
                                <?= $advancepayment['clientsFName'] ?? '--'; ?>
                                <?= $advancepayment['clientsMName'] ?? '--'; ?>
                            </th>
                        </tr>
                        <?
                    }
                    ?>
                    <tr>
                            <!--<td><? printr($advancepayment); ?></td>-->
                        <td><?= $advancepayment['servicesName']; ?></td>
                        <td><?= $advancepayment['servicesAppliedQty']; ?></td>
                        <td><?= $advancepayment['servicesAppliedPrice']; ?></td>
                    </tr>
                    <?
                    if ($advancepayment['servicesAppliedPrice'] ?? 0) {//подготавливаем данные для эвотора, наверное уже лишнее, но пускай пока будет. Может по аналогии надо будет со СБИС делать.
                        $clients[$clientsIndex]['servicesApplied'][] = [
                            "code" => $advancepayment['idservices'],
                            "name" => $advancepayment['serviceNameShort'] ?? $advancepayment['servicesName'] ?? 'НЕИЗВЕСТНАЯ ПОЗИЦИЯ',
                            "productType" => "NORMAL",
                            "price" => ($advancepayment['servicesAppliedPrice'] ?? 0),
                            "quantity" => $advancepayment['servicesAppliedQty'],
                            "priceWithDiscount" => ($advancepayment['servicesAppliedPrice'] ?? 0),
                            "vat" => [null => 'NO_VAT', '0' => 'NO_VAT', '20' => 'VAT_18'][$advancepayment['servicesVat']]
                        ];
                    }
                }
                ?>
            </table>
            <?
//            printr($clients);
            ?>
            <input type="button" value="подтвердить" onclick="GR({confirm: true});"><!-- comment -->
            <? ?>
            <?
            if (count($clients ?? [])) {

                $url = 'https://dclubs.ru/evotor/orders/api/3rdparty/v2/order/' . EVOTORGUID;
                foreach ($clients as $client) {
                    $dataToSend = [
                        "type" => "SELL",
                        "number" => $client['name'] . ' ' . RDS(5),
                        "period" => time(),
                        "state" => "new",
                        "client" => $client['name'] . ' cписание услуг по авансам',
                        "id" => 'id' . RDS(),
                        "positions" => $client['servicesApplied']
                    ];

                    if (($_GET['confirm'] ?? false)) {

                        print mysqlQuery("UPDATE `servicesApplied`
        LEFT JOIN `f_sales` ON (`idf_sales` = `servicesAppliedContract`)
        LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)
    SET `servicesAppliedAdvancePayment`=NOW()
WHERE
        `f_salesEntity`='" . $_GET['entity'] . "'
        AND NOT ISNULL(`servicesAppliedContract`)
        AND NOT ISNULL(`f_salesAdvancePayment`)
        AND NOT ISNULL(`servicesAppliedFineshed`)
        AND NOT ISNULL(`servicesAppliedService`)
        AND  ISNULL(`servicesAppliedDeleted`)
        AND  ISNULL(`servicesAppliedAdvancePayment`)
        AND `servicesAppliedClient` = " . $client['idclients'] . "
		");

                        $ch = curl_init($url);
                        $payload = json_encode($dataToSend);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . EVOTORBearer]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $result = curl_exec($ch);
                        sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'Закрывающий чек:' . "\n\n" . $result]);
                        curl_close($ch);
                    }
                }


//				printr($dataToSend, 1);
            } else {
                ?>Нет услуг<?
            }
        }
        ?>



    </div>
</div>




<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
//printr($PGT);
