<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';




printr(query2array(mysqlQuery("select * from `cdr` order by id desc LIMIT 10;", mysqli_connect('192.168.128.100', 'cdruser', '0lwddbjSLgRyXvpN', 'asterisk'))));



/* 
Для подключения используйте сервер 192.168.128.100 пользователь cdruser пароль 0lwddbjSLgRyXvpN
База  табличка cdr
 */

