<?php

$_START = microtime(1);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
//usleep(2100000);
$prices = query2array(mysqlQuery("SELECT * FROM `servicesPrices`"));
$users = query2array(mysqlQuery("SELECT * FROM `users`"));

$_FINISH = round(microtime(1) - $_START, 3);

exit(json_encode(
				[
					'time' => $_FINISH,
					'prices' => count($prices),
					'users' => count($users)
				]
));
