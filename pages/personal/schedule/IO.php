<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

$OUT['success'] = false;

//date	"2020-06-19"
//from	"10:00"
//halfs	"10"
//to	"15:00"
//user	123
if (R(54) &&
		  isset($_JSON['date']) &&
//		isset($_JSON['from']) &&
		  isset($_JSON['halfs']) &&
//		isset($_JSON['to']) &&
		  isset($_JSON['user'])
) {
  if ($_JSON['halfs'] == "00") {

	 mysqlQuery("INSERT INTO `usersScheduleLog` SET"
				. " `usersScheduleLogUser`='" . FSI($_JSON['user']) . "',"
				. " `usersScheduleLogDate`='" . $_JSON['date'] . "',"
				. " `usersScheduleSetBy` = " . $_USER['id'] . ","
				. " `usersScheduleLogAction` = 'delete'");

	 $deleteSQL = "DELETE FROM `usersSchedule` WHERE "
				. "`usersScheduleUser`='" . FSI($_JSON['user']) . "' AND "
				. "`usersScheduleDate`='" . $_JSON['date'] . "'";
	 if (mysqlQuery($deleteSQL)) {

		$OUT['success'] = true;
	 } else {
		$OUT['sql'] = $deleteSQL;
	 }
  } else {

	 mysqlQuery("INSERT INTO `usersScheduleLog` SET"
				. " `usersScheduleLogUser`='" . FSI($_JSON['user']) . "',"
				. " `usersScheduleLogDate`='" . $_JSON['date'] . "',"
				. " `usersScheduleLogFrom` = " . (($_JSON['from'] ?? false) ? ("'" . $_JSON['date'] . ' ' . $_JSON['from'] . ':00' . "'") : "null") . ","
				. " `usersScheduleLogTo` = " . (($_JSON['to'] ?? false) ? ("'" . $_JSON['date'] . ' ' . $_JSON['to'] . ':00' . "'") : "null") . ","
				. " `usersScheduleLogHalfs`='" . $_JSON['halfs'] . "',"
				. " `usersScheduleLogDuty`= " . ($_JSON['duty'] ? "'1'" : "null") . ","
				. " `usersScheduleSetBy` = " . $_USER['id'] . ","
				. " `usersScheduleLogAction` = 'insert'");

	 $insertSQL = "INSERT INTO `usersSchedule` SET "
				. "`usersScheduleUser`='" . FSI($_JSON['user']) . "', "
				. "`usersScheduleDate`='" . $_JSON['date'] . "',"
				. "`usersScheduleFrom` = " . (($_JSON['from'] ?? false) ? ("'" . $_JSON['date'] . ' ' . $_JSON['from'] . ':00' . "'") : "null") . ","
				. "`usersScheduleTo` = " . (($_JSON['to'] ?? false) ? ("'" . $_JSON['date'] . ' ' . $_JSON['to'] . ':00' . "'") : "null") . ","
				. "`usersScheduleHalfs`='" . $_JSON['halfs'] . "',"
				. "`usersScheduleSetBy`='" . $_USER['id'] . "',"
				. "`usersScheduleDuty`=" . ($_JSON['duty'] ? "'1'" : "null") . ""
				. " ON DUPLICATE KEY UPDATE "
				. "`usersScheduleFrom` = " . (($_JSON['from'] ?? false) ? ("'" . $_JSON['date'] . ' ' . $_JSON['from'] . ':00' . "'") : "null") . ","
				. "`usersScheduleTo` = " . (($_JSON['to'] ?? false) ? ("'" . $_JSON['date'] . ' ' . $_JSON['to'] . ':00' . "'") : "null") . ","
				. "`usersScheduleHalfs`='" . $_JSON['halfs'] . "',"
				. "`usersScheduleSetBy`='" . $_USER['id'] . "',"
				. "`usersScheduleDuty`=" . ($_JSON['duty'] ? "'1'" : "null") . "";
	 $OUT['sql'] = $insertSQL;
	 if (mysqlQuery($insertSQL)) {
		$OUT['success'] = true;
	 }
  }
}






















print json_encode($OUT, 288);
