<ul class="horisontalMenu"> 
	<? if (R(16) || R(35)) { ?><li><a href="/pages/personal/index.php">Все</a></li><? } ?> 

	<?
	if ((R(16) || R(35)) && !isset($_GET['employee'])) {
		$positions = query2array(mysqlQuery("SELECT * FROM `positions` WHERE isnull(`positionsDeleted`)"
						. (!R(16) ? "AND `idpositions` IN (32)" : "")
						. ""));
		uasort($positions, function ($a, $b) {
			return mb_strtolower($a['positionsName']) <=> mb_strtolower($b['positionsName']);
		});
		$groups = query2array(mysqlQuery("SELECT * FROM `usersGroups` ORDER BY `usersGroupsSort`"
						. ""));
		?>
		<li>
			<select style="width: auto;" onchange="GETreloc('position', this.value);">
				<option value="">Все должности</option>
				<? foreach ($positions as $position) { ?>
					<option<?= ((isset($_GET['position']) && $_GET['position'] == $position['idpositions']) ? ' selected' : '' ); ?> value="<?= $position['idpositions']; ?>"><?= $position['positionsName']; ?></option>
				<? } ?>
			</select>

		</li>
		<li>
			<select style="width: auto;" onchange="GETreloc('group', this.value);">
				<option value="">Все группы</option>
				<option value="none"<?= ((isset($_GET['group']) && $_GET['group'] == 'none') ? ' selected' : '' ); ?>>БЕЗ группы</option>
				<? foreach ($groups as $group) { ?>
					<option<?= ((isset($_GET['group']) && $_GET['group'] == $group['idusersGroups']) ? ' selected' : '' ); ?> value="<?= $group['idusersGroups']; ?>"><?= $group['usersGroupsName']; ?></option>
				<? } ?>
			</select>

		</li>

		<li>
			<select style="width: auto;" onchange="GETreloc('uncomplete', this.value);">
				<option value="">Любые данные</option>
				<option value="any" <?= (isset($_GET['uncomplete']) && $_GET['uncomplete'] === 'any') ? ' selected' : ''; ?>>Неполные данные</option>
				<option value="finger" <?= (isset($_GET['uncomplete']) && $_GET['uncomplete'] === 'finger') ? ' selected' : ''; ?>>Нет отпечатков</option>
				<option value="icq" <?= (isset($_GET['uncomplete']) && $_GET['uncomplete'] === 'icq') ? ' selected' : ''; ?>>Нет ICQ</option>

			</select>

		</li>

		<li>
			<div style="background-color: hsla(0,0%,100%,0.3); display: inline-flex; align-items: center; border-radius: 12px; padding: 2px 4px;"><input type="text" id="searchbyright" placeholder="Право" style="width: auto; margin-right: 3px;" size="3"><input type="button" value="🔍" onclick="GR({right: searchbyright.value});"></div>
		</li>


	<? } ?>
	<? if (R(50)) { ?><li><a href="/pages/personal/schedule/" target="_blank">График работы</a></li><? } ?> 
	<? if (R(17) || R(34)) { ?><li><a href="/pages/personal/index.php?add">Добавить</a></li><? } ?><li id="printSelected" style="display: none;"><a href="#" onclick="printSelected();">Печать выбранных</a></li>
</ul>