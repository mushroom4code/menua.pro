<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//printr($paymentsValues['52'] ?? 'ddd');
//printr($payments['dates'][$date]['52']['data'] ?? '');
if (in_array(52, $paymentTypes) && is_array($paymentsValues['52']['userPaymentsValuesValue'] ?? false)) {
    $percent = LT(
            ($paymentsValues['52']['userPaymentsValuesValue'] ?? []),
            ($payments['types']['52']['total'] ?? 0),
            $date);
    $payments['types']['52']['title'] = '#% от доли абонемента вторичного пациента';
    $payments['types']['52']['titleShort'] = ($percent * 100) . '% от <br>доли II аб. ';

    ///////////////////////////////////////////////////
    foreach (($payments['dates'][$date]['52']['data'] ?? []) as $paymentatdateIndex => $paymentatdate) {

        $payments['dates'][$date]['52']['data'][$paymentatdateIndex]['percent'] = $percent;
//        $payments['dates'][$date]['52']['data'][$paymentatdateIndex]['summToApply'] = 0;

        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['52']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent / $paymentatdate['saleParticipants'];
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['52']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent / $paymentatdate['saleParticipants'];
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['52']['data'][$paymentatdateIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent / $paymentatdate['saleParticipants'];
                } else {
//					printr($paymentatdate); 
                    $payments['dates'][$date]['52']['data'][$paymentatdateIndex]['reward'] = 0;
                }
            }
        }
    }
    ///////////////////////////////////////////////////////

    $payments['dates'][$date]['52']['reward'] = array_sum(array_column(($payments['dates'][$date]['52']['data'] ?? []), 'reward'));

//    $payments['dates'][$date]['52']['reward'] = (array_reduce(($payments['dates'][$date]['52']['data'] ?? []), function ($carry, $item) use ($paymentsValues) {
//
//                return $carry + ($item['payment'] / $item['saleParticipants']);
//            }, 0)) * $payments['dates'][$date]['52']['coeff'];
//	$payments['dates'][$date]['52']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
//				return $f_sale['paymentDate'] == $date;
//			}));
    //$payments['dates'][$date]['52']['data']
}
