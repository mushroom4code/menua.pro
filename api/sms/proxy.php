<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

print json_encode($_POST, 288);
sendSms($_POST['msisdn'], $_POST['text']);
