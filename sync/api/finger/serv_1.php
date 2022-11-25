<?php

set_time_limit(0);
ignore_user_abort(true);
error_reporting(E_ALL); // выводим все ошибки и предупреждения
ob_implicit_flush();


define('PORT', "8081");


function http_parse_headers($headers) {
	$headerdata = [];
	if ($headers === false) {
		return false;
	}
	$headers = str_replace("\r", "", $headers);
	$headers = explode("\n", $headers);
	foreach ($headers as $value) {
		$header = explode(": ", $value);
		if ($header[0] && !isset($header[1])) {
			$headerdata['status'] = $header[0];
		} elseif ($header[0] && $header[1]) {
			$headerdata[$header[0]] = $header[1];
		}
	}
	return $headerdata;
}

class chat {

	public function sendHeaders($headersString, $newSocket, $host, $port, $ip) {
		$headers = http_parse_headers($headersString);
		if (!isset($headers['Sec-WebSocket-Key'])) {
			return false;
		}
		$key = $headers['Sec-WebSocket-Key'];
		$sKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$strHeader = ""
				. (1) ? ( "HTTP/1.1 101 Switching Protocols\r\n" //$ip == '127.0.0.1'
				. "Upgrade: websocket\r\n"
				. "Connection: Upgrade\r\n"
//				. "Content-Security-Policy: connect-src 'self'\r\n"
//				. "Sec-WebSocket-Origin: $host\r\n"
//				. "Sec-WebSocket-Location: ws://$host:$port/WS/server.php\r\n"
				. "Sec-WebSocket-Accept: $sKey\r\n\r\n") : "HTTP/1.1 400 Bad Request\r\n\r\n"
		;
		socket_write($newSocket, $strHeader, strlen($strHeader));
	}


	public function seal($string) {
		$b1 = 0x81;
		$length = strlen($string);
		$header = "";
		if ($length <= 125) {
			$header = pack('CC', $b1, $length);
		} elseif ($length <= 65535) {
			$header = pack('CCn', $b1, 126, $length);
		} else {
			$header = pack('CCNN', $b1, 127, $length);
		}
		return $header . $string;
	}

	public function unseal($string) {
		$length = ord($string[1]) & 127;

		if ($length == 126) {
			$mask = substr($string, 4, 4);
			$data = substr($string, 8);
		} elseif ($length == 127) {
			$mask = substr($string, 10, 4);
			$data = substr($string, 14);
		} else {
			$mask = substr($string, 2, 4);
			$data = substr($string, 6);
		}

		$socketStr = "";
		for ($i = 0; $i < strlen($data); $i++) {
			$socketStr .= $data[$i] ^ $mask[$i % 4];
		}





		return $socketStr;
	}

	public function send($messageString, $clientSocketArray) {
		$messageLength = strlen($messageString);

		foreach ($clientSocketArray as $clientSocket) {
			@socket_write($clientSocket, $messageString, $messageLength);
		}
		return true;
	}

	public function createChatMessage($messageArray) {
		return $this->seal(json_encode($messageArray));
	}

}


$chat = new Chat();

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, '127.0.0.1', PORT);
socket_listen($socket);

function unserialize_session($session_data, $start_index = 0, &$dict = null) {
	isset($dict) or $dict = array();

	$name_end = strpos($session_data, "|", $start_index);

	if ($name_end !== FALSE) {
		$name = substr($session_data, $start_index, $name_end - $start_index);
		$rest = substr($session_data, $name_end + 1);

		$value = unserialize($rest);   // PHP will unserialize up to "|" delimiter.
		$dict[$name] = $value;

		return unserialize_session($session_data, $name_end + 1 + strlen(serialize($value)), $dict);
	}

	return $dict;
}

$clientSocketArray = [$socket];

while (true) {
	$newSocketArray = $clientSocketArray;
	$null = [];
	socket_select($newSocketArray, $null, $null, 0, 10);

	if (in_array($socket, $newSocketArray)) {
		print "NEW SOCKET\r\n";
		$newSocket = @socket_accept($socket);
		if (!$newSocket) {
			continue;
		}
		$clientSocketArray[] = $newSocket;

		$header = socket_read($newSocket, 2048);
		$headers = http_parse_headers($header);
		

		

		socket_getpeername($newSocket, $clientIP);
		$chat->sendHeaders($header, $newSocket, 'menua.pro', PORT, $clientIP);
		print "\t" . '(' . count($clientSocketArray) . ")\r\n";

		$newSocketArrayIndex = array_search($socket, $newSocketArray);
		if ($newSocketArrayIndex > -1) {
			unset($newSocketArray[$newSocketArrayIndex]);
		}
	}



	usleep(15000);
}


socket_close($socket);
