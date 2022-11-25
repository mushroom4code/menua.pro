<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(27)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(27)) {
	?>E403R27<?
} else {
	$sa = query2array(mysqlQuery("SELECT * FROM `servicesApplied` "
					. "LEFT JOIN `f_subscriptions` ON (`idf_subscriptions`=`servicesAppliedSubscription`) where not isnull(`servicesAppliedSubscription`) AND isnull(`servicesAppliedContract`);"));
	printr($sa);
	foreach ($sa as $s) {
		mysqlQuery("UPDATE `servicesApplied` SET `servicesAppliedContract` = '" . $s['f_subscriptionsContract'] . "' WHERE `idservicesApplied` = '" . $s['idservicesApplied'] . "'");
	}
	?>


<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
