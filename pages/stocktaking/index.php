<?php
$pageTitle = 'оприходовать';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<!--<ul class="horisontalMenu">
	<li><a href="?">Пункт меню</a></li>
	<li><a href="?">Пункт меню</a></li>
	<li><a href="?">Пункт меню</a></li>
</ul>
<div class="divider"></div>-->
<script src="/pages/goods/loadUnits.js" type="text/javascript"></script>
<script src="/pages/goods/addItemsWindow.js" type="text/javascript"></script>


<div class="box neutral">
	<div class="box-body">
		<table style="color: black;">
			<tr>
				<td>Дата:</td>
				<td><input type="date" id="date" autocomplete="off" value="<?= date("Y-m-d"); ?>" onchange="if (this.value == '') {
							this.valueAsDate = new Date();
						}
						loadReportForDate(this.value);
						this.blur();"></td>
			</tr>
			<tr>
				<td>Наименование:</td>
				<td><input type="text" id="INitemName" autocomplete="off" readonly></td>
			</tr>
			<tr>
				<td>Штрих-код:</td>
				<td style="white-space: nowrap;"><input type="text" id="itemBarcode" readonly autocomplete="off"></td>
			</tr>
			<tr>
				<td>Единицы измерения:</td>
				<td><input type="text" id="itemUnit" readonly autocomplete="off"></td>
			</tr>
			<tr>
				<td>Количество:</td>
				<td>
					<table>
						<tr><td>Расчёт</td><td>Факт</td></tr>
						<tr>
							<td><input type="text" id="qtyCount" size="3" autocomplete="off" readonly></td>
							<td><input type="text" id="qtyFactual" size="3" autocomplete="off"></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td></td>
				<td style="text-align: right;"><button id="sbmbtn" onclick="clearForm();this.blur();"> Очистить </button> <button id="sbmbtn" onclick="sendData();this.blur();"> Добавить </button></td>
			</tr>
		</table>
	</div>

</div>

<div class="box white">
	<div class="box-body" id="dayReport"></div>
</div>
<script>
	let currentItem = {};
	let dataTOSend = {
		date: '',
		id: '',
		qty: ''
	};
</script>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
