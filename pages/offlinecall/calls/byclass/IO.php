<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

//limit	"4"
//task	2
//user	115

mysqlQuery("INSERT INTO `OCC_tasksLimits` SET "
		. " `OCC_tasksLimitsTask` = " . sqlVON($_JSON['task']) . ","
		. " `OCC_tasksLimitsUser` = " . sqlVON($_JSON['user']) . ","
		. " `OCC_tasksLimitsLimit` = " . sqlVON($_JSON['limit']) . ""
		. " ON DUPLICATE KEY UPDATE "
		. " `OCC_tasksLimitsLimit` = " . sqlVON($_JSON['limit']) . ""
		. "");
