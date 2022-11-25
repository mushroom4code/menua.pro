<?php

foreach ($groups as &$group3_T) {
	foreach ($group3_T['users'] as &$user3_T) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {
			foreach (($user3_T['wages'][mydates('Y-m-d', $time)] ?? []) as &$wage4) {
//				printr($wage4);
				$user3_T['wagesTotal'][mydates('Y-m-d', $time)] = ($user3_T['wagesTotal'][mydates('Y-m-d', $time)] ?? 0) + ($wage4['value'] ?? 0);
			}
			$group3_T['wagesTotal'][mydates('Y-m-d', $time)] = ($group3_T['wagesTotal'][mydates('Y-m-d', $time)] ?? 0) + $user3_T['wagesTotal'][mydates('Y-m-d', $time)];
		}//$time
	}//users
}//groups




