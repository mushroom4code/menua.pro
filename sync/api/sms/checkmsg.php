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

foreach ($_JSON['checkIDs'] as &$id) {
	$id = "'" . $id . "'";
}

$statuses = query2array(mysqlQuery("SELECT `smsHash`,`smsState` FROM `sms` WHERE `smsHash` IN (" . implode(',', $_JSON['checkIDs']) . ")"));

print json_encode(['success' => true, 'statuses' => $statuses]);
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

