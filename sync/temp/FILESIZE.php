LLO<?php


$filename = '/var/www/html/public/logs/PGT-access.log';
$lastFilesize = 0;
//sendTelegram('sendMessage', ['chat_id' => 325908361, 'text' => 'start loggong']);
clearstatcache();
$filesize = filesize($filename);
var_dump($filesize);