<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>
    .hideChildren {
        cursor: pointer;
    }
    .table tr:hover{
        background-color: lightskyblue;
    }
    .hideChildren table {
        display: none;
    }
    .pointer {
        cursor: pointer;
    }
    .active{
        background-color: yellow;
    }
</style>

<?
if (!R(200) && $_USER['id'] != 176) {
	?>E403R<?
} else {
	$iduser = $_GET['user'] ?? $_USER['id'];

	include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/menu.php';

	function printclients($clients) {
		$n = 0;
		print '<div class="lightGrid" style="display: grid; grid-template-columns: repeat(5,auto);">';
		print '<div style="display: contents; position: sticky; top: 0px; background: white;">'
				. '<div>#</div>'
				. '<div>id</div>'
				. '<div>Ф.И.О. Клиента</div>'
				. '<div>Телефон(ы)</div>'
				. '<div>Последний звонок</div>'
				. '</div>';
		foreach ($clients as $client) {
			$n++;
//        printr($client, 1);
			print '<div style="display: contents;">';
/////////////////////////////////////////////////////
			print '<div>' . $n . '</div>';
			print '<div><a target="_blank" href="https://menua.pro/pages/offlinecall/schedule.php?client=' . $client['idclients'] . '">' . $client['idclients'] . '</a></div>';
			print '<div><a target="_blank" href="https://menua.pro/pages/offlinecall/schedule.php?client=' . $client['idclients'] . '">' . implode(' ', array_filter([$client['clientsLName'], $client['clientsFName'], $client['clientsMName']])) . '</a></div>';
			print '<div>' . $client['phones'] . '</div>';
			print '<div>' . ($client['OCC_callsTime'] ? date("d.m.Y H:i", strtotime($client['OCC_callsTime'])) : '') . '</div>';

/////////////////////////////////////////////////////
			print '</div>';
		}
		print '</div>';
	}

	$clients = query2array(mysqlQuery("SELECT *"
					. ", (SELECT MAX(`OCC_callsTime`) FROM `OCC_calls` WHERE `OCC_callsClient` = `idclients` AND `OCC_callsType`<>7) AS `OCC_callsTime`"
					. ", (SELECT GROUP_CONCAT(`clientsPhonesPhone` SEPARATOR ', ')  FROM `clientsPhones` WHERE isnull(`clientsPhonesDeleted`) AND `clientsPhonesClient`=`idclients`) as `phones`"
					. " FROM `clientsCategories`"
					. " LEFT JOIN `clients` ON (`idclients` = `clientsCategoriesClient`)"
					. " WHERE `clientsCategoriesCategory` ='" . mres($_GET['class']) . "'"
					. "AND (SELECT COUNT(1) FROM `f_sales` WHERE `f_salesSumm`>15000 AND `f_salesClient` = `idclients`) > 0 "
					. " HAVING NOT isnull(`phones`)"
					. " ORDER BY `OCC_callsTime`"));
	?>
	<div class="box neutral">
		<div class="box-body">
			<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/calls/callsmenu.php'; ?>
		</div>
	</div>
	<br>
	<div class="box neutral">
		<div class="box-body">
			<h1 style="margin: 10px;"><?= mfa(mysqlQuery("SELECT * FROM `OCC_tasks` WHERE `idOCC_tasks` = '" . mres($_GET['class']) . "'"))['OCC_tasksName']; ?></h1>
			Найдено <?= count($clients); ?>
			<?= printclients($clients); ?>
		</div>
	</div>
	<?
}
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
