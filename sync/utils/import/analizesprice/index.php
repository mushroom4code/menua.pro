<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';


$file = file('price1.csv');

$import = [];



//
//TPS_ServicesCatalog,
//TPS_ServicesName, TPS_ServicesPrice, TPS_ServicesCost, TPS_ServicesSupplier, TPS_ServicesAdded, TPS_ServicesDeleted
?>
<div class="box neutral">
	<div class="box-body">
		<?
//		import new 
//		foreach ($file as $row) {
//			$columns = explode(';', $row);
//			foreach ($columns as &$column2) {
//				$column2 = iconv("cp1251", "utf-8", $column2);
//			}
//
//			if (
//					($columns[1] ?? false) &&
//					($columns[2] ?? false) &&
//					($columns[7] ?? false)
//			) {
//				if (!($price = preg_replace("/[^0-9.]/", "", $columns[7]))) {
//					$price = '0';
//				}
//				$import[] = '(' . implode(',', [1, 3, "'" . trim($columns[1]) . "'", "'" . trim($columns[2]) . "'", "'" . $price . "'"]) . ')';
//				if (count($import) >= 100) {
//					$result = mysqlQuery("INSERT INTO `TPS_Services` (`TPS_ServicesSupplier`,`TPS_ServicesCatalog`,`TPS_ServicesCode`,`TPS_ServicesName`,`TPS_ServicesCost`) VALUES " . implode(',', $import) . ";");
//					var_dump($result);
//					printr(mysqli_error($link));
//					print '<br>';
//					$import = [];
//				}
//			}
//		}
//		update prices
//
		foreach ($file as $row) {
			$columns = explode(';', $row);
			foreach ($columns as &$column2) {
				$column2 = iconv("cp1251", "utf-8", $column2);
			}
//			printr($columns);
			if (
					($columns[0] ?? false) &&
					($columns[1] ?? false) &&
					($columns[6] ?? false) && trim($columns[6])
			) {
				if (!($price = preg_replace("/[^0-9.]/", "", $columns[6]))) {
					$price = '0';
				}
				$import[] = "((SELECT `idTPS_Services` FROM `TPS_Services` where `TPS_ServicesCode` = '" . trim($columns[0]) . "' LIMIT 1),'" . $price . "')";
				//. implode(',', ["" . trim($columns[1]) . "'", "'" . trim($columns[2]) . "'", "'" . $price . "'"]) . ')';
				if (count($import) >= 100) {
					//INSERT INTO TPS_costs (TPS_costsService, TPS_costsValue) VALUES (,999);
					$result = mysqlQuery("INSERT INTO `TPS_costs` (`TPS_costsService`,`TPS_costsValue`) VALUES " . implode(',', $import) . ";");
					var_dump($result);
					printr(mysqli_error($link));
					print '<br>';
					$import = [];
				}
			}
		}
//		
//		
//		$file = file('price2.csv');
//
//		foreach ($file as $row) {
//			$columns = explode(';', $row);
//			foreach ($columns as &$column2) {
//				$column2 = iconv("cp1251", "utf-8", $column2);
//			}
//
//			if (
//					($columns[0] ?? false) && $columns[0] &&
//					($columns[9] ?? false) && trim($columns[9])
//			) {
//				if (!($price = preg_replace("/[^0-9.]/", "", $columns[9]))) {
//					$price = '0';
//				}
////				print "'$columns[0]' '$columns[9]'<br>";
//				$import[] = "((SELECT `idTPS_Services` FROM `TPS_Services` where `TPS_ServicesCode` = '" . $columns[0] . "' LIMIT 1),'" . $price . "')";
//				//. implode(',', ["" . trim($columns[1]) . "'", "'" . trim($columns[2]) . "'", "'" . $price . "'"]) . ')';
//				if (count($import) >= 100) {
//					//INSERT INTO TPS_costs (TPS_costsService, TPS_costsValue) VALUES (,999);
//					$result = mysqlQuery("INSERT INTO `TPS_prices` (`TPS_pricesService`,`TPS_pricesValue`) VALUES " . implode(',', $import) . ";");
//					var_dump($result);
//					printr(mysqli_error($link));
//					print '<br>';
//					$import = [];
//				}
//			}
//		}
		?>	
	</div>
</div> 



<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
