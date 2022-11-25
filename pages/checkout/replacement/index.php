<?php
$pageTitle = 'Аннулирование / замена';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(26)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(195)) {
	?>E403R195<?
} else {

	function subscriptionsSumm($subscriptions) {
		usort($subscriptions, function ($a, $b) {
			return strtotime($a['f_subscriptionsDate']) <=> strtotime($b['f_subscriptionsDate']);
		});
		$OUT = [];
		foreach ($subscriptions as $subscription3) {
			$found = false;
			foreach ($OUT as &$OUTelem) {
				if (
						$OUTelem['f_salesContentService'] === $subscription3['f_salesContentService'] &&
						$OUTelem['f_salesContentPrice'] === $subscription3['f_salesContentPrice']
				) {
					$found = true;
					$OUTelem['f_salesContentQty'] += $subscription3['f_salesContentQty'];
				}
			}
			if (!$found) {
				$OUT[] = $subscription3;
			}
		}
		$filtered = array_filter($OUT, function ($el) {
			return $el['f_salesContentQty'] > 0;
		});
		return $filtered;
	}

	$contract = mfa(mysqlQuery(
					"SELECT * FROM "
					. "`f_sales` "
					. "LEFT JOIN `clients` ON (`idclients`= `f_salesClient`) WHERE `idf_sales` = '" . FSI($_GET['sale']) . "'"));

	$servicesApplied = query2array(mysqlQuery(""
					. " SELECT * FROM `servicesApplied`"
					. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
					. " LEFT JOIN `users` ON (`idusers` = `servicesAppliedPersonal`)"
					. " WHERE `servicesAppliedClient` = '" . $contract['idclients'] . "'"
					. " AND isnull(`servicesAppliedDeleted`)"
	));

	$subscriptions = subscriptionsSumm(query2array(mysqlQuery("SELECT "
							. "*,"
							. "`idservices`,"
							. "`idf_subscriptions`,"
							. "`servicesName`,"
							. "`f_salesContentPrice`,"
							. "`f_salesContentQty`"
							. " FROM `f_subscriptions`"
							. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
							. " LEFT JOIN `f_sales` ON (`idf_sales`=`f_subscriptionsContract`)"
							. " LEFT JOIN `clients` ON (`idclients` = `f_salesClient`)"
							. " WHERE `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'")));
	$contract2['subscriptions'] = [];
	foreach ($subscriptions as $subscription) {

		$done = array_sum(array_column(array_filter($servicesApplied, function ($element) {
							global $subscription;
							return (
							$element['servicesAppliedContract'] === $subscription['f_subscriptionsContract'] &&
							$element['servicesAppliedService'] === $subscription['f_salesContentService'] &&
							$element['servicesAppliedPrice'] === $subscription['f_salesContentPrice'] &&
							$element['servicesAppliedFineshed']);
						}), 'servicesAppliedQty'));
		$reserved = array_sum(array_column(array_filter($servicesApplied, function ($element) {
							global $subscription;
							return (
							$element['servicesAppliedContract'] === $subscription['f_subscriptionsContract'] &&
							$element['servicesAppliedService'] === $subscription['f_salesContentService'] &&
							$element['servicesAppliedPrice'] === $subscription['f_salesContentPrice'] &&
							!$element['servicesAppliedFineshed']);
						}), 'servicesAppliedQty'));

//			$remains =

		$subscription['max'] = $subscription['f_salesContentQty'] - $reserved - $done;
		$subscription['f_salesContentQty'] = $subscription['f_salesContentQty'] - $reserved - $done;
		$subscription['reserved'] = $reserved;
		$subscription['done'] = $done;

		$contract2['subscriptions'][] = $subscription;
	}



	usort($contract2['subscriptions'], function ($a, $b) {
		return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
	});
	?>
	<style>
		input[type='button']:disabled {
			background-color: pink;
		}
		.lightGrid {
			border-left: 1px solid silver;
			border-top: 1px solid silver;

		}
		.lightGrid>div {
			padding: 0px 10px;
			border-right: 1px solid silver;
			border-bottom: 1px solid silver;					
		}
		.suggestions {
			position: absolute;
			width: auto;
			background-color: white;
			border: 1px solid silver;
			box-shadow: 0px 0px 10px hsla(0,0%,0%,0.3);
			border-radius: 4px;
			z-index: 10;
			list-style: none;
			white-space: nowrap;
		}
		.suggestions .red {
			color: red;
		}
		.suggestions span {
			color: gray;
		}
		.suggestions li {
			font-size: 0.8em;
			padding: 2px 10px;
			cursor: pointer;
		}
		.suggestions li .mask{
			position: absolute;
			top: 0px;
			left: 0px;
			width: 100%;
			height: 100%;
			z-index: 10;
		}

		.suggestions li .mask:hover{
			background-color:  hsla(0,0%,0%,0.1);
		}

		.suggestions li .pointed{
			background-color:  hsla(0,0%,0%,0.1);
		}

		.displayContents{
			display: contents;
		}
		.repTable{
			display: grid;
			grid-template-columns: auto auto;
		}
		.repTable>div {
			min-height: 10px;
			padding: 10px;
			/*border: 1px solid red;*/
		}
	</style>
	<script src="/pages/checkout/javascript/recursiveReduce.js" type="text/javascript"></script>
	<div class="box neutral ">
		<div class="boxx-body" style="padding: 5px 10px;">
			<!--			<h2 style="background-color: white;">Title</h2>-->


			<a href="/pages/checkout/payments.php?client=<?= $contract['f_salesClient']; ?>&contract=<?= $contract['idf_sales']; ?>" style="padding: 10px; display: inline-block;">...назад</a>
			<h3 class="C"><?= $contract['clientsLName']; ?> <?= $contract['clientsFName']; ?> <?= $contract['clientsMName'] ?? ''; ?></h3>
			<h3 class="C">	<?= date("d.m.Y", strtotime($contract['f_salesDate'])); ?></h3>
			<?
			$_toAppend = query2array(mysqlQuery("SELECT * FROM "
							. " `f_subscriptions`"
							. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
							. " WHERE"
							. " `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'"
							. "AND `f_subscriptionsDate`>='" . date("Y-m-d 00:00:00") . "'"
							. "AND `f_subscriptionsDate`<='" . date("Y-m-d 23:59:59") . "'"
							. "AND `f_salesContentQty`>0"
							. ""));

			$_toRemove = query2array(mysqlQuery("SELECT * FROM `f_subscriptions` "
							. " LEFT JOIN `services` ON (`idservices` = `f_salesContentService`)"
							. "WHERE"
							. " `f_subscriptionsContract` = '" . $contract['idf_sales'] . "'"
							. "AND `f_subscriptionsDate`>='" . date("Y-m-d 00:00:00") . "'"
							. "AND `f_subscriptionsDate`<='" . date("Y-m-d 23:59:59") . "'"
							. "AND `f_salesContentQty`<0"
							. ""));

//			printr($_toAppend[0]);
//			printr($_toRemove[0]);
			?>
			<script>
				let _contract = <?= $contract['idf_sales']; ?>;
				let _remains = <?= json_encode(refine($contract2['subscriptions'], ['servicesName', 'f_salesContentQty', 'idservices', 'f_salesContentPrice']), 288); ?>;
				let _toRemove = <?= json_encode($_toRemove, 288); ?>;
				let _toAppend = <?= json_encode($_toAppend, 288); ?>;


				let _coordinators = <?=
		json_encode(query2array(mysqlQuery("SELECT "
								. "`idusers` as `id`,"
								. "`usersFirstName` as `fname`, "
								. "`usersLastName` as `lname`, "
								. "`usersMiddleName` as `mname` "
								. "FROM `f_salesReplacementsCoordinator`"
								. "LEFT JOIN `users` ON (`idusers` = `f_salesReplacementsCoordinatorCurator`)"
								. " WHERE `f_salesReplacementsCoordinatorContract` = '" . $contract['idf_sales'] . "' AND `f_salesReplacementsCoordinatorDate` = CURDATE();")), 288);
		?>;
			</script>

			<div class="repTable">
				<div>

					<div style="border: 1px solid silver; background-color: white; border-radius: 5px; display: inline-block; padding: 10px; display: grid; grid-template-columns: auto auto auto auto; grid-gap: 3px 15px; margin-bottom: 10px;">
						<div style="grid-column: 1/-1; text-align: center; font-weight: bold;">Остатки</div>
						<div>Наименование</div>
						<div>Количество</div>
						<div>Цена</div>
						<div>Сумма</div>
						<div id="remainsWrapper" style="display: contents;"></div>
					</div>

					<div style="border: 1px solid silver; background-color: white; border-radius: 5px; padding: 10px;">
						<div style=" text-align: center; font-weight: bold;">Дополнительная инфомрация</div>
						<div>
							<div>Ответственный координатор:</div>
							<div id="coordinators" style="display: grid; grid-template-columns: 1fr auto; grid-gap: 5px;"></div>
							<div style="display: contents;"><div style="padding: 15px;"><input type="text" autocomplete="off"  id="coordinatorsLname"  placeholder="фамилия" oninput="searchCoordsByLastName(this.value);" onblur="setTimeout(function () {
											qs('#coordsSuggestions').innerHTML = '';
										}, 300);"><ul id="coordsSuggestions" class="suggestions"></ul>
								</div>

							</div>
							<div>
								<div>Комментарий:</div>

								<div style="padding: 0px; margin-top: 10px;">
									<textarea autocomplete="off" autoco oninput="_comment = this.value;" style="width: 100%;
											  font-size: 14px;
											  padding: 0.2em 0.5em;
											  background-color: white;
											  color: #000000b0;
											  border: 0;
											  border-radius: 10px;
											  box-shadow: 0.2em 0.2em 7px rgba(122,122,122,0.5);
											  width: 100%;
											  height: 150px;
											  resize: none;"><?
											  print $_comment = mfa(mysqlQuery("SELECT * FROM `f_salesReplacementComments` WHERE"
																	  . " `f_salesReplacementCommentsContract`='" . $contract['idf_sales'] . "'"
																	  . " AND `f_salesReplacementCommentsDate`= CURDATE()"
																	  . ""))['f_salesReplacementCommentsText'] ?? '';
											  ?></textarea>
								</div>
							</div>
						</div>
					</div>


				</div>
				<div>
					<div style="border: 1px solid silver; background-color: hsl(0,100%,95%); border-radius: 5px; display: block; padding: 10px; margin-bottom: 10px;">
						<div style="display: grid; grid-template-columns: auto auto auto auto; grid-gap: 3px 15px;">
							<div style="grid-column: 1/-1; text-align: center; font-weight: bold;">Список услуг к удалению</div>
							<div>Наименование</div>
							<div>Количество</div>
							<div>Цена</div>
							<div>Сумма</div>
							<div id="toRemoveWrapper" style="display: contents;"></div>
						</div>
					</div>

					<div style="border: 1px solid silver; background-color:  hsl(120,100%,95%); border-radius: 5px; display: block; padding: 10px; margin-bottom: 10px;">
						<div style="display: grid; grid-template-columns: auto auto auto auto; grid-gap: 3px 15px;">
							<div style="grid-column: 1/-1; text-align: center; font-weight: bold;">Список услуг к добавлению</div>
							<div>Наименование</div>
							<div>Количество</div>
							<div>Цена</div>
							<div>Сумма</div>
							<div id="toAppendWrapper" style="display: contents;"></div>
						</div>
					</div>

					<div style="border: 1px solid silver; background-color: hsla(220,100%,95%,1); border-radius: 5px;  display: inline-block;  padding: 10px; display: grid; grid-template-columns: auto auto auto auto; grid-gap: 10px 15px;">
						<div style="grid-column: 1/-1; text-align: center; font-weight: bold;">Поиск услуг</div>
						<div style="grid-column: 1/-1;">
							<div style="align-self: center; position: relative;">
								<input type="text" placeholder="Поиск" id="serviceSearch" autocomplete="off" onkeydown="let confirm = false;
											if (event.keyCode === 38) {
												pointer--;
												suggestRemains(this.value, confirm);
											} else if (event.keyCode === 40) {
												pointer++;
												suggestRemains(this.value, confirm);
											}
											if (event.keyCode === 13) {
												confirm = true;
												suggestRemains(this.value, confirm);
											}" oninput="pointer = 0; suggestRemains(this.value);" style="display: inline; width: auto;">
								<ul id="suggestions" class="suggestions">
								</ul>
							</div>
						</div>

						<div style="display: contents; margin: 20px 0;">
							<div>Наименование</div>
							<div>Количество</div>
							<div>Цена</div>
							<div>Сумма</div>
						</div>
						<div id="candidateWrapper" style="display: contents; margin: 20px 0;"></div>
						<div style="grid-column: 1/-1; text-align: center; font-weight: bold;"><input type="button" id="addCandidate" value="Добавить" onclick="addCandidate();console.log(_candidate);"></div>
					</div>
				</div>
			</div>
			<div style="border: 1px solid silver; background-color: white; border-radius: 5px; margin: 10px; padding: 10px;">
				<div style=" text-align: center; font-weight: bold;">Итого:</div>
				<div id="toAppendTotalWrapper"></div>
			</div>



			<div style="grid-column: 1/-1; text-align: center; font-weight: bold;"><input type="button" value="Сохранить" onclick="saveReplace();"></div>

			<script>
				let _comment = <?= json_encode($_comment, 288); ?>;

				document.addEventListener("DOMContentLoaded", () => {
					renderRemains(_remains);
					renderToAppend();
					renderToRemove();
					renderCoordinators();
				});

			</script>
		</div>
	</div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
