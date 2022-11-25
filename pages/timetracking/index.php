<?php
$pageTitle = 'Учёт рабочего времени';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(31)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(31)) {
	?>E403R31<?
} else {
	?>

	<div style="display: inline-block;"><input type="date" value="<?= $_GET['date'] ?? date("Y-m-d"); ?>" onchange="GETreloc('date', this.value);" autocomplete="off"></div>
	<br>



	<?
	$fingersEntyies = query2array(mysqlQuery("SELECT * "
					. " FROM `fingerLog` "
					. " LEFT JOIN `users` ON (`idusers` = `fingerLogUser`)"
					. " LEFT JOIN `usersGroups` ON (`idusersGroups` = `usersGroup`)"
					. " WHERE NOT isnull(`idusers`) AND `fingerLogTime`>'" . ($_GET['date'] ?? date("Y-m-d")) . " 00:00:00' AND `fingerLogTime`<'" . ($_GET['date'] ?? date("Y-m-d")) . " 23:59:59'"));
//	printr($fingersEntyies[0]);
	$employees = [];
	foreach ($fingersEntyies as $fingersEntyie) {
		$employees[$fingersEntyie['idusers']]['id'] = $fingersEntyie['idusers'];
		$employees[$fingersEntyie['idusers']]['stamps'][] = $fingersEntyie['fingerLogTime'];
		$employees[$fingersEntyie['idusers']]['name'] = $fingersEntyie['usersLastName'] . ' ' . $fingersEntyie['usersFirstName'];
		$employees[$fingersEntyie['idusers']]['usersGroupsName'] = $fingersEntyie['usersGroupsName'] ?? '';
		$employees[$fingersEntyie['idusers']]['idusersGroups'] = $fingersEntyie['idusersGroups'] ?? '0';
	}

	$employees = obj2array($employees);

	foreach ($employees as &$employee) {

		sort($employee['stamps']);
	}

//	printr($employees);
	?>
	<script>
		function skipGroup(group, state, solo) {
	//			state = !state;
			skip = {};
			if (solo === 0) {

				skip[group] = state ? 'true' : null;
				GR(skip);
			}

			if (solo === 1) {
				document.querySelector(`#skipGrops`).querySelectorAll(`input`).forEach(input => {
					if (input.id != group) {
						skip[input.id] = true;
					} else {
						skip[input.id] = null;
					}
				});
				GR(skip);
			}
			event.preventDefault(true);
//			console.log(group, state, solo);
		}
	</script>
	<div class="box neutral" id="skipGrops">
		<span style="white-space: nowrap;" onclick="skipGroup('skip0', skip0.checked, 0);" oncontextmenu="skipGroup('skip0', this.checked, 1); void(0); return false;" >
			<input <?
			if (!($_GET['skip0'] ?? false)) {
				print ' checked';
			}
			?> type="checkbox" id="skip0"><label for="skip0">Без группы</label>
		</span>
		<? foreach (query2array(mysqlQuery("SELECT * FROM `usersGroups`")) as $usersGroup) {
			?>
			<span style="white-space: nowrap;" onclick="skipGroup('skip<?= $usersGroup['idusersGroups']; ?>', skip<?= $usersGroup['idusersGroups']; ?>.checked, 0);" oncontextmenu="skipGroup('skip<?= $usersGroup['idusersGroups']; ?>', skip<?= $usersGroup['idusersGroups']; ?>.checked, 1); void(0); return false;">
				<input id="skip<?= $usersGroup['idusersGroups']; ?>" type="checkbox"    <?
				if (!($_GET['skip' . $usersGroup['idusersGroups']] ?? false)) {
					print ' checked';
				}
				?> id="skip<?= $usersGroup['idusersGroups']; ?>"><label for="skip<?= $usersGroup['idusersGroups']; ?>"><?= $usersGroup['usersGroupsName']; ?></label>
			</span>

			<?
		}
		?>
	</div>

	<div class="box neutral">
		<div class="box-body">
			<h2>Приход</h2>

			<div style="display: grid; grid-template-columns: auto auto auto auto;" class="lightGrid">
				<?
				uasort($employees, function ($a, $b) {
					return $a['stamps'][0] <=> $b['stamps'][0];
				});
				$employeesCNT = 0;
				foreach ($employees as $iduser => $employee2) {
					if (($_GET['skip' . $employee2['idusersGroups']] ?? false)) {
						continue;
					}
					$employeesCNT++;
					?>
					<div style="display: contents;">
						<div class="C"><?= date("H:i:s", strtotime($employee2['stamps'][0])); ?></div>
						<div><? if (date("Hi", strtotime($employee2['stamps'][0])) > 1005) {
						?><i class="fas fa-exclamation-triangle" style="color: red;"></i><? }
					?></div>
						<div><?= mb_substr($employee2['usersGroupsName'] ?? '--', 0, 10); ?></div>
						<div><a target="_blank" href="/pages/personal/info.php?employee=<?= $employee2['id']; ?>"><?= $employee2['name']; ?></a></div>
					</div>
					<?
				}
				?>

				<hr style="display: block; grid-column: span 4;">
				<span style="grid-column: span 4; text-align: center;">Итого: <?= human_plural_form($employeesCNT, ['сотрудник', 'сотрудника', 'сотрудников'], true); ?></span>
			</div>

		</div>
	</div>
	<div class="box neutral">
		<div class="box-body">
			<h2>Уход</h2>
			<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto auto;">
				<?
				usort($employees, function ($a, $b) {

					if (count($a['stamps']) && count($b['stamps'])) {
						return $a['stamps'][count($a['stamps']) - 1] <=> $b['stamps'][count($b['stamps']) - 1];
					} elseif (count($a['stamps'])) {
						return -1;
					} elseif (count($b['stamps'])) {
						return 1;
					} else {
						return 0;
					}
				});

				foreach ($employees as $employee3) {
					if (($_GET['skip' . $employee3['idusersGroups']] ?? false)) {
						continue;
					}
					?>
					<div style="display: contents;">
						<div class="C"><? if (count($employee3['stamps']) > 1) { ?>
								<?= date("H:i:s", strtotime($employee3['stamps'][count($employee3['stamps']) - 1])); ?>
							<? } else {
								?>--:--:--<? }
							?></div>

						<div><?
							$seconds = strtotime($employee3['stamps'][count($employee3['stamps']) - 1]) - strtotime($employee3['stamps'][0]);

							$hours = floor($seconds / (60 * 60));
							$minutes = floor(($seconds % 3600) / 60);
							if ($hours > 0) {
								print "" . $hours . "ч. " . $minutes . "мин.";
							} else {
								if (count($employee3['stamps']) > 1) {
									print '<1ч.';
								} else {
									print 'Нет ухода';
								}
							}
							?></div>
						<div><? if (date("Hi", strtotime($employee3['stamps'][count($employee3['stamps']) - 1])) < 1600) {
								?>
																																																					<!--<i class="fas fa-exclamation-triangle" style="color: red;"></i>-->
							<? }
							?></div>	
						<div><?= mb_substr($employee3['usersGroupsName'] ?? '--', 0, 10); ?></div>
						<div>
							<a target="_blank" href="/pages/personal/info.php?employee=<?= $employee3['id']; ?>">
								<?= $employee3['name']; ?>
							</a>
						</div>
					</div>
					<?
				}
				?>

			</div>
		</div>
	</div>



<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
