<?php
$pageTitle = 'Проверка телефонов';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
	<div class="box-body">
		<?
		$phones = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`)"));
		$n = 0;
		foreach ($phones as $phone) {
			if (strlen($phone['clientsPhonesPhone']) == 11) {
				continue;
			}
			$n++;
			?>
			<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $phone['clientsPhonesClient']; ?>"><?= $phone['clientsPhonesPhone']; ?></a>
			<?
//			printr($phone);
//			break;
		}
		?>
		Телефонов: <?= $n; ?>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
