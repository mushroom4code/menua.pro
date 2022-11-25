<?php

if (isset($argv)) {
  parse_str(implode('&', array_slice($argv, 1)), $_GET);
  $_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
  $_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
  $_ROOTPATH = 'undefined';
}


include $_ROOTPATH . '/sync/includes/setupLight.php';

print "\r\r\r\n" . $_ROOTPATH . '/sync/includes/setupLight.php' . "\r\r\r\n";

function getExtension($filename) {
  $path_info = pathinfo($filename);
  if (isset($path_info['extension'])) {
	 return $path_info['extension'];
  } else {
	 print '';
  }
}

$backup_files = array("php", "css", "js");
$n = 0;
$dir_array = Array();
$count = [];
$strings = [];

function getDirectoryTree($patch = '') {
  global $_ROOTPATH;

  $root = $_ROOTPATH;
  global $backup_files, $dir_array, $link, $count, $strings;
  $handle = opendir($root . $patch);
  if ($handle) {
	 while (($file = readdir($handle))) {
		if (in_array($file, ['uploads', '3rdparty'])) {
		  continue;
		}
		if (is_file($root . $patch . "/" . $file) && in_array(getExtension($file), $backup_files) && filesize($root . $patch . "/" . $file)) {
		  // Выводим старое имя файла,  Переименовываем выводим новое
		  $fd = fopen($root . $patch . "/" . $file, "rb");
 
		  $content = fread($fd, filesize($root . $patch . "/" . $file));
		  fclose($fd);

		  $strings[] = "("
					 . "'" . mysqli_real_escape_string($link, $patch . "/" . $file) . "',"
					 . "'" . mysqli_real_escape_string($link, filesize($root . $patch . "/" . $file)) . "', "
					 . "FROM_UNIXTIME(" . mysqli_real_escape_string($link, filemtime($root . $patch . "/" . $file)) . "), "
					 . "'" . mysqli_real_escape_string($link, $content) . "' )";
		}
		if (is_dir($root . $patch . "/" . $file) && ($file != ".") && ($file != "..")) {
		  /* рекусрсивно проходим по директории */
		  getDirectoryTree($patch . "/" . $file);  // Обходим вложенный каталог
		}
	 }
	 closedir($handle);
  } else {
	 print 'CANNOT OPEN THE ' . $root . $patch;
  }
}

getDirectoryTree();
mysqlQuery("INSERT IGNORE INTO `backup` (`path`,`size`,`lastmod`,`content`) VALUES " . implode(",", $strings));

if (mysqli_affected_rows($link)) {
  $count = query2array(mysqlQuery("SELECT `path` FROM `backup` WHERE `backupTime`=CURRENT_TIMESTAMP"));
}

if (1 && count($count)) {
  ICQMSDelay(0, 'sashnone', "backuped:\r\n" . implode("\r\n", array_column($count, 'path')));
}

print date("Y.m.d H:i:s", time());
