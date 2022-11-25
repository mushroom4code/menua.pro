<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");


//action	"plan"
//month	1
//planValue	3
//user	135
//year	2021

if (
		($_JSON['action'] ?? '') == 'plan' && ($_JSON['month'] ?? false) && ($_JSON['user'] ?? false) && ($_JSON['year'] ?? false)
) {
	mysqlQuery("INSERT INTO `f_planUsers` SET "
			. "`f_planUsersYear`='" . intval($_JSON['year']) . "', "
			. "`f_planUsersMonth`='" . intval($_JSON['month']) . "', "
			. "`f_planUsersUser`='" . intval($_JSON['user']) . "', "
			. "`f_planUsersSales`= '" . floatval($_JSON['value']) . "'"
			. "ON DUPLICATE KEY UPDATE "
			. "`f_planUsersSales`= '" . floatval($_JSON['value']) . "'");
}


//action	"teamlid"
//month	1
//user	323
//value	146
//year	2021
if (
		($_JSON['action'] ?? '') == 'teamlid' && ($_JSON['month'] ?? false) && ($_JSON['user'] ?? false) && ($_JSON['year'] ?? false)
) {
	mysqlQuery("INSERT INTO `f_planUsers` SET "
			. "`f_planUsersYear`='" . intval($_JSON['year']) . "', "
			. "`f_planUsersMonth`='" . intval($_JSON['month']) . "', "
			. "`f_planUsersUser`='" . intval($_JSON['user']) . "', "
			. "`f_planUsersTeamlid`= '" . intval($_JSON['value']) . "'"
			. "ON DUPLICATE KEY UPDATE "
			. "`f_planUsersTeamlid`= " . ($_JSON['value'] ? intval($_JSON['value']) : 'null') . "");
}
