<?php
$pageTitle = 'Участники продажи';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (($_POST['action'] ?? '') == 'save') {
//    printr($_POST);
    mysqlQuery("DELETE FROM `f_salesToPersonal` WHERE `f_salesToPersonalSalesID` = '" . mres($_GET['sale'] ?? '0') . "'");
    mysqlQuery("DELETE FROM `f_salesToCoord` WHERE `f_salesToCoordSalesID` = '" . mres($_GET['sale'] ?? '0') . "'");
    mysqlQuery("DELETE FROM `f_salesRoles` WHERE `f_salesRolesSale` = '" . mres($_GET['sale'] ?? '0') . "'");
    sendTelegram('sendMessage', ['chat_id' => '-799645465', 'text' => '👁👁' . $_USER['lname'] . ' ' . $_USER['fname'] . ' исправляет участников продажи ' . $_GET['sale']]);
    foreach (($_POST['role'] ?? []) as $user => $role) {
        if (in_array($role, ['1', '2', '3'])) {
            mysqlQuery("INSERT INTO `f_salesToPersonal` SET `f_salesToPersonalSalesID` = '" . mres($_GET['sale'] ?? '0') . "', `f_salesToPersonalUser` = '" . mres($user) . "'");
//			print "INSERT INTO `f_salesToPersonal` SET `f_salesToPersonalSalesID` = '" . mres($_GET['sale'] ?? '0') . "', `f_salesToPersonalUser` = '" . mres($user) . "'";
        }
        if (in_array($role, ['4'])) {
            mysqlQuery("INSERT INTO `f_salesToCoord` SET `f_salesToCoordSalesID` = '" . mres($_GET['sale'] ?? '0') . "', `f_salesToCoordCoord` = '" . mres($user) . "'");
//			print "INSERT INTO `f_salesToCoord` SET `f_salesToCoordSalesID` = '" . mres($_GET['sale'] ?? '0') . "', `f_salesToCoordCoord` = '" . mres($user) . "'";
        }

        if (in_array($role, ['5'])) {
            mysqlQuery("UPDATE `f_sales` SET `f_salesCreditManager` = '" . mres($user) . "' WHERE `idf_sales`='" . mres($_GET['sale'] ?? '0') . "'");
//			print "UPDATE `f_sales` SET `f_salesCreditManager` = '" . mres($user) . "' WHERE `idf_sales`='" . mres($_GET['sale'] ?? '0') . "'";
        }
        if ($role) {
            mysqlQuery("INSERT INTO `f_salesRoles` SET `f_salesRolesSale` = '" . mres($_GET['sale'] ?? '0') . "', `f_salesRolesUser` = '" . mres($user) . "',`f_salesRolesRole` = '" . mres($role) . "'");
//			print "INSERT INTO `f_salesRoles` SET `f_salesRolesSale` = '" . mres($_GET['sale'] ?? '0') . "', `f_salesRolesUser` = '" . mres($user) . "',`f_salesRolesRole` = '" . mres($role) . "'";
        }
    }

    header("Location: " . GR());
    die();
}

$roles = query2array(mysqlQuery("SELECT * FROM `f_roles`"));

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
    <div class="box-body">


        <form>
            <input type="text" name="sale" style=" display: inline-block; width: auto;" value="<?= ($_GET['sale'] ?? ''); ?>">
            <input type="submit" value="найти продажу">
        </form>


        <?
        $sale = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales`='" . mres($_GET['sale'] ?? 0) . "'"));
        if ($sale && ($sale['f_salesDate'] == date('Y-m-d') || in_array($_USER['id'], [176, 135]))) {
            $users = query2array(mysqlQuery("SELECT * FROM `users`"
                            . "LEFT JOIN `f_salesToPersonal` ON (`f_salesToPersonalSalesID` = '" . mres($_GET['sale']) . "' AND `f_salesToPersonalUser` = `idusers`)"
                            . "LEFT JOIN `f_salesRoles` ON (`f_salesRolesSale` = '" . mres($_GET['sale']) . "' AND `f_salesRolesUser` = `idusers`)"
                            . " WHERE isnull(`usersDeleted`)"));
            usort($users, function ($a, $b) {
                if (($a['f_salesRolesRole'] == null) <=> ($b['f_salesRolesRole'] == null)) {
                    return ($a['f_salesRolesRole'] == null) <=> ($b['f_salesRolesRole'] == null);
                }

                if (($a['usersLastName']) <=> ($b['usersLastName'])) {
                    return ($a['usersLastName']) <=> ($b['usersLastName']);
                }


                if (($a['usersFirstName']) <=> ($b['usersFirstName'])) {
                    return ($a['usersFirstName']) <=> ($b['usersFirstName']);
                }
            });
            ?>
            <form action="<?= GR(); ?>" method="POST">
                <input type="hidden" name="action" value="save">
                <?
                foreach ($users as $user) {
                    ?>
                    <div style="padding: 1px;">
                        <select name="role[<?= $user['idusers']; ?>]" style=" display: inline-block; width: auto;" autocomplete="off">
                            <option></option>
                            <? foreach ($roles as $role) {
                                ?><option <?= ($user['f_salesRolesRole'] == $role['idf_roles']) ? 'selected' : ''; ?> value="<?= $role['idf_roles']; ?>"><?= $role['f_rolesName']; ?></option><? }
                            ?>
                        </select> <?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?> <?= $user['usersMiddleName']; ?>
                        <input type="submit" value="ok">
                    </div>
                    <?
                }
            } else {
                ?>
                Нет такой продажи, либо она старенькая;
                <?
            }
            ?>
        </form>
    </div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
