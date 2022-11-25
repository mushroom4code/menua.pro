<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
?>
https://t.me/<?= TGNICK; ?>?start=giftcert20211223
<?
die();
sendTelegram('sendPhoto', [
	'chat_id' => '325908361',
	'photo' => 'https://menua.pro/temp/giftcert.jpg',
	'caption' => 'Покажите данный сертификат на регистратуре медицинского центра «Инфинити» для проведения подарочной чистки зубов  и фторирования🌸']);
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

