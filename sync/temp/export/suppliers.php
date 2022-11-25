<?php

ini_set('memory_limit', '1G');
header("Content-type: text/plain; charset=utf8");
//header("Content-Type: ");
//header('Content-type: plain/text');
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$suppliers = query2array(mysqlQuery("SELECT * FROM `vita`.`suppliers` LEFT JOIN `vita`.`suppliersManagers` ON (`suppliersManagersSupplier` = `idsuppliers`)"));
//printr($suppliers);
foreach ($suppliers as $supplier) {
	print
			$supplier['idsuppliers'] . "\t"
			. htmlspecialchars_decode($supplier['suppliersName']) . "\t"
			. $supplier['suppliersINN'] . "\t"
			. "\t"
			. "\t"
			. $supplier['suppliersPhone'] . "\t"
			. $supplier['suppliersEmail'] . "\t"
			. $supplier['suppliersManagersName'] . "\t"
			. "\n";
}

/*
№	Наименование	ИНН	КПП	Адрес	Телефон	E-mail	Контактное лицо	Комментарий

*/