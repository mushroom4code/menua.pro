<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf8");
mb_internal_encoding("UTF-8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

function getMessages($client = false) {
	$messages = query2array(mysqlQuery("SELECT "
					. "  (SELECT MAX(`idinfinitimedbotMessages`) FROM `infinitimedbotMessages`) as `max`,"
					. " `idinfinitimedbotMessages` as `idmessages`,"
					. " `infinitimedbotMessagesChatid` as `chatid`,"
					. " `infinitimedbotMessagesMessage` as `message`,"
					. " `infinitimedbotMessagesTime` as `time`,"
					. " `infinitimedbotMessagesType` as `type`,"
					. " `infinitimedbotMessagesReaded` as `readed`,"
					. " `idclients`,"
					. " `clientsLName`,"
					. " `clientsFName`,"
					. " `clientsMName`,"
					. " `usersLastName`,"
					. " `usersFirstName`,"
					. " (SELECT COUNT(1) FROM `infinitimedbotMessages` AS `M2` WHERE isnull(`M2`.`infinitimedbotMessagesReaded`) AND `M2`.`infinitimedbotMessagesClient` = `idclients`) AS `unread`,"
					. " `clientsTG`"
					. " FROM"
					. " `infinitimedbotMessages` as `M1`"
					. " LEFT JOIN `clients` ON (`idclients`=`M1`.`infinitimedbotMessagesClient`) "
					. " LEFT JOIN `users` ON (`idusers`=`M1`.`infinitimedbotMessagesUser`) "
					. " WHERE NOT isnull(`infinitimedbotMessagesClient`)"
					. ($client ? " AND `infinitimedbotMessagesClient` = '" . mres($client) . "'" : "")
					. ""));
	$clients = [];
	foreach ($messages as $message) {
		$clients[$message['idclients']]['id'] = $message['idclients'];
		$clients[$message['idclients']]['clientsLName'] = $message['clientsLName'];
		$clients[$message['idclients']]['clientsFName'] = $message['clientsFName'];
		$clients[$message['idclients']]['clientsMName'] = $message['clientsMName'];
		$clients[$message['idclients']]['unread'] = $message['unread'];
		$clients[$message['idclients']]['clientsTG'] = $message['clientsTG'];
		$clients[$message['idclients']]['messages'][] = [
			'id' => $message['idmessages'],
			'chatid' => $message['chatid'],
			'message' => $message['message'],
			'time' => $message['time'],
			'type' => $message['type'],
			'readed' => $message['readed'],
			'user' => $message['usersLastName'] . ' ' . $message['usersFirstName'],
		];
		$lastmessage = $message['max'];
	}
	return ['clients' => array_values($clients), 'lastmessage' => $lastmessage];
}
 
function getUpdates($msgcnt) {
	$messages = query2array(mysqlQuery("SELECT "
					. "  (SELECT MAX(`idinfinitimedbotMessages`) FROM `infinitimedbotMessages`) as `max`,"
					. " `idinfinitimedbotMessages` as `idmessages`,"
					. " `infinitimedbotMessagesChatid` as `chatid`,"
					. " `infinitimedbotMessagesMessage` as `message`,"
					. " `infinitimedbotMessagesTime` as `time`,"
					. " `infinitimedbotMessagesType` as `type`,"
					. " `infinitimedbotMessagesReaded` as `readed`,"
					. " `idclients`,"
					. " `clientsLName`,"
					. " `clientsFName`,"
					. " `clientsMName`,"
					. " `usersLastName`,"
					. " `usersFirstName`,"
					. " (SELECT COUNT(1) FROM `infinitimedbotMessages` AS `M2` WHERE isnull(`M2`.`infinitimedbotMessagesReaded`) AND `M2`.`infinitimedbotMessagesClient` = `idclients`) AS `unread`,"
					. " `clientsTG`"
					. " FROM"
					. " `infinitimedbotMessages` as `M1` LEFT JOIN `clients` ON (`idclients`=`M1`.`infinitimedbotMessagesClient`) "
					. " LEFT JOIN `users` ON (`idusers`=`M1`.`infinitimedbotMessagesUser`) "
					. " WHERE NOT isnull(`infinitimedbotMessagesClient`)"
					. " AND `infinitimedbotMessagesClient` IN (SELECT distinct `infinitimedbotMessagesClient` FROM `infinitimedbotMessages` WHERE `idinfinitimedbotMessages`>'" . mres($msgcnt) . "')"
					. ""));
	$clients = [];
	foreach ($messages as $message) {
		$clients[$message['idclients']]['id'] = $message['idclients'];
		$clients[$message['idclients']]['clientsLName'] = $message['clientsLName'];
		$clients[$message['idclients']]['clientsFName'] = $message['clientsFName'];
		$clients[$message['idclients']]['clientsMName'] = $message['clientsMName'];
		$clients[$message['idclients']]['unread'] = $message['unread'];
		$clients[$message['idclients']]['clientsTG'] = $message['clientsTG'];
		$clients[$message['idclients']]['messages'][] = [
			'id' => $message['idmessages'],
			'chatid' => $message['chatid'],
			'message' => $message['message'],
			'time' => $message['time'],
			'type' => $message['type'],
			'readed' => $message['readed'],
			'user' => $message['usersLastName'] . ' ' . $message['usersFirstName'],
		];
		$lastmessage = $message['max'];
	}
	return ['clients' => array_values($clients), 'lastmessage' => $lastmessage ?? null];
}

if (($_JSON['action'] ?? '') == 'loadmessages') {
	$clients = getMessages();
	exit(json_encode($clients, 288));
}
if (($_JSON['action'] ?? '') == 'geupdates') {
	$clients = getUpdates($_JSON['lastmessage']);
	exit(json_encode($clients ?? [], 288));
}

if (($_JSON['action'] ?? '') == 'markasread') {
	mysqlQuery("UPDATE `infinitimedbotMessages` SET `infinitimedbotMessagesReaded` = NOW() WHERE `infinitimedbotMessagesClient` = '" . mres($_JSON['client']) . "'");
	$clients = getMessages($_JSON['client']);
	exit(json_encode($clients, 288));
}

if (($_JSON['action'] ?? '') == 'sendmessage') {
	infinitimedbot('sendMessage', ['chat_id' => $_JSON['clientsTG'], 'text' => trim($_JSON['message']), 'idclients' => ($_JSON['client'] ?? null)]);
	$clients = getMessages($_JSON['client']);
	exit(json_encode($clients, 288));
}



