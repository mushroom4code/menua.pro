<?php
$pageTitle = '';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (
        trim($_POST['fsale'] ?? '') !== '' &&
        trim($_POST['service'] ?? '') !== '' &&
        trim($_POST['before'] ?? '') !== '' &&
        trim($_POST['after'] ?? '') !== ''
) {

    $subs = query2array(mysqlQuery("SELECT * FROM `f_subscriptions` WHERE `f_subscriptionsContract` = " . sqlVON($_POST['fsale']) . " and `f_salesContentService` = " . sqlVON($_POST['service']) . " and `f_salesContentPrice`=" . sqlVON($_POST['before']) . ""));
    $saps = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `servicesAppliedContract` = " . sqlVON($_POST['fsale']) . " and `servicesAppliedService` = " . sqlVON($_POST['service']) . " and `servicesAppliedPrice`=" . sqlVON($_POST['before']) . ""));

    if (mysqlQuery("update f_subscriptions SET f_salesContentPrice = " . sqlVON($_POST['after']) . " where f_subscriptionsContract = " . sqlVON($_POST['fsale']) . " and f_salesContentService = " . sqlVON($_POST['service']) . " and f_salesContentPrice=" . sqlVON($_POST['before']) . "") && mysqlQuery("update servicesApplied SET servicesAppliedPrice = " . sqlVON($_POST['after']) . " where servicesAppliedContract = " . sqlVON($_POST['fsale']) . " and servicesAppliedService = " . sqlVON($_POST['service']) . " and servicesAppliedPrice=" . sqlVON($_POST['before']) . "")) {
//		header("Location: " . GR());
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
    <div class="box-body">
        <form action="?" method="POST">
            <table>
                <tr>
                    <td>Абон</td><td><input type="text" name="fsale"></td>
                </tr>
                <tr>
                    <td>Проц</td><td><input type="text" name="service"></td>
                </tr>
                <tr>
                    <td>Ц.до</td><td><input type="text" name="before"></td>
                </tr>
                <tr>
                    <td>Ц.сделать</td><td><input type="text" name="after"></td>
                </tr>
            </table>
            <input type="submit" value="Сохранить">
        </form>
        <?
        if (($subs ?? false) || ($saps ?? false)) {
            ?>
            В абонементах найдено <?= count($subs ?? []); ?><br>
            В пройденных процедурах найдено <?= count($saps ?? []); ?><br>
            <?
        }
        ?>
    </div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
