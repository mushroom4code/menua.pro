<?

header('Content-Encoding: none;');
$start = time();
/*
  Подключатся нужно на адрес 192.168.128.100 порт 5038
  Для авторизации manager manager
  Пример события для вызова номера 89213533936:
  'Originate',
  Channel='SIP/299',
  Exten='89213533936',
  Priority=1,
  Context='m111-home',
  CallerID='aster',
  Где 299 это внутренний номер абонента которого нужно соединить с внешним абонентом. вызов сперва пойдет на 299 и после его ответа дальше на 89213533936
  https://voxlink.ru/kb/linux/ami-funkcional-iz-php/ не большой пример как это работает с php
 */


print 'подключаемся по AMI (' . (time() - $start) . ')<br>';
flush();
/* подключаемся по AMI */
$socket = fsockopen('192.168.128.100', 5038, $errno, $errstr, 30);
fputs($socket, "Action: Login\r\n");
fputs($socket, "UserName: manager\r\n");
fputs($socket, "Secret: manager\r\n\r\n");
/* выполняем команды */
print 'выполняем команды(' . (time() - $start) . ')<br>';
flush();
fwrite($socket, "Action: Command\r\n");
fwrite($socket, "Command: 'Originate',
    Channel='SIP/299',
    Exten='89052084769 ',
    Priority=1,
    Context='m111-home',
    CallerID='aster' \r\n\r\n");
fputs($socket, "Action: Logoff\r\n\r\n");

print '/* получаем результат */(' . (time() - $start) . ')<br>';
flush();
$wrets = "woop";
$delstr = 'Output:       SIP/'; /* эту часть уберем в начале строки */
$start = time();
while (!feof($socket) && time() - $start < 10) {
	$tmpstr = fread($socket, 8192);
	/* начинаем парсить строчки как нам нужно */
//	if (strpos($tmpstr, "Not in use") !== false) {
	/* оставляем только Not in use */
//		$tmpstrout = str_replace($delstr, '', $tmpstr);
	/* тут до первого пробела оставим номер */
//		$tmpstrout = stristr($tmpstrout, ' ', TRUE);
//		$wrets .= $tmpstrout . ';';
//	}
}
echo $wrets . (time() - $start);
fclose($socket);
?>