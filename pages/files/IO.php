<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';


if (!empty($_FILES)) {
	$uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/pages/files/uploads/' . $_USER['id'] . '/';
	if (!is_dir($uploaddir)) {
		mkdir($uploaddir, 0700);
	}
	$newfilename = time();
	$uploadfile = $uploaddir . $newfilename;
	if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
		if (mysqlQuery("INSERT INTO `files` SET"
						. " `filesUser` = '" . $_USER['id'] . "',"
						. " `filesPath` = '" . $newfilename . "',"
						. ((isset($_POST['parent']) && $_POST['parent'] !== 'null') ? " `filesParent` = '" . $_POST['parent'] . "'," : '')
						. " `filesName` = '" . FSS(basename($_FILES['file']['name'])) . "'")
		) {
			print json_encode(['success' => true, 'msgs' => [['type' => 'success', 'text' => 'Файл успешно загружен']]], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
			die();
		} else {
			print json_encode(['success' => true, 'msgs' => [['text' => 'Ошибка базы данных']]], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
			unlink($uploadfile);
			die();
		}
	} else {
		print json_encode(['success' => true, 'msgs' => [['text' => 'Ошибка загрузки файла']]], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
		die();
	}
}








if (isset($_JSON['action']) && $_JSON['action'] === 'addFolder' && !empty($_JSON['name'])) {
	mysqlQuery("INSERT INTO `files` SET"
			. " `filesUser` = '" . $_USER['id'] . "',"
			. (isset($_JSON['parent']) ? " `filesParent` = '" . $_JSON['parent'] . "'," : '')
			. " `filesName` = '" . FSS($_JSON['name']) . "'");

	print json_encode(['success' => true], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
	die();
}

print json_encode(['success' => false], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
die();

//printr();

