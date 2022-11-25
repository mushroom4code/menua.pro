<?php
$pageTitle = '1';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (($_GET['sa'] ?? false) && $_GET['price'] ?? false) {
	mysqlQuery("UPDATE `servicesApplied` SET `servicesAppliedPrice` = '" . round($_GET['price'], 2) . "' WHERE `idservicesApplied` = '" . $_GET['sa'] . "'");
	header("Location: " . GR2(['sa' => null, 'price' => null]));
	die();
}


include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
$servicesApplied = query2array(mysqlQuery("SELECT * FROM servicesApplied"
				. " left join `services` ON (`idservices` = `servicesAppliedService`)"
				. " left join `clients` ON (`idclients` = `servicesAppliedClient`)"
				. " left join users on (idusers = servicesAppliedBy) where (select count(1) from f_subscriptions WHERE f_subscriptionsContract=servicesAppliedContract AND f_salesContentService=servicesAppliedService AND f_salesContentPrice=servicesAppliedPrice)=0 "
				. "AND not isnull(servicesAppliedContract)"
//				. " AND not isnull(servicesAppliedPrice)"
//				. " AND servicesAppliedPrice>20;"
		));
?>
<style>
	a:active {
		outline: 3px solid red;
	}
</style>
<div class="box neutral">
	<div class="box-body">
		<div style="display: grid; grid-template-columns: repeat(11,auto);" class="lightGrid">
			<div style="display: contents;">
				<div>#</div>
				<div>Дата</div>
				<div>id таблетки</div>
				<div>Абонемент</div>
				<div>Клиент</div>
				<div>Услуга</div>
				<div>Количество</div>
				<div>Цена</div>
				<div></div>
				<div>В абоне</div>
				<div>Оператор</div>
			</div>
			<?
			$n = 0;
			usort($servicesApplied, function ($a, $b) {
				return $a['servicesAppliedDate'] <=> $b['servicesAppliedDate'];
			});
			foreach ($servicesApplied as $serviceApplied) {
				if ($serviceApplied['servicesAppliedDeleted']) {
					continue;
				}
				$f_subscriptions = query2array(mysqlQuery("SELECT * FROM `f_subscriptions` WHERE"
								. " `f_subscriptionsContract`='" . $serviceApplied['servicesAppliedContract'] . "'"
								. "AND `f_salesContentService`='" . $serviceApplied['servicesAppliedService'] . "'"));
				?>
				<div style="display: contents;">
					<div><?= ++$n; ?></div>
					<div><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $serviceApplied['servicesAppliedClient']; ?>&date=<?= $serviceApplied['servicesAppliedDate']; ?>"><?= $serviceApplied['servicesAppliedDate']; ?></a></div>
					<div><?= $serviceApplied['idservicesApplied']; ?></div>
					<div><a target="_blank" href="/pages/checkout/payments.php?client=<?= $serviceApplied['servicesAppliedClient']; ?>&contract=<?= $serviceApplied['servicesAppliedContract']; ?>"><?= $serviceApplied['servicesAppliedContract']; ?></a></div>
					<div><a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $serviceApplied['servicesAppliedClient']; ?>"><?= $serviceApplied['servicesAppliedClient']; ?></a><br><?= $serviceApplied['clientsAKNum']; ?></div>
					<div><?= $serviceApplied['servicesName']; ?><br>
						<div style="color: <?
						switch (true) {
							case $serviceApplied['servicesAppliedFineshed'] && !$serviceApplied['servicesAppliedDeleted']: print 'red';
								break;
							case!$serviceApplied['servicesAppliedFineshed'] && $serviceApplied['servicesAppliedDeleted']: print 'green';
								break;
							case $serviceApplied['servicesAppliedFineshed'] && $serviceApplied['servicesAppliedDeleted']: print 'orange';
								break;
							default : print 'gray';
						}
						?>">
								 <?= $serviceApplied['servicesAppliedFineshed'] ? 'Завершена' : 'Не завершена'; ?>
								 <?= $serviceApplied['servicesAppliedDeleted'] ? 'Удалена' : 'Не удалена'; ?>
						</div>

					</div>
					<div><?= $serviceApplied['servicesAppliedQty']; ?></div>
					<div><?= $serviceApplied['servicesAppliedPrice'] ?? 'NULL'; ?></div>
					<div style="text-align: center;">
						<? if (($f_subscriptions[0]['f_salesContentPrice'] ?? null) !== null) {
							?><a href="?sa=<?= $serviceApplied['idservicesApplied']; ?>&price=<?= $f_subscriptions[0]['f_salesContentPrice']; ?>">&Lt;</a><br><? }
						?>
						<a href="">&Cross;</a>
					</div>
					<div>
						<? foreach ($f_subscriptions as $f_subscription) {
							?><div><?= $f_subscription['f_salesContentQty']; ?>: <?= $f_subscription['f_salesContentPrice']; ?></div><? }
						?>
						<? //printr($f_subscriptions);     ?>
					</div>
					<div><?= $serviceApplied['usersLastName']; ?></div>
				</div>
				<?
			}
			?>
		</div>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
