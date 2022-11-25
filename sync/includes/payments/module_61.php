<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//printr($paymentsValues['61'] ?? 'ddd');
//printr($payments['dates'][$date]['61']['data'] ?? '');
if (in_array(61, $paymentTypes) && is_array($paymentsValues['61']['userPaymentsValuesValue'] ?? false)) {
    $percent = LT(
            ($paymentsValues['61']['userPaymentsValuesValue'] ?? []),
            ($payments['types']['61']['total'] ?? 0),
            $date);
    $payments['types']['61']['title'] = '% доли от платежа по вторичной продаже в которой сотрудник указан как ПМ';
    $payments['types']['61']['titleShort'] = ($percent * 100) . '% от <br>доли II аб. ПМ';

    ///////////////////////////////////////////////////
    foreach (($payments['dates'][$date]['61']['data'] ?? []) as $paymentatdateIndex => $paymentatdate) {

        $payments['dates'][$date]['61']['data'][$paymentatdateIndex]['percent'] = $percent;
//        $payments['dates'][$date]['61']['data'][$paymentatdateIndex]['summToApply'] = 0;

        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['61']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent / $paymentatdate['saleParticipants'];
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['61']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent / $paymentatdate['saleParticipants'];
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['61']['data'][$paymentatdateIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent / $paymentatdate['saleParticipants'];
                } else {
//					printr($paymentatdate); 
                    $payments['dates'][$date]['61']['data'][$paymentatdateIndex]['reward'] = 0;
                }
            }
        }
    }
    ///////////////////////////////////////////////////////

    $payments['dates'][$date]['61']['reward'] = array_sum(array_column(($payments['dates'][$date]['61']['data'] ?? []), 'reward'));

//    $payments['dates'][$date]['61']['reward'] = (array_reduce(($payments['dates'][$date]['61']['data'] ?? []), function ($carry, $item) use ($paymentsValues) {
//
//                return $carry + ($item['payment'] / $item['saleParticipants']);
//            }, 0)) * $payments['dates'][$date]['61']['coeff'];
//	$payments['dates'][$date]['61']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
//				return $f_sale['paymentDate'] == $date;
//			}));
    //$payments['dates'][$date]['61']['data']
}
