<?php

//R(172) - звонилка
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

//Попробуем структурировать всё.
//Есть два сценария, попробуем их разделить.
//Сценарий первый - "клиент" из телефонной базы
// Есть два варианта что делать. Если клиент записывается или ему надо перезвонить - то надо перевести клиента из "телефонного номера" в клиента

$databases = [
	'1' => 'warehouse',
//	'2' => 'vita'
];

if ((($_JSON['action'] ?? '') === 'saveCall') && ($_JSON['database'] ?? false)) {
	$database = $databases[$_JSON['database']];

	if (!($_JSON['client']['idclients'] ?? false)) {//Первичка
		$OUT['msgs'][] = 'Первичка';
		if (in_array(($_JSON['call']['result'] ?? ''), ['4', '5'])) {///если клиент записывается или ему надо перезвонить, статусы звонков будут: 4,5
			$OUT['msgs'][] = 'Статус 4 или 5';
			//значит надо клиента внести в базу данных
			if (($_JSON['client']['clientsPhonesPhone'] ?? false) && (($_JSON['client']['clientsLName'] ?? false) || ($_JSON['client']['clientsFName'] ?? false))) {
				$OUT['msgs'][] = 'Проверим на наличие телефон';
				if (query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesPhone`='" . mres($_JSON['client']['clientsPhonesPhone']) . "' AND isnull(`clientsPhonesDeleted`)"))) {
					die(json_encode(['success' => false, 'error' => 'Найден дубликат номера телефона'], 288));
				}
				$OUT['msgs'][] = 'сохраняем клиента во вторичку';
//добавляем клиента только в том случае если есть поле телефон и (Фамилия или Имя)
				mysqlQuery("INSERT INTO `$database`.`clients` SET "
						. " `clientsLName` = '" . mres(mb_ucfirst(trim($_JSON['client']['clientsLName'] ?? ''))) . "', "
						. " `clientsFName` = '" . mres(mb_ucfirst(trim($_JSON['client']['clientsFName'] ?? ''))) . "', "
						. " `clientsMName` = '" . mres(mb_ucfirst(trim($_JSON['client']['clientsMName'] ?? ''))) . "', "
						. " `clientsBDay` = " . sqlVON($_JSON['client']['clientsBDay'] ?? null) . ", "
						. " `clientsAddedBy`='" . $_USER['id'] . "',"
						. " `clientsDatabase`=" . sqlVON($_JSON['RCC_phoneDatabase']) . ","
						. " `clientsSource` = " . sqlVON($_JSON['client']['clientsSource'] ?? null) . ""
						. ";");
				$client = mfa(mysqlQuery("SELECT * FROM `$database`.`clients` WHERE `idclients`='" . mysqli_insert_id($link) . "'"));
				if (!$client) {
					telegramSendByRights([112], 'Ошибка добавления клиента маркетингом');
					die('Ошибка добавления клиента');
				} else {
					$OUT['msgs'][] = 'Добавили';
				}

				//добавляем телефонный номер

				mysqlQuery("INSERT INTO `$database`.`clientsPhones` SET `clientsPhonesClient` = '" . $client['idclients'] . "', `clientsPhonesPhone`='" . mres($_JSON['client']['clientsPhonesPhone']) . "'");
				$clientPhone = mfa(mysqlQuery("SELECT * FROM `$database`.`clientsPhones` WHERE `idclientsPhones` = '" . mysqli_insert_id($link) . "'"));
				if (!$clientPhone) {
					die('Ошибка добавления телефонного номера');
				} else {
					$OUT['msgs'][] = 'Телефонный номер занесли в базу данных вторички';
					$OUT['msgs'][] = $clientPhone;
				}
			}/// Если есть телефон и фио
		}// если статус записать/перезвонить
		else {// если статус отличается от и это не клиент (есессно) записать/перезвонить
			$OUT['msgs'][] = 'Статус не 4 и не 5';
			if (
					($_JSON['client']['idRCC_phones'] ?? false) &&
					($_JSON['call']['result'] ?? false) &&
					($_JSON['call']['VOIP'] ?? false)
			) {//если звонок по базе, должен быть айдишник базы. помечаем номер по нему.
//			idRCC_calls, RCC_callsPhone, RCC_callsType, RCC_callsTime, RCC_callsUser, RCC_calls VOIP
				$OUT['msgs'][] = 'Сохраняем звонок по первичке';

				mysqlQuery("INSERT INTO `$database`.`RCC_calls` SET "
						. " `RCC_callsPhone` = '" . mres($_JSON['client']['idRCC_phones']) . "',"
						. " `RCC_callsType` = '" . mres($_JSON['call']['result']) . "',"
						. " `RCC_callsVOIP` = '" . mres($_JSON['call']['VOIP']) . "',"
						. " `RCC_callsUser` = '" . $_USER['id'] . "'");

				if (trim($_JSON['call']['comment'] ?? '')) {
					$OUT['msgs'][] = 'Есть комментарий, сохраняем';
					mysqlQuery("INSERT INTO `$database`.`RCC_callsComments` SET `RCC_callsCommentsCall` = '" . mysqli_insert_id($link) . "', `RCC_callsCommentsComment` = '" . mres(trim($_JSON['call']['comment'])) . "'");
				}
			}
			exit(json_encode(['success' => true], 288));
		}
	} else {//вторичка (условно)
		$OUT['msgs'][] = 'Вторичка';
		$client = mfa(mysqlQuery("SELECT * FROM `$database`.`clients` WHERE `idclients` = '" . mres($_JSON['client']['idclients']) . "'"));
	}



	// На этот момент у нас долежн быть клиент обязательно!
	if (!($client ?? false)) {
		die(json_encode(['success' => false, 'error' => 'Загадочным образом отсуствует клиент'], 288));
	}
	$OUT['msgs'][] = 'Клиент норм, идём дальше';

	//Сохраним звонок во вторичку, для этого нам нужен айди телефона. Он может быть либо только что создан, либо передан с параметрами
	//Посмотрим.
	if ((($_JSON['client']['idclientsPhones'] ?? "") !== '' || ($clientPhone['idclientsPhones'] ?? false))) {
		$OUT['msgs'][] = 'Звонок во вторичку, сохраняем';
		if ($_JSON['call']['idOCC_calls'] ?? false) {//Редактируем звонок
			if (!mysqlQuery("UPDATE `$database`.`OCC_calls` SET "
							. " `OCC_callsPhone` = '" . mres($clientPhone['idclientsPhones'] ?? $_JSON['client']['idclientsPhones']) . "', "
							. " `OCC_callsType` = '" . mres($_JSON['call']['result']) . "',"
							. " `OCC_callsTime` = NOW(),"
							. " `OCC_callsUser` = '" . $_USER['id'] . "',"
							. " `OCC_callsClient` = '" . $client['idclients'] . "'"
							. " WHERE `idOCC_calls` = '" . mres($_JSON['call']['idOCC_calls']) . "'")) {
				$OUT['msgs'][] = 'Ошибка записи звонка';
			}
			$OCC_call = mfa(mysqlQuery("SELECT * FROM `$database`.`OCC_calls` WHERE `idOCC_calls`='" . mres($_JSON['call']['idOCC_calls']) . "'"));
		} else {//новый звонок
			if (!mysqlQuery("INSERT INTO `$database`.`OCC_calls` SET "
							. " `OCC_callsPhone` = '" . mres($clientPhone['idclientsPhones'] ?? $_JSON['client']['idclientsPhones']) . "', "
							. " `OCC_callsType` = '" . mres($_JSON['call']['result']) . "',"
							. " `OCC_callsTime` = NOW(),"
							. " `OCC_callsUser` = '" . $_USER['id'] . "',"
							. " `OCC_callsClient` = '" . $client['idclients'] . "'")) {
				$OUT['msgs'][] = 'Ошибка записи звонка';
			}
			$OCC_call = mfa(mysqlQuery("SELECT * FROM `$database`.`OCC_calls` WHERE `idOCC_calls`='" . mysqli_insert_id($link) . "'"));
		}

		if (trim($_JSON['call']['comment'] ?? '')) {
			$OUT['msgs'][] = 'Есть комментарий, сохраняем';
			mysqlQuery("INSERT INTO `$database`.`OCC_callsComments` SET `OCC_callsCommentsCall` = '" . $OCC_call['idOCC_calls'] . "', `OCC_callsCommentsComment` = '" . mres(trim($_JSON['call']['comment'])) . "'");
		}

		if (
				($_JSON['call']['result'] ?? '') == 4 &&
				($_JSON['call']['recallDate'] ?? null) &&
				($_JSON['call']['recallTime'] ?? null)
		) {
			$OUT['msgs'][] = 'Добавляем отложенный звонок';
			if (!mysqlQuery("INSERT INTO `$database`.`OCC_calls` SET "
							. " `OCC_callsPhone` = " . sqlVON($clientPhone['idclientsPhones'] ?? $_JSON['client']['idclientsPhones']) . ","
							. " `OCC_callsType` = '7',"
							. " `OCC_callsTime` = '" . mres($_JSON['call']['recallDate']) . " " . mres($_JSON['call']['recallTime'] . ':00') . "',"
							. " `OCC_callsClient` = '" . $client['idclients'] . "',"
							. " `OCC_callsUser` = '" . $_USER['id'] . "'")) {
				die('Ошибка добавления отложенного звонка');
			}
		}
	} else {
		$OUT['msgs'][] = 'Ошибка с номером телефона, не понятно.';
	}
	//Ну и на последок сохраним процедурки!
	if (($_JSON['call']['result'] ?? '') == 5 && count($_JSON['appointments'])) {
		$appointmentsText = '';
		$appointmentsByTime = [];
		foreach ($_JSON['appointments'] as $appointment) {
			$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($appointment['service']['id'] ?? $appointment['service']) . "'"));

			if (!(mysqlQuery("INSERT INTO `$database`.`servicesApplied` SET "
							. "`servicesAppliedService`='" . mres($appointment['service']['id'] ?? $appointment['service']) . "',"
							. "`servicesAppliedQty`='1',"
							. "`servicesAppliedClient` = '" . $client['idclients'] . "',"
							. "`servicesAppliedBy` = '" . $_USER['id'] . "',"
							. "`servicesAppliedByReal` = '" . $_USER['id'] . "',"
							. "`servicesAppliedPersonal` = " . sqlVON($appointment['personnel']) . ", "
							. "`servicesAppliedDate` = '" . date("Y-m-d", $appointment['time']) . "',"
							. "`servicesAppliedTimeBegin` = '" . date("Y-m-d H:i:s", $appointment['time']) . "',"
							. "`servicesAppliedTimeEnd` = '" . date("Y-m-d H:i:s", $appointment['time'] + ($service['servicesDuration'] ?? 30) * 60) . "',"
							. "`servicesAppliedPrice` = " . sqlVON($appointment['price']) . ""
							. "") &&
					($idservicesApplied = mysqli_insert_id($link))
					)) {
				die('Ошибка добавления процедуры');
			}
			if (($appointment['comment'] ?? false) && !(mysqlQuery("INSERT INTO `$database`.`servicesAppliedComments` SET `servicesAppliedCommentsSA` = '" . $idservicesApplied . "', `servicesAppliedCommentText`=" . sqlVON($appointment['comment']) . ""))) {
				die('Ошибка добавления комментария к процедуре');
			}
			$appointmentsText .= date("d.m.Y H:i", $appointment['time'])
					. ' ' . $service['servicesName']
					. ' (' . $appointment['price'] . 'p.)' . "\r\n";

			$appointmentsByDate[date("Y-m-d", $appointment['time'])][] = $appointment;
		}

		telegramSendByRights([112], "🍀 Маркетинг осуществил запись клиента:\r\n"
				. "Оператор: " . $_USER['lname'] . " " . $_USER['fname'] . "\r\n"
				. "Клиент: " . $client['clientsLName'] . " " . $client['clientsFName'] . " " . $client['clientsMName'] . "\r\n" . 'https://' . SUBDOMEN . 'menua.pro/pages/offlinecall/schedule.php?client=' . $client['idclients'] . "&date=" . date("Y-m-d", $_JSON['appointments'][0]['time']) . "\n" . $appointmentsText);

// ну по всей видимости всё чудесно записалось.
		if ($_JSON['call']['smsTemplate'] ?? null) {

			//надо отправить отдельные смски по каждому дню. Для этого пересоберем массив исходя из дат (сделаем это на предыдущем этапе)
			$smsTemplatesText = mfa(mysqlQuery("SELECT * FROM `$database`.`smsTemplates` WHERE `idsmsTemplates` = '" . mres($_JSON['call']['smsTemplate']) . "'"))['smsTemplatesText'] ?? null;
			foreach ($appointmentsByDate as $date => $appointments) {
				usort($appointments, function ($a, $b) {
					return $a['time'] <=> $b['time'];
				});
				$smsdata = [
					'dateone' => date("d.m", $appointments[0]['time']),
					'timeone' => date("H:i", $appointments[0]['time']),
				];
				$smsText = smsTemplate($smsTemplatesText, $smsdata);

				$sendResult = sendSms(($_JSON['client']['clientsPhonesPhone'] ?? null), $smsText);
				$success = (($sendResult['status'] ?? '') === 'ok');
				if ($success) {
					$uid = preg_replace("/message-id-/", '', $sendResult['result']['uid']);
					mysqlQuery("UPDATE `$database`.`clientsPhones` SET `clientsPhonesSmsTotal` = `clientsPhonesSmsTotal`+1 WHERE `idclientsPhones` = '" . ($_JSON['client']['idclientsPhones'] ?? $clientPhone['idclientsPhones']) . "'");

//					$_JSON['client']['idclientsPhones'] ?? $clientPhone['idclientsPhones'];
//					sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "\$_JSON['client']['idclientsPhones']: '" . ($_JSON['client']['idclientsPhones'] ?? '') . "'\n"
//						. "\$clientPhone['idclientsPhones']: '" . ($clientPhone['idclientsPhones'] ?? '') . "'" . "\n\nЗаписываю\n" . max(($_JSON['client']['idclientsPhones'] ?? 0), ($clientPhone['idclientsPhones'] ?? 0))]);
					mysqlQuery("INSERT INTO `$database`.`sms` SET "
							. "`smsHash` = '" . $uid . "', "
							. "`smsUser` = '" . $_USER['id'] . "', "
							. "`smsClient` = '" . $client['idclients'] . "', "
							. "`smsText` = '" . mres($smsText) . "', "
							. "`smsPhone` = " . sqlVON(max(($_JSON['client']['idclientsPhones'] ?? 0), ($clientPhone['idclientsPhones'] ?? 0))) . "");
				} else {
					$output['errors'][] = 'Не удалось отправить SMS';
//					die('Не удалось отправить SMS');
				}
			}
		} else {
//			die('Отсутствует шаблон СМС в запросе');
		}
	}
//	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => json_encode($OUT, 288 + 128) . "] \nОператор: " . $_USER['lname'] . ' ' . $_USER['fname']]);
	exit(json_encode(['success' => true], 288));
}// SAVE CALL


if (($_JSON['action'] ?? '') == "loadCall" && isset($_JSON['call'])) {
	$clients = query2array(mysqlQuery(""
					. " SELECT  idclients,idclientsPhones,clientsPhonesPhone,clientsLName,clientsFName,clientsMName,clientsBDay,clientsOldSince "
					. " FROM `OCC_calls`"
					. " LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`)"
					. " LEFT JOIN `clients` ON (`idclients` = `OCC_callsClient`)"
					. " WHERE `idOCC_calls` = '" . mres($_JSON['call']) . "'"));
	exit(json_encode(['clients' => ($clients ?? [])], JSON_UNESCAPED_UNICODE));
}






if (($_JSON['action'] ?? '') == "getPhoneInfo" && isset($_JSON['phone'])) {
	if ($_JSON['phone'] == '') {
		if (isset($_JSON['RCC_phoneDatabase'])) {
			if ($_JSON['RCC_phoneDatabase'] === 'null') {
				$RCC_phoneDatabaseSQL = " AND isnull(`RCC_phonesBase`)";
			} elseif ($_JSON['RCC_phoneDatabase'] === '') {
				$RCC_phoneDatabaseSQL = "";
			} else {
				$RCC_phoneDatabaseSQL = " AND `RCC_phonesBase` = '" . mres($_JSON['RCC_phoneDatabase']) . "'";
			}
		}
		$RCC_phone = mfa(mysqlQuery("SELECT * FROM `RCC_phones` WHERE isnull(`RCC_phonesClaimedBy`) $RCC_phoneDatabaseSQL ORDER BY RAND() LIMIT 1;"));
		if ($RCC_phone) {
			$name = explode(' ', preg_replace('!\s+!', ' ', trim($RCC_phone['RCC_phonesLName'])));
			$client = [
				"idRCC_phones" => $RCC_phone['idRCC_phones'],
				"clientsPhonesPhone" => $RCC_phone['RCC_phonesNumber'],
				"clientsLName" => $name[0] ?? '',
				"clientsFName" => $name[1] ?? '',
				"clientsMName" => $name[2] ?? ''
			];
			mysqlQuery("UPDATE `RCC_phones` SET "
					. " `RCC_phonesClaimedBy`='" . $_USER['id'] . "',"
					. " `RCC_phonesClaimedAt`=CURRENT_TIMESTAMP()"
					. " WHERE `idRCC_phones`='" . $RCC_phone['idRCC_phones'] . "'");

			exit(json_encode(['clients' => [$client]], JSON_UNESCAPED_UNICODE));
		} else {
			exit(json_encode(['clients' => []], JSON_UNESCAPED_UNICODE));
		}
	} else {
		$phoneNumber = preg_replace("/[^0-9]/", "", $_JSON['phone'] ?? '');
		if (strlen($phoneNumber) == 11) {
			$phoneNumber[0] = '8';
		} elseif (strlen($phoneNumber) == 10) {
			$phoneNumber = '8' . $phoneNumber;
		}

		$clients = query2array(mysqlQuery("SELECT idclients,idclientsPhones,clientsPhonesPhone,clientsLName,clientsFName,clientsMName,clientsBDay,clientsOldSince,clientsSource,(SELECT `clientsVisitsDate` FROM `clientsVisits` WHERE `idclientsVisits` = (SELECT MAX(`idclientsVisits`) FROM `clientsVisits` WHERE `clientsVisitsClient` = `idclients`)) as `lastVisit`,"
						. " (SELECT MAX(`f_salesDate`) FROM `f_sales` WHERE `f_salesType`=3 AND `f_salesClient` = `idclients`) AS `lastSale3`"
						. " FROM `clients`"
						. " LEFT JOIN `clientsPhones` ON (`clientsPhonesClient` = `idclients`)"
						. " WHERE `clientsPhonesPhone`='" . mres($phoneNumber) . "'"
						. " AND isnull(`clientsPhonesDeleted`)"));
		if (count($clients) > 1) {
			telegramSendByRights([160], "🚨🚨🚨При записи через маркетинг найдено больше 1го клиента с номером телефона\n" . mres($phoneNumber) . "\nСрочно принять меры!\n https://" . SUBDOMEN . "menua.pro/sync/utils/clones/index.php?clones=[" . implode(',', array_unique(array_column($clients, 'idclients'))) . "] \nОператор: " . $_USER['lname'] . ' ' . $_USER['fname']);
		}
		foreach ($clients as $clientsIndex => $client) {
			$clients[$clientsIndex]['fromLastVisit'] = $client['lastVisit'] ? secondsToTime(time() - strtotime($client['lastVisit'])) : null;
			$clients[$clientsIndex]['sales'] = mfa(mysqlQuery("SELECT (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesClient` =" . $client['idclients'] . " AND `f_salesType` IN(1,2)) AS `qty` "));
			$clients[$clientsIndex]['lastSale3'] = $clients[$clientsIndex]['lastSale3'] ? secondsToTime(time() - strtotime($clients[$clientsIndex]['lastSale3'])) : null;
			$clients[$clientsIndex]['calls'] = array_map(function ($call) {
				$call['date'] = date("d.m.Y", strtotime($call['OCC_callsTime']));
				return $call;
			}, query2array(mysqlQuery("SELECT * FROM `OCC_calls`"
									. " LEFT JOIN `OCC_callsComments` on (`OCC_callsCommentsCall` = `idOCC_calls`)"
									. " LEFT JOIN `users` ON (`idusers` = `OCC_callsUser`)"
									. " WHERE not isnull(`OCC_callsCommentsComment`) and `OCC_callsClient`='" . $client['idclients'] . "'")))


			;
		}
		if (!$clients) {
			$clients = query2array(mysqlQuery("SELECT `RCC_phonesNumber` as `clientsPhonesPhone`,`RCC_phonesLName` as `clientsLName`,`RCC_phonesBase` FROM `RCC_phones` where RCC_phonesNumber = '$phoneNumber';"));
		}
//	Ограничить запись операторам.
//(предоставлять информацию при наличии:
//дата последнего приобретенного абонемента (его тип) (прошло месяцев),
//дата последнего визита (прошло месяцев)
//)
//
//
//При наличии абонементов тип (первичный, повторный) отключить возможность записать клиента. Уведомить оператора о причинах.
//
//При наличии разовых абонементов запись не ограничивать, но уведомить оператора (дата приобретения абонемента, последний визит)	


		exit(json_encode(['clients' => ($clients ?? [])], JSON_UNESCAPED_UNICODE));
	}
}



if (!($OUT ?? false)) {
	die(json_encode(['error' => 'wtf', 'json' => $_JSON], 288));
} else {
//	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => json_encode($OUT, 288 + 128) . "] \nОператор: " . $_USER['lname'] . ' ' . $_USER['fname']]);
	exit(json_encode($OUT, 288));
}


