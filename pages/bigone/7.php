<?php

foreach ($groups as &$group3_7) {
	foreach ($group3_7['users'] as &$user3_7) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
			$user3_7['wages'][mydates("Y-m-d", $time)][7]['value'] = 0;
			$user3_7['wages'][mydates("Y-m-d", $time)][7]['info'] = [
				'value' => ($user3_7['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][7] ?? 0) / $ndaysMonth,
				'monthwageoficial' => ($user3_7['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][7] ?? 0),
				'ndaysMonth' => $ndaysMonth
			];
		}//$time
	}//users
}//groups