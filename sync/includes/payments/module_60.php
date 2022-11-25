<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//printr($paymentsValues['60'] ?? 'ddd');
//printr($payments['dates'][$date]['60']['data'] ?? '');
if (in_array(60, $paymentTypes)) {
    $percent = ($paymentsValues['60']['userPaymentsValuesValue'] ?? 0) / 100;
    $payments['types']['60']['title'] = '% от всего платежа по первичной продаже в которой сотрудник указан как СПЛ';
    $payments['types']['60']['titleShort'] = ($percent * 100) . '% от всего I аб. СПЛ';

    foreach (($payments['dates'][$date]['60']['data'] ?? []) as $paymentIndex => $paymentatdate) {

        $payments['dates'][$date]['60']['data'][$paymentIndex]['percent'] = $percent;
        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['60']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent;
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['60']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent;
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['60']['data'][$paymentIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent;
                } else {
                    $payments['dates'][$date]['60']['data'][$paymentIndex]['reward'] = 0;
                }
            }
        }
    }

    $payments['dates'][$date]['60']['reward'] = array_sum(array_column(($payments['dates'][$date]['60']['data'] ?? []), 'reward'));
}
