<?php
$pageTitle = 'Должности';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(43)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(43)) {
	?>E403R43<?
} else {
	$positions = query2array(mysqlQuery("SELECT * FROM `positions`"));
	usort($positions, function ($a, $b) {

		if ($a['positionsDeleted'] !== $b['positionsDeleted']) {
			return $a['positionsDeleted'] <=> $b['positionsDeleted'];
		} else {
			return mb_strtolower($a['positionsName']) <=> mb_strtolower($b['positionsName']);
		}
	});
//	printr($positions[0]);
	?>

	<select autocomplete="off" onchange="GETreloc('position', this.value)">
		<option></option>
		<? foreach ($positions as $position) { ?>
			<option <?= (isset($_GET['position']) && $_GET['position'] === $position['idpositions']) ? ' selected' : ''; ?> value="<?= $position['idpositions']; ?>"><?= $position['positionsName'] . ($position['positionsDeleted'] ? ' (Должность удалена)' : ''); ?></option>
		<? } ?>
	</select>
	<?
	if (isset($_GET['position'])) {
		?>

		<ul class="horisontalMenu">
			<li><a onclick="GETreloc('type', null)" <?= empty($_GET['type']) ? ' class="activeButton"' : ''; ?>>Без типа</a></li>
			<li><a onclick="GETreloc('type', 'all')" <?= (!empty($_GET['type']) && $_GET['type'] === 'all') ? ' class="activeButton"' : ''; ?>>Все</a></li>


			<?
			$serviceTypes = query2array(mysqlQuery("SELECT * FROM `servicesTypes`"));
			foreach ($serviceTypes AS $type) {
				?>
				<li><a onclick="GETreloc('type',<?= $type['idservicesTypes']; ?>)"<?= (!empty($_GET['type']) && $_GET['type'] === $type['idservicesTypes']) ? ' class="activeButton"' : ''; ?>><?= $type['servicesTypesName']; ?></a></li>
				<?
			}
			?>
		</ul>

		<div class="box neutral">
			<div class="box-body">



				<div style="display: grid; grid-template-columns: auto auto; grid-gap: 6px; margin: 10px;">
					<div style="display: contents;">
						<div>#</div>
						<div>Наименование процедуры</div>

					</div>
					<?
					$services = query2array(mysqlQuery("SELECT *"
									. " FROM `services`"
									. " LEFT JOIN `servicesTypes` ON (`idservicesTypes` = `servicesType`)"
									. " LEFT JOIN `positions2services` ON (`positions2servicesPosition`='" . FSI($_GET['position']) . "' AND `positions2servicesService` = `idservices`)"
									. " WHERE isnull(`servicesDeleted`)"
									. " AND(isnull(`servicesEntryType`) OR `servicesEntryType` IN(2,3,4)) "
									. ((isset($_GET['type']) ? ($_GET['type'] === 'all' ? '' : (" AND `servicesType` = " . FSI($_GET['type'])) ) : (" AND isnull(`servicesType`)")))
									. ""));
					$N = 0;

					usort($services, function ($a, $b) {
						return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
					});
//					printr($services);

					foreach ($services as $service) {
						$N++;
						?>
						<div style="display: contents;">
							<div><?= $service['idservices']; ?>]</div>
							<div><input type="checkbox"<?= $service['positions2servicesService'] ? ' checked' : ''; ?> onclick="savePos2Serv(<?= FSI($_GET['position']); ?>,<?= $service['idservices']; ?>, this.checked);" id="SI<?= $service['idservices']; ?>"><label for="SI<?= $service['idservices']; ?>"><?= $service['servicesName']; ?></label></div>
						</div>
						<?
					}
					?>
				</div>
			</div>
		</div>
		<script>
			async function savePos2Serv(position, service, state) {
				console.log(position, service, state);



				fetch('IO.php', {
					body: JSON.stringify(
							{
								action: 'savePos2Serv',
								position: position,
								service: service,
								state: state
							}
					),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						//						let jsn = JSON.parse(text);
						//
						//						if (jsn.success && jsn.client) {
						//							window.location.href = `/pages/reception/?client=${jsn.client}`;
						//						}
						//						if (jsn.msgs) {
						//							jsn.msgs.forEach(msg => {
						//								MSG(msg);
						//							});
						//						}
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				});//fetch



			}
		</script>
		<?
	}
	?>
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
