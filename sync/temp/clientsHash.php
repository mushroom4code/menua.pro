<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';


$clients = mysqlQuery("SELECT * FROM `clients` WHERE isnull(`clientsHash`)");

while ($client = mfa($clients)) {
	while (!mysqlQuery("UPDATE `clients` SET `clientsHash` = '" . RDS(4) . "' WHERE `idclients` = '" . $client['idclients'] . "'")) {
		print "COLLISION!<br>";
	}
	print $client['idclients'] . ' ok<br>';
}