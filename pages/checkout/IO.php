<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

function quoteOrNull($var) {
	if ($var === '' || $var === null) {
		return "null";
	} else {
		return "'$var'";
	}
}

if (R(26) && isset($_JSON['action']) && $_JSON['action'] === 'saveReplace') {


	mysqlQuery("DELETE FROM `f_salesReplacementsCoordinator` WHERE `f_salesReplacementsCoordinatorDate` = CURDATE() AND `f_salesReplacementsCoordinatorContract` = " . FSI($_JSON['data']['contract']) . " ");

	foreach ($_JSON['data']['coordinators'] as $coordinator) {
		mysqlQuery("INSERT INTO `f_salesReplacementsCoordinator` SET"
				. " `f_salesReplacementsCoordinatorCurator`='" . $coordinator['id'] . "', "
				. " `f_salesReplacementsCoordinatorDate`= CURDATE(), "
				. " `f_salesReplacementsCoordinatorContract`='" . FSI($_JSON['data']['contract']) . "' "
				. "");
	}
	mysqlQuery("DELETE FROM `f_salesReplacementComments` WHERE `f_salesReplacementCommentsDate` = CURDATE() AND `f_salesReplacementCommentsContract` = " . FSI($_JSON['data']['contract']) . " ");

	mysqlQuery("INSERT INTO `f_salesReplacementComments` SET"
			. " `f_salesReplacementCommentsText`='" . mysqli_real_escape_string($link, $_JSON['data']['comment']) . "', "
			. " `f_salesReplacementCommentsDate`= CURDATE(), "
			. " `f_salesReplacementCommentsContract`='" . FSI($_JSON['data']['contract']) . "' "
			. "");
//print "DELETE `f_subscriptions` FROM "
//			. " `f_subscriptions` "
//			. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
//			. " WHERE `f_subscriptionsContract` = " . FSI($_JSON['data']['contract']) . ""
//			. " AND `f_subscriptionsDate`='" . date("Y-m-d") . "'"
//			. " AND NOT `f_subscriptionsDate`=`f_salesTime`"
//			. ""
//			. "";
	mysqlQuery("DELETE `f_subscriptions` FROM "
			. " `f_subscriptions` "
			. " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
			. " WHERE `f_subscriptionsContract` = " . FSI($_JSON['data']['contract']) . ""
			. " AND `f_subscriptionsDate`='" . date("Y-m-d") . "'"
//			. " AND NOT `f_subscriptionsDate`=`f_salesTime`"
			. ""
			. "");

	foreach (($_JSON['data']['toAppend'] ?? []) as $service) {
		if ($service['idservices'] ?? false) {
			mysqlQuery("INSERT INTO `f_subscriptions` SET "
					. "`f_subscriptionsContract` = " . intval($_JSON['data']['contract']) . ","
					. "`f_salesContentService`=" . intval($service['idservices']) . ","
					. "`f_salesContentPrice`=" . mres(round(($service['f_salesContentPrice'] ?? 0), 2)) . ","
					. "`f_salesContentQty`=" . intval($service['f_salesContentQty'] ?? 0) . ","
					. "`f_subscriptionsDate`='" . date("Y-m-d") . "',"
					. "`f_subscriptionsUser`=" . $_USER['id'] . "");
		}
	}


	foreach (($_JSON['data']['toRemove'] ?? []) as $service) {
		if ($service['f_salesContentQty'] ?? 0) {
			mysqlQuery("INSERT INTO `f_subscriptions` SET "
					. "`f_subscriptionsContract` = " . FSI($_JSON['data']['contract']) . ","
					. "`f_salesContentService`=" . FSI($service['idservices']) . ","
					. "`f_salesContentPrice`=" . mres(round($service['f_salesContentPrice'], 2)) . ","
					. "`f_salesContentQty`= " . FSI($service['f_salesContentQty']) . ","
					. "`f_subscriptionsDate`='" . date("Y-m-d") . "',"
					. "`f_subscriptionsUser`=" . $_USER['id'] . "");
		}
	}



	print json_encode(['success' => true, 'msgs' => [['type' => 'success', 'text' => 'Получилось!']]], 288);
}



if (0 && R(26) && isset($_JSON['action']) && $_JSON['action'] === 'getRemains') {

	function subscriptionsSumm($subscriptions) {
		usort($subscriptions, function ($a, $b) {
			return strtotime($a['f_subscriptionsDate']) <=> strtotime($b['f_subscriptionsDate']);
		});
		$OUT = [];
		foreach ($subscriptions as $subscription3) {
			$found = false;
			foreach ($OUT as &$OUTelem) {
				if (
						$OUTelem['f_salesContentService'] === $subscription3['f_salesContentService'] &&
						$OUTelem['f_salesContentPrice'] === ($subscription3['f_salesContentPrice'])
				) {
					$found = true;
					$OUTelem['f_salesContentQty'] += $subscription3['f_salesContentQty'];
				}
			}
			if (!$found) {
				$OUT[] = $subscription3;
			}
		}
		$filtered = array_filter($OUT, function ($el) {
			return $el['f_salesContentQty'] > 0;
		});
		return $filtered;
	}

	$contract = mfa(mysqlQuery(
					"SELECT * FROM "
					. "`f_sales` "
					. "LEFT JOIN `clients` ON (`idclients`= `f_salesClient`) WHERE `idf_sales` = '" . FSI($_JSON['contract']) . "'"));

	$servicesApplied = query2array(mysqlQuery(""
					. " SELECT * FROM `servicesApplied`"
					. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
					. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
					. " WHERE `servicesAppliedClient` = '" . $contract['idclients'] . "'"
					. " AND isnull(`servicesAppliedDeleted`)"
	));

	$subscriptions = subscriptionsSumm(query2array(mysqlQuery("SELECT "
							. "*,"
							. "`idservices`,"
							. "`idf_subscriptions`,"
							. "`servicesName`,"
							. "`f_salesContentPrice`,"
							. "`f_salesContentQty`"
							. " FROM `f_subscriptions`"
							. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
							. " LEFT JOIN `f_sales` ON (`idf_sales`=`f_subscriptionsContract`)"
							. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
							. " WHERE `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'")));

	foreach ($subscriptions as $subscription) {

		$done = array_sum(array_column(array_filter($servicesApplied, function ($element) {
							global $subscription;
							return (
							$element['servicesAppliedContract'] === $subscription['f_subscriptionsContract'] &&
							$element['servicesAppliedService'] === $subscription['f_salesContentService'] &&
							$element['servicesAppliedPrice'] === $subscription['f_salesContentPrice'] &&
							$element['servicesAppliedFineshed']);
						}), 'servicesAppliedQty'));
		$reserved = array_sum(array_column(array_filter($servicesApplied, function ($element) {
							global $subscription;
							return (
							$element['servicesAppliedContract'] === $subscription['f_subscriptionsContract'] &&
							$element['servicesAppliedService'] === $subscription['f_salesContentService'] &&
							$element['servicesAppliedPrice'] === $subscription['f_salesContentPrice'] &&
							!$element['servicesAppliedFineshed']);
						}), 'servicesAppliedQty'));

//			$remains =

		$subscription['max'] = $subscription['f_salesContentQty'] - $reserved - $done;
		$subscription['remains'] = $subscription['f_salesContentQty'] - $reserved - $done;
		$subscription['reserved'] = $reserved;
		$subscription['done'] = $done;

		$contract2['subscriptions'][] = $subscription;
	}



	usort($contract2['subscriptions'], function ($a, $b) {
		return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
	});
	print json_encode(['remains' => refine($contract2['subscriptions'], ['servicesName', 'max', 'remains', 'idservices', 'f_salesContentPrice'])], 288);
}



if (R(26) && isset($_JSON['action']) && $_JSON['action'] === 'getServices') {
	$services = query2array(mysqlQuery("SELECT "
					. "`idservices` as `idservices`, "
					. "`servicesName` as `name`,"
					. "`servicesTypesName` as `typeName`,"
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `price`,"
					. ""
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`)) as `priceMin`,"
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<=NOW() AND `servicesPricesType`='2' AND `servicesPricesService` = `idservices`)) as `priceMax`"
					. "FROM `services` "
					. "LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`) "
					. "WHERE isnull(`servicesDeleted`)"
					. " AND ((SELECT COUNT(1) FROM `positions2services` WHERE `positions2servicesService`=`idservices`)>0 OR (SELECT COUNT(1) FROM `users2services` WHERE `users2servicesInclude` = `idservices`)>0)"
					. " AND `servicesName` LIKE '%" . mysqli_real_escape_string($link, trim($_JSON['serviceName'])) . "%'"));
	print json_encode(['services' => $services], 288);
}



if (R(26) && isset($_JSON['action']) && $_JSON['action'] === 'saleCancelation') {
//	action: "saleCancelation"
//	date: "2020-07-07"
//	sale: 19685
//	summ: "45000"
//	idf_sales, f_salesNumber, f_salesCreditManager, f_salesClient, f_salesSumm, f_salesComment, f_salesTime, f_salesDate, f_salesType, f_salesCancellationDate, f_salesCancellationSumm, f_salesEntity
	$sale = query2array(mysqlQuery("SELECT * FROM `f_sales`  WHERE `idf_sales` = '" . FSI($_JSON['sale']) . "' AND isnull(`f_salesCancellationDate`) AND isnull(`f_salesCancellationSumm`)"));
	if (count($sale) === 1) {
		if (mysqlQuery("UPDATE `f_sales` SET `f_salesCancellationDate` = '" . $_JSON['date'] . "',  `f_salesCancellationSumm` = '" . mres($_JSON['summ']) . "' WHERE `idf_sales`='" . $sale[0]['idf_sales'] . "'")) {
			print json_encode(['success' => true], 288);
		} else {
			print json_encode(['success' => false, 'error' => mysqli_error($link)], 288);
		}
	} else {
		print json_encode(['success' => false, 'count' => count($sale)], 288);
	}
}


if (R(26) && isset($_JSON['action']) && $_JSON['action'] === 'plusOne') {

	foreach (getUsersByRights([59]) as $user) {
//		if ($user['usersICQ']) {
//			ICQ_messagesSend_SYNC($user['usersICQ'], 'Ждём одобрения');
//		}
		if ($user['usersTG'] ?? false) {
			sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => 'Ждём одобрения']);
		}
	}
}

if (R(26) && isset($_JSON['action']) && $_JSON['action'] === 'coordsSuggestions') {
	if (isset($_JSON['lastname']) && trim($_JSON['lastname']) != '') {
		$lastname = FSS(trim($_JSON['lastname']));
		$result = query2array(mysqlQuery("SELECT "
						. "`idusers` as `id`, "
						. "`usersLastName` as `lname`, "
						. "`usersFirstName` as `fname`,"
						. "`usersMiddleName` as `mname`"
						. ""
						. "FROM `users`"
						. "WHERE `usersLastName` LIKE '%" . $lastname . "%' "
						. "AND isnull(`usersDeleted`)"
						. "AND isnull(`usersFired`)"
						. " LIMIT 20"));

		print json_encode(['coords' => $result], 288);
	} else {
		print json_encode(['coords' => []], 288);
	}
}
if (R(26) && isset($_JSON['action']) && $_JSON['action'] === 'searchClientByLastName') {
	if (isset($_JSON['lastname']) && trim($_JSON['lastname']) != '') {
		$lastname = FSS(trim($_JSON['lastname']));
		$result = query2array(mysqlQuery("SELECT "
						. "`idclients` as `id`, "
						. "`clientsLName` as `lname`, "
						. "`clientsFName` as `fname`, "
						. "`clientsMName` as `mname`, "
						. "`clientsBDay` as `bday`, "
						. "`clientsAKNum` as `aknum`, "
						. "`clientsGender` as `gender`, "
						. "`clientsPassportNumber` AS `passportnumber`, "
						. "`clientsPassportsResidence` AS `residence`, "
						. "`clientsPassportsRegistration` AS `registration`, "
						. "`clientsPassportsBirthPlace` AS `birthplace`, "
						. "`clientsPassportsDepartment` AS `department`, "
						. "`clientsPassportsDate` AS `passportdate`,"
						. "`clientsPassportsCode` AS `passportcode`,"
						. "`clientsPhonesPhone` AS `phone` "
						. "FROM `clients`"
						. "LEFT JOIN `clientsPassports` ON (`idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = `idclients` )) "
						. "LEFT JOIN `clientsPhones` ON (`idclientsPhones` = (SELECT MAX(`idclientsPhones`) FROM `clientsPhones` WHERE `clientsPhonesClient` = `idclients` )) "
						. "WHERE `clientsLName` LIKE '%" . $lastname . "%' "
						. " AND (SELECT COUNT(1) FROM `servicesApplied` WHERE `servicesAppliedClient` = `idclients` AND `servicesAppliedDate`=CURDATE())>0"
						. " ORDER BY `lname`,`fname`,`mname`;"));
		foreach ($result as &$client) {
			$servicesAppliedDone = query2array(mysqlQuery("SELECT *,ifnull(`servicesAppliedPrice`,0) as `servicesAppliedPrice` FROM `servicesApplied` WHERE"
							. " `servicesAppliedClient`='" . $client['id'] . "'"
							. " AND isnull(`servicesAppliedContract`)"
//							. " AND isnull(`servicesAppliedIsFree`)" 
							. " AND NOT ifnull(`servicesAppliedPrice`,0) = 0 " //Исключаем бесплатные процедуры
							. " AND isnull(`servicesAppliedDeleted`)"
							. " AND NOT isnull(`servicesAppliedFineshed`)"
							. " AND NOT `servicesAppliedService` = 362" //исключаем диагностики
							. " AND NOT (`servicesAppliedService` = 361 AND ifnull(`servicesAppliedPrice`,0) = 0 ) "  //исключаем бесплатные консультации
							. ""));
			$client['servicesApplied'] = $servicesAppliedDone;
			$client['salesDraft'] = query2array(mysqlQuery("SELECT *,(SELECT COUNT(1) FROM `f_subscriptionsDraft` WHERE `f_subscriptionsDraftSaleDraft` = `idf_salesDraft`) as f_subscriptionsDraftCount "
							. " FROM `f_salesDraft` "
							. " LEFT JOIN `users` ON (`idusers`=`f_salesDraftAuthor`)"
							. " WHERE `f_salesDraftClient` = " . $client['id'] . ""));
			foreach ($client['salesDraft'] as &$saleDraft) {
				$saleDraft['subscriptionsDraft'] = query2array(mysqlQuery(""
								. " SELECT "
								. " `idservices`,"
								. " `servicesParent`,"
								. " `servicesCode`,"
								. " `servicesName`,"
								. " `serviceNameShort`,"
								. " `servicescolN804`,"
								. " `f_subscriptionsDraftPrice` AS `price`,"
								. " `f_subscriptionsDraftQty` AS `qty`"
								. " FROM `f_subscriptionsDraft` "
								. " LEFT JOIN `services` ON (`idservices` = `f_subscriptionsDraftService`)"
								. " WHERE `f_subscriptionsDraftSaleDraft` = '" . $saleDraft['idf_salesDraft'] . "'"));
			}
		}
		print json_encode(['clients' => $result], 288);
	} else {
		print json_encode(['clients' => []], 288);
	}
}



if (R(26) && isset($_JSON['action']) && $_JSON['action'] === 'saveSale') {
	$NOW = date("Y-m-d H:i:s");

//	ICQ_messagesSend('sashnone', json_encode($_JSON['sale'], 288));
//	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => json_encode($_JSON,288+128)]);
//	print json_encode($_JSON['sale'], 288);
	if (isset($_JSON['sale']['client'])) {
		$client = $_JSON['sale']['client'];
		foreach ($client as $key => &$value2) {
			if (is_array($value2)) {
//				ICQ_messagesSend_SYNC('AoLF0rcsY9MXT89Io2U', 'ERRORO' . $key . ': ' . print_r($value2, true));
			} else {
				$value2 = FSS(trim($value2));
			}
		}
		if ($client['id']) {

//если  нам передали айди клиента, проверяем, не поменялось ли чего у него
			$oldClient = mfa(mysqlQuery("SELECT * FROM `clients`"
							. "LEFT JOIN `clientsPassports` ON (`idclientsPassports` = (SELECT MAX(`idclientsPassports`) FROM `clientsPassports` WHERE `clientsPassportsClient` = `idclients` )) "
							. "LEFT JOIN `clientsPhones` ON (`idclientsPhones` = (SELECT MAX(`idclientsPhones`) FROM `clientsPhones` WHERE `clientsPhonesClient` = `idclients` )) "
							. "WHERE `idclients` = '" . FSI($client['id']) . "'"));

			if (!$oldClient['clientsOldSince']) {// && in_array(intval($_JSON['sale']['type']), [1, 2])
				mysqlQuery("UPDATE `clients` SET `clientsOldSince`='" . ($_JSON['sale']['date'] ?? date("Y-m-d")) . "' WHERE `idclients`='" . $oldClient['idclients'] . "'");
			}
			if ($oldClient['clientsPhonesPhone'] !== $client['phone']) {
				//если сменился номер телефона - добавляем последний
				mysqlQuery("INSERT INTO `clientsPhones` SET "
						. "`clientsPhonesClient`='" . FSI($client['id']) . "', "
						. "`clientsPhonesPhone`='" . FSS($client['phone'] ?? '') . "'");
			}
			if ($oldClient['clientsPassportNumber'] !== $client['passportnumber'] ||
					$oldClient['clientsPassportsResidence'] !== $client['residence'] ||
					$oldClient['clientsPassportsRegistration'] !== $client['registration'] ||
					$oldClient['clientsPassportsDate'] !== $client['passportdate'] ||
					$oldClient['clientsPassportsBirthPlace'] !== $client['birthplace'] ||
					$oldClient['clientsPassportsDepartment'] !== $client['department']) {
				//если поменялись какие-либо паспортные данные - добавляем новый паспорт
				$passportInsertSQL = [];
				$passportInsertSQL[] = "`clientsPassportsAdded`='" . date("Y-m-d") . "'";
				$passportInsertSQL[] = "`clientsPassportsAddedBy`='" . $_USER['id'] . "'";
				if (FSI($client['id'])) {
					$passportInsertSQL[] = "`clientsPassportsClient`='" . FSI($client['id']) . "'";
				}
				if (FSS($client['passportnumber'])) {
					$passportInsertSQL[] = "`clientsPassportNumber`='" . FSS($client['passportnumber']) . "'";
				}

				if (FSS($client['residence'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsResidence`='" . FSS($client['residence'] ?? '') . "'";
				}
				if (FSS($client['registration'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsRegistration`='" . FSS($client['registration'] ?? '') . "'";
				}
				if (FSS($client['passportdate'])) {
					$passportInsertSQL[] = "`clientsPassportsDate`='" . $client['passportdate'] . "'";
				}
				if (FSS($client['birthplace'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsBirthPlace`='" . FSS($client['birthplace'] ?? '') . "'";
				}
				if (FSS($client['department'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsDepartment`='" . FSS($client['department'] ?? '') . "'";
				}


				mysqlQuery("INSERT INTO `clientsPassports` SET "
						. implode(",", $passportInsertSQL)
						. ""
						. "ON DUPLICATE KEY UPDATE "
						. implode(",", $passportInsertSQL)
						. "");
			}
			if ($oldClient['clientsFName'] !== $client['fname'] ||
					$oldClient['clientsMName'] !== $client['mname'] ||
					$oldClient['clientsLName'] !== $client['lname'] ||
					$oldClient['clientsBDay'] !== $client['bday'] ||
					$oldClient['clientsAKNum'] !== $client['aknum'] ||
					$oldClient['clientsGender'] !== $client['gender']) {
//если поменялись какие-то данные клиента, обновляем их.
				$genderSQL = "null";
				if ($client['gender'] === '1') {
					$genderSQL = "1";
				}
				if ($client['gender'] === '0') {
					$genderSQL = "0";
				}

				mysqlQuery("UPDATE `clients` SET "
						. "`clientsFName` = '" . FSS($client['fname'] ?? '') . "',"
						. "`clientsMName` = '" . FSS($client['mname'] ?? '') . "',"
						. "`clientsLName` = '" . FSS($client['lname'] ?? '') . "',"
						. ($client['bday'] !== '' ? ("`clientsBDay` = '" . FSS($client['bday'] ?? '') . "',") : '')
						. "`clientsAKNum` = " . quoteOrNull($client['aknum']) . ","
						. "`clientsGender` = $genderSQL"
						. " WHERE `idclients` = '" . FSI($client['id'] ?? '') . "'");
			}
//			print mysqli_error($link);
			$idclients = $oldClient['idclients'];
//			print json_encode([$oldClient, $client], 288);
			//update actions
			//idclientsPassports, clientsPassportsClient, clientsPassportNumber, clientsPassportsResidence, clientsPassportsRegistration, clientsPassportsDate, clientsPassportsBirthPlace, clientsPassportsDepartment
			//idclients, clientsFName, clientsMName, clientsLName, clientsBDay, clientsAKNum, clientsAddedBy, clientsAddedAt, clientsGender
		} else {

			$genderSQL = "null";
			if ($client['gender'] === '1') {
				$genderSQL = "1";
			}
			if ($client['gender'] === '0') {
				$genderSQL = "0";
			}

			//добавляем нового клиента


			if (mysqlQuery("INSERT INTO `clients` SET "
							. "`clientsFName` = " . quoteOrNull($client['fname'] ?? '') . ","
							. "`clientsMName` = " . quoteOrNull($client['mname'] ?? '') . ","
							. "`clientsLName` = " . quoteOrNull($client['lname'] ?? '') . ","
							. "`clientsBDay` = " . quoteOrNull($client['bday'] ?? '') . ","
							. "`clientsAKNum` = " . quoteOrNull($client['aknum']) . ","
							. "`clientsAddedBy` = " . $_USER['id'] . ", "
							. "`clientsGender` = $genderSQL"
					)) {
				$idclients = mysqli_insert_id($link);
				//Добавляем паспорт


				$passportInsertSQL = [];

				if ($idclients) {
					$passportInsertSQL[] = "`clientsPassportsClient`='" . $idclients . "'";
				}
				if (FSS($client['passportnumber'])) {
					$passportInsertSQL[] = "`clientsPassportNumber`='" . FSS($client['passportnumber']) . "'";
				}

				if (FSS($client['residence'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsResidence`='" . FSS($client['residence'] ?? '') . "'";
				}
				if (FSS($client['registration'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsRegistration`='" . FSS($client['registration'] ?? '') . "'";
				}
				if (FSS($client['passportdate'])) {
					$passportInsertSQL[] = "`clientsPassportsDate`='" . $client['passportdate'] . "'";
				}
				if (FSS($client['birthplace'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsBirthPlace`='" . FSS($client['birthplace'] ?? '') . "'";
				}
				if (FSS($client['department'] ?? '')) {
					$passportInsertSQL[] = "`clientsPassportsDepartment`='" . FSS($client['department'] ?? '') . "'";
				}




				if (mysqlQuery("INSERT INTO `clientsPassports` SET "
								. implode(",", $passportInsertSQL)
								. ""
								. "ON DUPLICATE KEY UPDATE "
								. implode(",", $passportInsertSQL)
								. "")) {
					//Добавляем телефон
					if (isset($client['phone'])) {
						if (!mysqlQuery("INSERT INTO `clientsPhones` SET "
										. "`clientsPhonesClient`='" . $idclients . "', "
										. "`clientsPhonesPhone`='" . FSS($client['phone'] ?? '') . "'")) {
							die(json_encode(['msgs' => ['Ошибка при добавлении телефона<br>' . FSS(mysqli_error($link))]], 288));
						}
					}
				} else {
					die(json_encode(['msgs' => ['Ошибка при добавлении паспорта<br>' . FSS(mysqli_error($link))]], 288));
				}
			} else {
				die(json_encode(['msgs' => ['Ошибка при добавлении клиента<br>' . FSS(mysqli_error($link))]], 288));
			}
		}

		//добавляем продажу

		mysqlQuery("INSERT INTO `f_sales` SET "
				. "`f_salesNumber` = (SELECT * FROM (SELECT IF(isnull((SELECT MAX(f_salesNumber) FROM `f_sales` WHERE `f_salesClient`=$idclients AND `f_salesEntity`='" . intval($_JSON['sale']['entity']) . "' AND NOT isnull(`f_salesIsAppendix`))),2,(SELECT MAX(f_salesNumber) FROM `f_sales` WHERE `f_salesClient`=$idclients AND NOT isnull(`f_salesIsAppendix`))+1)) as `tmp`),"
				. "`f_salesCreditManager` = " . $_USER['id'] . ","
				. "`f_salesClient` = " . $idclients . ","
				. "`f_salesType` = '" . intval($_JSON['sale']['type']) . "',"
				. "`f_salesEntity` = '" . intval($_JSON['sale']['entity']) . "',"
				. "`f_salesSumm` = '" . intval($_JSON['sale']['payment']['summ']) . "',"
				. "`f_salesDate` = '" . ($_JSON['sale']['date'] ?? date("Y-m-d")) . "',"
				. "`f_salesIsAppendix` = '1',"
				. "`f_salesTime` = '$NOW'"
		);

		$idf_sales = mysqli_insert_id($link);

//		mysqlQuery("UPDATE `clients` SET `clientsIsNew` = null WHERE `idclients` = '" . $idclients . "'");
//		добавляем состав абонемента
		$evotorpositions = [];
		if (isset($_JSON['sale']['subscriptions']) && count($_JSON['sale']['subscriptions'])) {
			foreach ($_JSON['sale']['subscriptions'] as $service) {
				mysqlQuery("INSERT INTO `f_subscriptions` SET "
						. "	`f_subscriptionsContract`=$idf_sales,"
						. " `f_subscriptionsUser`=" . $_USER['id'] . ","
						. " `f_salesContentService`=" . $service['service']['id'] . ","
						. " `f_salesContentPrice`=" . mysqli_real_escape_string($link, ($service['service']['price'] ?? 0)) . ","
						. (validateDate($service['service']['expDate'] ?? '') ? " `f_subscriptionsExpDate`='" . validateDate($service['service']['expDate']) . "'," : '')
						. " `f_salesContentQty`=" . $service['qty'] . " ");
				if ($service['idservicesApplied'] ?? false) {
					$idf_subscriptions = mysqli_insert_id($link);
					$querySQL = "UPDATE `servicesApplied` SET "
							. " `servicesAppliedContract`='" . $idf_sales . "',"
							. " `servicesAppliedPrice`='" . intval($service['service']['price']) . "'"
							. " WHERE `idservicesApplied` = '" . $service['idservicesApplied'] . "'";
					mysqlQuery($querySQL);
//					ICQ_messagesSend_SYNC('sashnone', $querySQL);
				}

				$serviceDB = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($service['service']['id']) . "'"));

				$evotorpositions[] = [
					"code" => $serviceDB['idservices'],
					"name" => $serviceDB['serviceNameShort'] ?? $serviceDB['servicesName'] ?? 'НЕИЗВЕСТНАЯ ПОЗИЦИЯ',
					"productType" => "NORMAL",
					"price" => ($service['service']['price'] ?? 0),
					"quantity" => $service['qty'],
					"priceWithDiscount" => ($service['service']['price'] ?? 0),
					"vat" => [null => 'NO_VAT', '0' => 'NO_VAT', '20' => 'VAT_18'][$serviceDB['servicesVat']]
				];
			}
		}

//		Добавляем координаторов
		if (isset($_JSON['sale']['coordinators']) && count($_JSON['sale']['coordinators'])) {
			$_SESSION['coords'] = $_JSON['sale']['coordinators'];
			foreach ($_JSON['sale']['coordinators'] as $user) {
				mysqlQuery("INSERT INTO `f_salesToCoord` SET "
						. "	`f_salesToCoordSalesID`=$idf_sales,"
						. " `f_salesToCoordCoord`=" . $user['id'] . ""
				);
			}
		} else {
			$_SESSION['coords'] = [];
		}

//		Добавляем участников сделки
		$participantsStr = 'Без участников';
		if (isset($_JSON['sale']['participants']) && count($_JSON['sale']['participants'])) {

			$participantsStr = implode(" / ", array_column($_JSON['sale']['participants'], 'lname'));
			foreach ($_JSON['sale']['participants'] as $user) {
				mysqlQuery("INSERT INTO `f_salesToPersonal` SET "
						. "	`f_salesToPersonalSalesID`=$idf_sales,"
						. " `f_salesToPersonalUser`=" . $user['id'] . ""
				);
			}
		}
//		Заносим информацию по кредиту, если она есть.
//		idf_credits, f_f_creditsBankAgreementNumber, f_creditsSummIncInterest, f_creditsMonthes, f_creditsSalesID, f_creditsBankID
//		SELECT * FROM warehouse.f_credits;
		if (
				isset($_JSON['sale']['payment']['bank']) &&
				count($_JSON['sale']['payment']['bank']) &&
				isset($_JSON['sale']['payment']['bank']['id']) &&
				isset($_JSON['sale']['payment']['bank']['agreementnumber']) &&
				isset($_JSON['sale']['payment']['bank']['summ']) &&
//				isset($_JSON['sale']['payment']['bank']['summincinterest']) &&
				isset($_JSON['sale']['payment']['bank']['period'])
		) {
//			ICQ_messagesSend('sashnone', "Надо оформить кредит");
			$bankErrors = [];
			if (empty($_JSON['sale']['payment']['bank']['id'])) {
				$bankErrors[] = 'Не указан банк';
			}
			if (empty($_JSON['sale']['payment']['bank']['agreementnumber'])) {
				$bankErrors[] = 'Не указан номер договора';
			}

			if (empty($_JSON['sale']['payment']['bank']['summ'])) {
				$bankErrors[] = 'Не указана сумма по банку';
			}
//			if (empty($_JSON['sale']['payment']['bank']['summincinterest'])) {
//				$bankErrors[] = 'Не указана сумма с учётом процента по банку';
//			}
			if (empty($_JSON['sale']['payment']['bank']['period'])) {
				$bankErrors[] = 'Не указан срок кредитования';
			}

			if (!count($bankErrors)) {
				mysqlQuery("INSERT INTO `f_credits` SET "
						. "`f_creditsBankID` = '" . FSI($_JSON['sale']['payment']['bank']['id']) . "',"
						. "`f_creditsBankAgreementNumber` = '" . FSS($_JSON['sale']['payment']['bank']['agreementnumber']) . "',"
//						. " `f_creditsSummIncInterest` = '" . FSS($_JSON['sale']['payment']['bank']['summincinterest']) . "', "
						. " `f_creditsSumm` = '" . FSI($_JSON['sale']['payment']['bank']['summ']) . "', "
						. "`f_creditsMonthes` = '" . FSS($_JSON['sale']['payment']['bank']['period']) . "', "
						. "`f_creditsSalesID` = " . $idf_sales . ""
						. "");
				if (mysqli_error($link)) {
//					MSDelay(0, 'sashnone', "Ошибка внесения кредита: " . mysqli_error($link));
					if ($_USER['icq'] ?? false) {
//						ICQMSDelay(0, $_USER['icq'], "Ошибка внесения кредита: " . mysqli_error($link));
					}
				}
			} else {

//				ICQMSDelay(0, 'sashnone', "Ошибка внесения кредита: " . implode("\r\n", $bankErrors));
				if ($_USER['icq'] ?? false) {
//					ICQMSDelay(0, $_USER['icq'], "Ошибка внесения кредита: " . implode("\r\n", $bankErrors));
				}
			}
		} else {
//			ICQMSDelay(0, 'sashnone', "Неполные данные по кредиту: " . print_r($_JSON['sale']['payment']['bank'], true));
			if ($_USER['icq'] ?? false) {
//				ICQMSDelay(0, $_USER['icq'], "Неполные данные по кредиту: " . print_r($_JSON['sale']['payment']['bank'], true));
			}
		}

//		Добавляем первоначальный взнос  если он есть
//		f_payments
//		idf_payments, f_paymentsSalesID, f_paymentsType, f_paymentsAmount
		$prePay = 0;
		if ($_JSON['sale']['payment']['instant']['cash'] ?? 0) {
			mysqlQuery("INSERT INTO `f_payments` SET  "
					. "`f_paymentsSalesID` = " . $idf_sales . ", "
					. "`f_paymentsType` = '1', "
					. (($_JSON['sale']['date'] && $_JSON['sale']['date'] !== date("Y-m-d")) ? ("`f_paymentsDate` = '" . $_JSON['sale']['date'] . " 04:00:00', ") : ("`f_paymentsDate` = '" . $NOW . "', "))
					. "`f_paymentsUser` = '" . $_USER['id'] . "',"
					. "`f_paymentsClient` = $idclients, "
					. "`f_paymentsAmount`='" . $_JSON['sale']['payment']['instant']['cash'] . "'");
			$prePay += $_JSON['sale']['payment']['instant']['cash'];

			///////////////////// МЕСТО ДЛЯ СКИДЫВАНИЯ ЗАКАЗА В КАССУ
		}

		if ($_JSON['sale']['payment']['instant']['bankcard'] ?? 0) {
			mysqlQuery("INSERT INTO `f_payments` SET  "
					. "`f_paymentsSalesID` = " . $idf_sales . ", "
					. "`f_paymentsType` = '2', "
					. (($_JSON['sale']['date'] && $_JSON['sale']['date'] !== date("Y-m-d")) ? ("`f_paymentsDate` = '" . $_JSON['sale']['date'] . " 04:00:00', ") : ("`f_paymentsDate` = '" . $NOW . "',"))
					. "`f_paymentsUser` = '" . $_USER['id'] . "', "
					. "`f_paymentsClient` = $idclients, "
					. "`f_paymentsAmount`='" . $_JSON['sale']['payment']['instant']['bankcard'] . "'");
			$prePay += $_JSON['sale']['payment']['instant']['bankcard'];
			///////////////////// МЕСТО ДЛЯ СКИДЫВАНИЯ ЗАКАЗА В КАССУ
		}

		if (($_JSON['sale']['payment']['instant']['cash'] ?? 0) || ($_JSON['sale']['payment']['instant']['bankcard'] ?? 0)) {
			if (DBNAME == 'warehouse') {
				$url = 'https://dclubs.ru/evotor/orders/api/3rdparty/v2/order/20181116-2246-4087-801D-290AF8ABEF38';
				$dataToSend = [
					"type" => "SELL",
					"number" => "$idf_sales",
					"period" => time(),
					"state" => "new",
					"client" => $client['lname'] . ' ' . $client['fname'] . ' ' . $client['mname'],
					"id" => "$idf_sales",
					"positions" => $evotorpositions
				];

				if (1) {
					$ch = curl_init($url);
					$payload = json_encode($dataToSend);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . EVOTORBearer]);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$result = curl_exec($ch);
					curl_close($ch);
				}
			}
			if (DBNAME == 'vita') {
				$url = 'https://dclubs.ru/evotor/orders/api/3rdparty/v2/order/20190926-5D13-40C8-805D-C4D93C26DA85';
				$dataToSend = [
					"type" => "SELL",
					"number" => "$idf_sales",
					"period" => time(),
					"state" => "new",
					"client" => $client['lname'] . ' ' . $client['fname'] . ' ' . $client['mname'],
					"id" => "$idf_sales",
					"positions" => $evotorpositions
				];

				if (1) {
					$ch = curl_init($url);
					$payload = json_encode($dataToSend);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . EVOTORBearer]);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$result = curl_exec($ch);
					curl_close($ch);
				}
			}
		}



		if (1) {
			$url = 'https://api.calltouch.ru/lead-service/v1/api/client-order/create';

			$tags = [];

			$saPersonnel = query2array(mysqlQuery("SELECT * "
							. " FROM `servicesApplied`"
							. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
							. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
							. " WHERE `servicesAppliedDate` = CURDATE() "
							. " AND `servicesAppliedClient` = '$idclients'"
							. " AND isnull(`servicesAppliedDeleted`) "
							. " AND (isnull(`servicesAppliedContract`) OR `servicesAppliedContract`='" . $idf_sales . "')"
							. " AND NOT isnull(`servicesAppliedFineshed`)"
							. ";"));
			foreach ($saPersonnel as $sa) {
				if ($sa['usersLastName'] && !in_array($sa['usersLastName'] . ' ' . $sa['usersFirstName'], array_column($tags, 'tag'))) {
					$tags[] = ['tag' => $sa['usersLastName'] . ' ' . $sa['usersFirstName']];
				}
				if ($sa['servicesName'] && !in_array(($sa['serviceNameShort'] ?? $sa['servicesName']), array_column($tags, 'tag'))) {
					$tags[] = ['tag' => ($sa['serviceNameShort'] ?? $sa['servicesName'])];
				}
			}

			$dataToSend = [
				"crm" => "menua",
				"orders" => [
					[
						"matching" => [
							[
								"type" => "callContact",
								"callContactParams" => [
									"phones" => array_column(query2array(mysqlQuery("SELECT `clientsPhonesPhone` FROM `clientsPhones` WHERE `clientsPhonesClient`='" . $idclients . "' AND isnull(`clientsPhonesDeleted`)")), 'clientsPhonesPhone'),
									"date" => date("d-m-Y H:i:s"),
									"callTypeToMatch" => "nearest",
									"searchDepth" => 12000
								]
							]
						],
						"orderNumber" => SMSNAME . '.' . $idf_sales,
						"status" => "Абонемент",
						"statusDate" => date("d-m-Y H:i:s"),
						"orderDate" => date("d-m-Y H:i:s"),
						"revenue" => $_JSON['sale']['payment']['summ'],
						"manager" => SMSNAME,
						"comment" => [
							"text" => "https://" . SUBDOMEN . "menua.pro/pages/checkout/payments.php?client=" . $idclients . "&contract=" . $idf_sales
						],
						"addTags" => $tags
					]
				]
			];
			if (!count($tags)) {
				unset($dataToSend['orders']['0']['addTags']);
			}
			if (1) {
				$ch = curl_init($url);
				$payload = json_encode($dataToSend);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
				curl_setopt($ch, CURLOPT_HTTPHEADER, [
					'Content-Type: application/json',
					'Access-Token: qq6qtZvSv9r9zhsOte2iRLHPG4lNMIoeMqMf3erDAa/AZ',
					'SiteId: 43769']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
//				sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => '📞' . $result]);
				curl_close($ch);
				$result = json_decode($result, 1);
				if ($result['data']['orders'][0]['calltouchOrderId'] ?? false) {
					foreach (getUsersByRights([167]) as $user) {
						if ($user['usersTG'] ?? false) {
							sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => '📞' . ' Добавлена продажа и привязана к Calltouch' . "\n" . 'https://my.calltouch.ru/accounts/29140/sites/43769/reports/deals-journal?dealId=' . $result['data']['orders'][0]['calltouchOrderId']]);
						}
					}
				}
			}
		}





//		Добавляем информацию о внутренней рассрочке
		//idf_installments, f_installmentsSalesID, f_installmentsSumm, f_installmentsPeriod
		if (($_JSON['sale']['payment']['installment']['summ'] ?? 0) && $_JSON['sale']['payment']['installment']['summ'] !== '' && ($_JSON['sale']['payment']['installment']['period'] ?? 0)) {
			if (!mysqlQuery("INSERT INTO `f_installments` SET "
							. "`f_installmentsSalesID` = " . $idf_sales . ", "
							. "`f_installmentsPeriod` = " . FSI($_JSON['sale']['payment']['installment']['period']) . ", "
							. "`f_installmentsSumm`='" . FSI($_JSON['sale']['payment']['installment']['summ']) . "'")) {
				ICQ_messagesSend('sashnone', mysqli_error($link));
			}
		}

		if (true) {
			$todaySales = query2array(mysqlQuery("SELECT * FROM "
							. " `f_sales`"
							. " LEFT JOIN `f_installments` ON (`f_installmentsSalesID` = `idf_sales`)"
							. " LEFT JOIN `f_credits` ON (`f_creditsSalesID` = `idf_sales`)"
							. " WHERE "
							. "  `f_salesSumm`>='25000' "
							. " AND `f_salesDate` = '" . ($_JSON['sale']['date'] ?? date("Y-m-d")) . "';")); //CURDATE();
			foreach ($todaySales as &$todaySale2) {
				$todaySale2['payments'] = query2array(mysqlQuery("SELECT * FROM `f_payments` WHERE `f_paymentsSalesID` = '" . $todaySale2['idf_sales'] . "'"));
			}
			$total = 0;
			$total2 = 0;
			$AEs = 0;
			foreach ($todaySales as $todaySale) {
				if ($todaySale['f_salesSumm']) {
					$AEs += getAE($todaySale['f_salesSumm'], date("Y-m-d"));
				}


				$total += array_sum(array_column($todaySale['payments'], 'f_paymentsAmount'));
				if ($todaySale['f_installmentsSumm']) {
					
				}

				if ($todaySale['f_creditsSumm']) {
					$total += $todaySale['f_creditsSumm'];
				}
//				else {
//					$total += $todaySale['f_salesSumm'];
//				}
				$total2 += $todaySale['f_salesSumm'];
			}

			$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`='" . $idclients . "'"));

			$saleTextShort = (intval($_JSON['sale']['type']) == 1 ? urldecode("1%EF%B8%8F%E2%83%A3") : '') . '🏆 ' . $participantsStr . " (" . $_JSON['sale']['payment']['summ'] . " руб.)\r\nИтого: " . $AEs;

			if ($AEs >= 25) {
				$saleTextShort .= ' ' . urldecode("%F0%9F%A4%AA"); // o_0
			} elseif ($AEs >= 22) {
				$saleTextShort .= ' ' . urldecode("%F0%9F%8D%96"); //🍖 шашлык
			} elseif ($AEs >= 20) {
				$saleTextShort .= ' ' . urldecode("%F0%9F%A5%9F") . '/' . urldecode("%F0%9F%8D%A3") . '/' . urldecode("%F0%9F%8D%94") . '???'; //Хинкали/суши/бургер
			} elseif ($AEs >= 18) {
				$saleTextShort .= ' ' . urldecode("%F0%9F%8D%95"); //🍕 Пицца
			} elseif ($AEs >= 16) {
				$saleTextShort .= ' 😍';
			} elseif ($AEs >= 12) {
				$saleTextShort .= ' 😄';
			} elseif ($AEs >= 10) {
				$saleTextShort .= ' 😃';
			} elseif ($AEs >= 8) {
				$saleTextShort .= ' 🙂';
			} elseif ($AEs >= 5) {
				$saleTextShort .= ' ✅'; //😐
			} else {
				$saleTextShort .= ' ✅'; //' ✅';
			}

			$saleText = ($client['clientsLName'] . ' ')
					. ($client['clientsFName'] ? (mb_substr($client['clientsFName'], 0, 1) . '. ') : '')
					. ($client['clientsMName'] ? (mb_substr($client['clientsMName'], 0, 1) . '. ') : '')
					. ' ' . (number_format(($_JSON['sale']['payment']['summ'] ?? '0'), 0, '.', ' ')) . 'р. ' . "\r\n"
					. $participantsStr . "\r\n"
					. (
					($_JSON['sale']['payment']['installment']['summ'] ?? 0) ?
					(("ПВ: " . number_format(($prePay ?? 0), 0, '.', ' ') . "р." . (FSI($_JSON['sale']['payment']['bank']['summ'] ?? 0) ? ('+ Банк' . number_format(FSI($_JSON['sale']['payment']['bank']['summ'] ?? 0), 0, '.', ' ') . "р.") : '') . "\r\n")) : '')
					. (intval($_JSON['sale']['type']) == 1 ? ('Первичка' . "\r\n") : '')
					. (intval($_JSON['sale']['type']) == 2 ? ('Вторичка' . "\r\n") : '')
					. (intval($_JSON['sale']['type']) == 3 ? ('on-Line' . "\r\n") : '')
					. "Итог: " . number_format($total, 0, '.', ' ') . "р. (" . number_format($total2, 0, '.', ' ') . "р.)\r\n"
					. "За " . ((($_JSON['sale']['date'] ?? date("Y-m-d")) == date("Y-m-d") ? 'сегодня' : $_JSON['sale']['date'])) . " это " . (($_JSON['sale']['payment']['summ'] ?? 0) < 25000 ? 'НЕ ' : '') . ((count($todaySales) > 10 && ($_JSON['sale']['payment']['summ'] ?? 0) >= 25000) ? 'уже ' : '') . ((($_JSON['sale']['payment']['summ'] ?? 0) >= 25000) ? (count($todaySales) . "-я " ) : '') . "продажа.\r\n"
					. 'Оформила: ' . $_USER['lname'] . ' ' . $_USER['fname'];

			foreach (getUsersByRights([57]) as $user) {
				if ($user['usersICQ']) {
					ICQ_messagesSend_SYNC($user['usersICQ'], $saleText);
				}
				if ($user['usersTG'] ?? false) {
					sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => $saleText]);
				}
			}




			if ($_JSON['sale']['payment']['summ'] >= 25000 && ($_JSON['sale']['date'] ?? date("Y-m-d")) == date("Y-m-d")) {
				foreach (getUsersByRights([58]) as $user) {
					if ($user['usersICQ']) {
//						ICQMSDelay(5000, $user['usersICQ'], $saleTextShort);
						ICQ_messagesSend_SYNC($user['usersICQ'], $saleTextShort);
					}
					if ($user['usersTG'] ?? false) {
						sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => $saleTextShort]);
					}
				}
			}



			print(json_encode(['success' => true, 'idfsale' => ($idf_sales ?? null), 'idclients' => ($idclients ?? null)], 288));
		}
	}
	sendVisitsSales();
}

