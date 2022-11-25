<?php
$pageTitle = $load['title'] = 'Импорт анализов HELIX';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<div class="box neutral">
	<div class="box-body">

		<?php
		$database = [];
		if (($handle = fopen("helix.csv", "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {

				foreach ($data as $nc => $column) {
					$data[$nc] = iconv("windows-1251", "UTF-8", $column);
				}
				$database[] = $data;
			}
			fclose($handle);
		}
		foreach ($database as $row) {
//1931
			if (!$row[1]) {
				if (!($group = mfa(mysqlQuery("SELECT * FROM `services` WHERE `servicesName`='" . mres($row[0]) . "'")))) {
					mysqlQuery("INSERT INTO `services` SET  `servicesName`='" . mres($row[0]) . "',`servicesParent`='1931'");
					print 'NEW' . $row[0];
					$groupid = mysqli_insert_id($link);
				} else {
					printr($group);
					$groupid = $group['idservices'];
					print 'exist' . $row[0];
				}
			} else {

				if (!$service = mfa(mysqlQuery("SELECT * FROM `services` WHERE `servicesName`='" . mres($row[1]) . "'"))) {
					mysqlQuery("INSERT INTO `services` SET `servicesSupplierCode`='" . mres($row[0]) . "', `servicesName`='" . mres($row[1]) . "',`servicesParent`='" . $groupid . "'");
					$idservices = mysqli_insert_id($link) ? mysqli_insert_id($link) : 'NULL';
					print '<br>';
					mysqlQuery("INSERT INTO `TPSn_prices` SET "
							. " `TPSn_pricesService`='" . $idservices . "',"
							. " `TPSn_pricesDate`='" . date("Y-m-d") . "',"
							. " `TPSn_pricesValue` = '" . round(str_replace(' ', '', str_replace(',', '.', $row[3]))) . "'");
					print '<br>';
					$price = round(floatval(str_replace(' ', '', str_replace(',', '.', $row[5]))));
					mysqlQuery("INSERT INTO `servicesPrices` SET "
							. " `servicesPricesService`='" . $idservices . "',"
							. " `servicesPricesDate`='" . date("Y-m-d") . "',"
							. " `servicesPricesPrice` = " . ($price ? $price : 'NULL') . ","
							. " `servicesPricesType` = '1'");
//					print 'NEW' . $row[1];
					printr($row);
				}
			}
			/*
			 * servicesSupplierCode
			  [
			  "02-001",
			  "Анализ кала на скрытую кровь",
			  "медицинская",
			  "100,00",
			  "1 сутки",
			  330,
			  "330%",
			  "",
			  330
			  ] */
			?>

			<?
		}
		?>	

	</div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
