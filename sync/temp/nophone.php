<?php
$pageTitle = 'телефоны';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (($_POST['phone'] ?? '') != '') {
	if (count($_POST['phone'])) {
		foreach ($_POST['phone'] as $client => $phone) {
			if ($phone) {
				mysqlQuery("INSERT INTO `clientsPhones` SET"
						. " `clientsPhonesPhone` = '" . $phone . "',"
						. "`clientsPhonesClient` = '" . $client . "'");
			}
		}
	}

	header("Location: " . GR());
	die();
}
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (0) {
	?>E403R27<?
} else {

	$clients = query2array(mysqlQuery("SELECT "
					. "`idclients`,"
					. "`clientsLName`,"
					. "`clientsFName`,"
					. "`clientsMName`,"
					. "`clientsBDay`,"
					. "`clientsAKNum`"
					. " FROM warehouse.clients where (select count(1) from `clientsPhones` where `clientsPhonesClient` = `idclients`)=0;"));
	?>
	<style>
		tr:hover td {
			background-color: lemonchiffon;
		}
	</style>
	<div class="box neutral">
		<div class="box-body">
			<h3 style="margin-bottom: 10px;">
				В нашей базе <?= human_plural_form(count($clients), ['клиент', 'клиента', 'клиентов'], true); ?> без номер телефона. Это <?=
				rt([
					'капец.',
					'как так??',
					'как так выщло??',
					'плохо.',
					'пи#@ц.',
					'недопустимо.',
				]);
				?>
			</h3>
			<form action="?" method="post">
				<table border="1" style="border-collapse: collapse;"><?
					usort($clients, function($a, $b) {
						if (mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName'])) {
							return mb_strtolower($a['clientsLName']) <=> mb_strtolower($b['clientsLName']);
						}

						if (mb_strtolower($a['clientsFName']) <=> mb_strtolower($b['clientsFName'])) {
							return mb_strtolower($a['clientsFName']) <=> mb_strtolower($b['clientsFName']);
						}

						if (mb_strtolower($a['clientsMName']) <=> mb_strtolower($b['clientsMName'])) {
							return mb_strtolower($a['clientsMName']) <=> mb_strtolower($b['clientsMName']);
						}
					});
					foreach ($clients as $client) {
						?>
						<tr>	
							<td>
								<input type="checkbox" onclick="cbxes();" data-clone="<?= $client['idclients']; ?>" id="c<?= $client['idclients']; ?>"><label for="c<?= $client['idclients']; ?>"></label><span id="l<?= $client['idclients']; ?>"></span>
							</td>
							<td>
								<?= $client['idclients']; ?>
							</td>
							<td><a href="/pages/offlinecall/schedule.php?client=<?= $client['idclients']; ?>" target="_blank">
									<?= $client['clientsLName']; ?>
									<?= $client['clientsFName']; ?>
									<?= $client['clientsMName']; ?>
								</a>
							</td>
							<td>
								<?= $client['clientsBDay']; ?>
							</td>
							<td>
								<?= $client['clientsAKNum']; ?>
							</td>

							<td>
								<!--<input type="text" name="phone[<?= $client['idclients']; ?>]" autocomplete="off" oninput="digon();">-->
							</td>

						</tr>
						<?
					}
					?>
				</table>
				<input type="submit" value="Сохранить">
			</form>
		</div>
	</div>
	<script>
		function cbxes() {

			let printArr = [];
			for (let elem of qsa('input[type=checkbox]')) {
				if (elem.checked) {
					printArr.push(+elem.dataset.clone);
				}
			}
			for (let elem of qsa('input[type=checkbox]')) {
				if (elem.checked) {
					qs(`#l${elem.dataset.clone}`).innerHTML = `<a target="_blank" href="/sync/utils/clones/?clones=${JSON.stringify(printArr)}">Мочить клона!</a>`;
				} else {
					qs(`#l${elem.dataset.clone}`).innerHTML = ``;
				}
			}
			console.log(printArr);
		}
	</script>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
