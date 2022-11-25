<?php

if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    $_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
    $_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
    $_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';

$clients = query2array(mysqlQuery("SELECT * FROM `clients` WHERE DATE(`clientsTGset`) = CURDATE()"));
$text = '';
if (count($clients)) {
    $clientsAdded = array_filter($clients, function ($client) {
        return $client['clientsTG'];
    });

    $clientsRefuse = array_filter($clients, function ($client) {
        return $client['clientsTGrefuse'];
    });
    if (count($clientsAdded)) {
        $text .= 'Сегодня телеграм ' . human_plural_form(count($clientsAdded), ['подключил', 'подключили', 'подключили']) . ' ' . human_plural_form(count($clientsAdded), ['клиент', 'клиента', 'клиентов'], 1) . "\n";
    }

    if (count($clientsRefuse)) {
        $text .= 'Сегодня  ' . human_plural_form(count($clientsRefuse), ['отказался', 'отказались', 'отказались']) . '  подключать телеграм ' . human_plural_form(count($clientsRefuse), ['клиент', 'клиента', 'клиентов'], 1) . "\nПричины:\n\n" . implode("\n", array_column($clientsRefuse, 'clientsTGrefuse'));
    }
}
printr($text, 1); 

sendTelegram('sendMessage', ['chat_id' => -822747663, 'text' => $text]);
