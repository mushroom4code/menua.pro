<ul class="horisontalMenu">
	<? foreach (query2array(mysqlQuery("SELECT * FROM `goodsTypes` WHERE isnull(`goodsTypesDeleted`)")) as $goodType) { ?>
		<? if (R(9)) { ?><li<?= active('/pages/warehouse/goods/index.php?filter=' . $goodType['goodsTypesName']); ?>><a href="/pages/warehouse/goods/?filter=<?= $goodType['idgoodsTypes']; ?>"><?= $goodType['goodsTypesName']; ?></a></li><? } ?>
	<? } ?>


</ul>




<ul class="horisontalMenu">
	<? if (R(6)) { ?><li<?= active('/pages/warehouse/out/index.php'); ?>><a href="/pages/warehouse/out/">Списание</a></li><? } ?>
	<? if (R(7)) { ?><li<?= active('/pages/warehouse/in/log'); ?>><a href="/pages/warehouse/in/">Приход</a></li><? } ?>
	<!--<? if (R(7)) { ?><li<?= active('/pages/warehouse/in/index.php'); ?>><a href="/pages/warehouse/in/">Накладная</a></li><? } ?>-->
	<? if (R(24)) { ?><li<?= active('/pages/warehouse/goods/orders.php'); ?>><a href="/pages/warehouse/goods/orders.php">Заказы</a></li><? } ?>
</ul>


