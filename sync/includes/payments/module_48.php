<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
if (($paymentsValues['48']['userPaymentsValuesValue'] ?? false)) {
	$payments['types']['48']['title'] = '% от продажи (дежурная смена) делить на всех участников';
	$payments['types']['48']['titleShort'] = '% от ДС<br>продаж';

	$payments['dates'][$date]['48']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
				return $f_sale['paymentDate'] == $date && $f_sale['usersSalesScheduleDuty'];
			}));

	foreach ($payments['dates'][$date]['48']['data'] as $paymentIndex => $payment) {
		$payments['dates'][$date]['48']['data'][$paymentIndex]['percent'] = $paymentsValues['48']['userPaymentsValuesValue'];
	}

	//$payments['dates'][$date]['84']['data']


	$payments['dates'][$date]['48']['reward'] = (array_reduce($payments['dates'][$date]['48']['data'], function ($carry, $item) use ($paymentsValues) {
				return $carry + ($item['payment'] / $item['saleParticipants']) * $paymentsValues['48']['userPaymentsValuesValue'] / 100;
			}, 0));
}
