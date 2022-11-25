<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
header("Content-type: application/json; charset=utf8");

$_JSON = [
	"params" => [
		[
			"clinicId" => "2",
			"doctorId" => "5823",
			"time" => "2021-02-12T13=>30",
			"name" => "Василий Пупкин",
			"phone" => "71234567890",
			"email" => "vasya.pupkin@example.tld",
			"type" => "inclinic",
			"comment" => "Здесь важная информация",
			"birthday" => null,
			"specialityName" => ''
		]
	]
];

$response = [
	"jsonrpc" => "2.0",
	"result" => [
		"bookId" => "5101020e5dce7b9038b29231"
	],
	"id" => microtime(true)
];
exit(json_encode($response, 288));

