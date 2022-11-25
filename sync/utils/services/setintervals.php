<?php
$pageTitle = 'Интерваля процедур';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

if (
		!empty($_POST['service']) &&
		!empty($_POST['min']) &&
		!empty($_POST['max'])
) {
	mysqlQuery("UPDATE `services` SET"
			. " `servicesMinInterval` = " . sqlVON($_POST['min']) . ","
			. " `servicesMaxInterval` = " . sqlVON($_POST['max']) . " "
			. " WHERE `idservices` = '" . mres($_POST['service']) . "'"
			. " ");
	header("Location: " . GR());
	die();
}

//idservices, servicesParent, servicesCode, servicesName, serviceNameShort, servicesType, servicesDeleted, servicesEquipment, servicesDuration, servicesURL, servicesAdded, servicesEquipped, servicescolN804, servicesSupplierCode, servicesEntryType, servicesNewPlan, servicesVat, servicesAddedBy, servicesTestsReferral, 
$services = query2array(mysqlQuery("SELECT * FROM `services` WHERE (isnull(`servicesMinInterval`) OR isnull(`servicesMaxInterval`)) AND isnull(`servicesDeleted`)"));
?>
<div class="box neutral">
	<div class="box-body">
		Осталось: <?= count($services); ?>
		<div class="lightGrid" style="display: grid; grid-template-columns: repeat(5,auto);">
			<div style="display: contents;">
				<div>#</div>
				<div>Наименование услуги</div>
				<div class="C"><span>Минимальный<br>интервал<br>дней</span></div>
				<div class="C">Максимальный<br>интервал<br>дней</div>
				<div></div>
			</div>
			<?
			$n = 0;
//			usort($services, function ($a, $b) {
//				return rand(0, 100) > 50 ? 1 : -1;
//			});
			foreach ($services as $service) {
				if ($n > 50) {
//					break;
				}
				$n++;
				?>
				<form method="post" style="display: contents;">
					<input type="hidden" name="service" value="<?= $service['idservices']; ?>">
					<div><?= $n; ?></div>
					<div><a target="_blank" href="/pages/services/index.php?service=<?= $service['idservices']; ?>"><?= $service['servicesName']; ?></a></div>
					<div><input name="min" type="text"></div>
					<div><input name="max" type="text"></div>
					<div><input type="submit" value="ok"></div>
				</form>
				<?
			}
			?>
		</div>

	</div>
</div>

<?
//printr($services, 1);
