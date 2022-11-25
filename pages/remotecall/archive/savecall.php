<?

//	ICQ_messagesSend_SYNC('sashnone', json_encode($_JSON, 288));
//записываем звоночек в базу первички


if (($_JSON['call']['callid'] ?? '') !== '') {//перезвон
	$occphone = mfa(mysqlQuery("SELECT * FROM `OCC_calls` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `OCC_callsPhone`) WHERE `idOCC_calls`='" . mres($_JSON['call']['callid']) . "'"));
	$idclientsPhones = $occphone['OCC_callsPhone']; //айди телефона
	$idclients = $occphone['clientsPhonesClient']; //айди клиента
} else {//новый звонок
	if (in_array($_JSON['call']['callResult'], [5, 4])) {
//если клиент записывается вносим клиента в базу или ищем если он уже есть.
		mysqlQuery("INSERT INTO `clients` SET "
				. " `clientsLName` = '" . mres(mb_ucfirst(trim($_JSON['call']['client']['clientLName']))) . "', "
				. " `clientsFName` = '" . mres(mb_ucfirst(trim($_JSON['call']['client']['clientFName']))) . "', "
				. " `clientsMName` = '" . mres(mb_ucfirst(trim($_JSON['call']['client']['clientMName']))) . "', "
				. " `clientsAddedBy`='" . $_USER['id'] . "',"
				. " `clientsSource` = '2',"
				. " `clientsDatabase` = " . (($_JSON['call']['client']['clientDatabase'] ?? false) ? (mres(trim(($_JSON['call']['client']['clientDatabase'] ?? false)))) : "null") . ""
				. ";");
		$idclients = mysqli_insert_id($link); ///айди клиента
	}
}



if (in_array($_JSON['call']['callResult'], [5, 4])) {
	if ($_JSON['call']['idRCC_phone'] ?? false) {
		$rccphone = mfa(mysqlQuery("SELECT `RCC_phonesNumber` FROM `RCC_phones` WHERE `idRCC_phones` = '" . mres($_JSON['call']['idRCC_phone']) . "'")); // телефон из общей базы
		mysqlQuery("INSERT INTO `clientsPhones` SET `clientsPhonesClient` = '" . $idclients . "', `clientsPhonesPhone`='" . $rccphone['RCC_phonesNumber'] . "'"); //вставляем телефон в базу клиентов
		$idclientsPhones = mysqli_insert_id($link); //айди телефона
	} elseif ($_JSON['call']['client']['clientPhone'] ?? false) {
		mysqlQuery("INSERT INTO `clientsPhones` SET `clientsPhonesClient` = '" . $idclients . "', `clientsPhonesPhone`='" . mres($_JSON['call']['client']['clientPhone']) . "'"); //вставляем
		$idclientsPhones = mysqli_insert_id($link); //айди телефона
	}
}







if ($_JSON['call']['callResult'] == 5) {
	if (($_JSON['call']['callid'] ?? '') !== '') {//перезвон
	} else {//новый звонок
		mysqlQuery("INSERT INTO `OCC_calls` SET "
				. " `OCC_callsPhone` = '$idclientsPhones',"
				. " `OCC_callsType` = '" . mres($_JSON['call']['callResult']) . "',"
				. " `OCC_callsClient` = '" . $idclients . "',"
				. " `OCC_callsUser` = '" . $_USER['id'] . "'");
		$idOCC_calls = mysqli_insert_id($link);
	}


//добавляем  процедурку
	mysqlQuery("INSERT INTO `servicesApplied` SET "
			. "`servicesAppliedService`='" . mres($_JSON['call']['serviceApplied']['selectedService']) . "',"
			. "`servicesAppliedQty`='1',"
			. "`servicesAppliedClient` = '" . $idclients . "',"
			. "`servicesAppliedBy` = '" . $_USER['id'] . "',"
			. "`servicesAppliedPersonal` = " . (($_JSON['call']['serviceApplied']['selectedPersonnel'] ?? '') == '' ? 'null' : ("'" . mres($_JSON['call']['serviceApplied']['selectedPersonnel']) . "'")) . ", "
			. "`servicesAppliedDate` = '" . date("Y-m-d", $_JSON['call']['serviceApplied']['selectedTimestamp']) . "',"
			. "`servicesAppliedTimeBegin` = '" . date("Y-m-d H:i:s", $_JSON['call']['serviceApplied']['selectedTimestamp']) . "',"
			. "`servicesAppliedTimeEnd` = '" . date("Y-m-d H:i:s", $_JSON['call']['serviceApplied']['selectedTimestamp'] + 30 * 60) . "',"
			. "`servicesAppliedPrice` = " . (($_JSON['call']['serviceApplied']['selectedPrice'] ?? '') == '' ? 'null' : ("'" . intval($_JSON['call']['serviceApplied']['selectedPrice']) . "'")) . ""
			. "");
	$idservicesApplied = mysqli_insert_id($link);
	if (trim(($_JSON['call']['comment'] ?? '')) !== '') {
		mysqlQuery("INSERT INTO `servicesAppliedComments` SET `servicesAppliedCommentsSA` = '" . $idservicesApplied . "', `servicesAppliedCommentText`='" . mres(trim($_JSON['call']['comment'])) . "'");
	}


//тут ещё можно было бы смску отправить.

	$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices` = '" . mres($_JSON['call']['serviceApplied']['selectedService']) . "'"));

	if (($_JSON['call']['serviceApplied']['selectedPersonnel'] ?? '') !== '') {
		$personnel = query2array(mysqlQuery("SELECT `positionsName` FROM `usersPositions` left join `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`='" . mres($_JSON['call']['serviceApplied']['selectedPersonnel']) . "'"));
		if (count($personnel)) {
			$positions = implode(',', array_column($personnel, 'positionsName'));
			$positionsText = ", вас примет " . $positions;
		}
	}


	foreach (getUsersByRights([112]) as $recipient) {
		if ($recipient['usersTG']) {
			$mgsg = "🍀 Новая запись из маркетинга.\r\n"
					. "Оператор: " . $_USER['lname'] . " " . $_USER['fname'] . "\r\n"
					. "Клиент: " . mres(mb_ucfirst(trim($_JSON['call']['client']['clientLName']))) . " " . mres(mb_ucfirst(trim($_JSON['call']['client']['clientFName']))) . " " . mres(mb_ucfirst(trim($_JSON['call']['client']['clientMName']))) . "\r\n"
					. "Процедура: " . $service['servicesName'] . "\r\n"
					. "Дата: " . date("d.m.yг. в H:i", $_JSON['call']['serviceApplied']['selectedTimestamp']);
			sendTelegram('sendMessage', ['chat_id' => $recipient['usersTG'], 'text' => $mgsg]);
		}
	}

	$smsTemplatesText = mfa(mysqlQuery("SELECT * FROM `smsTemplates` WHERE `idsmsTemplates` = '" . mres($_JSON['call']['smsTemplate']) . "'"))['smsTemplatesText'] ?? null;

	$smsText = "Добрый день!\r\nВы записаны на " . $service['servicesName'] . ($positionsText ?? '') . " " . date("d.m.yг. в H:i", $_JSON['call']['serviceApplied']['selectedTimestamp']) . " по адресу: ст.м. Московские ворота, Московский проспект 111. При себе иметь паспорт. \r\nтел. регистратуры: 89522026912.";
	$smsdata = [
		'dateone' => date("d.m", $_JSON['call']['serviceApplied']['selectedTimestamp']),
		'timeone' => date("H:i", $_JSON['call']['serviceApplied']['selectedTimestamp']),
	];
	$smsText = smsTemplate($smsTemplatesText, $smsdata);

	$sendResult = sendSms($rccphone['RCC_phonesNumber'] ?? $occphone['clientsPhonesPhone'] ?? $_JSON['call']['client']['clientPhone'], $smsText);
	$success = (($sendResult['status'] ?? '') === 'ok');
	if ($success) {
		$uid = preg_replace("/message-id-/", '', $sendResult['result']['uid']);
		mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesSmsTotal` = `clientsPhonesSmsTotal`+1 WHERE `idclientsPhones` = '" . $idclientsPhones . "'");
		mysqlQuery("INSERT INTO `sms` SET "
				. "`smsHash` = '" . $uid . "', "
				. "`smsUser` = '" . $_USER['id'] . "', "
				. "`smsClient` = '" . $idclients . "', "
				. "`smsText` = '" . mres($smsText) . "', "
				. "`smsPhone` = '" . $idclientsPhones . "'");
	} else {
		$output['errors'][] = 'Не удалось отправить SMS';
	}
} elseif ($_JSON['call']['callResult'] == 4 && $_JSON['call']['dateRecall']) {
	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "📞⏳Перезвон\r\nОператор: " . $_USER['lname'] . ' ' . $_USER['fname']]);
	mysqlQuery("INSERT INTO `OCC_calls` SET "
			. " `OCC_callsPhone` = '$idclientsPhones',"
			. " `OCC_callsType` = '" . mres($_JSON['call']['callResult']) . "',"
			. " `OCC_callsClient` = '" . $idclients . "',"
			. " `OCC_callsUser` = '" . $_USER['id'] . "'");
	$idOCC_calls = mysqli_insert_id($link);

	if (trim(($_JSON['call']['comment'] ?? '')) !== '') {
		mysqlQuery("INSERT INTO `OCC_callsComments` SET "
				. ""
				. "`OCC_callsCommentsComment`='" . mres(trim($_JSON['call']['comment'])) . "'"
				. ", `OCC_callsCommentsCall`='" . $idOCC_calls . "'");
	}
	mysqlQuery("INSERT INTO `OCC_calls` SET "
			. " `OCC_callsPhone` = '$idclientsPhones',"
			. " `OCC_callsType` = '7',"
			. " `OCC_callsTime` = '" . mres($_JSON['call']['dateRecall']) . " 12:00:00',"
			. " `OCC_callsClient` = '" . $idclients . "',"
			. " `OCC_callsUser` = '" . $_USER['id'] . "'");
} else {//остальные статусы звонков
}

print json_encode(($output ?? []), 288);
die();
