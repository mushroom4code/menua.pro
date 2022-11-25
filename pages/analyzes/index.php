<?php
$pageTitle = 'Обследования';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(46)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(46)) {
	?>E403R46<?
} else {
	?>
	<ul class="horisontalMenu">
		<li><a href="#" onclick="addItemWindow(<?= $_GET['parent'] ?? 'null' ?>);">Добавить</a></li>
	</ul>
	<div class="box neutral">
		<div class="box-body">

			<!--TPS_Catalog-->
			<?
			$qtext = "SELECT * "
//					. "(SELECT COUNT(*) FROM `WH_goods` WHERE `WH_goodsNomenclature` = `idWH_nomenclature`) AS `bcs`, "
//					. "(SELECT COUNT(*) FROM `WH_goodsSetsContent` WHERE `WH_goodsSetsContentSet` = `idWH_nomenclature`) AS `inset` "
					. " FROM `TPS_Catalog`"
					. " WHERE " . (isset($_GET['parent']) ? ("`TPS_CatalogParent` = '" . $_GET['parent'] . "'") : 'isnull(`TPS_CatalogParent`)') . ""
					. ";";
			$TPS_Catalog = query2array(mysqlQuery($qtext));




			usort($TPS_Catalog, function($a, $b) {
				if ($a['TPS_CatalogEntryType'] === $b['TPS_CatalogEntryType']) {
					return mb_strtolower($a['TPS_CatalogName']) <=> mb_strtolower($b['TPS_CatalogName']);
				} else {
					return $a['TPS_CatalogEntryType'] <=> $b['TPS_CatalogEntryType'];
				}
			});
//			printr($TPS_Catalog);
			?>

			<?
			if ($_GET['parent'] ?? 0) {
				$back = mfa(mysqlQuery("SELECT * FROM `TPS_Catalog` WHERE `idTPS_Catalog` = '" . FSI($_GET['parent']) . "'"));
//			print $back;
				?>
				<ul class="horisontalMenu">
					<li><a href="<?= GR('parent', $back['TPS_CatalogParent']); ?>">...На уровень выше</a></li>
				</ul>

				<?
			}
			?>

			<h2><?= $back['TPS_CatalogName'] ?? 'Все обследования'; ?></h2>

			<div style="display: inline-block;">
				<div style="display: grid; grid-template-columns: auto auto auto auto; grid-gap: 0px 10px;">
					<div></div>
					<div style="text-align: center; font-weight: bold;"></div>
					<div style="text-align: center; font-weight: bold;">Наименование</div>
					<div></div>




					<?
					foreach ($TPS_Catalog as $service) {
						?>
						<div><?= $service['idTPS_Catalog']; ?></div>

						<? if ($service['TPS_CatalogEntryType'] == 1) { ?>
							<div style="text-align: center; color: gray;">	
								<i class="fas fa-folder"></i>
							</div>	
							<div>
								<a href="/pages/analyzes/?parent=<?= $service['idTPS_Catalog']; ?>">
									<?= $service['TPS_CatalogName']; ?>
								</a>
							</div>
						<? } else { ?>
							<div style="text-align: center; color: gray;">	
								<i class="fas fa-microscope"></i>
							</div>
							<div>
								<a href="/pages/analyzes/services/?tps=<?= $service['idTPS_Catalog']; ?>">
									<?= $service['TPS_CatalogName']; ?>
								</a>
							</div>
						<? } ?>



						<div></div>
					<? } ?>



				</div>
			</div>

		</div>
	</div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
