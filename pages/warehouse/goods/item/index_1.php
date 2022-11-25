<?php
$pageTitle = 'Ресурсы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (empty($_GET['item'])) {
	header("Location: /pages/warehouse/goods");
	die();
}
?>
<?
$query = mysqlQuery("SELECT"
		. "`parents`.`idgoods` AS `idparents`,"
		. "`parents`.`goodsName` AS `parentsName`, "
		. "`items`.`idgoods`, "
		. "`items`.`goodsParent`, "
		. "`items`.`goodsName`, "
		. "`items`.`goodsEntryType`, "
		. "`items`.`goodsDeleted`, "
		. "`items`.`goodsUnit`, "
		. "`items`.`goodsAdded`, "
		. "`items`.`goodsMinAmnt`, "
		. "`items`.`goodsMaxAmnt`, "
		. "`U1`.`idunits`, "
		. "`U1`.`unitsCode`, "
		. "`U1`.`unitsName`, "
		. "`U1`.`unitsFullName`, "
		. "`U2`.`idunits` as `idunitsSupplier`, "
		. "`U2`.`unitsName` as `unitsSupplierName`, "
		. "`U2`.`unitsFullName` as `unitsSupplierFullName`, "
		. "`items`.`goodsUSUratio`, "
		. "`idbarcodes`, "
		. "`barcodesItem`,"
//		. "$QTY AS `QTY`, "
		. "`barcodesCode`"
		. " FROM `goods` AS `items`"
		. "LEFT JOIN `goods`  AS `parents`  ON (`parents`.`idgoods` = `items`.`goodsParent`) "
		. "LEFT JOIN `units` AS `U1` on (`U1`.`idunits`  =  `items`.`goodsUnit`)"
		. "LEFT JOIN `units` AS `U2` on (`U2`.`idunits`  =  `items`.`goodsSupplierUnit`)"
		. "LEFT JOIN `barcodes` on (`barcodesItem`  =  `items`.`idgoods`)"
		. "WHERE `items`.`idgoods` = '" . FSI($_GET['item']) . "' ");
while ($row = mfa($query)) {
	if (empty($item)) {
		$item = $row;
	}
	if ($row['idbarcodes']) {
		$item['goodsBarcode'][] = ['id' => $row['idbarcodes'], 'code' => $row['barcodesCode']];
	}
}


$lastSTSQL = "SELECT * FROM `stocktaking` WHERE (SELECT MAX(`stocktakingDate`),`idstocktaking` WHERE `stocktakingItem` = '" . FSI($_GET['item']) . "')";


if ($lastSTSQL) {
	
}

$lastST = mfa(mysqlQuery($lastSTSQL));

printr($lastST);
print $lastSTSQL;

$QTY = "(IFNULL((SELECT `stocktakingQty` FROM `stocktaking` WHERE `stocktakingItem` = `items`.`idgoods` AND `stocktakingDate` = $lastSTSQL LIMIT 1), 0) + IFNULL((SELECT SUM(`inQty`) FROM `in` WHERE `inGoodsId` = `items`.`idgoods` AND `inTime` >= $lastSTSQL LIMIT 1), 0) - IFNULL((SELECT SUM(`outQty`) FROM `out` WHERE `outItem` = `items`.`idgoods` AND `outDate` >= $lastSTSQL AND ISNULL(`outDeleted`) LIMIT 1), 0))";




//printr($item);

$price = mfa(mysqlQuery("SELECT "
				. "*"
				. " FROM `in`"
				. "LEFT JOIN `units` ON (`idunits` = `inUnits`)"
				. " WHERE NOT isnull(`inPrice`)"
				. " AND `idin` = (SELECT MAX(`idin`) FROM `in` WHERE `inGoodsId` = '" . $item['idgoods'] . "')"));
//printr($price);
?>
<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
<ul class="horisontalMenu">
	<li><li><a href="/pages/warehouse/goods/index.php?<?= $item['goodsParent'] ? 'parent=' . $item['goodsParent'] : ''; ?>">Назад</a></li>
</ul>


<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
<div class="box neutral" style="display: inline-block;">
	<h2>Карточка: <?= $item['goodsName']; ?></h2>
	<div class="box-body">

		<!--
		<div style="display: inline-block;">
			<div class="personalUserpic"></div>
		<?
		foreach ($item['goodsBarcode'] as $barcode) {
//				printr($barcode);
			?>
																																																			<a target="_blank" style="padding: 0px;" href="/sync/plugins/barcodePrint.php?print=">
																																																			<svg class="barcode" style="border: 1px solid black; display: block; margin: 0 auto;" jsbarcode-text="<?= $barcode['code']; ?>" jsbarcode-value="<?= $barcode['code']; ?>" jsbarcode-width="1" jsbarcode-height="30" jsbarcode-fontSize="12" jsbarcode-font="Arial">
																																																			</svg>
																																																			</a>
			<?
		}
		?>

		</div>
		-->
		<table class="itemCardTable">
			<tr>
				<td class="btmdash">Раздел:</td>
				<td class="btmdash"><?
					if ($item['goodsEntryType'] == 1) {
						?>
						<?= $item['parentsName'] ?? 'без раздела'; ?>
						<?
					} else {
						?>
						<? if (R(15)) { ?><a href="#" data-function="editField" data-field="itemParent" data-item="<?= $item['idgoods']; ?>" data-value="<?= $item['idparents']; ?>"><? } ?><?= $item['parentsName'] ?? 'без раздела'; ?><? if (R(15)) { ?></a><? } ?>
						<?
					}
					?></td>
			</tr>
			<tr>
				<td class="btmdash">Название:</td>
				<td class="btmdash"><? if (R(15)) { ?><a href="#" data-function="editField" data-field="itemName" data-item="<?= $item['idgoods']; ?>" data-value="<?= $item['goodsName']; ?>"><? } ?><?= $item['goodsName'] ?? 'не указана'; ?><? if (R(15)) { ?></a><? } ?></td>
			</tr>
			<tr>
				<td class="btmdash">Лимиты:</td>
				<td class="btmdash">
					<div style="display: grid; grid-template-columns: auto 40px auto ;">
						<span>min: <? if (R(15)) { ?><a href="#" data-function="editField" data-field="goodsMinLimit" data-item="<?= $item['idgoods']; ?>" data-value="<?= $item['goodsMinAmnt'] === null ? '' : $item['goodsMinAmnt']; ?>"><? } ?><?= ($item['goodsMinAmnt'] === null ? 'Не задан' : ($item['goodsMinAmnt'] . '<span class="small">' . $item['unitsName'] . '</span>')); ?><? if (R(15)) { ?></a><? } ?></span>
						<span></span>
						<span>max: <? if (R(15)) { ?><a href="#" data-function="editField" data-field="goodsMaxLimit" data-item="<?= $item['idgoods']; ?>" data-value="<?= $item['goodsMaxAmnt'] ?? ''; ?>"><? } ?><?= ($item['goodsMaxAmnt'] ? ($item['goodsMaxAmnt'] . '<span class="small">' . $item['unitsName'] . '</span>') : 'Не задан'); ?><? if (R(15)) { ?></a><? } ?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="btmdash">Штрихкод:</td>
				<td class="btmdash">
					<?
					if (!empty($item['goodsBarcode'])) {
						?>
						<table><?
							foreach ($item['goodsBarcode'] as $goodsBarcode) {
								?>
								<tr>
									<td>
										<? if (R(15)) { ?><a href="#" data-function="editField" data-field="goodsBarcode" data-item="<?= $goodsBarcode['id']; ?>" data-value="<?= $goodsBarcode['code']; ?>"><? } ?><?= $goodsBarcode['code']; ?><? if (R(15)) { ?></a><? } ?>
									</td>
									<td>
										<? if (R(15)) { ?><button data-function="editField" class="btn deleteBarcode" data-field="deleteBarcode" data-item="<?= $goodsBarcode['id']; ?>">&Cross;</button><? } ?>
									</td>
								</tr>
								<?
							}
							?>
							<tr>

								<td></td><td><? if (R(15)) { ?><button data-function="editField" class="btn addBarcode" data-field="addBarcode" data-item="<?= $item['idgoods']; ?>">&plus;</button><? } ?></td>
							</tr>
						</table>
					<? } else {
						?>
						<? if (R(15)) { ?><a href="#" data-function="editField" data-field="addBarcode" data-item="<?= $item['idgoods']; ?>" data-value=""><? } ?>Не указан<? if (R(15)) { ?></a><? } ?>
						<?
					}
					?>
				</td>
			</tr>


			<tr>
				<td>На складе:</td>
				<td><a href="#" data-function="editField" data-field="goodsQty" data-item="<?= $item['idgoods']; ?>" data-value="<?= round($item['QTY'], 6); ?>"><?= round($item['QTY'], 6); ?> <?= $item['unitsName'] ?? ''; ?> </a></td>
			</tr>
			<tr>
				<td>Единица измерения (склад):</td>
				<td><? if (R(15)) { ?><a href="#" data-function="editField" data-field="idunits" data-item="<?= $item['idgoods']; ?>" data-value="<?= $item['idunits']; ?>"><? } ?><?= $item['unitsFullName'] ?? 'Не указана'; ?><? if (R(15)) { ?></a><? } ?></td>
			</tr>

			<tr>
				<td>Единица измерения (поставщик):</td>
				<td><? if (R(15)) { ?>
						<a href="#" data-function="editField" data-field="idunitsSupplier" data-item="<?= $item['idgoods']; ?>" data-value="<?= $item['idunitsSupplier']; ?>">
						<? } ?><?= $item['unitsSupplierFullName'] ?? $item['unitsFullName'] ?? 'Не указана'; ?><? if (R(15)) { ?></a><? } ?>
					<?
					if ($item['idunits'] !== $item['idunitsSupplier'] && $item['idunitsSupplier'] !== null) {
						?>
							<? if (R(15)) { ?><a <?= $item['goodsUSUratio'] ? '' : ' style="color: red;"' ?> href="#" data-function="editField" data-field="goodsUSUratio" data-item="<?= $item['idgoods']; ?>" data-value="<?= $item['goodsUSUratio']; ?>"><? } ?>
							(<?= $item['goodsUSUratio'] ?? '??'; ?><?= $item['unitsName']; ?>)
							<? if (R(15)) { ?></a><? } ?>
						<?
					}
					?>
				</td>
			</tr>

			<tr>
				<td>Цена:</td>
				<td><?
					if ($price) {
						?>

						<?= nf($price['inPrice'], 2); ?>р.
						<?= !empty($price['unitsName']) ? (' за 1' . $price['unitsName']) : ''; ?>



						<?
					} else {
						?>Не указана<?
					}
					?></td>
			</tr>
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
