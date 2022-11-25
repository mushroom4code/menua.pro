<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$record = mfa(mysqlQuery("SELECT *"
                . ""
                . " FROM `medrecords`"
                . " LEFT JOIN `clients` ON (`idclients` = `medrecordsClient`)"
                . " LEFT JOIN `medrecordsForms` ON (`idmedrecordsForms` = `medrecordsForm`)"
                . " LEFT JOIN `users` ON (`idusers` = `medrecordsUser`)"
                . " WHERE `idmedrecords` = '" . mres($_GET['record']) . "'"));
$medrecordsFormData = json_decode($record['medrecordsFormData'], 1);

//printr($record);
?>

<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Other/html.html to edit this template
-->
<html>
    <head>
        <title>Осмотр</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>



        <h2>Осмотр <?= $record['medrecordsFormsName']; ?></h2>
        <h3>Дата осмотра: ______<u><?= date("d.m.Y", strtotime($record['medrecordsTime'])); ?></u>_____</h3>
        <h3>Ф.И.О. пациента:<u>__<?= $record['clientsLName'] ?>_<?= $record['clientsFName'] ?>_<?= $record['clientsMName'] ?>____</u></h3>
        <h3>Дата рождения:<u>__________<?= $record['clientsBDay'] ? date("d.m.Y", strtotime($record['clientsBDay'])) : 'Не указана'; ?>__________________</u></h3>
        <h3>Осмотр провёл:<u>_____________<?= $record['usersLastName'] ?>_<?= $record['usersFirstName'] ?>_<?= $record['usersMiddleName'] ?>_______________</u></h3>


        <?php
        foreach ($medrecordsFormData as $key => $value) {
            ?>
            <div style="margin-bottom: 10px; border-bottom: 1px solid silver;"><b><?= $key ?></b>: <?
                    if (is_array($value)) {
                        ?><?= implode(', ', $value); ?><?
                    } else {
                        ?><?= $value; ?><?
                    }
                    ?></div>
                <?
            }
            ?>
    </body>
</html>
