<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день 
if (($paymentsValues['56']['userPaymentsValuesValue'] ?? false)) {
    $payments['types']['56']['title'] = 'Маркетинг. Премия за визит клиента согласно источнику';
    $payments['types']['56']['titleShort'] = 'Приходы по<br>источнику';
    $rewards56filtered = array_filter($rewards56, function ($reward) use ($date) {
//		printr([$reward['clientsSourcesRewardsDate'], $date, strtotime($reward['clientsSourcesRewardsDate']) <= strtotime($date)]);
        return strtotime($reward['clientsSourcesRewardsDate']) <= strtotime($date);
    });
    $rewards56today = [];
    foreach ($rewards56filtered as $reward) {
        if (!($rewards56today[$reward['clientsSourcesRewardsSource']] ?? false)) {
            $rewards56today[$reward['clientsSourcesRewardsSource']] = $reward;
        }
    }

    /* {
      "2": {
      "idclientsSourcesRewards": 1,
      "clientsSourcesRewardsSource": 2,
      "clientsSourcesRewardsReward": 1000,
      "clientsSourcesRewardsDate": "2022-07-01"
      }, */


//	printr($rewards56);
//	printr($rewards56today);
    if ($payments['dates'][$date]['56']['data'] ?? false) {

        foreach ($payments['dates'][$date]['56']['data'] as $clientIndex => $client) {
            $payments['dates'][$date]['56']['data'][$clientIndex]['clientsSourceReward'] = ($rewards56today[$client['clientsSource']]['clientsSourcesRewardsReward'] ?? 0);
        }

        $filtered = array_filter($payments['dates'][$date]['56']['data'], function ($client) use ($date) {
            return $client['scoreMarket'] && !$client['salesQty'] && !$client['not_salesQty'] && ($client['lastVizitMonthes'] === null || $client['lastVizitMonthes'] >= 6);
        });
//		printr($payments['dates'][$date]['56']['data']);
        $payments['dates'][$date]['56']['reward'] = 0;
        foreach ($payments['dates'][$date]['56']['data'] as $notfilteredIndex => $notfiltered) {
            $payments['dates'][$date]['56']['data'][$notfilteredIndex]['check'] = !!($filtered[$notfilteredIndex] ?? false);
            if ($payments['dates'][$date]['56']['data'][$notfilteredIndex]['check']) {
                $payments['dates'][$date]['56']['reward'] += $notfiltered['clientsSourceReward'];
            }
        }
    }
}
