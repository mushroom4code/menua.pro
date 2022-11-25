<?php
$pageTitle = 'Клиенты';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(32)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(32)) {
	?>E403R32<?
} else {
	?>
	<script src="/sync/3rdparty/canvasjs2.min.js" type="text/javascript"></script>
	<div class="box neutral">
		<div class="box-body">
			<form action="/pages/clients/index.php?add" method="post">
				<div style="border: 0px solid red; display: grid; grid-template-columns: auto auto; grid-gap: 5px; text-align: left;">
					<div style="display: contents">
						<div>Паспорт:</div>
						<div style="display: grid; grid-template-columns: auto auto auto auto; grid-gap: 5px;">
							<span>Серия:</span><input type="text"  name="passportSeries" style="width: 60px;">
							<span> Номер:</span><input type="text" name="passportNumber">
						</div>
					</div>
					<div style="display: contents">
						<div>Паспорт выдан:</div>
						<div><input id="passportUrisdiction" name="passportUrisdiction" type='text' placeholder="организация выдавшая паспорт"></div>
					</div>

					<div style="display: contents">
						<div>Дата выдачи:</div>
						<div><input id="passportDate" name="passportDate" type='date' placeholder=""></div>
					</div>
					<div style="display: contents">
						<div>Код подразеделения:</div>
						<div><input id="passportCode" name="passportCode" type='text' placeholder="xxx-xxx"></div>
					</div>
					<div style="display: contents">
						<div>Фамилия:</div>
						<div><input id="clientLName" name="clientLName" type='text' oninput="qs('#clientData').innerHTML = ((qs('#clientLName').value +' '+qs('#clientFName').value+' '+qs('#clientMName').value).trim() || 'Добавить');"></div>
					</div>
					<div style="display: contents">
						<div>Имя:</div>
						<div><input id="clientFName" name="clientFName" type='text' oninput="qs('#clientData').innerHTML = ((qs('#clientLName').value +' '+qs('#clientFName').value+' '+qs('#clientMName').value).trim() || 'Добавить');"></div>
					</div>
					<div style="display: contents">
						<div>Отчество:</div>
						<div><input id="clientMName" name="clientMName" type='text' oninput="qs('#clientData').innerHTML = ((qs('#clientLName').value +' '+qs('#clientFName').value+' '+qs('#clientMName').value).trim() || 'Добавить');"></div>
					</div>

					<div style="display: contents">
						<div>Пол:</div>
						<div style="display: grid; grid-template-columns: auto auto; text-align: center;">
							<div><input type="radio" name="gender" id="gender_f"><label for="gender_f"> Женский</label></div>
							<div><input type="radio" name="gender" id="gender_m"><label for="gender_m"> Мужской</label></div>
						</div>
					</div>
					<div style="display: contents">
						<div>Дата рождения:</div>
						<div><input id="" name="clientBDay" type='date' placeholder=""></div>
					</div>
					<div style="display: contents">
						<div>Место рождения:</div>
						<div><input id="" type='text' name="clientBPlace" placeholder="город/населенный пункт"></div>
					</div>
					<div style="display: contents">
						<div>Зарегистрирован по адресу:</div>
						<div><textarea style="width: 100%; resize: none;" name="clientAddressRegistered" oninput="qs('#creditAddress').innerHTML=this.value;"></textarea></div>
					</div>
					<div style="display: contents">
						<div>Фактическое место проживания:<br>
							<input type="checkbox" id="fmp"><label for="fmp"><small>Совпадает с пропиской</small></label></div>
						<div><textarea style="width: 100%; resize: none;"  name="clientAddressReal"></textarea></div>
					</div>

					<div style="display: contents">
						<div>Телефоны:</div>
						<div style="display: grid; grid-template-columns: auto auto; grid-gap: 5px;" id="phones">
							<div style="display: contents;">
								<input type="text" name="phone[0][number]">
								<select name="phone[0][type]">
									<option></option>
									<option>Домашний</option>
									<option>Мобильный</option>
									<option>Рабочий</option>
								</select>
							</div>
							<div style="display: contents;">
								<input type="text" name="phone[1][number]">
								<select name="phone[1][type]">
									<option></option>
									<option>Домашний</option>
									<option>Мобильный</option>
									<option>Рабочий</option>
								</select>
							</div>
							<div style="display: contents;">
								<input type="text" name="phone[2][number]">
								<select name="phone[2][type]">
									<option></option>
									<option>Домашний</option>
									<option>Мобильный</option>
									<option>Рабочий</option>
								</select>
							</div>
							<div style="display: contents;">
								<input type="text" name="phone[3][number]">
								<select name="phone[3][type]">
									<option></option>
									<option>Домашний</option>
									<option>Мобильный</option>
									<option>Рабочий</option>
								</select>
							</div>
							<div style="display: contents;">
								<input type="text" name="phone[4][number]">
								<select name="phone[4][type]">
									<option></option>
									<option>Домашний</option>
									<option>Мобильный</option>
									<option>Рабочий</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div style="text-align: right; padding: 30px;"><input id="" type='submit' value="Сохранить"></div>
			</form>

		</div>
	</div>


<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
