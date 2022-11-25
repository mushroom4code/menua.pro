<div class="box success">
	<div class="box-body">
		<h2>Заявка на кредит:</h2>


		<div style="display: grid;  grid-template-columns: auto auto; grid-gap: 5px;">


			<div style="display: contents;">
				<div>Возраст:</div>
				<div>
					хх
				</div>
			</div>
			<div style="display: contents;">
				<div>Девичья фамилия матери или кодовое слово:</div>
				<div>
					<input type="text">
				</div>
			</div>


			<div style="display: contents;">
				<div>Предпочтительный срок кредита:</div>
				<div style="display: grid; grid-template-columns: auto auto auto auto; grid-gap: 5px;">
					<select>
						<option value="0">0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
					</select>
					<span>лет</span>
					<select>
						<option value="0">0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="4">5</option>
						<option value="4">6</option>
						<option value="4">7</option>
						<option value="4">8</option>
						<option value="4">9</option>
						<option value="4">10</option>
						<option value="4">11</option>
					</select>
					<span>месяцев</span>
				</div>
			</div>


			<div style="display: contents;">
				<div>Адрес:
					<div style="color: black; font-style: italic; font-size: 0.8em; " id="creditAddress"></div>
				</div>
				<div style="display: grid; grid-template-columns: auto auto; grid-gap: 10px;">
					<span>Статус помещения</span>
					<select><option>Собственность</option></select>
				</div>
			</div>

			<div style="display: contents;">
				<div>Наличие собственности:</div>
				<div>
					<input type="checkbox" id="propertyFlat"><label for="propertyFlat">Квартира в собственности</label>
				</div>
			</div>


			<div style="display: contents;">
				<div>Другая собственность:</div>
				<div>
					<input type="text">
				</div>
			</div>
			<div style="display: contents;">
				<div>Образование:</div>
				<div>
					<select></select>
				</div>
			</div>
			<div style="display: contents;">
				<div>Вид занятости:</div>
				<div>
					<select></select>
				</div>
			</div>

			<div style="display: contents;">
				<div>Должность:</div>
				<div>
					<input type="text">
				</div>
			</div>
			<div style="display: contents;">
				<div>Тип должности:</div>
				<div>
					<select></select>
				</div>
			</div>
			<div style="display: contents;">
				<div>В данной организации:</div>
				<div>
					<input type="date">
				</div>
			</div>
			<div style="display: contents;">
				<div>Срок работы в данной сфере</div>
				<div style="display: grid; grid-template-columns: auto auto auto auto; grid-gap: 5px;">
					<select>
						<?
						for ($n = 0; $n <= 50; $n++) {
							?><option value="<?= $n; ?>"><?= $n; ?></option><?
						}
						?>
					</select>
					<span>лет</span>
					<select>
						<option value="0">0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="4">5</option>
						<option value="4">6</option>
						<option value="4">7</option>
						<option value="4">8</option>
						<option value="4">9</option>
						<option value="4">10</option>
						<option value="4">11</option>
					</select>
					<span>месяцев</span>
				</div>
			</div>
			<div style="display: contents;">
				<div>Общий стаж работы</div>
				<div style="display: grid; grid-template-columns: auto auto auto auto; grid-gap: 5px;">
					<select>
						<?
						for ($n = 0; $n <= 50; $n++) {
							?><option value="<?= $n; ?>"><?= $n; ?></option><?
						}
						?>

					</select>
					<span>лет</span>
					<select>
						<option value="0">0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="4">5</option>
						<option value="4">6</option>
						<option value="4">7</option>
						<option value="4">8</option>
						<option value="4">9</option>
						<option value="4">10</option>
						<option value="4">11</option>
					</select>
					<span>месяцев</span>
				</div>
			</div>
			<div style="display: contents;">
				<div>Наименование работодателя:</div>
				<div>
					<input type="text">
				</div>
			</div>
			<div style="display: contents;">
				<div>Рабочий телефон:</div>
				<div>
					<input type="text">
				</div>
			</div>
			<div style="display: contents;">
				<div>Статус компании:</div>
				<div>
					<select></select>
				</div>
			</div>
			<div style="display: contents;">
				<div>Адрес работодателя:</div>
				<div>
					<input type="text">
				</div>
			</div>
			<div style="display: contents;">
				<div>Основной доход:</div>
				<div>
					<input type="text">
				</div>
			</div>
			<div style="display: contents;">
				<div>Расходы на жильё и комунальные платежи:</div>
				<div>
					<input type="text">
				</div>
			</div>
			<div style="display: contents;">
				<div>Суммарные ежемесячные расходы:</div>
				<div>
					<input type="text">
				</div>
			</div>

			<div style="display: contents;">
				<div>Контактные лица:</div>
				<div>
					<div style="display: grid; grid-gap: 5px; grid-template-columns: auto auto auto 160px;">

						<div>#</div>
						<div class="">Ф.И.О.</div>
						<div>Телефон</div>
						<div>Кем приходится</div>
						<div>1.</div>
						<input type="text">
						<input type="text">
						<input type="text">
						<div>2.</div>
						<input type="text">
						<input type="text">
						<input type="text">
					</div>


				</div>


			</div>

		</div>



	</div>
</div>