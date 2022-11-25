<?php
$load['title'] = $pageTitle = 'Оборудование';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(92)) {

	if (isset($_GET['item']) && isset($_GET['deleteEquipment'])) {
		if ($_GET['item'] == $_GET['deleteEquipment']) {
			mysqlQuery("UPDATE `equipment` SET `equipmentDeleted`=NOW() WHERE `idequipment` = '" . mysqli_real_escape_string($link, $_GET['deleteEquipment']) . "'; ");
			header("Location: " . GR2(['deleteEquipment' => null]));
			die();
		}
	}

	if (isset($_POST['idequipment']) && isset($_POST['equipmentName']) && isset($_POST['equipmentQty']) && isset($_POST['equipmentPrice']) && isset($_POST['equipmentServiceLifeYears'])) {

		mysqlQuery("UPDATE `equipment` SET "
				. " `equipmentName` = " . (trim($_POST['equipmentName']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['equipmentName'])) . "'") : 'null') . ","
				. " `equipmentQty` = " . (trim($_POST['equipmentQty']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['equipmentQty'])) . "'") : 'null') . ","
				. " `equipmentPrice` = " . (trim($_POST['equipmentPrice']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['equipmentPrice'])) . "'") : 'null') . ","
				. " `equipmentDepreciation` = " . (trim($_POST['equipmentDepreciation']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['equipmentDepreciation'])) . "'") : 'null') . ","
				. " `equipmentServiceLifeYears` = " . (trim($_POST['equipmentServiceLifeYears']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['equipmentServiceLifeYears'])) . "'") : 'null') . ""
				. ""
				. " WHERE `idequipment` = '" . mysqli_real_escape_string($link, $_POST['idequipment']) . "'");

		header("Location: " . GR());
	}
	if (isset($_POST['newEquipmentName']) && isset($_POST['newEquipmentQty']) && isset($_POST['newEquipmentPrice']) && isset($_POST['newEquipmentServiceLifeYears'])) {
		mysqlQuery("INSERT INTO `equipment` SET "
				. " `equipmentName` = " . (trim($_POST['newEquipmentName']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['newEquipmentName'])) . "'") : 'null') . ","
				. " `equipmentQty` = " . (trim($_POST['newEquipmentQty']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['newEquipmentQty'])) . "'") : 'null') . ","
				. " `equipmentPrice` = " . (trim($_POST['newEquipmentPrice']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['newEquipmentPrice'])) . "'") : 'null') . ","
				. " `equipmentDepreciation` = " . (trim($_POST['newEquipmentDepreciation']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['newEquipmentDepreciation'])) . "'") : 'null') . ","
				. " `equipmentServiceLifeYears` = " . (trim($_POST['newEquipmentServiceLifeYears']) !== '' ? ("'" . mysqli_real_escape_string($link, trim($_POST['newEquipmentServiceLifeYears'])) . "'") : 'null') . ""
		);
		header("Location: " . GR());
		die();
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(92)) {
	?>E403R92<?
} else {
	?>
	<div class="box neutral">
		<div class="box-body">
			<?
//
			?>
			<?
			if (isset($_GET['item'])) {
				$equipment = mfa(mysqlQuery("SELECT * FROM `equipment` WHERE `idequipment` = '" . mysqli_real_escape_string($link, $_GET['item']) . "'"));
				?>
				<h3 style="margin-bottom: 20px;"><a  href="/pages/equipment/">Назад</a></h3>
				<?= $equipment['equipmentDeleted'] ? ("<h3 style='color: darkred; margin-bottom: 10px;'>Удалено " . date("d.m.Y", strtotime($equipment['equipmentDeleted'])) . '</h3>') : ''; ?>
				<?
//				printr($equipment);
				?>
				<form action="<?= GR(); ?>" method="post">
					<input type="hidden" name="idequipment" value="<?= $equipment['idequipment']; ?>">
					<div class="lightGrid" style="display: grid; grid-template-columns: auto auto;">
						<div style="display: contents;">
							<div>Наименование</div>
							<div><input type="text" name="equipmentName" value="<?= htmlentities($equipment['equipmentName']); ?>"></div>
						</div>
						<div style="display: contents;">
							<div>Количество, шт.</div>
							<div><input type="text" name="equipmentQty"  oninput="digon();" value="<?= htmlentities($equipment['equipmentQty']); ?>"></div>
						</div>
						<div style="display: contents;">
							<div>Стоимость, р.</div>
							<div><input type="text" name="equipmentPrice"  oninput="digon();" value="<?= round($equipment['equipmentPrice'], 3); ?>"></div>
						</div>
						<div style="display: contents;">
							<div>Срок эксплуатации, лет</div>
							<div><input type="text" name="equipmentServiceLifeYears"  oninput="digon();" value="<?= round($equipment['equipmentServiceLifeYears'], 3); ?>"></div>
						</div>
						<div style="display: contents;">
							<div>Амортизация на 1 проц.,р.</div>
							<div><input type="text" name="equipmentDepreciation"  oninput="digon();" value="<?= round($equipment['equipmentDepreciation'], 4); ?>"></div>
						</div>


						<div style="display: contents;">
							<div style="display: flex; justify-content: flex-start; align-items: center;"><input type="submit" style="background-color: lightgreen;" value="Сохранить"></div>
							<div style="display: flex; justify-content: flex-end; align-items: center;">
								<? if ($equipment['equipmentDeleted']) {
									?>
									<input style="background-color: pink;" onclick="if (confirm(`Восстановить <?= htmlentities($equipment['equipmentName']); ?>?`)) {
												GR({restoreEquipment:<?= $equipment['idequipment']; ?>});
											}" type="button" value="Восстановить">
										   <?
									   } else {
										   ?>
									<input style="background-color: pink;" onclick="if (confirm(`Удалить <?= htmlentities($equipment['equipmentName']); ?>?`)) {
												GR({deleteEquipment:<?= $equipment['idequipment']; ?>});
											}" type="button" value="Удалить">
									   <? }
									   ?>

							</div>

						</div>

					</div>
					<h3 style="margin: 20px auto 5px auto;">Данное оборудование используется:</h3>
					<?
					$servicesEquipment = query2array(mysqlQuery("SELECT * FROM `services` WHERE `servicesEquipment`='" . $equipment['idequipment'] . "'"));
//						printr($servicesEquipment);
					if (count($servicesEquipment)) {
						?>
						<div>
							<div class="lightGrid" style="display: grid; grid-template-columns: auto 1fr;">
								<div style="display: contents;">
									<div class="C B">#</div>
									<div class="C B">Процедура</div>
								</div>
								<?
								foreach ($servicesEquipment as $service) {
									?>
									<div style="display: contents;">
										<div><?= $service['idservices']; ?></div>
										<div><a target="_blank" href="/pages/services/index.php?service=<?= $service['idservices']; ?>"><?= $service['servicesName']; ?></a></div>
									</div>
									<?
								}
								?>
							</div>
							<?
						} else {
							?> НИГДЕ!<?
						}
						?>

					</div>

				</form>

				<?
			} else {
				$equipment = query2array(mysqlQuery("SELECT * FROM `equipment`"));
				usort($equipment, function($a, $b) {
					if ($a['equipmentDeleted'] <=> $b['equipmentDeleted']) {
						return $a['equipmentDeleted'] <=> $b['equipmentDeleted'];
					}
					return mb_strtolower($a['equipmentName']) <=> mb_strtolower($b['equipmentName']);
				});
				?>

				<?
//				printr($equipment[0]);
				?>
				<form style="display: contents;" action="<?= GR(); ?>" method="post">
					<div class="lightGrid" style="display: grid; grid-template-columns: auto auto auto auto auto auto;">
						<div style="display: contents;">
							<div style="display: flex; align-items: center; justify-content: center;" class="B C">Наименование</div>
							<div style="display: flex; align-items: center; justify-content: center;" class="B C">количество, шт.</div>
							<div style="display: flex; align-items: center; justify-content: center;" class="B C">Стоимость, р.</div>
							<div style="display: flex; align-items: center; justify-content: center;" class="B C">Срок<br>эксплуатации, лет</div>
							<div style="display: flex; align-items: center; justify-content: center;" class="B C">Амортизация<br>на 1проц, р.</div>
							<div style="display: flex; align-items: center; justify-content: center;" class="B C">X</div>
						</div>


						<div style="display: contents;">
							<div style="display: flex; align-items: center;"><input size="60" type="text" name="newEquipmentName"></div>
							<div style="display: flex; align-items: center;"><input size="5" class="C" type="text" name="newEquipmentQty"   oninput="digon();"></div>
							<div style="display: flex; align-items: center;"><input size="5" class="C" type="text" name="newEquipmentPrice" oninput="digon();"></div>
							<div style="display: flex; align-items: center;"><input size="5" class="C" type="text" name="newEquipmentServiceLifeYears" oninput="digon();"></div>
							<div style="display: flex; align-items: center;"><input size="5" class="C" type="text" name="newEquipmentDepreciation" oninput="digon();"></div>
							<div style="display: flex; align-items: center;" class="C"><input type="submit" value="+"></div>
						</div>


						<?
						foreach ($equipment as $item) {
							?>
							<div style="display: contents;">
								<div style="display: flex; align-items: center;"><a style="<?= $item['equipmentDeleted'] ? ' text-decoration: line-through;' : '' ?>" href="/pages/equipment/index.php?item=<?= $item['idequipment']; ?>"><?= $item['equipmentName']; ?></a></div>
								<div style="display: flex; align-items: center; justify-content: center;<?= $item['equipmentDeleted'] ? ' text-decoration: line-through;' : '' ?>"><?= $item['equipmentQty']; ?></div>
								<div style="display: flex; align-items: center; justify-content: flex-end;<?= $item['equipmentDeleted'] ? ' text-decoration: line-through;' : '' ?>"><?= nf(round($item['equipmentPrice'], 3)); ?>р.</div>
								<div style="display: flex; align-items: center; justify-content: center;<?= $item['equipmentDeleted'] ? ' text-decoration: line-through;' : '' ?>"><?= round($item['equipmentServiceLifeYears'], 3); ?></div>
								<div style="display: flex; align-items: center; justify-content: center;<?= $item['equipmentDeleted'] ? ' text-decoration: line-through;' : '' ?>"><?= round($item['equipmentDepreciation'], 3); ?></div>
								<div></div>
							</div>
							<?
						}
						?>
					</div>

				</form>

			<? } ?>




		</div>
	</div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
