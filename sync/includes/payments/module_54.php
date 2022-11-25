<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//printr($paymentsValues['54'] ?? 'ddd');
//printr($payments['dates'][$date]['54']['data'] ?? '');
if (in_array(54, $paymentTypes) && is_array($paymentsValues['54']['userPaymentsValuesValue'] ?? false)) {
    $percent = LT(
            ($paymentsValues['54']['userPaymentsValuesValue'] ?? []),
            ($payments['types']['54']['total'] ?? 0),
            $date);
    $payments['types']['54']['title'] = '% от всего абонемента первичного пациента';
    $payments['types']['54']['titleShort'] = ($percent * 100) . '% от<br>всего I аб. ';

    foreach (($payments['dates'][$date]['54']['data'] ?? []) as $paymentatdateIndex => $paymentatdate) {
        $payments['dates'][$date]['54']['data'][$paymentatdateIndex]['percent'] = $percent;
        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['54']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['54']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['54']['data'][$paymentatdateIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent;
                } else {
                    $payments['dates'][$date]['54']['data'][$paymentatdateIndex]['reward'] = 0;
                }
            }
        }
    }

    $payments['dates'][$date]['54']['reward'] = array_sum(array_column(($payments['dates'][$date]['54']['data'] ?? []), 'reward'));
}
