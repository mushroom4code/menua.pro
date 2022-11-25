<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (($_JSON['action'] ?? false) == 'saveOptions' &&
		($_JSON['option'] ?? false) &&
		($_JSON['SA'] ?? false) &&
		isset($_JSON['value'])
) {

	if ($_JSON['value'] === true) {
		$result = mysqlQuery("INSERT IGNORE INTO `servicesAppliedOptions` SET `servicesAppliedOptionsSA`='" . mres($_JSON['SA']) . "',`servicesAppliedOptionsOption` = '" . mres($_JSON['option']) . "'");
	}
	if ($_JSON['value'] === false) {
		$result = mysqlQuery("DELETE FROM `servicesAppliedOptions` WHERE `servicesAppliedOptionsSA`='" . mres($_JSON['SA']) . "' AND `servicesAppliedOptionsOption` = '" . mres($_JSON['option']) . "'");
	}

	print json_encode(['success' => (!!$result)], 288);
}

if (($_JSON['action'] ?? false) == 'saveDraft') {
	$success = true;
	if ($_JSON['data']['asTemplate'] ?? false) {

		if ($_JSON['data']['idtemplate'] ?? false) {//update
			$f_salesDraftTemplate = mfa(mysqlQuery("SELECT * FROM `f_salesDraftTemplates` WHERE `idf_salesDraftTemplates`='" . mres($_JSON['data']['idtemplate']) . "'"));
			if (!$f_salesDraftTemplate) {
				die(json_encode(['success' => false, 'error' => mysqli_error($link)], 288));
			}
			mysqlQuery("UPDATE `f_salesDraftTemplates` SET `f_salesDraftTemplatesName`='" . mres($_JSON['data']['templateName'] ?? null) . "' WHERE `idf_salesDraftTemplates` = '" . $f_salesDraftTemplate['idf_salesDraftTemplates'] . "'");

			mysqlQuery("DELETE FROM `f_subscriptionsDraftTemplates` WHERE `f_subscriptionsDraftTemplatesSaleDraftTemplate`='" . $f_salesDraftTemplate['idf_salesDraftTemplates'] . "'");
			foreach (($_JSON['data']['subscriptionsDraft'] ?? []) as $subscriptionDraft) {
				if ($success && mysqlQuery("INSERT INTO `f_subscriptionsDraftTemplates` SET "
								. "`f_subscriptionsDraftTemplatesService`='" . mres($subscriptionDraft['idservices']) . "', "
								. "`f_subscriptionsDraftTemplatesQty`='" . mres($subscriptionDraft['qty']) . "', "
								. "`f_subscriptionsDraftTemplatesPrice`='" . mres(trim($subscriptionDraft['price']) ? trim($subscriptionDraft['price']) : '0') . "', "
								. "`f_subscriptionsDraftTemplatesSaleDraftTemplate`='" . $f_salesDraftTemplate['idf_salesDraftTemplates'] . "'")) {
					
				} else {
					$success = false;
				}
			}
		} else {//new
			if (!mysqlQuery("INSERT INTO `f_salesDraftTemplates` SET"
							. " `f_salesDraftTemplatesDate` = CURDATE(),"
							. " `f_salesDraftTemplatesAuthor`='" . $_USER['id'] . "',"
							. " `f_salesDraftTemplatesName`='" . mres($_JSON['data']['templateName'] ?? null) . "'"
							. "")) {
				$success = false;
			}

			if ($success) {
				$f_salesDraftTemplates = mfa(mysqlQuery("SELECT * FROM `f_salesDraftTemplates` WHERE `idf_salesDraftTemplates` = '" . mysqli_insert_id($link) . "'"));
				foreach (($_JSON['data']['subscriptionsDraft'] ?? []) as $subscriptionDraft) {
					if ($success && mysqlQuery("INSERT INTO `f_subscriptionsDraftTemplates` SET "
									. "`f_subscriptionsDraftTemplatesService`='" . mres($subscriptionDraft['idservices']) . "', "
									. "`f_subscriptionsDraftTemplatesQty`='" . mres($subscriptionDraft['qty']) . "', "
									. "`f_subscriptionsDraftTemplatesPrice`='" . mres(trim($subscriptionDraft['price']) ? trim($subscriptionDraft['price']) : '0') . "', "
									. "`f_subscriptionsDraftTemplatesSaleDraftTemplate`='" . $f_salesDraftTemplates['idf_salesDraftTemplates'] . "'")) {
						
					} else {
						$success = false;
					}
				}
			}
		}
	} else {
		if (($_JSON['data']['saleDraft'] ?? null) == null) {
			mysqlQuery("INSERT INTO `f_salesDraft` SET "
					. " `f_salesDraftClient`='" . mres($_JSON['data']['client']) . "',"
					. " `f_salesDraftDate`=CURDATE(),"
					. " `f_salesDraftAuthor`='" . $_USER['id'] . "',"
					. " `f_salesDraftNumber` = "
					. " (SELECT * FROM (SELECT ifnull(MAX(`f_salesDraftNumber`),0)+1 FROM `f_salesDraft` WHERE `f_salesDraftClient`='" . mres($_JSON['data']['client']) . "') as `maxNum`)"
					. "");
			$_JSON['data']['saleDraft'] = mysqli_insert_id($link);
		}
		if (!mysqlQuery("DELETE FROM `f_subscriptionsDraft` WHERE `f_subscriptionsDraftSaleDraft` = '" . mres($_JSON['data']['saleDraft'] ?? '0') . "'")) {
			$success = false;
		}
		if ($success) {
			foreach (($_JSON['data']['subscriptionsDraft'] ?? []) as $subscriptionDraft) {
				if ($success && mysqlQuery("INSERT INTO `f_subscriptionsDraft` SET "
								. "`f_subscriptionsDraftService`='" . mres($subscriptionDraft['idservices']) . "', "
								. "`f_subscriptionsDraftQty`='" . mres($subscriptionDraft['qty']) . "', "
								. "`f_subscriptionsDraftPrice`='" . mres(trim($subscriptionDraft['price']) ? trim($subscriptionDraft['price']) : '0') . "', "
								. "`f_subscriptionsDraftSaleDraft`='" . mres($_JSON['data']['saleDraft']) . "'")) {
					
				} else {
					$success = false;
				}
			}
		}
	}


	exit(json_encode(['success' => $success, 'error' => mysqli_error($link)], 288));
}


if (($_JSON['action'] ?? false) == 'saveValue' &&
		($_JSON['option'] ?? false) &&
		($_JSON['SA'] ?? false) &&
		isset($_JSON['qtyValue'])
) {

	if (floatval($_JSON['qtyValue']) > 0) {
		$result = mysqlQuery("INSERT IGNORE INTO `servicesAppliedOptions` SET"
				. " `servicesAppliedOptionsSA`='" . mres($_JSON['SA']) . "',"
				. "`servicesAppliedOptionsOption` = '" . mres($_JSON['option']) . "',"
				. "`servicesAppliedOptionsQty` = '" . floatval($_JSON['qtyValue']) . "'");
	}
	if ($_JSON['qtyValue'] <= 0) {
		$result = mysqlQuery("DELETE FROM `servicesAppliedOptions` WHERE `servicesAppliedOptionsSA`='" . mres($_JSON['SA']) . "' AND `servicesAppliedOptionsOption` = '" . mres($_JSON['option']) . "'");
	}

	print json_encode(['success' => (!!$result), 'error' => mysqli_error($link)], 288);
}

