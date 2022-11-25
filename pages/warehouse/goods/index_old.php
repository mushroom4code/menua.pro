<?php
$pageTitle = 'Ресурсы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (R(13)) {
	?>
	<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>


	<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
	<ul class="horisontalMenu">
		<li><a href="?">Все</a></li>
		<? if (R(14)) { ?><li><a href="#" onclick="addItemWindow();">Добавить</a></li><? } ?>
		<? if (1) { ?><li><a href="?urgent">К закупке</a></li><? } ?>
		<li><a data-function="searchWindow">Поиск</a></li>
	</ul>
	<?
	if (isset($_GET['urgent'])) {
		$lastST = "IFNULL((SELECT MAX(`stocktakingDate`) FROM `stocktaking` WHERE `stocktakingItem` = `items`.`idgoods`), '0000-01-01 00:00:00')";

		$QTY = "(IFNULL((SELECT `stocktakingQty` FROM `stocktaking` WHERE `stocktakingItem` = `items`.`idgoods` AND `stocktakingDate` = $lastST LIMIT 1), 0) + IFNULL((SELECT SUM(`inQty`) FROM `in` WHERE `inGoodsId` = `items`.`idgoods` AND `inTime` >= $lastST LIMIT 1), 0) - IFNULL((SELECT SUM(`outQty`) FROM `out` WHERE `outItem` = `items`.`idgoods` AND `outDate` >= $lastST AND ISNULL(`outDeleted`) LIMIT 1), 0))";

		$query = "SELECT "
				. "`suppliersName`,"
				. "`suppliersEmailIsVoid`,"
				. "`parents`.`idgoods` AS `idparents`,"
				. "`parents`.`goodsName` AS `parentsName`,"
				. "`items`.`goodsMinAmnt` AS `goodsMinAmnt`,"
				. "`items`.`goodsMaxAmnt` AS `goodsMaxAmnt`,"
				. "`suppliers`.`idsuppliers`,"
				. "`suppliers`.`suppliersEmail`,"
				. "`U2`.`unitsName` as `supplierUnitsName`,"
				. "`U1`.`unitsName` as `warhouseUnitsName`,"
				. "`items`.`idgoods` AS `iditems`,"
				. "`items`.`goodsUSUratio` AS `ratio`,"
				. "(SELECT sum(orderedItemsQty) FROM warehouse.orderedItems WHERE `orderedItemsItem`=`iditems` AND `orderedItemsSupplier`=`suppliers`.`idsuppliers` GROUP BY `orderedItemsItem`) AS `expectationQty`,"
				. "`items`.`goodsName` AS `itemsName`, $QTY AS `itemsQty`"
				. " FROM `goods` AS `items` "
				. " LEFT JOIN `goods` AS `parents` ON (`parents`.`idgoods` = `items`.`goodsParent`) "
				. " LEFT JOIN `suppliers` ON (`idsuppliers` IN (SELECT `inSupplier` FROM `in` WHERE `inGoodsId`=`items`.`idgoods`))"
				. " LEFT JOIN `units` AS `U1` ON (`U1`.`idunits` = `items`.`goodsUnit`)"
				. " LEFT JOIN `units` AS `U2` ON (`U2`.`idunits` = `items`.`goodsSupplierUnit`)"
				. ""
				. " WHERE "
				. "`items`.`goodsEntryType`= 2 "
				. " AND (($QTY <= 0 OR $QTY <= `items`.`goodsMinAmnt`) OR isnull(`items`.`goodsMaxAmnt`) OR isnull(`items`.`goodsMinAmnt`))";

		$items = query2array(mysqlQuery($query));

		if (!empty($_JSON['loadGoods'])) {
			$OUT = [];

			if (isset($_JSON['parent'])) {
				$_JSON['parent'] = $_JSON['parent'] === 'null' ? null : $_JSON['parent'];
				if ($_JSON['parent']) {
					$OUT['parentLVL'] = mysqli_result(mysqlQuery("SELECT `goodsParent` FROM `goods` WHERE `idgoods` = '" . $_JSON['parent'] . "'"), 0) ?? 'null';
				}
			}
			$qtext = "SELECT *, IFNULL((SELECT
            MAX(`stocktakingDate`)
        FROM
            `stocktaking`
        WHERE
            `stocktakingItem` = `idgoods`),'0000-01-01 00:00:00') AS `lastSTdate`,
    (IFNULL((SELECT
                    `stocktakingQty`
                FROM
                    `stocktaking`
                WHERE
                    `stocktakingItem` = `idgoods`
                        AND `stocktakingDate` = `lastSTdate`
                LIMIT 1),
            0) + IFNULL((SELECT
                    SUM(`inQty`)
                FROM
                    `in`
                WHERE
                    `inGoodsId` = `idgoods`
                        AND `inTime` >= `lastSTdate`
                LIMIT 1),
            0) - IFNULL((SELECT
                    SUM(`outQty`)
                FROM
                    `out`
                WHERE
                    `outItem` = `idgoods`
                        AND `outDate` >= `lastSTdate`
						 AND isnull(`outDeleted`)
                LIMIT 1),
            0)) AS `qty` FROM `goods`"
					. " LEFT JOIN `units` ON (`idunits` = `goodsUnit`) "
					. " LEFT JOIN `barcodes` ON (`barcodesItem` = `idgoods`) "
					. " WHERE " . ($_JSON['parent'] ? ("`goodsParent` = '" . $_JSON['parent'] . "'") : 'isnull(`goodsParent`)') . " ";


			$goods = [];

			$result = mysqlQuery($qtext);

			while ($row = mfa($result)) {
				if (empty($goods[$row['idgoods']])) {
					$goods[$row['idgoods']] = $row;
				}
				$goods[$row['idgoods']]['goodsBarcode'][] = FSS($row['barcodesCode']);
			}
			//printr($goods);
			$goods = obj2array($goods);

//	$OUT['$qtext'] = $qtext;
			$OUT['goods'] = $goods;
			//$tree = adjArr2obj($goodsADJ, $id = 'idgoods', $parent = 'goodsParent', $content = 'content', $debug = false);
			//$OUT = $tree;
//

			print json_encode(array_filter_recursive($OUT), JSON_UNESCAPED_UNICODE);
		}
		usort($items, function($a, $b) {

			if ((mb_strtolower($a['suppliersName']) <=> mb_strtolower($b['suppliersName'])) !== 0) {

				if ($a['suppliersName'] == null && $b['suppliersName'] != null) {
					return 1;
				} elseif ($b['suppliersName'] == null && $a['suppliersName'] != null) {
					return -1;
				}
				return mb_strtolower($a['suppliersName']) <=> mb_strtolower($b['suppliersName']);
			} else {

				if ($a['itemsQty'] <= 0 && $b['itemsQty'] > 0) {
					return -1;
				} elseif ($a['itemsQty'] > 0 && $b['itemsQty'] <= 0) {
					return 1;
				} elseif ($a['itemsQty'] <= 0 && $b['itemsQty'] <= 0) {
					return mb_strtolower($a['itemsName']) <=> mb_strtolower($b['itemsName']);
				} elseif ($a['itemsQty'] > 0 && $b['itemsQty'] > 0) {

					if ($a['itemsQty'] <= $a['goodsMinAmnt'] && $b['itemsQty'] > $b['goodsMinAmnt']) {
						return -1;
					} elseif ($a['itemsQty'] > $a['goodsMinAmnt'] && $b['itemsQty'] <= $b['goodsMinAmnt']) {
						return 1;
					} else {
						return mb_strtolower($a['itemsName']) <=> mb_strtolower($b['itemsName']);
					}
				} else {
					//return strtolower($a['itemsName']) <=> strtolower($b['itemsName']);
				}
			}
		});
		?>
		<div class="box neutral">
			<div class="box-body">
				<table style="background-color: gray;">
					<thead>
						<tr>
							<th>#</th>
							<th>БД</th>
							<!--<th><i class="fas fa-barcode"></i></th>-->
							<!--<th>Поставщик</th>-->
							<th>Категория</th>
							<th>Наименование</th>
							<th>min</th>
							<th>к-во</th>
							<th>max</th>
							<th>В заказе</th>
							<th>Ожидается</th>

						</tr>
					</thead>
					<tbody id="goodsTable">
						<?
						$n = 0;
						$oldSupplyer = null;
						$entities = query2array(mysqlQuery("SELECT `entitiesName` AS `text` ,`identities` AS `value`  FROM `entities`"));


						foreach ($items as $item) {
							$n++;
							if ($item['itemsQty'] <= 0) {
								$color = ' style="color: red;"';
							} elseif (isset($item['goodsMinAmnt']) && $item['itemsQty'] <= $item['goodsMinAmnt']) {
								$color = ' style="color: yellow;"';
							} else {
								$color = null;
							}

							if ($oldSupplyer !== $item['suppliersName']) {

								if ($oldSupplyer) {
									?>
									<tr>
										<td colspan="9" style=" padding-top: 20px; border-top: 1px solid black;"></td>
									</tr>
								<? } else { ?>
									<tr>
										<td colspan="8"></td>
									</tr>
								<? } ?>
								<tr><td style="border-top: 1px solid black; border-left: 1px solid black;">
										<? if ($item['suppliersEmail'] || $item['suppliersEmailIsVoid']) { ?>
											<input onclick='for (let elem of qsa("input[data-supplier=\"<?= $item['idsuppliers']; ?>\"]")) {
														elem.checked = (elem.dataset.maxamnt > 0 && this.checked);
														if (elem.checked) {
															qs("input[data-itemtobuy=\"" + elem.dataset.id + "\"]").value = Math.ceil((elem.dataset.maxamnt - elem.dataset.amnt) / elem.dataset.ratio);
														} else {
															qs("input[data-itemtobuy=\"" + elem.dataset.id + "\"]").value = "";
														}

													}' type="checkbox" id="supplier_<?= $item['idsuppliers']; ?>"><label for="supplier_<?= $item['idsuppliers']; ?>"></label>
											   <? } ?>
									</td>
									<td colspan="6" style="font-size: 1.5em; border-top: 1px solid black;">
										<? if ($item['suppliersName']) { ?><a href="/pages/suppliers/?supplier=<?= $item['idsuppliers']; ?>" target="_blank"><? } ?><?= $item['suppliersName'] ?? 'Без поставщика'; ?><? if ($item['suppliersName']) { ?></a><? } ?>
									</td>
									<td style="border-top: 1px solid black; text-align: center;"><? if ($item['suppliersEmail'] || $item['suppliersEmailIsVoid']) { ?> <ul class="horisontalMenu" style="display: inline; font-size: 0.75em; float: right;"><li><a onclick='sendOrder(<?= $item['idsuppliers']; ?>, <?
													if ($item['suppliersEmail']) {
														print json_encode($entities, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
													} else {
														print 'false';
													}
													?>);'><?= $item['suppliersEmail'] ? '<i class="far fa-envelope" style="vertical-align: middle;"> </i> ' : ''; ?>Заказать</a></li></ul><? } ?></td>
									<td style="border-right: 1px solid black; border-top: 1px solid black;"></td>
								</tr>
								<?
								$oldSupplyer = $item['suppliersName'];
							}
							?><tr><td style="border-left: 1px solid black;">
							<? if (($item['suppliersEmail'] || $item['suppliersEmailIsVoid']) && $item['goodsMaxAmnt']) { ?>
										<input type="checkbox"
											   data-supplier="<?= $item['idsuppliers']; ?>"
											   data-maxamnt="<?= $item['goodsMaxAmnt'] ?? 0; ?>"
											   data-amnt="<?= $item['itemsQty'] ?? 0; ?>"
											   data-ratio="<?= ($item['warhouseUnitsName'] !== $item['supplierUnitsName'] && $item['supplierUnitsName'] !== null) ? ($item['ratio'] ?? 1) : 1; ?>"
											   data-id="<?= $item['iditems']; ?>"
											   id="item_<?= $item['iditems']; ?>"
											   onclick='if (this.checked) {
														   qs("input[data-itemtobuy=\"" + this.dataset.id + "\"]").value = Math.ceil((this.dataset.maxamnt - this.dataset.amnt) / this.dataset.ratio);
													   } else {
														   qs("input[data-itemtobuy=\"" + this.dataset.id + "\"]").value = "";
													   }'>
										<label for="item_<?= $item['iditems']; ?>"><?= $n; ?></label>
									<? } ?></td>
								<td><?= $item['iditems']; ?></td>
								<td><? if ($item['parentsName']) { ?><a href="/pages/warehouse/goods/?dir=<?= $item['idparents']; ?>" target="_blank"><? } ?><?= $item['parentsName']; ?><? if ($item['parentsName']) { ?></a><? } ?></td>
								<td><a href="/pages/warehouse/goods/item/?item=<?= $item['iditems']; ?>" target="_blank"><?= $item['itemsName']; ?></a></td>
								<td class="C"><?= $item['goodsMinAmnt'] ?? '<span style="color: red;">!!</span>'; ?></td>
								<td class="C"<?= $color ?? ''; ?><?
								if ($item['warhouseUnitsName'] !== $item['supplierUnitsName'] && $item['supplierUnitsName'] !== null) {
									print ' title="1' . $item['supplierUnitsName'] . ' = ' . $item['ratio'] . $item['warhouseUnitsName'] . '"';
								}
								?>><?= round($item['itemsQty'], 2); ?>&nbsp;<small><?= $item['warhouseUnitsName']; ?></small></td>
								<td class="C"><?= $item['goodsMaxAmnt'] ?? '<span style="color: red;">!!</span>'; ?></td>
								<td style="white-space: nowrap;"><? if (($item['suppliersEmail'] || $item['suppliersEmailIsVoid']) && $item['goodsMaxAmnt']) { ?><input type="text" data-suppliertobuy="<?= $item['idsuppliers']; ?>"  data-itemtobuy="<?= $item['iditems']; ?>" style="width: 40px; text-align: right; display: inline;">&nbsp;<small><?= $item['supplierUnitsName'] ?? $item['warhouseUnitsName'] ?? '--'; ?></small><? } ?></td>
								<td style="border-right: 1px solid black; text-align: right;"><span id="s_<?= $item['idsuppliers']; ?>_i_<?= $item['iditems']; ?>_exqty"><?= $item['expectationQty'] ?? ''; ?></span> <small id="s_<?= $item['idsuppliers']; ?>_i_<?= $item['iditems']; ?>_exu"><?= $item['expectationQty'] ? ($item['supplierUnitsName'] ?? $item['warhouseUnitsName'] ?? '--') : ''; ?></small></td></tr>
							<?
						}
						?>
					</tbody>
				</table>
			</div>
		</div>


		<?
	} else {
		?>
		<div class="box neutral">
			<div class="box-body">
				<table>
					<thead>
						<tr>
							<th>#</th>
							<th>БД</th>
							<th><i class="fas fa-barcode"></i></th>
							<th>Наименование</th>
							<th>к-во</th>
							<th>Ед.изм.</th>
							<th>#</th>
						</tr>
					</thead>
					<tbody id="goodsTable">
					</tbody>
				</table>
			</div>
		</div>
		<script>
			var dir = <?= $_GET['dir'] ?? 'null'; ?>
		</script>

	<? } ?>

<? } else { ?>E403R13<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
