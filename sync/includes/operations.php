<?php

function getWage($idservicesApplied) {
	global $_USER, $__getWage, $__getWagePersonnelServiceDate, $__personalPrice;
	if (is_array($idservicesApplied)) {
		$serviceApplied = $idservicesApplied;
		$idservicesApplied = $idservicesApplied['idservicesApplied'];
	} else {
		if (isset($__getWage[$idservicesApplied])) {
			return $__getWage[$idservicesApplied];
		}
		$serviceApplied = mfa(mysqlQuery("SELECT `servicesAppliedDate`,`servicesAppliedContract`,`servicesAppliedService`,`servicesAppliedPersonal`,`servicesAppliedPrice`,`servicesAppliedService` FROM `servicesApplied` WHERE `idservicesApplied` = '" . $idservicesApplied . "'"));
	}


	$debug = false;
	if (!($serviceApplied['servicesAppliedService'] ?? false)) {
		printr($serviceApplied);
	}
//	printr($serviceApplied);
	$isfree = (!round($serviceApplied['servicesAppliedPrice'] ?? 0) && !$serviceApplied['servicesAppliedContract']);
//	print $isfree;
//	servicesAppliedPrice
//	servicesAppliedContract

	if (!($serviceApplied['servicesAppliedService'])) {
		$__getWage[$idservicesApplied] = 0;
		return 0;
	}
	if ($_USER['id'] == 176 && $debug) {
		print '$serviceApplied';
		printr($serviceApplied);
	}
//	print $isfree ? '1' : '0';

	$pricesSQL = "SELECT * FROM `servicesPrices` AS `a` INNER JOIN (SELECT MAX(`idservicesPrices`) AS `idservicesPricesMAX` FROM `servicesPrices` GROUP BY `servicesPricesService`,`servicesPricesType`) AS `b` ON (`a`.`idservicesPrices` = `b`.`idservicesPricesMAX`) LEFT JOIN servicesPricesTypes ON (idservicesPricesTypes = servicesPricesType) WHERE `servicesPricesService` = '" . $serviceApplied['servicesAppliedService'] . "'  AND `servicesPricesDate`<='" . $serviceApplied['servicesAppliedDate'] . "'";

	$pricesSQL = "SELECT * FROM `servicesPrices` where idservicesPrices in (SELECT max(idservicesPrices) FROM servicesPrices WHERE  `servicesPricesService` = " . $serviceApplied['servicesAppliedService'] . " AND `servicesPricesDate` <= '" . $serviceApplied['servicesAppliedDate'] . " 23:59:59' GROUP BY `servicesPricesType`)";

	$in = floatval($serviceApplied['servicesAppliedPrice']);

	if (!$in) {
//		return 0;
	}
//	print $in.'<br>';
	$prices = query2array(mysqlQuery($pricesSQL), 'servicesPricesType');
	if ($serviceApplied['servicesAppliedService']) {

		if (!isset($__personalPrice[$serviceApplied['servicesAppliedPersonal']][$serviceApplied['servicesAppliedService']][$serviceApplied['servicesAppliedDate']])) {


			$__personalPrice[$serviceApplied['servicesAppliedPersonal']][$serviceApplied['servicesAppliedService']][$serviceApplied['servicesAppliedDate']] = mfa(mysqlQuery("SELECT `usersServicesPaymentsSumm`,`usersServicesPaymentsSummFree` FROM `usersServicesPayments` WHERE `idusersServicesPayments` = (SELECT MAX(idusersServicesPayments) FROM `usersServicesPayments` "
							. " WHERE `usersServicesPaymentsUser` = " . $serviceApplied['servicesAppliedPersonal'] . " "
							. " AND `usersServicesPaymentsService`= " . $serviceApplied['servicesAppliedService'] . ""
							. " AND `usersServicesPaymentsDate`<='" . $serviceApplied['servicesAppliedDate'] . "')"));
		}
		$personalPrice = $__personalPrice[$serviceApplied['servicesAppliedPersonal']][$serviceApplied['servicesAppliedService']][$serviceApplied['servicesAppliedDate']];
	}


	if ($_USER['id'] == 176 && $debug) {
//		printr($pricesSQL);
		print '$personalPrice';
		printr($personalPrice);
		print '$prices';
		printr($prices);
	}
	$__getWagePersonnelServiceDate[$serviceApplied['servicesAppliedPersonal']][$serviceApplied['servicesAppliedService']][$serviceApplied['servicesAppliedDate']] = 0;
	$matrix = [];

	if ($isfree) {
		$matrix['w']['min'] = $personalPrice['usersServicesPaymentsSummFree'] ?? 0;
		$matrix['w']['max'] = $personalPrice['usersServicesPaymentsSummFree'] ?? 0;
		$matrix['p']['min'] = $prices['1']['servicesPricesPrice'] ?? $prices['2']['servicesPricesPrice'] ?? null;
		$matrix['p']['max'] = $prices['2']['servicesPricesPrice'] ?? $prices['1']['servicesPricesPrice'] ?? null;
	} else {
		$matrix['w']['min'] = $personalPrice['usersServicesPaymentsSumm'] ?? $prices['3']['servicesPricesPrice'] ?? $prices['4']['servicesPricesPrice'] ?? null;
		$matrix['w']['max'] = $personalPrice['usersServicesPaymentsSumm'] ?? $prices['4']['servicesPricesPrice'] ?? $prices['3']['servicesPricesPrice'] ?? null;

		$matrix['p']['min'] = $prices['1']['servicesPricesPrice'] ?? $prices['2']['servicesPricesPrice'] ?? null;
		$matrix['p']['max'] = $prices['2']['servicesPricesPrice'] ?? $prices['1']['servicesPricesPrice'] ?? null;
	}



	if ($_USER['id'] == 176 && $debug) {
//		printr($pricesSQL);
		printr($matrix);
	}

	if (
			is_null($matrix['w']['min']) ||
			is_null($matrix['w']['max']) ||
			is_null($matrix['p']['min']) ||
			is_null($matrix['p']['max'])
	) {
		$__getWage[$idservicesApplied] = 0;
		return 0;
	}
	$matrix['w']['d'] = $matrix['w']['max'] - $matrix['w']['min'];
	$matrix['p']['d'] = $matrix['p']['max'] - $matrix['p']['min'];

	if ($matrix['p']['d']) {
		$matrix['slope'] = $matrix['w']['d'] / $matrix['p']['d'];
	} else {
		$matrix['slope'] = null;
	}
	if (is_null($matrix['slope'])) {
		if ($in <= $matrix['p']['min']) {
			$__getWage[$idservicesApplied] = $matrix['w']['min'];
			return $matrix['w']['min'];
		} else {
			$__getWage[$idservicesApplied] = $matrix['w']['max'];
			return $matrix['w']['max'];
		}
	} else {
		if ($in <= $matrix['p']['min']) {
			$__getWage[$idservicesApplied] = $matrix['w']['min'];
			return $matrix['w']['min'];
		}
		if ($in >= $matrix['p']['max']) {
			$__getWage[$idservicesApplied] = $matrix['w']['max'];
			return $matrix['w']['max'];
		}
		$__getWage[$idservicesApplied] = $matrix['w']['min'] + ($in - $matrix['p']['min']) * $matrix['slope'];
		return $matrix['w']['min'] + ($in - $matrix['p']['min']) * $matrix['slope'];
	}
	$__getWage[$idservicesApplied] = 0;
	return 0;
}

function getFIO($user) {
	global $link;
	$userResult = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . mysqli_real_escape_string($link, $user) . "'"));
	if (!$user) {
		return '---';
	}
	return
			mb_substr($userResult['usersLastName'], 0, 1) .
			mb_substr($userResult['usersFirstName'], 0, 1) .
			mb_substr($userResult['usersMiddleName'], 0, 1)
	;
}

function contractInfo($idf_sale, $date = null) {
	$contract = mfa(mysqlQuery("SELECT * FROM `f_sales` LEFT JOIN `clients` ON (`idclients` = `f_salesClient`) WHERE `idf_sales`='" . intval($idf_sale) . "'"));

	$f_credits = query2array(mysqlQuery("SELECT * FROM `f_credits` WHERE `f_creditsSalesID`='" . $contract['idf_sales'] . "'"));
	$f_installments = mfa(mysqlQuery("SELECT * FROM `f_installments` WHERE `f_installmentsSalesID`='" . $contract['idf_sales'] . "'"));
	$f_payments = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID`='" . $contract['idf_sales'] . "'"));
	$f_balance = query2array(mysqlQuery("SELECT * FROM `f_balance` WHERE `f_balanceSalesID`='" . $contract['idf_sales'] . "'"));
	$f_subscriptions = query2array(mysqlQuery("SELECT * FROM `f_subscriptions` WHERE `f_subscriptionsContract`='" . $contract['idf_sales'] . "'"));
	$servicesApplied = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `servicesAppliedContract`='" . $contract['idf_sales'] . "'"));

	$remains = [];
	$calculatedSumm = 0;
	foreach ($f_subscriptions as $f_subscription) {
		$calculatedSumm += ($f_subscription['f_salesContentQty'] ?? 0) * ($f_subscription['f_salesContentPrice'] ?? 0);
	}
	$paymentsSumm = 0;
	if (is_array($f_payments ?? false)) {
		$paymentsSumm += array_sum(array_column($f_payments, 'f_paymentsAmount'));
	}
	if (is_array($f_credits ?? false)) {
		$paymentsSumm += array_sum(array_column($f_credits, 'f_creditsSumm'));
	}
	if (is_array($f_balance ?? false)) {
		$paymentsSumm += array_sum(array_column($f_balance, 'f_balanceAmount'));
	}

//	;


	$output = [
		'contract' => $contract,
		'calculatedSumm' => round($calculatedSumm),
		'f_salesSumm' => $contract['f_salesSumm'],
		'paymentsSumm' => $paymentsSumm,
		'paymentsOK' => ($contract['f_salesSumm'] > 0 && $paymentsSumm == round($calculatedSumm) && ($calculatedSumm ?? 0) > 0),
		'paymentsDebt' => $calculatedSumm - $paymentsSumm,
		'f_payments' => $f_payments,
		'f_credits' => $f_credits,
		'f_installments' => $f_installments,
		'f_subscriptions' => $f_subscriptions,
		'servicesApplied' => $servicesApplied,
	];
//	printr($output);
	return $output;
}

function sendSms($phone, $text) {
	$phone = "$phone";
	$username = SMSLOGIN;
	$password = SMSPASSWORD;
	$URL = 'https://target.tele2.ru/api/v2/send_message';
	$phone[0] = 7;
	$data = [
		"msisdn" => $phone,
		"shortcode" => SMSNAME,
		"text" => $text
	];
	$data_string = json_encode($data);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($username . ":" . $password), 'Content-Type:application/json']);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	$result = curl_exec($ch);
//	$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
	curl_close($ch);
	$resultARR = explode("\r\n", $result);
	$resultJSON = $resultARR[count($resultARR) - 1];
	$resultOBJ = json_decode($resultJSON, 1);
	return $resultOBJ;
}

function callVOIP($internalPhoneline = '220', $target = '89052084769') {
	global $_USER;
	$start = microtime(1);
	$timing = [['action' => 'begin', 'time' => microtime(1), 'delta' => 0]];
	$service_port = 5038;
	$address = ASTER_IP;
	$username = "manager";
	$password = "manager";
	$context = "call-home";
	$errors = [];
	if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
//		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 3, 'usec' => 0));
//		socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));
		$timing[] = ['action' => 'socketCreated', 'time' => microtime(1), 'delta' => round(microtime(1) - $start, 6)];
		if (($result = @socket_connect($socket, $address, $service_port))) {
			$timing[] = ['action' => 'socketConnected', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
			$authenticationRequest = "Action: Login\r\n";
			$authenticationRequest .= "Username: $username\r\n";
			$authenticationRequest .= "Secret: $password\r\n";
			$authenticationRequest .= "Events: off\r\n\r\n";
			$authenticate = socket_write($socket, $authenticationRequest);
			if ($authenticate > 0) {
				$timing[] = ['action' => 'authenticateSent', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
				usleep(200000);
				$authenticateResponse = socket_read($socket, 4048);
				$timing[] = ['action' => 'authenticateRead', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
				if (strpos($authenticateResponse, 'Success') !== false) {
					$timing[] = ['action' => 'authenticatePassed', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
					$originateRequest = "Action: Originate\r\n";
					$originateRequest .= "Channel: SIP/$internalPhoneline\r\n";
					$originateRequest .= "Callerid: " . $internalPhoneline . "\r\n";
					$originateRequest .= "Exten: $target\r\n";
					$originateRequest .= "Context: $context\r\n";
					$originateRequest .= "Priority: 1\r\n";
					$originateRequest .= "Async: yes\r\n\r\n";
					$originate = socket_write($socket, $originateRequest);
					$timing[] = ['action' => 'originateRequestSent', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
					if ($originate > 0) {
						$timing[] = ['action' => 'originateRequestSentSucces', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
						usleep(200000);
						$originateResponse = socket_read($socket, 4096);
						$timing[] = ['action' => 'originateResponseRead', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
						if (strpos($originateResponse, 'Success') !== false) {
							$timing[] = ['action' => 'originateSuccess', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
						} else {
							$errors[] = "Could not initiate call.\n";
							$timing[] = ['action' => 'originateFail', 'time' => microtime(1), 'originateResponse' => $originateResponse, 'originateRequest' => $originateRequest, 'delta' => (microtime(1) - $start)];
						}
					} else {
						$errors[] = "Could not write call initiation request to socket.\n";
						$timing[] = ['action' => 'originateWriteFail', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
					}
				} else {
					$timing[] = ['action' => 'authenticateFail', 'time' => microtime(1), 'authenticationRequest' => $authenticationRequest, 'delta' => (microtime(1) - $start)];
				}
			} else {
				$errors[] = 'cant write authenticationRequest to socket';
				$timing[] = ['action' => 'authenticate write Fail', 'time' => microtime(1), 'authenticationRequest' => $authenticationRequest, 'delta' => (microtime(1) - $start)];
			}
			socket_close($socket);
		} else {
			$errors[] = "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket));
			$timing[] = ['action' => 'socket_connect Fail', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
		}
	} else {
		$errors[] = 'cant create socket';
		$timing[] = ['action' => 'socket create Fail', 'time' => microtime(1), 'delta' => (microtime(1) - $start)];
	}
	mysqlQuery("INSERT INTO `VOIPcalls` SET "
			. "`VOIPcallsSuccess` ='" . (intval(!count($errors))) . "',"
			. " `VOIPcallsErrors` = " . (count($errors) ? ("'" . json_encode($errors, 288) . "'") : 'null') . ","
			. " `VOIPcallsTiming` = " . (1 ? ("'" . json_encode($timing, JSON_UNESCAPED_UNICODE) . "'") : 'null') . ","
			. " `VOIPcallsUser` = '" . $_USER['id'] . "'");
	return ['operator' => ($_USER['lname']) . ' ' . ($_USER['fname']) . ' (' . implode(', ', array_column(($_USER['positions'] ?? []), 'name')) . ')', 'success' => !count($errors), 'errors' => $errors, 'timing' => $timing];
}

function getAE($saleSumm, $date) {

	$params = query2array(mysqlQuery("SELECT * FROM `AEvalues` WHERE `AEvaluesDate` = (SELECT MAX(AEvaluesDate) FROM `AEvalues` WHERE `AEvaluesDate`<='" . $date . "')"));

	usort($params, function ($a, $b) {
		return floatval($a['AEvaluesAEqty']) <=> floatval($b['AEvaluesAEqty']);
	});
	/*
	  [idAEvalues] => 1
	  [AEvaluesDate] => 2015-01-01
	  [AEvaluesLTSumm] => 25000.00
	  [AEvaluesAEqty] => 0.00
	  [AEvaluesSetBy] => 176
	  [AEvaluesSetTime] => 2021-03-16 15:02:12
	 */
	foreach ($params as $param) {
		if ($saleSumm < $param['AEvaluesLTSumm']) {
			return $param['AEvaluesAEqty'];
		}
	}
	return null;
}

function getAEs($AEs, $saleSumm, $date) {
	$AEs = array_filter($AEs, function ($AE) use ($date) {
		return mystrtotime($date) > mystrtotime($AE['AEvaluesDate']);
	});
	usort($AEs, function ($a, $b) {
		if ($a['AEvaluesDate'] <=> $b['AEvaluesDate']) {
			return $b['AEvaluesDate'] <=> $a['AEvaluesDate'];
		}

		if ($a['AEvaluesAEqty'] <=> $b['AEvaluesAEqty']) {
			return floatval($a['AEvaluesDate']) <=> floatval($b['AEvaluesDate']);
		}
	});
	foreach ($AEs as $param) {
		if ($saleSumm < $param['AEvaluesLTSumm']) {
			return floatval($param['AEvaluesAEqty']);
		}
	}
	return null;
}

function getClientSatate($idclient, $date) {
	global $AEs;

	$servicesApplied = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE "
					. "`servicesAppliedClient`='$idclient'"
					. " AND `servicesAppliedDate` < '" . $date . "'"
					. " AND `servicesAppliedDeleteReason`<>5 "
					. " ORDER BY `servicesAppliedDate`"));
	if (!count($servicesApplied)) {
		return 'cold';
	}

	if (1) {
		$f_salesSQL = "SELECT *, "
				. "(SELECT SUM(IFNULL(`salesContentQty`, 0) - IFNULL(`servicesAppliedQty`, 0)) as `remains`"
				. "       FROM (SELECT `f_salesContentService`, SUM(`f_salesContentQty`) AS `salesContentQty` FROM `f_subscriptions` WHERE `f_subscriptionsContract` = `idf_sales` GROUP BY `f_salesContentService`) AS `subscriptions` "
				. "       LEFT JOIN (SELECT"
				. "				SUM(`servicesAppliedQty`) AS `servicesAppliedQty`,"
				. "				`servicesAppliedService` FROM `servicesApplied`"
				. "				WHERE `servicesAppliedContract` = `idf_sales`"
				. "				AND ISNULL(`servicesAppliedDeleted`) "
				. "				AND `servicesAppliedDate` < '" . $date . "'"
				. "				AND NOT ISNULL(`servicesAppliedFineshed`) "
				. "				GROUP BY `servicesAppliedService`) AS `servicesApplied` ON(`servicesAppliedService` = `f_salesContentService`) "
				. " ) as `remains`,"
				. " (SELECT MAX(`servicesAppliedDate`) FROM `servicesApplied` WHERE `servicesAppliedContract` = `idf_sales` AND ISNULL(`servicesAppliedDeleted`) AND NOT ISNULL(`servicesAppliedFineshed`)  ) AS `lastServicesApplied` "
				. " FROM `f_sales` WHERE `f_salesClient`='" . $idclient . "' AND `f_salesDate`<'" . $date . "'";
		$f_sales = array_values(array_filter(query2array(mysqlQuery($f_salesSQL)), function ($f_sale) use ($AEs) {
					return getAEs($AEs, $f_sale['f_salesSumm'], $f_sale['f_salesDate']) > 0;
				}));
//		printr($f_sales);
		if (!count($f_sales)) {
			if (mystrtotime($date) - mystrtotime($servicesApplied[count($servicesApplied) - 1]['servicesAppliedDate']) > (60 * 60 * 24 * 90)) {
				return 'cold';
			}
		} else {///client have AE>0
			$f_sales = array_values(array_filter($f_sales, function ($fsale) use ($date) {
						return !($fsale['remains'] <= 0 && (mystrtotime($date) - mystrtotime($fsale['lastServicesApplied']) > (60 * 60 * 24 * 90)));
					}));
			if (!count($f_sales)) {
				return 'cold';
			}
		}
	}
	return 'notcold';
}

function sendVisitsSales() {
	/*
	 * Первичные сайт визиты/продажи (сумма сег/с1го)
	 * Вторичные сайт визиты/продажи (сумма сег/с1го)
	 * Первичные лиды визиты/продажи (сумма сег/с1го)
	 * Вторичные лиды визиты/продажи (сумма сег/с1го)
	 * Остальные визиты/продажи (сумма сег/с1го)
	 */
	$visits = query2array(mysqlQuery(""
					. "SELECT * "
					. "FROM `clientsVisits` "
					. "LEFT JOIN `clients` ON (`idclients` = `clientsVisitsClient`)"
					. "WHERE `clientsVisitsDate` = CURDATE()"));
	$_Isite = array_filter($visits, function ($client) {
		return ($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 3;
	});
	$_IIsite = array_filter($visits, function ($client) {
		return ($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 3;
	});
	$_Ilg = array_filter($visits, function ($client) {
		return ($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 13;
	});
	$_IIlg = array_filter($visits, function ($client) {
		return ($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 13;
	});
	$_else = array_filter($visits, function ($client) {
		return
		!((($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 3) ||
		(($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 3) ||
		(($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 13) ||
		(($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 13))
		;
	});

	$allSales = query2array(mysqlQuery("SELECT *"
					. " FROM `f_sales`"
					. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
					. " WHERE `f_salesDate` BETWEEN '" . date('Y-m-01') . "' AND CURDATE();"));

	$f_sales = query2array(mysqlQuery("SELECT *"
					. " FROM `f_sales`"
					. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
					. " WHERE `f_salesDate` = CURDATE();"));

	$_IsiteS = array_filter($f_sales, function ($client) {
		return ($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 3;
	});
	$_IIsiteS = array_filter($f_sales, function ($client) {
		return ($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 3;
	});

	$_IlgS = array_filter($f_sales, function ($client) {
		return ($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 13;
	});
	$_IIlgS = array_filter($f_sales, function ($client) {
		return ($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 13;
	});
	$_elseS = array_filter($f_sales, function ($client) {
		return
		!((($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 3) ||
		(($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 3) ||
		(($client['clientsOldSince'] == null || $client['clientsOldSince'] == date('Y-m-d')) && $client['clientsSource'] == 13) ||
		(($client['clientsOldSince'] == null || mystrtotime($client['clientsOldSince']) < mystrtotime(date('Y-m-d'))) && $client['clientsSource'] == 13))
		;
	});

	$_IsiteAS = array_filter($allSales, function ($client) {
		return ($client['clientsOldSince'] == null || $client['f_salesDate'] == $client['clientsOldSince']) && $client['clientsSource'] == 3;
	});
	$_IIsiteAS = array_filter($allSales, function ($client) {
		return (mystrtotime($client['f_salesDate']) > mystrtotime($client['clientsOldSince'])) && $client['clientsSource'] == 3;
	});
	$_IlgAS = array_filter($allSales, function ($client) {
		return ($client['clientsOldSince'] == null || $client['f_salesDate'] == $client['clientsOldSince']) && $client['clientsSource'] == 13;
	});
	$_IIlgAS = array_filter($allSales, function ($client) {
		return (mystrtotime($client['f_salesDate']) > mystrtotime($client['clientsOldSince'])) && $client['clientsSource'] == 13;
	});
	$_elseAS = array_filter($allSales, function ($client) {
		return
		!(
		(($client['clientsOldSince'] == null || $client['f_salesDate'] == $client['clientsOldSince']) && $client['clientsSource'] == 3) ||
		((mystrtotime($client['f_salesDate']) > mystrtotime($client['clientsOldSince'])) && $client['clientsSource'] == 3) ||
		(($client['clientsOldSince'] == null || $client['f_salesDate'] == $client['clientsOldSince']) && $client['clientsSource'] == 13) ||
		((mystrtotime($client['f_salesDate']) > mystrtotime($client['clientsOldSince'])) && $client['clientsSource'] == 13)
		);
	});

//number_format(array_sum(array_column($_Is, 'f_salesSumm')), 0, '.', ' ')
	$message = "Первичные сайт " . count($_Isite ?? []) . "/" . count($_IsiteS) . " (" . number_format(array_sum(array_column($_IsiteS, 'f_salesSumm')), 0, '.', ' ') . "/" . number_format(
					array_sum(array_column($_IsiteAS, 'f_salesSumm')) - array_sum(array_column($_IsiteAS, 'f_salesCancellationSumm'))
					, 0, '.', ' ') . ")\n"
			. "Вторичные сайт " . count($_IIsite ?? []) . "/" . count($_IIsiteS) . " (" . number_format(array_sum(array_column($_IIsiteS, 'f_salesSumm')), 0, '.', ' ') . "/" . number_format(
					array_sum(array_column($_IIsiteAS, 'f_salesSumm')) - array_sum(array_column($_IIsiteAS, 'f_salesCancellationSumm'))
					, 0, '.', ' ') . ")\n"
			. "Первичные лиды " . count($_Ilg ?? []) . "/" . count($_IlgS) . " (" . number_format(array_sum(array_column($_IlgS, 'f_salesSumm')), 0, '.', ' ') . "/" . number_format(
					array_sum(array_column($_IlgAS, 'f_salesSumm')) - array_sum(array_column($_IlgAS, 'f_salesCancellationSumm'))
					, 0, '.', ' ') . ")\n"
			. "Вторичные лиды " . count($_IIlg ?? []) . "/" . count($_IIlgS) . " (" . number_format(array_sum(array_column($_IIlgS, 'f_salesSumm')), 0, '.', ' ') . "/" . number_format(
					array_sum(array_column($_IIlgAS, 'f_salesSumm')) - array_sum(array_column($_IIlgAS, 'f_salesCancellationSumm'))
					, 0, '.', ' ') . ")\n"
			. "Остальные " . count($_else ?? []) . "/" . count($_elseS) . " (" . number_format(array_sum(array_column($_elseS, 'f_salesSumm')), 0, '.', ' ') . "/" . number_format(
					array_sum(array_column($_elseAS, 'f_salesSumm')) - array_sum(array_column($_elseAS, 'f_salesCancellationSumm'))
					, 0, '.', ' ') . ")\n"
			. "ИТОГО: " . number_format(array_sum(array_column($f_sales, 'f_salesSumm')), 0, '.', ' ') . "/" . number_format(
					array_sum(array_column($allSales, 'f_salesSumm')) - array_sum(array_column($allSales, 'f_salesCancellationSumm'))
					, 0, '.', ' ');
	foreach (getUsersByRights(['149']) as $user) {
		if ($user['usersTG']) {
			sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => $message]);
		}
	}
}

function LT($ltgrids, $LTvalue, $LTdate) {// $ltgrids <- только нужные сетки  Ключ - дата.
//	printr($ltgrids);
	$grids = array_filter($ltgrids, function ($griddate) use ($LTdate) {

		return $griddate <= $LTdate;
	}, ARRAY_FILTER_USE_KEY);
	krsort($grids);

	$grids = array_values($grids);

	if (count($grids ?? [])) {
		$grid = $grids[0];
		if ($grid['type'] === "Z") {//calculations
			usort($grid['data'], function ($a, $b) {
				return ($a['from'] ?? 0) <=> ($b['from'] ?? 0);
			});
			$return = 0;
			foreach ($grid['data'] as $row) {
				$min = min($LTvalue, $row['to'] ?? $LTvalue);
				$delta = $min - $row['from'];
//				print('+' . $delta . 'x' . round($row['result']) . 'p.');
				$return += $delta * $row['result'];
				if ($min < $row['to'] || $row['to'] === null) {
					break;
				}
			}
			return $return;
		}
		if ($grid['type'] === "F") {


			$row = array_filter($grid['data'], function ($gridRow) use ($LTvalue) {
//				printr([$gridRow, $LTvalue], 1);
				if ($gridRow['from'] === null && $LTvalue <= $gridRow['to']) {
					return true;
				}
				if ($gridRow['from'] !== null && $gridRow['to'] !== null && $gridRow['from'] <= $LTvalue && $LTvalue <= $gridRow['to']) {
					return true;
				}
				if ($gridRow['from'] <= $LTvalue && $gridRow['to'] === null) {
					return true;
				}
				return false;
			});

			return array_values($row)[0]['result'] ?? null;
		}
//		printr($grid);
	}
}

function getEvotorKKTS() {
	$kkts = query2array(mysqlQuery("SELECT * FROM `evotorKKTS`"));
	return $kkts;
}

function getKKTS($identity) {
	$entity = mfa(mysqlQuery("SELECT * FROM `entities` LEFT JOIN `entitiesSBISkeys` ON (`identitiesSBISkeys` = (SELECT MAX(`identitiesSBISkeys`) FROM `entitiesSBISkeys` WHERE `entitiesSBISEntity` = `identities`)) WHERE `identities` = '" . mres($identity) . "'"));
	if (!$entity['entitiesTIN']) {
		print 'apsent entitiesTIN';
		die();
	}
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => 'https://api.sbis.ru/ofd/v1/orgs/' . $entity['entitiesTIN'] . '/kkts',
		CURLOPT_POST => false,
		CURLOPT_HEADER => 0,
		CURLINFO_HEADER_OUT => true,
		CURLOPT_HTTPHEADER => array(
			'Content-type:  application/json; charset=utf-8'
		),
		CURLOPT_COOKIE => 'sid=' . $entity['sid']
	));
	$response = curl_exec($ch);
	curl_close($ch);
	return json_decode($response);
}

function vatCalc($summ, $vatRate) {
	if (!$vatRate) {
		$vatRate = 0;
	}

	return round(-1 * (($summ) / (1 + $vatRate / 100) - $summ), 2);
}
