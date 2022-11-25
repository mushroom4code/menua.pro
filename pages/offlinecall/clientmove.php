<?php

if (R(157) && ($_GET['client'] ?? false) && isset($_POST['targetdatabase'])) {
	$idclient = $_GET['client'];
	$TARGETDATABASE = ['0' => 'warehouse', '1' => 'vita'][$_POST['targetdatabase']];
	$SUBDOMEN = ['0' => '', '1' => 'vita.'][$_POST['targetdatabase']];

	/// ПОЛУЧАЕМ ДАННЫЕ КЛИЕНТА
	$client = mfa(mysqlQuery("SELECT idclients, GUID,clientsLName,clientsFName,clientsMName,clientsBDay,clientsAKNum,clientsAddedAt,clientsGender,clientsOldSince FROM `clients` WHERE `idclients` = '" . $idclient . "'"));
	$client['clientsPassports'] = query2array(mysqlQuery("SELECT * FROM `clientsPassports` WHERE `clientsPassportsClient` = '" . $client['idclients'] . "'"));
	$client['clientsPhones'] = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "'"));
	$client['servicesApplied'] = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE isnull(`servicesAppliedContract`) AND `servicesAppliedClient` = '" . $client['idclients'] . "'"));

	$client['f_sales'] = query2array(mysqlQuery("SELECT idf_sales,f_salesSumm,f_salesComment,f_salesTime,f_salesDate,f_salesType,f_salesCancellationDate,f_salesCancellationSumm,import,f_salesGUID FROM `f_sales` WHERE `f_salesClient` = '" . $client['idclients'] . "'"));
	foreach ($client['f_sales'] as $N_f_sales => $f_sale) {
		$client['f_sales'][$N_f_sales]['f_credits'] = query2array(mysqlQuery("SELECT * FROM `f_credits` WHERE `f_creditsSalesID` = '" . $f_sale['idf_sales'] . "'"));
		$client['f_sales'][$N_f_sales]['f_installments'] = query2array(mysqlQuery("SELECT * FROM `f_installments` WHERE `f_installmentsSalesID` = '" . $f_sale['idf_sales'] . "'"));
		$client['f_sales'][$N_f_sales]['f_payments'] = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` = '" . $f_sale['idf_sales'] . "'"));
		$client['f_sales'][$N_f_sales]['f_subscriptions'] = query2array(mysqlQuery("SELECT * FROM `f_subscriptions` WHERE `f_subscriptionsContract` = '" . $f_sale['idf_sales'] . "'"));
		$client['f_sales'][$N_f_sales]['servicesApplied'] = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `servicesAppliedContract` = '" . $f_sale['idf_sales'] . "'"));
	}
//	print '<pre>' . json_encode($clie nt, 256 + 128) . '</pre>';
/// СОХРАНЯЕМ ДАННЫЕ КЛИЕНТА
	mysqlQuery("INSERT INTO `$TARGETDATABASE`.`clients` SET "
			. " `GUID`='" . $client['GUID'] . "',"
			. " `clientsLName`=" . sqlVON($client['clientsLName']) . ","
			. " `clientsFName`=" . sqlVON($client['clientsFName']) . ","
			. " `clientsMName`=" . sqlVON($client['clientsMName']) . ","
			. " `clientsBDay`=" . sqlVON($client['clientsBDay']) . ","
			. " `clientsAKNum`=" . sqlVON($client['clientsAKNum']) . ","
//			. " `clientsHash`='" . $_USER['id'] . "',"
			. " `clientsAddedAt`= NOW(),"
			. " `clientsGender`=" . sqlVON($client['clientsGender']) . ","
			. " `clientsSource`='18',"
			. " `clientsOldSince`=" . sqlVON($client['clientsOldSince']) . "");
	$new_idclients = mysqli_insert_id($link);
	if (!$new_idclients) {
		die('Не удалось создать клиента. ' . mysqli_error($link));
	}
//	print '<a target="_blank" href="https://menua.pro/pages/offlinecall/schedule.php?client=' . $new_idclients . '">' . $new_idclients . '</a>';

	foreach ($client['f_sales'] as $N_f_sales => $f_sale) {
//	printr($f_sale);

		mysqlQuery("INSERT INTO `$TARGETDATABASE`.`f_sales` SET "
				. "`f_salesClient`='" . $new_idclients . "',"
				. "`f_salesSumm`=" . sqlVON($f_sale['f_salesSumm']) . ","
				. "`f_salesComment`=" . sqlVON($f_sale['f_salesComment']) . ","
				. "`f_salesTime`=" . sqlVON($f_sale['f_salesTime']) . ","
				. "`f_salesDate`=" . sqlVON($f_sale['f_salesDate']) . ","
				. "`f_salesType`=2,"
				. "`f_salesEntity`=3,"
				. "`f_salesCancellationDate`=" . sqlVON($f_sale['f_salesCancellationDate']) . ","
				. "`f_salesCancellationSumm`=" . sqlVON($f_sale['f_salesCancellationSumm']) . ","
				. "`import`=" . sqlVON($f_sale['import']) . ","
				. "`f_salesGUID`=" . sqlVON($f_sale['f_salesGUID']) . "");
		$new_idf_sales = mysqli_insert_id($link);
		if (!$new_idf_sales) {
			die('Не удалось создать абонемент. ' . mysqli_error($link));
		}

		foreach ($client['f_sales'][$N_f_sales]['f_credits'] as $f_credit) {
			mysqlQuery("INSERT INTO `$TARGETDATABASE`.`f_credits` SET "
					. " `f_creditsBankAgreementNumber` = " . sqlVON($f_credit['f_creditsBankAgreementNumber']) . ","
					. " `f_creditsSumm`=" . sqlVON($f_credit['f_creditsSumm']) . ","
					. " `f_creditsSummIncInterest`=" . sqlVON($f_credit['f_creditsSummIncInterest']) . ","
					. " `f_creditsMonthes`=" . sqlVON($f_credit['f_creditsMonthes']) . ","
					. " `f_creditsSalesID`='" . $new_idf_sales . "'"
			);
		}

		foreach ($client['f_sales'][$N_f_sales]['f_installments'] as $f_installment) {
			mysqlQuery("INSERT INTO `$TARGETDATABASE`.`f_installments` SET "
					. " `f_installmentsSumm` = " . sqlVON($f_installment['f_installmentsSumm']) . ","
					. " `f_installmentsPeriod` = " . sqlVON($f_installment['f_installmentsPeriod']) . ","
					. " `f_installmentsSalesID`='" . $new_idf_sales . "'"
			);
		}

		foreach ($client['f_sales'][$N_f_sales]['f_payments'] as $f_payment) {
			mysqlQuery("INSERT INTO `$TARGETDATABASE`.`f_payments` SET "
					. " `f_paymentsType` = " . sqlVON($f_payment['f_paymentsType']) . ","
					. " `f_paymentsAmount` = " . sqlVON($f_payment['f_paymentsAmount']) . ","
					. " `f_paymentsDate` = " . sqlVON($f_payment['f_paymentsDate']) . ","
					. " `f_paymentsUser` = " . sqlVON($f_payment['f_paymentsUser']) . ","
					. " `f_paymentsComment` = " . sqlVON($f_payment['f_paymentsComment']) . ","
					. " `f_paymentsSalesID`='" . $new_idf_sales . "'"
			);
		}

		foreach ($client['f_sales'][$N_f_sales]['f_subscriptions'] as $f_subscription) {
			mysqlQuery("INSERT INTO `$TARGETDATABASE`.`f_subscriptions` SET "
					. " `f_salesContentService` = " . sqlVON($f_subscription['f_salesContentService']) . ","
					. " `f_salesContentPrice` = " . sqlVON($f_subscription['f_salesContentPrice']) . ","
					. " `f_salesContentQty` = " . sqlVON($f_subscription['f_salesContentQty']) . ","
					. " `f_subscriptionsDate` = " . sqlVON($f_subscription['f_subscriptionsDate']) . ","
					. " `f_subscriptionsContract`='" . $new_idf_sales . "'"
			);
		}
		foreach ($client['f_sales'][$N_f_sales]['servicesApplied'] as $serviceApplied) {
			mysqlQuery("INSERT INTO `$TARGETDATABASE`.`servicesApplied` SET "
					. " `servicesAppliedService` = " . sqlVON($serviceApplied['servicesAppliedService']) . ","
					. " `servicesAppliedQty` = " . sqlVON($serviceApplied['servicesAppliedQty']) . ","
					. " `servicesAppliedClient` = " . $new_idclients . ","
					. " `servicesAppliedDate` = " . sqlVON($serviceApplied['servicesAppliedDate']) . ","
					. " `servicesAppliedAt` = " . sqlVON($serviceApplied['servicesAppliedAt']) . ","
					. " `servicesAppliedTimeBegin` = " . sqlVON($serviceApplied['servicesAppliedTimeBegin']) . ","
					. " `servicesAppliedStarted` = " . sqlVON($serviceApplied['servicesAppliedStarted']) . ","
					. " `servicesAppliedTimeEnd` = " . sqlVON($serviceApplied['servicesAppliedTimeEnd']) . ","
					. " `servicesAppliedFineshed` = " . sqlVON($serviceApplied['servicesAppliedFineshed']) . ","
					. " `servicesAppliedPrice` = " . sqlVON($serviceApplied['servicesAppliedPrice']) . ","
					. " `servicesAppliedContract`='" . $new_idf_sales . "'"
			);
			if ($serviceApplied['servicesAppliedComment'] ?? false) {
				$new_idserviceApplied = mysqli_insert_id($link);
				mysqlQuery("INSERT INTO `$TARGETDATABASE`.`servicesAppliedComments` SET "
						. " `servicesAppliedCommentsSA`='" . $new_idserviceApplied . "',"
						. " `servicesAppliedCommentText` = " . sqlVON($serviceApplied['servicesAppliedComment']) . "");
			}
		}
	}

	foreach ($client['clientsPassports'] as $clientsPassport) {
		mysqlQuery("INSERT INTO `$TARGETDATABASE`.`clientsPassports` SET "
				. " `clientsPassportNumber` = " . sqlVON($clientsPassport['clientsPassportNumber']) . ","
				. " `clientsPassportsResidence` = " . sqlVON($clientsPassport['clientsPassportsResidence']) . ","
				. " `clientsPassportsRegistration` = " . sqlVON($clientsPassport['clientsPassportsRegistration']) . ","
				. " `clientsPassportsDate` = " . sqlVON($clientsPassport['clientsPassportsDate']) . ","
				. " `clientsPassportsBirthPlace` = " . sqlVON($clientsPassport['clientsPassportsBirthPlace']) . ","
				. " `clientsPassportsDepartment` = " . sqlVON($clientsPassport['clientsPassportsDepartment']) . ","
				. " `clientsPassportsAdded` = " . sqlVON($clientsPassport['clientsPassportsAdded']) . ","
				. " `clientsPassportsCode` = " . sqlVON($clientsPassport['clientsPassportsCode']) . ","
				. " `clientsPassportsAddedBy` = " . sqlVON($clientsPassport['clientsPassportsAddedBy']) . ","
				. " `clientsPassportsClient` = " . $new_idclients . ""
				. " ON DUPLICATE KEY UPDATE "
				. " `clientsPassportsResidence` = " . sqlVON($clientsPassport['clientsPassportsResidence']) . ","
				. " `clientsPassportsRegistration` = " . sqlVON($clientsPassport['clientsPassportsRegistration']) . ","
				. " `clientsPassportsDate` = " . sqlVON($clientsPassport['clientsPassportsDate']) . ","
				. " `clientsPassportsBirthPlace` = " . sqlVON($clientsPassport['clientsPassportsBirthPlace']) . ","
				. " `clientsPassportsDepartment` = " . sqlVON($clientsPassport['clientsPassportsDepartment']) . ","
				. " `clientsPassportsAdded` = " . sqlVON($clientsPassport['clientsPassportsAdded']) . ","
				. " `clientsPassportsCode` = " . sqlVON($clientsPassport['clientsPassportsCode']) . ","
				. " `clientsPassportsAddedBy` = " . sqlVON($clientsPassport['clientsPassportsAddedBy']) . ","
				. " `clientsPassportsClient` = " . $new_idclients . ""
				. ""
		);
	}
	foreach ($client['clientsPhones'] as $clientsPhone) {
		mysqlQuery("INSERT INTO `$TARGETDATABASE`.`clientsPhones` SET "
				. " `clientsPhonesPhone` = " . sqlVON($clientsPhone['clientsPhonesPhone']) . ","
				. " `clientsPhonesType` = " . sqlVON($clientsPhone['clientsPhonesType']) . ","
				. " `clientsPhonesClient` = " . $new_idclients . ""
		);
	}

	foreach ($client['servicesApplied'] as $serviceApplied) {
		mysqlQuery("INSERT INTO `$TARGETDATABASE`.`servicesApplied` SET "
				. " `servicesAppliedService` = " . sqlVON($serviceApplied['servicesAppliedService']) . ","
				. " `servicesAppliedQty` = " . sqlVON($serviceApplied['servicesAppliedQty']) . ","
				. " `servicesAppliedClient` = " . $new_idclients . ","
				. " `servicesAppliedDate` = " . sqlVON($serviceApplied['servicesAppliedDate']) . ","
				. " `servicesAppliedAt` = " . sqlVON($serviceApplied['servicesAppliedAt']) . ","
				. " `servicesAppliedTimeBegin` = " . sqlVON($serviceApplied['servicesAppliedTimeBegin']) . ","
				. " `servicesAppliedStarted` = " . sqlVON($serviceApplied['servicesAppliedStarted']) . ","
				. " `servicesAppliedTimeEnd` = " . sqlVON($serviceApplied['servicesAppliedTimeEnd']) . ","
				. " `servicesAppliedFineshed` = " . sqlVON($serviceApplied['servicesAppliedFineshed']) . ","
				. " `servicesAppliedPrice` = " . sqlVON($serviceApplied['servicesAppliedPrice']) . ","
				. " `servicesAppliedContract`= NULL"
		);
		if ($serviceApplied['servicesAppliedComment'] ?? false) {
			$new_idserviceApplied = mysqli_insert_id($link);
			mysqlQuery("INSERT INTO `$TARGETDATABASE`.`servicesAppliedComments` SET "
					. " `servicesAppliedCommentsSA`='" . $new_idserviceApplied . "',"
					. " `servicesAppliedCommentText` = " . sqlVON($serviceApplied['servicesAppliedComment']) . "");
		}
	}
	mysqlQuery("UPDATE `clients` SET `clientsLName` =  CONCAT('ПЕРЕНЕСЕН $TARGETDATABASE ', `clientsLName`) WHERE `idclients`='" . $client['idclients'] . "'");
	header("Location: https://" . $SUBDOMEN . "menua.pro/pages/offlinecall/schedule.php?client=" . $new_idclients);
	die();
}

