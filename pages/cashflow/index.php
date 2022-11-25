<?php
$load['title'] = $pageTitle = 'Касса';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(146)) {
	if (!empty($_POST['summ'])) {
		if (mysqlQuery("INSERT INTO `cashFlow` SET "
						. "`cashFlowSumm`='" . FSS($_POST['summ']) . "'"
						. ", `cashFlowComment`='" . FSS($_POST['comment']) . "'"
						. (($_POST['date']) != date("Y-m-d") ? ", `cashFlowDate`='" . FSS($_POST['date']) . " 12:00:00'" : '')
						. (!empty($_POST['cashFlowType']) ? ", `cashFlowType`='" . FSI($_POST['cashFlowType']) . "'" : ''))) {
			header("Location: /pages/cashflow/");
			die();
		} else {
			die(mysqli_error($link));
		}
	}

	if (!empty($_GET['deleteCFentry'])) {
		$delete = $_GET['deleteCFentry'];
		unset($_GET['deleteCFentry']);
		if (
//				mysqlQuery("DELETE FROM `cashFlow` WHERE `idcashFlow`='" . FSI($delete) . "'")
				mysqlQuery("UPDATE `cashFlow` SET `cashFlowDeleted` = NOW() WHERE `idcashFlow`='" . FSI($delete) . "'")
		) {
			header("Location: /pages/cashflow/index.php?" . http_build_query($_GET));
			die();
		} else {
			die(mysqli_error($link));
		}
	}
	$_GET['cft'] = $_GET['cft'] ?? 'all';
	$_GET['dateFrom'] = $_GET['dateFrom'] ?? date("Y-m-d", time() - 60 * 60 * 24 * 1);
	$_GET['dateTo'] = $_GET['dateTo'] ?? date("Y-m-d", time());
	$cashFlowTypesArray = query2array(mysqlQuery("SELECT * FROM `cashFlowTypes`"));
	usort($cashFlowTypesArray, function ($a, $b) {
		return mb_strtolower($a['cashFlowTypeName']) <=> mb_strtolower($b['cashFlowTypeName']);
	});

	$CFTquery = "SELECT * FROM `cashFlow` LEFT JOIN `cashFlowTypes` ON (`idcashFlowType` = `cashFlowType`) WHERE  isnull(`cashFlowDeleted`) AND "
			. " `cashFlowDate`>= '" . $_GET['dateFrom'] . " 00:00:00' AND "
			. " `cashFlowDate`<= '" . $_GET['dateTo'] . " 23:59:59'"
			. (isset($_GET['comment']) ? " AND `cashFlowComment` LIKE '%" . mysqli_real_escape_string($link, $_GET['comment']) . "%'" : "")
			. (isset($_GET['income']) ? " AND `cashFlowSumm` = '" . mysqli_real_escape_string($link, $_GET['income']) . "'" : "")
			. (isset($_GET['outlay']) ? " AND `cashFlowSumm` = '" . mysqli_real_escape_string($link, $_GET['outlay']) . "'" : "")
			. (($_GET['cft'] === 'all' ? '' : ($_GET['cft'] === 'null' ? " AND isnull(`cashFlowType`)" : " AND `cashFlowType` = " . FSI($_GET['cft']))));

	$cashFlow = query2array(mysqlQuery($CFTquery));

	if (isset($_GET['save'])) {

		function exportCSV($rows = false) {
			if (!empty($rows)) {
				$name = 'kassa_' . date("Ymd", strtotime($_GET['dateFrom'])) . '-' . date("Ymd", strtotime($_GET['dateTo'])) . ".csv";
				header('Content-Type: text/csv; charset=utf-8');
				header('Content-Disposition: attachment; filename=' . $name);
				$output = fopen('php://output', 'w');
				fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
				foreach ($rows as $idrow => $row) {
					if (is_array($row)) {
						foreach ($row as $idcolumn => $column) {
							$row[$idcolumn] = strip_tags($row[$idcolumn]);
						}
					}
					if (!is_array($row)) {
						$row = [$row];
					}
					fputcsv($output, $row, ';');
				}
				exit();
			}
			return false;
		}

		$table = [];
		foreach ($cashFlow as $cashFlowEntry) {
			$table[] = [
				date("d.m.Y", strtotime($cashFlowEntry['cashFlowDate'])),
				$cashFlowEntry['cashFlowSumm'],
				$cashFlowEntry['cashFlowComment'],
				$cashFlowEntry['cashFlowTypeName'],
			];
		}
		exportCSV($table);
		printr($table);
		printr($cashFlow);
	}
}





include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(146)) {
	?>E403R146<?
} else {
	?>
	<style>
		.copyTable{
			border-top: 1px solid black;
			border-left: 1px solid black;
			border-collapse: collapse;
		}
		.copyTable td{
			border-bottom: 1px solid black;
			border-right: 1px solid black;
		}


	</style>
	<ul class="horisontalMenu">
		<li><a href="?">Внесение</a></li>
		<li><a href="?view">Просмотр</a></li>
		<li><a href="?setup"><i style="font-size: 0.8em;" class="fas fa-cogs"></i></a></li>
		<li><a><?= nf(mfa(mysqlQuery("SELECT SUM(`cashFlowSumm`) AS `cashFlowSumm` FROM `cashFlow` WHERE isnull(`cashFlowDeleted`)"))['cashFlowSumm']); ?></a></li>
		<? if (isset($_GET['view'])) { ?><li><a href="<?= GR2(['save' => true]); ?>">Экспорт</a></li><? } ?>
	</ul>

	<div class="divider"></div>

	<? ?>


	<?
	if (isset($_GET['view'])) {
		?>

		<div style="display: grid; grid-template-columns: auto auto auto auto auto; grid-gap: 10px;">

			<input type="text" style="width: 100px;" placeholder="Приход" id="income" value="<?= urldecode($_GET['income'] ?? ''); ?>">
			<input type="text" style="width: 100px;" placeholder="Расход" id="outlay" value="<?= urldecode($_GET['outlay'] ?? ''); ?>">
			<input type="text" style="width: 150px;" placeholder="Назначение" id="comment" value="<?= urldecode($_GET['comment'] ?? ''); ?>">
			<select name = "cashFlowType"  id="cft">
				<option<?= (($_GET['cft'] == 'all') ? ' selected' : ''); ?>  value = "all">Всё ДДС</option>
				<option<?= (($_GET['cft'] == 'null') ? ' selected' : ''); ?> value = "null">Без ДДС</option>
				<? foreach ($cashFlowTypesArray as $type) { ?>
					<option<?= (($_GET['cft'] == $type['idcashFlowType']) ? ' selected' : ''); ?> value="<?= $type['idcashFlowType'] ?>"><?= $type['cashFlowTypeName'] ?></option>
				<? } ?>
			</select>

			<input type="button" value="ok" onclick="GR({
						income: qs('#income').value,
						outlay: qs('#outlay').value,
						comment: qs('#comment').value,
						cft: qs('#cft').value

					});">
		</div>
		<? ?>
		<div style="display: grid; grid-gap: 10px; grid-template-columns: auto auto; margin-top: 10px;">
			<input type="date" name="dateFrom" onchange="GETreloc('dateFrom', this.value);"<?= !empty($_GET['dateFrom']) ? ' value="' . $_GET['dateFrom'] . '"' : ''; ?>>
			<input type="date" name="dateTo" onchange="GETreloc('dateTo', this.value);"<?= !empty($_GET['dateTo']) ? ' value="' . $_GET['dateTo'] . '"' : ''; ?>>
		</div>

		<div style="text-align: center;">

			<div class="box neutral">
				<!--<br>-->
				<div class="box-body">
					<?
					$startSumm = mfa(mysqlQuery("SELECT SUM(`cashFlowSumm`) AS `cashFlowSumm` FROM `cashFlow` WHERE `cashFlowDate`< '" . $_GET['dateFrom'] . " 00:00:00' AND  isnull(`cashFlowDeleted`) "
											. (($_GET['cft'] === 'all' ? '' : ($_GET['cft'] === 'null' ? " AND isnull(`cashFlowType`)" : " AND `cashFlowType` = " . FSI($_GET['cft']))))
									)
							)['cashFlowSumm'];
					?>
					<script>

						let deleteCFentry = async (entryid) => {
							let response = await MSG({type: 'error', text: ['Удалить??'], options: [{text: ['Нет'], value: false}, {text: ['Да'], value: true}]});
							if (response) {
								GETreloc('deleteCFentry', entryid);
							}
						};
					</script>
					<table style="border-collapse: collapse;">
						 <!--style="display: grid; grid-gap: 0px 0px; grid-template-columns: auto auto <? if ($_GET['cft'] == 'all') { ?> auto <? } ?> auto"-->
						<tr style="font-weight: bold;">
							<td style="text-align: center; padding: 6px;">Приход</td>
							<td style="text-align: center; padding: 6px;">Расход</td>
							<td style="text-align: center; padding: 6px;">Назначение</td>
							<? if (!isset($_GET['cft']) || $_GET['cft'] == 'all') { ?> <td style="text-align: center; padding: 6px;"><a href="<?= GR('byDDS', isset($_GET['byDDS']) ? null : true); ?>">ДДС</a></td> <? } ?>
							<!--						<div>Итог</div>-->
							<!--<div></div>-->
						</tr>

						<?
						$runningTotal = $startSumm ?? 0;
						$date = null;
						$print = [];
						usort($cashFlow, function ($a, $b) {
							if (isset($_GET['byDDS'])) {
								$adate = date("Ymd", strtotime($a['cashFlowDate']));
								$bdate = date("Ymd", strtotime($b['cashFlowDate']));
								if ($adate == $bdate) {
									return $a['cashFlowType'] <=> $b['cashFlowType'];
								} else {
									return $adate <=> $bdate;
								}
							} else {
								return $a['cashFlowDate'] <=> $b['cashFlowDate'];
							}
						});

						$add = 0;
						$sub = 0;
						foreach ($cashFlow as $cashFlowRow) {

							$print[date("d.m.Y", strtotime($cashFlowRow['cashFlowDate']))][] = $cashFlowRow;

							if ($date != date("d/m/Y", strtotime($cashFlowRow['cashFlowDate']))) {

								if ($date) {
									?>
									<tr>
										<td style=" padding: 0px 10px; "><?= ($add > 0 ? '+' : '') . nf($add); ?></td>
										<td style=" padding: 0px 10px; "><?= nf($sub); ?></td>
										<? if ($_GET['cft'] == 'all') { ?>
											<td style=""></td>
										<? } ?>
										<td style=""></td>
										<!--<div style=""></div>-->
										<!--<div style=""></div>-->
									</tr>
									<?
									$add = 0;
									$sub = 0;
								}
								?>





								<? $date = date("d/m/Y", strtotime($cashFlowRow['cashFlowDate'])); ?>


								<tr>
									<td style="background-color: black; color: white;"></td>
									<td style="background-color: black; color: white;"></td>
									<td style="background-color: black; color: white;" class="text-dark"><b><?= $date; ?></b> &nbsp; &nbsp; &nbsp; <?= nf($runningTotal); ?></td>
									<? if ($_GET['cft'] == 'all') { ?>
										<td style="background-color: black; color: white;"></td>
									<? } ?>
									<!--<div style="align-self: center;"></div>-->
								</tr>

								<?
							}
							if ($cashFlowRow['cashFlowSumm'] > 0) {
								$add += $cashFlowRow['cashFlowSumm'];
							} else {
								$sub += $cashFlowRow['cashFlowSumm'];
							}
							$runningTotal += $cashFlowRow['cashFlowSumm'];
							?>


							<tr style="<?
							if ($cashFlowRow['cashFlowTypesColor']) {
								print 'background-color: ' . $cashFlowRow['cashFlowTypesColor'] . ';';
							}
							?>" oncontextmenu="editField({id:<?= $cashFlowRow['idcashFlow']; ?>,cft:<?= $cashFlowRow['idcashFlowType'] ?? 'null'; ?>,summ:<?= $cashFlowRow['cashFlowSumm'] ?? 0; ?>,comment: '<?= FSS($cashFlowRow['cashFlowComment']); ?>', date:'<?= date("Y-m-d", strtotime($cashFlowRow['cashFlowDate'])); ?>'});void(0);return false;">
								<td style="padding: 0px 10px; align-items: center; background-color: inherit;"><?= $cashFlowRow['cashFlowSumm'] > 0 ? nf($cashFlowRow['cashFlowSumm']) : ''; ?></td>
								<td style="padding: 0px 10px; align-items: center; background-color: inherit;"><?= $cashFlowRow['cashFlowSumm'] < 0 ? nf($cashFlowRow['cashFlowSumm']) : ''; ?></td>




								<td style="padding: 0px 10px; text-align: left; background-color: inherit;"><?= $cashFlowRow['cashFlowComment']; ?></td>
								<? if ($_GET['cft'] == 'all') { ?>
									<td style="padding: 0px 10px; align-items: center; background-color: inherit;"><?= $cashFlowRow['cashFlowTypeName']; ?></td>
								<? } ?>
			<!--<div style="padding: 0px 10px; align-items: center; display: flex; text-align: right; background-color: inherit;"><?= nf($runningTotal); ?></div>-->
			<!--							<div style="padding: 0px 10px; align-items: center; display: flex; background-color: inherit;"><button style=" color: red;" onclick="deleteCFentry(<?= $cashFlowRow['idcashFlow']; ?>);">X</button></div>-->
							</tr>
							<?
						}
						?>
						<tr>
							<td style=" padding: 0px 10px; "><?= ($add > 0 ? '+' : '') . nf($add); ?></td>
							<td style=" padding: 0px 10px; "><?= nf($sub); ?></td>
							<? if ($_GET['cft'] == 'all') { ?>
								<td style=""></td>
							<? } ?>
							<td style=""></td>
		<!--							<td style=""></td>
							<td style=""></td>-->
						</tr>
					</table>
					<div style="text-align: left;">

					</div>

					<script>
						function selectElementContents(el) {
							var body = document.body, range, sel;
							if (document.createRange && window.getSelection) {
								range = document.createRange();
								sel = window.getSelection();
								sel.removeAllRanges();
								try {
									range.selectNodeContents(el);
									sel.addRange(range);
								} catch (e) {
									range.selectNode(el);
									sel.addRange(range);
								}
							} else if (body.createTextRange) {
								range = body.createTextRange();
								range.moveToElementText(el);
								range.select();
							}
						}
					</script>

					<br>
					<br>
					<br>
					<?
					$startSummPrint = $startSumm;
					foreach ($print as $date => $day) {
						?>
						<div style="background-color: #fafafa; padding: 10px;" onclick="selectElementContents(this);">
							<table class="copyTable" style="background-color: white; width: 100%;  border: none; box-shadow: 0px 0px 10px hsla(0,0%,0%,0.4);" border="1">
								<thead>
									<tr>
										<td style="text-align: right; border: none;"></td>
										<td style="font-weight: bolder;border: none;">Касса за <?= $date; ?></td>
										<td style="font-weight: bolder;border: none;"><?= number_format($startSummPrint, 2, ',', ' '); ?></td>
										<td style="border: none;"></td>
									</tr>
									<tr>
										<td style="border: none;"></td>
										<td style="font-weight: bolder;">Наименование</td>
										<td style="font-weight: bolder;">Приход</td>
										<td style="font-weight: bolder;">Расход</td>
									</tr>
								</thead>
								<tbody>
									<?
									$in = 0;
									$out = 0;
									foreach ($day as $entry) {
										if ($entry['cashFlowSumm'] > 0) {
											$in += $entry['cashFlowSumm'];
										} else {
											$out += $entry['cashFlowSumm'];
										}
										$startSummPrint += $entry['cashFlowSumm'] ?? 0;
										?>
										<tr>
											<td style="text-align: right; border: none;"><?= $entry['cashFlowTypeName']; ?></td>
											<td style="text-align: left;"><?= $entry['cashFlowComment']; ?></td>
											<td style="text-align: right;"><?= $entry['cashFlowSumm'] > 0 ? number_format($entry['cashFlowSumm'], 2, ',', ' ') : ''; ?></td>
											<td style="text-align: right;"><?= $entry['cashFlowSumm'] < 0 ? number_format(abs($entry['cashFlowSumm']), 2, ',', ' ') : ''; ?></td>
										</tr>

									<? } ?>
								</tbody>
								<tfoot>
									<tr>
										<td style="text-align: right; border: none;"></td>
										<td style="text-align: right; border: none;">Итого:</td>
										<td style="text-align: right;"><?= number_format(abs($in), 2, ',', ' '); ?></td>
										<td style="text-align: right;"><?= number_format(abs($out), 2, ',', ' '); ?></td>
									</tr>
									<tr>
										<td style="text-align: right; border: none;"></td>
										<td style="text-align: right; border: none;">Остаток на конец дня:</td>
										<td style="text-align: right; border: none;"><?= number_format($startSummPrint, 2, ',', ' '); ?></td>
										<td style="text-align: right; border: none;"></td>
									</tr>
								</tfoot>
							</table>
						</div>
						<?
					}

//					printr($print);
					?>



				</div>
			</div>

		</div>

		<?
	} elseif (isset($_GET['setup'])) {
		$cfTypes = query2array(mysqlQuery("SELECT *, (SELECT COUNT(*) FROM `cashFlow` WHERE `cashFlowType` = `idcashFlowType` AND  isnull(`cashFlowDeleted`)) AS `qty` FROM `cashFlowTypes`"));
		uasort($cfTypes, function ($a, $b) {
			return mb_strtolower($a['cashFlowTypeName']) <=> mb_strtolower($b['cashFlowTypeName']);
		});
		?>
		<div class="box neutral">
			<div class="box-body">
				<div style="display: inline-block;">
					<div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;"><?
						foreach ($cfTypes as $cfType) {
							?>
							<input type="color" value="<?= $cfType['cashFlowTypesColor'] ?? '#ffffff'; ?>" onchange="fetch('IO.php', {
										body: JSON.stringify({action: 'cfTypeColor', id:<?= $cfType['idcashFlowType']; ?>, cfTypeColor: this.value}),
										credentials: 'include',
										method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
									})">
							<div oncontextmenu="editCFT({cft:<?= $cfType['idcashFlowType'] ?? 'null'; ?>,cftName:'<?= FSS($cfType['cashFlowTypeName']); ?>'});void(0);return false;"><?= $cfType['cashFlowTypeName']; ?> <i style="color: gray;">(<?= human_plural_form($cfType['qty'], ['запись', 'записи', 'записей'], true); ?>)</i></div>

						<? }
						?>
						<div></div><div style="padding: 10px; cursor: pointer;" onclick="editCFT({cft: 'new', cftName: ''});void(0);return false;">Добавить</div>
					</div>
				</div>
			</div>
		</div>
		<?
	} else {
		?>
		<form action="/pages/cashflow/" method="post">
			<div style="padding: 5px; display: grid; grid-gap: 10px;">
				<div><input type="number" name="summ" placeholder="Сумма" required></div>
				<div><input type="text"  name="comment" placeholder="Назначение" required></div>

				<div><select name="cashFlowType"><option value="">ДДС</option><?
						foreach ($cashFlowTypesArray as $type) {
							?><option value="<?= $type['idcashFlowType'] ?>"><?= $type['cashFlowTypeName'] ?></option><? } ?></select></div>
				<div><input type="date"  name="date" value="<?= date("Y-m-d"); ?>"></div>
				<div style="text-align: center;"><input type="submit" value="Сохранить"></div>
			</div>
		</form>
	<? } ?>









<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';

