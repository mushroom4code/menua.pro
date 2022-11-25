<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
// Replace with your port if not using the default.
// If unsure check /etc/asterisk/manager.conf under [general];
$port = 5038;

// Replace with your username. You can find it in /etc/asterisk/manager.conf.
// If unsure look for a user with "originate" permissions, or create one as
// shown at http://www.voip-info.org/wiki/view/Asterisk+config+manager.conf.
$username = "manager";

// Replace with your password (refered to as "secret" in /etc/asterisk/manager.conf)
$password = "manager";

// Internal phone line to call from
$internalPhoneline = $_GET['src'] ?? "220";
$target = $_GET['dist'] ?? "89052084769";

printr($_GET);
// Context for outbound calls. See /etc/asterisk/extensions.conf if unsure.
$context = "m111-home";

$socket = stream_socket_client("tcp://192.168.128.100:$port");
if ($socket) {
	echo "Connected to socket, sending authentication request.\n";

	// Prepare authentication request
	$authenticationRequest = "Action: Login\r\n";
	$authenticationRequest .= "Username: $username\r\n";
	$authenticationRequest .= "Secret: $password\r\n";
	$authenticationRequest .= "Events: off\r\n\r\n";

	// Send authentication request
	$authenticate = stream_socket_sendto($socket, $authenticationRequest);
	if ($authenticate > 0) {
		// Wait for server response
		usleep(200000);

		// Read server response
		$authenticateResponse = fread($socket, 4096);

		// Check if authentication was successful
		if (strpos($authenticateResponse, 'Success') !== false) {
			echo "Authenticated to Asterisk Manager Inteface. Initiating call.\n";

			// Prepare originate request
			$originateRequest = "Action: Originate\r\n";
			$originateRequest .= "Channel: SIP/$internalPhoneline\r\n";
			$originateRequest .= "Callerid: " . $_GET['src'] . "\r\n";
			$originateRequest .= "Exten: $target\r\n";
			$originateRequest .= "Context: $context\r\n";
			$originateRequest .= "Priority: 1\r\n";
			$originateRequest .= "Async: yes\r\n\r\n";

			// Send originate request
			$originate = stream_socket_sendto($socket, $originateRequest);
			if ($originate > 0) {
				// Wait for server response
				usleep(200000);

				// Read server response
				$originateResponse = fread($socket, 4096);

				// Check if originate was successful
				if (strpos($originateResponse, 'Success') !== false) {
					echo "Call initiated, dialing.";
				} else {
					echo "Could not initiate call.\n";
				}
			} else {
				echo "Could not write call initiation request to socket.\n";
			}
		} else {
			echo "Could not authenticate to Asterisk Manager Interface.\n";
		}
	} else {
		echo "Could not write authentication request to socket.\n";
	}
} else {
	echo "Unable to connect to socket.";
}