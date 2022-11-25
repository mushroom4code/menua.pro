<?php
$load['title'] = $pageTitle = 'Книга продаж';
mb_internal_encoding("UTF-8");
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(95)) {
  
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(95)) {
  ?>E403R95<?
} else {
  ?>
  <div style="display: inline-block;">
    <div style="padding: 20px  20px 0px  20px;">
  	 <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 10px;">
  		<div><input type="date" value="<?= $_GET['statFrom'] ?? date("Y-m-01"); ?>" onchange="GETreloc('statFrom', this.value);"></div>
  		<div><input type="date" value="<?= $_GET['statTo'] ?? date("Y-m-d"); ?>" onchange="GETreloc('statTo', this.value);"></div>
  	 </div>
    </div>
  </div>
  <?
  $dateFrom = $_GET['statFrom'] ?? date("Y-m-01");
  $datTo = $_GET['statTo'] ?? date("Y-m-d");

  $salesSQL = "SELECT * FROM `f_sales`"
			 . "LEFT JOIN `entities` ON (`identities` = `f_salesEntity`) WHERE `f_salesDate`>='" . $dateFrom . "' AND `f_salesDate`<='" . $datTo . "'"; // WHERE `f_salesCreditManager` ='111'

  $sales = query2array(mysqlQuery($salesSQL));

  $conds = [];

  if ($_GET['filterName'] ?? null) {
	 $conds[] = " AND `clientsLName` LIKE '%" . mysqli_real_escape_string($link, trim($_GET['filterName'])) . "%'";
  }

  if ($_GET['filterDate'] ?? null) {
	 $conds[] = " AND `f_salesDate` = '" . mysqli_real_escape_string($link, trim($_GET['filterDate'])) . "'";
  }

  if ($_GET['filterSumm'] ?? null) {
	 $conds[] = " AND `f_salesSumm` = '" . mysqli_real_escape_string($link, trim($_GET['filterSumm'])) . "'";
  }
  if ($_GET['filterEnty'] ?? null) {
	 $conds[] = " AND `identities` = '" . mysqli_real_escape_string($link, trim($_GET['filterEnty'])) . "'";
  }


  if ($_GET['filterBank'] ?? null) {
	 $conds[] = " AND `idRS_banks` = '" . mysqli_real_escape_string($link, trim($_GET['filterBank'])) . "'";
  }


  if (($_GET['filterPaymentMethod'] ?? '') == '1' || ($_GET['filterPaymentMethod'] ?? '') == '2') {
	 $conds[] = " AND `f_paymentsType` = '" . mysqli_real_escape_string($link, trim($_GET['filterPaymentMethod'])) . "'";
  }


  if (($_GET['filterPaymentMethod'] ?? '3') == '3') {
	 $credits = query2array(mysqlQuery(""
						  . " SELECT * FROM `f_credits` "
						  . " LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID` )"
						  . " LEFT JOIN `clients` ON (`idclients` = `f_salesClient` )"
						  . " LEFT JOIN `RS_banks` ON (`idRS_banks` = `f_creditsBankID`)"
						  . " LEFT JOIN `RS_brokers` ON (`idRS_brokers` = `f_creditsBroker`)"
						  . " LEFT JOIN `entities` ON (`identities` = `f_salesEntity`) "
						  . " WHERE"
						  . " `f_salesDate`>='" . $dateFrom . "' "
						  . " AND `f_salesDate`<='" . $datTo . "'"
						  . "  " . implode(' ', $conds)
	 ));
  }




  if (!($_GET['filterBank'] ?? null) && (!($_GET['filterPaymentMethod'] ?? false) || $_GET['filterPaymentMethod'] == 1 || $_GET['filterPaymentMethod'] == 2)) {
	 $payments = query2array(mysqlQuery(""
						  . " SELECT * FROM `f_payments` "
						  . " LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID` )"
						  . " LEFT JOIN `clients` ON (`idclients` = `f_salesClient` )"
						  . " LEFT JOIN `entities` ON (`identities` = `f_salesEntity`) "
						  . " LEFT JOIN `f_paymentsTypes` ON (`idf_paymentsTypes` = `f_paymentsType`) "
						  . " WHERE "
						  . " `f_salesDate`>='" . $dateFrom . "'"
						  . " AND `f_salesDate`<='" . $datTo . "'"
						  . " " . implode(' ', $conds)));
  }


//	printr($credits[0] ?? '-');
//	printr($payments[0] ?? '-');
//	printr(count($credits));
//	printr(count($payments));
//	$transactions =
//mb_ucfirst($sale['client']['clientsLName']);

  $transactions = [];
  foreach (($credits ?? []) as $credit) {
	 $subscriptions = query2array(mysqlQuery(""
						  . "SELECT `idservicesTypes`,`servicesTypesName`"
						  . "FROM `f_subscriptions` "
						  . "LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
						  . "LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"
						  . "WHERE `f_subscriptionsContract`=  '" . $credit['idf_sales'] . "'"
						  . "GROUP BY `idservicesTypes`"));

	 $transactions[] = [
		  'idf_sales' => $credit['idf_sales'],
		  'dateTS' => strtotime($credit['f_salesDate']),
		  'dateHR' => date('d.m.Y', strtotime($credit['f_salesDate'])),
		  'client' => mb_ucfirst($credit['clientsLName'] ?? '') . ' ' . mb_ucfirst($credit['clientsFName'] ?? '') . ' ' . mb_ucfirst($credit['clientsMName'] ?? ''),
		  'type' => implode(' / ', array_unique(array_filter(array_column($subscriptions, 'servicesTypesName')))),
		  'method' => 'Банк',
		  'RS_brokersName' => $credit['RS_brokersName'],
		  'bank' => $credit['RS_banksShort'],
		  'summ' => $credit['f_creditsSumm'],
		  'identities' => $credit['identities'],
		  'entity' => $credit['entitiesName']
	 ];
  }
  foreach (($payments ?? []) as $payment) {
	 $subscriptions = query2array(mysqlQuery(""
						  . "SELECT `idservicesTypes`,`servicesTypesName`"
						  . "FROM `f_subscriptions` "
						  . "LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
						  . "LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"
						  . "WHERE `f_subscriptionsContract`=  '" . $payment['idf_sales'] . "'"
						  . "GROUP BY `idservicesTypes`"));

	 $transactions[] = [
		  'idf_sales' => $payment['idf_sales'],
		  'dateTS' => strtotime($payment['f_salesDate']),
		  'dateHR' => date('d.m.Y', strtotime($payment['f_salesDate'])),
		  'client' => mb_ucfirst($payment['clientsLName'] ?? '') . ' ' . mb_ucfirst($payment['clientsFName'] ?? '') . ' ' . mb_ucfirst($payment['clientsMName'] ?? ''),
		  'type' => implode(' / ', array_unique(array_filter(array_column($subscriptions, 'servicesTypesName')))),
		  'method' => $payment['f_paymentsTypesName'],
		  'bank' => '',
		  'summ' => $payment['f_paymentsAmount'],
		  'identities' => $payment['identities'],
		  'entity' => $payment['entitiesName']
	 ];
  }
//	die();
  usort($transactions, function ($a, $b) {
	 if ($a['dateTS'] <=> $b['dateTS']) {
		return $a['dateTS'] <=> $b['dateTS'];
	 }
	 return $a['client'] <=> $b['client'];
  });
  $banks = query2array(mysqlQuery("SELECT * FROM `RS_banks` WHERE NOT isnull(`RS_banksShort`)"));
  usort($banks, function ($a, $b) {
	 return mb_strtolower($a['RS_banksShort']) <=> mb_strtolower($b['RS_banksShort']);
  });
  ?>
  <br>
  <div class="box neutral">
    <div class="box-body">
  	 <div style="display: inline-block">
  		<div class="lightGrid" style="display: grid; white-space: nowrap; font-size: 8pt; line-height: 8pt; grid-template-columns: repeat(8, auto);;">
  		  <div style="display: contents; font-weight: bold; text-align: center;">
  			 <div>Дата</div>
  			 <div>Абон</div>
  			 <div>ФИО клиента</div>
  			 <div>Брокер</div>
  			 <div>Метод оплаты</div>
  			 <div>Банк</div>
  			 <div>Cумма</div>
  			 <div>Юр. лицо</div>
  		  </div>

  		  <div style="display: contents; font-weight: bold; text-align: center;">
  			 <div><input oninput="GR({'filterDate': this.value});" type="date" value="<?= $_GET['filterDate'] ?? ''; ?>" style="font-size: 8pt; line-height: 8pt;"></div>
  			 <div></div>
  			 <div><input type="text" value="<?= $_GET['filterName'] ?? ''; ?>"  style="font-size: 8pt; line-height: 8pt;" onkeypress="if (event.keyCode == 13) {
  					 GR({'filterName': this.value});
  				  }"></div>
  			 <div>
          <!--							<input type="text" value="<?= $_GET['filterSaleType'] ?? ''; ?>" style="font-size: 8pt; line-height: 8pt;" onkeypress="if (event.keyCode == 13) {
  		 GR({'filterSaleType': this.value});
           }">-->

  			 </div>
  			 <div><select value="<?= $_GET['filterPaymentMethod'] ?? ''; ?>" style="font-size: 8pt; line-height: 8pt; border-radius: 10px 0px 0px 10px;" onchange="GR({'filterPaymentMethod': this.value});">
  				  <option></option>
  				  <option value="1"<?= 1 == ($_GET['filterPaymentMethod'] ?? '') ? ' selected' : ''; ?>>Наличные</option>
  				  <option value="2"<?= 2 == ($_GET['filterPaymentMethod'] ?? '') ? ' selected' : ''; ?>>Безналичные</option>
  				  <option value="3"<?= 3 == ($_GET['filterPaymentMethod'] ?? '') ? ' selected' : ''; ?>>Банк</option>

  				</select></div>
  			 <div><select value="<?= $_GET['filterBank'] ?? ''; ?>" style="font-size: 8pt; line-height: 8pt; border-radius: 10px 0px 0px 10px;" onchange="GR({'filterBank': this.value});">
  				  <option></option>
					 <?
					 foreach ($banks as $bank) {
						?>
	 				  <option<?= $bank['idRS_banks'] == ($_GET['filterBank'] ?? '') ? ' selected' : ''; ?> value="<?= $bank['idRS_banks']; ?>"><?= $bank['RS_banksShort']; ?></option><?
					 }
					 ?>
  				</select></div>
  			 <div><input type="text" value="<?= $_GET['filterSumm'] ?? ''; ?>" style="font-size: 8pt; line-height: 8pt; width: auto; " onkeypress="if (event.keyCode == 13) {
  					 GR({'filterSumm': this.value});
  				  }"></div>
  			 <div><select value="<?= $_GET['filterEnty'] ?? ''; ?>" style="font-size: 8pt; line-height: 8pt; border-radius: 10px 0px 0px 10px;" onchange="GR({'filterEnty': this.value});">
  				  <option></option>
  				  <option value="1"<?= 1 == ($_GET['filterEnty'] ?? '') ? ' selected' : ''; ?> >ООО «Инфинити»</option>
  				  <option value="2"<?= 2 == ($_GET['filterEnty'] ?? '') ? ' selected' : ''; ?> >ООО «Инфинити Стом»</option>
  				</select></div>



  		  </div>

			 <?
			 foreach ($transactions as $pSale) {
//					  printr($pSale);
				?>
	 		  <div style="display: contents;">
	 			 <div><?= $pSale['dateHR']; ?></div>
	 			 <div><?= $pSale['idf_sales']; ?></div>
	 			 <div><?= $pSale['client']; ?></div>
	 			 <div style="text-align: center;"><?= $pSale['RS_brokersName'] ?? '-'; ?></div>
	 			 <div style="text-align: center;"><?= $pSale['method']; ?></div>
	 			 <div style="text-align: center;"><?= $pSale['bank'] ?? '' ?></div>
	 			 <div style="text-align: right;"><?= nf($pSale['summ'], 2); ?></div>
	 			 <div style="text-align: center;"><?= $pSale['entity'] ?? '-' ?></div>
	 		  </div>
				<?
			 }
			 ?>
  		</div>
  	 </div>
    </div>
  </div>

  <?
//	printr($sales);
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
