<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (($_JSON['name'] ?? false) && ($_JSON['idservice'] ?? false) && mysqlQuery("UPDATE `services` SET `serviceNameShort`=" . sqlVON($_JSON['name'], 1) . " WHERE `idservices` = '" . mres($_JSON['idservice']) . "'")) {
	$output['success'] = true;
} else {
	$output['success'] = false;
}



if (($_JSON['action'] ?? false) === 'applyPosition') {
	$output['success'] = true;
	foreach (query2array(mysqlQuery("SELECT * FROM `services` WHERE `servicesParent`='" . mres($_JSON['parent']) . "'")) as $service) {
		if (($_JSON['state'] ?? false)) {
			if (!mysqlQuery("INSERT IGNORE INTO `positions2services` SET `positions2servicesPosition`='" . mres($_JSON['idposition']) . "', `positions2servicesService`='" . $service['idservices'] . "'")) {
				$output['success'] = false;
				$output['error'] = mysqli_error($link);
			}
		} else {
			if (!mysqlQuery("DELETE FROM `positions2services` WHERE `positions2servicesPosition`='" . mres($_JSON['idposition']) . "' AND `positions2servicesService`='" . $service['idservices'] . "'")) {
				$output['success'] = false;
				$output['error'] = mysqli_error($link);
			}
		}
	}
}



exit(json_encode(($output ?? []), 288));
