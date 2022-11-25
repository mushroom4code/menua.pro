<ul class="horisontalMenu">
  <li><a href="check.php">Проверка базы</a></li>
  <? if ($_USER['id'] == 176 || R(40)) { ?><li><a href="upload.php?pbdupload">Загрузка ТБД</a></li><? } ?>
  <li><a href="/pages/remotecall/call.php">Обзвон</a></li>
  <? if (R(111)) { ?><li><a href="/pages/remotecall/calls.php">Звонки</a></li><? } ?>
  <? if (R(163) || R(164)) { ?><li><a href="/pages/remotecall/control.php">Контроль</a></li><? } ?>
  <? if (R(184)) { ?><li><a href="/pages/remotecall/olddb.php">Вторичные</a></li><? } ?>
  <? if (R(143)) { ?><li><a href="/pages/remotecall/confirm.php">Подтверждение</a></li><? } ?>
  <? if (R(143)) { ?><li><a href="/pages/remotecall/todays.php">Назначения</a></li><? } ?>
  <li><a href="/pages/remotecall/recall.php">Перезвонить</a></li>
  <? if (R(145)) { ?><li><a href="/pages/remotecall/calls.php">Перезвонить, админ</a></li><? } ?>
  <? if (R(173)) { ?><li><a href="/pages/remotecall/voice.php">Прослушивание звонков</a></li><? } ?>
  <li><a href="/pages/remotecall/entries.php">Записи</a></li>

  <!--<li><a href="/pages/remotecall/calls.php">Мои звонки</a></li>-->
</ul>
