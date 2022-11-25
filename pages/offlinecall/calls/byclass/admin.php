<?php
$load['title'] = $pageTitle = 'Обзвон II';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(199)) {
	?>E403R199<?
} else {
	include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/menu.php';

	$users = query2array(mysqlQuery("SELECT * FROM `users` WHERE `usersGroup` = 9 AND isnull(`usersDeleted`)"));
	$OCC_tasks = query2array(mysqlQuery("SELECT * FROM `OCC_tasks`"));
	$OCC_tasksLimits = query2array(mysqlQuery("SELECT * FROM `OCC_tasksLimits`"));

	function findOCC_tasksLimit($user, $task) {
//		printr([$user, $task]);
		global $OCC_tasksLimits;
		$filtered_array = array_values(array_filter($OCC_tasksLimits, function ($val) use ($user, $task) {
					return ($val['OCC_tasksLimitsUser'] == $user and $val['OCC_tasksLimitsTask'] == $task);
				}));
		return $filtered_array[0]['OCC_tasksLimitsLimit'] ?? 0;
	}
	?>
	<div class="box neutral">
		<div class="box-body">
			<? include $_SERVER['DOCUMENT_ROOT'] . '/pages/offlinecall/calls/callsmenu.php'; ?>
		</div>
	</div>
	<div class="box neutral">
		<div class="box-body">
			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(11,auto);">
				<div style="display: contents;">
					<div style="grid-row: span 3; display: flex; align-items: center; justify-content: center;">
						Сотрудник
					</div>
					<div style="grid-column: span 10; display: flex; align-items: center; justify-content: center;">
						Задача
					</div>
				</div>
				<div style="display: contents;">
					<? foreach ($OCC_tasks as $OCC_task) {
						?>
						<div style="grid-column: span 2; display: flex; align-items: center; justify-content: center;">
							<?= $OCC_task['OCC_tasksName']; ?>
						</div>
					<? } ?>
				</div>
				<div style="display: contents;">
					<? foreach ($OCC_tasks as $OCC_task) {
						?>
						<div style="display: flex; align-items: center; justify-content: center;">
							План
						</div>
						<div style="display: flex; align-items: center; justify-content: center;">
							Готово
						</div>
					<? } ?>
				</div>
				<?
				foreach ($users as $user) {
					?>
					<div style="display: contents;">
						<div>
							<?= $user['usersLastName']; ?>
							<?= $user['usersFirstName']; ?>
							<?= $user['usersMiddleName']; ?>
						</div>
						<? foreach ($OCC_tasks as $OCC_task) {
							?>
							<div>
								<input autocomplete="off" type="text" style="width: auto; display: inline-block; text-align: center;" size="2" name="tasklimit[<?= $user['idusers'] ?>][<?= $OCC_task['idOCC_tasks'] ?>]" oninput="digon(); savelimit(<?= $user['idusers'] ?>,<?= $OCC_task['idOCC_tasks'] ?>,this.value)" value="<?= findOCC_tasksLimit($user['idusers'], $OCC_task['idOCC_tasks']); ?>">
							</div>
							<div style="display: flex; align-items: center; justify-content: center;">
								---
							</div>
						<? } ?>
					</div>
					<?
				}
				?>
			</div>
		</div>
	</div>
	<script>
		function savelimit(user, task, limit) {
			fetch('IO.php', {
				body: JSON.stringify({user, task, limit}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}
			);
		}
	</script>
	<?
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
