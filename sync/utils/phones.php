<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

die();

$clientsPhones = query2array(mysqlQuery("SELECT * FROM `clientsPhones`"));
?>
<table style="border: 1px solid black;"><?
	foreach ($clientsPhones as $clientsPhone) {
		$updateNeed = false;
		$phone = preg_replace("/[^0-9]/", "", trim($clientsPhone['clientsPhonesPhone']) ?? '');
		if (strlen($phone) == 10) {
			$phone = '8' . $phone;
			$updateNeed = true;
		} elseif (strlen($phone) == 11 && $phone[0] != '8') {
			$phone[0] = '8';
			$updateNeed = true;
		}
		if ($updateNeed) {
			?>
			<tr>
				<td><?= mysqlQuery("UPDATE `clientsPhones` SET `clientsPhonesPhone` = '$phone' WHERE `idclientsPhones` = '" . $clientsPhone['idclientsPhones'] . "'"); ?></td>
				<td><?= strlen($phone); ?></td>
				<td><?= $phone; ?></td>
				<td><?= $updateNeed; ?></td>
				<td><?= $clientsPhone['clientsPhonesInvalid']; ?></td>
			</tr>
			<?
		}
	}
	?>
</table>


<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
