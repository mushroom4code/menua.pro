<!DOCTYPE html>

<html>
	<head>
		<title>TODO supply a title</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
			.sqr {
				width: 10px;
				height: 10px;
				border: 1px solid silver;
				display: inline-block;
			}
			.delivered {
				background-color: lightgreen;
			}
			.awaiting_report {
				background-color: yellow;
			}
			.insufficient_balance {
				background-color: plum;
			}
			.unprocessed {
				background-color: gray;
			}
			.failed {
				background-color: red;
			}
			.ready {
				background-color: orange;
			}
			.null {
				background-color: lightblue;
			}

		</style>
	</head>
	<body>
		<?php
		include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
		$messages = query2array(mysqlQuery("SELECT * FROM `sms` LEFT JOIN `clientsPhones` ON (`idclientsPhones` = `smsPhone`)   WHERE `smsTime`>='" . date("Y-m-d") . " 00:00:00' order by `idsms`;"));

//printr($messages);
		print count($messages);
		?>

		<input type="button" value="Сканировать" onclick="recheck();"><br>
		<?
		foreach ($messages as $message) {
			?>
			<a data-msgid="<?= $message['smsHash']; ?>" title="<?= $message['clientsPhonesPhone'] ?>" class="sqr <?= $message['smsState'] ?? 'null'; ?>"></a>
			<?
		}
		?>



		<script src="/sync/js/basicFunctions.js" type="text/javascript"></script>
		<script>

			let elements = qsa(`[data-msgid]`);
			let offset = 100;
			function recheck() {
				elements.forEach(el => {
					offset += 100 + Math.random() * 500;
					//		el.style.backgroundColor = 'silver';
					setTimeout(async function () {
						el.style.backgroundColor = 'lightskyblue';
						await fetch(`/pages/sms/IO.php?smsHash=${el.dataset.msgid}`, {
							credentials: 'include',
							method: 'GET', headers: new Headers({'Content-Type': 'application/json'})
						}).then(result => result.text()).then(async function (text) {
							//				console.log(text);
							try {
								let jsn = JSON.parse(text);
								if (jsn.status) {
									el.title = jsn.status;
									if (jsn.status === 'delivered') {
										el.style.backgroundColor = 'lightgreen';
									} else if (jsn.status === 'awaiting_report') {
										el.style.backgroundColor = 'yellow';
									} else if (jsn.status === 'insufficient_balance') {
										el.style.backgroundColor = 'plum';
									} else if (jsn.status === 'unprocessed') {
										el.style.backgroundColor = 'gray';
									} else if (jsn.status === 'failed') {
										el.style.backgroundColor = 'red';
									} else if (jsn.status === 'ready') {
										el.style.backgroundColor = 'orange';
									}
								} else {
									el.style.backgroundColor = 'pink';
								}
							} catch (e) {
								//					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
							}
						});
					}, offset);

				});

			}

		</script>
		<hr>
		<div><span class="sqr delivered"></span> - Сообщениедоставлено</div>
		<div><span class="sqr awaiting_report"></span> - Сообщение принято оператором, но ещё не был получен статус доставки</div>
		<div><span class="sqr insufficient_balance"></span> - Сообщение не отправлено,так как возникла ошибка тарификации</div>
		<div><span class="sqr unprocessed"></span> - Не обработано</div>
		<div><span class="sqr failed"></span> - Сообщение не доставлено</div>
		<div><span class="sqr ready"></span> - Сообщение принято платформой, но еще не принято оператором на доставку</div>
		<div><span class="sqr null"></span> - Ещё не получен статус сообщения.</div>
	</body>
</html>