<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день

if (($paymentsValues['1']['userPaymentsValuesValue'] ?? false) && ($userShifts[$date]['scheduleSize'] ?? false) === 1) {
	$payments['types']['1']['title'] = 'Оклад за полную смену';
	$payments['types']['1']['titleShort'] = 'Смена<br>полная';
	$payments['dates'][$date]['1']['data'] = $userShifts[$date] ?? null;
	if (($paymentsValues['10']['userPaymentsValuesValue'] ?? false)) {//указана продолжительность полной смены
//fingerDuration
//				printr(($paymentsValues['10']['userPaymentsValuesValue'] ?? false));
		$min = min(
				$paymentsValues['10']['userPaymentsValuesValue'] * 60 * 60,
				($payments['dates'][$date]['1']['data']['fingerDuration'] ?? 0));
		$k = ($payments['dates'][$date]['1']['data']['fingerDuration'] ?? false) ? min(1,
						$min / ($paymentsValues['10']['userPaymentsValuesValue'] * 60 * 60)
				) : 0;

		$payments['dates'][$date]['1']['comment'] = 'Учитывается пункт 10] (' . $paymentsValues['10']['userPaymentsValuesValue'] . 'ч, ' . $k . ' ( ' . ($min / ($paymentsValues['10']['userPaymentsValuesValue'] * 60 * 60)) . ')';
	} else {
		$k = ($payments['dates'][$date]['1']['data']['WHpercentLimit_1'] ?? 0);
	}
	$payments['dates'][$date]['1']['reward'] = $paymentsValues['1']['userPaymentsValuesValue'] * $k;
}