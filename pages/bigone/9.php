<?php

foreach ($groups as &$group3_9) {
	foreach ($group3_9['users'] as &$user3_9) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
			$user3_9['wages'][mydates("Y-m-d", $time)][9]['value'] = ($user3_9['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][9] ?? 0) *
					($user3_9['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] ?? 0) *
					($user3_9['userSchedule'][mydates("Y-m-d", $time)]['duration'] ?? 0);
			$user3_9['wages'][mydates("Y-m-d", $time)][9]['info'] = [
				'fingerlogPerc' => ($user3_9['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] ?? 0),
				'userSchedule' => ($user3_9['userSchedule'][mydates("Y-m-d", $time)]['duration'] ?? 0),
				'wageperhour' => ($user3_9['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][9] ?? 0),
			];
		}//$time
	}//users
}//groups