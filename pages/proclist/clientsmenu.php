<? if (R(175)) { ?>
	<ul class="horisontalMenu">
		<li><a href="/pages/proclist/client.php?client=<?= $_GET['client']; ?>">Процедуры</a></li>
		<? if (R(176)) { ?><li><a href="/pages/proclist/prescriptionslist.php?client=<?= $_GET['client']; ?>">Назначения</a></li><? } ?>
		<li><a href="/pages/proclist/quickexam.php?client=<?= $_GET['client']; ?>">Осмотр/динамика</a></li>
	</ul>		
<? } ?>