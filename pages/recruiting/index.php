<?php
$load['title'] = $pageTitle = 'Рекрутинг';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (1) {
    error_reporting(E_ALL); //
    ini_set('display_errors', 1);
}



if (R(138)) {
    
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<?
if (!R(138)) {
    ?>E403R138<?
    include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
    exit();
}
include 'menu.php';




include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
