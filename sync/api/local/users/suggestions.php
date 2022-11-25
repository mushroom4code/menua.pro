<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

if (!($_JSON['search'] ?? false)) {
	die(json_encode(['success' => false, 'msgs' => [['type' => 'error', 'text' => 'Пустой запрос']]], 288));
}
if (mb_strlen(trim($_JSON['search'])) < 2) {
	die(json_encode(['success' => false, 'msgs' => [['type' => 'error', 'text' => 'Слишком короткий запрос. Дополните.']]], 288));
}

$n = 0;
$_search = explode(' ', preg_replace('/\s+/', ' ', trim($_JSON['search'])));

$_searchParts = [];
foreach ($_search as $_searchElement) {
	$_searchParts[] = "CONCAT_WS(' ', `usersLastName`, `usersFirstName`, `usersMiddleName`) LIKE '%" . mres($_searchElement) . "%' ";
}

$sql = "SELECT "
		. " `idusers`,`usersLastName`,`usersFirstName`,`usersMiddleName`"
		. " FROM  `users`"
		. " WHERE isnull(`usersDeleted`)"
		. " AND (" . implode(' AND ', $_searchParts) . ")"
		. " LIMIT 25";
$searchResult = query2array(mysqlQuery($sql));

if (!($searchResult ?? [])) {
	die(json_encode(['success' => false, 'msgs' => [['sql' => $sql, 'type' => 'error', 'text' => 'Поиск не дал результатов. Измените запрос.']]], 288));
}

exit(json_encode(['success' => true, 'users' => $searchResult], JSON_UNESCAPED_UNICODE));
