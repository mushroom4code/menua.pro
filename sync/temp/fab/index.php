<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

function fixPhone($phoneInput) {
	if (!$phoneInput) {
		return null;
	}
	$phone = preg_replace("/[^0-9]/", "", trim($phoneInput));
	if (strlen($phone) == 11) {
		$phone[0] = '8';
		return $phone;
	} elseif (strlen($phone) == 10) {
		return '8' . $phone;
	}
	return null;
}

$clients = query2array(mysqlQuery("SELECT `idclients`,`clientsPhonesPhone` FROM `clientsPhones` LEFT JOIN `clients` ON (`idclients`=`clientsPhonesClient`)"));



include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(27)) {
	?>E403R27<?
} else {

	$file = file('fab.csv');
	$output = [];

	function array_search_2d_and_remove($needle, &$haystack, $column) {
		$index = array_search($needle, array_column($haystack, $column));
		if ($index === false) {
			return null;
		}
		$out = ($haystack[$index]);
		unset($haystack[$index]);
		return $out;
	}
	?>
	<div class="box neutral">
		<div class="box-body">
			<?
			$statuses;
			$n = 0;
			$start = microtime(1);
			$sts;
			foreach ($file as &$fileRow) {
				$n++;
				$fileRowArr = explode(';', $fileRow);
				foreach ($fileRowArr as &$column2) {
					$column2 = iconv("cp1251", "utf-8", $column2);
				}

				$fileRowArr[5] = fixPhone($fileRowArr[5]);

				$fileRowArr[6] = array_search_2d($fileRowArr[5], $clients, 'clientsPhonesPhone')['idclients'] ?? '';
				if ($fileRowArr[6]) {
					$fileRowArr[6] = 'https://menua.pro/pages/offlinecall/schedule.php?client=' . $fileRowArr[6];
				}
				if ($fileRowArr[5]) {
					$statuses[$fileRowArr[3]][] = $fileRowArr;
				}

				$sts[floor($n / 1000)] = microtime(1) - $start;
//				printr($fileRowArr);
				if ($n > 2000) {
//					break;
				}
			}


			foreach ($statuses as $statusName => $statusArray) {

				$fp = fopen('colored/' . $statusName . '.csv', 'w');

				foreach ($statusArray as $fields) {
					foreach ($fields as &$column3) {
						$column3 = iconv("utf-8", "cp1251", $column3);
					}
					fputcsv($fp, $fields, ";");
				}

				fclose($fp);
			}
			printr($sts);
			printr(microtime(1) - $start);
			?>

		</div>
	</div>

<? } ?>Ok

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';


























