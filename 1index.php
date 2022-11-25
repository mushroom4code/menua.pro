<?php

$pageTitle = 'Пустая страница';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';

printr(getUsersByRights([160])); 
//print function_exists('sendTelegram');
