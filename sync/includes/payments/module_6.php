<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//printr(($paymentsValues['6']['userPaymentsValuesValue'] ?? false));
if (($paymentsValues['6']['userPaymentsValuesValue'] ?? false) && ($userShifts[$date]['fingerDuration'] ?? false)) {//Оклад за месяц 5/2, р.
	$payments['types']['6']['title'] = 'Оклад за месяц';
	$payments['types']['6']['titleShort'] = 'Оклад<br>месяц';
	$payments['dates'][$date]['6']['data'] = $userShifts[$date] ?? null;

	if (($paymentsValues['10']['userPaymentsValuesValue'] ?? false)) {//указана продолжительность полной смены
		$payments['dates'][$date]['6']['data']['durationBy10'] = $paymentsValues['10']['userPaymentsValuesValue'] * 60 * 60;
		$payments['dates'][$date]['6']['data']['durationBy10scheduleSize'] = ($paymentsValues['10']['userPaymentsValuesValue'] * 60 * 60) *
				$payments['dates'][$date]['6']['data']['scheduleSize'];
	} else {
		
	}
//	printr(($payments['dates'][$date]['6']['durationBy10'] ?? $payments['dates'][$date]['6']['scheduleDuration'] ?? false));
	$schedulePlan = ($payments['dates'][$date]['6']['data']['durationBy10scheduleSize'] ?? $payments['dates'][$date]['6']['data']['scheduleDuration'] ?? 0);
	if ($schedulePlan) {
		$payments['dates'][$date]['6']['data']['k'] = $payments['dates'][$date]['6']['data']['scheduleSize'] * min($schedulePlan, $payments['dates'][$date]['6']['data']['fingerDuration']) / $schedulePlan;
	}


	$payments['dates'][$date]['6']['weekdays'] = getWeekdays(date("m", strtotime($date)), date("Y", strtotime($date)));
	$payments['dates'][$date]['6']['reward'] = ($payments['dates'][$date]['6']['data']['k'] ?? 0) * ($paymentsValues['6']['userPaymentsValuesValue'] ?? 0) / $payments['dates'][$date]['6']['weekdays'];
}


