<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: application/json; charset=utf8");
mb_internal_encoding("UTF-8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (($_JSON['action'] ?? false) === 'getKKTS') {
	exit(json_encode(['success' => true, 'KKTS' => getEvotorKKTS()]));
}

die(json_encode(['success' => false]));
