<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
include 'functions.php';
header("Content-type: application/json; charset=utf8");

$URL = '';

$headers = array();
$headers[] = "X-Authorization: 1377c0b1-87ff-4d7b-8878-c7f0c9ba77c3";
$curl = curl_init();

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // return the results instead of outputting it
curl_setopt($curl, CURLOPT_URL, $URL);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

// Verify SSL
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

$result = (curl_exec($curl));
print($result);
