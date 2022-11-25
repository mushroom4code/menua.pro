<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?>
<? include 'includes/top.php'; ?>

<?
if (R(22) || R(36)) {
	?>
	<table style="background-color: white; margin: 20px; padding: 10px; border-radius: 10px;">
		<tr>
			<td>Фамилия:</td>
			<td><a href="#"<? if (R(18) || R(38)) { ?> data-function="editField" data-field="userLName" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersLastName']; ?>"<? } ?>><?= $employee['usersLastName'] ?? 'не указана'; ?></a></td>
		</tr>
		<tr>
			<td>Имя:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="userFName" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersFirstName']; ?>"<? } ?>><?= $employee['usersFirstName'] ?? 'не указано'; ?></a></td>
		</tr>
		<tr>
			<td>Отчество:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="userMName" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersMiddleName']; ?>"<? } ?>><?= $employee['usersMiddleName'] ?? 'не указано'; ?></a></td>
		</tr>
		<tr>
		<tr>
			<td>Логин:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="login" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['credentialsLogin']; ?>"<? } ?>><?= $employee['credentialsLogin'] ?? 'не указан'; ?></a></td>
		</tr>
		<tr>
		<tr>
			<td>Пароль:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="password" data-user="<?= $employee['idusers']; ?>" data-value=""<? } ?>><?= $employee['credentialsPassword'] ? 'указан' : 'не указан'; ?></a></td>
		</tr>
		<tr>
			<td>Отпечаток пальца №:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="usersFinger" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersFinger'] ?? ''; ?>"<? } ?>><?= $employee['usersFinger'] ?? 'не указан'; ?></a></td>
		</tr>

		<tr>
			<td>Доступ в ПО:</td>
			<td><input <? if (R(20) || R(37)) { ?>onclick="userActivate(this,<?= $employee['idusers']; ?>);"<? } else { ?> disabled<? } ?> type="checkbox"<?= $employee['usersActiveState'] ? ' checked' : ''; ?> id="userActive"><label for="userActive"></label></td>
		</tr>

		<tr>
			<td>Номер телефона:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="userPhone" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersPhone']; ?>"<? } ?>>не указан</a></td>
		</tr>

		<tr>
			<td>Дата рождения:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="usersBday" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersBday']; ?>"<? } ?>><?= $employee['usersBday'] ? date("d.m.Y", strtotime($employee['usersBday'])) : 'не указана'; ?></a></td>
		</tr>
		<tr>
			<td>Номер ICQ:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="usersICQ" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersICQ'] ?? ''; ?>"<? } ?>><?= $employee['usersICQ'] ?? 'не указан'; ?></a></td>
		</tr>
		<tr>
			<td>Telegram ID:</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="usersTG" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersTG'] ?? ''; ?>"<? } ?>><?= $employee['usersTG'] ?? 'не указан'; ?></a></td>
		</tr>
		<tr>
			<td>Личный код (<b>ШТРИХ-КОД</b>):</td>
			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="userBC" data-user="<?= $employee['idusers']; ?>" data-value="<?= $employee['usersBarcode']; ?>"<? } ?>><?= $employee['usersBarcode'] ? $employee['usersBarcode'] : 'Не указан'; ?></a></td>
		</tr>
		<tr>
			<td>Должность:</td>

			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="userPosition"  data-user="<?= $employee['idusers']; ?>" data-value='<?= json_encode(array_column($employee['positions'] ?? [], 'id')); ?>'<? } ?>><?= (isset($employee['positions']) && count($employee['positions'])) ? implode(', ', array_column($employee['positions'], 'name')) : 'Не указана'; ?></a></td>
		</tr>
		<tr>
			<td>Группа:</td>

			<td><a href="#" <? if (R(18) || R(38)) { ?> data-function="editField" data-field="userGroup"  data-user="<?= $employee['idusers']; ?>" data-value='<?= $employee['usersGroup']; ?>'<? } ?>><?= (isset($employee['usersGroup'])) ? $employee['usersGroupsName'] : 'Не указана'; ?></a></td>
		</tr>
	<!--						<tr>
				<td>Подразделение:</td>
				<td><a href="#">не указано</a></td>
		</tr>-->
		<tr>
			<td colspan="2">Дополнительная информация:</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="console">
					<?
					$log = [];

					$outData = query2array(mysqlQuery("SELECT *,"
									. " UNIX_TIMESTAMP(`WH_goodsOutDate`) AS `TS`"
									. " FROM `WH_goodsOut` "
									. " LEFT JOIN `units` ON (`idunits` = `WH_goodsOutUnits`) "
									. " LEFT JOIN `WH_goods` ON (`idWH_goods` = `WH_goodsOutItem`) "
									. "WHERE `WH_goodsOutUser` = '" . $employee['idusers'] . "' AND isnull(`WH_goodsOutDeleted`)"));
//												printr($outData);
					$n = 0;
					foreach ($outData as $out) {
						$log[$n]['out'] = round($out['WH_goodsOutQty'], 3);
						$log[$n]['name'] = $out['WH_goodsName'];
						$log[$n]['id'] = $out['idWH_goods'];
						$log[$n]['nom'] = $out['WH_goodsNomenclature'];
						$log[$n]['units'] = $out['unitsName'];
						$log[$n]['TS'] = $out['TS'];
						$n++;
					}
					usort($log, function ($a, $b) {
						return $a['TS'] <=> $b['TS'];
					});

//printr($log);
					$olddate = '';
					foreach ($log as $entry) {
						$date = $entry['TS'];
						if ($olddate !== date('Ymd', $entry['TS'])) {

							if ($olddate) {
								?><hr><?
							}
							print date("d", $date)
									. ' ' . ['ошибка', 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'][date("n", $date)]
									. (date("Y") != date("Y", $date) ? date(" Y", $date) : '');
							$olddate = date('Ymd', $date);
						}
						if (isset($entry['out'])) {
							?>
							<div class="consItemOut"><?= date("H:i", $date); ?> <a href="/pages/warehouse/goods/item/?item=<?= $entry['nom']; ?>" target="_blank"><?= $entry['name']; ?></a> <span><?= $entry['out']; ?><?= $entry['units']; ?></span></div>
						<? } ?>
					<? } ?>
				</div>
			</td>
		</tr>
	</table>


	<?
}
?>
<script>
	function userActivate(elem, iduser) {
		fetch('personal_IO.php', {
			body: JSON.stringify({
				userActivate: iduser,
				setTo: elem.checked ? true : false
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
</script>


<? include 'includes/bottom.php'; ?>