<?php

//мы внутри цикла по дням. $date - дата каждого дня, $paymentsValues - данные по оплате труда на этот день

$PV49 = array_filter($userPaymentsValues, function ($paymentValue) {
    return $paymentValue['iduserPaymentsTypes'] == 49;
});
$PV49show = false;

foreach (($payments['dates'][$date]['49']['data'] ?? []) as $payment49index => $payment49) {

    $payments['dates'][$date]['49']['data'][$payment49index]['PV49@paymentDate'] = (getPaymentsValues($PV49, $date)['49']['userPaymentsValuesValue'] ?? 0) / 100;

    $payments['dates'][$date]['49']['data'][$payment49index]['PV49'] = $payments['dates'][$date]['49']['data'][$payment49index]['PV49@paymentDate'];
    if ($payments['dates'][$date]['49']['data'][$payment49index]['PV49']) {
        $PV49show = true;
    }
}

if ($PV49show) {
    $payments['types']['49']['title'] = '% от выручки с участием этого сотрудника';
    $payments['types']['49']['titleShort'] = '% от выручки';
    $todaysumm = 0;

    foreach (($payments['dates'][$date]['49']['data'] ?? []) as $paymentatdateIndex => $paymentatdate) {
        $todaysumm += ($paymentatdate['payment'] ?? 0) * $paymentatdate['PV49']; //($paymentatdate['myShift'] == 1 ) ? : 0;


        $percent = $paymentatdate['PV49'];
        $payments['dates'][$date]['49']['data'][$paymentatdateIndex]['percent'] = $percent;
//        $payments['dates'][$date]['40']['data'][$paymentatdateIndex]['summToApply'] = 0;

        if ($paymentatdate['f_salesDate'] < '2022-08-01') {
            $payments['dates'][$date]['49']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
        } else {

            if (($paymentatdate['prePaymentsSumm'] ?? 0) > 15000) {

                $payments['dates'][$date]['49']['data'][$paymentatdateIndex]['reward'] = ($paymentatdate['payment'] ?? 0) * $percent;
            } else {//prePaymentsSumm<15000
                if (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0) > 15000) {
                    $payments['dates'][$date]['49']['data'][$paymentatdateIndex]['reward'] = (($paymentatdate['prePaymentsSumm'] ?? 0) + ($paymentatdate['payment'] ?? 0)) * $percent;
                } else {
//					printr($paymentatdate); 
                    $payments['dates'][$date]['49']['data'][$paymentatdateIndex]['reward'] = 0;
                }
            }
        }
    }



    $payments['dates'][$date]['49']['reward'] = array_sum(array_column($payments['dates'][$date]['49']['data'], 'reward'));
    if ($todaysumm !== 0) {
//		$payments['dates'][$date]['49']['reward'] = $todaysumm;
    }
}


//if (($paymentsValues['49']['userPaymentsValuesValue'] ?? false)) {
//	$payments['types']['49']['title'] = 'Процент от выручки';
//	$payments['types']['49']['titleShort'] = '% от <br>оборота РД';
//
//	$todaysumm = array_reduce(($payments['dates'][$date]['49']['data'] ?? []), function ($carry, $item) {
////		print $item['paymentValue'] . '/';
//		return $carry + (($item['myShift'] == 1 ) ? ($item['paymentValue'] ?? 0) : 0);
//	}, 0);
//	$payments['dates'][$date]['49']['userPaymentsValuesValue'] = ($paymentsValues['49']['userPaymentsValuesValue'] / 100);
//	$payments['dates'][$date]['49']['reward'] = ($paymentsValues['49']['userPaymentsValuesValue'] / 100) * $todaysumm;
//
//	printr($payments['dates'][$date]['49'] ?? '', false, 'pink');
//}