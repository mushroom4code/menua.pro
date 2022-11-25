<?php
$load['title'] = $pageTitle = 'Коллцентр / Работа с пользователями';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(74)) {
	if ($_GET['check'] ?? false) {
		$user = mfa(mysqlQuery("SELECT * FROM `users` WHERE `idusers` = '" . intval($_GET['check']) . "'"));
		if (mysqlQuery("INSERT INTO `fingerLog` SET "
						. "`fingerLogUser` = '" . $user['idusers'] . "',"
						. "`fingerLogData` = '" . $user['usersFinger'] . "',"
						. "`fingerLogManual` = '" . $_USER['id'] . "'"
						. "")) {
			header("Location: " . GR('check'));
			die();
		}
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>
	.lightGrid {
		border-left: 1px solid silver;
		border-top:  1px solid silver;
		background-color: white;
	}

	.lightGrid>div:hover div {
		background-color: hsl(180, 80%, 95%);;
	}

	.lightGrid>div>div {
		padding: 3px;
		border-right: 1px solid silver;
		border-bottom:  1px solid silver;
	}


</style>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/menu.php';
if (!R(74)) {
	?>E403R74<?
} else {
	?>	
	<div class="box neutral">
		<div class="box-body">
			<div style="background-color: #F2F2F2; border: 1px solid gray; padding: 5px 20px; margin-bottom: 20px;">
				<a href="/pages/offlinecall/users/">Все</a> | 
				<a href="/pages/offlinecall/users/?deleted">Уволенные</a> | 
				<a href="/pages/offlinecall/users/?add">Добавить</a> 
			</div>
			<?
			if (isset($_GET['add'])) {
				
			} elseif (isset($_GET['deleted'])) {
				
			} else {
				$users = query2array(mysqlQuery("SELECT * FROM `usersPositions` left join `users` ON (`idusers`=`usersPositionsUser`) WHERE isnull(`usersDeleted`) AND `usersPositionsPosition` = '32' ORDER BY `usersLastName`, `usersFirstName`"));
				?>	

				<div style="display: grid; grid-template-columns: auto auto auto auto;" class="lightGrid">
					<div style="display: contents;">
						<div class="C B">КОД</div>
						<div class="C B">Ф.И.О. сотрудника</div>
						<div class="C B">Сегодня на смене</div>
						<div class="C B">Уволить</div>
					</div>
					<?
					foreach ($users as $user) {
						if (mfa(mysqlQuery("SELECT * FROM `fingerLog` WHERE `fingerLogTime` >= '" . date("Y-m-d 08:00:00") . "' AND `fingerLogTime` <= '" . date("Y-m-d 23:59:59") . "' AND `fingerLogUser` = '" . $user['idusers'] . "'"))) {
							$check = '<i class="fas fa-check-circle" style="color: green"></i>';
						} else {
							$check = '<i class="fas fa-check" style="color: silver;"></i>';
						}
						?>
						<div style="display: contents;">
							<div class="C"><?= sprintf('%03d', $user['idusers']); ?></div>
							<div><a target="_blank" href="/pages/personal/info.php?employee=<?= $user['idusers']; ?>"><?= $user['usersLastName']; ?> <?= $user['usersFirstName']; ?></a></div>
							<div class="C"><a href="<?= GR('check', $user['idusers']); ?>"><?= $check; ?></a></div>
							<div class="C"><i class="far fa-times-circle" style="color: red; cursor: pointer;" onclick="GR({'delete':<?= $user['idusers']; ?>})"></i><? ?></div>

						</div><? } ?>

				</div>
				<?
			}
			?>







		</div>
	</div>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
