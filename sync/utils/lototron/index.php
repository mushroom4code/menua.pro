<script src="/sync/js/basicFunctions.js" type="text/javascript"></script>
<input type="date" onchange="GR({date: this.value});">

<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$users = query2array(mysqlQuery("SELECT usersLastName,usersFirstName FROM f_salesToPersonal left join f_sales on (idf_sales = f_salesToPersonalSalesID) left join users on (idusers= f_salesToPersonalUser) WHERE f_salesDate = '" . ($_GET['date'] ?? date("Y-m-d")) . "' AND `f_salesSumm`>=25000 AND isnull(`f_salesCancellationDate`) ORDER BY `usersLastName`"));
?><div style="display: grid; grid-template-columns: repeat( auto-fit, minmax(250px, 300px) );"><?
foreach ($users as $user) {
	?><div style="text-align: center; font-family: Arial; font-size: 18pt; padding: 30px 0px; border: 1px dashed gray;"><?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?> </div><?
	}
	?>
</div>