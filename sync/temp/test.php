<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';



$myVar = '123456';
define('ICQAPIKEY', '223');

function printConst() {
	print ICQAPIKEY;
}

printConst();

function printMyVar() {
	global $myVar;
	print $myVar;
}

printMyVar();
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

