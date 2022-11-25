<?php

if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    $_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
    $_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
    $_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setup.php';
//print $_ROOTPATH . '/sync/includes/setup.php';
//{"phones":{"8090":"89052084769"},"date":"2020-12-16","client":"112","phone":"8090"}
//$_JSON['date']
//$_JSON['client']
//$_JSON['phone']

$clientPhone = mfa(mysqlQuery("SELECT * FROM `clientsPhones` left join `clients` on (`idclients` =  `clientsPhonesClient`) WHERE `clientsPhonesClient`='" . mysqli_real_escape_string($link, $_JSON['client']) . "' AND `idclientsPhones`='" . mysqli_real_escape_string($link, $_JSON['phone']) . "'"));
//printr($clientPhone);


if (($clientPhone['clientsPhonesPhone'] ?? false)) {

    if (strlen($clientPhone['clientsPhonesPhone']) !== 11) {
        die(json_encode(['success' => false, 'msgs' => ['Некорректный номер']], 288));
    }
    $clientPhone['clientsPhonesPhone'][0] = 7;

    $SA = mfa(mysqlQuery("SELECT MIN(`servicesAppliedTimeBegin`) AS `time` FROM `servicesApplied` WHERE `servicesAppliedClient`='" . $clientPhone['clientsPhonesClient'] . "' AND `servicesAppliedDate` = '" . mysqli_real_escape_string($link, $_JSON['date']) . "' AND isnull(`servicesAppliedDeleted`)"));
//smsTemplate	"2"
    if ($_JSON['smsTemplate'] ?? false) {
        $smsTemplatesText = mfa(mysqlQuery("SELECT * FROM `smsTemplates` WHERE `idsmsTemplates` = '" . mres($_JSON['smsTemplate']) . "'"))['smsTemplatesText'] ?? null;
    }
//$smsTemplate = mfa(mysqlQuery(""));
    if (!($smsTemplatesText ?? false)) {
        die(json_encode(['success' => false, 'msgs' => ['Отсутствуют шаблон сообщения']], 288));
    }
    if (!($SA['time'] ?? null) && $_JSON['smsTemplate'] != '9') {
        die(json_encode(['success' => false, 'msgs' => ['Отсутствуют процедуры']], 288));
    }
    $username = SMSLOGIN;
    $password = SMSPASSWORD;
    $URL = 'https://target.tele2.ru/api/v2/send_message';

    if (($SA['time'] ?? null)) {
        $TS = strtotime($SA['time']);
        if ($TS <= time()) {
            die(json_encode(['success' => false, 'msgs' => ['Дата процедур в прошлом']], 288));
        }
        $smsdata = [
            'dateone' => date("d.m", $TS),
            'timeone' => date("H:i", $TS),
        ];
        $data = [
            "msisdn" => $clientPhone['clientsPhonesPhone'],
            "shortcode" => SMSNAME,
            "text" => smsTemplate($smsTemplatesText, $smsdata)
        ];
    }

    if ($_JSON['smsTemplate'] == '9') {
        $data = [
            "msisdn" => $clientPhone['clientsPhonesPhone'],
            "shortcode" => SMSNAME,
            "text" => smsTemplate($smsTemplatesText, ['dateone' => date("d.m", strtotime($_JSON['date']))])
        ];
    }





    $data_string = json_encode($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); //timeout after 30 seconds
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($username . ":" . $password), 'Content-Type:application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

    $result = curl_exec($ch);
//					sendTelegram('sendMessage', ['chat_id' => -522070992, 'text' => json_encode($result)]); 
//					usleep(600000);
//
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
//				printr($result);
    curl_close($ch);
    $resultARR = explode("\r\n", $result);
    $resultJSON = $resultARR[count($resultARR) - 1];

    $resultOBJ = json_decode($resultJSON, 1);
    $success = (($resultOBJ['status'] ?? '') === 'ok');
//					ICQ_messagesSend_SYNC('sashnone', json_encode($resultOBJ, 288));
    mysqlQuery("INSERT INTO `sms` SET "
            . "`smsUser` = '" . $_USER['id'] . "', "
            . "`smsClient` = '" . $clientPhone['clientsPhonesClient'] . "', "
            . "`smsText` = '" . mysqli_real_escape_string($link, $data['text']) . "', "
            . "`smsPhone` = '" . $clientPhone['idclientsPhones'] . "'");
    $idsms = mysqli_insert_id($link);
    if ($success) {
        $uid = preg_replace("/message-id-/", '', $resultOBJ['result']['uid']);
        mysqlQuery("UPDATE `sms` SET "
                . "`smsHash` = '" . $uid . "' "
                . " WHERE `idsms` = '" . $idsms . "'");
        mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesSmsTotal` = `clientsPhonesSmsTotal`+1 WHERE `idclientsPhones` = '" . $clientPhone['idclientsPhones'] . "'");

        print json_encode(['success' => $success, 'uid' => $uid], 288);
//						sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => json_encode($resultOBJ, 288)]);
    } else {
        sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => '$resultOBJ: ' . json_encode($resultOBJ, 288)]);
        sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => '$request: ' . json_encode(json_decode($data_string, 1), 288 + 128) . "\n\n" . '$result: ' . $result]);
        print json_encode(['success' => false, 'msgs' => ['SMS не ушло, возникла ошибка на стороне провайдера.']], 288);
    }
} else {
    print json_encode(['success' => false, 'msgs' => ['Отсутствует номер']], 288);
}


    