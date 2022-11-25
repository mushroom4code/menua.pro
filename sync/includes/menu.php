<div class="menuWrapper">
	<ul class="menu">
		<li><a><?= $_USER['lname'] ?? ''; ?> <?= $_USER['fname'] ?? ''; ?></a></li>
		<? if (R(76)) { ?><li<?= active('/pages/infi/'); ?>><a href="/pages/infi/"><img src="/css/images/icq.svg" style="width: 22px; height: 22px; display: inline-block; vertical-align: middle;"> <?= BOTNAME; ?></a></li><? } ?>
		<? if (R(8) || R(39)) { ?><li<?= active('/pages/personal/index.php'); ?>><a href="/pages/personal/"><i class="fas fa-user-tie"></i> Персонал</a></li><? } ?>

		<? if (R(78)) { ?><li<?= active('/pages/mobile'); ?>><a href="/pages/mobile/"><i class="fas fa-mobile-alt"></i> Моб. офис</a></li><? } ?>
		<?
//printr($_USER['positions']);
		?>
			<? if (R(43)) { ?><li<?= active('/pages/positions/index.php'); ?>><a href="/pages/positions/"><i class="fas fa-users-cog"></i> Должности</a></li><? } ?>
		<? if (R(35) || array_search_2d(32, ($_USER['positions'] ?? []), 'id')) { ?><li<?= active('/pages/remotecall/index.php'); ?>><a href="/pages/remotecall/"><i class="fas fa-house-user"></i> Маркетинг</a></li><? } ?>
		<? if (R(47)) { ?><li<?= active('/pages/offlinecall/index.php'); ?>><a href="/pages/offlinecall/"><i class="fas fa-phone-alt ICOfirst"></i> Коллцентр</a></li><? } ?>
		<? if (R(52)) { ?><li<?= active('/pages/service/index.php'); ?>><a href="/pages/service/"><i class="fas fa-phone-alt ICOsecondary"></i> Сервис</a></li><? } ?>
		<? if (R(32)) { ?><li<?= active('/pages/clients/index.php'); ?>><a href="/pages/clients/"><i class="far fa-address-book"></i> Клиенты</a></li><? } ?>
		<? if (R(9)) { ?><li<?= active('/pages/warehouse/goods/index.php'); ?>><a href="/pages/warehouse/goods/"><i class="fas fa-dolly"></i> Склад</a></li><? } ?>
		<? if (R(42)) { ?><li<?= active('/pages/reception/index.php'); ?>><a href="/pages/reception/"><i class="fas fa-stream"></i> Регистратура</a></li><? } ?>

		<? if (R(138)) { ?><li<?= active('/pages/recruiting/index.php'); ?>><a href="/pages/recruiting/"><i class="far fa-address-card"></i> Рекрутинг</a></li><? } ?>

		<? if (R(45)) { ?><li<?= active('/pages/proclist/index.php'); ?>><a href="/pages/proclist/"><i class="fas fa-clipboard-list"></i> Проц.лист</a></li><? } ?>
		<?
		if (R(45) || mfa(mysqlQuery("SELECT * FROM `f_planUsers` WHERE "
								. " `f_planUsersYear` = '" . date("Y") . "' "
								. " AND `f_planUsersMonth` = '" . date("n") . "' "
								. " AND `f_planUsersUser` = " . $_USER['id'] . " "
								. ""))) {
			?><li<?= active('/pages/statistic/index.php'); ?>><a href="/pages/statistic/"><i class="fas fa-chart-line"></i> Статистика</a></li><? } ?>
		<? if (R(51)) { ?><li<?= active('/pages/schedule/index.php'); ?>><a href="/pages/schedule/"><i class="fas fa-calendar-alt"></i> График работы</a></li><? } ?>
		<? if (R(10)) { ?><li<?= active('/pages/suppliers/index.php'); ?>><a href="/pages/suppliers/"><i class="fas fa-boxes"></i> Поставщики</a></li><? } ?>
		<? if (R(25)) { ?><li<?= active('/pages/files/index.php'); ?>><a href="/pages/files/"><i class="far fa-file-word"></i> Документы</a></li><? } ?>
		<? if (R(46)) { ?><li<?= active('/pages/analyzes/index.php'); ?>><a href="/pages/analyzes/"><i class="fas fa-flask"></i> Обследования</a></li><? } ?>
		<? if (R(26)) { ?><li<?= active('/pages/checkout/index.php'); ?>><a href="/pages/checkout/"><i class="fas fa-cash-register"></i> Оплата</a></li><? } ?>
		<? if (R(48)) { ?><li<?= active('/pages/reports/index.php'); ?>><a href="/pages/reports/"><i class="fas fa-chart-area"></i> Отчёты</a></li><? } ?>
		<? if (R(92)) { ?><li<?= active('/pages/equipment/index.php'); ?>><a href="/pages/equipment/"><i class="fas fa-tools"></i> Оборудование</a></li><? } ?>

		<? if (R(171)) { ?><li<?= active('/pages/price/index.php'); ?>><a href="/pages/price/"><i class="fas fa-dollar-sign"></i> Прайс</a></li><? } ?>

		<? if (R(28)) { ?><li<?= active('/pages/services/index.php'); ?>><a href="/pages/services/"><i class="fas fa-book-medical"></i> Услуги</a></li><? } ?>
		<? if (R(191)) { ?><li<?= active('/pages/payments/index.php'); ?>><a href="/pages/payments/"><i class="fas fa-dollar-sign"></i> Начисления</a></li><? } ?>
		<? if (R(196)) { ?><li<?= active('/pages/telegram/index.php'); ?>><a href="/pages/telegram/"><i class="fab fa-telegram-plane"></i> Телеграм БОТ</a></li><? } ?>
		<? if (1 && R(28)) { ?><li<?= active('/pages/services2/index.php'); ?>><a href="/pages/services2/"><i class="fas fa-book-medical"></i> Услуги 2</a></li><? } ?>
		<? if (R(31)) { ?><li<?= active('/pages/timetracking/index.php'); ?>><a href="/pages/timetracking/"><i class="fas fa-stopwatch"></i> Учёт времени</a></li><? } ?>
		<? if (R(44)) { ?><li<?= active('/pages/salesbook/index.php'); ?>><a href="/pages/salesbook/">Книга продаж</a></li><? } ?>
		<? if (R(95)) { ?><li<?= active('/pages/salesbook2/index.php'); ?>><a href="/pages/salesbook2/">Книга продаж МВ</a></li><? } ?>
		<li class="divider"></li>
		<? if (R(146)) { ?><li<?= active('/pages/cashflow/index.php'); ?>><a href="/pages/cashflow/">Касса</a></li><? } ?>
		<? if (R(142)) { ?><li><a target="_blank" href="/pages/reports/servicessales/">Продажи услуг</a></li><? } ?>
		<? if (in_array($_USER['id'], [176, 199])) { ?><li<?= active('/pages/rs/index.php'); ?>><a href="/pages/rs/">РС</a></li><? } ?>
		<? if (in_array($_USER['id'], [176, 199, 135])) { ?><li<?= active('/pages/banks/index.php'); ?>><a href="/pages/banks/">Банки</a></li><? } ?>
		<? if (R(134)) { ?><li<?= active('/pages/stat/index.php'); ?>><a href="/pages/stat/">Стат</a></li><? } ?>
		<? if (R(136)) { ?><li<?= active('/pages/bigone/index.php'); ?>><a href="/pages/bigone/">Сводная</a></li><? } ?>

		<? if (in_array($_USER['id'], [176, 199])) { ?><li class="divider"></li><? } ?>
		<? if (0) { ?><li<?= active('/pages/settings/index.php'); ?>><a href="/pages/settings/">Настройки</a></li><? } ?>


		<? if (!(mfa(mysqlQuery("SELECT `usersTG` FROM `users` WHERE `idusers`='" . $_USER['id'] . "'"))['usersTG'] ?? false)) { ?><li style="white-space: nowrap;"><a href="/pages/tgconnect/"><i class="fab fa-telegram-plane"></i>&nbsp;Уведомления</a></li><? } ?>

		<li><a href="/?logout">Выход</a></li>
	</ul>
</div>