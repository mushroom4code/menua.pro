<?php
$pageTitle = 'Перенос абонементов';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (
		($_POST['sourceDatabase'] ?? false) &&
		($_POST['sourceSale'] ?? false) &&
		($_POST['targetDatabase'] ?? false) &&
		($_POST['targetClient'] ?? false)
) {
	$sales = query2array(mysqlQuery("SELECT * FROM `" . mres($_POST['sourceDatabase']) . "`.`f_sales` WHERE `idf_sales` in (" . mres($_POST['sourceSale']) . ")"));
	
	
	
	foreach ($sales as $saleIndex => $sale) {
//		$sales[$saleIndex]

		$sales[$saleIndex]['remains'] = query2array(mysqlQuery(""
						. "SELECT sum(qty) as `qty`, `service`, `price` FROM (SELECT "
						. "`f_subscriptionsContract` AS `sale`,"
						. "`f_salesContentService` AS `service`,"
						. "`f_salesContentPrice` as `price`,"
						. "`f_salesContentQty` as `qty`"
						. " FROM `" . mres($_POST['sourceDatabase']) . "`.`f_subscriptions` WHERE `f_subscriptionsContract`='" . $sales[$saleIndex]['idf_sales'] . "'"
						. " UNION ALL"
						. " SELECT "
						. "`servicesAppliedContract` AS `sale`,"
						. "`servicesAppliedService` AS `service`,"
						. "`servicesAppliedPrice` as `price`,"
						. "-`servicesAppliedQty` as `qty`"
						. " FROM `" . mres($_POST['sourceDatabase']) . "`.`servicesApplied` WHERE `servicesAppliedContract`='" . $sales[$saleIndex]['idf_sales'] . "' AND isnull(`servicesAppliedDeleted`)) as `remains`"
						. "WHERE NOT isnull(service)"
						. "GROUP BY service,price"
						. ""));

		if (!array_sum(array_column($sales[$saleIndex]['remains'], 'qty'))) {
			printr('Нет остатков на абонементе ' . $sales[$saleIndex]['idf_sales']);
			continue;
		}

		$client = mfa(mysqlQuery("SELECT * FROM `" . mres($_POST['targetDatabase']) . "`.`clients` WHERE `idclients` = '" . mres($_POST['targetClient']) . "'"));

//idf_sales, f_salesNumber, f_salesCreditManager, f_salesClient, f_salesSumm, f_salesComment, f_salesTime, f_salesDate, f_salesType, f_salesCancellationDate, f_salesCancellationSumm, f_salesEntity, f_salesAlert, f_salesAlertBy, import, f_salesGUID, f_salesIsAppendix, f_salesReceipt
		mysqlQuery("INSERT INTO `" . mres($_POST['targetDatabase']) . "`.`f_sales` SET "
				. "`f_salesNumber` = (SELECT * FROM (SELECT ifnull((SELECT MAX(`f_salesNumber`) FROM `warehouse`.`f_sales` WHERE `f_salesClient`= '" . $client['idclients'] . "' AND `f_salesEntity`='1' AND NOT isnull(`f_salesIsAppendix`)),1)+1 as `fsn`) AS `fsntmp`), "
//			. "(SELECT * FROM (SELECT IF(isnull((),2,(SELECT MAX(f_salesNumber) FROM `" . mres($_POST['targetDatabase']) . "`.`f_sales` WHERE `f_salesClient`='" . $client['idclients'] . "' AND NOT isnull(`f_salesIsAppendix`))+1)) as `tmp`),"
				. " `f_salesClient` = '" . mres($_POST['targetClient']) . "',"
				. " `f_salesSumm` = 0,"
				. " `f_salesComment` = 'Перенос остатков из " . $_POST['sourceDatabase'] . ", " . $_POST['sourceSale'] . "',"
				. " `f_salesTime` = NOW(),"
				. " `f_salesDate`=" . sqlVON($sales[$saleIndex]['f_salesDate']) . ","
				. " `f_salesType`=" . sqlVON($sales[$saleIndex]['f_salesType']) . ","
				. " `f_salesCancellationDate`=" . sqlVON($sales[$saleIndex]['f_salesCancellationDate']) . ","
				. " `f_salesCancellationSumm`=" . sqlVON($sales[$saleIndex]['f_salesCancellationSumm']) . ","
				. " `f_salesEntity`=" . sqlVON($sales[$saleIndex]['f_salesEntity']) . ","
				. " `f_salesIsAppendix`='1'"
				. "  ");
		$newSale = mfa(mysqlQuery("SELECT *  FROM `" . mres($_POST['targetDatabase']) . "`.`f_sales` WHERE `idf_sales`='" . mysqli_insert_id($link) . "'"));
		foreach ($sales[$saleIndex]['remains'] as $remain) {
//		printr($remain);
//idf_subscriptions, f_subscriptionsContract, f_salesContentService, f_salesContentPrice, f_salesContentQty, f_subscriptionsDate, f_subscriptionsUser, f_subscriptionsExpDate, f_subscriptionsImport
			mysqlQuery("INSERT INTO `" . mres($_POST['targetDatabase']) . "`.`f_subscriptions` SET "
					. "`f_subscriptionsContract` = '" . $newSale['idf_sales'] . "',"
					. "`f_salesContentService`='" . $remain['service'] . "',"
					. "`f_salesContentPrice`='" . $remain['price'] . "',"
					. "`f_salesContentQty`='" . $remain['qty'] . "',"
					. "`f_subscriptionsDate` = CURDATE(),"
					. "`f_subscriptionsUser` = null"
					. "");

			mysqlQuery("INSERT INTO `" . mres($_POST['sourceDatabase']) . "`.`f_subscriptions` SET "
					. "`f_subscriptionsContract` = '" . $sales[$saleIndex]['idf_sales'] . "',"
					. "`f_salesContentService`='" . $remain['service'] . "',"
					. "`f_salesContentPrice`='" . $remain['price'] . "',"
					. "`f_salesContentQty`='" . (-$remain['qty']) . "',"
					. "`f_subscriptionsDate` = CURDATE(),"
					. "`f_subscriptionsUser` = '" . $_USER['id'] . "'"
					. "");
		}


		mysqlQuery("INSERT INTO `" . mres($_POST['sourceDatabase']) . "`.`f_salesReplacementsCoordinator` SET "
				. "`f_salesReplacementsCoordinatorContract` = '" . $sales[$saleIndex]['idf_sales'] . "',"
				. "`f_salesReplacementsCoordinatorDate`= CURDATE(),"
				. "`f_salesReplacementsCoordinatorCurator` = '" . $_USER['id'] . "'"
				. "");
		mysqlQuery("INSERT INTO `" . mres($_POST['sourceDatabase']) . "`.`f_salesReplacementComments` SET "
				. "`f_salesReplacementCommentsContract` = '" . $sales[$saleIndex]['idf_sales'] . "',"
				. "`f_salesReplacementCommentsDate`= CURDATE(),"
				. "`f_salesReplacementCommentsText` = 'Перенос остатков абонемента " . $sales[$saleIndex]['idf_sales'] . " на площадку " . mres($_POST['targetDatabase']) . ", клиент " . $client['idclients'] . " абонемент " . $newSale['idf_sales'] . "'"
				. "");
	}



	header("Location: " . GR());
	exit();
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<div class="box neutral">
	<div class="box-body">
		<?
//		printr($sale ?? [], 1);
//		printr($client ?? [], 1);
//		printr($_POST, 1);
		?>
		<form action="?" method="POST">
			<table border="1">
				<tr>
					<th>Источник</th>
					<th>Назначение</th>
				</tr>
				<tr>
					<td>
						<table>
							<tr>
								<td>База данных</td>
								<td>
									<select name="sourceDatabase">
										<option value=""></option>
										<option value="vita">ЧК</option>
										<option value="warehouse">МВ</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>Абонемент</td>
								<td>
									<input type="text" name="sourceSale">
								</td>
							</tr>
						</table>
					</td>
					<td>
						<table>
							<tr>
								<td>База данных</td>
								<td>
									<select name="targetDatabase">
										<option value=""></option>
										<option value="vita">ЧК</option>
										<option value="warehouse">МВ</option>
									</select>
								</td>
							</tr>
							<tr>
								<td>Клиент</td>
								<td>
									<input type="text" name="targetClient">
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<input type="submit" value="Перенести">
		</form>
	</div>
</div>


<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
