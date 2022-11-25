<?php

if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	$_ROOTPATH = '/var/www/html/' . $_GET['root'];
} elseif (isset($_SERVER['DOCUMENT_ROOT'])) {
	$_ROOTPATH = $_SERVER['DOCUMENT_ROOT'];
} else {
	$_ROOTPATH = 'undefined';
}
include $_ROOTPATH . '/sync/includes/setupLight.php';
//https://api.sbis.ru/ofd/v1/orgs/

foreach (query2array(mysqlQuery("SELECT * FROM `entities` WHERE "
				. " NOT isnull(`SBISapp_client_id`) "
				. " AND NOT isnull(`SBISapp_secret`) "
				. " AND NOT isnull(`SBISsecret_key`) "
				. " "))as $entity) {
	$auth = json_encode([
		'app_client_id' => $entity['SBISapp_client_id'],
		'app_secret' => $entity['SBISapp_secret'],
		'secret_key' => $entity['SBISsecret_key']
	]);
	$ch = curl_init('https://api.sbis.ru/oauth/service/');
	curl_setopt_array($ch, array(
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HEADER => 0,
		CURLOPT_POSTFIELDS => $auth,
		CURLOPT_HTTPHEADER => array(
			'Content-type: charset=utf-8'
		)
	));
	$response = json_decode(curl_exec($ch), 1);
	mysqlQuery("INSERT INTO `entitiesSBISkeys` SET "
			. " `access_token` ='" . mres($response['access_token']) . "',"
			. " `sid` ='" . mres($response['sid']) . "',"
			. " `token` ='" . mres($response['token']) . "',"
			. " `entitiesSBISEntity` ='" . mres($entity['identities']) . "'");
	printr($response);
	curl_close($ch);
}

/*
POST https://api.sbis.ru/retail/sale/create
X-SBISAccessToken: 
"IXRnMUREP2g1fi93XjYqYVRVbUdZPCxuUFpwLEVDKlspSH5MZTNweDVnJDw2d0kmUzo6RztDN2RXaiVVbFgyQTIwMjAtMDYtMDQgMDk6Mzg6MTYuODA2MzM4"
{
   "companyID": "132",
   "kktRegNumber": "0003456798763214",
   "cashierFIO": "Иванов",
   "operationType": "1",
   "cashSum": "100",
   "bankSum": null,
   "internetSum": null,
   "accountSum": null,
   "postpaySum": null,
   "prepaySum": null,
   "vatNone": null,
   "vatSum0": null,
   "vatSum10": null,
   "vatSum20": null,
   "vatSum110": null,
   "vatSum120": "100",
   "allowRetailPayed": "1",
   "nomenclatures": [
      {
         "nameNomenclature": "Интернет товар",
         "barcodeNomenclature": "123456",
         "priceNomenclature": "100",
         "quantityNomenclature": "1",
         "measureNomenclature": "ШТ",
         "kindNomenclature": "Т",
         "totalPriceNomenclature": "100",
         "taxRateNomenclature": "10",
         "totalVat": "100"
      }
   ],
   "customerFIO": null,
   "customerEmail": null,
   "customerPhone": null,
   "customerINN": null,
   "customerExtId": null,
   "taxSystem": "1",
   "sendEmail": "test@test.ru",
   "propName": null,
   "propVal": null,
   "comment": "тестовый чек",
   "payMethod": "4"
}
 
 */ 