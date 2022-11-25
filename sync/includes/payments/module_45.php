<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день 
if (($paymentsValues['45']['userPaymentsValuesValue'] ?? false)) {
  $payments['types']['45']['title'] = 'Маркетинг. Премия за визит клиента (источник не 13 и не 28)';
  $payments['types']['45']['titleShort'] = 'Приходы<br>не (ЛГМ,А-вхд)';
  if ($payments['dates'][$date]['45']['data'] ?? false) {
	 $filtered = array_filter($payments['dates'][$date]['45']['data'], function ($client) use ($date) {
		return $client['scoreMarket'] && !$client['salesQty'] && !$client['not_salesQty'] //стоит зачёт

		;
	 });
	 foreach ($payments['dates'][$date]['45']['data'] as $notfilteredIndex => $notfiltered) {
		$payments['dates'][$date]['45']['data'][$notfilteredIndex]['check'] = !!($filtered[$notfilteredIndex] ?? false);
		$payments['dates'][$date]['45']['data'][$notfilteredIndex]['clientsSourceReward'] = $paymentsValues['45']['userPaymentsValuesValue'];
	 }

	 if ($paymentsValues['45']['userPaymentsValuesValue'] * count($payments['dates'][$date]['45']['data'] ?? [])) {

		$payments['dates'][$date]['45']['reward'] = $paymentsValues['45']['userPaymentsValuesValue'] * count($filtered ?? []);
	 }
  }
}
