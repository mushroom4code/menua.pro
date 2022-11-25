<?php

include 'constants.php';

$data['jsonrpc'] = '2.0';
$data['method'] = 'addClinics';
$data['id'] = microtime(1);

$data['params']['clinics'][0]['prefix'] = $alias;
$data['params']['clinics'][0]['external_id'] = $alias . '_1';
$data['params']['clinics'][0]['name'] = 'Инфинити на Московском, многопрофильный центр';
$data['params']['clinics'][0]['phone'] = '78125098049';
$data['params']['clinics'][0]['city'] = 'Санкт-Петербург';
$data['params']['clinics'][0]['address'] = 'Московский проспект, д.111';
$data['params']['clinics'][0]['status'] = 'active';

$data['params']['clinics'][1]['prefix'] = $alias;
$data['params']['clinics'][1]['external_id'] = $alias . '_2';
$data['params']['clinics'][1]['name'] = 'Инфинити на Чкаловской, многопрофильный медицинский центр';
$data['params']['clinics'][1]['phone'] = '78125098049';
$data['params']['clinics'][1]['city'] = 'Санкт-Петербург';
$data['params']['clinics'][1]['address'] = 'ул. Большая Зеленина, д.8, к.2';
$data['params']['clinics'][1]['status'] = 'active';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	"Content-Type: application/json",
	"Authorization: Bearer " . $bearer
));

$response = curl_exec($ch);
curl_close($ch);

printr(json_decode($response), 1);

