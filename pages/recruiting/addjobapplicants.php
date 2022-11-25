<?php
$load['title'] = $pageTitle = 'Рекрутинг/Добавление';

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

if (R(201)) {
    if ($_POST) {
//        printr($_POST);
        mysqlQuery("INSERT INTO `jobapplicants` SET "
                . " `jobapplicantsLName` = '" . mres($_POST['lname']) . "',"
                . " `jobapplicantsFName` = '" . mres($_POST['fname']) . "',"
                . " `jobapplicantsMName` = '" . mres($_POST['mname']) . "',"
//                . " `jobapplicantsPassportNumber` = '" . mres($_POST['passport']) . "',"
                . " `jobapplicantsPhone` = '" . mres($_POST['phone']) . "',"
                . " `jobapplicantsAddedBy` = '" . mres($_USER['id']) . "',"
                . " `jobapplicantsPosition` = '" . mres($_POST['position']) . "'");
        header("Location: " . GR2());
        exit('ok');
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<?
if (!R(201)) {
    ?>E403R201<?
    include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
    exit();
}
include 'menu.php';
$jobapplicants = query2array(mysqlQuery("SELECT * FROM `jobapplicants` LEFT JOIN `positions` ON (`idpositions` = `jobapplicantsPosition`) WHERE date(`jobapplicantsAdded`) = CURDATE()"));
$positions = query2array(mysqlQuery("SELECT * FROM `positions` ORDER BY `positionsName`"));
?>
<div class="box neutral">
    <div class="box-body">
        <a href="jobapplikantblank.docx" style="padding: 10px; display: inline-block;">Анкета</a>
        <form action="?" method="POST">
            <table border="1" style="border-collapse: collapse;">
                <tr>
                    <th style="padding: 3px 6px;">Фамилия</th>
                    <th style="padding: 3px 6px;">Имя</th>
                    <th style="padding: 3px 6px;">Отчество</th>
                    <th style="padding: 3px 6px;">Телефон</th>
                    <!--<th style="padding: 3px 6px;">№ паспорта</th>-->
                    <th style="padding: 3px 6px;">Должность</th>
                    <th style="padding: 3px 6px;"></th>
                </tr>
                <tr>
                    <th style="padding: 3px 6px;"><input type="text" name="lname" placeholder="Фамилия"></th>
                    <th style="padding: 3px 6px;"><input type="text" name="fname" placeholder="Имя"></th>
                    <th style="padding: 3px 6px;"><input type="text" name="mname" placeholder="Отчество"></th>
                    <th style="padding: 3px 6px;"><input type="text" name="phone" placeholder="Телефон"></th>
                    <!--<th style="padding: 3px 6px;"><input type="text" name="passport" placeholder="№ паспорта"></th>-->
                    <th style="padding: 3px 6px;">
                        <select name="position" id="position">
                            <option value="">Выбрать должность</option>
                            <?
                            foreach ($positions as $position) {
                                if ($position['positionsDeleted']) {
                                    continue;
                                }
                                ?>   <option value="<?= $position['idpositions']; ?>"><?= $position['positionsName']; ?></option><? }
                            ?>
                        </select>
                    </th>
                    <th>
                        <input type="submit" onclick="if (!document.querySelector(`#position`).value) {
                                    alert('Укажите должность');
                                    return false;
                                }" value="Сохранить">
                    </th>
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
                        <td><?= $jobapplicant['positionsName']; ?></td>
                        <td><?= date("d.m.Y H:i", strtotime($jobapplicant['jobapplicantsAdded'])); ?></td>

                    </tr>
                <? }
                ?>
            </table>
        </form>
    </div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
