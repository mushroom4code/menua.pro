<?php
$load['title'] = $pageTitle = 'Слияние процедур';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (($_POST['A'] ?? false) && ($_POST['B'] ?? false)) {
	$servicesApplied = query2array(mysqlQuery("SELECT idservicesApplied, servicesAppliedService FROM `servicesApplied` WHERE `servicesAppliedService` = '" . mysqli_real_escape_string($link, $_POST['B']) . "'"));
	if (count($servicesApplied)) {
		$SA = count($servicesApplied);
		$fp = fopen('backup/' . date("YmdHis") . 'SA' . $_POST['B'] . '.csv', 'w');
		foreach ($servicesApplied as $fields) {
			fputcsv($fp, $fields, ';');
		}
		fclose($fp);
		mysqlQuery("UPDATE `servicesApplied` SET `servicesAppliedService` = '" . mysqli_real_escape_string($link, $_POST['A']) . "' WHERE  `servicesAppliedService` = '" . mysqli_real_escape_string($link, $_POST['B']) . "' ");
	}
	$f_subscriptions = query2array(mysqlQuery("SELECT idf_subscriptions, f_salesContentService FROM `f_subscriptions` WHERE `f_salesContentService` = '" . mysqli_real_escape_string($link, $_POST['B']) . "'"));
	if (count($f_subscriptions)) {
		$FS = count($f_subscriptions);
		$fp = fopen('backup/' . date("YmdHis") . 'FS' . $_POST['B'] . '.csv', 'w');
		foreach ($f_subscriptions as $fields) {
			fputcsv($fp, $fields, ';');
		}
		fclose($fp);
		mysqlQuery("UPDATE `f_subscriptions` SET `f_salesContentService` = '" . mysqli_real_escape_string($link, $_POST['A']) . "' WHERE  `f_salesContentService` = '" . mysqli_real_escape_string($link, $_POST['B']) . "' ");
	}
	mysqlQuery("UPDATE `services` SET `servicesDeleted` = NOW() WHERE `idservices` = '" . mysqli_real_escape_string($link, $_POST['B']) . "'");
//	header("Location: " . GR());
//	die();
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
	<div class="box-body">
		Пройдено: <?= $SA ?? 'нет'; ?><br>
		В абонементах: <?= $FS ?? 'нет'; ?>
		<form method="post">
			<table>
<!--				<tr>
					<th>
						Услуга А
					</th>
					<th>
						Услуга В
					</th>
				</tr>-->
				<tr>

					<td style="text-align: center;">
						<input type="text" name="B" id="theB" style="width: auto; display: block;" oninput="digon();">
						Удаляем
					</td>
				<script>
					qs('#theB').focus();
				</script>
				<td style="text-align: center;">
					<input type="text" name="A" style="width: auto; display: block;" oninput="digon();">
					Оставляем
				</td>
				</tr>
				<tr><td colspan="2" class="C"><input type="submit" value="Выполнить"></td></tr>
			</table>
		</form>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
