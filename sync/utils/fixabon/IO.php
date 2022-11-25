<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");




if (($_JSON['source'] ?? 0) && ($_JSON['target'] ?? 0)) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if (($_JSON['target']['action'] ?? '') == 'makeitfree') {
		if (mysqlQuery("UPDATE `servicesApplied` SET "
						. "`servicesAppliedIsFree` = '1',"
						. "`servicesAppliedPrice` = '2'"
						. " WHERE `idservicesApplied` = '" . intval($_JSON['source']['idservicesApplied']) . "'")) {
			print json_encode(['success' => true], 288);
		} else {
			print json_encode(['success' => false], 288);
		}
		die();
	}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if (mysqlQuery("UPDATE `servicesApplied` SET "
//					. " `servicesAppliedSubscription` = '" . intval($_JSON['target']['idf_subscriptions']) . "',"
					. " `servicesAppliedPrice` = '" . intval($_JSON['target']['f_salesContentPrice']) . "',"
					. " `servicesAppliedContract` = '" . intval($_JSON['target']['f_subscriptionsContract']) . "'"
					. " WHERE `idservicesApplied` = '" . intval($_JSON['source']['idservicesApplied']) . "'")) {
		print json_encode(['success' => true], 288);
	} else {
		print json_encode(['success' => false], 288);
	}
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}