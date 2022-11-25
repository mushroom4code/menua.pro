<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
			$todaySales = query2array(mysqlQuery("SELECT * FROM "
					. " `f_sales`"
					. " LEFT JOIN `f_installments` ON (`f_installmentsSalesID` = `idf_sales`)"
					. " WHERE `f_salesDate` = '" . ($_JSON['sale']['date'] ?? date("Y-m-d")) . "';")); //CURDATE();
			foreach ($todaySales as &$todaySale2) {
				$todaySale2['payments'] = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` = '" . $todaySale2['idf_sales'] . "'"));
			}
			$total = 0;
			foreach ($todaySales as $todaySale) {
				if ($todaySale['f_installmentsSumm']) {
					$total += array_sum(array_column($todaySale['payments'], 'f_paymentsAmount'));
				} else {
					$total += $todaySale['f_salesSumm'];
				}
			}
printr($total);

printr($todaySales);





