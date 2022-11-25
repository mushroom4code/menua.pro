<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?>
<? include 'includes/top.php'; ?>

<?

function printRulesTree($array) {
	if (!R(21)) {
		print "Нет доступа";
		return;
	}
	global $employee;
	if (is_array($array) && count($array)) {

		uasort($array, function ($a, $b) {
			return $a['rightsName'] <=> $b['rightsName'];
		});
		?><ul><?
			foreach ($array as $element) {
				?>
				<li <?= $element['rightsPrivate'] ? ' style="color: red;"' : ''; ?>><input type="checkbox"<?= !R(21) ? ' disabled' : '' ?><?= $element['value'] ? ' checked' : ''; ?> id="rule_<?= $element['idrights']; ?>"><label<? if (R(21)) { ?> data-function="setRights" data-user="<?= $employee['idusers']; ?>" data-right="<?= $element['idrights']; ?>"<? } ?> for="rule_<?= $element['idrights']; ?>"><?= $element['idrights']; ?>] <?= $element['rightsName']; ?></label> <?
					if (isset($element['descendant'])) {
						printRulesTree($element['descendant']);
					}
					?></li>
				<?
			}
			?></ul><?
		}
	}
	?>


<div style="text-align: center;">
	<div style="text-align: left; margin: 10px;">
		<h3>Просмотр данных клиентов из источников:</h3>
		<ul style="list-style: none; margin-left: 20px;">

			<?
			$clientsSources = query2array(mysqlQuery("SELECT * FROM `clientsSources` ORDER BY `clientsSourcesName`"));
			$clientsSourcesRights = query2array(mysqlQuery("SELECT * FROM `clientsSourcesRights` WHERE `clientsSourcesRightsUser` = '" . mres($_GET['employee'] ?? '0') . "'"));
			?>
			<li style="list-style: none;line-height: 1.7;"><input type="checkbox" <?= in_array(null, array_column($clientsSourcesRights, 'clientsSourcesRightsSource')) ? 'checked' : ''; ?>  onclick="setCSfilter({user:<?= $_GET['employee'] ?? 'null' ?>, clientSource: null, state: this.checked});"  id="CSnull" value="null"><label for="CSnull">Без источника</label></li>
			<?
			foreach ($clientsSources as $clientsSource) {
				?>
				<li style="list-style: none;line-height: 1.7;"><input type="checkbox" <?= in_array($clientsSource['idclientsSources'], array_column($clientsSourcesRights, 'clientsSourcesRightsSource')) ? 'checked' : ''; ?> id="CS<?= $clientsSource['idclientsSources']; ?>" onclick="setCSfilter({user:<?= $_GET['employee'] ?? 'null' ?>, clientSource:<?= $clientsSource['idclientsSources']; ?>, state: this.checked});" value="<?= $clientsSource['idclientsSources']; ?>"><label for="CS<?= $clientsSource['idclientsSources']; ?>"><?= $clientsSource['clientsSourcesName']; ?></label></li>
				<?
			}
			?>
		</ul>
		<script>
			function setCSfilter(data) {
				data.action = 'setCSfilter';
				fetch('personal_IO.php', {
					body: JSON.stringify(data),
					credentials: 'include',
					method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
				}).then(result => result.text()).then(async function (text) {
					try {
						let jsn = JSON.parse(text);
					} catch (e) {
						MSG("Ошибка парсинга ответа сервера. <br><br><i>" + e + "</i>");
					}
				});//fetch

			}
		</script>
	</div>
	<div id="allRights">
		<h3>Права пользователя:</h3>
		<?
		$allRights = adjArr2obj(query2array(mysqlQuery("SELECT "
								. "`idrights` AS `idrights`,"
								. "'$employee[idusers]' AS `user`, "
								. " null as `usersPhone`,"
								. "`rightsParent` AS `rightsParent`,"
								. "`rightsName` AS `rightsName`,"
								. "`usersRightsDate` as `date`,"
								. " `usersRightsValue` AS `value`,"
								. "`rightsPrivate`"
								. " FROM   "
								. " `rights` AS `R1`"
								. " LEFT JOIN"
								. " `usersRights` AS `UR1` ON (`UR1`.`idusersRights` = (SELECT "
								. " MAX(`UR2`.`idusersRights`)"
								. " FROM"
								. " `usersRights` AS `UR2`"
								. " WHERE"
								. " `UR1`.`usersRightsRule` = `UR2`.`usersRightsRule`"
								. " AND `UR2`.`usersRightsUser` = '$employee[idusers]'"
								. ")"
								. " AND `R1`.`idrights` = `UR1`.`usersRightsRule`) "
								. (($_USER['id'] != 176) ? ("WHERE isnull(`rightsPrivate`) ") : (''))
								. "ORDER BY `R1`.`idrights`")), $id = 'idrights', $parent = 'rightsParent', $content = 'descendant');

		//printr($allRights);
		printRulesTree($allRights);
		?>
	</div>
</div>


<? include 'includes/bottom.php'; ?>