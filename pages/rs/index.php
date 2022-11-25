<?php
$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (in_array($_USER['id'], [176, 199])) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (in_array($_USER['id'], [176, 199])) {
//	printr($_POST);
	?>
	<?
	$cashFlowTypesArray = query2array(mysqlQuery("SELECT * FROM `cashFlowTypes`"));
	usort($cashFlowTypesArray, function($a, $b) {
		return mb_strtolower($a['cashFlowTypeName']) <=> mb_strtolower($b['cashFlowTypeName']);
	});
	?>
	<div class="box neutral">
		<div class="box-body">
			<i class="fas fa-file-upload" style="padding: 10px; cursor: pointer;" onclick="fileUpload();"></i>
			<?
			if (1 || isset($_GET['view'])) {
				$_GET['cft'] = $_GET['cft'] ?? 'all';
				?>
				<div>
					<select name = "cashFlowType" onchange="GETreloc('cft', this.value);">
						<option<?= (($_GET['cft'] == 'all') ? ' selected' : ''); ?>  value = "all">Всё</option>
						<option<?= (($_GET['cft'] == 'null') ? ' selected' : ''); ?> value = "null">Без ДДС</option>
						<? foreach ($cashFlowTypesArray as $type) { ?>
							<option<?= (($_GET['cft'] == $type['idcashFlowType']) ? ' selected' : ''); ?> value="<?= $type['idcashFlowType'] ?>"><?= $type['cashFlowTypeName'] ?></option>
						<? } ?>
					</select>
				</div>
				<?
				$_GET['dateFrom'] = $_GET['dateFrom'] ?? date("Y-m-d", time() - 60 * 60 * 24 * 2);
				$_GET['dateTo'] = $_GET['dateTo'] ?? date("Y-m-d", time());
				?>
				<div style="display: grid; grid-gap: 10px; grid-template-columns: auto auto; margin-top: 10px;">
					<input type="date" name="dateFrom" onchange="GETreloc('dateFrom', this.value);"<?= !empty($_GET['dateFrom']) ? ' value="' . $_GET['dateFrom'] . '"' : ''; ?>>
					<input type="date" name="dateTo" onchange="GETreloc('dateTo', this.value);"<?= !empty($_GET['dateTo']) ? ' value="' . $_GET['dateTo'] . '"' : ''; ?>>
				</div>

				<?
				$RS_SQL = "SELECT * FROM `RS_entries`"
						. " LEFT JOIN `RS_comments` ON (`RS_commentsRS_entry` = `idRS_entries`)"
						. " LEFT JOIN `RS_banks` ON (`idRS_banks` = `RS_entriesBank`)"
						. " LEFT JOIN `RS_correspondents` ON (`idRS_correspondents` = `RS_entriesCorrespondent`)"
						. " WHERE"
						. " `RS_entriesOperationDate`>= '" . $_GET['dateFrom'] . "' AND "
						. " `RS_entriesOperationDate`<= '" . $_GET['dateTo'] . "' "
						. " "
				//. (($_GET['cft'] === 'all' ? '' : ($_GET['cft'] === 'null' ? " AND isnull(`cashFlowType`)" : " AND `cashFlowType` = " . FSI($_GET['cft']))))


				;
//				print $CFTquery;
				$RSentries = query2array(mysqlQuery($RS_SQL));
				?>
				<div style="display: grid; font-size: 0.8em; grid-gap: 0px 0px; grid-template-columns: auto auto <? if ($_GET['cft'] == 'all') { ?> auto <? } ?> auto auto">
					<div style="display: contents; font-weight: bold;">
						<div style="text-align: center; padding: 6px;">Кредит</div>
						<div style="text-align: center; padding: 6px;">Дебет</div>
						<div style="text-align: center; padding: 6px;">Корреспондент</div>
						<div style="text-align: center; padding: 6px;">Примечание</div>
						<? if (!isset($_GET['cft']) || $_GET['cft'] == 'all') { ?> <div style="text-align: center; padding: 6px;">ДДС</div> <? } ?>
					</div>

					<?
					$date = null;
					usort($RSentries, function($a, $b) {
						return $a['RS_entriesOperationDate'] <=> $b['RS_entriesOperationDate'];
					});
					$add = 0;
					$sub = 0;
					foreach ($RSentries as $RSentry) {
						if ($date != date("d/m/Y", strtotime($RSentry['RS_entriesOperationDate']))) {

							if ($date) {
								?>
								<div style="display: contents;">
									<div style=" padding: 0px 10px; background-color: #0aa;"><?= ($add > 0 ? '' : '') . nf($add); ?></div>
									<div style=" padding: 0px 10px; background-color: #0aa;"><?= nf($sub); ?></div>
									<div style="background-color: #0aa;"></div>
									<div style="background-color: #0aa;"></div>
									<? if ($_GET['cft'] == 'all') { ?>
										<div style="background-color: #0aa;"></div>
									<? } ?>

									<!--<div style=""></div>-->
									<!--<div style=""></div>-->
								</div>
								<?
								$add = 0;
								$sub = 0;
							}
							?>





							<? $date = date("d/m/Y", strtotime($RSentry['RS_entriesOperationDate'])); ?>


							<div style="display: contents;">
								<div style="display: flex;background-color: black; color: white;"></div>
								<div style="display: flex;background-color: black; color: white;"></div>
								<div style="display: flex;background-color: black; color: white;" class="text-dark"><b><?= $date; ?></b> &nbsp; &nbsp; &nbsp; <?= nf(0); ?></div>
								<div style="display: flex;background-color: black; color: white;"></div>
								<? if ($_GET['cft'] == 'all') { ?>
									<div style="display: flex;background-color: black; color: white;"></div>
								<? } ?>
								<!--<div style="align-self: center;"></div>-->
							</div>

							<?
						}
						?>


						<div style="display: contents;<?
						if (0 && $RSentry['cashFlowTypesColor']) {
							print 'background-color: ' . $RSentry['cashFlowTypesColor'] . ';';
						}
						?>">
							<div style="padding: 0px 10px; align-items: center; display: flex; background-color: inherit;"><?= $RSentry['RS_entriesCredit'] ? nf($RSentry['RS_entriesCredit']) : ''; ?></div>
							<div style="padding: 0px 10px; align-items: center; display: flex; background-color: inherit;"><?= $RSentry['RS_entriesDebet'] ? nf($RSentry['RS_entriesDebet']) : ''; ?></div>




							<div style="padding: 0px 10px; align-items: center; display: flex; background-color: inherit;"><?= mb_substr($RSentry['RS_correspondentsName'], 0, 1250); ?></div>
							<div style="padding: 0px 10px; align-items: center; display: flex; background-color: inherit;"><?= mb_substr($RSentry['RS_commentsComment'], 0, 50); ?></div>
							<? if ($_GET['cft'] == 'all') { ?>
								<div style="padding: 0px 10px; align-items: center; display: flex; background-color: inherit;"><?= "--"; ?></div>
							<? } ?>
			<!--<div style="padding: 0px 10px; align-items: center; display: flex; text-align: right; background-color: inherit;"><?= nf($runningTotal); ?></div>-->
			<!--							<div style="padding: 0px 10px; align-items: center; display: flex; background-color: inherit;"><button style=" color: red;" onclick="deleteCFentry(<?= $cashFlowRow['idcashFlow']; ?>);">X</button></div>-->
						</div>
						<?
						$add += $RSentry['RS_entriesCredit'];

						$sub += $RSentry['RS_entriesDebet'];
					}
					?>
					<div style="display: contents;">
						<div style="background-color: #0aa; padding: 0px 10px; "><?= ($add > 0 ? '' : '') . nf($add); ?></div>
						<div style="background-color: #0aa; padding: 0px 10px; "><?= nf($sub); ?></div>
						<? if ($_GET['cft'] == 'all') { ?>
							<div style="background-color: #0aa;"></div>
						<? } ?>
						<div style="background-color: #0aa;"></div>
						<div style="background-color: #0aa;"></div>
						<div style="background-color: #0aa;"></div>
						<div style="background-color: #0aa;"></div>
					</div>
				</div>

				<? ?>


				<?
			}
			?>
		</div>
	</div>
<? } else {
	?>
	<div>Нет доступа</div>
<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
