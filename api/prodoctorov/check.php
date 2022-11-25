<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
include 'functions.php';
header("Content-type: application/json; charset=utf8");
if (getBearerToken() !== 'AIuZLsEgEShFbCNuwzko') {
	header("HTTP/1.1 401 Unauthorized");
	die();
}

if ($_JSON['claim_id'] ?? false) {
	$serviceApplied = mfa(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `idservicesApplied`= '" . mres($_JSON['claim_id']) . "'"));
	if ($serviceApplied) {
		if ($serviceApplied['servicesAppliedDeleted']) {
			print json_encode(["status_code" => 204, "detail" => "cancelled"]);
			die();
		} else {
			print json_encode(["status_code" => 204, "detail" => "successfully"]);
			die();
		}
	} else {
		print json_encode(["status_code" => 416, "detail" => "Appointment doesn't exist"]);
		die();
	}
}

	