<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
if (($paymentsValues['39']['userPaymentsValuesValue'] ?? false)) {
    $payments['types']['39']['title'] = '% от продажи делить на всех участников';
    $payments['types']['39']['titleShort'] = '% от доли<br>продаж';

    $payments['dates'][$date]['39']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
                return $f_sale['paymentDate'] == $date && !$f_sale['usersSalesScheduleDuty'];
            }));

    foreach ($payments['dates'][$date]['39']['data'] as $paymentIndex => $paymentatdate) {
        $percent = $paymentsValues['39']['userPaymentsValuesValue'] / 100;
        $payments['dates'][$date]['39']['data'][$paymentIndex]['percent'] = $percent;
        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['39']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent / $paymentatdate['saleParticipants'];
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['39']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent / $paymentatdate['saleParticipants'];
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['39']['data'][$paymentIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent / $paymentatdate['saleParticipants'];
                } else {
                    $payments['dates'][$date]['39']['data'][$paymentIndex]['reward'] = 0;
                }
            }
        }
    }
    //$payments['dates'][$date]['39']['data']
//    $payments['dates'][$date]['39']['reward'] = (array_reduce($payments['dates'][$date]['39']['data'], function ($carry, $item) use ($paymentsValues) {
//                return $carry + ($item['payment'] / $item['saleParticipants']) * $paymentsValues['39']['userPaymentsValuesValue'] / 100;
//            }, 0));

    $payments['dates'][$date]['39']['reward'] = array_sum(array_column($payments['dates'][$date]['39']['data'], 'reward'));
}
