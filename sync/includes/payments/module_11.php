<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день

if (in_array(11, $paymentTypes) && is_array($paymentsValues['11']['userPaymentsValuesValue'] ?? [])) {
	$payments['types']['11']['title'] = '#% от продажи делить на всех участников';

	$payments['dates'][$date]['11']['coeff'] = LT(
			($paymentsValues['11']['userPaymentsValuesValue'] ?? []),
			($payments['types']['11']['total'] ?? 0),
			$date);
	
	$payments['types']['11']['titleShort'] = (($payments['dates'][$date]['11']['coeff']) * 100) . '% от<br>продаж ';

	$payments['dates'][$date]['11']['reward'] = (array_reduce(($payments['dates'][$date]['11']['data'] ?? []), function ($carry, $item) use ($paymentsValues) {
				global $_USER;
				if ($_USER['id'] == 176) {
//					printr($item);
				}
				return $carry + ($item['payment'] / $item['saleParticipants']);
			}, 0)) * $payments['dates'][$date]['11']['coeff'];

//	$payments['dates'][$date]['11']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
//				return $f_sale['paymentDate'] == $date;
//			}));
	//$payments['dates'][$date]['11']['data']
}
