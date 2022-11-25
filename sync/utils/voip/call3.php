<?php

$start = microtime(1);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
$port = 5038;
$username = "manager";
$password = "manager";
$internalPhoneline = $_GET['src'] ?? "220";

$target = $_GET['dist'] ?? "89052084769";
if ($_GET['idclientsPhones'] ?? false) {
	$target = mfa(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `idclientsPhones`='" . mres($_GET['idclientsPhones']) . "'"))['clientsPhonesPhone'];
}

$context = mfa(mysqlQuery("SELECT * FROM `voipservers` WHERE `idvoipservers`='" . mres($_GET['viopserver'] ?? 1) . "'"))['voipserversIP'];
//$context = "call-home";
$info = [];

function claim($idRCC_phones, $user) {
	mysqlQuery("UPDATE `RCC_phones` SET "
			. "`RCC_phonesClaimedBy`='" . $user . "',"
			. "`RCC_phonesClaimedAt`=CURRENT_TIMESTAMP()"
			. "WHERE `idRCC_phones`='" . $idRCC_phones . "'");
}

$voipserver = '192.168.128.100';

try {
	$socket = stream_socket_client("tcp://" . $voipserver . ":$port", $errno, $errstr, 1);
} catch (Exception $e) {
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);
	$info['exception'] = $errormsg; //$e->getMessage();
}


if (!$socket) {

	sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "💢" . date("H:i:s") . substr(microtime(), 1, 4) . " Fail to connect"]);
	$info['connected'] = ['success' => false];
	$error = 'timeout';
} else {
	$info['connected'] = ['success' => true, 'time' => microtime(1) - $start];
	$authenticationRequest = "Action: Login\r\n";
	$authenticationRequest .= "Username: $username\r\n";
	$authenticationRequest .= "Secret: $password\r\n";
	$authenticationRequest .= "Events: off\r\n\r\n";
	$authenticate = stream_socket_sendto($socket, $authenticationRequest);
	if ($authenticate > 0) {
		usleep(100000);
		$authenticateResponse = fread($socket, 4096);
		if (strpos($authenticateResponse, 'Success') !== false) {
			$originateRequest = "Action: Originate\r\n";
			$originateRequest .= "Channel: SIP/$internalPhoneline\r\n";
			$originateRequest .= "Callerid: " . $_GET['src'] . "\r\n";
			$originateRequest .= "Exten: $target\r\n";
			$originateRequest .= "Context: $context\r\n";
			$originateRequest .= "Priority: 1\r\n";
			$originateRequest .= "Async: yes\r\n\r\n";
			$originate = stream_socket_sendto($socket, $originateRequest);
			if ($originate > 0) {
				usleep(100000);
				$originateResponse = fread($socket, 4096);
				if (strpos($originateResponse, 'Success') !== false) {
//					echo "Call initiated, dialing.";
					$info['dial'] = ['success' => true, 'time' => microtime(1) - $start];
//					sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => "📞✅\t$voipserver\t" . $_GET['src'] . " " . $_USER['lname'] . " " . $_USER['fname'] . "\t$target"]);
					if ($_GET['idRCC_phones'] ?? false) {
						claim($_GET['idRCC_phones'], $_USER['id']);
					}
				} else {
					$error = 'badrequest';
					$info['dial'] = ['success' => false, 'error' => $originateResponse, 'time' => microtime(1) - $start];
				}
			} else {
				$error = 'Could not write call initiation request to socket';
//				echo "Could not write call initiation request to socket.\n";
			}
		} else {
			$error = 'Could not authenticate to Asterisk Manager Interface';
//			echo "Could not authenticate to Asterisk Manager Interface.\n";
		}
	} else {
		$error = 'Could not write authentication request to socket';
//		echo "Could not write authentication request to socket.\n";
	}
	fclose($socket);
}

mysqlQuery("INSERT INTO `VOIPcalls` SET "
		. "`VOIPcallsSuccess` ='" . (intval($info['dial']['success'] ?? false)) . "',"
		. "`VOIPcallsErrors`= '" . ($error ?? '') . "', "
		. " `VOIPcallsUser` = '" . $_USER['id'] . "'");
print json_encode($info, 288);
