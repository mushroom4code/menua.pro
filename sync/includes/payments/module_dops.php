<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день

if (
		count($payments['dates'][$date]['dops']['data'] ?? []) && //Если есть процедуры
		!($paymentsValues['13']['userPaymentsValuesValue'] ?? false) //И НЕ установлен флажок "не учитывать процедуры 13]"
) {
	$payments['types']['dops']['title'] = 'Дополнительная оплата услуг';
	$payments['types']['dops']['titleShort'] = 'Оказано<br>услуг';

	$payments['dates'][$date]['dops']['reward'] = array_reduce($payments['dates'][$date]['dops']['data'], function ($carry, $item) use ($paymentsValues) {

		if (
				($item['servicesAppliedContract'] && $item['servicesAppliedPrice'])//Если услуга не бесплатная
		) {
			$carry += $item['servicesAppliedQty'] * ($item['usersServicesPaymentsSumm'] ?? $item['minWage'] ?? 0);
		} elseif ($item['usersServicesPaymentsSummFree']) {
			$carry += $item['servicesAppliedQty'] * ($item['usersServicesPaymentsSummFree'] ?? 0);
		}

		return $carry;
	}, 0);
}
