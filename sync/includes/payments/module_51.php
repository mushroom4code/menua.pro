<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
if (($paymentsValues['51']['userPaymentsValuesValue'] ?? false)) {
    $payments['types']['51']['title'] = '% от всего абонемента первичного пациента';
    $payments['types']['51']['titleShort'] = '% от всего<br>пер.абон';

    $payments['dates'][$date]['51']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
                return $f_sale['f_salesType'] == 1 && $f_sale['paymentDate'] == $date;
            }));

//	printr($payments['dates'][$date]['51']['data']);
    $payments['dates'][$date]['51']['reward'] = 0;
    foreach ($payments['dates'][$date]['51']['data'] as $paymentIndex => $paymentatdate) {
        $percent = $paymentsValues['51']['userPaymentsValuesValue'] / 100;
        $payments['dates'][$date]['51']['data'][$paymentIndex]['percent'] = $percent;

        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['51']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent;
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['51']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent;
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['51']['data'][$paymentIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent;
                } else {
                    
                }
            }
        }
   }
    $payments['dates'][$date]['51']['reward'] = array_sum(array_column($payments['dates'][$date]['51']['data'], 'reward'));
}
