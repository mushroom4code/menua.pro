<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день
//printr($paymentsValues['59'] ?? 'ddd');
//printr($payments['dates'][$date]['59']['data'] ?? '');
if (in_array(59, $paymentTypes) && is_array($paymentsValues['59']['userPaymentsValuesValue'] ?? false)) {
    $percent = LT(
            ($paymentsValues['59']['userPaymentsValuesValue'] ?? []),
            ($payments['types']['59']['total'] ?? 0),
            $date);
    $payments['types']['59']['title'] = '% от всего платежа по первичной продаже в которой сотрудник указан как ПМ';
    $payments['types']['59']['titleShort'] = ($percent * 100) . '% от <br>всего I аб. ПМ';

    ///////////////////////////////////////////////////
    foreach (($payments['dates'][$date]['59']['data'] ?? []) as $paymentatdateIndex => $paymentatdate) {

        $payments['dates'][$date]['59']['data'][$paymentatdateIndex]['percent'] = $percent;
//        $payments['dates'][$date]['59']['data'][$paymentatdateIndex]['summToApply'] = 0;

        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['59']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['59']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['59']['data'][$paymentatdateIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent;
                } else {
//					printr($paymentatdate); 
                    $payments['dates'][$date]['59']['data'][$paymentatdateIndex]['reward'] = 0;
                }
            }
        }
    }
    ///////////////////////////////////////////////////////

    $payments['dates'][$date]['59']['reward'] = array_sum(array_column(($payments['dates'][$date]['59']['data'] ?? []), 'reward'));

//    $payments['dates'][$date]['59']['reward'] = (array_reduce(($payments['dates'][$date]['59']['data'] ?? []), function ($carry, $item) use ($paymentsValues) {
//
//                return $carry + ($item['payment'] / $item['saleParticipants']);
//            }, 0)) * $payments['dates'][$date]['59']['coeff'];
//	$payments['dates'][$date]['59']['data'] = array_values(array_filter($f_payments, function ($f_sale) use ($date) {
//				return $f_sale['paymentDate'] == $date;
//			}));
    //$payments['dates'][$date]['59']['data']
}
