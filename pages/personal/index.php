<?php
$load['title'] = $pageTitle = 'Персонал';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?><script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>


<? include 'includes/topmenu.php'; ?>

<div class="divider"></div>
<?
if (isset($_GET['add'])) {
	if (R(17) || R(34)) {
		?>
		<table style="color: black;">
			<tr>
				<td>Фамилия:</td>
				<td><input type="text" id="LN" autocomplete="off"></td>
				<td></td>
			</tr>
			<tr>
				<td>Имя:</td>
				<td><input type="text" id="FN" autocomplete="off"></td>
				<td></td>
			</tr>
			<tr>
				<td>Отчество:</td>
				<td><input type="text" id="MN" autocomplete="off"></td>
				<td></td>
			</tr>
			<tr>
				<td>Номер телефона:</td>
				<td><input type="text" id="phone" autocomplete="off" placeholder="89123456789"></td>
				<td></td>
			</tr>
			<tr>
				<td>Должность:</td>
				<td>
					<select style="width: auto;" id="PN">
						<option value=""></option>
						<? foreach ($positions as $position) { ?>
							<option<?= ((isset($_SESSION['addUserPosition']) && $_SESSION['addUserPosition'] == $position['idpositions']) ? ' selected' : '' ); ?> value="<?= $position['idpositions']; ?>"><?= $position['positionsName']; ?></option>
						<? } ?>
					</select>
				</td>
				<td></td>
			</tr>
			<tr>
				<td>Штрихкод:</td>
				<td><input type="text" id="barcode" placeholder="сканировать или создать" autocomplete="off"></td>
				<td><input type="button" value="+" onclick="genBC();"> <button  onclick="printBC(qs('#barcode').value.trim(), qs('#LN').value.trim(), qs('#FN').value.trim());"><i class="fas fa-print"></i></button></td>
			</tr>
			<tr>
				<td  colspan="2" style="text-align: right;"><button style="margin: 0px;" onclick="addPersonal();">Внести сотрудника</button></td>
				<td></td>
			</tr>

		</table>
		<?
	} else {
		?>E403R17<?
	}
	?>


	<?
} elseif (isset($_GET['search'])) {
	?>
	<div class="box neutral">
		<div class="box-body" style="text-align: center; font-size: 3em; padding: 2em;">¯\_(ツ)_/¯</div>
	</div>

	<?
} elseif (isset($_GET['employee'])) {
	ICQ_messagesSend_SYNC('sashnone', print_r($_SERVER, 1));
} else {
	if (R(16) || R(33)) {// Просмотр списка сотрудников
		?>
		<div class="box neutral">
			<div class="box-body">
				<table style="color: black;">
					<thead>
						<tr>
							<th style="">№<input type="checkbox" id="checkAll" onclick="
											let show = false;
											for (let elem of qsa('input[data-print]')) {
												elem.checked = this.checked;
												if (elem.checked) {
													show = true;
												}
											}
											qs('#printSelected').style.display = show ? '' : 'none';
												 "><label for="checkAll"></label></th>
							<th><i class="fas fa-barcode"></i></th>
							<th><i class="fas fa-fingerprint"></i></th>
							<th><i class="far fa-id-badge"></i></th>
							<th><img src="/css/images/icq.svg" style="width: 22px; height: 22px;"></a></th>
							<th><i class="fab fa-telegram-plane"></i></th>
							<? if (R(194)) { ?><th><i class="fas fa-dollar-sign"></i></th><? } ?>
							<th><i class="fas fa-user"></i></th>
							<th>Человек</th>
							<th>Работа</th>
							<? if (R(19)) { ?><th>Кнопка</th><? } ?>
						</tr>
					</thead>
					<?
					$n = 0;
					$personalSQL = "SELECT "
							. "idusers,usersCard, usersLastName,usersFirstName,usersMiddleName,usersBarcode,usersICQ,usersTG,credentialsUser,credentialsPassword,usersFinger,usersBday,"
							//. " *,"
							. "(SELECT `usersActiveState` FROM `usersActive` WHERE `usersActiveUser`=`idusers` ORDER BY `idusersActive` DESC LIMIT 1) AS `usersActiveState`,"
							. "(SELECT GROUP_CONCAT(`positionsName` SEPARATOR ', ') FROM `usersPositions` LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) WHERE `usersPositionsUser`= `idusers`)  AS `positions`"
							. " FROM `users` "
							. "LEFT JOIN `credentials` ON (`credentialsUser` = `idusers`)"
							. "LEFT JOIN  `usersPositions` ON (`usersPositionsUser` = `idusers`)"
							. "WHERE "
							. "isnull(`usersDeleted`) "
							. (!R(16) ? "AND `usersPositionsPosition` IN (32)" : "")
							. (isset($_GET['group']) ? ($_GET['group'] == 'none' ? ("AND isnull(`usersGroup`)") : ("AND `usersGroup`='" . FSI($_GET['group']) . "'")) : '')
							. (isset($_GET['position']) ? ("AND `usersPositionsPosition`='" . FSI($_GET['position']) . "'") : '')
							. (isset($_GET['right']) ? (" AND NOT isnull((SELECT usersRightsValue FROM `usersRights` WHERE idusersRights = (SELECT MAX(idusersRights) FROM `usersRights` WHERE `usersRightsUser` = `idusers` AND `usersRightsRule` = " . sqlVON($_GET['right'], true) . " )))") : '')
							. ((isset($_GET['uncomplete']) && ($_GET['uncomplete'] === 'any' || $_GET['uncomplete'] === 'finger')) ? ("AND isnull(`usersFinger`)") : '')
							. ((isset($_GET['uncomplete']) && ($_GET['uncomplete'] === 'any' || $_GET['uncomplete'] === 'icq')) ? ("AND isnull(`usersICQ`)") : '')
							. "GROUP BY `idusers`ORDER BY `usersLastName`";
//					print $personalSQL;
					$personal = mysqlQuery($personalSQL);
					while ($employee = mfa($personal)) {
						if (!$n) {
							//		printr($employee);
						}
						$n++;
						?>
						<tr>
							<td style="text-align: right;"><input type="checkbox" data-print="<?= $employee['idusers']; ?>" onclick="checkBoxes();" id="checkAll<?= $n; ?>"><label for="checkAll<?= $n; ?>"><?= $n; ?></label></td>

							<td style="text-align: center;"><a href="#" onclick="printBC('<?= $employee['usersBarcode']; ?>', '<?= $employee['usersLastName']; ?>', '<?= $employee['usersFirstName']; ?>')"><?= $employee['usersBarcode'] ? '<i class="fas fa-barcode"></i>' : ''; ?></a></td>
							<td style="text-align: center;"><?= $employee['usersFinger'] ? '<i class="fas fa-fingerprint"></i>' : ''; ?></td>
							<td style="text-align: center;"><?= ($employee['usersCard'] ?? false) ? ('<i class="far fa-id-badge" title="' . $employee['usersCard'] . '"></i>') : ''; ?></td>
							<td style="text-align: center;"><?= $employee['usersICQ'] ? '<img src="/css/images/icq.svg" style="width: 22px; height: 22px;">' : ''; ?></td>
							<td style="text-align: center;"><?= ($employee['usersTG'] ?? false) ? '<i class="fab fa-telegram-plane"></i>' : ''; ?></td>
							<?
							if (R(194)) {
								?>
								<td style="text-align: center;"><a target="_blank" href="/pages/payments/?user=<?= $employee['idusers']; ?>"><i class="fas fa-dollar-sign"></i></a></td>
								<?
							}
							?>


							<td style="text-align: center;"><?
								if ($employee['credentialsPassword']) {
									if ($employee['usersActiveState']) {
										?>
										<i class="fas fa-user"></i>
										<?
									} else {
										?><i class="fas fa-user-slash"></i><?
									}
								}
								?></td>

							<td><a target="_blank" href="/pages/personal/info.php?employee=<?= $employee['idusers']; ?>"><?= trim($employee['usersLastName'] . ' ' . $employee['usersFirstName'] . ' ' . $employee['usersMiddleName']) ?></a></td>
							<td><?= trim($employee['positions'] ?? '--'); ?></a></td>

							<? if (R(19)) { ?><td style="text-align: center;"><button style="color: red;" onclick="deleteEmployee({name: '<?= $employee['usersLastName']; ?> <?= $employee['usersFirstName']; ?>', id:<?= $employee['idusers']; ?>})">X</button></td><? } ?>

						</tr><?
					}
					?>
				</table>

			</div>
		</div>

		<?
	} else {
		?>E403R16||33<?
	}
	?>
	<?
}//END PAGES
?>


<script>


	function printSelected() {
		let printArr = [];
		for (let elem of qsa('input[data-print]')) {
			if (elem.checked) {
				printArr.push(+elem.dataset.print);
			}
		}
		console.log(printArr);
		window.open('/sync/plugins/badges.php?print=' + JSON.stringify(printArr), '', 'left=50,top=50,width=800,height=640,toolbar=1,scrollbars=1,status=1,menubar=1');
	}

	function checkBoxes() {
		let show = false;
		let allChecked = true;
		for (let elem of qsa('input[data-print]')) {
			if (elem.checked) {
				show = true;
			} else {
				allChecked = false;
			}
		}

		qs('#checkAll').checked = allChecked;
		qs('#printSelected').style.display = show ? '' : 'none';
	}

	let deleteEmployee = async function (employee) {
		let decision = await MSG({type: 'neutral', text: 'Удалить сотрудника<br>' + employee.name, options: [{text: ['Да', 'Конечно', 'Угу', 'Хорошо', 'Согласен', 'Да будет так'], value: true}, {text: ['Нет', 'Отказываюсь', 'Ни за что', 'Вот уж нет', 'Не', 'В другой раз'], value: false}]});
		if (decision === true) {
			fetch('personal_IO.php', {
				body: JSON.stringify({
					deleteEmployee: employee.id
				}),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);
					if ((jsn.msgs || []).length) {
						for (let msg of jsn.msgs) {
							let data = await MSG(msg);
							if (data) {
								window.location.reload();
							}
							console.log(data);
						}
					}
				} catch (e) {
					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
			});
		}
	};
	let addPersonal = function () {
		let FN = qs('#FN').value.trim();
		let LN = qs('#LN').value.trim();
		let MN = qs('#MN').value.trim();
		let position = qs('#PN').value.trim();
		let BC = qs('#barcode').value.trim();
		if (FN === '' || LN === '') {
			MSG('ВВЕДИТЕ ФАМИЛИЮ И ИМЯ');
			return false;
		}

		fetch('personal_IO.php', {
			body: JSON.stringify({
				FN: FN,
				LN: LN,
				MN: MN,
				position: position,
				BC: BC
			}),
			credentials: 'include',
			method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
		}).then(result => result.text()).then(async function (text) {
			try {
				let jsn = JSON.parse(text);
				if ((jsn.msgs || []).length) {
					for (let msg of jsn.msgs) {
						let data = await MSG(msg);
						if ((data || {}).employee) {
							window.location.href = '/pages/personal/info.php?employee=' + data.employee;
						}

						console.log(data);
					}
				}
			} catch (e) {
				MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
			}
		});
	};
	let genBC = function () {
		let bc = RDS(16, true);
		qs('#barcode').value = bc;
	};
	let printBC = function (bc, LN, FN) {
		if (!FN || !LN || !bc) {
			MSG({text: 'ВВЕДИТЕ ФАМИЛИЮ, ИМЯ И ШТРИХКОД'});
			return false;
		}
		window.open('/sync/plugins/barcodePrint.php?BC=' + bc + '&FN=' + FN + '&LN=' + LN, '', 'left=50,top=50,width=800,height=640,toolbar=0,scrollbars=1,status=0');
	};

</script>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
