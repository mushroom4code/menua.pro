<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(150)) {

}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(150)) {
	?>E403R100<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/menu.php';
	$start = microtime(1);
	?>

	<div class="box neutral">
		<div class="box-body">
			<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/calls/callsmenu.php'; ?>
			Есть кэш абонементы с остатками<br>
			<?
			$f_sales = query2array(mysqlQuery("SELECT *,("
							. "(SELECT SUM(`f_salesContentQty`) FROM `f_subscriptions` WHERE `f_subscriptionsContract`=`idf_sales`) "
							. "-"
							. "(SELECT SUM(`servicesAppliedQty`) FROM `servicesApplied` WHERE `servicesAppliedContract`=`idf_sales` AND isnull(`servicesAppliedDeleted`) AND NOT isnull(`servicesAppliedFineshed`))"
							. ") as `remains` "
							. " FROM `f_sales`"
							. "LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
							. " "
							. " WHERE (SELECT COUNT(1) FROM `f_payments` WHERE `f_paymentsSalesID`=`idf_sales`)>0"
							. " AND ("
							. "(SELECT SUM(`f_salesContentQty`) FROM `f_subscriptions` WHERE `f_subscriptionsContract`=`idf_sales`) "
							. "-"
							. "(SELECT SUM(`servicesAppliedQty`) FROM `servicesApplied` WHERE `servicesAppliedContract`=`idf_sales` AND isnull(`servicesAppliedDeleted`))"
							. ")>0"
							. " AND `f_salesType` IN (1,2)"
							. " AND isnull(`f_salesCancellationDate`)"));
print "SELECT *,("
							. "(SELECT SUM(`f_salesContentQty`) FROM `f_subscriptions` WHERE `f_subscriptionsContract`=`idf_sales`) "
							. "-"
							. "(SELECT SUM(`servicesAppliedQty`) FROM `servicesApplied` WHERE `servicesAppliedContract`=`idf_sales` AND isnull(`servicesAppliedDeleted`) AND NOT isnull(`servicesAppliedFineshed`))"
							. ") as `remains` "
							. " FROM `f_sales`"
							. "LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
							. " "
							. " WHERE (SELECT COUNT(1) FROM `f_payments` WHERE `f_paymentsSalesID`=`idf_sales`)>0"
							. " AND ("
							. "(SELECT SUM(`f_salesContentQty`) FROM `f_subscriptions` WHERE `f_subscriptionsContract`=`idf_sales`) "
							. "-"
							. "(SELECT SUM(`servicesAppliedQty`) FROM `servicesApplied` WHERE `servicesAppliedContract`=`idf_sales` AND isnull(`servicesAppliedDeleted`))"
							. ")>0"
							. " AND `f_salesType` IN (1,2)"
							. " AND isnull(`f_salesCancellationDate`)";
//			print count($f_sales);
			?>
			<div style="display: inline-block;">
				<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto;">
					<div style="display: contents;">
						<div>#</div>
						<div class="C B">Клиент</div>
						<div class="C B">ост</div>
					</div>

					<?
					$clients = [];

					foreach ($f_sales as $f_sale) {
						$clients[$f_sale['idclients']]['info'] = array_intersect_key($f_sale, array_flip([
							'idclients',
							'clientsLName',
							'clientsFName',
							'clientsMName',
						]));
						$clients[$f_sale['idclients']]['sales'][] = $f_sale;
					}

					$n = 0;
					foreach (($clients ?? []) as $client) {
						$n++;
						?>
						<div  style="display: contents;">
							<div>
								<?= $n; ?>
							</div>
							<div>
								<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['info']['idclients']; ?>">
									<?= $client['info']['clientsLName']; ?>
									<?= $client['info']['clientsFName']; ?>
									<?= $client['info']['clientsMName']; ?>
								</a>
							</div>
							<div class="C"><?= array_sum(array_column($client['sales'], 'remains')); ?></div>
						</div>
						<?
					}
					?>
				</div>
			</div>

		</div>
	</div>



	<br>
	<br>
	<br>

	<br>
	<?
	print microtime(1) - $start;
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
