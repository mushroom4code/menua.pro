<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день

if ($paymentsValues['50']['userPaymentsValuesValue'] ?? false) {
//printr($paymentsValues['50'] ?? null);
  $payments['types']['50']['title'] = 'Бонус за продажу';
  $payments['types']['50']['titleShort'] = 'Бонус<br>за продажу';
  if ($payments['dates'][$date]['50']['data'] ?? false) {
//	 printr($payments['dates'][$date]['50']['data']);
	 foreach ($payments['dates'][$date]['50']['data'] as $index50 => $client) {
		$payments['dates'][$date]['50']['data'][$index50]['clientsSourceReward'] = $paymentsValues['50']['userPaymentsValuesValue'];
		$payments['dates'][$date]['50']['data'][$index50]['check'] = true;
	 }
	 $payments['dates'][$date]['50']['reward'] = $paymentsValues['50']['userPaymentsValuesValue'] * count($payments['dates'][$date]['50']['data']);
  }

//	$payments['dates'][$date]['50']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
//				return $f_sale['paymentDate'] == $date;
//			}));
  //$payments['dates'][$date]['50']['data']
}
