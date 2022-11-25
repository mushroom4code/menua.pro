
<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$path = $_SERVER['DOCUMENT_ROOT'] . '/logs/PGT-access.log';

$log = file($path);
//print count($log);

$files = [];

//print $log[50];

//0.297 "GET /css/2/mobile.css?202008180929 HTTP/2.0"
//GET /pages/proclist/ HTTP/2.0
foreach ($log as $row) {
	$parts = explode(" ", $row);
	$url = explode('?', $parts[2]);
	if ($url[0][strlen($url[0]) - 1] == '/') {
		$url[0] .= 'index.php';
	}

	$files[$url[0]]['time'] = ($files[$url[0]]['time'] ?? 0) + floatval($parts[0]);
	$files[$url[0]]['requests'] = ($files[$url[0]]['requests'] ?? 0) + 1;
	$files[$url[0]]['price'] = $files[$url[0]]['time'] / $files[$url[0]]['requests'];
}
uasort($files, function($a, $b) {
	return $b['time'] <=> $a['time'];
});

//$fh = fopen($path, 'w');
//fclose($fh);
printr($files);




