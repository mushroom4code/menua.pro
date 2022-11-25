<?php
$pageTitle = 'Статичтически';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$userid = $_GET['user'] ?? $_USER['id'];

if (mfa(mysqlQuery("SELECT * FROM `f_planUsers` WHERE "
						. " `f_planUsersYear` = '" . date("Y") . "' "
						. " AND `f_planUsersMonth` = '" . date("n") . "' "
						. " AND `f_planUsersUser` = " . mres($userid) . " "
						. ""))) {

	$user = mfa(mysqlQuery("SELECT *,"
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
						
FROM `f_salesRoles` LEFT JOIN `f_sales` ON (`idf_sales`=`f_salesRolesSale`) WHERE `f_salesRolesUser`=`idusers` AND `f_salesDate` >= DATE_FORMAT(NOW(), '%Y-%m-01') AND `f_salesDate` <= DATE_FORMAT(LAST_DAY(NOW()), '%Y-%m-%d') AND `f_salesType` IN (1,2)) as `participants`
) as `thismonthsumm`"
					. " FROM `users` WHERE `idusers` = " . ($_USER['id'] == 176 && ($_GET['user'] ?? false) ? $_GET['user'] : $_USER['id']) . " "));
//	printr($user);
	$f_planUsers = mfa(mysqlQuery("SELECT * FROM `f_planUsers` WHERE "
					. " `f_planUsersYear` = '" . date("Y") . "' "
					. " AND `f_planUsersMonth` = '" . date("n") . "' "
					. " AND `f_planUsersUser` = " . $user['idusers'] . " "
					. ""));
	?>
	<div class="box neutral">
		<div class="box-body" style="text-align: center; padding: 2em;">
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(2,auto);">
				<div style="display: contents;">
					<div>План на месяц</div>
					<div><?= ($f_planUsers['f_planUsersSales'] ?? 0); ?></div>
				</div>
				<div style="display: contents;">
					<div>Продаж за месяц</div>
					<div><?= nf($user['thismonthsumm'] ?? 0); ?></div>
				</div>
				<div style="display: contents;">
					<div><?= ($f_planUsers['f_planUsersSales'] ?? 0) - ($user['thismonthsumm'] ?? 0) < 0 ? 'Перевыполнено' : 'Осталось'; ?></div>
					<div><?= nf(abs(($f_planUsers['f_planUsersSales'] ?? 0) - ($user['thismonthsumm'] ?? 0))); ?></div>
				</div>
				<div style="display: contents;">
					<div><?= ($f_planUsers['f_planUsersSales'] ?? 0) - ($user['thismonthsumm'] ?? 0) < 0 ? 'План выполнен' : 'Ежедневная цель'; ?></div>
					<div>
						<?
						if (($f_planUsers['f_planUsersSales'] ?? 0) - ($user['thismonthsumm'] ?? 0) > 0) {
							?>
							<?= ((date("t") - date("j") + 1) > 0 ? nf((($f_planUsers['f_planUsersSales'] ?? 0) - ($user['thismonthsumm'] ?? 0)) / (date("t") - date("j") + 1)) : '') ?>
							<?
						}
						?>
					</div>
				</div>

			</div>

		</div>
	</div>
<? } else {
	?>???<?
}
?>


<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
