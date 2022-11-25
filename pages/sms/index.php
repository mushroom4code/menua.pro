<?php
$load['title'] = $pageTitle = 'Эсэмэски';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (1) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!1) {
	?>E403R<?
} else {
	?>
	<style>
		.cell {
			display: inline-block;
			width: 10px;
			height: 10px;
			background-color: silver;
			margin: 1px;
		}
	</style>
	<div class="box neutral">
		<div class="box-body">
			<textarea style="width: 300px; height: 100px;" id="message" oninput="qs('#cntr').innerHTML = `Частей сообщения: ${Math.ceil(this.value.length/67)}`">CLIENTNAME, МЦ "Инфинити" поздравляет Вас с Новым годом и наступающим рождеством и напоминает, что у Вас остались следующие процедуры (REMAINS): https://infiniti-clinic.ru/r/?h=CLIENTHASH
		тел.6122063</textarea>
			<div style="display: inline-block;">
				REMAINS, CLIENTHASH, CLIENTNAME	
			</div>

			<div id="cntr"></div>
			<input type="button" onclick="send();" value="Отправить" style="margin: 10px;">
			<?
			$phones = query2array(mysqlQuery("SELECT * FROM "
							. "`clientsPhones` LEFT JOIN `clients` ON (`idclients` = `clientsPhonesClient`) "
							. "WHERE "
							. "isnull(`clientsPhonesDeleted`) AND idclients = 112 "
//							. " `clientsPhonesPhone` IN('89052084769','89819541216')  AND "
//							. "(SELECT COUNT(1) FROM `sms` WHERE `smsText`='Новогодняя акция до конца декабря -  КТ челюсти ВСЕГО  990р вместо 3200! т.6122063,8960247264.Ждем вас МЦ\"Инфинити\", Московский пр.111' AND `smsPhone` = `idclientsPhones` AND NOT `smsState`='insufficient_balance') = 0 "
//					. "AND (SELECT COUNT(1) FROM `sms` WHERE `smsText`='Новогодняя акция до конца декабря -  КТ челюсти ВСЕГО  990р вместо 3200! т.6122063,89602472647.Ждем вас МЦ\"Инфинити\",Московский пр.111' AND `smsPhone` = `idclientsPhones`) = 0 "
//							. "isnull(`clientsPhonesDeleted`) AND"
//(clientsPhonesPhone like '8900%' OR
//clientsPhonesPhone like '8902%' OR
//clientsPhonesPhone like '8904%' OR
//clientsPhonesPhone like '8908%' OR
//clientsPhonesPhone like '8950%' OR
//clientsPhonesPhone like '8951%' OR
//clientsPhonesPhone like '8952%' OR
//clientsPhonesPhone like '8953%')"
//							. " LIMIT 1000"
							. ""));
			foreach ($phones as &$phone) {
				
			}
			$n = 0;
			?><div style="line-height: 10px;">
			<?
			foreach ($phones as &$phone) {
				$phone['clientsPhonesPhone'] = preg_replace('/\D/', '', $phone['clientsPhonesPhone']);
				$remains = array_sum(array_column(getRemainsByClient($phone['idclients']), 'f_salesContentQty'));
				if (strlen($phone['clientsPhonesPhone']) !== 11 ||
						$remains <= 0
				) {
					continue;
				}
				$phone['clientsPhonesPhone'][0] = '7';
				$n++;
				?><a class="cell"
					   data-clientname="<?= $phone['clientsFName'] . ' ' . $phone['clientsMName']; ?>"
					   data-idphones="<?= $phone['idclientsPhones']; ?>"
					   data-clienthash="<?= $phone['clientsHash']; ?>" 
					   data-remains="<?= $remains; ?>" 
					   target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $phone['clientsPhonesClient']; ?>"></a><?
//			printr($phone);
//			break;
				   }
				   ?>
			</div>
			Телефонов: <?= $n; ?>
		</div>
	</div>
	<script>
		let idmsgs = [];
		function send() {
			let elements = qsa(`[data-idphones]`);
			let offset = 0;

			elements.forEach(el => {
				offset += 200;
				setTimeout(async function () {
					el.style.backgroundColor = 'lightskyblue';

					let text = qs(`#message`).value;
					text = text.replace("REMAINS", el.dataset.remains);
					text = text.replace("CLIENTNAME", el.dataset.clientname);
					text = text.replace("CLIENTHASH", el.dataset.clienthash);
					let inputData = {phone: el.dataset.idphones, text: text};
	//					console.log(inputData);
					await fetch('/sync/api/sms/masssend.php', {
						body: JSON.stringify(inputData),
						credentials: 'include',
						method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
					}).then(result => result.text()).then(async function (text) {
						try {
							let jsn = JSON.parse(text);
							if ((jsn.msgs || []).length) {
								jsn.msgs.forEach(async msg => {
									await MSG(msg);
								});
							}
							if (jsn.success) {
								el.style.backgroundColor = 'yellow';
								el.dataset.idmsg = jsn.idmsg;
							} else {
								el.style.backgroundColor = 'pink';
							}
						} catch (e) {
							//							MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
						}
					});

				}, offset);

			});
		}
		function checkMSG() {
			let elements = qsa(`[data-idmsg]`);
			let ids = [];
			elements.forEach(el => {
				ids.push(el.dataset.idmsg);
			});
			console.log(ids.length);
			if (ids.length) {
				fetch('/sync/api/sms/checkmsg.php', {
					body: JSON.stringify({checkIDs: ids}),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
						if (jsn.success) {
							if ((jsn.statuses || []).length) {
								jsn.statuses.forEach(ststus => {
									let el = qs(`[data-idmsg="${ststus.smsHash}"]`);
									if (ststus.smsState === 'delivered') {
										el.style.backgroundColor = 'green';
										delete el.dataset.idmsg;
									} else if (ststus.smsState === 'awaiting_report') {
										el.style.backgroundColor = 'orange';
									}
								});
							}
						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				});
			}



		}

		setInterval(function () {
			checkMSG();
		}, 3000);

	</script>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
