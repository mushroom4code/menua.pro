<ul class="horisontalMenu">
	<li><a href="/pages/personal/info.php?employee=<?= FSI($_GET['employee']); ?>">Основная информация</a></li>
	<? if (R(21)) { ?><li><a href="/pages/personal/rights.php?employee=<?= FSI($_GET['employee']); ?>">Права доступа</a></li><? } ?>
	<? if (R(89)) { ?><li><a href="/pages/personal/skud.php?employee=<?= FSI($_GET['employee']); ?>">СКУД</a></li><? } ?>
	<? if (R(122)) { ?><li><a href="/pages/personal/time.php?employee=<?= FSI($_GET['employee']); ?>">Рабочее время</a></li><? } ?>
	<? if (R(125)) { ?><li><a href="/pages/personal/procedures.php?employee=<?= FSI($_GET['employee']); ?>">Процедуры</a></li><? } ?>
	<? if (R(120)) { ?><li><a href="/pages/personal/options.php?employee=<?= FSI($_GET['employee']); ?>">Оплата труда</a></li><? } ?>
	<? if ($_USER['id'] == 176) { ?><li><a href="/pages/personal/payments.php?employee=<?= FSI($_GET['employee']); ?>">ЗП</a></li><? } ?>
	<? if (R(121)) { ?><li><a href="/pages/personal/payments/?employee=<?= FSI($_GET['employee']); ?>">ЗП.нов</a></li><? } ?>
</ul>