<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день


$PV40 = array_filter($userPaymentsValues, function ($paymentValue) {
    return $paymentValue['iduserPaymentsTypes'] == 40;
});

$PV40show = false;
foreach (($payments['dates'][$date]['40']['data'] ?? []) as $payment40index => $payment40) {
    $payments['dates'][$date]['40']['data'][$payment40index]['PV40@saleDate'] = (getPaymentsValues($PV40, $payment40['f_salesDate'])['40']['userPaymentsValuesValue'] ?? 0) / 100;
    $payments['dates'][$date]['40']['data'][$payment40index]['PV40@paymentDate'] = (getPaymentsValues($PV40, $date)['40']['userPaymentsValuesValue'] ?? 0) / 100;

    $payments['dates'][$date]['40']['data'][$payment40index]['PV40'] = $payments['dates'][$date]['40']['data'][$payment40index]['PV40@paymentDate'];

    if ($payments['dates'][$date]['40']['data'][$payment40index]['PV40']) {
        $PV40show = true;
    }
}

//printr($payments['dates'][$date]['40'] ?? '');

if ($PV40show) {
    $payments['types']['40']['title'] = 'Процент от выручки';
    $payments['types']['40']['titleShort'] = '% от <br>оборота';
    $todaysumm = 0;
    foreach (($payments['dates'][$date]['40']['data'] ?? []) as $paymentatdateIndex => $paymentatdate) {
        $percent = $paymentatdate['PV40'];
        $payments['dates'][$date]['40']['data'][$paymentatdateIndex]['percent'] = $percent;
//        $payments['dates'][$date]['40']['data'][$paymentatdateIndex]['summToApply'] = 0;

        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['40']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['40']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['40']['data'][$paymentatdateIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent;
                } else {
//					printr($paymentatdate); 
                    $payments['dates'][$date]['40']['data'][$paymentatdateIndex]['reward'] = 0;
                }
            }
        }
    }

    $payments['dates'][$date]['40']['reward'] = array_sum(array_column($payments['dates'][$date]['40']['data'], 'reward'));
    if ($todaysumm !== 0) {
        
    }
}