<?php
//userPaymentsTypesName
//userPaymentsTypesShort
//
//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день 
if (($paymentsValues['43']['userPaymentsValuesValue'] ?? false)) {
  $payments['types']['43']['title'] = 'Маркетинг. Премия за визит клиента (источник 13 Лидогенерация МСК)';
  $payments['types']['43']['titleShort'] = 'Приходы<br>ЛГМ';
  if ($payments['dates'][$date]['43']['data'] ?? false) {
	 $filtered = array_filter($payments['dates'][$date]['43']['data'], function ($client) use ($date) {
		return $client['scoreMarket'] && !$client['salesQty'] && !$client['not_salesQty'];
	 });
	 foreach ($payments['dates'][$date]['43']['data'] as $notfilteredIndex => $notfiltered) {
		$payments['dates'][$date]['43']['data'][$notfilteredIndex]['check'] = !!($filtered[$notfilteredIndex] ?? false);
		$payments['dates'][$date]['43']['data'][$notfilteredIndex]['clientsSourceReward'] = $paymentsValues['43']['userPaymentsValuesValue'];
	 }

	 if ($paymentsValues['43']['userPaymentsValuesValue'] * count($payments['dates'][$date]['43']['data'] ?? [])) {
		$payments['dates'][$date]['43']['reward'] = $paymentsValues['43']['userPaymentsValuesValue'] * count($filtered ?? []);
	 }
  }
}
