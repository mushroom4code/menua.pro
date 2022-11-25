<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день 
if (($paymentsValues['44']['userPaymentsValuesValue'] ?? false)) {
	$payments['types']['44']['title'] = 'Маркетинг. Премия за визит клиента (источник 28 Актель ВХОД)';
	$payments['types']['44']['titleShort'] = 'Приходы<br>А-вхд';
	if ($payments['dates'][$date]['44']['data'] ?? false) {
		$filtered = array_filter($payments['dates'][$date]['44']['data'], function ($client) use ($date) {
			return $client['scoreMarket'] && !$client['salesQty'] && !$client['not_salesQty'];
		});
		foreach ($payments['dates'][$date]['44']['data'] as $notfilteredIndex => $notfiltered) {
			$payments['dates'][$date]['44']['data'][$notfilteredIndex]['check'] = !!($filtered[$notfilteredIndex] ?? false);
		}

		if ($paymentsValues['44']['userPaymentsValuesValue'] * count($payments['dates'][$date]['44']['data'] ?? [])) {
			$payments['dates'][$date]['44']['reward'] = $paymentsValues['44']['userPaymentsValuesValue'] * count($filtered ?? []);
		}
	}
}
