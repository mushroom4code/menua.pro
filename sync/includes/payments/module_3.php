<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//3] Кредитный. Премия за оформление абонемента, р.
if (($paymentsValues['3']['userPaymentsValuesValue'] ?? false)) {
	$payments['types']['3']['title'] = 'Кредитный. Премия за оформление абонемента';
	$payments['types']['3']['titleShort'] = 'Оформлений<br>договоров';

	$filtered = array_filter(($payments['dates'][$date]['3']['data'] ?? []), function ($sale) {
//		return in_array($sale['f_salesType'], [1, 2]);
		return in_array($sale['f_salesType'], [1, 2]) && !$sale['f_salesIsSmall'];
	});

	$payments['dates'][$date]['3']['reward'] = $paymentsValues['3']['userPaymentsValuesValue'] * count($filtered);
}