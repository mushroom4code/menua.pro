<?php
//die();
header('Content-Encoding: none;');
ini_set('memory_limit', '-1');
$pageTitle = 'Жданова';

$globaltimer = microtime(1);
die('Выключено');  
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176) {

}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {

	mysqlQuery("delete FROM clients;");
	mysqlQuery("ALTER TABLE clients AUTO_INCREMENT = 1;");
	mysqlQuery("ALTER TABLE f_sales AUTO_INCREMENT = 1;");
	mysqlQuery("ALTER TABLE f_credits AUTO_INCREMENT = 1;");
	mysqlQuery("ALTER TABLE f_payments AUTO_INCREMENT = 1;");
	mysqlQuery("ALTER TABLE f_subscriptions AUTO_INCREMENT = 1;");
	?>
	<style>

		.sq {
			display: inline-block;
			width: 6px;
			height: 6px;
			margin: 1px;
		}
		.ok {
			background-color: green;
		}
		.err {
			background-color: red;
		}

		.warn {
			background-color: orange;
		}
		.wrapper {
			line-height: 8px;
		}
		.box {
			margin: 0.0em 1.2em 1.0em 1.2em;
		}
	</style>

	<?
	$salesjson = json_decode(file_get_contents("Договоры_2023.json"), true);
	$allsales = $salesjson['Договоры'];

	$goodsjson = json_decode(file_get_contents("Договоры_2023.json"), true); //должен быть отдельный файл goods
	$goods = $goodsjson['Договоры'];

	$paymentsjson = json_decode(file_get_contents("Оплаты_2023.json"), true);
	$payments = $paymentsjson['Оплаты'];
	$clientsjson = json_decode(file_get_contents("DataClients.json"), true);
	$jsonclients = $clientsjson['clients'];
	$clientsOldSince = [];
	?>

	<div class="box neutral">
		<div class="box-body">
			<h2>Сбор данных клиентов</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);
				foreach ($allsales as $client) {
					if ($client['GUIDКлиента'] ?? false) {
						$sales = $client['Договоры'];
						foreach ($sales as $sale) {
							$date = date("Y-m-d", strtotime(min(array_column($sale["Сертификаты"], 'ДатаПродажи'))));
							if ($date != '0001-01-01') {
								$clientsOldSince[$client['GUIDКлиента']] = $date;
							}
						}
					} else {
						print '<div class="sq err" title="NO GUIDКлиента"></div>';
					}
				}
				?>
			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>


	<div class="box neutral">
		<div class="box-body">
			<h2>Импорт клиентов</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);
				$head = "INSERT INTO `clients` (`GUID`,`clientsLName`,`clientsFName`,`clientsMName`,`clientsBDay`,`clientsAKNum`,`clientsAddedBy`,`clientsOldSince`) VALUES ";
				$insert = [];
				foreach ($jsonclients as $client) {
					$name = explode(' ', trim(preg_replace('/\s+/', ' ', $client['ФИО'])));
					$bTime = strtotime($client['Дата рождения']);
					$bdate = date("Y-m-d", $bTime);
					///
					$lname = mb_ucfirst($name[0] ?? '');
					$fname = mb_ucfirst($name[1] ?? '');
					$mname = mb_ucfirst($name[2] ?? '');
					$errorsInsert = false;
					$insert[] = "( '" . $client['GUID'] . "',"
							. " " . ($lname ? ("'" . mres($lname) . "'") : "null") . ","
							. " " . ($fname ? ("'" . mres($fname) . "'") : "null") . ","
							. " " . ($mname ? ("'" . mres($mname) . "'") : "null") . ","
							. " " . ((($bdate ?? false) && $bdate !== '0001-01-01') ? ("'" . $bdate . "'") : "null") . ","
							. " " . ($client['Номер амбулаторной карты'] ? ("'" . $client['Номер амбулаторной карты'] . "'") : "null") . ","
							. " '" . $_USER['id'] . "', "
							. " " . (($clientsOldSince[$client['GUID']] ?? false) ? ("'" . $clientsOldSince[$client['GUID']] . "'") : 'null' ) . ") ";
					if (count($insert) > 50) {
						if (mysqlQuery($head . implode(',', $insert))) {
							print '<div class="sq ok" title="25"></div>';
						} else {
							print '<div class="sq warn" title="MYSQL ERROR"></div>';
						}
						$insert = [];
					}
					for ($n = 0; $n <= 10; $n++) {
						print '<!--                                                                                                    -->';
					}
					flush();
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>
			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>

	<? $clients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'GUID'); ?>

	<div class="box neutral">
		<div class="box-body">
			<h2>Импорт телефонов</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);
				$head = "INSERT INTO `clientsPhones` (`clientsPhonesClient`,`clientsPhonesPhone`,`clientsPhonesInvalid`) VALUES ";
				$insert = [];
				foreach ($jsonclients as $client) {
					$phone = preg_replace("/[^0-9]/", "", trim($client['Номер телефона']) ?? '');
					$phones = [];
					$invalidphones = [];
					if (strlen($phone) == 11) {
						$phone[0] = '8';
						$phones[] = $phone;
					} elseif (strlen($phone) == 10) {
						$phone = '8' . $phone;
						$phones[] = $phone;
					} elseif (strlen($phone) == 7) {
						$phones[] = $phone;
					} elseif (strlen($phone) == 22) {
						$phones[] = '8' . substr($phone, 1, 10);
						$phones[] = '8' . substr($phone, 12, 10);
					} elseif ($phone) {
						$invalidphones[] = $phone;
					}

					$phone = preg_replace("/[^0-9]/", "", trim($client['Мобильный']) ?? '');

					if (strlen($phone) == 11) {
						$phone[0] = '8';
						$phones[] = $phone;
					} elseif (strlen($phone) == 10) {
						$phone = '8' . $phone;
						$phones[] = $phone;
					} elseif (strlen($phone) == 7) {
						$phones[] = $phone;
					} elseif (strlen($phone) == 22) {
						$phones[] = '8' . substr($phone, 1, 10);
						$phones[] = '8' . substr($phone, 12, 10);
					} elseif ($phone) {
						$invalidphones[] = $phone;
					}

					$insertedUserId = $clients[$client['GUID']]['idclients'];
					foreach ($phones as $phone) {
						$insert[] = "('" . $insertedUserId . "','" . $phone . "', null) ";
					}
					foreach ($invalidphones as $phone) {
						$insert[] = "('" . $insertedUserId . "','" . $phone . "', 1) ";
					}



					if (count($insert) > 50) {
						if (mysqlQuery($head . implode(',', $insert))) {
							print '<div class="sq ok" title="25"></div>';
						} else {
							print '<div class="sq warn" title="MYSQL ERROR"></div>';
						}
						$insert = [];
					}


					for ($n = 0; $n <= 10; $n++) {
						print '<!--                                                                                                    -->';
					}
					flush();
//					die();
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>
			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>

	<div class="box neutral">
		<div class="box-body">
			<h2>Импорт паспортных данных</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);

				$insert = [];
				foreach ($jsonclients as $client) {



					if (1) {


						$insertedUserId = $clients[$client['GUID']]['idclients'];

						$passport = $client['Паспортные данные'];
						$passportSQL = [];
						if ($passport['Серия'] . ' ' . $passport['Номер'] != ' ') {
							$passportSQL[] = "`clientsPassportNumber`='" . $passport['Серия'] . ' ' . $passport['Номер'] . "'";
						}

						if ($client['Адрес проживания'] ?? '') {
							$passportSQL[] = "`clientsPassportsResidence`='" . mysqli_real_escape_string($link, $client['Адрес проживания']) . "'";
						}

						if ($passport['Кем выдан'] ?? '') {
							$passportSQL[] = "`clientsPassportsDepartment`='" . mysqli_real_escape_string($link, $passport['Кем выдан']) . "'";
						}
						$paspDate = strtotime($passport['Дата выдачи']);

						if ($paspDate > strtotime("1900-01-01 00:00:00")) {
							$passportSQL[] = "`clientsPassportsDate`='" . date("Y-m-d", $paspDate) . "'";
						}
						$head = "INSERT INTO `clientsPassports` ("
								. "`clientsPassportsClient`,"
								. " `clientsPassportNumber`,"
								. " `clientsPassportsResidence`,"
								. " `clientsPassportsRegistration`,"
								. " `clientsPassportsDate`, "
								. " `clientsPassportsBirthPlace`, "
								. " `clientsPassportsDepartment`,"
								. " `clientsPassportsAdded` "
								. " ) VALUES ";

						$insert[] = "("
								. "" . $insertedUserId . ","
								. (($passport['Серия'] ?? false) && ($passport['Номер'] ?? false) ? ("'" . mres($passport['Серия'] . ' ' . $passport['Номер']) . "'") : 'null') . ','
								. (($client['Адрес проживания'] ?? false) ? ("'" . mres($client['Адрес проживания']) . "'") : 'null') . ','
								. (($passport['Адрес по паспорту'] ?? false) ? ("'" . mres($passport['Адрес по паспорту']) . "'") : 'null') . ','
								. ((($passport['Дата выдачи'] ?? false) && ($passport['Дата выдачи'] !== '0001-01-01T00:00:00Z')) ? ("'" . date("Y-m-d", strtotime($passport['Дата выдачи'])) . "'") : 'null') . ','
								. ((($passport['Место рождения'] ?? false)) ? ("'" . mres($passport['Место рождения']) . "'") : 'null') . ','
								. ((($passport['Кем выдан'] ?? false)) ? ("'" . mres($passport['Кем выдан']) . "'") : 'null') . ','
								. "'" . date("Y-m-d") . "'"
								. ")";

						$ending = " AS new ON DUPLICATE KEY UPDATE "
								. " `clientsPassportsClient`= new.`clientsPassportsClient`,"
								. " `clientsPassportsResidence`= new.`clientsPassportsResidence`,"
								. " `clientsPassportsRegistration`= new.`clientsPassportsRegistration`,"
								. " `clientsPassportsDate`= new.`clientsPassportsDate`, "
								. " `clientsPassportsBirthPlace`= new.`clientsPassportsBirthPlace`, "
								. " `clientsPassportsDepartment`= new.`clientsPassportsDepartment`,"
								. " `clientsPassportsAdded`= new.`clientsPassportsAdded` "
								. "";

						if (count($insert) > 50) {
							if (mysqlQuery($head . implode(',', $insert) . $ending)) {
								print '<div class="sq ok" title="25"></div>';
							} else {
								print '<div class="sq warn" title="MYSQL ERROR"></div>';
							}
							$insert = [];
						}
					}

					for ($n = 0; $n <= 10; $n++) {
						print '<!--                                                                                                    -->';
					}
					flush();
//					die();
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert) . $ending);
					$insert = [];
				}
				?>
			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>



	<div class="box neutral">
		<div class="box-body">
			<h2>Договоры на окозание услуг</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);
				$head = "INSERT INTO `f_sales` (`f_salesClient`,`f_salesSumm`,`f_salesComment`,`f_salesTime`,`f_salesDate`,`f_salesGUID`,`import`) VALUES ";
				$insert = [];
//				print count($allsales);
				$inserted = 0;
				foreach ($allsales as $client) {
					if ($client['GUIDКлиента'] ?? false) {
						$clientSQL = $clients[$client['GUIDКлиента']] ?? null;
						$sales = $client['Договоры'];
						if ($clientSQL['idclients'] ?? false) {
							foreach ($sales as $sale) {
								$f_sale = [];
								$f_sale['f_salesClient'] = $clientSQL['idclients'] ?? null;
								$f_sale['f_salesSumm'] = $sale['Сумма кредита'] ?? $sale['Сумма договора'] ?? 'UNDEFINED';
								$f_sale['f_salesComment'] = 'импорт 1С' . mres(trim($sale['Комментарий'] ?? ''));
								$f_sale['f_salesTime'] = date("Y-m-d H:i:s");
								$f_sale['f_salesDate'] = date("Y-m-d", strtotime($sale["Сертификаты"][0]['ДатаПродажи']));
								$f_sale['f_salesGUID'] = $sale['GUIDДоговора'];
								$f_sale['import'] = date("Y-m-d H:i:s");

								$inserted++;

								$insert[] = "('" . $f_sale['f_salesClient'] . "',"
										. "'" . $f_sale['f_salesSumm'] . "',"
										. "'" . $f_sale['f_salesComment'] . "', "
										. "'" . $f_sale['f_salesTime'] . "', "
										. "'" . $f_sale['f_salesDate'] . "',"
										. "'" . $f_sale['f_salesGUID'] . "',"
										. "'" . $f_sale['import'] . "'"
										. ")";
								if (count($insert) > 100) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="100"></div>';
									} else {
										print '<div class="sq warn" title="MYSQL ERROR"></div>';
									}
									$insert = [];
								}
							}
						} else {
							print '<div class="sq err" title="NO CLIENT FOR GUID (' . $client['GUIDКлиента'] . ')" onclick="alert(`Клиент не найден \r\n' . $client['GUIDКлиента'] . '`)"></div>';
						}
					} else {
						print '<div class="sq err" title="NO SALE GUID"></div>';
					}
					for ($n = 0; $n <= 10; $n++) {
						print '<!--                                                                                                    -->';
					}
					flush();
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>
			</div>
			<h4><?= $inserted; ?> Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>

	<?
	$BANKGUIDS = (query2array(mysqlQuery("SELECT `idRS_banks`,`RS_banksGUID` FROM `RS_banks`"), 'RS_banksGUID'));
	$f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID');

	if (0) {
		?>

		<div class="box neutral">
			<div class="box-body">
				<h2>Договоры на товары</h2>
				<div class="wrapper">
					<?
					$start = microtime(1);
					$head = "INSERT INTO `f_sales` (`f_salesClient`,`f_salesSumm`,`f_salesComment`,`f_salesTime`,`f_salesDate`,`import`,`f_salesGUID`) VALUES ";
					$insert = [];
					$pendingCredits = [];
					$pendingPayments = [];
					$pendingInstallments = [];

//				printr(array_unique(array_column($goods, "Схема оплаты")));
//				die();
					foreach ($goods as $sale) {

						/*  {
						  "GUIDДоговора": "7fefa8f5-72c8-11e7-98f8-94de80ba9b12",
						  "GUIDКлиента": "5744e061-4d80-11e6-ac7e-94de80ba9b12",
						  "Сумма договора": 6000,
						  "Схема оплаты": "КЭШ",
						  "Комментарий": "                                                                                                    ",
						  "Товары": [
						  {
						  "Тип номенклатуры": "Товар",
						  "Наименование": "Жидкий филлер",
						  "Полное наименование": "Жидкий филлер",
						  "GUIDТовара": "7fefa8ef-72c8-11e7-98f8-94de80ba9b12",
						  "ДатаПродажи": "2017-07-26T21:00:00Z",
						  "Количество": 1,
						  "Цена продажи": 6000
						  }
						  ]
						  }, */
						if ($sale['GUIDКлиента'] ?? false) {
							$clientSQL = $clients[$client['GUIDКлиента']] ?? null;
//сначала посмотрим, есть ли у нас такой договор уже, то тогда ничего не делаем,
							//Если его нет, то надо внести....
							//потом перезагрузим все договоры и дополним их товарами..
							if ($f_sales[$sale['GUIDДоговора']] ?? false) {
								continue;
							}




							if ($clientSQL['idclients'] ?? false) {

//						$pendingCredits = [];
//						$pendingPayments = [];
//						$pendingInstallments = [];
								if ($sale['Схема оплаты'] == 'Кредит') {

									if (!($BANKGUIDS[$sale["GUID Банка"] ?? '']['idRS_banks'] ?? false)) {
										mysqlQuery("INSERT INTO `RS_banks` SET `RS_banksName`='" . mres($sale["Банк"]) . "', `RS_banksShort`='" . mres($sale["Банк"]) . "', `RS_banksGUID`='" . $sale["GUID Банка"] . "'");
										$idbank = mysqli_insert_id($link);
										$BANKGUIDS[$sale["GUID Банка"]] = ['idRS_banks' => $idbank, 'RS_banksGUID' => $sale["GUID Банка"]];
									} else {
										$idbank = $BANKGUIDS[$sale["GUID Банка"] ?? '']['idRS_banks'];
									}
									if (!($sale['Номер Кредитного Договора'] ?? false)) {
										print '<div class="sq err" title="Отсутствует номер Кредитного Договора" onclick="alert(`Отсутствует номер Кредитного Договора в GUIDДоговора\r\n' . ($sale['GUIDДоговора'] ?? 'NO GUIDДоговора') . '`)"></div>';
										continue;
									}
									$pendingCredits[] = [
										'GUID' => $sale['GUIDДоговора'],
										'f_creditsBankAgreementNumber' => $sale['Номер Кредитного Договора'],
										'f_creditsSumm' => $sale['Сумма кредита'],
										'f_creditsSummIncInterest' => $sale['Сумма договора'],
										'f_creditsMonthes' => 24,
										'f_creditsSalesID' => null,
										'f_creditsBankID' => $idbank,
									];

									printr($pendingCredits);
								}


								$insert[] = "('" . $clientSQL['idclients'] . "',"
										. "'" . ( $sale['Сумма кредита'] ?? $sale['Сумма договора'] ?? 'UNDEFINED') . "',"
										. "'" . '[импорт 1С]' . mres(trim($sale['Комментарий'])) . "', "
										. "NOW(), "
										. "'" . date("Y-m-d", strtotime($sale["Товары"][0]['ДатаПродажи'])) . "',"
										. "NOW(),"
										. "'" . $sale['GUIDДоговора'] . "')";
								if (count($insert) > 25) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="25"></div>';
									} else {
										print '<div class="sq warn" title="MYSQL ERROR"></div>';
									}
									$insert = [];
								}
							} else {
								print '<div class="sq err" title="NO CLIENT FOR GUID (' . $client['GUIDКлиента'] . ')" onclick="alert(`Клиент не найден \r\n' . $client['GUIDКлиента'] . '`)"></div>';
							}
						} else {
							print '<div class="sq err" title="NO CLIENT GUID"></div>';
						}
						for ($n = 0; $n <= 10; $n++) {
							print '<!--                                                                                                    -->';
						}
						flush();
					}
					if (count($insert)) {
						mysqlQuery($head . implode(',', $insert));
						$insert = [];
					}
					?>

					|
					<?
					$f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID');
					$head = "INSERT INTO `f_credits` (`f_creditsBankAgreementNumber`,`f_creditsSumm`,`f_creditsSummIncInterest`,`f_creditsMonthes`,`f_creditsSalesID`,`f_creditsBankID`) VALUES ";
					$insert = [];

					foreach ($pendingCredits as $pendingCredit) {
						if (!($pendingCredit['GUIDДоговора'] ?? false)) {
							print '<div class="sq err" title="NO GUID"></div>';
							continue;
						}
						if (!($f_sales[$pendingCredit['GUIDДоговора']]['idf_sales'] ?? false)) {
							print '<div class="sq err" title="NO IDSALE FOR GUID ' . $pendingCredit['GUIDДоговора'] . '"></div>';
							continue;
						}
						$insert[] = "('" . $pendingCredit['f_creditsBankAgreementNumber'] . "', "
								. " '" . $pendingCredit['f_creditsSumm'] . "', "
								. " '" . $pendingCredit['f_creditsSummIncInterest'] . "', "
								. " '" . $pendingCredit['f_creditsMonthes'] . "', "
								. " '" . $f_sales[$pendingCredit['GUIDДоговора']]['idf_sales'] . "', "
								. " '" . $pendingCredit['f_creditsBankID'] . "')";
						if (count($insert) > 25) {
							if (mysqlQuery($head . implode(',', $insert))) {
								print '<div class="sq ok" title="25"></div>';
							} else {
								print '<div class="sq warn" title="mysqlErr"></div>';
							}
							$insert = [];
							for ($n = 0; $n <= 10; $n++) {
								print '<!--                                                                                                    -->';
							}
							ob_flush();
							flush();
						}
					}
					if (count($insert)) {
						if (mysqlQuery($head . implode(',', $insert))) {
							print '<div class="sq ok" title="25"></div>';
						} else {
							print '<div class="sq warn" title="mysqlErr"></div>';
						}
						$insert = [];
						for ($n = 0; $n <= 10; $n++) {
							print '<!--                                                                                                    -->';
						}
						ob_flush();
						flush();
					}
					?>
				</div>
				<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
			</div>
		</div>
	<? }
	?>



	<div class="box neutral">
		<div class="box-body">
			<h2>Кредиты</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);
				$head = "INSERT INTO `f_credits` (`f_creditsBankAgreementNumber`,`f_creditsSumm`,`f_creditsSummIncInterest`,`f_creditsMonthes`,`f_creditsSalesID`,`f_creditsBankID`) VALUES ";
				foreach ($allsales as $client) {
					if ($client['GUIDКлиента']) {
						$clientSQL = $clients[$client['GUIDКлиента']] ?? null;
						$sales = $client['Договоры'];
						foreach ($sales as $sale) {
							$idf_sale = $f_sales[$sale['GUIDДоговора']]['idf_sales'] ?? false;
							if (!$idf_sale) {
								?><div class="sq err" title="Нет договора по GUID<?= $sale['GUIDДоговора'] ?>" onclick="alert('Нет договора по GUID\r\n<?= $sale['GUIDДоговора'] ?>');"></div><?
								continue;
							}
							if (($sale['Схема оплаты'] ?? '') == 'Кредит') {
								if (!($BANKGUIDS[$sale["GUID Банка"] ?? '']['idRS_banks'] ?? false)) {
									mysqlQuery("INSERT INTO `RS_banks` SET `RS_banksName`='" . mres($sale["Банк"]) . "', `RS_banksShort`='" . mres($sale["Банк"]) . "', `RS_banksGUID`='" . $sale["GUID Банка"] . "'");
									$idbank = mysqli_insert_id($link);
									$BANKGUIDS[$sale["GUID Банка"]] = ['idRS_banks' => $idbank, 'RS_banksGUID' => $sale["GUID Банка"]];
								} else {
									$idbank = $BANKGUIDS[$sale["GUID Банка"] ?? '']['idRS_banks'];
								}
								$f_credits['f_creditsBankAgreementNumber'] = ( $sale['Номер Кредитного Договора'] ?? '');
								$f_credits['f_creditsSumm'] = floatval($sale['Сумма кредита'] ?? 0);
								$f_credits['f_creditsSummIncInterest'] = floatval($sale['Сумма договора'] ?? 0);
								$f_credits['f_creditsMonthes'] = 24;
								$f_credits['f_creditsSalesID'] = $idf_sale;
								$f_credits['f_creditsBankID'] = $BANKGUIDS[$sale['GUID Банка'] ?? '']['idRS_banks'];

								$insert[] = "('" . $f_credits['f_creditsBankAgreementNumber'] . "', "
										. " '" . $f_credits['f_creditsSumm'] . "', "
										. " '" . $f_credits['f_creditsSummIncInterest'] . "', "
										. " '" . $f_credits['f_creditsMonthes'] . "', "
										. " '" . $f_credits['f_creditsSalesID'] . "', "
										. " '" . $f_credits['f_creditsBankID'] . "')";
								if (count($insert) > 25) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="25"></div>';
									} else {
										print '<div class="sq warn" title="mysqlErr"></div>';
									}
									$insert = [];
									for ($n = 0; $n <= 10; $n++) {
										print '<!--                                                                                                    -->';
									}
									ob_flush();
									flush();
								}
							}
						}
					}
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>


			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>

	<div class="box neutral">
		<div class="box-body">
			<h2>Оплаты нал/безнал</h2>
			<div class="wrapper">
				<?
				$missingGUIDs = [];

				$start = microtime(1);
				$insert = [];
				$head = "INSERT INTO `f_payments` (`f_paymentsSalesID`,`f_paymentsType`,`f_paymentsAmount`,`f_paymentsDate`,`f_paymentsUser`,`f_paymentsComment`) VALUES ";
				$missingGUIDsSumm = 0;
				$paymentsComments = [];
				foreach ($payments as $payment) {

//						printr($payment);
					if ($payment['GUIDДоговора'] ?? false) {


						$f_payments['f_paymentsSalesID'] = $f_sales[$payment['GUIDДоговора']]['idf_sales'] ?? false;

						if (!$f_payments['f_paymentsSalesID']) {
							$missingGUIDsSumm += $payment['Сумма'];
							$missingGUIDs[] = $payment['GUIDДоговора'];
							?><div class="sq err" onclick="alert('нет договора по GUID <?= $payment['GUIDДоговора']; ?>');" title="нет договора по GUID <?= $payment['GUIDДоговора']; ?>"></div><?
							continue;
						}
						//, , , , ,
						if ($payment['Источник'] == 'Касса') {
							$f_payments['f_paymentsType'] = 1;
						}
						if ($payment['Источник'] == 'Оплата картой') {
							$f_payments['f_paymentsType'] = 2;
						}
						if ($payment['Источник'] == 'Банк') {
							$f_payments['f_paymentsType'] = 2;
						}
						$f_payments['f_paymentsAmount'] = $payment['Сумма'];
						$f_payments['f_paymentsDate'] = date("Y-m-d H:i:s", strtotime($payment['Дата платежа']));
						$f_payments['f_paymentsUser'] = 176;

						$insert[] = "("
								. "'" . $f_payments['f_paymentsSalesID'] . "',"
								. "'" . $f_payments['f_paymentsType'] . "',"
								. "'" . $f_payments['f_paymentsAmount'] . "',"
								. "'" . $f_payments['f_paymentsDate'] . "',"
								. "'" . $f_payments['f_paymentsUser'] . "',"
								. "'" . mres(trim($payment['Комментарий'])) . "')";
						if (count($insert) > 25) {
							if (mysqlQuery($head . implode(',', $insert))) {
								print '<div class="sq ok" title="25"></div>';
							} else {
								print '<div class="sq warn" title="mysqlError"></div>';
							}
							$insert = [];
							for ($n = 0; $n <= 10; $n++) {
								print '<!--                                                                                                    -->';
							}
							ob_flush();
							flush();
						}
					} else {
						print '<div class="sq err" title="no GUID sale"></div>';
					}
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>
			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
			Список GUIDов отсутствующих договоров на которые есть ссылки в оплатах (<?= count($missingGUIDs); ?>):
			<?
			if (1 && count($missingGUIDs)) {
				$missingGUIDs = array_unique($missingGUIDs);
				foreach ($missingGUIDs as $missingGUID) {
					print "<div>$missingGUID</div>";
				}
				print 'На общую сумму: ' . $missingGUIDsSumm;
			}
			?>
		</div>
	</div>




	<div class="box neutral">
		<div class="box-body">
			<h2>Рассрочки</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);
				$insert = [];
				$head = "INSERT INTO `f_installments` (`f_installmentsSalesID`,`f_installmentsSumm`,`f_installmentsPeriod`) VALUES ";

				foreach ($allsales as $client) {
					if ($client['GUIDКлиента']) {
						$clientSQL = $clients[$client['GUIDКлиента']] ?? null;
						if (!($clientSQL['idclients'] ?? false)) {
							?><div class="sq err" title="Нет id клиента по GUID <?= $client['GUIDКлиента'] ?? 'Да и гуида нет...'; ?>" onclick="alert('Нет id клиента по GUID <?= $client['GUIDКлиента'] ?? 'Да и гуида нет...'; ?>');"></div><?
						}
						$sales = $client['Договоры'];
						foreach ($sales as $sale) {
							if (($sale['Схема оплаты'] ?? '') == 'В рассрочку') {
								$f_installments['f_installmentsSalesID'] = $f_sales[$sale['GUIDДоговора']]['idf_sales'] ?? null;
								$f_installments['f_installmentsSumm'] = $sale["Сумма договора"] - mfa(
												mysqlQuery("SELECT SUM(`f_paymentsAmount`) as `summ` FROM `f_payments` WHERE `f_paymentsSalesID`='" . $f_installments['f_installmentsSalesID'] . "'")
										)['summ'] ?? 0;
								$f_installments['f_installmentsPeriod'] = 1;

								if ($f_installments['f_installmentsSalesID'] ?? false) {
									$insert[] = "('" . $f_installments['f_installmentsSalesID'] . "', "
											. " '" . $f_installments['f_installmentsSumm'] . "', "
											. " '" . $f_installments['f_installmentsPeriod'] . "')";
								} else {
									?><div class="sq warn" onclick="alert('Нет продажи по GUID <?= $sale['GUIDДоговора'] ?? 'Да и гуида нет...'; ?>');" title="Нет продажи по GUID <?= $sale['GUIDДоговора'] ?? 'Да и гуида нет...'; ?>"></div><?
								}

								if (count($insert) > 15) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="25"></div>';
									} else {
										print '<div class="sq warn" title="mysqlError"></div>';
									}
									$insert = [];
									for ($n = 0; $n <= 10; $n++) {
										print '<!--                                                                                                    -->';
									}
									ob_flush();
									flush();
								}
							}
						}
					}
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				?>
			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>


	<div class="box neutral">
		<div class="box-body">
			<h2>Расторжения</h2>
			<div class="wrapper">
				<?
				$start = microtime(1);
				foreach ($payments as $payment) {
					if ($payment['GUIDДоговора'] ?? false) {
						$f_payments['f_paymentsSalesID'] = $f_sales[$payment['GUIDДоговора']]['idf_sales'] ?? false;
						if (!$f_payments['f_paymentsSalesID']) {
							?><div class="sq err" onclick="alert('нет договора по GUID <?= $payment['GUIDДоговора']; ?>');" title="нет договора по GUID <?= $payment['GUIDДоговора']; ?>"></div><?
							continue;
						}
						//, , , , ,   "": "",
						if ($payment['Расторгнут'] == 'Да') {
							if (mysqlQuery("UPDATE `f_sales` SET "
											. " `f_salesCancellationDate` = '" . date("Y-m-d", strtotime($payment['Дата расторжения'])) . "'"
											. " WHERE `idf_sales`='" . $f_payments['f_paymentsSalesID'] . "'")) {
								print '<div class="sq ok"></div>';
							} else {
								print '<div class="sq warn" title="mysqlError"></div>';
							}
							for ($n = 0; $n <= 10; $n++) {
								print '<!--                                                                                                    -->';
							}
							ob_flush();
							flush();
						}
					} else {
						print '<div class="sq err" title="no GUID sale"></div>';
					}
				}
				?>
			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>



	<div class="box neutral">
		<div class="box-body"><? $start = microtime(1); ?>
			<h2>Процедуры</h2>
			<div class="wrapper">
				<?
				$head = "INSERT INTO `f_subscriptions` "
						. "("
						. "`f_subscriptionsContract`,"
						. "`f_salesContentService`,"
						. "`f_salesContentPrice`,"
						. "`f_salesContentQty`,"
						. "`f_subscriptionsDate`,"
						. "`f_subscriptionsUser`"
						. ") VALUES ";
				$insert = [];
				$services = query2array(mysqlQuery("SELECT `idservices`,`servicesGUIDsGUID` FROM `warehouse`.`services` left join `warehouse`.`servicesGUIDs` on (`servicesGUIDsService`=`idservices`) where not isnull(`servicesGUIDsGUID`);"), 'servicesGUIDsGUID');
				$UNDEFINEDPROCEDURE = [];
				foreach ($allsales as $client) {
					?><?
//					printr($client);
					if ($client['GUIDКлиента']) {
						$clientSQL = $clients[$client['GUIDКлиента']] ?? null;

						$sales = $client['Договоры'];

						foreach ($sales as $sale) {
							$idf_sales = $f_sales[$sale['GUIDДоговора']]['idf_sales'] ?? null;

							if (!$idf_sales) {
								print '<div class="sq err" title="' . ($idf_sales ?? 'NOIDFSALE') . '" onclick="alert(`' . $sale['GUIDДоговора'] . '`)"></div>';
								continue;
							}

							foreach (($sale['Сертификаты'] ?? []) as $cert) {
								foreach (($cert['Процедуры'] ?? []) as $subscription) {

									if (!($services[$subscription['GUIDПроцедуры']] ?? false)) {
										print '<div class="sq err" title="UNDEFINED PROCEDURE (' . htmlentities($subscription['GUIDПроцедуры']) . ')"></div>';
										mysqlQuery("INSERT INTO `services` SET `servicesName`='" . mres(($subscription['Наименование'] ?? false) ? $subscription['Наименование'] : 'ПУСТАЯ СТРОКА') . "'");
										$insertid = mysqli_insert_id($link);
										mysqlQuery("INSERT INTO `warehouse`.`servicesGUIDs` SET `servicesGUIDsService`='" . $insertid . "', `servicesGUIDsGUID`='" . $subscription['GUIDПроцедуры'] . "'");
										$services[$subscription['GUIDПроцедуры']] = ['idservices' => $insertid, 'servicesGUIDsGUID' => $subscription['GUIDПроцедуры']];
									}

									if ($services[$subscription['GUIDПроцедуры']]['idservices'] ?? false) {
										$insert[] = "("
												. "'" . $idf_sales . "', "
												. "'" . ($services[$subscription['GUIDПроцедуры']]['idservices']) . "', "
												. "'" . intval($subscription['Цена продажи']) . "', "
												. "'" . intval($subscription['Продано']) . "', "
												. "'" . date("Y-m-d", strtotime($cert['ДатаПродажи'])) . "', "
												. "'176'"
												. ")";
									} else {
										$UNDEFINEDPROCEDURE[] = $subscription['GUIDПроцедуры'];
									}
								}
								if (count($insert) > 100) {
									if (mysqlQuery($head . implode(',', $insert))) {
										print '<div class="sq ok" title="100"></div>';
									} else {
										print mysqli_error($link);
										$UNDEFINEDPROCEDURE[] = $subscription['GUIDПроцедуры'];
										print '<div class="sq err" title="UNDEFINED PROCEDURE (' . htmlentities($subscription['GUIDПроцедуры']) . ')"></div>';
									}
									$insert = [];
									for ($n = 0; $n <= 10; $n++) {
										print '<!--                                                                                                    -->';
									}
									ob_flush();
									flush();
								}
							}
						}
					}
				}
				if (count($insert)) {
					mysqlQuery($head . implode(',', $insert));
					$insert = [];
				}
				printr($UNDEFINEDPROCEDURE);
				?>

			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?></h4>
		</div>
	</div>

	<?
}
?>
На всё провсё ушло <?= round((microtime(1) - $globaltimer), 2); ?>сек.
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
