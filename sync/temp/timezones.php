<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
date_default_timezone_set('Asia/Barnaul');

$_DB = array('dbUser' => 'root', 'dbName' => 'warehouse', 'dbPass' => 'yflt;ysqgfhjkm');
$link1 = mysqli_connect('localhost', $_DB['dbUser'], $_DB['dbPass'], $_DB['dbName']);
$link2 = mysqli_connect('localhost', $_DB['dbUser'], $_DB['dbPass'], $_DB['dbName']);

mysqli_query($link2, "set time_zone = '+9:00';");
print date("Y-m-d H:i:s") . '<br>';
//mysqli_query($link1, "INSERT INTO warehouse.test SET testtimestamp =  now(), testdatetime =  now(), testdate = CURDATE(),testComment='1'");
//mysqli_query($link2, "INSERT INTO warehouse.test SET testtimestamp =  now(), testdatetime =  now(), testdate = CURDATE(),testComment='2'");
?>
<div style="display: inline-block">СОЕДИНЕНИЕ 1<pre><? printr(query2array(mysqlQuery("SELECT * FROM test;", $link1))); ?></pre></div><?
?><div style="display: inline-block">СОЕДИНЕНИЕ 2<pre><? printr(query2array(mysqlQuery("SELECT * FROM test;", $link2))); ?></pre></div><?