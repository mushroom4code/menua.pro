<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день

if (($paymentsValues['9']['userPaymentsValuesValue'] ?? false)) {
	$payments['types']['9']['title'] = 'Оплата за 1 час, р.';
	$payments['types']['9']['titleShort'] = 'Почасовая';
	$payments['dates'][$date]['9']['data'] = $userShifts[$date] ?? null;
	
	$payments['dates'][$date]['9']['userPaymentsValuesValue'] = $paymentsValues['9']['userPaymentsValuesValue'];
	$payments['dates'][$date]['9']['reward'] = (
			$payments['dates'][$date]['9']['data']['scheduleSize'] ?? 0) //
			? ($paymentsValues['9']['userPaymentsValuesValue'] * ($payments['dates'][$date]['9']['data']['fingerDuration'] ?? 0) / 3600)//
			: 0;
}