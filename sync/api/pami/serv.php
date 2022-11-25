<?php

$context = stream_context_create();

$fp = stream_socket_client("tcp://192.168.128.100:5038", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

if (!$fp) {
	echo "$errstr ($errno)<br />\n";
} else {
	fwrite($fp, "GET / HTTP/1.0\r\nHost: www.example.com\r\nAccept: */*\r\n\r\n");
	while (!feof($fp)) {
		echo fgets($fp, 1024);
	}
	fclose($fp);
}