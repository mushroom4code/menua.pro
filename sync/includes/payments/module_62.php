<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//printr($paymentsValues['62'] ?? 'ddd');
//printr($payments['dates'][$date]['62']['data'] ?? '');
if (in_array(62, $paymentTypes)) {
    $percent = ($paymentsValues['62']['userPaymentsValuesValue'] ?? 0) / 100;
    $payments['types']['62']['title'] = '% доли от платежа по вторичной продаже в которой сотрудник указан как СПЛ';
    $payments['types']['62']['titleShort'] = ($percent * 100) . '% от доли II аб. СПЛ';

    foreach (($payments['dates'][$date]['62']['data'] ?? []) as $paymentIndex => $paymentatdate) {

        $payments['dates'][$date]['62']['data'][$paymentIndex]['percent'] = $percent;
        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['62']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent / $paymentatdate['saleParticipants'];
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['62']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent / $paymentatdate['saleParticipants'];
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['62']['data'][$paymentIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent / $paymentatdate['saleParticipants'];
                } else {
                    $payments['dates'][$date]['62']['data'][$paymentIndex]['reward'] = 0;
                }
            }
        }
    }

    $payments['dates'][$date]['62']['reward'] = array_sum(array_column(($payments['dates'][$date]['62']['data'] ?? []), 'reward'));
}
