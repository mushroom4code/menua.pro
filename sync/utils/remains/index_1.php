<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$fileContents = file_get_contents("2020.json");



$json = json_decode($fileContents, true);

//printr($json);

$remains = $json['remains'];
$DBservices = query2array(mysqlQuery("SELECT * FROM `services` LEFT JOIN `servicesGUIDs` ON (`servicesGUIDsService` = `idservices`)"));
$DBusers = query2array(mysqlQuery("SELECT * FROM `clients`"));

$undefined = [];

$exist = 0;
?>

<div class="box neutral">
	<div class="box-body">
		<?
		foreach ($remains as &$remain2) {
			$remain2['ДатаПродажи'] = date("Y-m-d", strtotime($remain2['ДатаПродажи']));
		}
		usort($remains, function($a, $b) {
			if ($a['GUIDКонтрагента'] <=> $b['GUIDКонтрагента']) {
				return $a['GUIDКонтрагента'] <=> $b['GUIDКонтрагента'];
			} else {
				return $a['ДатаПродажи'] <=> $b['ДатаПродажи'];
			}
		});
//		printr($remains[0]);
		?>
		<table>
			<tr>
				<td>Клиент</td>
				<td>Дата</td>
				<td>Процедура</td>
				<td>Количество</td>
				<td>Стоимость</td>
				<td>+</td>
			</tr>
			<?
			$GUID = null;
			$date = null;
			$sale_summ = 0;
			$idf_sales = null;
			foreach ($remains as $remain) {

				if ($GUID !== $remain['GUIDКонтрагента']) {
					$GUID = $remain['GUIDКонтрагента'];
					$clients = array_filter($DBusers, function($element) {
						global $GUID;
						return $element['GUID'] == $GUID;
					});

					$clients = obj2array($clients);

					if (count($clients) == 1) {
//						printr($clients);
						$client = $clients[0];
					} else {
						$client = null;
						continue;
					}
					?>

					<?
				}


				if ($date !== $remain['ДатаПродажи']) {
					$date = $remain['ДатаПродажи'];


					$salesDB = query2array(mysqlQuery("SELECT * FROM `f_sales`"
									. " WHERE `f_salesClient` = '" . $client['idclients'] . "'"
									. "AND `f_salesDate` = '" . $date . "'"));

					if (count($salesDB)) {
						$exist++;
						continue;
					}
					if (!$client['idclients']) {
						continue;
					}
					?>
					<tr <? if (count($salesDB)) { ?>style="background-color: pink;"<? } ?>><td colspan="6">
							<? if (count($salesDB)) { ?>EXIST (<? printr($salesDB); ?>) <? } ?>						
							<?
							if ($idf_sales) {
								$UPDATE_f_salesSQL = "UPDATE `f_sales` SET `f_salesSumm`='" . $sale_summ . "' WHERE `idf_sales`='" . $idf_sales . "'";
//								mysqlQuery($UPDATE_f_salesSQL);
								print $UPDATE_f_salesSQL . '<br>' . '<br>';
								$sale_summ = 0;
							}


							$saleSQL = "INSERT INTO `f_sales` SET"
									. " `f_salesClient`='" . $client['idclients'] . "',"
									. " `f_salesComment`='1С',"
									. " `f_salesDate` = '" . $date . "',"
									. " `f_salesCreditManager`='" . $_USER['id'] . "' "
									. "";
//							mysqlQuery($saleSQL);
							$idf_sales = mysqli_insert_id($link) ? mysqli_insert_id($link) : 'XXX';
							print $saleSQL . '<br>' . '<br>';
							?>
						</td></tr>
					<?
				}


				$services = array_filter($DBservices, function($element) {
					global $remain;
					return $element['servicesGUIDsGUID'] == $remain['GUIDПроцедуры'];
				});
				$services = obj2array($services);
				if (count($services) == 0 || count($services) > 1) {
					continue;
				}
				?>
				<tr>
					<td><?= $client['idclients']; ?></td>
					<td><?= $date; ?></td>
					<td><?
						if (count($services) == 1) {
							print $services[0]['idservices'];
						} elseif (count($services) == 0) {
							?>---<?
							$undefined['notfound'][$remain['GUIDПроцедуры']] = ($undefined['notfound'][$remain['GUIDПроцедуры']] ?? 0) + 1;
						} else {

							$undefined['array'][$remain['GUIDПроцедуры']] = ($undefined['array'][$remain['GUIDПроцедуры']] ?? 0) + 1;
							print implode(',', array_column($services, 'idservices'));
							?> !!!<?
						}
						?>
					</td>
					<td><?= $remain['КоличествоОстаток']; ?></td>
					<td><?= $remain['Стоимость']; ?></td>
					<td><?
						$sale_summ += $remain['Стоимость'] * $remain['КоличествоОстаток'];


						$f_subscriptionsINSERT_SQL = "INSERT "
								. "INTO `f_subscriptions` "
								. "SET"
								. "`f_subscriptionsContract`='" . $idf_sales . "',"
								. "`f_salesContentService` = '" . $services[0]['idservices'] . "',"
								. " `f_salesContentPrice` = '" . $remain['Стоимость'] . "',"
								. " `f_salesContentQty` = '" . $remain['КоличествоОстаток'] . "'";
						print $f_subscriptionsINSERT_SQL;
//						mysqlQuery($f_subscriptionsINSERT_SQL);
						printr($remain);
						?></td>
				</tr>
				<?
			}
			?>
		</table>
		<? printr($undefined); ?>
		Exist: <? printr($exist); ?>


	</div>
</div>

<?
//	mysqlQuery("select * from `asasas`");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
