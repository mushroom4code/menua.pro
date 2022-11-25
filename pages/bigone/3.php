<?php
foreach ($groups as &$group3_3) {
	foreach ($group3_3['users'] as &$user3_3) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
			$mysales = array_filter($f_salesPeriod, function ($sale) use ($user3_3, $time) {
				return $sale['AE'] > 0 && $sale['f_salesCreditManager'] == $user3_3['idusers'] && $sale['f_salesDate'] == mydates("Y-m-d", $time);
			});
			$user3_3['wages'][mydates("Y-m-d", $time)][3]['value'] = (count($mysales)) * ($user3_3['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][3] ?? 0);
			$user3_3['wages'][mydates("Y-m-d", $time)][3]['info'] = [
				'wagepersale' => ($user3_3['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][3] ?? 0),
				'mysales' => $mysales
			];
		}//$time
	}//users
}//groups