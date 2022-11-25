<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
if (($paymentsValues['64']['userPaymentsValuesValue'] ?? false)) {
    $payments['types']['64']['title'] = '% от личной продажи';
    $payments['types']['64']['titleShort'] = '% от личной<br>продажи';

//	printr($payments['dates'][$date]['64']['data']);
    $payments['dates'][$date]['64']['reward'] = 0;
    foreach (($payments['dates'][$date]['64']['data'] ?? []) as $paymentIndex => $paymentatdate) {
        $percent = $paymentsValues['64']['userPaymentsValuesValue'] / 100;
        $payments['dates'][$date]['64']['data'][$paymentIndex]['percent'] = $percent;
        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['64']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent;
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['64']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent;
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['64']['data'][$paymentIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent;
                } else {
                    $payments['dates'][$date]['64']['data'][$paymentIndex]['reward'] = 0;
                }
            }
        }
    }
    $payments['dates'][$date]['64']['reward'] = array_sum(array_column(($payments['dates'][$date]['64']['data'] ?? []), 'reward'));
}
