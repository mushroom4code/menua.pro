<?php
$pageTitle = 'Ресурсы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (empty($_GET['item'])) {
	header("Location: /pages/warehouse/goods");
	die();
}
?>

<div class="box neutral" style="display: none;">
	<div class="box-body">

		<?
		$units = query2array(mysqlQuery("SELECT * FROM `units`"));
		usort($units, function($a, $b) {
			return mb_strtolower($a['unitsName']) <=> mb_strtolower($b['unitsName']);
		});
		$nomenclatureSQL = "SELECT `nomenclature`.*,"
				. " `parentNomenclature`.`idWH_nomenclature` AS `idparent`,"
				. " `parentNomenclature`.`WH_nomenclatureName` AS `parentName`"
				. " FROM `WH_nomenclature` AS `nomenclature`"
				. " LEFT JOIN  `WH_nomenclature` AS `parentNomenclature` ON (`parentNomenclature`.`idWH_nomenclature`=`nomenclature`.`WH_nomenclatureParent`)"
				. " WHERE `nomenclature`.`idWH_nomenclature` = '" . FSI($_GET['item']) . "'";

		$nomenclature = mfa(mysqlQuery($nomenclatureSQL));
//		printr($nomenclature);
		?>

	</div>
</div>

<?
?>
<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
<ul class="horisontalMenu">
	<li><li><a href="/pages/warehouse/goods/index.php?<?= $nomenclature['idparent'] ? 'parent=' . $nomenclature['idparent'] : ''; ?>">Назад</a></li>
</ul>


<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
<div class="box neutral" style="display: inline-block;">
	<h2>Карточка: <?= $nomenclature['WH_nomenclatureName']; ?></h2>
	<div class="box-body">
		<table class="itemCardTable">
			<tr>
				<td class="btmdash">Раздел:</td>
				<td class="btmdash"><?
					if ($nomenclature['WH_nomenclatureEntryType'] == 1) {
						?>
						<?= $nomenclature['parentName'] ?? 'без раздела'; ?>
						<?
					} else {
						?>
						<? if (R(15)) { ?><a href="#" data-function="editField" data-field="itemParent" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['idparent']; ?>"><? } ?><?= $nomenclature['parentName'] ?? 'без раздела'; ?><? if (R(15)) { ?></a><? } ?>
						<?
					}
					?></td>
			</tr>
			<tr>
				<td class="btmdash">Название:</td>
				<td class="btmdash"><? if (R(15)) { ?><a href="#" data-function="editField" data-field="itemName" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureName']; ?>"><? } ?><?= $nomenclature['WH_nomenclatureName'] ?? 'не указано'; ?><? if (R(15)) { ?></a><? } ?></td>
			</tr>
			<tr>
				<td>Единица измерения:</td>
				<td><? if (R(15)) { ?><a href="#" data-function="editField" data-field="idunits" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureUnits']; ?>"><? } ?><?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsFullName'] ?? 'Не указана'; ?><? if (R(15)) { ?></a><? } ?></td>
			</tr>
			<tr>
				<td class="btmdash">Лимиты:</td>
				<td class="btmdash"><? //printr($nomenclature);          ?>
					<div style="display: grid; grid-template-columns: auto 40px auto ;">
						<span>min: <? if (R(15)) { ?><a href="#" data-function="editField" data-field="goodsMinLimit" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureMin'] ?? ''; ?>"><? } ?><?= ($nomenclature['WH_nomenclatureMin'] === null ? 'Не задан' : (round($nomenclature['WH_nomenclatureMin'], 3) . '<span class="small">' . array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] . '</span>')); ?><? if (R(15)) { ?></a><? } ?></span>
						<span></span>
						<span>max: <? if (R(15)) { ?><a href="#" data-function="editField" data-field="goodsMaxLimit" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureMax'] ?? ''; ?>"><? } ?><?= ($nomenclature['WH_nomenclatureMax'] ? (round($nomenclature['WH_nomenclatureMax'], 3) . '<span class="small">' . array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] . '</span>') : 'Не задан'); ?><? if (R(15)) { ?></a><? } ?></span>
					</div>
				</td>
			</tr>
		</table>

		<?
		$itemsSQL = "SELECT * FROM `WH_goods` WHERE `WH_goodsNomenclature`=" . $nomenclature['idWH_nomenclature'] . " ";
//		printr($itemsSQL);
		$items = query2array(mysqlQuery($itemsSQL));
		?>
		<pre><?
//			var_dump($items);
			?>
		</pre>
		<h3 style="text-align: center;">Товары</h3>
		<br>
		<table>
			<tr>
				<th>idDB</th>
				<th>Наименование</th>
				<th>Штрихкод</th>
				<th>Ед.изм.</th>
				<th>Единицы номенклатуры</th>
				<th>Отпуск со склада</th>
				<th>Остаток</th>
				<th></th>
			</tr>
			<?
			foreach ($items as $item) {
				$stocktakingSQL = "SELECT ifnull(`WH_stocktakingQty`,0) as `stQty`, ifnull(`WH_stocktakingDate`,'2020-02-02 00:00:00') as `stDate`  FROM `WH_stocktaking` WHERE `idWH_stocktaking` = (SELECT MAX(`idWH_stocktaking`) FROM `WH_stocktaking` WHERE `WH_stocktakingDate` = (SELECT MAX(`WH_stocktakingDate`) FROM `WH_stocktaking` WHERE `WH_stocktakingGoods` = '" . $item['idWH_goods'] . "'))";
//				print($stocktakingSQL);
				$stocktaking = mfa(mysqlQuery($stocktakingSQL));
//				print '<h1> ALALA' . printr($stocktaking) . 'ololo</h1>';
//				WH_stocktakingDate
				$inSQL = "SELECT ifnull(SUM(`WH_goodsInQty`),0) AS `inSumm` FROM `WH_goodsIn` WHERE `WH_goodsInGoodsId` =  '" . $item['idWH_goods'] . "' AND `WH_goodsInDate`>='" . ($stocktaking['stDate'] ?? '2020-02-02 00:00:00') . "';";
//				print "<br><br>" . ($inSQL);
				$in = mfa(mysqlQuery($inSQL));

				$outSQL = "SELECT ifnull(SUM(`WH_goodsOutQty`),0) AS `outSumm` FROM `WH_goodsOut` WHERE `WH_goodsOutItem` =  '" . $item['idWH_goods'] . "' AND `WH_goodsOutDate`>='" . ($stocktaking['stDate'] ?? '2020-02-02 00:00:00') . "';";
//				print "<br><br>" . ($outSQL);
				$out = mfa(mysqlQuery($outSQL));

				$balance = ($stocktaking['stQty'] + $in['inSumm'] - $out['outSumm']);


//				in
//				out
//				summ
				?>
				<tr>
					<td><?= $item['idWH_goods']; ?></td>
					<td><?= $item['WH_goodsName']; ?></td>
					<td style="text-align: center;"><?= $item['WH_goodBarCode']; ?></td>
					<td><?= array_search_2d($item['WH_goodsUnits'], $units, 'idunits')['unitsFullName'] ?? 'Не указана'; ?></td>
					<td style="text-align: center;">1<?= array_search_2d($item['WH_goodsUnits'], $units, 'idunits')['unitsName'] ?? '??'; ?> = <?= $item['WH_goodsNomenclatureQty'] ? floatval($item['WH_goodsNomenclatureQty']) : '??'; ?><?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] ?? '??'; ?></td>



					<td style="text-align: center;">1<?= array_search_2d($item['WH_goodsWHUnits'], $units, 'idunits')['unitsName'] ?? '??'; ?> = <?= $item['WH_goodsWHQty'] ? floatval($item['WH_goodsWHQty']) : '??'; ?><?= $item['WH_goodsWHUnits'] ? (array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName']) : '??'; ?></td>
					<td class="C"><a href="#" data-function="editField" data-field="ballance" data-units="<?= array_search_2d($item['WH_goodsWHUnits'], $units, 'idunits')['unitsName']; ?>" data-item="<?= $item['idWH_goods']; ?>" data-value="<?= $balance; ?>"><?= $balance; ?></a><?= array_search_2d($item['WH_goodsWHUnits'], $units, 'idunits')['unitsName']; ?></td>
					<td><button style="color: red;">&Cross;</button></td>
				</tr>
				<?
			}
			?>
			<script>
				function choeseItem(item) {
					console.log('choeseItem', item);
//					idwh_goods: 1027
//					wh_goodbarcode: 4987480010100
//					wh_goodsdeleted: "null"
//					wh_goodsname: "Лаеннек р-р д/ин. амп. 2 мл №10"
//					wh_goodsnomenclature: 934
//					wh_goodsnomenclatureqty: 20
//					wh_goodstype: 1
//					wh_goodsunits: 8
//					wh_goodswhqty: 2
//					wh_goodswhunits: 10

					if (item.wh_goodsname) {
						qs('#goodsName').value = item.wh_goodsname;
					}
					if (item.wh_goodbarcode) {
						qs('#goodsBarCode').value = item.wh_goodbarcode;
					}
					if (item.idwh_goods) {
						qs('#idgoods').value = item.idwh_goods;
					}

					if (item.wh_goodsunits) {
						qs('#itemUnits').value = item.wh_goodsunits;
						qs('#WH_nomenclatureUnits').value = item.wh_goodsunits;
					}
					if (item.wh_goodsnomenclatureqty && item.wh_goodsnomenclatureqty !== 'null') {
						qs('#wh_goodsnomenclatureqty').value = item.wh_goodsnomenclatureqty;
					}
					if (item.wh_goodswhunits) {
						qs('#WH_goodsWHUnits').value = item.wh_goodswhunits;
						qs('#WH_goodsWHUnits2').value = item.wh_goodswhunits;

					}
					if (item.wh_goodswhqty && item.wh_goodswhqty !== 'null') {
						qs('#wh_goodswhqty').value = item.wh_goodswhqty;
					}
					if (item.clear) {
						clear(qs(`#${item.clear}`));
					}

				}
				function searchGoodsByName(inputElement, resultsDiv) {
//					console.log(inputElement.value);
					clear(resultsDiv);
					if (inputElement.value.trim().length >= 3) {
						fetch('/pages/warehouse/goods/goods_IO.php', {
							body: JSON.stringify({action: 'searchGoods', search: inputElement.value.trim()}),
							credentials: 'include',
							method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
						}).then(result => result.text()).then(async function (text) {
							try {
								let jsn = JSON.parse(text);
								if ((jsn.items || []).length > 0) {
									jsn.items.forEach((item) => {
										let reg = new RegExp("(" + inputElement.value.trim() + ")", 'gi');
										let html = item.WH_goodsName.replace(reg, function (str) {//itemsName
											return '<b style="color: pink;">' + str + '</b>';
										});

										let params = '';

										for (let elem in item) {
											params += `data-${elem.toString().toLowerCase()}="${item[elem]}"`;
										}


										resultsDiv.appendChild(el('div', {innerHTML: `<div style="background-color: white; padding: 3px 10px; border: 1px solid silver; border-radius: 0px; white-space: nowrap;">${item.parentsName || ''} ${html || ''}<div style="position: absolute; width: 100%; height: 100%; cursor: pointer; top: 0px; left: 0px; z-index: 10;" data-clear="${resultsDiv.id}"  data-function="choeseItem" ${params}></div></div>`}));
									});
								}
							} catch (e) {
								MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
							}
						});
					}
				}


				function searchGoodsByBC(inputElement) {
					if (inputElement.value.trim().length >= 3) {
						fetch('/pages/warehouse/goods/goods_IO.php', {
							body: JSON.stringify({action: 'searchGoodsBC', search: inputElement.value.trim()}),
							credentials: 'include',
							method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
						}).then(result => result.text()).then(async function (text) {
							try {
								let jsn = JSON.parse(text);
								if ((jsn.items || []).length == 1) {
									console.log('jsn.items[0]', jsn.items[0]);
									let lcItem = {};
									for (let elem in jsn.items[0]) {
										lcItem[elem.toString().toLowerCase()] = jsn.items[0][elem];
									}
									choeseItem(lcItem);
								}
							} catch (e) {
								MSG("Ошибка парсинга ответа сервера: <br><br><i>" + e + "</i>");
							}
						});
					}
				}


				function saveGoodsToNomenclature() {
					let item = {
						id: qs('#idgoods').value.trim(),
						goodsName: qs('#goodsName').value.trim(),
						goodsBarCode: qs('#goodsBarCode').value.trim(),
						itemUnits: qs('#itemUnits').value.trim(),
						wh_goodsnomenclatureqty: qs('#wh_goodsnomenclatureqty').value.trim(),
						WH_goodsWHUnits: qs('#WH_goodsWHUnits').value.trim(),
						wh_goodswhqty: qs('#wh_goodswhqty').value.trim(),
						idWH_nomenclature: qs('#idWH_nomenclature').value.trim(),
						ballance: qs('#goodsQty').value.trim()
					};

					console.log(item);

					fetch('/pages/warehouse/goods/goods_IO.php', {
						body: JSON.stringify({action: 'saveGoodsToNomenclature', item: item}),
						credentials: 'include',
						method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
					}).then(result => result.text()).then(async function (text) {
						try {
							let jsn = JSON.parse(text);
							if (jsn.success) {
								document.location.reload(true);
							}

						} catch (e) {
							MSG("Ошибка парсинга ответа сервера: <br><br><i>" + e + "</i>");
						}
					});






				}



			</script>

			<tr>
				<td><input type="hidden" id="idWH_nomenclature" value="<?= $nomenclature['idWH_nomenclature']; ?>"><input type="text" id="idgoods" style="display: inline; width: auto;" size="2"></td>
				<td>
					<input type="text" id="goodsName" oninput="searchGoodsByName(this, qs('#newGoodname'));"><div id="newGoodname" style="position: absolute; z-index: 10;"></div></td>
				<th><input type="text" id="goodsBarCode" onkeydown="if (event.keyCode == 13) {
							searchGoodsByBC(this);
						}" style="display: inline; width: auto;"> <button style="display: inline;" onclick="qs('#goodsBarCode').value = RDS(13, true);">+</button></th>
				<th>
					<select id="itemUnits" onchange="qs('#WH_nomenclatureUnits').value = this.value;">
						<option></option>
						<?
						foreach ($units as $unit) {
							?>
							<option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option>
							<?
						}
						?>
					</select>
				</th>

				<td class="C">1<select style="display: inline-block; width: auto;" disabled id="WH_nomenclatureUnits">
						<option></option>
						<?
						foreach ($units as $unit) {
							?>
							<option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option>
							<?
						}
						?>
					</select> = <input type="text" id="wh_goodsnomenclatureqty" oninput="digon();" style="display: inline-block; width: auto;" size="2"><?= isset($nomenclature['WH_nomenclatureUnits']) ? (array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName']) : '??'; ?></td>
				<td class="C" style="white-space: nowrap;">1<select style="display: inline-block; width: auto;" id="WH_goodsWHUnits" onchange="qs('#WH_goodsWHUnits2').value = this.value;">
						<option></option>
						<?
						foreach ($units as $unit) {
							?>
							<option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option>
							<?
						}
						?>
					</select>=<input type="text" id="wh_goodswhqty" oninput="digon();" style="display: inline-block; width: auto;" size="2"><?= isset($nomenclature['WH_nomenclatureUnits']) ? (array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName']) : '??'; ?></td>
				<td><input type="text" id="goodsQty" oninput="digon();" style="display: inline; width: auto;" size="3"><select style="display: inline-block; width: auto;" id="WH_goodsWHUnits2" disabled>
						<option></option>
						<?
						foreach ($units as $unit) {
							?>
							<option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option>
							<?
						}
						?>
					</select></td>
				<td><button style="color: green;" onclick="saveGoodsToNomenclature();">+</button></td>
			</tr>
		</table>



		<table>
			<tr><td colspan="2"></td></tr>
			<tr>
				<td colspan="2">

					<div style="display: inline-block;">
						<div style="padding: 10px;">
							<div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 10px;">
								<div><input type="date" value="<?= $_GET['statFrom'] ?? date("Y-m-d", strtotime('1 months ago')); ?>" onchange="GETreloc('statFrom', this.value);"></div>
								<div><input type="date" value="<?= $_GET['statTo'] ?? date("Y-m-d"); ?>" onchange="GETreloc('statTo', this.value);"></div>
							</div>
						</div>
					</div>

					<div class="console">
						<?
						$statFrom = $_GET['statFrom'] ?? date("Y-m-d", strtotime('1 months ago'));
						$statTo = $_GET['statTo'] ?? date("Y-m-d");


						$log = [];
						$inSQL = "SELECT "
								. "*, UNIX_TIMESTAMP(`inDate`) AS `TS` "
								. "FROM `in` "
								. "LEFT JOIN `suppliers` ON (`idsuppliers` = `inSupplier`) "
								. "LEFT JOIN `units` ON (`idunits` = `inUnits`) "
								. "WHERE `inGoodsId` = '" . $item['idgoods'] . "'"
								. "AND `inDate`>='" . $statFrom . " 00:00:00'"
								. "AND `inDate`<='" . $statTo . " 23:59:59'"
								. "";
//print $inSQL;
						$inData = query2array(mysqlQuery($inSQL));
//						printr($inData);

						foreach ($inData as $in) {

							$log[] = [
								'in' => $in['inQty'],
								'id' => $in['idsuppliers'],
								'units' => $in['unitsName'],
								'name' => $in['suppliersName'],
								'date' => $in['TS']
							];
						}

						$stData = query2array(mysqlQuery("SELECT "
										. "*, UNIX_TIMESTAMP(`stocktakingDate`) AS `TS` "
										. "FROM `stocktaking` "
										. "WHERE `stocktakingItem` = '" . $item['idgoods'] . "' "
										. "AND `stocktakingDate`>='" . $statFrom . " 00:00:00'"
										. "AND `stocktakingDate`<='" . $statTo . " 23:59:59'"
										. ""));
//						printr($stData);
						foreach ($stData as $st) {
							$log[] = [
								'st' => $st['stocktakingQty'],
								'date' => $st['TS']
							];
						}


						$outData = query2array(mysqlQuery("SELECT "
										. "*, UNIX_TIMESTAMP(`outDate`) AS `TS`"
										. " FROM `out`"
										. " LEFT JOIN `users` ON (`idusers` = `outUser`)"
										. " WHERE `outItem` = '" . $item['idgoods'] . "'"
										. " AND isnull(`outDeleted`)"
										. "AND `outDate`>='" . $statFrom . " 00:00:00'"
										. "AND `outDate`<='" . $statTo . " 23:59:59'"
										. ""));

//						printr($outData);
						$n = 0;
						foreach ($outData as $out) {

							$log[] = [
								'out' => $out['outQty'],
								'name' => $out['usersLastName'] . ' ' . $out['usersFirstName'],
								'id' => $out['idusers'],
								'date' => $out['TS']
							];




							$n++;
						}
						usort($log, function($a, $b) {
							return $a['date'] <=> $b['date'];
						});

//printr($log);



						$month = '';
						foreach ($log as $entry) {
//							printr($entry);
							$date = $entry['date'];

							if ($month != date('n', $date)) {
								$month = date('n', $date);
								?>

								<div style="font-size: 1.5em; line-height: 1.5em;"><?= $_MONTHES['full']['nom'][$month]; ?></div>

								<?
							}
							?>



							<? if (isset($entry['st'])) { ?>
								<div class="consStocktaking"><span><?= myDate($date, true); ?></span> Инвентаризация <?= $entry['st']; ?> ед.</div>
							<? } elseif (isset($entry['out'])) { ?>
								<div class="consItemOut"><span><?= myDate($date, true); ?></span> Списание <?= $entry['out']; ?> ед. (<a href="/pages/personal/?employee=<?= $entry['id']; ?>" target="_blank"><?= $entry['name']; ?></a>)</div>
							<? } elseif (isset($entry['in'])) { ?>
								<div class="consItemIn"><span><?= myDate($date, true); ?></span> Приход <?= $entry['in']; ?>  <?= $entry['units'] ?? 'неизвестно чего'; ?> <? if ($entry['name']) { ?>(<a href="/pages/suppliers/?supplier=<?= $entry['id']; ?>" target="_blank"><?= $entry['name']; ?></a>)<? } ?></div>
									<? } ?>
								<? } ?>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>


<script>
	JsBarcode(".barcode").init();
</script>

<? ?>




<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
