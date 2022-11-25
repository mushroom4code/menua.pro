<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
global $_USER;
if (1) {//$_USER['id'] == 176
	if (($paymentsValues['47']['userPaymentsValuesValue'] ?? false) && ($payments['dates'][$date]['1']['data']['fingerDuration'] ?? false)) {
		$payments['types']['47']['title'] = 'Сверхурочные';
		$payments['types']['47']['titleShort'] = 'Сверх-<br>урочные';
		$payments['dates'][$date]['47']['data'] = $userShifts[$date] ?? null;

		$overtimeThreshold = $payments['dates'][$date]['1']['data']['scheduleDuration'];
		if (($paymentsValues['10']['userPaymentsValuesValue'] ?? false)) {
			$overtimeThreshold = $paymentsValues['10']['userPaymentsValuesValue'] * 60 * 60;
		}//указана продолжительность полной смены

		$overtimeTime = ($payments['dates'][$date]['1']['data']['fingerDuration'] ?? 0) - $overtimeThreshold;
		$payments['dates'][$date]['47']['data']['overtimeTimeSeconds'] = $overtimeTime;
//				printr(($paymentsValues['10']['userPaymentsValuesValue'] ?? false));

		if ($overtimeTime > 0) {
			$payments['dates'][$date]['47']['reward'] = ($overtimeTime / 3600) * ($paymentsValues['47']['userPaymentsValuesValue']);
		}
	}
}
