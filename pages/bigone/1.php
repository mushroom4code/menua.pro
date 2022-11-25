<?php

foreach ($groups as &$group3_1) {
	foreach ($group3_1['users'] as &$user3_1) {
		for ($time = mystrtotime($from); $time <= mystrtotime($to); $time += 60 * 60 * 24) {



			//Так тут надо на счёт дежурных смен.
			//[16]	Учитывать дежурные смены
			if ($user3_1['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][16]) {//Если мы учитываем дежурные смены то
				if ($user3_1['userSchedule'][mydates("Y-m-d", $time)]['isduty'] ?? false) {//если смена дежурная
					$user3_1['wages'][mydates("Y-m-d", $time)][1]['value'] = $user3_1['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][1] * ($user3_1['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] ?? 0); //то оплачиваем её
				}//если смена не дежурная, то не оплачиваем
			} else {//Если дежурные смены не учитываются - считаем умножением оплаты на отработанное время.
				$user3_1['wages'][mydates("Y-m-d", $time)][1]['value'] = $user3_1['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][1] * ($user3_1['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] ?? 0);
			}
			$user3_1['wages'][mydates("Y-m-d", $time)][1]['info'] = [
				'isduty' => ($user3_1['userSchedule'][mydates("Y-m-d", $time)]['isduty'] ?? false) == 1 ? '1' : '0',
				'isdutycounts' => $user3_1['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][16],
				'paymentvalue' => $user3_1['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][1],
				'timePCT' => ($user3_1['fingerLogTime'][mydates("Y-m-d", $time)]['perc'] ?? 0),
				'paymentShiftDuration' => ($user3_1['usersPaymentsValuesByDate'][mydates("Y-m-d", $time)][10] ?? 0),
				'scheduleDuration' => ($user3_1['userSchedule'][mydates("Y-m-d", $time)]['duration'] ?? 0),
				'fingerDuration' => ($user3_1['fingerLogTime'][mydates("Y-m-d", $time)]['duration'] ?? 0),
			];
//	----1	Оклад за смену, р.
			//Расчёты
		}//$time
	}
}