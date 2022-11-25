<?php
$load['title'] = $pageTitle = 'Инфи - ассистент';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(110)) {
    
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(110)) {
    ?>E403R110<?
} else {
    include $_SERVER['DOCUMENT_ROOT'] . '/pages/infi/menu.php';
    $_date = $_GET['date'] ?? date("Y-m-d");
    $_Y = ($_GET['Y'] ?? date("Y"));
    $_m = ($_GET['m'] ?? date("m"));
    ?>
    <div class="box neutral">
        <div class="box-body" style="min-width: 400px;">
            <h2>
                <div style="display: inline-block;">
                    <select onchange="GETreloc('m', this.value);">
                        <?
                        for ($m = 1; $m <= 12; $m++) {
                            ?><option value="<?= ($m < 10 ? '0' : '') . $m; ?>"<?= ($m == $_m ? ' selected' : ''); ?>><?= $_MONTHES['full']['nom'][$m]; ?></option><?
                        }
                        ?>
                    </select>
                </div>
                /
                <div style="display: inline-block;">
                    <select onchange="GETreloc('Y', this.value);">
                        <?
                        for ($Y = date("Y") + 1; $Y >= 2020; $Y--) {
                            ?><option value="<?= $Y; ?>"<?= ($Y == $_Y ? ' selected' : ''); ?>><?= $Y; ?></option><?
                        }
                        ?>
                    </select>
                </div>
            </h2>


            <?
            $allusers = query2array(mysqlQuery("SELECT * FROM "
                            . "`users`"
                            . "WHERE isnull(`usersDeleted`) AND NOT isnull(`usersGroup`)"));
            $users = query2array(mysqlQuery("SELECT * FROM "
                            . " `users`"
                            . " LEFT JOIN `f_planUsers` ON (`f_planUsersUser`=`idusers` AND `f_planUsersYear` = '" . mres($_Y) . "' AND `f_planUsersMonth` = '" . mres($_m) . "')"
                            . " WHERE `usersGroup` IN (1,2,3,4,5,7,11)"));
//			printr($users);
            uasort($users, function ($a, $b) {
                if ($a['f_planUsersTeamlid'] <=> $b['f_planUsersTeamlid']) {
                    return $b['f_planUsersTeamlid'] <=> $a['f_planUsersTeamlid'];
                }

                if ($a['usersLastName'] <=> $b['usersLastName']) {
                    return $a['usersLastName'] <=> $b['usersLastName'];
                }
            });
            ?>

            <br><br><br>
            <?

            function getMySales($userid, $from, $to) {
//totalQty
                $out = mfa(mysqlQuery("SELECT *,"
                                . "(SELECT SUM(`summ`) FROM (SELECT *, (
SELECT SUM(payment) FROM (SELECT
        SUM(f_paymentsAmount) as `payment`
    FROM
       f_payments
    
    WHERE
           f_paymentsSalesID = idf_sales

            UNION ALL
     SELECT
        SUM(f_creditsSumm) as `payment`
    FROM
       f_credits
    WHERE
        f_creditsSalesID=idf_sales) as `paymentsSelcet`

)/(SELECT COUNT(1) FROM `f_salesRoles` where `f_salesRolesSale` = `idf_sales`
                        AND `f_salesRolesRole` IN (1 , 2, 3)) as `summ`
						
FROM `f_salesRoles` LEFT JOIN `f_sales` ON (`idf_sales`=`f_salesRolesSale`) WHERE `f_salesRolesUser`=`idusers` AND `f_salesDate` >= '$from' AND `f_salesDate` <= '$to' AND `f_salesType` IN (1,2)) as `participants`
) as `totalQty`"
                                . " FROM `users` WHERE `idusers` = $userid"));

//				printr($out);
                return $out;
            }
            ?>


            <div class="lightGrid" style="display: grid; grid-template-columns: repeat(6,auto);">
                <div style="display: contents;">
                    <div class="C B"></div>
                    <div class="C B">Сотрудник</div>
                    <div class="C B">План на мес.</div>
                    <div class="C B">Выполнено</div>
                    <div class="C B">Осталось</div>
                    <div class="C B">Е.план</div>
                </div>
                <?
                uasort($allusers, function ($a, $b) {
                    return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
                });

                $teamPlanSumm = 0;
                $totalPlanSumm = 0;
                $teamPayedSumm = 0;
                $totaPayedSumm = 0;
                $teamAllSumm = 0;
                $totalAllSumm = 0;
                $n = 1;
                $oldTeamlid = 'none';
                foreach ($users as $user) {
                    $nDays = date("t", mktime(12, 0, 0, $_m, 1, $_Y));
                    $nDay = date("j", mktime(12, 0, 0, $_m, 1, $_Y));

                    $mysales = getMySales($user['idusers'], "$_Y-$_m-01", "$_Y-$_m-" . $nDays);

                    if ($user['usersDeleted'] && $mysales['totalQty'] == 0) {
                        continue;
                    }
                    $totalPlanSumm += $user['f_planUsersSales'];
                    $totalAllSumm += ($mysales['totalQty'] ?? 0);
                    ?>
                    <div style="display: contents; ">
                        <div><?= $n; //$user['usersGroup']                                                   ?></div>
                        <div<?= $user['usersDeleted'] ? ' style="color: red; text-decoration: line-through;"' : ''; ?>><?= $user['usersLastName'] ?> <?= $user['usersFirstName']; ?></div>
                        <div><input type="text" value="<?= $user['f_planUsersSales']; ?>" size="2" oninput="digon(); save('plan',<?= $user['idusers'] ?>,<?= $_Y; ?>,<?= $_m; ?>, this.value);"></div>
                        <div class="R"><?= nf(($mysales['totalQty'] ?? 0), 0); ?></div>
                        <div class="R"><?= ($user['f_planUsersSales'] ?? 0) - ($mysales['totalQty'] ?? 0) < 0 && $user['f_planUsersSales'] ? '<span style="color: green; font-weight: bold;">+' : '<span>'; ?><?= nf(abs(($user['f_planUsersSales'] ?? 0) - ($mysales['totalQty'] ?? 0)), 0); ?><?= '</span>'; ?></div>
                        <div class="R">
                            <?= ($user['f_planUsersSales'] ?? 0) - ($mysales['totalQty'] ?? 0) < 0 && $user['f_planUsersSales'] ? ('<span style="color: green; font-weight: bold;">План выполнен</span>') : ('<span>' . nf(abs(($user['f_planUsersSales'] ?? 0) - ($mysales['totalQty'] ?? 0)) / ((date("t") - date("j") + 1)), 0) . '</span>'); ?>

                        </div>
                    </div>
                    <?
                    $n++;
                }
                ?>
                <div style="display: contents;">
                    <div class="C B"></div>
                    <div class="C B">Итого:</div>
                    <div class="C B"><?= nf(($totalPlanSumm), 3); ?></div>
                    <div class="C B"><?= nf($totalAllSumm, 3); ?></div>
                    <div class="C B"><?= nf($totalPlanSumm - $totalAllSumm, 3); ?></div>
                    <div></div>

                </div>
            </div>

        </div>
    </div>
    <script>
        function save(action, user, year, month, value) {
            fetch('IO.php', {
                body: JSON.stringify({
                    action: action,
                    user: user,
                    year: year,
                    month: month,
                    value: parseFloat(value || 0)
                }),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            });
        }
    </script>




<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
