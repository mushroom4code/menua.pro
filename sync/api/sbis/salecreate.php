<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setupLight.php';
?>
Hello
<?
//https://api.sbis.ru/ofd/v1/orgs/
$auth = json_encode(['app_client_id' => '4721820222192201',
	'app_secret' => 'DRDKDMOPHKPNTDTLUATX2JNJ',
	'secret_key' => 'ITJ1TLGOQqqPlhdITP7zG4HzLOQjHk7bmiDugZbwTTcsqfZvBuG2woUTjLUUvU48fUnV0d4qrNAbnHcvYzfSdJYd9JHpq2pTNJzvFFWj9HLwYZ5eem9lil']);

$ch = curl_init('https://api.sbis.ru/retail/sale/create');
curl_setopt_array($ch, array(
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_POST => true,
	CURLOPT_HEADER => 0,
	CURLOPT_POSTFIELDS => $auth,
	CURLOPT_HTTPHEADER => array(
		'Content-type: charset=utf-8',
		'X-SBISAccessToken: azJTZCU0RTRrN1dKTFckIVVqJXN0eWpOP0lAVlhDQ10xaTUqZDhSP0l5fHNJZjhtSGtSWClTQzZLXkp1WHltRTIwMjEtMTAtMTEgMDY6MDA6MDIuMDg1Mzk0'
	)
));
$response = curl_exec($ch);
printr(json_decode($response));
curl_close($ch);
/*
 * 
 * Основная задача - передать на кассу необходимую для оплаты сумму.
 * 
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