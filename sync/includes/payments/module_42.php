<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день 
if (($paymentsValues['42']['userPaymentsValuesValue'] ?? false)) {
	$payments['types']['42']['title'] = 'Премия за первичный визит пациента, записанного этим оператором';
	$payments['types']['42']['titleShort'] = 'Приходы';
//	$payments['dates'][$date]['42']['data'] = [];

	if($paymentsValues['42']['userPaymentsValuesValue'] * count($payments['dates'][$date]['42']['data'] ?? [])){		$payments['dates'][$date]['42']['reward'] = $paymentsValues['42']['userPaymentsValuesValue'] * count($payments['dates'][$date]['42']['data'] ?? []);
	}
}
