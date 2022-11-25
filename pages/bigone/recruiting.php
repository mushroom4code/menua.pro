<?php

if (date("Ym", strtotime($from)) === date("Ym", strtotime($to))) {

	$monthBegin = date("Y-m-01", strtotime($from));
	$monthEnd = date("Y-m-t", strtotime($from));

	$recruitingValues = query2array(mysqlQuery("SELECT * FROM `recruitingValues` WHERE `recruitingValuesDate`<='$to'"));

	usort($recruitingValues, function ($a, $b) {
		if ($b['recruitingValuesDate'] <=> $a['recruitingValuesDate']) {
			return $b['recruitingValuesDate'] <=> $a['recruitingValuesDate'];
		}
		if ($b['recruitingValuesQty'] <=> $a['recruitingValuesQty']) {
			return $b['recruitingValuesQty'] <=> $a['recruitingValuesQty'];
		}
	});

//	printr($recruitingValues);

	$recruitingResults = query2array(mysqlQuery("SELECT * FROM `recruiting` WHERE `recruitingDate`>='$monthBegin' AND `recruitingDate`<='$monthEnd'"));
	$recruitingResultsByUser = [];
	foreach ($recruitingResults as $recruitingResult) {
		$recruitingResultsByUser[$recruitingResult['recruitingUser']]['bydate'][$recruitingResult['recruitingDate']]['qty'] = $recruitingResult['recruitingQty'];
	}

	function getRecruitingWage($db, $date, $qty) {
		$filtered1 = array_filter($db, function ($row) use ($date) {
			return mystrtotime($date) >= mystrtotime($row['recruitingValuesDate']);
		});
		$maxDate = max(array_column($filtered1, 'recruitingValuesDate'));

		$filtered2 = array_filter($db, function ($row) use ($maxDate) {
			return mystrtotime($maxDate) == mystrtotime($row['recruitingValuesDate']);
		});
		usort($filtered2, function ($a, $b) {
			return $a['recruitingValuesQty'] <=> $b['recruitingValuesQty'];
		});
		$OUT = null;
		foreach ($filtered2 as $row) {
			if ($qty < $row['recruitingValuesQty']) {
				$OUT = $row['recruitingValuesWage'];
				break;
			}
		}
		return $OUT;
	}

	foreach ($recruitingResultsByUser as &$recruitingResultByUser2) {
		$recruitingResultByUser2['total'] = array_sum(array_column($recruitingResultByUser2['bydate'], 'qty'));

		foreach ($recruitingResultByUser2['bydate'] as $date => &$param) {
			$param['wage'] = getRecruitingWage($recruitingValues, $date, $recruitingResultByUser2['total']);
			$param['totalwage'] = $param['wage'] * $param['qty'];
		}
	}


//	printr($recruitingResultsByUser);

	foreach ($groups as &$group3_R) {
		foreach ($group3_R['users'] as &$user3_R) {
			for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
				$user3_R['wages'][mydates("Y-m-d", $time)]['R']['value'] = isset($recruitingResultsByUser[$user3_R['idusers']]['bydate'][mydates("Y-m-d", $time)]) ? $recruitingResultsByUser[$user3_R['idusers']]['bydate'][mydates("Y-m-d", $time)]['totalwage'] : 0;
				//($user3_R['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][6] ?? 0) / $ndaysMonth;
				$user3_R['wages'][mydates("Y-m-d", $time)]['R']['info'] = isset($recruitingResultsByUser[$user3_R['idusers']]['bydate'][mydates("Y-m-d", $time)]) ? $recruitingResultsByUser[$user3_R['idusers']]['bydate'][mydates("Y-m-d", $time)] : [];
//				[
//					'info' => ($recruitingResultsByUser[$user3_R['idusers']]['total'] ?? 0),
//					'todayQty' => (['bydate'][mydates("Y-m-d", $time)]['qty'] ?? 0),
//					'todayWage' => ($recruitingResultsByUser[$user3_R['idusers']]['bydate'][mydates("Y-m-d", $time)]['wage'] ?? 0),
//					'todayTotalWage' => ($recruitingResultsByUser[$user3_R['idusers']]['bydate'][mydates("Y-m-d", $time)]['wage'] ?? 0) * ($recruitingResultsByUser[$user3_R['idusers']]['bydate'][mydates("Y-m-d", $time)]['qty'] ?? 0),
////				'monthwage' => ($user3_R['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][6] ?? 0),
////				'ndaysMonth' => $ndaysMonth
//				];
			}//$time
		}//users
	}//groups
}



