<?php
$pageTitle = 'Склад';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(13)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(13)) {
	?>E403R13<?
} else {
	?>
	<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>

	<ul class="horisontalMenu">
		<li><a href="?">Все</a></li>
		<? if (R(14)) { ?><li><a href="#" onclick="addItemWindow();">Добавить</a></li><? } ?>
		<? if (1) { ?><li><a href="?urgent" <?= isset($_GET['urgent']) ? ' class="active"' : ''; ?>>К закупке</a></li><? } ?>
		<li><a data-function="searchWindow">Поиск</a></li>
	</ul>
	<script>
		let currentLevel = <?= $_GET['parent'] ?? 'null'; ?>;
	<? $goodsTypes = query2array(mysqlQuery("SELECT `idgoodsTypes` as `id`, `goodsTypesName` as `name` FROM `goodsTypes`")) ?>
		let goodsTypes = JSON.parse('<?= json_encode($goodsTypes, 288); ?>');
	</script>
	<?
	if (isset($_GET['urgent'])) {
		include 'urgent.php';
	} else {

		$filter = $_GET['filter'] ?? 1;
		$qtext = "SELECT *,"
				. "(SELECT COUNT(*) FROM `WH_goods` WHERE `WH_goodsNomenclature` = `idWH_nomenclature`) AS `bcs`, "
				. "(SELECT COUNT(*) FROM `WH_goodsSetsContent` WHERE `WH_goodsSetsContentSet` = `idWH_nomenclature`) AS `inset` "
				. " FROM `WH_nomenclature`"
				. " LEFT JOIN `units` ON (`idunits` = `WH_nomenclatureUnits`)"
				. " WHERE " . (isset($_GET['parent']) ? ("`WH_nomenclatureParent` = '" . $_GET['parent'] . "'") : 'isnull(`WH_nomenclatureParent`)') . ""
				. "AND `WH_nomenclatureType` = " . FSI($filter) . ";";
		$result = mysqlQuery($qtext);
		$goods = query2array($result);
		usort($goods, function ($a, $b) {
			if ($a['WH_nomenclatureEntryType'] === $b['WH_nomenclatureEntryType']) {
				return mb_strtolower($a['WH_nomenclatureName']) <=> mb_strtolower($b['WH_nomenclatureName']);
			} else {
				return $a['WH_nomenclatureEntryType'] <=> $b['WH_nomenclatureEntryType'];
			}
		});
		?>
		<?
		if ($_GET['parent'] ?? 0) {
			$back = mfa(mysqlQuery("SELECT * FROM `WH_nomenclature` WHERE `idWH_nomenclature` = '" . FSI($_GET['parent']) . "'"));
//			print $back;
			?>
			<ul class="horisontalMenu">
				<li><a href="<?= GR('parent', $back['WH_nomenclatureParent']); ?>">...На уровень выше</a></li>
			</ul>

			<?
		}
		?>
		<div class="box neutral">
			<div class="box-body">
				<h2><?= $back['WH_nomenclatureName'] ?? $goodsTypes[array_search($filter, array_column($goodsTypes, 'id'))]['name']; ?></h2>
				<? // printr($goods[17]); ?>
				<div style="display: grid; grid-template-columns: auto auto auto auto auto; grid-gap: 0px 10px;">
					<div></div>
					<div style="text-align: center; font-weight: bold;"></div>
					<div style="text-align: center; font-weight: bold;"><i class="fas fa-barcode"></i></div>
					<div style="text-align: center; font-weight: bold;">Наименование</div>
					<div></div>
					<?
					foreach ($goods as $good) {

						$wh_goods = query2array(mysqlQuery("SELECT * FROM `WH_goods` WHERE `WH_goodsNomenclature`='" . $good['idWH_nomenclature'] . "';"));
						?>

						<?
						$balance = 0;
						foreach ($wh_goods as $wh_good) {
							$st = "SELECT IFNULL(`WH_stocktakingQty`, 0) AS `stQty`, IFNULL(`WH_stocktakingDate`, '2020-02-02 00:00:00') AS `stDate` FROM `WH_stocktaking` WHERE `idWH_stocktaking` = (SELECT MAX(`idWH_stocktaking`) FROM `WH_stocktaking` WHERE `WH_stocktakingDate` = (SELECT MAX(`WH_stocktakingDate`) FROM `WH_stocktaking` WHERE `WH_stocktakingGoods` = '" . $wh_good['idWH_goods'] . "'))";
							$WH_stocktaking = mfa(mysqlQuery($st));
//								printr($WH_stocktaking['stQty']);
							$WH_goodsIn = mfa(mysqlQuery("SELECT ifnull(SUM(`WH_goodsInQty`),0) as InSumm FROM `WH_goodsIn` "
											. "WHERE `WH_goodsInDate`>='" . ($WH_stocktaking['stDate'] ?? '2020-02-02 02:02:02') . "'"
											. " AND `WH_goodsInGoodsId`='" . $wh_good['idWH_goods'] . "'"));
							$WH_goodsOut = mfa(mysqlQuery("SELECT ifnull(SUM(`WH_goodsOutQty`),0) as OutSumm FROM `WH_goodsOut` "
											. "WHERE `WH_goodsOutDate`>='" . ($WH_stocktaking['stDate'] ?? '2020-02-02 02:02:02') . "'"
											. " AND `WH_goodsOutItem`='" . $wh_good['idWH_goods'] . "'"
											. " AND isnull(`WH_goodsOutDeleted`)"));
							$balance += ($WH_stocktaking['stQty'] ?? 0) + ($WH_goodsIn['InSumm'] ?? 0) - ($WH_goodsOut['OutSumm'] ?? 0);
						}
						?>

						<div style="text-align: center;"><? if ($good['WH_nomenclatureEntryType'] == 1) {
							?><i class="fas fa-folder btn"></i><?
							} elseif ($good['WH_nomenclatureEntryType'] == 2) {
								if (($good['WH_nomenclatureMin'] ?? 0) && ($good['WH_nomenclatureMax'] ?? 0)) {
									if ($good['WH_nomenclatureMin'] > 0) {
										$coeff = 60 / $good['WH_nomenclatureMin'];
									}
									if ($balance > 0 && $balance <= $good['WH_nomenclatureMin']) {
										$h = $coeff * $balance;
									} else {
										$h = 0;
									}

									if ($balance <= $good['WH_nomenclatureMin']) {
										?><i class="fas fa-shopping-cart" style="color: hsl(<?= $h; ?>,100%,50%);"></i><? } else {
										?>
										<i class="far fa-file-alt" style="color: black;"></i>
										<?
									}
								} else {
									?>
									<i class="far fa-file-alt" style="color: silver;"></i>
									<?
								}
							} elseif ($good['WH_nomenclatureEntryType'] == 3) {
								?><i class="fas fa-archive" style="color: gray;"></i><?
							}
							?><div>
							</div>
							<? ?></div>
						<div><?= $good['idWH_nomenclature']; ?></div>
						<div style="text-align: center;"><?
							if ($good['WH_nomenclatureEntryType'] != 1) {
								if ($good['bcs']) {
//									printr($good);
									?><a target="_blank" href="/sync/plugins/barcodePrint.php?item=<?= $good['idWH_nomenclature']; ?>"><i class="fas fa-barcode"></i><span style="font-size: 0.8em; line-height: 0.8em;"> x<?= $good['bcs']; ?></span></a><?
								} elseif ($good['inset']) {
									?>
									<i class="fas fa-prescription-bottle-alt" style="color: gray;"></i> <span style="font-size: 0.8em; line-height: 0.8em;"> x<?= $good['inset']; ?></span>
									<?
								} else {
									?><i class="fas fa-link" style="color: red;"></i><?
								}
							}
							?></div>
						<div><a href="<?= $good['WH_nomenclatureEntryType'] == 1 ? GR('parent', $good['idWH_nomenclature']) : ('/pages/warehouse/goods/item/?item=' . $good['idWH_nomenclature']); ?>"><?= $good['WH_nomenclatureName']; ?></a></div>
						<div style="text-align: right;"><?= $good['WH_nomenclatureEntryType'] == 2 ? ($balance ?? '') : ''; ?> <?= $good['WH_nomenclatureEntryType'] == 2 ? ($good['unitsName'] ?? '--') : ''; ?></div>

					<? } ?>
				</div>		
			</div>
		</div>












		<?
	}
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';

