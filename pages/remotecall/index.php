<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(35) || array_search_2d(32, ($_USER['positions'] ?? []), 'id')) {

}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!(R(35) || array_search_2d(32, ($_USER['positions'] ?? []), 'id'))) {
	?>E403P32<?
} else {



//(
//    [phoneDataBase] =>
//        (
//            [name] => Книга1.csv
//            [type] => application/vnd.ms-excel
//            [tmp_name] => /tmp/phpTqIYOl
//            [error] => 0
//            [size] => 588048
//        )
//
//)



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
				$phoneNumber,
				"'" . FSS(mb_convert_encoding($rowData[1] ?? '', "utf-8", "windows-1251")) . "'",
				$ageOnlyDigits ? FSI($ageOnlyDigits) : 'null'
			];
		}
		//mysqlQuery("delete FROM warehouse.RCC_phones;");
		$insertSQL = "INSERT IGNORE INTO `RCC_phones`(`RCC_phonesNumber`,`RCC_phonesLName`,`RCC_phonesAge`) VALUES " . batchInsert($data) . ";";
		mysqlQuery($insertSQL);
		$added = mysqli_affected_rows($link);
		$count = count($data);
		header("Location: /pages/remotecall/upload.php?pbdupload&added=$added&count=$count");
	}
	?>


	<?
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/remotecall/menu.php';
	?>
	<? if (R(40) && isset($_GET['pbdupload'])) { ?>
		<div class="box neutral">
			<div class="box-body">
				<h2>Загрузка телефонной базы</h2>
				телефон;фио;возраст
				<form action="/pages/remotecall/upload.php?pbdupload" method="post" enctype="multipart/form-data">
					<input type="file" name="phoneDataBase" accept=".csv">
					<input type="submit">
				</form>
				<?
				if (isset($_GET['count']) && isset($_GET['added']) && FSI($_GET['count']) > 0) {
					print "Добавлено " . human_plural_form($_GET['added'], ['новый телефон', 'новых телефона', 'новых телефонов'], true) . ' из ' . human_plural_form($_GET['count'], ['телефон', 'телефона', 'телефонов'], true) . '<br> содержащихся в файле. Это примерно ' . round(($_GET['added'] / $_GET['count']) * 100) . '%.';
				}
				?>
			</div>
		</div>

	<? } ?>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
