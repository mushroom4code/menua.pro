<?
if ($_USER['id'] ?? $_USER['idusers'] ?? false) {
	mysqlQuery("UPDATE `users` SET `usersIP` = '" . ($_SERVER['REMOTE_ADDR'] ?? '??') . "' WHERE `idusers` = " . ($_USER['id'] ?? $_USER['idusers']) . " ");
}
$PGT_START = microtime(1);
foreach (glob("_*.php") as $filename) {
	//	print "<br>including: " . $filename;
	include ( $filename);
}
?><!DOCTYPE html>
<html lang="ru-RU">
	<head>
		<!-- Yandex.Metrika counter -->
		<script src="https://cdn.jsdelivr.net/npm/bowser@2.9.0/es5.js"></script>
		<script>
			window.onerror = function (errorMsg, file, lineNumber, column, object) {
				if (lineNumber) {
					fetch('/sync/api/icq/jse.php', {body: JSON.stringify({
							errorMessage: errorMsg,
							file: file,
							url: window.location.href,
							lineNumber: lineNumber,
							column: column,
							object: object,
							browser: bowser.getParser(navigator.userAgent).getResult()
									//ua: navigator.userAgent
						}), credentials: 'include', method: 'POST', headers: new Headers({'Content-Type': 'application/json'})});
				}

				//					document.body.style.background = 'black';
				//MSG('На странице ошибка!<br>Отчёт отправлен куда надо.');
			};
		</script>
		<meta charset="utf-8">
		<title><?= $load['title'] ?? 'Считать'; ?></title>
		<!--<meta name="viewport" content="width=device-width, initial-scale=1">-->
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="/css/<?= (isset($_USER['style']) ? ($_USER['style'] . '/') : '') ?>all.css?<?= date("YmdHi", filemtime($_SERVER['DOCUMENT_ROOT'] . "/css/" . ((isset($_USER['style']) ? ($_USER['style'] . '/') : '')) . 'all.css')); ?>" rel="stylesheet" type="text/css"/>

		<link href="/css/<?= (isset($_USER['style']) ? ($_USER['style'] . '/') : '') ?>common.css?<?= date("YmdHi", filemtime($_SERVER['DOCUMENT_ROOT'] . "/css/" . ((isset($_USER['style']) ? ($_USER['style'] . '/') : '')) . 'common.css')); ?>" rel="stylesheet">
		<link href="/css/<?= (isset($_USER['style']) ? ($_USER['style'] . '/') : '') ?>mobile.css?<?= date("YmdHi", filemtime($_SERVER['DOCUMENT_ROOT'] . "/css/" . ((isset($_USER['style']) ? ($_USER['style'] . '/') : '')) . 'mobile.css')); ?>" rel="stylesheet" type="text/css"/>
		<script src="/sync/js/basicFunctions.js" type="text/javascript"></script>
		<script src="/sync/js/interceptor.js" type="text/javascript"></script>
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=Gvb5KbkQRq">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=Gvb5KbkQRq">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=Gvb5KbkQRq">
		<link rel="mask-icon" href="/safari-pinned-tab.svg?v=Gvb5KbkQRq" color="#009eaa">
		<link rel="shortcut icon" href="/favicon.ico?v=Gvb5KbkQRq">
		<meta name="apple-mobile-web-app-title" content="Infinity">
		<meta name="application-name" content="Infinity">
		<meta name="msapplication-TileColor" content="#da532c">
		<meta name="msapplication-TileImage" content="/mstile-144x144.png?v=Gvb5KbkQRq">
		<meta name="theme-color" content="#ffffff">



		<?php
		if ($load['vuejs'] ?? false) {
			?><script src="/sync/3rdparty/vue.min.js" type="text/javascript"></script>
			<?
		}

		foreach (glob("javascript/*.js") as $filename) {
			?>
			<script src="<?= $filename; ?>?<?= date("YmdHi", filemtime(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename)); ?>"  type="text/javascript"></script>
			<?php
		}
		?>
		<?php
		foreach (glob("javaScript/*.js") as $filename) {
			?>
			<script src="<?= $filename; ?>?<?= date("YmdHi", filemtime(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename)); ?>"  type="text/javascript"></script>
			<?php
		}
		?>
		<?php
		foreach (glob("*.js") as $filename) {
			?>
			<script src="<?= $filename; ?>?<?= date("YmdHi", filemtime(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename)); ?>"  type="text/javascript"></script>
			<?php
		}
		?>
		<?php
		foreach (glob("styles/" . (isset($_USER['style']) ? ($_USER['style'] . '/') : '') . "*.css") as $filename) {
			?>
			<link href="<?= $filename; ?>?<?= date("YmdHi", filemtime(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename)); ?>" rel="stylesheet" type="text/css"/>
			<?php
		}
		foreach (glob("" . (isset($_USER['style']) ? ($_USER['style'] . '/') : '') . "*.css") as $filename) {
			?>
			<link href="<?= $filename; ?>?<?= date("YmdHi", filemtime(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename)); ?>" rel="stylesheet" type="text/css"/>
			<?php
		}
		?>
		<style>
			.QR {
				position: fixed; bottom: 0px; left: 0px; opacity: 0; background-color: white; padding: 20px;
			}
			.QR:hover {
				opacity: 1;
			}
		</style>
	</head>
	<body>
		<?
		if (R(30)) {
			?>

			<div id="fingerBlock"></div>
			<?php
			$lastFingers = query2array(mysqlQuery("SELECT * FROM `fingerLog` LEFT JOIN `users` on (`idusers` = `fingerLogUser`) WHERE NOT isnull(`idusers`) ORDER BY `fingerLogTime` DESC LIMIT 20"));
			$users = [];
			foreach ($lastFingers as $lastFinger) {
				$users[] = ['user' => ['id' => $lastFinger['idusers'], 'name' => $lastFinger['usersLastName'] . ' ' . $lastFinger['usersFirstName']], 'time' => date('H:i:s', strtotime($lastFinger['fingerLogTime']))];
			}
			?>

			<script>
			let ws;
			document.addEventListener("DOMContentLoaded", function () {

				let preloadedUsers = <?= json_encode($users, JSON_UNESCAPED_UNICODE); ?>;

				function makeFingerRow(data) {
					return el('div', {className: 'fingerLogRow', innerHTML: `<div><i class="fas fa-user"></i></div><div><a target="_blank" href="/pages/personal/info.php?employee=${data.user.id}">${data.user.name}</a></div><div>${data.time}</div>`});
				}

				let fingerBlock = qs('#fingerBlock');
				preloadedUsers.forEach(preloadedUser => {
					//						console.log(preloadedUser);
					fingerBlock.appendChild(makeFingerRow(preloadedUser));
				});

				async function connect() {
					ws = new WebSocket("wss://menua.pro/api/finger/");
					ws.onopen = function () {
						console.log('opened');
					};
					ws.onmessage = function (e) {
						console.log('message');
						try {
							let socketData = JSON.parse(e.data);
							console.log('PARSED:', socketData);
							if (socketData) {
								fingerBlock.insertBefore(makeFingerRow(socketData), fingerBlock.firstChild);
								while (fingerBlock.childNodes.length > 20) {
									fingerBlock.removeChild(fingerBlock.lastChild);
								}
							}
						} catch (e) {
						}
					};
					ws.onclose = function (e) {
						console.log('Socket is closed. Reconnect will be attempted in 1 second.', e);
						setTimeout(function () {
							connect();
						}, 1000);
					};
					ws.onerror = function (err) {
						console.log('Socket encountered error: ', err.message, 'Closing socket');
						setTimeout(function () {
							ws.close();
						}, 1000);
					};
				}
				connect();
			});
			</script>
			<?
		}
		?>

		<div id="clouds"></div>
		<!--<canvas id='world'></canvas>-->
		<!--<script src="/js/screensaver.js" type="text/javascript"></script>-->
		<?

		function active($page) {
			return $_SERVER['DOCUMENT_URI'] === $page ? ' class="active"' : '';
		}
		?>

		<div class="maingrid">
			<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/menu.php'; ?>
			<div>
				<div class="box mainClipboard">
					<h2><?= $pageTitle; ?></h2>
					<div class="box-body">