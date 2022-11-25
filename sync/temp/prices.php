<?php
$pageTitle = 'сетка';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<div class="box neutral">
	<div class="box-body">
		<?
		$services = query2array(mysqlQuery("SELECT *, "
						. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<= NOW() AND `servicesPricesType`='1' AND `servicesPricesService` = `idservices`)) as `priceMin`,"
						. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`<=NOW() AND `servicesPricesType`='2' AND `servicesPricesService` = `idservices`)) as `priceMax`"
						. " FROM `services`"
						. " WHERE isnull(`servicesDeleted`)"
						. " AND `servicesType`='4'"
						. ""));
		print count($services);
		$newPrices = [];

		function priceChange($oldPrice) {

			$newPrice = round($oldPrice * 1.1);
			$newPrice = $newPrice + (50 - $newPrice % 50);
			return $newPrice;
		}

		foreach ($services as $service) {
			if ($service['priceMin']) {
				$newPrices[] = [
					'servicesPricesService' => $service['idservices'],
					'servicesPricesPrice' => priceChange($service['priceMin']),
					'servicesPricesType' => 1,
					'servicesPricesSetBy' => 176
				];
			}


			if ($service['priceMax']) {
				$newPrices[] = [
					'servicesPricesService' => $service['idservices'],
					'servicesPricesPrice' => priceChange($service['priceMax']),
					'servicesPricesType' => 2,
					'servicesPricesSetBy' => 176
				];
			}
		}

		print ("INSERT INTO `servicesPrices` (`servicesPricesService`, `servicesPricesPrice`, `servicesPricesType`, `servicesPricesSetBy`) VALUES " . batchInsert($newPrices));
//		mysqlQuery("INSERT INTO `servicesPrices` (`servicesPricesService`, `servicesPricesPrice`, `servicesPricesType`, `servicesPricesSetBy`) VALUES " . batchInsert($newPrices));
		?>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
