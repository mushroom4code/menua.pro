<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
if (($paymentsValues['53']['userPaymentsValuesValue'] ?? false)) {
    $payments['types']['53']['title'] = '% от продажи делить на всех участников';
    $payments['types']['53']['titleShort'] = '% от доли<br>втор.абон';
//	printr($f_payments);
    $payments['dates'][$date]['53']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
//                printr([$date, $f_sale]);
                return $f_sale['f_salesType'] == 2 && $date == $f_sale['paymentDate'];
            }));

    foreach ($payments['dates'][$date]['53']['data'] as $paymentIndex => $paymentatdate) {
        $percent = ($paymentsValues['53']['userPaymentsValuesValue'] / 100);
        $payments['dates'][$date]['53']['data'][$paymentIndex]['percent'] = $percent;

        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['53']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent / $paymentatdate['saleParticipants'];
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['53']['data'][$paymentIndex]['reward'] = ($paymentatdate['payment']) * $percent / $paymentatdate['saleParticipants'];
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {

                    $payments['dates'][$date]['53']['data'][$paymentIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent / $paymentatdate['saleParticipants'];
                    $payments['dates'][$date]['53']['data'][$paymentIndex]['info'] = [($paymentatdate['prePaymentsSumm'] ?? 0), ($paymentatdate['payment'] ?? 0), $percent / $paymentatdate['saleParticipants']];
                } else {
                    
                }
            }
        }
    }
    $payments['dates'][$date]['53']['reward'] = array_sum(array_column($payments['dates'][$date]['53']['data'], 'reward'));
    //$payments['dates'][$date]['53']['data']
//    $payments['dates'][$date]['53']['reward'] = (array_reduce($payments['dates'][$date]['53']['data'], function ($carry, $item) use ($percent) {
//                return $carry + ($item['payment'] / $item['saleParticipants']) * $percent;
//            }, 0));
}
