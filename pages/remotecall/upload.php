<?php
$pageTitle = 'Удалённый коллцентр';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(35)) {
	if (!empty($_FILES)) {
		$file = file($_FILES['phoneDataBase']['tmp_name']);
//		printr($_POST);
//		[iddatabase] => new
//		[phonesBasesNameShort] => ЮлБор
//		[phonesBasesName] => 08.04 Юлия Борменталь
		if ($_POST['iddatabase'] == 'new') {
			if ($pdb = mfa(mysqlQuery("SELECT * FROM `RCC_phonesBases` WHERE `RCC_phonesBasesNameShort` = '" . mres($_POST['phonesBasesNameShort']) . "' AND `RCC_phonesBasesName`='" . mres($_POST['phonesBasesName']) . "'"))) {
				$_POST['iddatabase'] = $pdb['idRCC_phonesBases'];
			} else {
				mysqlQuery("INSERT INTO `RCC_phonesBases` SET"
						. " `RCC_phonesBasesNameShort` = '" . mres($_POST['phonesBasesNameShort']) . "',"
						. " `RCC_phonesBasesName`='" . mres($_POST['phonesBasesName']) . "',"
						. " `RCC_phonesBasesAddedBy`='" . $_USER['id'] . "'");
				$_POST['iddatabase'] = mysqli_insert_id($link);
			}
		}
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
				"'" . FSS(mb_convert_encoding(($rowData[1] ?? ''), "utf-8", "windows-1251")) . "'",
				$ageOnlyDigits ? FSI($ageOnlyDigits) : 'null',
				mres($_POST['iddatabase'])
			];
		}
		//mysqlQuery("delete FROM warehouse.RCC_phones;");
		if (($_POST['refresh'] ?? false) === '1') {
			$insertSQL = "INSERT INTO `RCC_phones`(`RCC_phonesNumber`,`RCC_phonesLName`,`RCC_phonesAge`,`RCC_phonesBase`) VALUES " . batchInsert($data) . " "
					. " ON DUPLICATE KEY UPDATE"
					. " `RCC_phonesClaimedBy`=null,"
					. " `RCC_phonesClaimedAt`=null,"
					. " `RCC_phonesBase`= '" . $_POST['iddatabase'] . "'"
					. ";";
		} else {
			$insertSQL = "INSERT IGNORE INTO `RCC_phones`(`RCC_phonesNumber`,`RCC_phonesLName`,`RCC_phonesAge`,`RCC_phonesBase`) VALUES " . batchInsert($data) . ";";
		}


		mysqlQuery($insertSQL);
		$added = mysqli_affected_rows($link);
		$count = count($data);
		mysqlQuery("DELETE FROM `RCC_phones` WHERE `RCC_phonesNumber` IN (SELECT `clientsPhonesPhone` FROM `clientsPhones`) AND isnull(`RCC_phonesClaimedBy`)");
		$deleted = mysqli_affected_rows($link);
		mysqlQuery("UPDATE `RCC_phonesBases` SET `RCC_phonesBasesFresh` = (select count(1) from `RCC_phones` where `RCC_phonesBase` = `idRCC_phonesBases`) WHERE `idRCC_phonesBases` = '" . $_POST['iddatabase'] . "'");

		header("Location: /pages/remotecall/upload.php?pbdupload&added=$added&count=$count&deleted=$deleted&database=" . $_POST['iddatabase'] . '&filename=' . $_FILES['phoneDataBase']['name']);
		die();
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(35)) {
	?>E403R35<?
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

	include 'menu.php';
	?>




	<? if (R(40)) { ?>
		<div class="box neutral">
			<div class="box-body">
				<h2>Загрузка телефонной базы</h2>
				<h3>Формат файла: телефон;фио;возраст</h3>
				<form action="/pages/remotecall/upload.php?pbdupload" method="post" enctype="multipart/form-data">
					<div style="padding: 10px;">
						<select name="iddatabase" onchange="if (this.value == 'new') {
									qs('#newdbdiv').style.display = 'grid';
								} else {
									qs('#newdbdiv').style.display = 'none';
								}">
							<option value="">Выбрать/создать</option>
							<option value="new">Новая</option>
							<?
							foreach (query2array(mysqlQuery("SELECT * FROM RCC_phonesBases;")) as $phoneBase) {
								?><option value="<?= $phoneBase['idRCC_phonesBases']; ?>">(<?= $phoneBase['RCC_phonesBasesNameShort']; ?>) <?= $phoneBase['RCC_phonesBasesName']; ?></option><?
							}
							?>

						</select>
					</div>
					<div style="padding: 10px;">
						<div id="newdbdiv" style="display: none; grid-template-columns: 1fr 3fr;"><input type="text" maxlength="10" name="phonesBasesNameShort" placeholder="сокращённо"><input type="text" maxlength="145" name="phonesBasesName" placeholder="Название базы"></div>
					</div>

					<input type="file" name="phoneDataBase" accept=".csv">
					<input type="hidden" name="refresh" value="0">
					<div style="margin: 10px;"><input type="checkbox" value="1" name="refresh" id="refresh"><label for="refresh"> Обновить</label></div>
					<input type="submit">
				</form>
				<div style="padding: 20px;">
					<?
					if (isset($_GET['count']) && isset($_GET['added']) && FSI($_GET['count']) > 0) {
						print "В первичную базу " . human_plural_form($_GET['added'], ['добавлен ', 'добавлено ', 'добавлено ']) . human_plural_form($_GET['added'], [' новый телефон', ' новых телефона', ' новых телефонов'], true)
								. ' без дублей,<br> а в последствии удалено ' . human_plural_form($_GET['deleted'], ['телефон', 'телефона', 'телефонов'], true)
								. ', которые уже были во вторичной телефонной базе. <br>'
								. ' В файле было <b>' . human_plural_form($_GET['count'], ['телефон', 'телефона', 'телефонов'], true)
								. '</b>, в итоге загрузилось <b>' . human_plural_form(($_GET['added'] - $_GET['deleted']), ['телефон', 'телефона', 'телефонов'], true) . '</b>  <br> Новых в телефонных номеров в файле <b>' . ($_GET['filename'] ?? 'filename') . '</b> было примерно <b>' . round((($_GET['added'] - $_GET['deleted']) / $_GET['count']) * 100) . '</b>%.<br><br> Предпросмотр:';
						$sample = query2array(mysqlQuery("SELECT `RCC_phonesNumber` as `Телефон`,`RCC_phonesLName` AS `ФИО` FROM `RCC_phones` WHERE `RCC_phonesBase` = '" . mres($_GET['database']) . "' LIMIT 20"));
						printArray($sample);
					}
					?>
				</div>
			</div>
		</div>

	<? } ?>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
