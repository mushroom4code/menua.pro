<?
header('Content-Encoding: none;');
//set_time_limit(50);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Calltouch</title>
		<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
    </head>
    <body>
		<div>
			<?
			$sales = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesDate`>='2021-07-01' AND `idf_sales`>='13771' ORDER BY `idf_sales`"));
			printr('Обработать' . count($sales), 1);
//			die();
			foreach ($sales as $n => $sale) {
				$tags = [];

				$saPersonnel = query2array(mysqlQuery("SELECT * "
								. " FROM `servicesApplied`"
								. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
								. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`) "
								. " WHERE `servicesAppliedDate` = '" . $sale['f_salesDate'] . "' "
								. " AND `servicesAppliedClient` = '" . $sale['f_salesClient'] . "'"
								. " AND isnull(`servicesAppliedDeleted`) "
								. " AND isnull(`servicesAppliedContract`) "
								. " AND NOT isnull(`servicesAppliedFineshed`)"
								. ";"));

				foreach ($saPersonnel as $sa) {
					if ($sa['usersLastName'] && !in_array($sa['usersLastName'] . ' ' . $sa['usersFirstName'], array_column($tags, 'tag'))) {
						$tags[] = ['tag' => $sa['usersLastName'] . ' ' . $sa['usersFirstName']];
					}
					if ($sa['servicesName'] && !in_array(($sa['serviceNameShort'] ?? $sa['servicesName']), array_column($tags, 'tag'))) {
						$tags[] = ['tag' => ($sa['serviceNameShort'] ?? $sa['servicesName'])];
					}
				}



				$dataToSend = [
					"crm" => "menua",
					"orders" => [
						[
							"matching" => [
								[
									"type" => "callContact",
									"callContactParams" => [
										"phones" => array_column(query2array(mysqlQuery("SELECT `clientsPhonesPhone` FROM `clientsPhones` WHERE `clientsPhonesClient`='" . $sale['f_salesClient'] . "' AND isnull(`clientsPhonesDeleted`)")), 'clientsPhonesPhone'),
										"date" => date("d-m-Y H:i:s", mystrtotime($sale['f_salesTime'])),
										"callTypeToMatch" => "nearest",
										"searchDepth" => 12000
									]
								]
							],
							"orderNumber" => SMSNAME . '.' . $sale['idf_sales'],
							"status" => "Абонемент",
							"statusDate" => date("d-m-Y H:i:s", mystrtotime($sale['f_salesTime'])),
							"orderDate" => date("d-m-Y H:i:s", mystrtotime($sale['f_salesTime'])),
							"revenue" => $sale['f_salesSumm'],
							"manager" => SMSNAME,
							"comment" => [
								"text" => "https://" . SUBDOMEN . "menua.pro/pages/checkout/payments.php?client=" . $sale['f_salesClient'] . "&contract=" . $sale['idf_sales']
							],
							"addTags" => $tags
						]
					]
				];

				if (!count($tags)) {
					unset($dataToSend['orders']['0']['addTags']);
				}

//				printr($dataToSend, 1);

				if (1) {
					$url = 'https://api.calltouch.ru/lead-service/v1/api/client-order/create';
					$ch = curl_init($url);
					$payload = json_encode($dataToSend);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_HTTPHEADER, [
						'Content-Type: application/json',
						'Access-Token: qq6qtZvSv9r9zhsOte2iRLHPG4lNMIoeMqMf3erDAa/AZ',
						'SiteId: 43769']);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$result = curl_exec($ch);
//					sendTelegram('sendMessage', ['chat_id' => '-522070992', 'text' => '📞' . json_encode(json_decode($result, 1), 288 + 128)]);
					curl_close($ch);
					$result = json_decode($result, 1);
//					printr($result, 1);
					print $sale['idf_sales'] . ' ' . (($result['data']['orders'][0]['calltouchOrderId'] ?? false) ? '📞' : '❌') . ($result['data']['orders'][0]['error'] ?? 'success') . (SMSNAME . '.' . $sale['idf_sales']) . '<br>';
					for ($n = 0; $n <= 50; $n++) {
						print '<!--                                                                                                    -->';
					}
					ob_flush();
					flush();
					if ($result['data']['orders'][0]['calltouchOrderId'] ?? false) {
						printr($sale['idf_sales']);
						foreach (getUsersByRights([167]) as $user) {
							if ($user['usersTG'] ?? false) {
								sendTelegram('sendMessage', ['chat_id' => $user['usersTG'], 'text' => '📞' . ' Добавлена продажа и привязана к Calltouch' . "\n" . 'https://my.calltouch.ru/accounts/29140/sites/43769/reports/deals-journal?dealId=' . $result['data']['orders'][0]['calltouchOrderId']]);
							}
						}
					}
				}
				usleep(200000);
			}
			?>

		</div>
    </body>
</html>
