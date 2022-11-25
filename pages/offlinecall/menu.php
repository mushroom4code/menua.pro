<ul class="horisontalMenu">
	<li><a href="/pages/offlinecall/addnewclient.php">Клиент</a></li>
	<!--<li><a href="/pages/offlinecall/calls/">обзвон</a></li>-->
	<? if (R(179)) { ?><li><a href="/pages/offlinecall/confirm/">Подтверждение</a></li><? } ?>
	<? if (R(80)) { ?><li><a href="/pages/offlinecall/calls/">Обзвон II</a></li><? } ?>
	<? if (R(74)) { ?><li><a href="/pages/offlinecall/users/">Операторы I</a></li><? } ?>
	<? if (R(87)) { ?><li><a href="/pages/offlinecall/usersII/">Операторы II</a></li><? } ?>

	<? if (R(178)) { ?><li><a href="/pages/offlinecall/incomecalls/">Входящие звонки</a></li><? } ?>
	<!--<li><a href="/pages/offlinecall/clients.php">Клиенты</a></li>-->
</ul>
