<?php
ini_set("memory_limit", -1);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
$filename = "Прохождения процедур_2023.json";
$handle = fopen($filename, "r");
$json = fread($handle, filesize($filename));
fclose($handle);
?>
<pre>
	<?
	$ereg = '/(\{)((.|\n)+?)("(Комментарий)")((.|\n)+?)("(Комментарий)")((.|\n)+?)(\})/i';

	$converted = preg_replace($ereg, '\1\2"\5Проц"\6"КОД"\10\11\12', $json);

	$filename = 'servisesApplied.json';

// Вначале давайте убедимся, что файл существует и доступен для записи.
	// В нашем примере мы открываем $filename в режиме "записи в конец".
	// Таким образом, смещение установлено в конец файла и
	// наш $somecontent допишется в конец при использовании fwrite().
	if (!$handle = fopen($filename, 'w+')) {
		echo "Не могу открыть файл ($filename)";
		exit;
	}

	// Записываем $somecontent в наш открытый файл.
	if (fwrite($handle, $converted) === FALSE) {
		echo "Не могу произвести запись в файл ($filename)";
		exit;
	}

	echo "Ура! Записали в файл ($filename)";
	fclose($handle);
	?>


</pre>
