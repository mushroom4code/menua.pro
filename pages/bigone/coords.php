<?php

if (date("Ym", strtotime($from)) === date("Ym", strtotime($to))) {


	$LT = query2array(mysqlQuery("SELECT * "
					. " FROM `LT` "
					. " WHERE"
					. " `LTtype` = '1'"
	));

	usort($LT, function ($a, $b) {
		if ($a['LTdate'] <=> $b['LTdate']) {
			return $b['LTdate'] <=> $a['LTdate'];
		}

		if ($a['LTvalue'] <=> $b['LTvalue']) {
			return floatval($a['LTvalue']) <=> floatval($b['LTvalue']);
		}
	});

	$coordinatorsSales = array_values(array_filter(query2array(mysqlQuery(""
									. " SELECT * "
									. " FROM `f_sales` "
									. " LEFT JOIN `f_salesToCoord` ON (`f_salesToCoordSalesID`=`idf_sales`)"
									. " WHERE"
									. " (isnull(`f_salesCancellationDate`) OR `f_salesCancellationDate`>'$monthEnd')"
									. " AND `f_salesDate`>='$monthBegin' AND `f_salesDate`<='$monthEnd'"
									. " AND (ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales`),0)+"
									. "  ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0))>=`f_salesSumm`"
									. "")), function ($f_sale) use ($AEs) {
						return getAEs($AEs, $f_sale['f_salesSumm'], $f_sale['f_salesDate']) > 0;
					}));
	$coordinatorsCanceledSales = array_values(array_filter(query2array(mysqlQuery(""
									. " SELECT * "
									. " FROM `f_sales` "
									. " LEFT JOIN `f_salesToCoord` ON (`f_salesToCoordSalesID`=`idf_sales`)"
									. " WHERE"
									. " NOT isnull(`f_salesCancellationDate`)"
									. " AND `f_salesCancellationDate`>='$monthBegin' AND `f_salesCancellationDate`<='$monthEnd'"
									. " AND `f_salesDate`<'$monthBegin'"
									. " AND (ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` WHERE `f_paymentsSalesID` = `idf_sales`),0)+"
									. "  ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` WHERE `f_creditsSalesID` = `idf_sales`),0))>=`f_salesSumm`"
									. "")), function ($f_sale) use ($AEs) {
						return getAEs($AEs, $f_sale['f_salesSumm'], $f_sale['f_salesDate']) > 0;
					}));
	$coordinators = [];

	foreach ($groups as &$group3_C1) {
		if ($group3_C1['idusersGroups'] != 10) {
			continue;
		}
		foreach ($group3_C1['users'] as &$user3_C1) {
			$countAllSales = ($user3_C1['usersPaymentsValuesByDate'][$monthBegin][27] ?? 0);
			foreach ($coordinatorsSales as $coordinatorsSale) {
				if ($coordinatorsSale['f_salesToCoordCoord']) {
					if ($countAllSales) {
						$user3_C1['wages'][$coordinatorsSale['f_salesDate']]['C']['info']['sales'][$coordinatorsSale['idf_sales']] = $coordinatorsSale;
					} else {
						if ($coordinatorsSale['f_salesToCoordCoord'] == $user3_C1['idusers']) {
							$user3_C1['wages'][$coordinatorsSale['f_salesDate']]['C']['info']['sales'][$coordinatorsSale['idf_sales']] = $coordinatorsSale;
						}
					}
				}
//				printr($user3_C1['wages'][$coordinatorsSale['f_salesDate']]['C']['info']['salesSumm']) ;
			}//$coordinatorsSales 10-18

			foreach ($coordinatorsCanceledSales as $coordinatorsSale) {
				if ($coordinatorsSale['f_salesToCoordCoord']) {
					if ($countAllSales) {
						$user3_C1['wages'][$coordinatorsSale['f_salesCancellationDate']]['C']['info']['canceledSales'][$coordinatorsSale['idf_sales']] = $coordinatorsSale;
					} else {
						if ($coordinatorsSale['f_salesToCoordCoord'] == $user3_C1['idusers']) {
							$user3_C1['wages'][$coordinatorsSale['f_salesCancellationDate']]['C']['info']['canceledSales'][$coordinatorsSale['idf_sales']] = $coordinatorsSale;
						}
					}
				}
			}//$coordinatorsSales 10-18

			foreach ($user3_C1['wages'] as $wages) {
				if ($wages['C']['info']['sales'] ?? false) {
					$user3_C1['coordinatorsSalesSumm'] = ($user3_C1['coordinatorsSalesSumm'] ?? 0) + array_sum(array_column($wages['C']['info']['sales'], 'f_salesSumm'));
				}
				if ($wages['C']['info']['canceledSales'] ?? false) {
					$user3_C1['coordinatorsSalesSumm'] = ($user3_C1['coordinatorsSalesSumm'] ?? 0) - array_sum(array_column($wages['C']['info']['canceledSales'], 'f_salesSumm'));
				}
			}
//			 = array_column(array_column(array_column($user3_C1['wages'], 'C'), 'info'), 'sales');
		}
	}


//		printr($userCoord);
//	foreach ($coordinatorsSales as $coordinatorsSale) {
//		if (!$coordinatorsSale['f_salesCancellationDate'] && $coordinatorsSale['f_salesToCoordCoord']) {
//			$coordinators[$coordinatorsSale['f_salesToCoordCoord']]['sales'][$coordinatorsSale['idf_sales']] = $coordinatorsSale;
//		}
//	}
//	$allSalesSumm = array_sum(array_column($coordinatorsSales, 'f_salesSumm')) - array_sum(array_column($coordinatorCanceledSales, 'f_salesSumm'));
//	printr($recruitingResultsByUser);

	foreach ($groups as &$group3_C) {
		foreach ($group3_C['users'] as &$user3_C) {

			$ltreward = LT($LT, $user3_C['idusers'], ($user3_C['coordinatorsSalesSumm'] ?? 0), mydates("Y-m-d", $time));
			for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
				$user3_C['wages'][mydates("Y-m-d", $time)]['C']['info']['reward'] = $ltreward;

				$user3_C['wages'][mydates("Y-m-d", $time)]['C']['value'] = $ltreward * (array_sum(array_column(($user3_C['wages'][mydates("Y-m-d", $time)]['C']['info']['sales'] ?? []), 'f_salesSumm')) - array_sum(array_column(($user3_C['wages'][mydates("Y-m-d", $time)]['C']['info']['canceledSales'] ?? []), 'f_salesSumm'))) / 100;
				//($user3_C['wages'][mydates("Y-m-d", $time)]['C']['info']['salesSumm'] ?? 0) * ($ltreward ?? 0) / 100
//isset($recruitingResultsByUser[$user3_C['idusers']]['bydate'][mydates("Y-m-d", $time)]) ? $recruitingResultsByUser[$user3_C['idusers']]['bydate'][mydates("Y-m-d", $time)]['totalwage'] : 0;
//($user3_C['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][6] ?? 0) / $ndaysMonth;
//				$user3_C['wages'][mydates("Y-m-d", $time)]['C']['info'] = []; //
//isset($recruitingResultsByUser[$user3_C['idusers']]['bydate'][mydates("Y-m-d", $time)]) ? $recruitingResultsByUser[$user3_C['idusers']]['bydate'][mydates("Y-m-d", $time)] : [];
//				[
//					'info' => ($recruitingResultsByUser[$user3_C['idusers']]['total'] ?? 0),
//					'todayQty' => (['bydate'][mydates("Y-m-d", $time)]['qty'] ?? 0),
//					'todayWage' => ($recruitingResultsByUser[$user3_C['idusers']]['bydate'][mydates("Y-m-d", $time)]['wage'] ?? 0),
//					'todayTotalWage' => ($recruitingResultsByUser[$user3_C['idusers']]['bydate'][mydates("Y-m-d", $time)]['wage'] ?? 0) * ($recruitingResultsByUser[$user3_C['idusers']]['bydate'][mydates("Y-m-d", $time)]['qty'] ?? 0),
////				'monthwage' => ($user3_C['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][6] ?? 0),
////				'ndaysMonth' => $ndaysMonth
//				];
			}//$time
		}//users
	}//groups
}



