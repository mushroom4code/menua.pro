<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день

if (
		count($payments['dates'][$date]['dops']['data'] ?? []) && //Если есть процедуры
		($paymentsValues['33']['userPaymentsValuesValue'] ?? false) // считаем процент от продаж
//И НЕ установлен флажок "не учитывать процедуры 13]"
) {
//	printr($date);
	$payments['types']['33']['title'] = 'Дополнительная оплата услуг';
	$payments['types']['33']['titleShort'] = '% от стоимости<br>оказанных<br>услуг';

	$payments['dates'][$date]['33']['reward'] = ($paymentsValues['33']['userPaymentsValuesValue'] / 100) * array_reduce(($payments['dates'][$date]['dops']['data'] ?? []), function ($carry, $item) {
				if (
						($item['servicesAppliedContract'] && $item['servicesAppliedPrice'])//Если услуга не бесплатная
				) {
					$carry += $item['servicesAppliedQty'] * $item['servicesAppliedPrice'];
				}
				return $carry;
			}, 0);
}
