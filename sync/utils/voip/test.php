<?php

header('Content-Encoding: none;');

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
error_reporting(E_ALL);

echo "<h2>TCP/IP Connection</h2><br>";

printr(SOL_TCP);
?>
