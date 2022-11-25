<?php

foreach ($groups as &$group3_6) {
	foreach ($group3_6['users'] as &$user3_6) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
			$user3_6['wages'][mydates("Y-m-d", $time)][6]['value'] = ($user3_6['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][6] ?? 0) / $ndaysMonth;
			$user3_6['wages'][mydates("Y-m-d", $time)][6]['info'] = [
				'monthwage' => ($user3_6['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][6] ?? 0),
				'ndaysMonth' => $ndaysMonth
			];
		}//$time
	}//users
}//groups