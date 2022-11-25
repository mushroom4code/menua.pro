<?php
$pageTitle = 'Приход';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(7)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(7)) {
	?>E403R27<?
} else {
	?>
	<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
	<input type="date" id="date" autocomplete="off" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>" onchange="GETreloc('date', this.value);">
	<div class="box neutral">
		<div class="box-body">

			<?
			$OUT = [];

			$OUT['entries'] = [];
			//idWH_goodsIn, WH_goodsInGoodsId, WH_goodsInQty, WH_goodsInUnits, WH_goodsInDate, WH_goodsInTime, WH_goodsInCN, WH_goodsInPrice, WH_goodsInVatSumm, WH_goodsInSummIncVat, WH_goodsInUser, WH_goodsInSupplier
			$result = query2array(mysqlQuery(""
							. "SELECT * "
							. " FROM `WH_goodsIn`"
							. " LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsInGoodsId`)"
							. " LEFT JOIN `units` ON (`idunits` = `WH_goodsInUnits`)"
							. " WHERE `WH_goodsInDate` BETWEEN '" . FSS($_GET['date'] ?? date("Y-m-d")) . " 00:00:00' AND '" . FSS($_GET['date'] ?? date("Y-m-d")) . " 23:59:59'"));
			if (count($result)) {


//				printr($result[0]);
				?>
				<div style="display: grid; grid-template-columns: auto auto auto; grid-gap: 0px 10px;">
					<div>Наименование</div>
					<div>кол-во</div>
					<div>ед.изм</div>

					<?
					foreach ($result as $entry) {
						//printr($entry);
						?>
						<div><a href="/pages/warehouse/goods/item/?item=<?= $entry['WH_goodsNomenclature']; ?>" target="_blank"><?= $entry['WH_goodsName']; ?></a></div>
						<div style="text-align: right;"><?= round($entry['WH_goodsInQty'], 3); ?></div>
						<div><?= $entry['unitsName'] ?? '???'; ?></div>

						<?
					}
					?>
				</div>				
				<?
			} else {
				?>Нет данных за эту дату<?
			}
		}
		?>

	</div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
