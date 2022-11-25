<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
// Replace with your port if not using the default.
// If unsure check /etc/asterisk/manager.conf under [general];

$call = callVOIP(($_GET['src'] ?? "220"), ($_GET['dist'] ?? "89052084769"));


if ($call['success'] ?? false) {
	print json_encode($call, 288);
} else {
	ICQ_messagesSend_SYNC('sashnone', "call failed\r\n" . print_r($call, 1));
	print json_encode($call, 288); 
}
