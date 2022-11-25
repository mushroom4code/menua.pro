<?php

include '/var/www/html/public/sync/includes/setupLight.php';
$username = '072c6477c5';
$password = '0906751026';
$URL = 'https://target.tele2.ru/sync/api/send_message';
$URL = 'https://target.tele2.ru/sync/api/v2/send_message/message-id-' . ($_GET['id'] ?? 'Px8n8aUylDzs');


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($username . ":" . $password), 'Content-Type:application/json']);

$result = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
curl_close($ch);
printr($status_code);
printr($result);
?>
