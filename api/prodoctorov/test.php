<?php

$json = '{
    "doctor": {
        "id": "ID врача",
        "lpu_id": "ID клиники",
        "specialty": {
            "id": "ID специальности",
            "name": "Название специальности"
        }
    },
    "appointment": {
        "dt_start": "2020-01-01 08:00",
        "dt_end": "2020-01-01 09:00",
        "is_online": false,
        "comment": "Комментарий записи"
    },
    "client": {
        "first_name": "Иван",
        "second_name": "Иванович",
        "last_name": "Иванов",
        "mobile_phone": "79999999999",
        "birthday": "1990-01-01"
    },
    "appointment_source": "Prodoctorov"
}';

include $_ROOTPATH . '/sync/includes/setupLight.php';

$payload = json_encode($dateToSend);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

