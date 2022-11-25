<?php
$load['title'] = $pageTitle = 'Процедурный лист';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!($client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients` = '" . mres($_GET['client']) . "'")))) {
	header("Location: /pages/proclist/");
	die('no such client');
}

if ($_POST) {
//	printr($_POST);
	foreach ($_POST['param'] as $idmedrecordsTypes => $medrecordsExamsValue) {
		if (trim($medrecordsExamsValue ?? '')) {
			if (mysqlQuery("INSERT INTO `medrecordsExams` SET "
							. " `medrecordsExamsType` = '" . mres($idmedrecordsTypes) . "',"
							. " `medrecordsExamsValue` = " . sqlVON($medrecordsExamsValue) . ","
							. " `medrecordsExamsClient` = " . $client['idclients'] . ","
							. " `medrecordsExamsTime` = NOW()")) {
				header("Location: " . GR());
				exit('ok');
			} else {
				die('error');
			}
		}
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

$medrecordsTypes = query2array(mysqlQuery("SELECT * FROM `medrecordsTypes` ORDER BY `medrecordsTypesSort`"));
foreach ($medrecordsTypes as $medrecordsTypesIndex => $medrecordsType) {
	$medrecordsTypes[$medrecordsTypesIndex]['records'] = query2array(mysqlQuery("SELECT * FROM `medrecordsExams` WHERE `medrecordsExamsClient` = '" . $client['idclients'] . "' AND `medrecordsExamsType` = '" . $medrecordsType['idmedrecordsTypes'] . "'"));
}
?>
<style>
	.contents {
		display: contents;
	}

	.hidden {
		display: none !important;
	}
</style>

<div>
	<div class="box neutral">
		<div class="box-body" style="">
			<h2><?= mb_ucfirst($client['clientsLName']); ?> <?= mb_ucfirst($client['clientsFName']); ?> <?= mb_ucfirst($client['clientsMName']); ?> </h2>

			<? include 'clientsmenu.php'; ?>

			<div class="lightGrid" style="display: grid; grid-template-columns: repeat(5,auto);">	

				<div style="display: contents;">
					<div style="grid-row: span 2;">Показатель</div>
					<div style="grid-column: span 2;" class="C">Последнее измерение</div>
					<div style="grid-column: span 2;" class="C">Записать</div>
				</div>

				<div style="display: contents;">
					<div class="C">Дата</div>
					<div class="C">Зачение</div>
					<div class="C">Зачение</div>
					<div class="C"></div>
				</div>

				<?
				foreach ($medrecordsTypes as $medrecordsType) {
					$lastrecord = ($medrecordsType['records'][count($medrecordsType['records']) - 1] ?? false);
					?>
					<form style="display: contents;" action="<?= GR(); ?>" method="POST">
						<div style=" cursor: pointer;" onclick="document.querySelector(`#details_<?= $medrecordsType['idmedrecordsTypes']; ?>`).classList.toggle('hidden');">
							<?= $medrecordsType['medrecordsTypesName']; ?>
						</div>
						<div style=" cursor: pointer;" onclick="document.querySelector(`#details_<?= $medrecordsType['idmedrecordsTypes']; ?>`).classList.toggle('hidden');">
							<? if ($lastrecord ?? false) { ?>
								<?= date("d.m.Y", strtotime($lastrecord['medrecordsExamsTime'])); ?>
							<? } ?>
						</div>
						<div style=" cursor: pointer;" onclick="document.querySelector(`#details_<?= $medrecordsType['idmedrecordsTypes']; ?>`).classList.toggle('hidden');">
							<? if ($lastrecord ?? false) { ?>
								<?= $lastrecord['medrecordsExamsValue']; ?>
								<?= $medrecordsType['medrecordsTypesUnits']; ?>
							<? } ?>
						</div>
						<div><input type="text" placeholder="новое значение" name="param[<?= $medrecordsType['idmedrecordsTypes']; ?>]"></div>
						<div><input type="submit" value="Сохранить"></div>
					</form>
					<div class="contents hidden" id="details_<?= $medrecordsType['idmedrecordsTypes']; ?>">
						<?
						foreach ($medrecordsType['records'] as $record) {
							?>
							<div></div>
							<div><?= date("d.m.Y H:i", strtotime($record['medrecordsExamsTime'])); ?></div>
							<div>	<?= $record['medrecordsExamsValue']; ?>
								<?= $medrecordsType['medrecordsTypesUnits']; ?></div>
							<div></div>
							<div></div>
							<?
						}
						?>

					</div>


					<?
				}
				?>
			</div>
		</div>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';

