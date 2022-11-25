<?php
$load['title'] = $pageTitle = 'Рекрутинг/Отбор';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (1) {
    error_reporting(E_ALL); //
    ini_set('display_errors', 1);
}

/*
  "lname": "1",
  "fname": "3",
  "mname": "4",
  "phone": "5",
  "passport": "6",
  "position": "5" */

if (R(202)) {
    if ($_POST) {
        /*
          "idjobapplicants": "4",
          "jobapplicantsHR": "1188",
          "state": "approved" */

//        printr($_POST);
        if (
                ($_POST['idjobapplicants'] ?? false) &&
                ($_POST['jobapplicantsHR'] ?? false) &&
                ($_POST['state'] ?? false) &&
                ($_POST['position'] ?? false) &&
                ($jobapplicant = mfa(mysqlQuery("SELECT * FROM `jobapplicants` WHERE `idjobapplicants` = '" . mres($_POST['idjobapplicants']) . "'")))
        ) {
            mysqlQuery("UPDATE `jobapplicants` SET `jobapplicantsPosition` = " . mres($_POST['position']) . " WHERE `idjobapplicants` = '" . mres($_POST['idjobapplicants']) . "'");

            if ($_POST['state'] == 'approved') {

                mysqlQuery("INSERT INTO `users` SET "
                        . "`usersLastName` = '" . $jobapplicant['jobapplicantsLName'] . "',"
                        . "`usersFirstName` = '" . $jobapplicant['jobapplicantsFName'] . "',"
                        . "`usersMiddleName` = '" . $jobapplicant['jobapplicantsMName'] . "',"
                        . "`usersGroup` = '18',"
                        . "`usersBarcode` = '" . RDS(16, true) . "'"
                        . "");
                $user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . mysqli_insert_id($link) . "'"));
                mysqlQuery("INSERT INTO `usersPositions` SET"
                        . " `usersPositionsUser`='" . $user['idusers'] . "',"
                        . " `usersPositionsPosition` = '" . $_POST['position'] . "'");
            }
            mysqlQuery("UPDATE `jobapplicants` SET "
                    . "`jobapplicantsHR`='" . mres($_POST['jobapplicantsHR']) . "',"
                    . "`" . ['approved' => 'jobapplicantsApproved', 'rejected' => 'jobapplicantsRejected'][$_POST['state']] . "`=NOW(),"
                    . "`jobapplicantsARBy`='" . $_USER['id'] . "',"
                    . "`jobapplicantsUser`=" . (($_POST['state'] == 'approved') ? $user['idusers'] : 'null') . ""
                    . " WHERE "
                    . "`idjobapplicants`='" . mres($_POST['idjobapplicants']) . "'"
                    . "");
        }
        header("Location: " . GR2());
        exit('ok');
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<?
if (!R(202)) {
    ?>E403R202<?
    include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
    exit();
}
include 'menu.php';
$jobapplicants = query2array(mysqlQuery("SELECT * FROM `jobapplicants`"
                . " LEFT JOIN `positions` ON (`idpositions` = `jobapplicantsPosition`)"
                . " LEFT JOIN `users` ON (`idusers` = `jobapplicantsHR`)"
                . " WHERE date(`jobapplicantsAdded`) = CURDATE()"));
$positions = query2array(mysqlQuery("SELECT * FROM `positions` ORDER BY `positionsName`"));
?>
<div class="box neutral">
    <div class="box-body">
        <form action="?" method="POST">
            <table border="1" style="border-collapse: collapse;">
                <tr>
                    <th style="padding: 3px 6px;">Фамилия</th>
                    <th style="padding: 3px 6px;">Имя</th>
                    <th style="padding: 3px 6px;">Отчество</th>
                    <th style="padding: 3px 6px;">Телефон</th>
<!--                    <th style="padding: 3px 6px;">№ паспорта</th>-->
                    <th style="padding: 3px 6px;">Должность</th>
                    <th style="padding: 3px 6px;">Добавлен</th>
                    <th style="padding: 3px 6px;">HR</th>
                    <th style="padding: 3px 6px;">Статус</th>
                    <th style="padding: 3px 6px;"></th>
                </tr>
                <?
                foreach ($jobapplicants as $jobapplicant) {
//                idjobapplicants, jobapplicantsLName, jobapplicantsFName, jobapplicantsMName, jobapplicantsPassportNumber, jobapplicantsPhone, jobapplicantsAdded, jobapplicantsAddedBy
                    ?>
                    <tr>
                        <td><?= $jobapplicant['jobapplicantsLName']; ?></td>
                        <td><?= $jobapplicant['jobapplicantsFName']; ?></td>
                        <td><?= $jobapplicant['jobapplicantsMName']; ?></td>
                        <td><?= $jobapplicant['jobapplicantsPhone']; ?></td>
                        <!--<td><?= $jobapplicant['jobapplicantsPassportNumber']; ?></td>-->
                        <td><?
                            if ($jobapplicant['jobapplicantsApproved'] || $jobapplicant['jobapplicantsRejected']) {
                                ?>
                                <?= $jobapplicant['positionsName']; ?>
                                <?
                            } else {
                                ?>
                                <select name="position" id="position">
                                    <option value="">Выбрать должность</option>
                                    <?
                                    foreach ($positions as $position) {
                                        if ($position['positionsDeleted']) {
                                            continue;
                                        }
                                        ?>   <option<?= $position['idpositions'] == $jobapplicant['jobapplicantsPosition'] ? ' selected' : '' ?> value="<?= $position['idpositions']; ?>"><?= $position['positionsName']; ?></option><? }
                                    ?>
                                </select>
                                <?
                            }
                            ?>

                        </td>
                        <td><?= date("d.m.Y H:i", strtotime($jobapplicant['jobapplicantsAdded'])); ?></td>
                        <td><?
                            if ($jobapplicant['jobapplicantsHR']) {
                                ?>
                                <?= $jobapplicant['usersLastName']; ?>
                                <?= $jobapplicant['usersFirstName']; ?>
                                <?= $jobapplicant['usersMiddleName']; ?>
                                <?
                            }
                            ?>
                            <? if (!$jobapplicant['jobapplicantsHR']) {
                                ?>
                                <input type="hidden" name="idjobapplicants" value="<?= $jobapplicant['idjobapplicants']; ?>">
                                <select autocomplete="on" name="jobapplicantsHR"> 
                                    <option value=""></option>
                                    <?
                                    foreach ((query2array(mysqlQuery("SELECT * FROM `users` LEFT JOIN `usersPositions` ON (`usersPositionsUser` = `idusers`) WHERE isnull(`usersDeleted`) AND `usersPositionsPosition` = 28 AND `usersGroup`=14 ORDER BY `usersLastName`,`usersFirstName`,`usersMiddleName`")) ?? []) as $user) {
                                        ?>
                                        <option<?= ($user['idusers'] == $_USER['id']) ? ' selected' : '' ?> value="<?= $user['idusers']; ?>">
                                            <?= $user['usersLastName']; ?>
                                            <?= $user['usersFirstName']; ?>
                                            <?= $user['usersMiddleName']; ?>
                                        </option>
                                        <?
                                    }
                                    ?>
                                </select>
                                <?
                            }
                            ?>

                        </td>
                        <td class="C">
                            <?
                            if ($jobapplicant['jobapplicantsApproved'] && $jobapplicant['jobapplicantsUser']) {
                                $jobapplicantUser = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . $jobapplicant['jobapplicantsUser'] . "'"));
                                ?>
                                <a target="_blank" href="/sync/plugins/barcodePrint.php?BC=<?= $jobapplicantUser['usersBarcode']; ?>&FN=<?= $jobapplicantUser['usersFirstName']; ?>&LN=<?= $jobapplicantUser['usersLastName']; ?>">
                                    <i class="far fa-address-card" style="color: green;"></i> Утвержден
                                </a>
                            <? } elseif ($jobapplicant['jobapplicantsRejected']) { ?>
                                <i class="far fa-times-circle" style="color: red;"></i> Отклонён
                            <? } else { ?>
                                <select autocomplete="off" name="state">
                                    <option value=""></option>
                                    <option value="approved">Утверждён</option>
                                    <option value="rejected">Отклонён</option>
                                </select>
                            <? } ?>



                        </td>
                        <td>
                            <? if ($jobapplicant['jobapplicantsARBy']) {
                                ?>
                                <?= date("d.m.Y H:i", strtotime($jobapplicant['jobapplicantsApproved'] ?? $jobapplicant['jobapplicantsRejected'])); ?>
                            <? } else {
                                ?>
                                <input type="submit" value="Сохранить">
                            <? } ?>

                        </td>
                    </tr>
                <? }
                ?>


            </table>
        </form>
    </div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
