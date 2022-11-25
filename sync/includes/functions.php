<?php

if (isset($_SESSION['user'])) {
    $_USER = $_SESSION['user'];
} else {
    $_USER = NULL;
}
if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    $_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
    $_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
    $_ROOTPATH = 'undefined';
}

include 'operations.php';
include $_ROOTPATH . '/sync/includes/payments.php';

setlocale(LC_CTYPE, "ru_RU.UTF-8");
register_shutdown_function("fatal_handler");
set_error_handler("myErrorHandler", E_ALL);

$__errcntr = 0;

function myErrorHandler($errno, $errstr, $errfile, $errline) {
    global $_USER, $__errcntr, $_JSON;
    $__errcntr++;
    if ($__errcntr > 5) {
        die();
    }
    if ((mfa(mysqlQuery("SELECT count(1) as `cnt` FROM `customErrors` WHERE `errorsTime`>DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 10 SECOND)"))['cnt'] ?? 0) == 0) {

        sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => (date("H:i:s") . substr(microtime(), 1, 4) . "\r\n"
            . (($_JSON ?? false) ? (" \$_JSON: " . print_r($_JSON, true) . "\r\n") : '')
            . (($_POST ?? false) ? (" \$_POST: " . print_r($_POST, true) . "\r\n") : '')
            . (($_GET ?? false) ? (" \$_GET: " . print_r($_GET, true) . "\r\n") : '')
            . ' PHP error' . "\r\n" . ($_SERVER['REQUEST_URI'] ?? 'CLI'))
            . "\r\n"
            . 'errno: ' . $errno . "\r\n"
            . 'errstr: ' . $errstr . "\r\n"
            . 'errfile: ' . $errfile . "\r\n"
            . 'errline: ' . $errline . "\r\n"
            . '_USER: ' . ($_USER['lname'] ?? 'no lname') . ' ' . ($_USER['fname'] ?? 'no fname')]);
    }
//	mysqlQuery("INSERT INTO `customErrors` SET "
//			. "`errorsNum`='" . $errno . "',"
//			. " `errorsString` = '" . mres($errstr) . "',"
//			. " `errorsFile` = '" . mres($errfile) . "',"
//			. " `errorsLine`='" . $errline . "'");
    if (0) {
        if (($_USER['id'] ?? '') != '') {
            printr('errno: ' . $errno . "\r\n"
                    . 'errstr: ' . $errstr . "\r\n"
                    . 'errfile: ' . $errfile . "\r\n"
                    . 'errline: ' . $errline . "\r\n");
            die('sorry dude');
        }
    }
}

function fatal_handler() {
    global $_USER;
    $errfile = "unknown file";
    $errstr = "shutdown";
    $errno = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if ($error !== NULL) {
//		$errno = $error["type"];
//		$errfile = $error["file"];
//		$errline = $error["line"];
//		$errstr = $error["message"];
//		ICQMSDelay(0, 'AoLF0rcsY9MXT89Io2U', '@[751363572] ' . print_r($error, true));
        sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => $_SERVER['REQUEST_URI'] . "\r\n" . (($_JSON ?? false) ? ('$_JSON:' . print_r($_JSON, true)) : '') . print_r($error, true) . "\r\n" . ($_USER['lname'] ?? '') . ' ' . ($_USER['fname'] ?? '')]);
    }
}

function mres($escapestr) {
    global $link;
    if ($link === null) {
        die('func mres() $link is null');
    }
    if (strlen($escapestr) > 100000) {
        die('func mres() strlen too long (' . strlen($escapestr) . '):' . "\r\n");
    }
    return mysqli_real_escape_string($link, $escapestr);
}

function mysqlQuery($query, $lnk = false) {
    if ($query == '') {
        return false;
    }
    global $link, $_DB, $_QUERY_TIME, $_USER, $_JSON, $_QUERIES;
    if ($lnk !== false) {
        $ln = $lnk;
    } else {
        $ln = $link;
    }
    if (!($ln ?? false) || (!mysqli_ping($ln) ?? false)) {
        $link = mysqli_connect('localhost', $_DB['dbUser'], $_DB['dbPass'], $_DB['dbName']);
        mysqli_query($link, "SET character_set_client = utf8");
        mysqli_query($link, "SET collation_connection=utf8_general_ci");
        mysqli_query($link, "SET character_set_results = utf8");
        $ln = $link;
    }
    $qstart = microtime(true);
    $resilt = mysqli_query($ln, $query);
    $_QUERIES[$query] = ($_QUERIES[$query] ?? 0) + microtime(true) - $qstart;
    $_QUERY_TIME += microtime(true) - $qstart;
    $die = false;
//	print "<h1>ID " . mysqli_insert_id($link) . " is OKAY</h1>";
    if (mysqli_warning_count($ln) && !preg_match("/INSERT IGNORE/", $query)) {
//		print 'WARNINGS (' . mysqli_warning_count($ln) . ') IN QUERY: ' . $query . '<br>';
        if (($result = mysqli_query($ln, "SHOW WARNINGS"))) {
//			$row = mysqli_fetch_row($result);
//			print '<h2 style="color: orange;">';
//			printf("%s (%d): %s\n", $row[0], $row[1], $row[2]);
            mysqli_free_result($result);
//			print '</h2>';
        }
// $die = true;  
    }
    if (mysqli_error($ln)) {
        $backtrace = debug_backtrace();
        $bt = [];
        foreach ($backtrace as $stackLevel) {
            $bt['file'] = preg_replace('|' . addslashes($_SERVER['DOCUMENT_ROOT']) . '|i', '', $stackLevel['file'] ?? '') . '?' . ($_SERVER['QUERY_STRING'] ?? '');
            $bt['line'] = $stackLevel['line'] ?? '';
        }

//		print 'file: ' . $bt['file'] . "\r\n\r\n" . 'line: ' . $bt['line'] . "\r\n\r\n" . $query . "\r\n\r\n" . mysqli_error($ln) . "\r\n\r\n" . $_USER['lname'] . ' ' . $_USER['fname'];

        if (($_USER['id'] ?? '') == 176) {
            die('sorry dude MYSQL error: ' . $query . "<br><br>" . mysqli_error($ln) . print_r($backtrace, 1));
        } else {
            sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => 'MYSQL ERROR file: ' . $bt['file'] . "\r\n\r\n" . 'line: ' . $bt['line'] . "\r\n JSON: " . json_encode($_JSON, 288 + 128) . "\r\n\r\n" . $query . "\r\n\r\n" . mysqli_error($ln) . "\r\n\r\n" . ($_USER['lname'] ?? 'NOT') . ' ' . ($_USER['fname'] ?? ' A USER')]);
        }
    }

    return $resilt;
}

function printr($txt, $pre = false, $color = 'white') {
    global $_USER;
    if (($_USER['id'] ?? false) && !in_array(($_USER['id'] ?? false), [176, 135])) {
        return null;
    }
    if ($pre) {
        print '<pre>';
        print_r($txt);
        print "</pre>";
    } else {
        print '<textarea autocomplete="off" style="width: 100%; min-height: 200px; background-color:' . $color . '; ">';
        print json_encode($txt, JSON_UNESCAPED_UNICODE + 128);
        print "</textarea>";
    }
}

function printq($query) {
    print '<table border="1">';
    $n = 0;
    while ($row = mysqli_fetch_assoc($query)) {
        if ($n == 0) {
            $n = 1;
            print '<tr>';
            foreach ($row as $columnId => $columnValue) {
                print '<td>' . $columnId . '</td>';
            }
            print '</tr>';
        }
        print '<tr>';
        foreach ($row as $columnId => $columnValue) {
            print '<td>' . $columnValue . '</td>';
        }
        print '</tr>';
    }
    print '</table>';
}

function query2array($result, $key = null, $remove = []) {//Преобразуем результат mysql запроса в массив, Key - строка, название ключевой колонки в запросе
    $output = array();
    $n = 0;
    while ($result && ($row = mysqli_fetch_assoc($result))) {

        foreach ($row as $columnId => $columnValue) {
            if (in_array($columnId, $remove)) {
                continue;
            }
            if ($key && isset($row[$key])) {
                $output[(string) $row[$key]][(string) $columnId] = $columnValue;
            } else {
                $output[$n][$columnId] = $columnValue;
            }
        }
        $n++;
    }
    return $output;
}

function query2KVarray($result, $key = null, $value = null) {//Преобразуем результат mysql запроса в массив, Key - строка, название ключевой колонки в запросе
    $output = array();
    $n = 0;
    while ($row = mysqli_fetch_array($result)) {
        $output[$row[0]] = $row[1];
    }
    return $output;
}

function searchKids($obj, $skArr, $id, $parent, $content) {
//printr($obj);
    foreach ($skArr as $key1 => $value1) {
        if ($obj[$id] == $value1[$parent]) {
            unset($skArr[$key1]);
            $obj[$content][] = searchKids($value1, $skArr, $id, $parent, $content);
        }
    }
    return $obj;
}

function adjArr2obj($data, $id = 'id', $parent = 'parent', $content = 'content', $debug = false) {
    $return = array();

    foreach ($data as $key => $value) {//пройтись по всему массиву
        if (!$value[$parent]) {//найти строку без Парента, она будет являться корневым разделом
//$value[$content] = []; //добавить в строку подмассив с именем Контента
            $return[$key] = $value;
            unset($data[$key]); //удалить из входного массива данную строку
        }
    }



    foreach ($return as $key => $value) {//пройтись по корневым разделам рекурсивной функцией
        $return[$key] = searchKids($value, $data, $id, $parent, $content);
    }
    return $return;
}

function RDS($length = 10, $onlyDigits = false) {
    if ($onlyDigits) {
        $characters = '0123456789';
    } else {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }

    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function FIGI($name) {
    return filter_input(INPUT_GET, $name, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * FIGS
 * string санитайзер
 * Возвращает отфильтрованную переменную с именем $name из массива GET как строку
 * @param name  - имя переменной для фильтрации
 */
function FIGS($name) {
    return filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING);
}

/**
 * FIPS
 * string санитайзер
 * Возвращает отфильтрованную переменную с именем $name из массива POST как строку
 * @param name  - имя переменной для фильтрации
 */
function FIPS($name) {//FILTER_SANITIZE_STRING
    return filter_input(INPUT_POST, $name, FILTER_SANITIZE_STRING);
}

/**
 * FIPI
 *
 * INT санитайзер
 * Возвращает отфильтрованную переменную с именем $name из массива POST как INT
 * @param name  - имя переменной для фильтрации
 */
function FIPI($name) {
    return filter_input(INPUT_POST, $name, FILTER_SANITIZE_NUMBER_INT);
}

function FIPHTML($name) {
    return filter_input(INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS);
}

function mfa($result) {
    if ($result) {
        return mysqli_fetch_assoc($result);
    } else {
        if (1) {
            print 'ERROR! No result at ';
            $backtrace = debug_backtrace();
            foreach ($backtrace as $stackLevel) {
                print preg_replace('|' . addslashes($_SERVER['DOCUMENT_ROOT']) . '|i', '', $stackLevel['file']) . ' at line ' . $stackLevel['line'] . ', ';
            }
        }
    }
}

/**
 * сортирует дерево и все потомки по ключу
 * @param array  - массив для сортировки
 * @param sortBy  - имя переменной для сортировки, например <b>название</b>
 * @param content  - где искать вложенные массивы
 */
function treeSort($array, $sortBy, $content, $sortMethod = SORT_STRING) {
    foreach ($array as $key => &$subArray) {
        if (isset($subArray[$content]) && is_array($subArray[$content])) {
            $subArray[$content] = treeSort($subArray[$content], $sortBy, $content, $sortMethod);
        }
        if (!isset($subArray[$sortBy])) {
            continue;
        }
        $reference[$key] = $subArray[$sortBy];
    }

    if ($sortMethod == SORT_STRING && isset($reference)) {
        $reference = array_map('mb_strtolower', $reference);
    }
    if (isset($reference)) {
        array_multisort($reference, SORT_ASC, $sortMethod, $array);
    }
    return $array;
}

function obj2array($object) {
    $array = [];
    foreach ($object as $item) {
        array_push($array, array_filter($item, function ($value) {
                    return ($value !== null && $value !== false && $value !== '' && !(is_array($value) && !count($value)) );
                })); //$array[] = $item;
    }
    return $array;
}

function FSS($string) {
    return filter_var($string, FILTER_SANITIZE_STRING);
}

function FSI($integer) {
    return filter_var($integer, FILTER_SANITIZE_NUMBER_INT);
}

function numORnull($var) {
    return $var !== null ? +$var : null;
}

function array_filter_recursive($data) {
    $original = $data;

    $data = array_filter($data, function ($value) {
        return ($value !== null && $value !== false && $value !== '' && !(is_array($value) && !count($value)) );
    });

    $data = array_map(function ($e) {
        return is_array($e) ? array_filter_recursive($e) : $e;
    }, $data);

    return $original === $data ? $data : array_filter_recursive($data);
}

function mysqli_result($res, $row, $field = 0) {
    $res->data_seek($row);
    $datarow = $res->fetch_array();
    return $datarow[$field];
}

function rt($array) {
    return $array[mt_rand(0, count($array) - 1)];
}

function myDate($date, $seconds = false) {
    return date("d", $date)
            . ' ' . ['ошибка', 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'][date("n", $date)]
            . (date("Y") != date("Y", $date) ? date(" Y", $date) : '')
            . date(" H:i", $date)
            . ($seconds ? date(":s", $date) : '');
}

function O($option, $user = null) {
    global $_USER, $link;
    $resultSQL = "SELECT * "
            . "FROM `userOptions`"
            . " WHERE `iduserOptions` = "
            . "("
            . "SELECT max(`iduserOptions`) "
            . "FROM `userOptions` "
            . "WHERE `userOptionsUser`='" . mysqli_real_escape_string($link, $user ?? $_USER['id']) . "'"
            . "AND `userOptionsOption` = '" . mysqli_real_escape_string($link, $option) . "')"
            . ";";
    $result = mfa(mysqlQuery($resultSQL));
    return $result['userOptionsValue'] ?? null;
}

function R($rule, $adjArr = NULL) {
    global $_USER;
    if (!($_USER['rights'] ?? false) && !$adjArr) {
        return false;
    }
    return (rightRecursiveSearch($rule, $adjArr ? $adjArr : $_USER['rights'])); //boolval
}

function rightRecursiveSearch($rightId, $array, $any = false) {
    $OUT = null;
    if (is_array($array)) {
        foreach ($array as $subarray) {
            if ($subarray['id'] == $rightId || $any) {
                if (isset($subarray['V']) && $subarray['V']) {
                    $OUT = true;
                }
                if (isset($subarray['c'])) {
                    $OUT = ($OUT | rightRecursiveSearch($rightId, $subarray['c'], true));
                }
            }
            if (isset($subarray['c'])) {
                $OUT = ($OUT | rightRecursiveSearch($rightId, $subarray['c']));
            }
        }
    }

    return $OUT;
}

function get_data_mail($smtp_conn) {
    $data = "";
    while ($str = fgets($smtp_conn, 515)) {
        $data .= $str;
        if (substr($str, 3, 1) == " ") {
            break;
        }
    }
    return $data;
}

function smtpmail($fromName, $toName, $toEmail, $subject, $message) {
    $header = "Date: " . date("D, j M Y G:i:s") . " +0700\r\n";
    $header .= "From: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", urlencode(html_entity_decode($fromName)))) . "?= <sklad_m111@mail.ru>\r\n";
    $header .= "X-Mailer: The Bat! (v3.99.3) Professional\r\n";
    $header .= "Reply-To: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", urlencode(html_entity_decode($fromName)))) . "?= <sklad_m111@mail.ru>\r\n";
    $header .= "X-Priority: 3 (Normal)\r\n";
    $header .= "Message-ID: <172562218." . date("YmjHis") . "@mail.ru>\r\n";
    $header .= "To: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", urlencode(html_entity_decode($toName)))) . "?= <" . $toEmail . ">\r\n";
    $header .= "Subject: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", (html_entity_decode($subject)))) . "?=\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: text/html; charset=UTF-8\r\n";
    $header .= "Content-Transfer-Encoding: 8bit\r\n";
    $errno = null;
    $errstr = null;

    $smtp_conn = fsockopen("ssl://smtp.mail.ru", 465, $errno, $errstr, 10);
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "EHLO mail.ru\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "AUTH LOGIN\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, base64_encode("sklad_m111@mail.ru") . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, base64_encode("infiniti111") . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "MAIL FROM:sklad_m111@mail.ru\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "RCPT TO:" . $toEmail . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "DATA\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, $header . "\r\n" . $message . "\r\n.\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "QUIT\r\n");
    $data = get_data_mail($smtp_conn);
}

function smtpmailu($login, $password, $fromName, $toName, $toEmail, $subject, $message) {
    $header = "Date: " . date("D, j M Y G:i:s") . " +0700\r\n";
    $header .= "From: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", urlencode(html_entity_decode($fromName)))) . "?= <" . $login . ">\r\n";
    $header .= "X-Mailer: The Bat! (v3.99.3) Professional\r\n";
    $header .= "Reply-To: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", urlencode(html_entity_decode($fromName)))) . "?= <" . $login . ">\r\n";
    $header .= "X-Priority: 3 (Normal)\r\n";
    $header .= "Message-ID: <172562218." . date("YmjHis") . "@mail.ru>\r\n";
    $header .= "To: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", urlencode(html_entity_decode($toName)))) . "?= <" . $toEmail . ">\r\n";
    $header .= "Subject: =?UTF-8?Q?" . str_replace("+", "_", str_replace("%", "=", (html_entity_decode($subject)))) . "?=\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: text/html; charset=UTF-8\r\n";
    $header .= "Content-Transfer-Encoding: 8bit\r\n";
    $errno = null;
    $errstr = null;
    $sender = explode("@", $login)[1];
    $smtp_conn = fsockopen("ssl://smtp." . $sender, 465, $errno, $errstr, 10);
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "EHLO " . $sender . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "AUTH LOGIN\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, base64_encode($login) . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, base64_encode($password) . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "MAIL FROM:" . $login . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "RCPT TO:" . $toEmail . "\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "DATA\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, $header . "\r\n" . $message . "\r\n.\r\n");
    $data = get_data_mail($smtp_conn);
    fputs($smtp_conn, "QUIT\r\n");
    $data = get_data_mail($smtp_conn);
}

function nf($num, $decimals = 0) {
    return number_format($num, $decimals, '.', '&nbsp;');
}

function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");

    $delta = $dtF->diff($dtT); //->format('%y years, %m monthes, %d days, %h hours, %i minutes and %s seconds');

    $Y = human_plural_form($delta->format('%y'), ['год', 'года', 'лет'], true);
    $m = human_plural_form($delta->format('%m'), ['месяц', 'месяца', 'месяцев'], true);
    $d = human_plural_form($delta->format('%d'), ['день', 'дня', 'дней'], true);
    $H = human_plural_form($delta->format('%h'), ['час', 'часа', 'часов'], true);
    $i = human_plural_form($delta->format('%i'), ['минута', 'минуты', 'минут'], true);
    $s = human_plural_form(($delta)->format('%s'), ['секунда', 'секунды', 'секунд'], true);
    $output = "";
    $output .= ($delta->format('%y') > 0 ? " $Y" : '');
    $output .= ($delta->format('%m') > 0 ? " $m" : '');
    $output .= ($delta->format('%d') > 0 ? " $d" : '');
    $output .= ($delta->format('%h') > 0 ? " $H" : '');
    $output .= ($delta->format('%i') > 0 ? " $i" : $s);
    return $output;
}

function secondsToTimeShort($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");

    $delta = $dtF->diff($dtT); //->format('%y years, %m monthes, %d days, %h hours, %i minutes and %s seconds');

    $Y = human_plural_form($delta->format('%y'), ['год', 'года', 'лет'], true);
    $m = human_plural_form($delta->format('%m'), ['месяц', 'месяца', 'месяцев'], true);
    $d = human_plural_form($delta->format('%d'), ['день', 'дня', 'дней'], true);
    $H = human_plural_form($delta->format('%h'), ['час', 'часа', 'часов'], true);
    $i = human_plural_form($delta->format('%i'), ['минута', 'минуты', 'минут'], true);
    $s = human_plural_form(($delta)->format('%s'), ['секунда', 'секунды', 'секунд'], true);

    $output = ($delta->format('%H')) . ':' . ($delta->format('%I'));
    return $output;
}

function secondsToTimeObj($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT); //->format('%y years, %m monthes, %d days, %h hours, %i minutes and %s seconds');
}

function human_plural_form($number, $titles = array('неделю', 'недели', 'недель'), $returnNumber = false) {
    $number = abs($number);
    /**
     * @param $number int число чего-либо
     * @param $titles array варинаты написания для количества 1, 2 и 5
     * @return string
     */
    $cases = array(2, 0, 1, 1, 1, 2);
    return ($returnNumber ? ($number . ' ') : '') . $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function batchInsert($cleanedArray) {
    $sql = [];
    foreach ($cleanedArray as $row) {
        $sql[] = '(' . implode(",", $row) . ')';
    }
    return implode(',' . "\r\n", $sql);
}

function mb_ucfirst($str) {
    if ($str) {
        $str = mb_strtolower($str);
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc . mb_substr($str, 1);
    }
    return '';
}

function GR($var = null, $val = null) {
    $query = [];
    parse_str($_SERVER['QUERY_STRING'], $query);

    if ($val !== 'null' && $val !== '') {
        $query[$var] = $val;
    } else {
        unset($query[$var]);
    }
    return $_SERVER['SCRIPT_NAME'] . '?' . http_build_query($query);
}

function GR2($arr = []) {
    $query = [];
    parse_str($_SERVER['QUERY_STRING'], $query);

    foreach ($arr as $key => $val) {
        if ($val !== 'null' && $val !== '') {
            $query[$key] = $val;
        } else {
            unset($query[$key]);
        }
    }

    return $_SERVER['SCRIPT_NAME'] . '?' . http_build_query($query);
}

function array_search_2d($needle, $haystack, $column) {
    global $_ARRAY_SEARCH_2D_TIME;
    $start = microtime(true);
    $index = array_search($needle, array_column($haystack, $column));
    if ($index === false) {
        return null;
    }
    $out = ($haystack[$index] ?? null);
    $_ARRAY_SEARCH_2D_TIME = ($_ARRAY_SEARCH_2D_TIME ?? 0) + microtime(true) - $start;
    return $out;
}

function clientIsNew($idclient, $date = null) {
    $date = $date ?? date("Y-m-d");
    global $clientIsNewFunc;
    if (isset($clientIsNewFunc[$idclient][$date])) {
        return $clientIsNewFunc[$idclient][$date];
    }
    $client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`= '" . $idclient . "' AND `clientsAddedAt`<='" . $date . " 23:59:59'"));

    if (!$client) {
        $clientIsNewFunc[$idclient][$date] = null;
        return null;
    }
    if ($client['clientsOldSince']) {
        $clientIsNewFunc[$idclient][$date] = $date <= $client['clientsOldSince'];
        return $date <= $client['clientsOldSince'];
    } else {
        $clientIsNewFunc[$idclient][$date] = true;
        return true;
    }
}

$strtotime = [];

function mystrtotime($str) {
    global $strtotime, $strtotime_cnt;
    $strtotime_cnt = ($strtotime_cnt ?? 0) + 1;
    if (!isset($strtotime[$str])) {
        $strtotime[$str] = strtotime($str);
    }
    return $strtotime[$str];
}

$___mydates = [];

function mydates($format, $timestamp = null) {

    global $___mydates, $___mydatesCnt;
    $___mydatesCnt = ($___mydatesCnt ?? 0) + 1;
    if (!isset($___mydates[$format][$timestamp])) {
        $___mydates[$format][$timestamp] = date($format, ($timestamp ?? time()));
    }
    return $___mydates[$format][$timestamp];
}

function ICQ_Api_SYNC_call($params = array(), $endpoint = '/messages/sendText', $buttons = false) {
    return false;
    global $link;
    $params['token'] = ICQAPIKEY;
    $url = "https://api.icq.net/bot/v1" . $endpoint . '?' . http_build_query($params);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if ($buttons) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, ['inlineKeyboardMarkup' => json_encode($buttons)]);
    }
    $json = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($json, true);
    if (($response['msgId'] ?? false) && !in_array(($params['chatId'] ?? ''), ['AoLF0rcsY9MXT89Io2U', 'sashnone', '751363572'])) {
        mysqlQuery("INSERT INTO `ICQmessagesSent` SET "
                . "`ICQmessagesSentMsgId` = '" . ($response['msgId'] ?? '') . "', "
                . "`ICQmessagesSentText` = '" . mysqli_real_escape_string($link, ($params['text'] ?? '')) . "',"
                . "`ICQmessagesSentChatId` = '" . ($params['chatId'] ?? '') . "'");
    }

    return $response;
}

//Функция для вызова messages.send
function ICQ_messagesSend_SYNC($peer_id, $message, $buttons = false) {
    return false;
    return ICQ_Api_SYNC_call(
            ['chatId' => $peer_id, 'text' => $message],
            '/messages/sendText',
            $buttons
    );
}

//Функция для вызова messages.send
function ICQ_actionSend_SYNC($peer_id, $actions) {
    return false;
    return ICQ_Api_SYNC_call(
            ['chatId' => $peer_id, 'actions' => $actions],
            '/chats/sendActions'
    );
}

function ICQMSDelay($delay_ms, $peer_id, $message) {
    return false;
//	print "\r\n\r\nICQMSDelay";
    global $_ROOTPATH;
//	print ">>>" . $_ROOTPATH . "<<<";
    $_ROOT = str_replace('/var/www/html/', '', $_ROOTPATH);
//	echo "/sync/api/icq/delayedMesage.php";
    exec("php -q " . $_ROOTPATH . "/sync/api/icq/delayedMesage.php root=" . $_ROOT . " delay=" . $delay_ms . " peerid=" . $peer_id . " message=" . escapeshellarg($message) . "> /dev/null &");
}

function number2string($num) {
    $nul = 'ноль';
    $ten = array(
        array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
        array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
    );
    $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
    $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
    $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
    $unit = array(// Units
        array('копейка', 'копейки', 'копеек', 1),
        array('рубль', 'рубля', 'рублей', 0),
        array('тысяча', 'тысячи', 'тысяч', 1),
        array('миллион', 'миллиона', 'миллионов', 0),
        array('миллиард', 'милиарда', 'миллиардов', 0),
    );
//
    list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
    $out = array();
    if (intval($rub) > 0) {
        foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
            if (!intval($v))
                continue;
            $uk = sizeof($unit) - $uk - 1; // unit key
            $gender = $unit[$uk][3];
            list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
// mega-logic
            $out[] = $hundred[$i1]; # 1xx-9xx
            if ($i2 > 1)
                $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];# 20-99
            else
                $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];# 10-19 | 1-9
// units without rub & kop
            if ($uk > 1)
                $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
        } //foreach
    } else
        $out[] = $nul;
    $out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
    if ($kop != '00') {
        $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
    }

    return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
}

function morph($n, $f1, $f2, $f5) {
    $n = abs(intval($n)) % 100;
    if ($n > 10 && $n < 20)
        return $f5;
    $n = $n % 10;
    if ($n > 1 && $n < 5)
        return $f2;
    if ($n == 1)
        return $f1;
    return $f5;
}

function getUsersByRights($rights) {
    return array_filter(query2array(mysqlQuery("SELECT *, (SELECT `usersActiveState` FROM `usersActive` WHERE `idusersActive` = (SELECT MAX(`idusersActive`) FROM `usersActive` WHERE `usersActiveUser` = `idusers`)) as `usersActiveState` "
                            . "FROM `usersRights`"
                            . " LEFT JOIN `users` ON (`idusers` = `usersRightsUser`)"
                            . "WHERE `idusersRights` IN (SELECT MAX(`idusersRights`) FROM `usersRights` WHERE `usersRightsRule` IN (" . implode(',', $rights) . ") AND isnull(`usersDeleted`) GROUP BY `usersRightsUser`)")), function ($element) {
                return $element['usersRightsValue'] && $element['usersDeleted'] === null && $element['usersActiveState'];
            });
}

function refine($obj, $array) {
    foreach ($obj as &$elem) {
        foreach ($elem as $key => $value) {
            if (!in_array($key, $array)) {
                unset($elem[$key]);
            }
        }
    }
    return $obj;
}

function httpGet($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function printArray($array) {
    $n = 0;
    print '<table border="1" cellpadding="3" style="hyphens: initial;
">
		<thead>
			<tr>';
    if (count($array)) {
        print "<th>#</th>";
        foreach ($array as $row) {
            foreach ($row as $key => $value) {
                print "<th>$key</th>";
            }
            break;
        }
    }
    print '</tr>
	</thead>
	<tbody>';
    foreach ($array as $row) {
        print '<tr>';
        print '<td>' . (++$n) . '</td>';

        foreach ($row as $column) {
            print '<td>';
            if (is_array($column)) {
                printr($column);
            } else {
                print $column;
            }
            print '</td>';
        }
        print '</tr>';
    }
    print '</tbody>
	</table>';
}

function getRemainsCountByClient($idclient) {
    return mfa(mysqlQuery("SELECt `remainsQty` FROM `remains` WHERE `remainsClient` = '" . $idclient . "' and `remainsDate` = (SELECT MAX(`remainsDate`) FROM `remains` WHERE `remainsClient` = '" . $idclient . "')"))['remainsQty'] ?? 0;
}

function getRemainsByClient($idclient) {
    if ($idclient) {
        $subscriptions = query2array(mysqlQuery("SELECT "
                        . "SUM(`f_salesContentQty`) as `f_salesContentQty`, "
                        . "`f_salesContentService`, "
                        . "`f_salesDate`, "
                        . "`servicesName`, "
                        . "`f_salesContentPrice`, "
                        . "`f_subscriptionsContract` "
                        . " FROM `f_subscriptions`"
                        . " LEFT JOIN `f_sales` ON (`idf_sales` = `f_subscriptionsContract`)"
                        . " LEFT JOIN `services` on (`idservices` = `f_salesContentService`)"
                        . " WHERE `f_salesClient` = '" . $idclient . "' "
                        . " AND isnull(`f_salesCancellationDate`)"
                        . " GROUP BY `f_salesContentService`, `f_salesContentPrice`, `f_subscriptionsContract`;
"));
        $servicesApplied = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `servicesAppliedClient` = '" . $idclient . "' AND NOT isnull(`servicesAppliedContract`) AND isnull(`servicesAppliedDeleted`)"));
        foreach ($subscriptions as &$subscription2) {
            $filtered = array_filter($servicesApplied, function ($serviceApplied) use ($subscription2) {
                $result = $subscription2['f_salesContentService'] == $serviceApplied['servicesAppliedService'] &&
                        $subscription2['f_subscriptionsContract'] == $serviceApplied['servicesAppliedContract'] &&
                        $subscription2['f_salesContentPrice'] == $serviceApplied['servicesAppliedPrice']
                ;

                return $result;
            });
            $subscription2['f_salesContentQty'] -= array_sum(array_column($filtered, 'servicesAppliedQty'));
        }

        $qty = array_sum(array_column($subscriptions, 'f_salesContentQty'));
        mysqlQuery("INSERT INTO `remains` SET `remainsClient` = '" . $idclient . "', `remainsDate` = CURDATE(), `remainsQty` = '" . ($qty ?? 0) . "' ON duplicate key update `remainsQty` = '" . ($qty ?? 0) . "'");

        return($subscriptions);
    }
}

function getRemainsBySale($idsale) {
    if ($idsale) {
        $subscriptions = query2array(mysqlQuery("SELECT "
                        . "SUM(`f_salesContentQty`) as `f_salesContentQty`, "
                        . "`f_salesContentService`, "
                        . "`servicesName`, "
                        . "`f_salesContentPrice`, "
                        . "`f_subscriptionsContract` "
                        . " FROM `f_subscriptions`"
                        . " LEFT JOIN `services` on (`idservices` = `f_salesContentService`)"
                        . " WHERE `f_subscriptionsContract` = '" . $idsale . "'"
                        . " GROUP BY `f_salesContentService`, `f_salesContentPrice`, `f_subscriptionsContract`"
                        . " "));
        $servicesApplied = query2array(mysqlQuery("SELECT * FROM `servicesApplied` WHERE `servicesAppliedContract` = '" . $idsale . "' AND isnull(`servicesAppliedDeleted`)"));
        foreach ($subscriptions as &$subscription2) {
            $filtered = array_filter($servicesApplied, function ($serviceApplied) use ($subscription2) {
                $result = $subscription2['f_salesContentService'] == $serviceApplied['servicesAppliedService'] &&
                        $subscription2['f_subscriptionsContract'] == $serviceApplied['servicesAppliedContract'] &&
                        $subscription2['f_salesContentPrice'] == $serviceApplied['servicesAppliedPrice']
                ;

                return $result;
            });
            $subscription2['f_salesContentQty'] -= array_sum(array_column($filtered, 'servicesAppliedQty'));
        }
        return($subscriptions);
    }
}

function get2hidden() {
    foreach ($_GET as $key => $value) {
        print '<input type="hidden" name="' . $key . '" value="' . $value . '">';
    }
}

function smsTemplate($template, $data) {
    foreach ($data as $key => $value) {
        $template = str_replace('#' . $key, $value, $template);
    }
    return $template;
}

function str_split_unicode($str, $l = 0) {
    if (strlen($str) > 1000000) {
        return ['error'];
    }
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

//sendSticker
function sendTelegram($method, $data, $headers = []) {
//	return false;
    $var_info = print_r($data, true);
//	if (strlen($var_info) > 10000 && ($handle = fopen('/var/www/html/public/pages/files/uploads/176/' . microtime(1) . '.log', 'a'))) {
//		fwrite($handle, $var_info);
//		fclose($handle);
//	}
    if ($method == 'sendMessage') {
        $GUID = exec('uuidgen -r');
        $textArray = str_split_unicode($data['text'], 4000);
        $n = 0;
        mysqlQuery("INSERT INTO `telegramTexts` SET"
                . " `telegramTextsGUID` = '" . $GUID . "',"
                . " `telegramTextsText` = '" . mres($data['text']) . "'"
        );
        foreach ($textArray as $msg) {
            $n++;
            $data['parse_mode'] = 'html';
            $data['text'] = (count($textArray) > 1 ? ("[" . $n . "/" . count($textArray) . "]\n") : '') . ($msg);
            mysqlQuery("INSERT INTO `telegramQuery` SET "
                    . (($data['chat_id'] ?? false) ? ("`telegramQueryChatId`='" . mres($data['chat_id']) . "',") : "")
                    . " `telegramQueryMethod` = '" . $method . "',"
                    . " `telegramQueryMessageGUID` = '" . $GUID . "',"
                    . " `telegramQueryData`='" . mres(json_encode($data, JSON_UNESCAPED_UNICODE)) . "'"
                    . "");
        }

        return false;
    }

    if ($method == 'sendSticker') {
        mysqlQuery("INSERT INTO `telegramQuery` SET"
                . (($data['chat_id'] ?? false) ? ("`telegramQueryChatId`='" . mres($data['chat_id']) . "',") : "")
                . "`telegramQueryMethod` = '" . $method . "',"
                . "`telegramQueryData`='" . mres(json_encode($data)) . "'"
                . "");
        return false;
    }




//	 ['text' => $text . "\r\nUSER: " . $_USER['idusers'], 'chat_id' => '-597902452']
    $curl = curl_init('https://api.telegram.org/bot' . TGKEY . '/' . $method);
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $headers)
    ]);
    $result = curl_exec($curl);
    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}

function sqlVON($value, $countEptyAsNull = false) {
    if ($value == null) {
        return "null";
    }
    if ($countEptyAsNull && trim($value) == '') {
        return "null";
    }

    return "'" . mres(trim($value)) . "'";
}

function telegramSendByRights($rightsArray = [], $messageText = '') {
    foreach (getUsersByRights($rightsArray) as $user) {
        if ($user['usersTG'] ?? false) {
            sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => $messageText]);
        }
    }
}

function dates($from, $to) {
    $curdate = min($from, $to);
    $output = [$curdate];
    $n = 0;
    while ($curdate < max($from, $to) && $n < 1000) {
        $curdate = date("Y-m-d", strtotime($curdate . ' +1 day'));
        $output[] = $curdate;
    }
    return $output;
}

function GUID() {
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    } return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function getWeekdays($m, $y) {
    //t 	Number of days in the given month 	28 through 31
    //w 	Numeric representation of the day of the week 	0 (for Sunday) through 6 (for Saturday)
    $lastday = date("t", mktime(0, 0, 0, $m, 1, $y));
    $weekdays = 0;
    for ($d = 29; $d <= $lastday; $d++) {
        $wd = date("w", mktime(0, 0, 0, $m, $d, $y));
        if ($wd > 0 && $wd < 6) {
            $weekdays++;
        }
    }
    return $weekdays + 20;
}

function paymentsSumm($payments, $idpayment, $half = false) {
//			printr($payments, 1);
    if ($half === 1) {
        $payments['dates'] = array_filter($payments['dates'] ?? [], function ($elem, $key) {
            return date("j", strtotime($key)) <= 15;
        }, ARRAY_FILTER_USE_BOTH);
//				printr($payments);
    }
    if ($half === 2) {
        $payments['dates'] = array_filter($payments['dates'] ?? [], function ($elem, $key) {
            return date("j", strtotime($key)) >= 16;
        }, ARRAY_FILTER_USE_BOTH);
//				printr($payments);
    }

    return round(array_reduce($payments['dates'] ?? [], function ($carry, $item) use ($idpayment) {
                return $carry + ($item[$idpayment]['reward'] ?? 0);
            }, 0));
}

function replaceInTemplate($template, $dataArray) {
    $matches = [];
    preg_match_all('~\[(.*?)\]~s', $template, $matches);
    foreach ($matches[1] as $key => $value) {
        if ($dataArray[$value] ?? false) {
            $template = str_replace($matches[0][$key], $dataArray[$matches[1][$key]], $template);
        }
    }
    return $template;
}

function logTG($text) {
    $backtrace = debug_backtrace();
    $bt = [];
    foreach ($backtrace as $stackLevel) {
        $bt['file'] = preg_replace('|' . addslashes($_SERVER['DOCUMENT_ROOT']) . '|i', '', $stackLevel['file'] ?? '') . '?' . ($_SERVER['QUERY_STRING'] ?? '');
        $bt['line'] = $stackLevel['line'] ?? '';
    }
    sendTelegram('sendMessage', ['chat_id' => '-836360346', 'text' => $bt['file'] . "\n" . $bt['line'] . "\n\n" . $text]);
}

include $_ROOTPATH . "/sync/includes/infinitimedbot.php";

