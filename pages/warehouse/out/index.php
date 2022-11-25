<?php
$pageTitle = 'Списывать';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(6)) {
	?>E403R06<?
} else {
	?>
	<script>
		let _personal = {id: <?= isset($_GET['user']) ? $_GET['user'] : 'null'; ?>};
		let BCminLength = 5;
	</script>

	<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/warehouse/menu.php'; ?>
	<div class="box neutral">
		<div class="box-body">
			<div style="display: inline-block;">
				<div style="display: grid; vertical-align: middle; grid-template-columns: auto auto; grid-gap: 10px;">
					<input id="DATE" type="date" value="<?= isset($_GET['date']) ? $_GET['date'] : date("Y-m-d"); ?>" oninput="GETreloc('date',this.value);">
					<a target="_blank" href="/pages/warehouse/daily.php?date=<?= isset($_GET['date']) ? $_GET['date'] : date("Y-m-d"); ?>"><i class="fas fa-print" style="display: flex; align-self: center; background-color: white; box-shadow: 3px 3px 5px hsla(0,0%,0%,0.2); padding: 5px; border-radius: 8px; cursor: pointer;"></i></a>
				</div>
			</div>

			<div style="display: inline-block; vertical-align: middle;">
				<div id="back"></div><div id="personalInfo" style="padding: 10px; "><? if (isset($_GET['user'])) { ?>
						<a target="_blank" href="/pages/personal/info.php?employee=<?= FSI($_GET['user']); ?>"><?
							print mysqli_result(mysqlQuery("SELECT CONCAT_WS(' ',`usersLastName`,`usersFirstName`,`usersMiddleName`) as `name` FROM `users` WHERE `idusers` = '" . FSI($_GET['user']) . "'"), 0);
							?></a><? } ?>
				</div>

			</div>
			<table class="btmdashTable" id="withdrawal">
			</table>

		</div>

	</div>
	<script>
	<?= isset($_GET['date']) && isset($_GET['user']) ? ('renderBack("' . $_GET['date'] . '");') : ''; ?>
		(async function () {
			let wda = await getWithdrawal(_personal.id, qs('#DATE').value);
			renderWithdrawal(wda);
		})();
	</script>
	<?
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
