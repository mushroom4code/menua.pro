<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день 
if (($paymentsValues['46']['userPaymentsValuesValue'] ?? false)) {
	$payments['types']['46']['title'] = 'Маркетинг. Вторичка с разовыми процедурами.';
	$payments['types']['46']['titleShort'] = 'Вторичка<br>без абонов';
	if ($payments['dates'][$date]['46']['data'] ?? false) {
		$filtered = array_filter($payments['dates'][$date]['46']['data'], function ($client) use ($date) {
			return $client['scoreMarket'] && !$client['salesQty'] && $client['not_salesQty']==1;
		});
		
		foreach ($payments['dates'][$date]['46']['data'] as $notfilteredIndex => $notfiltered) {
			$payments['dates'][$date]['46']['data'][$notfilteredIndex]['check'] = !!($filtered[$notfilteredIndex] ?? false);
		}

		if ($paymentsValues['46']['userPaymentsValuesValue'] * count($payments['dates'][$date]['46']['data'] ?? [])) {
			$payments['dates'][$date]['46']['reward'] = $paymentsValues['46']['userPaymentsValuesValue'] * count($filtered ?? []);
		}
	}
}
