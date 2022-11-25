<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день 
if (($paymentsValues['41']['userPaymentsValuesValue'] ?? false) && ($userShifts[$date]['scheduleSize'] ?? false) === 0.5) {
	$payments['types']['41']['title'] = 'Оклад за &half; смены';
	$payments['types']['41']['titleShort'] = 'Смена<br>&half;';
	$payments['dates'][$date]['41']['data'] = $userShifts[$date] ?? null;
	$k = ($payments['dates'][$date]['41']['data']['WHpercentLimit_1'] ?? 0);
	$payments['dates'][$date]['41']['reward'] = $paymentsValues['41']['userPaymentsValuesValue'] * $k;
}
