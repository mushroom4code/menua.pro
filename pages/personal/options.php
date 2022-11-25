<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?>
<?
//printr($_POST);
//die();
if (R(120) && //указать правило
		isset($_POST['paymentType']) &&
		isset($_POST['grid']) &&
		!empty($_POST['gridType']) &&
		!empty($_POST['paymentDate']) &&
		isset($_GET['employee'])
) {
	mysqlQuery("DELETE FROM `LT` WHERE"
			. " `LTuser` = '" . mres($_GET['employee']) . "'"
			. " AND `LTdate` = '" . mres($_POST['paymentDate']) . "'"
			. " AND `LTid` = '" . mres($_POST['paymentType']) . "'"
			. "");
	foreach ($_POST['grid'] as $LTid => $grid) {
		foreach ($grid['from'] as $index => $null) {
			if (($grid['from'][$index] || $grid['to'][$index]) && $grid['value'][$index]) {
				mysqlQuery("INSERT INTO `LT` SET "
						. " `LTtype`='" . mres($_POST['gridType']) . "',"
						. " `LTid`='" . mres($LTid) . "',"
						. " `LTuser`='" . mres($_GET['employee']) . "',"
						. " `LTsetBy`='" . $_USER['id'] . "',"
						. " `LTdate`='" . mres($_POST['paymentDate']) . "',"
						. " `LTfrom`=" . sqlVON($grid['from'][$index], 1) . ","
						. " `LTto`=" . sqlVON($grid['to'][$index], 1) . ","
						. " `LTresult`=" . sqlVON($grid['value'][$index], 1) . ""
						. "");
			}
		}
	}

	header("Location: " . GR());
	die();
}

/* {
  "position": "",
  "paymentType": 25,
  "grid": {
  "25": {
  "from": [
  "",
  1000000,
  1500000,
  2500000
  ],
  "to": [
  1000000,
  1500000,
  2500000,
  ""
  ],
  "value": [
  0.03,
  0.04,
  0.05,
  0.06
  ]
  }
  },
  "paymentDate": "2021-08-16"
  } */




if (R(120) && //указать правило
		isset($_POST['paymentType']) &&
		isset($_POST['paymentValue']) &&
		!empty($_POST['paymentDate']) &&
		isset($_GET['employee'])
) {


	mysqlQuery("INSERT INTO `userPaymentsValues` SET "
			. "`userPaymentsValuesType`='" . mres($_POST['paymentType']) . "',"
			. "`userPaymentsValuesUser`='" . mres($_GET['employee']) . "',"
			. "`userPaymentsValuesDate`='" . mres($_POST['paymentDate']) . "',"
			. "`userPaymentsValuesValue`=" . ((floatval($_POST['paymentValue']) != 0) ? mres($_POST['paymentValue']) : 'null' ) . ", "
			. "`userPaymentsValuesSetBy`='" . $_USER['id'] . "' "
	);
	header("Location: " . GR());
	die();

	/**/
}

if (R(120) && //указать правило
		( $_POST['action'] ?? false) == 'loadWageTemplate' &&
		( $_POST['templateLoad'] ?? false) &&
		validateDate($_POST['templateDate'])
) {
	/*
	  "action": "loadWageTemplate",
	  "templateLoad": 2,
	  "templateDate": "2021-05-31"
	 */
	foreach (query2array(mysqlQuery("SELECT * FROM `userPaymentsTemplatesValues` WHERE `userPaymentsTemplatesValuesTemplate`='" . mres($_POST['templateLoad']) . "'")) as $templateValue) {

		mysqlQuery("INSERT INTO `userPaymentsValues`"
				. " SET `userPaymentsValuesType`='" . $templateValue['userPaymentsTemplatesValuesType'] . "',"
				. "  `userPaymentsValuesUser`='" . mres($_GET['employee']) . "',"
				. "  `userPaymentsValuesDate`='" . mres($_POST['templateDate']) . "',"
				. "  `userPaymentsValuesValue`='" . $templateValue['userPaymentsTemplatesValuesValue'] . "',"
				. "  `userPaymentsValuesSetBy`='" . $_USER['id'] . "'"
				. "");
	}
	header("Location: " . GR());
	die();
//	printr($_POST);
}
if (R(120) && //указать правило
		( $_POST['action'] ?? false) == 'saveWageTemplate'
) {
	if (($_POST['newTemplateName'] ?? '') !== '') {
		$userPaymentsValues = query2array(mysqlQuery("SELECT * FROM `userPaymentsValues` WHERE `userPaymentsValuesUser` = '" . mres($_GET['employee']) . "' AND `userPaymentsValuesDate`<=CURDATE()"));

		usort($userPaymentsValues, function ($b, $a) {
			if ($b['userPaymentsValuesDate'] <=> $a['userPaymentsValuesDate']) {
				return $b['userPaymentsValuesDate'] <=> $a['userPaymentsValuesDate'];
			}
			return $b['iduserPaymentsValues'] <=> $a['iduserPaymentsValues'];
		});
		$values = [];
		foreach ($userPaymentsValues as $userPaymentsValue) {
			$values[$userPaymentsValue['userPaymentsValuesType']] = $userPaymentsValue['userPaymentsValuesValue'];
		}
		$values = array_filter($values, function ($value) {
			return $value !== null;
		});

		mysqlQuery("INSERT INTO `userPaymentsTemplates` SET `userPaymentsTemplatesName` = '" . mres($_POST['newTemplateName']) . "'");
		$iduserPaymentsTemplates = mysqli_insert_id($link);
		foreach ($values as $idtype => $value) {
			mysqlQuery("INSERT INTO `userPaymentsTemplatesValues`"
					. " SET `userPaymentsTemplatesValuesTemplate`= '$iduserPaymentsTemplates',"
					. " `userPaymentsTemplatesValuesType` ='" . $idtype . "',"
					. " `userPaymentsTemplatesValuesValue`= '$value'");
		}
	}




	header("Location: " . GR());
	die();
}



include 'includes/top.php';
?>
<style>
	.grid{
		display: grid;
		grid-template-columns: repeat(4, auto);
		grid-gap: 3px;
	}
	.hidden {
		display: none;
	}
</style>
<?
if (!R(120)) {
	?>
	<div class="box-body" style="background-color: white; margin: 5px; min-height: 400px;">
		E401R120
	</div>
	<?
} else {
	?>

	<div class="box-body" style="background-color: white; margin: 5px; min-height: 400px;">
		<?
		$userPaymentsValues = query2array(mysqlQuery("SELECT * FROM `userPaymentsValues` WHERE `userPaymentsValuesUser` = '" . mres($_GET['employee']) . "' AND `userPaymentsValuesDate`<=CURDATE()"));
//	printr($userPaymentsValues);
		?>
		<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto auto auto;">
			<div style="display: contents;">
				<div class="B C">#</div>
				<div class="B C">id</div>
				<div class="B C">Тип премирования</div>
				<div class="B C">Значение</div>
				<div class="B C">Действует с</div>
				<div></div>
			</div>
			<?
			$n = 0;
			$paymentTypes = query2array(mysqlQuery("SELECT * FROM `userPaymentsTypes` WHERE isnull(`userPaymentsTypesDeleted`)  ORDER BY `userPaymentsTypesSort`,`userPaymentsTypesName`"));
			foreach ($paymentTypes as $paymentType) {
//			printr($paymentType);
				$userPaymentValues = array_filter($userPaymentsValues, function ($element)use ($paymentType) {
					return $element['userPaymentsValuesType'] == $paymentType['iduserPaymentsTypes'];
				});

				usort($userPaymentValues, function ($a, $b) {
					if ($b['userPaymentsValuesDate'] <=> $a['userPaymentsValuesDate']) {
						return $b['userPaymentsValuesDate'] <=> $a['userPaymentsValuesDate'];
					}
					return $b['iduserPaymentsValues'] <=> $a['iduserPaymentsValues'];
				});
				$n++;
				?>
				<form style="display: contents;" method="post" action="<?= GR(); ?>" onsubmit="if (this.paymentDate.value == '') {
							MSG('Укажите дату');
							return false;
							void(0);
						}
						if (this.gridType && this.gridType.value == '') {
							MSG('Укажите тип сетки');
							return false;
							void(0);
						}
					  ">
					<input type="hidden" name="position" value="">
					<div style="display: flex; align-items: center;"><input type="hidden" name="paymentType" value="<?= $paymentType['iduserPaymentsTypes']; ?>"> <?= $n; ?></div>
					<div><?= $paymentType['iduserPaymentsTypes']; ?>]</div>
					<div style="display: flex; align-items: center;">
						<div>
							<a href="<?= GR2(['paymentType' => (($_GET['paymentType'] ?? null) == $paymentType['iduserPaymentsTypes']) ? null : $paymentType['iduserPaymentsTypes']]); ?>"><?= $paymentType['userPaymentsTypesName']; ?></a>
							<br><table border="1" style=" border-collapse: collapse;">
								<?
								if (($_GET['paymentType'] ?? null) == $paymentType['iduserPaymentsTypes']) {
									$paymentsValues = query2array(mysqlQuery("SELECT *"
													. " FROM `userPaymentsValues`"
													. " LEFT JOIN `users` ON (`idusers` = `userPaymentsValuesSetBy`)"
													. " WHERE `userPaymentsValuesUser` = " . mres($_GET['employee']) . " AND `userPaymentsValuesType` = " . $paymentType['iduserPaymentsTypes'] . " ORDER BY `userPaymentsValuesDate`,`iduserPaymentsValues`"));
									foreach ($paymentsValues as $paymentsValuesIndex => $paymentsValue) {
										if (($paymentsValues[$paymentsValuesIndex - 1] ?? false) && $paymentsValues[$paymentsValuesIndex - 1]['userPaymentsValuesDate'] == $paymentsValues[$paymentsValuesIndex]['userPaymentsValuesDate']) {
											$paymentsValues[$paymentsValuesIndex - 1]['invalid'] = true;
										}
									}
//									printr($paymentsValues);

									foreach ($paymentsValues as $paymentsValue) {
										$style = ($paymentsValue['invalid'] ?? false) ? ' style="color: silver; text-decoration:  line-through;"' : '';
										?>
										<tr>
											<td><span<?= $style; ?>><?= date("d.m.Y", strtotime($paymentsValue['userPaymentsValuesDate'])); ?></span></td>
											<td><span<?= $style; ?>><?= $paymentsValue['userPaymentsValuesValue']; ?></span></td>
											<td><div style="display: flex; align-items:  center;"><span<?= $style; ?>><?= $paymentsValue['usersLastName']; ?>&nbsp;<?= date("d.m", strtotime($paymentsValue['userPaymentsValuesSetTime'])); ?> </span> <?= $style ? '' : '<img src="signature-solid.svg" style=" height: 16px;">' ?></div></td>
										</tr>
										<?
									}
								}
								?>
							</table>
						</div>
					</div>
					<?
					$grids = [];
					if ($paymentType['userPaymentsTypesType'] == 'grid') {
						$LTdata = query2array(mysqlQuery("SELECT * FROM `LT` WHERE `LTuser`='" . mres($_GET['employee']) . "' AND `LTid`='" . $paymentType['iduserPaymentsTypes'] . "' AND `LTdate` = (SELECT MAX(`LTdate`) FROM `LT`  WHERE `LTuser`='" . mres($_GET['employee']) . "' AND `LTid`='" . $paymentType['iduserPaymentsTypes'] . "' AND `LTdate`<=CURDATE())"));
//						printr($LTdata);
						$ltgrids = [];
						foreach ($LTdata as $LTdataRow) {
							$ltgrids[$LTdataRow['LTdate']]['type'] = $LTdataRow['LTtype'] ?? '-';
							$ltgrids[$LTdataRow['LTdate']]['date'] = $LTdataRow['LTdate'] ?? '';
							$ltgrids[$LTdataRow['LTdate']]['data'][] = [
								'from' => $LTdataRow['LTfrom'],
								'to' => $LTdataRow['LTto'],
								'result' => $LTdataRow['LTresult'],
							];
						}
						krsort($ltgrids);
						$grids = array_values($ltgrids);
//						printr($grids);
						?>
						<div class="C"><span style="cursor: pointer;" onclick="document.querySelector(`#grid<?= $paymentType['iduserPaymentsTypes']; ?>`).classList.toggle('hidden');">Сетка</span>

							<div class="hidden grid" id="grid<?= $paymentType['iduserPaymentsTypes']; ?>">
								<div style="grid-column: span 4;">
									<select name="gridType">
										<option value="">Тип сетки</option>
										<option value="F" <?= ($grids[0]['type'] ?? '') == 'F' ? ' selected' : ''; ?>>Тип F</option>
										<option value="Z" <?= ($grids[0]['type'] ?? '') == 'Z' ? ' selected' : ''; ?>>Тип Z</option>
									</select>

								</div>
								<div style="display: contents;">
									<div>От</div>
									<div>До</div>
									<div>Знач.</div>
									<div></div>
								</div>
								<?
								foreach (($grids[0]['data'] ?? []) as $gridrow) {
//										printr($gridrow);
									?>
									<div style="display: contents;">
										<div><input autocomplete="off" style="border-radius: 2px; width: 70px; text-align: center;" value="<?= $gridrow['from']; ?>" type="text" name="grid[<?= $paymentType['iduserPaymentsTypes']; ?>][from][]" oninput="digon();"></div>
										<div><input autocomplete="off" style="border-radius: 2px; width: 70px; text-align: center;" value="<?= $gridrow['to']; ?>"  type="text" name="grid[<?= $paymentType['iduserPaymentsTypes']; ?>][to][]"  oninput="digon();"></div>
										<div><input autocomplete="off" style="border-radius: 2px; width: 50px; text-align: center;" value="<?= $gridrow['result']; ?>"  type="text" name="grid[<?= $paymentType['iduserPaymentsTypes']; ?>][value][]"  oninput="digon();"></div>
										<div><input type="button" value="+" onclick="addGridRow('<?= $paymentType['iduserPaymentsTypes']; ?>');"></div>
									</div>
									<?
								}
								if (!($grids[0] ?? false)) {
									?>
									<div style="display: contents;">
										<div><input style="border-radius: 2px; width: 70px; text-align: center;" type="text" name="grid[<?= $paymentType['iduserPaymentsTypes']; ?>][from][]" oninput="digon();"></div>
										<div><input style="border-radius: 2px; width: 70px; text-align: center;"  type="text" name="grid[<?= $paymentType['iduserPaymentsTypes']; ?>][to][]"  oninput="digon();"></div>
										<div><input style="border-radius: 2px; width: 50px; text-align: center;"  type="text" name="grid[<?= $paymentType['iduserPaymentsTypes']; ?>][value][]"  oninput="digon();"></div>
										<div><input type="button" value="+" onclick="addGridRow('<?= $paymentType['iduserPaymentsTypes']; ?>');"></div>
									</div>
									<?
								}
								?>


							</div>
						</div>
					<? } ?>
					<? if ($paymentType['userPaymentsTypesType'] == 'num') { ?>
						<div><input type="text" name="paymentValue" oninput="digon();" value="<?= $userPaymentValues[0]['userPaymentsValuesValue'] ?? ''; ?>"></div>
					<? } ?>
					<? if ($paymentType['userPaymentsTypesType'] == 'cb') { ?>
						<div style="text-align: center;">
							<? $userPaymentValues[0]['userPaymentsValuesValue'] ?? ''; ?>
							<input type="hidden" name="paymentValue" value="">
							<input type="checkbox" <?= ($userPaymentValues[0]['userPaymentsValuesValue'] ?? '') == 1 ? 'checked' : ''; ?>  id="opt<?= $paymentType['iduserPaymentsTypes']; ?>" name="paymentValue" oninput="digon();" value="1">
							<label for="opt<?= $paymentType['iduserPaymentsTypes']; ?>"></label>
						</div>
					<? } ?>


					<div><input type="date" name="paymentDate" value="<?= ($grids[0]['date']) ?? $userPaymentValues[0]['userPaymentsValuesDate'] ?? ''; ?>"></div>
					<div><input type="submit" value="Ok"></div>
				</form>
			<? } ?>

		</div>
		<? if (1) { ?>
			<div style="text-align: center;">
				<div style="display: inline-block; padding: 20px; text-align: left;">
					<div style="display: grid; grid-template-columns: repeat(3,auto); grid-gap: 10px;">
						<form style="display: contents;" method="POST" action="<?= GR(); ?>">
							<input type="hidden" name="action" value="loadWageTemplate">
							<select name="templateLoad"><option value="">Выбрать шаблон ЗП</option>
								<?
								foreach (query2array(mysqlQuery("SELECT * FROM `userPaymentsTemplates`")) as $template) {
									?><option value="<?= $template['iduserPaymentsTemplates']; ?>"><?= $template['iduserPaymentsTemplates']; ?>] <?= $template['userPaymentsTemplatesName']; ?></option><?
								}
								?></select>
							<input type="date" name="templateDate">
							<input type="submit" value="Применить шаблон ЗП">
						</form>
						<form style="display: contents;" method="POST" action="<?= GR(); ?>">
							<input type="hidden" name="action" value="saveWageTemplate">
							<input style="grid-column: span 2;" type="text" name="newTemplateName" placeholder="Назовите шаблон">
							<input type="submit" value="Сохранить как шаблон">
						</form>
					</div>
				</div>
			</div>
		<? } ?>

	</div>
	<script>
		function addGridRow(grid) {
			let table = document.querySelector(`#grid${grid}`);
			let div = el('div', {innerHTML: `<div><input type="text" style="border-radius: 2px; width: 70px; text-align: center;" name="grid[${grid}][from][]" oninput="digon();"></div>
										<div><input style="border-radius: 2px; width: 70px; text-align: center;" type="text" name="grid[${grid}][to][]"  oninput="digon();"></div>
										<div><input style="border-radius: 2px; width: 50px; text-align: center;" type="text" name="grid[${grid}][value][]"  oninput="digon();"></div>
										<div><input type="button" value="+" onclick="addGridRow('${grid}');"></div>`});
			div.style.display = 'contents';
			table.appendChild(div);
		}
		//10 утра, каб 110, консультация 2100, паспорт, снилс.
	</script>
<? } ?>

<? include 'includes/bottom.php'; ?>