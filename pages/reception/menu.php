<ul class="horisontalMenu">
	<li><a href="/pages/reception/index.php?date=<?= $_GET['date'] ?? date("Y-m-d"); ?>">Клиенты</a></li>
	<li><a href="/pages/reception/index.php?findClient&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>">Поиск клиента</a></li>
	<li><a href="/pages/reception/index.php?bypersonal&date=<?= $_GET['date'] ?? date("Y-m-d"); ?>">Специалисты</a></li>
	<? if (R(129)) { ?><li><a href="/pages/reception/marks.php?date=<?= $_GET['date'] ?? date("Y-m-d"); ?>">Зачёт</a></li><? } ?>
</ul>