<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$visits = query2array(mysqlQuery("SELECT * FROM `clientsVisits`  WHERE `clientsVisitsClient` ='" . mres($_GET['client'] ?? '') . "'  ORDER BY `clientsVisitsDate` DESC")); //AND `clientsVisitsDate`>=DATE_SUB(CURDATE(), INTERVAL 4 MONTH)
?><?
foreach ($visits as $visit) {
	?>
	<div style=" padding: 5px 0px; border: 1px dashed gray;<?= (time() - strtotime($visit['clientsVisitsDate'])) < 3 * 30.5 * 24 * 60 * 60 ? ' background-color: pink;' : ''; ?>"><?= $visit['clientsVisitsDate']; ?></div><?
}
?>