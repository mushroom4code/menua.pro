<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mysqlQuery("INSERT INTO `amievents` SET `amieventsEvent` = 'incomecall'");
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Barcode</title>
    </head>
    <body>
		Входящий звонок	
    </body>
</html>
