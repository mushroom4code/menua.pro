<?php
$pageTitle = 'Ресурсы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';



if (!R(24)) {
	?>E403R24<?
} else {

	if (isset($_GET['order'])) {
		$order = query2array(mysqlQuery("SELECT *, "
						. "`U1`.`unitsName` as `warhouseUnitsName`,"
						. "`U2`.`unitsName` as `supplierUnitsName`"
						. " FROM `orders` "
						. " LEFT JOIN `orderedItems` ON (`orderedItemsOrder` = `idorders`)"
						. " LEFT JOIN `goods` ON (`idgoods` = `orderedItemsItem`) "
						. " LEFT JOIN `units` AS `U1` ON (`U1`.`idunits` = `goodsUnit`)"
						. " LEFT JOIN `units` AS `U2` ON (`U2`.`idunits` = `goodsSupplierUnit`)"
						. "WHERE `idorders`='" . FSI($_GET['order']) . "'"));
//		printr($order);
		?>
		<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
		<div class="box neutral">
			<div class="box-body">
				<table class="boot">
					<thead>
						<tr>
							<th>#</th>
							<th>БД</th>
							<th>Наименование</th>
							<th>количество</th>
							<th></th>
							<th>#</th>
						</tr>
					</thead>
					<tbody>
						<?
						$n = 0;
						foreach ($order as $item) {
							?>
							<tr>
								<td><?= ++$n; ?></td>
								<td><?= $item['idgoods']; ?></td>
								<td><a href="/pages/warehouse/goods/item/?item=<?= $item['idgoods']; ?>" target="_blank"><?= $item['goodsName']; ?></a></td>
								<td class="C"><? if ($item['orderedItemsChecked']) { ?><?= $item['orderedItemsQty']; ?><? } else { ?><input oninput="this.value = onlyDigits(this.value);" type="text" id="ioi_<?= $item['idorderedItems']; ?>" style="width: 60px; text-align: center;" value="<?= $item['orderedItemsQty']; ?>"> <? } ?><small><?= $item['supplierUnitsName'] ?? $item['warhouseUnitsName']; ?></small></td>
								<td><?= ($item['goodsUnit'] !== $item['goodsSupplierUnit'] && $item['goodsSupplierUnit'] !== null) ? ($item['orderedItemsQty'] * ($item['goodsUSUratio'] ?? 1) . '' . ('<small>' . $item['warhouseUnitsName'] . '</small>')) : ''; ?></td>
								<td class="C"><? if ($item['orderedItemsChecked']) { ?><span style="font-weight: bolder; color: green;">&check;</span><? } else { ?><input type="button" style="font-weight: bolder; color: green; margin: 0px;" value="Подтвердить получение" onclick="confirmItem(<?= $item['idorderedItems']; ?>, qs(`#ioi_<?= $item['idorderedItems']; ?>`).value);"><? } ?></td></tr>
							<?
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<div style="text-align: right; color: black;">
			<?
			$order = mfa(mysqlQuery("SELECT * FROM `orders` WHERE `idorders` = '" . FSI($_GET['order']) . "'"));

			if ($order['ordersDone']) {
				?>
				Заказ закрыт <?= date("d.m.Y", strtotime($order['ordersDone'])); ?>
				<?
			} else {
				?>
				<button type="button" onclick="orderConfirm(<?= $_GET['order']; ?>);">Закрыть заказ</button>
				<?
			}
			?>

		</div>
		<?
	} else {


		$orders = query2array(mysqlQuery("SELECT *, (SELECT COUNT(*) FROM `orderedItems` WHERE `orderedItemsOrder` = `idorders`) as `items` FROM"
						. " `orders`"
						. "LEFT JOIN `suppliers` ON (`idsuppliers` = `ordersSupplier`)"));
		//		printr($orders[0]);
		?>
<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
		<div class="box neutral">
			<div class="box-body">
				<table>
					<thead>
						<tr>
							<th>#</th>
							<th>№</th>
							<th>Дата</th>
							<th>Поставщик</th>
							<th>Позиций</th>
							<th>Состояние</th>
						</tr>
					</thead>
					<tbody>
						<?
						$n = 0;
						foreach ($orders as $order) {
							$n++;
							?>
							<tr>
								<td><?= $n; ?></td>
								<td><a href="/pages/warehouse/goods/orders.php?order=<?= $order['idorders']; ?>"><?= $order['idorders']; ?></a></td>
								<td><a href="/pages/warehouse/goods/orders.php?order=<?= $order['idorders']; ?>"><?= $order['ordersDate'] ? date("Y.m.d", strtotime($order['ordersDate'])) : '???'; ?></a></td>
								<td><a href="/pages/warehouse/goods/orders.php?order=<?= $order['idorders']; ?>"><?= $order['suppliersName']; ?></a></td>
								<td class="C"><?= $order['items']; ?></td>
								<td class="C"><?= $order['ordersDeleted'] ? 'Удалён' : ( $order['ordersDone'] ? 'Завершен' : 'В процессе'); ?></td>
							</tr>
							<?
						}
						?>
					</tbody>
				</table>

			</div>
		</div>


		<?
	}
}


include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
