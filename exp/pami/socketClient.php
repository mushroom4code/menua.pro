<?php

//$redis = new Redis();
//$redis->connect('127.0.0.1', 6379);

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . ($_GET['root'] ?? '');
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
$socket = fsockopen("192.168.128.100", "5038", $errno, $errstr, 10);
if (!$socket) {
	echo "$errstr ($errno)\n";
} else {
	fputs($socket, "Action: Login\r\n");
	fputs($socket, "UserName: manager\r\n");
	fputs($socket, "Secret: manager\r\n\r\n");
	fputs($socket, "Action: WaitEvent\r\n");
//	fputs($socket, "Action: Logoff\r\n\r\n");
	echo "socket kinda work\n";
	while (!feof($socket)) {
		$result = fread($socket, 50000);
		$events = parse($result);
//		print_r();

		foreach ($events as $event) {
//			print microtime(1) . "\n";
//			print json_encode($event, 288 + 128) . "\n";
//			mysqlQuery("INSERT INTO `amievents` SET"
//					. " `amieventsEvent`=" . sqlVON($event['Event'] ?? null) . ","
//					. " `amieventsUniqueid`=" . sqlVON($event['Uniqueid'] ?? $event['UniqueID'] ?? null) . ","
//					. " `amieventsData`='" . mres(json_encode($event, 288 + 128)) . "'");
			if (in_array(($event['ChannelState'] ?? ''), [4])) {
//				"": "",
				$phones = [];
				$client = null;
				$local = $event['ConnectedLineNum'] ?? $event['ConnectedLineName'] ?? null;
				$remote = $event['CallerIDNum'] ?? null;

				if ($remote) {
					if (strlen($remote) == 10) {
						$remote = '8' . $remote;
					} elseif (strlen($remote) == 11) {
						$remote[0] = '8';
					}

					if (strlen($remote) == 11) {

						//НОВЫЙ ПЛАН
						//
//						echo '🔔 Ringing: ' . ($remote) . "\n";
						$phones = query2array(mysqlQuery(""
										. "SELECT *,'warehouse' as `database` FROM `warehouse`.`clientsPhones` WHERE `clientsPhonesPhone`='" . mres($remote) . "' AND isnull(`clientsPhonesDeleted`) UNION ALL "
										. "SELECT *,'vita' as `database` FROM `vita`.`clientsPhones` WHERE `clientsPhonesPhone`='" . mres($remote) . "' AND isnull(`clientsPhonesDeleted`)"
										. "; "));
						if (!count($phones)) {
							mysqlQuery("INSERT INTO `incomeCalls` SET `incomeCallsPhone`='" . mres($remote) . "'");
							telegramSendByRights(['182'], '🔔 Входящий звонок (' . ( $remote ?? 'unknown') . ') клиент не найден');
						}
					}
				} else {
					echo '?? Ringing: ' . ( $remote ?? 'unknown') . ' to ' . ( $local ?? 'unknown') . "\n";
//					ICQ_messagesSend_SYNC('sashnone', 'Ringing: ' . ( $remote ?? 'unknown') . ' to ' . ( $local ?? 'unknown'));
				}
			}
//	print_r($event);
		}

//		[0] => Array
//		(
//		[ChannelState] => 5
//		[ChannelStateDesc] => Ringing
//		[CallerIDNum] => 218
//		[CallerIDName] =>
//		[ConnectedLineNum] => 9052084769
//		[ConnectedLineName] => 9052084769
//		[Uniqueid] => 1611741487.66314
//		)



		sleep(0.01);

//		$fromRedis = $redis->lPop('incomeCall');
//		if ($fromRedis !== false) {
//			echo "I've got " . var_dump($fromRedis, 1) . " fromRedis\n";
//			$fromRedisArray = json_decode($fromRedis, true);
//		}
	}
	echo "Socket closed\n";
}
fclose($socket);
echo "yep, it is.\n";

//6122063
function parse($string) {
	$parse = [];
	$result = explode("\r\n", $string);
	end($result);
	$count1 = key($result);
	$result2 = array_slice($result, 3, $count1);
	$result3 = array_slice($result2, 0, $count1 - 8);
	array_shift($result);

	$result4 = implode(';', $result3);
	$result5 = explode(';;', $result4);
	end($result5);
	$count2 = key($result5);
	for ($i = 0; $i <= $count2; ++$i) {
		$item = explode(';', $result5[$i]);
		foreach ($item as &$val) {
			list($k, $v) = array_pad(explode(': ', $val, 2), 2, null);
			$parse[$i][$k] = $v;
		}
	}
	return $parse;
}

?> 