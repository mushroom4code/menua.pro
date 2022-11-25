<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?>

<? include 'includes/top.php'; ?>

<?
//printr($employee);
if (count($employee['positions'])) {
	$positionSpecificServices = query2array(mysqlQuery("SELECT *, "
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`< NOW() AND `servicesPricesType`='3' AND `servicesPricesService` = `idservices`)) as `wageMin`,"
					. "(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT MAX(`idservicesPrices`) FROM `servicesPrices` WHERE `servicesPricesDate`< NOW() AND `servicesPricesType`='4' AND `servicesPricesService` = `idservices`)) as `wageMax`,"
					. "(SELECT `usersServicesPaymentsSumm` FROM `usersServicesPayments` WHERE `idusersServicesPayments` = (SELECT MAX(`idusersServicesPayments`) FROM `usersServicesPayments` WHERE `usersServicesPaymentsDate`< NOW() AND `usersServicesPaymentsUser`='" . $employee['idusers'] . "' AND `usersServicesPaymentsService` = `idservices`)) as `usersServicesPaymentsSumm`,"
					. "(SELECT `usersServicesPaymentsSummFree` FROM `usersServicesPayments` WHERE `idusersServicesPayments` = (SELECT MAX(`idusersServicesPayments`) FROM `usersServicesPayments` WHERE `usersServicesPaymentsDate`< NOW() AND `usersServicesPaymentsUser`='" . $employee['idusers'] . "' AND `usersServicesPaymentsService` = `idservices`)) as `usersServicesPaymentsSummFree`,"
					. "(SELECT `usersServicesPaymentsDate` FROM `usersServicesPayments` WHERE `idusersServicesPayments` = (SELECT MAX(`idusersServicesPayments`) FROM `usersServicesPayments` WHERE `usersServicesPaymentsDate`< NOW() AND `usersServicesPaymentsUser`='" . $employee['idusers'] . "' AND `usersServicesPaymentsService` = `idservices`)) as `usersServicesPaymentsDate`"
					. " FROM `positions2services` LEFT JOIN `services` ON (`idservices`=`positions2servicesService`) WHERE `positions2servicesPosition` in (" . implode(',', array_column($employee['positions'], 'id')) . ")"));
}

$users2services = query2array(mysqlQuery("SELECT * FROM `users2services` LEFT JOIN `services` ON (`idservices` = `users2servicesInclude`) WHERE `users2servicesUser` = '" . $employee['idusers'] . "'"));
//printr($users2services);
?>

<? if (!R(125)) {
	?>E403R125<? } else { ?>

	<div style="display: inline-block; padding: 10px;">
		<div class="lightGrid" style="display: grid; grid-template-columns:  repeat(6,auto);">
			<div style="display: contents;" class="C B">
				<div style="grid-row: span 2;">Процедуры по должности</div>
				<div style="grid-row: span 2;"><i class="fas fa-check-square"></i></div>
				<div style="grid-column: span 4;">Оплата</div>
			</div>
			<div style="display: contents;" class="C B">
				<div>общая</div>
				<div>Платная</div>
				<div>Подарочная</div>
				<div>дата</div>
			</div>

			<?
			usort($positionSpecificServices, function ($a, $b) {
				return mb_strtolower($a['servicesName']) <=> mb_strtolower($b['servicesName']);
			});
			foreach ($positionSpecificServices as $positionSpecificService) {

				$highlight = $positionSpecificService['idservices'] == ($_GET['highlight'] ?? '') ? ' background-color: lightblue; ' : '';
//				print $highlight;
				?>
				<div style="display: contents;">
					<div style="<?= $highlight; ?>display: flex; align-items: center; text-indent: 10px;" id="service<?= $positionSpecificService['idservices']; ?>"><a target="_blank" href="/pages/services/index.php?service=<?= $positionSpecificService['idservices']; ?>"><?= $positionSpecificService['idservices']; ?>] <?= $positionSpecificService['servicesName'] ?></a><?= $positionSpecificService['servicesDeleted'] ? '(удалена)' : ''; ?></div>
					<div style="<?= $highlight; ?>"><input autocomplete="off" type="checkbox"<?= in_array($positionSpecificService['idservices'], array_column($users2services, 'users2servicesExclude')) ? '' : ' checked'; ?> onclick="changeServices({state: !this.checked, service:<?= $positionSpecificService['idservices']; ?>, user: <?= $employee['idusers']; ?>, action: 'excludeSrevice'});" id="cb<?= $positionSpecificService['idservices']; ?>"><label for="cb<?= $positionSpecificService['idservices'] ?>"></label></div>

					<div style="<?= $highlight; ?>display: flex; align-items: center; justify-content: center;"><?= $positionSpecificService['wageMin']; ?><?= ($positionSpecificService['wageMax'] && $positionSpecificService['wageMin'] != $positionSpecificService['wageMax']) ? ('...' . $positionSpecificService['wageMax']) : ''; ?></div>
					<div style="<?= $highlight; ?>isplay: flex; align-items: center; text-indent: 10px;">
						<input type="text" id="pValue<?= $positionSpecificService['idservices']; ?>" autocomplete="off" value="<?= $positionSpecificService['usersServicesPaymentsSumm']; ?>" style="width: auto; text-align: center;" size="5">
					</div>
					<div style="<?= $highlight; ?>isplay: flex; align-items: center; text-indent: 10px;">
						<input type="text" id="pValueFree<?= $positionSpecificService['idservices']; ?>" autocomplete="off" value="<?= $positionSpecificService['usersServicesPaymentsSummFree']; ?>" style="width: auto; text-align: center;" size="5">
					</div> 
					<div style="<?= $highlight; ?>display: flex; align-items: center; text-indent: 10px;">
						<input type="date" id="pDate<?= $positionSpecificService['idservices']; ?>"  autocomplete="off" value="<?= $positionSpecificService['usersServicesPaymentsDate']; ?>">
						<input type="button" value="ok" onclick="savePersonalPayments(
												{
													service:<?= $positionSpecificService['idservices']; ?>,
													employee: <?= $employee['idusers']; ?>,
													date: document.querySelector(`#pDate<?= $positionSpecificService['idservices']; ?>`).value,
													value: document.querySelector(`#pValue<?= $positionSpecificService['idservices']; ?>`).value,
													valueFree: document.querySelector(`#pValueFree<?= $positionSpecificService['idservices']; ?>`).value
												});">
					</div>
				</div>
				<?
			}
			?>

		</div>
	</div><!-- comment -->
	<script>
		function savePersonalPayments(params) {
			if (!params.date) {
				MSG('Дату нада');
				return false;
			}

			params.action = 'savePersonalPayments';

			fetch('IO.php', {
				body: JSON.stringify(params),
				credentials: 'include',
				method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
			}).then(result => result.text()).then(async function (text) {
				try {
					let jsn = JSON.parse(text);
					if (jsn.success) {
						MSG({type: 'success', text: 'ok', autoDismiss: 400});
					}
					;
				} catch (e) {
					MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
				}
				console.log(params);
			});
		}
	</script>

	<div style="display: inline-block; padding: 10px;">
		<div style="padding: 10px;"><input type="text" placeholder="Поиск процедур" id="serviceSearch" onkeydown="
					if (event.keyCode === 38) {
						pointer--;
					} else if (event.keyCode === 40) {
						pointer++;
					}
					let confirm = false;
					if (event.keyCode === 13) {
						confirm = true;
					}
					suggest(this.value, confirm);" oninput="pointer = 0; suggest(this.value);" style="display: inline; width: auto;">
			<ul id="suggestions">
			</ul></div>

		<div class="lightGrid" style="display: grid; grid-template-columns:  auto auto;">
			<div style="display: contents;" class="C B">
				<div>Личные процедуры</div>
				<div><i class="fas fa-check-square"></i></div>
			</div>
			<!--<div style="display: contents; background-color: white;">-->

			<!--</div>-->

			<?
//		printr($users2services);
			$filtered = array_filter($users2services, function ($service) {
				return ($service['idservices'] ?? false);
			});
//		printr($filtered);
			foreach ($filtered as $users2service) {
				?>
				<div style="display: contents;">
					<div style="display: flex; align-items: center; text-indent: 10px;"><a target="_blank" href="/pages/services/index.php?service=<?= $users2service['idservices']; ?>"><?= $users2service['idservices']; ?>] <?= $users2service['servicesName']; ?></a></div>
					<div><input autocomplete="off" type="checkbox"<?= in_array($users2service['idservices'], array_column($filtered, 'users2servicesInclude')) ? ' checked' : ''; ?> onclick="changeServices({state: this.checked, service:<?= $users2service['idservices']; ?>, user: <?= $employee['idusers']; ?>, action: 'includeSrevice'});" id="cbi<?= $users2service['idservices']; ?>"><label for="cbi<?= $users2service['idservices'] ?>"></label></div>
				</div>


				<?
			}
			?>

		</div>
	</div>
<? } ?>

<? include 'includes/bottom.php'; ?>