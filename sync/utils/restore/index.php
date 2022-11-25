<?php

$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
$saleToRestore = 39279;
$database = 'warehouse';
$backup = 'warehouseBackup';
//mysql -u root -p warehouseBackup < /var/www/db_backup/20220421infi.sql
//yflt;ysqgfhjkm
//INSERT INTO table2
//SELECT * FROM table1
//WHERE condition; 
//
//
//print mysqlQuery("INSERT INTO `$database`.`f_sales` SELECT * FROM `$backup`.`f_sales` where `idf_sales`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`f_credits` SELECT * FROM `$backup`.`f_credits` where `f_creditsSalesID`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`f_credits` SELECT * FROM `$backup`.`f_credits` where `f_creditsSalesID`=$saleToRestore;");
foreach (query2array(mysqlQuery("SELECT * FROM `$backup`.`f_credits` where `f_creditsSalesID`=$saleToRestore;")) as $f_credit) {
//	print mysqlQuery("INSERT INTO `$database`.`f_creditsTransactions` SELECT * FROM `$backup`.`f_creditsTransactions` where `f_creditsTransactionsCredit`=" . $f_credit['idf_credits'] . ";");
}
//print mysqlQuery("INSERT INTO `$database`.`f_installments` SELECT * FROM `$backup`.`f_installments` where `f_installmentsSalesID`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`f_payments` SELECT * FROM `$backup`.`f_payments` where `f_paymentsSalesID`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`f_salesRoles` SELECT * FROM `$backup`.`f_salesRoles` where `f_salesRolesSale`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`f_salesToCoord` SELECT * FROM `$backup`.`f_salesToCoord` where `f_salesToCoordSalesID`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`f_salesToPersonal` SELECT * FROM `$backup`.`f_salesToPersonal` where `f_salesToPersonalSalesID`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`f_subscriptions` SELECT * FROM `$backup`.`f_subscriptions` where `f_subscriptionsContract`=$saleToRestore;");
//print mysqlQuery("INSERT INTO `$database`.`servicesApplied` SELECT * FROM `$backup`.`servicesApplied` where `servicesAppliedContract`=$saleToRestore;");
foreach (query2array(mysqlQuery("SELECT * FROM `$backup`.`servicesApplied` where `servicesAppliedContract`=$saleToRestore;")) as $serviceApplied) {
//	print mysqlQuery("INSERT INTO `$database`.`servicesAppliedComments` SELECT * FROM `$backup`.`servicesAppliedComments` where `servicesAppliedCommentsSA`=" . $serviceApplied['idservicesApplied'] . ";");
}
?>


<?

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
