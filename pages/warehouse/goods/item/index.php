<?php
$pageTitle = 'Ресурсы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (empty($_GET['item'])) {
	header("Location: /pages/warehouse/goods");
	die();
}
?>

<div class="box neutral" style="display: block;">
	<div class="box-body">

		<?
		$units = query2array(mysqlQuery("SELECT * FROM `units`"));
		usort($units, function ($a, $b) {
			return mb_strtolower($a['unitsName']) <=> mb_strtolower($b['unitsName']);
		});
		$nomenclatureSQL = "SELECT `nomenclature`.*,"
				. " `parentNomenclature`.`idWH_nomenclature` AS `idparent`,"
				. " `parentNomenclature`.`WH_nomenclatureName` AS `parentName`"
				. " FROM `WH_nomenclature` AS `nomenclature`"
				. " LEFT JOIN  `WH_nomenclature` AS `parentNomenclature` ON (`parentNomenclature`.`idWH_nomenclature`=`nomenclature`.`WH_nomenclatureParent`)"
				. " WHERE `nomenclature`.`idWH_nomenclature` = '" . FSI($_GET['item']) . "'";

		$nomenclature = mfa(mysqlQuery($nomenclatureSQL));
//		printr();
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
			<? if ($nomenclature['WH_nomenclatureEntryType'] == 3) { ?>
				<tr>
					<td class="btmdash">Штрихкод:</td>
					<td class="btmdash">SET<?= $nomenclature['idWH_nomenclature']; ?><a target="_blank" href="/sync/plugins/barcodePrint.php?BC=SET<?= $nomenclature['idWH_nomenclature']; ?>&amp;FN=<?= urlencode($nomenclature['WH_nomenclatureName']); ?>&amp;LN&a&amp;qty=48"><i class="fas fa-print"></i></a></td>
				</tr>
			<? } ?>

			<tr>
				<td>Единица измерения:</td>
				<td><? if ($nomenclature['WH_nomenclatureEntryType'] == 3) { ?>
						шт.
					<? } else { ?>
						<? if (R(15)) { ?><a href="#" data-function="editField" data-field="idunits" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureUnits']; ?>"><? } ?><?= $nomenclature['WH_nomenclatureUnits'] ? array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsFullName'] : 'Не указана'; ?><? if (R(15)) { ?></a><? } ?>
					<? } ?>
				</td>
			</tr>

			<tr>
				<td>Лаборатория</td>
				<td>
					<? if (R(15)) { ?><a href="#" data-function="editField" data-field="istps" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureIsTPS']; ?>"><? } ?><?= $nomenclature['WH_nomenclatureIsTPS'] ? 'Да' : 'Нет'; ?><? if (R(15)) { ?></a><? } ?>

				</td>
			</tr>


			<? if (!$nomenclature['WH_nomenclatureEntryType'] == 3) { ?>
				<tr>
					<td class="btmdash">Лимиты:</td>
					<td class="btmdash"><? //printr($nomenclature);                                                                                                                                                                                                                        ?>
						<div style="display: grid; grid-template-columns: auto 40px auto ;">
							<span>min: <? if (R(15)) { ?><a href="#" data-function="editField" data-field="goodsMinLimit" data-units="<?= ($nomenclature['WH_nomenclatureUnits'] ? array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] : 'не указаны'); ?>"  data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureMin'] ?? ''; ?>"><? } ?><?= ($nomenclature['WH_nomenclatureMin'] === null ? 'Не задан' : (round($nomenclature['WH_nomenclatureMin'], 3) . '<span class="small">' . ($nomenclature['WH_nomenclatureUnits'] ? array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] : 'не указаны') . '</span>')); ?><? if (R(15)) { ?></a><? } ?></span>
							<span></span>
							<span>max: <? if (R(15)) { ?><a href="#" data-function="editField"  data-units="<?= ($nomenclature['WH_nomenclatureUnits'] ? array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] : 'не указаны'); ?>"  data-field="goodsMaxLimit" data-item="<?= $nomenclature['idWH_nomenclature']; ?>" data-value="<?= $nomenclature['WH_nomenclatureMax'] ?? ''; ?>"><? } ?><?= ($nomenclature['WH_nomenclatureMax'] ? (round($nomenclature['WH_nomenclatureMax'], 3) . '<span class="small">' . ($nomenclature['WH_nomenclatureUnits'] ? array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] : 'не указаны') . '</span>') : 'Не задан'); ?><? if (R(15)) { ?></a><? } ?></span>
						</div>
					</td>
				</tr>
			<? } ?>
		</table>

		<?
		if ($nomenclature['WH_nomenclatureEntryType'] == 3) {
			$itemsSQL = "SELECT * FROM `WH_goodsSetsContent` LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsSetsContentGood`) WHERE `WH_goodsSetsContentSet`=" . $nomenclature['idWH_nomenclature'] . " ";
//			printr($itemsSQL);
		} else {
			$itemsSQL = "SELECT * FROM `WH_goods` WHERE `WH_goodsNomenclature`='" . $nomenclature['idWH_nomenclature'] . "'";
		}

//		printr($itemsSQL);
		$items = query2array(mysqlQuery($itemsSQL));
		?>
		<pre><?
//			printr($items);
			?>
		</pre>
		<h3 style="text-align: center;">Товары<? if ($nomenclature['WH_nomenclatureEntryType'] == 3) { ?> входящие в состав набора<? } ?></h3>
		<br>
		<input type="checkbox" id="showempty"  <?= ($_GET['showEmpty'] ?? false) ? 'checked' : ''; ?> onchange="GR({showEmpty: this.checked ? 'yes' : null});"><label for="showempty">Показать пустые</label>
		<table>
			<tr>
				<th>idDB</th>
				<th>Наименование</th>
				<th>Штрихкод</th>
				<!--<th>Ед.изм.</th>-->
				<!--<th>Единицы номенклатуры</th>-->
				<!--<th>Отпуск со склада</th>-->
				<th><? if ($nomenclature['WH_nomenclatureEntryType'] == 3) { ?>количество<? } else { ?>Остаток<? } ?></th>
				<th>Цена</th>
				<th></th>
			</tr>
			<?
			$ttl = 0;
			foreach ($items as $item) {
				if ($nomenclature['WH_nomenclatureEntryType'] == 3) {
					$balance = round($item['WH_goodsSetsContentQty'], 3);
				} else {
					$stocktakingSQL = "SELECT ifnull(`WH_stocktakingQty`,0) as `stQty`, ifnull(`WH_stocktakingDate`,'2020-02-02 00:00:00') as `stDate`  FROM `WH_stocktaking` WHERE `idWH_stocktaking` = (SELECT MAX(`idWH_stocktaking`) FROM `WH_stocktaking` WHERE `WH_stocktakingDate` = (SELECT MAX(`WH_stocktakingDate`) FROM `WH_stocktaking` WHERE `WH_stocktakingGoods` = '" . $item['idWH_goods'] . "'))";
					$stocktaking = mfa(mysqlQuery($stocktakingSQL));
					$inSQL = "SELECT ifnull(SUM(`WH_goodsInQty`),0) AS `inSumm` FROM `WH_goodsIn` WHERE `WH_goodsInGoodsId` =  '" . $item['idWH_goods'] . "' AND `WH_goodsInDate`>='" . ($stocktaking['stDate'] ?? '2020-02-02 00:00:00') . "';";
					$in = mfa(mysqlQuery($inSQL));
					$outSQL = "SELECT ifnull(SUM(`WH_goodsOutQty`),0) AS `outSumm` FROM `WH_goodsOut` WHERE `WH_goodsOutItem` =  '" . $item['idWH_goods'] . "' AND `WH_goodsOutDate`>='" . ($stocktaking['stDate'] ?? '2020-02-02 00:00:00') . "' AND isnull(`WH_goodsOutDeleted`);";
					$out = mfa(mysqlQuery($outSQL));
					$balance = (($stocktaking['stQty'] ?? 0) + ($in['inSumm'] ?? 0) - ($out['outSumm'] ?? 0));
				}

//				printr($item);
				?>


				<? if ($nomenclature['WH_nomenclatureEntryType'] == 3) { ?>

					<tr>
						<td><?= $item['idWH_goods']; ?></td>
						<td><?= $item['WH_goodsName']; ?></td>
						<td style="text-align: center;"><?= $item['WH_goodBarCode']; ?> <a target="_blank" href="/sync/plugins/barcodePrint.php?item=<?= $item['idWH_goods']; ?>"><i class="fas fa-print"></i></a></td>
						<td class="C"><a href="#"  data-units="<?= array_search_2d($item['WH_goodsSetsContentUnits'], $units, 'idunits')['unitsName'] ?? '???'; ?>"  data-function="editField" data-field="contentQty" data-item="<?= $item['idWH_goodsSetsContent']; ?>" data-value="<?= $balance; ?>"><?= $balance; ?></a><?= array_search_2d($item['WH_goodsSetsContentUnits'], $units, 'idunits')['unitsName'] ?? 'Не указана'; ?></td>
						<td class="C"><?= $item['WH_goodsPrice'] ? nf($item['WH_goodsPrice'] * $balance, 2) : '??'; ?>р.</td>
						<td><button style="color: red;" onclick="deleteFromGoodsToSet(<?= $item['idWH_goodsSetsContent']; ?>);">&Cross;</button></td>
					</tr>


					<?
					$ttl += $item['WH_goodsPrice'] * $balance;
				} else {
					$ttl += $item['WH_goodsPrice'] * $balance;
					if (!isset($_GET['showEmpty']) && $balance == 0) {
//						printr($item['WH_goodsNomenclatureQty']);
						continue;
					}
					$balancettl = ($balancettl ?? 0) + $balance;
					?>

					<tr>
						<td><?= $item['idWH_goods']; ?></td>
						<td><?= $item['WH_goodsName']; ?></td>
						<td style="text-align: center;"><?= $item['WH_goodBarCode']; ?> <a target="_blank" href="/sync/plugins/barcodePrint.php?item=<?= $item['idWH_goods']; ?>"><i class="fas fa-print"></i></a></td>
						<td class="C"><a href="#"  data-units="<?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] ?? '???'; ?>"  data-function="editField" data-field="ballance" data-item="<?= $item['idWH_goods']; ?>" data-value="<?= $balance; ?>"><?= $balance; ?></a><?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] ?? 'Не указана'; ?></td>
						<td class="C"><a href="#"  data-units="<?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] ?? '???'; ?>"  data-function="editField" data-field="price" data-item="<?= $item['idWH_goods']; ?>" data-value="<?= $item['WH_goodsPrice']; ?>"><?= $item['WH_goodsPrice'] ? nf($item['WH_goodsPrice'], 2) : '??'; ?></a> &#x20bd/<?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] ?? 'Не указана'; ?></td>
						<td><button style="color: red;">&Cross;</button></td>
					</tr>

				<? }
				?>


				<?
			}
			?>

			<tr>
				<td colspan="3" class="B R">Итого:</td>
				<td class="B C"><?= $balancettl ?? 0; ?><?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] ?? 'Не указана'; ?></td>
				<td class="B R"><?= nf($ttl); ?>р.</td>
			</tr>

			<? if ($nomenclature['WH_nomenclatureEntryType'] == 3) {
				?>
				<tr>
					<td colspan="4" class="R">Итого:</td>
					<td class="R"><?= nf($ttl, 2) ?>р.</td>
				</tr>	
			<? } ?>

			<? if ($nomenclature['WH_nomenclatureEntryType'] == 3) { ?>


				<tr>
					<td>
						<input type="hidden" id="idWH_nomenclature" value="<?= $nomenclature['idWH_nomenclature']; ?>">
						<input type="hidden" id="itemUnits" value="<?= $nomenclature['WH_nomenclatureUnits']; ?>">

						<input type="text" autocomplete="off" id="idgoods" style="display: inline; width: auto;" size="2"></td>
					<td>
						<input type="text" autocomplete="off" id="goodsName" oninput="searchGoodsByName(this, qs('#newGoodname')); qs('#idgoods').value=''; qs('#goodsBarCode').value='';  qs('#setGoodsUnit').innerHTML=''; "><div id="newGoodname" style="position: absolute; z-index: 10;"></div></td>
					<th><input type="text" autocomplete="off" id="goodsBarCode" oninput="this.value = filterKeys(this.value);" onkeydown=" if (event.keyCode == 13) {
								searchGoodsByBC(this);
							}" style="display: inline; width: auto;"> <button style="display: inline;" onclick="qs('#goodsBarCode').value = RDS(13, true);">+</button></th>
					<td><input type="text" id="goodsQty" oninput="digon();" style="display: inline; width: auto;" size="3"> <span id="setGoodsUnit"></span>
					</td>
					<td></td>
					<td><button style="color: green;" onclick="saveGoodsToSet();">+</button></td>
				</tr>
				<script>
					function deleteFromGoodsToSet(id) {
						fetch('/pages/warehouse/goods/goods_IO.php', {
							body: JSON.stringify({action: 'deleteFromGoodsToSet', item: id}),
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
					function saveGoodsToSet() {
						let item = {};
						if (qs('#idgoods').value.trim() === '') {
							MSG("Нужно выбрать товар");
						} else if (qs('#goodsQty').value.trim() === '') {
							MSG("Нужно указать количество");
						} else {
							item = {
								id: qs('#idgoods').value.trim() || null,
								idWH_nomenclature: ((qs('#idWH_nomenclature') || {}).value || '').trim() || null,
								qty: qs('#goodsQty').value.trim() || null
							};
							console.log(item);
							fetch('/pages/warehouse/goods/goods_IO.php', {
								body: JSON.stringify({action: 'saveGoodsToSet', item: item}),
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



					}
				</script>

				<?
			} else {
				if (R(14)) {
					?>
					<tr>
						<td>
							<input type="hidden" id="idWH_nomenclature" value="<?= $nomenclature['idWH_nomenclature']; ?>">
							<input type="hidden" id="itemUnits" value="<?= $nomenclature['WH_nomenclatureUnits']; ?>">

							<input type="text" autocomplete="off" id="idgoods" style="display: inline; width: auto;" size="2"></td>
						<td>
							<input type="text" autocomplete="off" id="goodsName" oninput="searchGoodsByName(this, qs('#newGoodname'));"><div id="newGoodname" style="position: absolute; z-index: 10;"></div></td>
						<th><input type="text" autocomplete="off" id="goodsBarCode" oninput="this.value = filterKeys(this.value);" onkeydown=" if (event.keyCode == 13) {

									searchGoodsByBC(this);
								}" style="display: inline; width: auto;"> <button style="display: inline;" onclick="qs('#goodsBarCode').value = RDS(13, true);">+</button></th>

																																																				<!--				<th>
																																																				<select id="itemUnits" onchange="qs('#WH_nomenclatureUnits').value = this.value;">
																																																				<option></option>
						<?
						foreach ($units as $unit) {
							?><option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option>
							<?
						}
						?></select></th>-->

																																																				<!--				<td class="C">1<select style="display: inline-block; width: auto;" disabled id="WH_nomenclatureUnits">
																																																				<option></option>
						<?
						foreach ($units as $unit) {
							?>
																																																					<option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option>
							<?
						}
						?>
																																																				</select> = <input type="text" id="wh_goodsnomenclatureqty" oninput="digon();" style="display: inline-block; width: auto;" size="2"><?= isset($nomenclature['WH_nomenclatureUnits']) ? (array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName']) : '??'; ?></td><td class="C" style="white-space: nowrap;">1<select style="display: inline-block; width: auto;" id="WH_goodsWHUnits" onchange="qs('#WH_goodsWHUnits2').value = this.value;">
																																																				<option></option>
						<?
						foreach ($units as $unit) {
							?><option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option><?
						}
						?></select>=<input type="text" id="wh_goodswhqty" oninput="digon();" style="display: inline-block; width: auto;" size="2"><?= isset($nomenclature['WH_nomenclatureUnits']) ? (array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName']) : '??'; ?></td>-->


																																																				<td><input type="text" id="goodsQty" oninput="digon();" style="display: inline; width: auto;" size="3"> <?= array_search_2d($nomenclature['WH_nomenclatureUnits'], $units, 'idunits')['unitsName'] ?? 'Не указана'; ?><!--	<select style="display: inline-block; width: auto;" id="WH_goodsWHUnits2" disabled>
																																																				<option></option>
							<?
							foreach ($units as $unit) {
								?>
																																																					<option value="<?= $unit['idunits']; ?>"><?= $unit['unitsName']; ?></option>
								<?
							}
							?>
																																																				</select>-->
						</td>
						<td></td>
						<td><button style="color: green;" onclick="saveGoodsToNomenclature();">+</button></td>
					</tr>
				<? } ?>
			<? } ?>
		</table>


		<? if ($nomenclature['WH_nomenclatureEntryType'] != 3) { ?>
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

//						printr($item);
							$log = [];

							$inSQL = "SELECT *, "
									. "UNIX_TIMESTAMP(`WH_goodsInDate`) AS `TS` "
									. "FROM `WH_goodsIn` "
									. "LEFT JOIN WH_goods ON (idWH_goods = WH_goodsInGoodsId) "
									. "LEFT JOIN `suppliers` ON (`idsuppliers` = `WH_goodsInSupplier`) "
									. " WHERE `WH_goodsNomenclature` = '" . $nomenclature['idWH_nomenclature'] . "'"
									. " AND `WH_goodsInDate` >= '" . $statFrom . " 00:00:00'"
									. " AND `WH_goodsInDate` <= '" . $statTo . "  23:59:59';";

//						print $inSQL;
							$inData = query2array(mysqlQuery($inSQL));
//						printr($inData);

							foreach ($inData as $in) {
								$log[] = [
									'in' => round(($in['WH_goodsInQty']), 3),
									'id' => $in['WH_goodsInSupplier'],
									'units' => array_search_2d($in['WH_goodsInUnits'], $units, 'idunits')['unitsName'] ?? null,
									'name' => $in['suppliersName'],
									'gname' => $in['WH_goodsName'],
									'date' => $in['TS']
								];
							}


							$outData = query2array(mysqlQuery("SELECT "
											. " *, UNIX_TIMESTAMP(`WH_goodsOutDate`) AS `TS`"
											. " FROM `WH_goodsOut`"
											. " LEFT JOIN WH_goods ON (idWH_goods = WH_goodsOutItem) "
											. " LEFT JOIN `users` ON (`idusers` = `WH_goodsOutUser`)"
											. " WHERE `WH_goodsNomenclature` = '" . $nomenclature['idWH_nomenclature'] . "'"
											. " AND isnull(`WH_goodsOutDeleted`)"
											. " AND `WH_goodsOutDate`>='" . $statFrom . " 00:00:00'"
											. " AND `WH_goodsOutDate`<='" . $statTo . " 23:59:59'"
											. ""));

//						printr($outData);
							$n = 0;
							foreach ($outData as $out) {
								$log[] = [
									'out' => $out['WH_goodsOutQty'],
									'name' => $out['usersLastName'] . ' ' . $out['usersFirstName'],
									'gname' => $out['WH_goodsName'] . ' (' . $out['WH_goodBarCode'] . ')',
									'id' => $out['idusers'],
									'units' => $out['WH_goodsOutUnits'] ? (array_search_2d($out['WH_goodsOutUnits'], $units, 'idunits')['unitsName']) : null,
									'date' => $out['TS']
								];
								$n++;
							}


							$stData = query2array(mysqlQuery("SELECT "
											. "*, UNIX_TIMESTAMP(`WH_stocktakingDate`) AS `TS` "
											. "FROM `WH_stocktaking` "
											. "LEFT JOIN WH_goods ON (idWH_goods = WH_stocktakingGoods) "
											. "WHERE `WH_goodsNomenclature` = '" . $nomenclature['idWH_nomenclature'] . "' "
											. "AND `WH_stocktakingDate`>='" . $statFrom . " 00:00:00'"
											. "AND `WH_stocktakingDate`<='" . $statTo . " 23:59:59'"
											. ""));
//						printr($stData);
							foreach ($stData as $st) {
								$log[] = [
									'st' => round($st['WH_stocktakingQty'], 3),
									'name' => $st['WH_goodsName'] . ' (' . $st['WH_goodBarCode'] . ')',
									'units' => $st['WH_stocktakingUnits'] ? array_search_2d($st['WH_stocktakingUnits'], $units, 'idunits')['unitsName'] : null,
									'date' => $st['TS']
								];
							}

							usort($log, function ($a, $b) {
								return $a['date'] <=> $b['date'];
							});

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
									<!--consStocktaking--><div class=""><span><?= myDate($date, true); ?></span> Инвентаризация <b>"<?= $entry['name']; ?>"</b> <?= $entry['st']; ?>  <?= $entry['units'] ?? 'неизвестно чего.'; ?></div>
								<? } elseif (isset($entry['out'])) { ?>
									<!--consItemOut--><div class=""><span><?= myDate($date, true); ?></span> Списание <b> <?= $entry['gname']; ?></b> <?= round($entry['out'], 3); ?> <?= $entry['units'] ?? 'неизвестно чего.'; ?> (<a href="/pages/personal/info.php?employee=<?= $entry['id']; ?>" target="_blank"><?= $entry['name']; ?></a>)</div>
								<? } elseif (isset($entry['in'])) { ?>
									<!--consItemIn--><div class=""><span><?= myDate($date, true); ?></span> Приход <b>"<?= $entry['gname']; ?>"</b> <?= $entry['in']; ?>  <?= $entry['units'] ?? 'неизвестно чего.'; ?> <? if ($entry['name']) { ?>(<a href="/pages/suppliers/?supplier=<?= $entry['id']; ?>" target="_blank"><?= $entry['name']; ?></a>)<? } ?></div>
								<? } ?>
							<? } ?>
						</div>
					</td>
				</tr>
			</table>
		<? } ?>
	</div>
</div>


<script>
	JsBarcode(".barcode").init();
</script>

<? ?>




<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
