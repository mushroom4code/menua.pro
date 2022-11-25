<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(35)) {
  if (!empty($_FILES)) {
	 $file = file($_FILES['phoneDataBase']['tmp_name']);

	 $data = [];
	 foreach ($file as $fileRow) {
		$rowData = explode(";", $fileRow);
		if (isset($rowData[2])) {
		  $ageRaw = mb_convert_encoding($rowData[2], "utf-8", "windows-1251");
		  $ageOnlyDigits = preg_replace("/[^0-9]/", "", $ageRaw);
		} else {
		  $ageOnlyDigits = null;
		}
		if (!$ageOnlyDigits) {
		  $ageOnlyDigits = null;
		}

		$phoneNumber = preg_replace("/[^0-9]/", "", $rowData[0] ?? '');

		if (strlen($phoneNumber) == 11) {
		  $phoneNumber[0] = '8';
		} elseif (strlen($phoneNumber) == 10) {
		  $phoneNumber = '8' . $phoneNumber;
		}

		if (!$phoneNumber) {
		  continue;
		}

		$data[] = [
			 $phoneNumber
		];
	 }
	 $result = mfa(mysqlQuery("SELECT "
						  . "(SELECT COUNT(1) FROM `RCC_phones` WHERE `RCC_phonesNumber` IN (" . implode(', ', array_filter(array_column($data, '0'))) . ")) AS `I`,"
						  . "(SELECT COUNT(1) FROM `clientsPhones` WHERE `clientsPhonesPhone` IN (" . implode(', ', array_filter(array_column($data, '0'))) . ")) AS `II`"
						  . ""));
//	 printr($result);
  }
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(35)) {
  ?>E403R35<?
} else {
  include 'menu.php';
  ?>
  <div class="box neutral">
    <div class="box-body">
    	 <h2>Загрузка телефонной базы</h2>
    	 <h3 style="padding: 20px; color: red;">Формат файла CSV: телефон;фио;</h3>
    	 <form action="?" method="post" enctype="multipart/form-data">
  		<input type="file" name="phoneDataBase" accept=".csv">
  		<input type="submit">
    	 </form>
		<? if ($result ?? false) {
		  ?>
	 	 <div style="padding: 20px; margin: 20px; background-color: white; border: 2px solid silver;">
	 		В первичной телефонной базе найдено <?= human_plural_form($result['I'], ['номер', 'номера', 'номеров'], 1); ?> (<?= round(100 * $result['I'] / count($data)); ?>%)<br>
	 		В базе клиентов найдено <?= human_plural_form($result['II'], ['номер', 'номера', 'номеров'], 1); ?> (<?= round(100 * $result['II'] / count($data)); ?>%)<br>
	 	 </div>
		  <?
		}
		?>
    </div>
  </div>
<? } ?>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
