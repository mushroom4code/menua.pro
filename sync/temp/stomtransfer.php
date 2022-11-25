<?php
$pageTitle = 'Перенос стомы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if ($_POST && ($_GET['sale'] ?? false) && ($sale = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales` = " . sqlVON($_GET['sale'], 1) . "")))) {
	$f_salesSumm = 0;
	foreach ($_POST['oldservice'] as $key => $serviceid) {
		$f_salesSumm += $_POST['qty'][$key] * $_POST['newprice'][$key];
		if ($_POST['qty'][$key]) {
			mysqlQuery("INSERT INTO `f_subscriptions` SET"
					. " `f_subscriptionsContract` = '" . $sale['idf_sales'] . "',"
					. " `f_salesContentService`='$serviceid',"
					. " `f_salesContentPrice` = " . intval($_POST['oldprice'][$key]) . ","
					. " `f_salesContentQty` = -" . intval($_POST['qty'][$key]) . ","
					. " `f_subscriptionsDate` = CURDATE(),"
					. " `f_subscriptionsUser`= " . $_USER['id'] . " ");
		}
	}


	mysqlQuery("INSERT INTO `f_sales` SET "
			. "`f_salesNumber` = (SELECT * FROM (SELECT IF(isnull((SELECT MAX(f_salesNumber) FROM `f_sales` WHERE `f_salesClient`=" . $sale['f_salesClient'] . " AND `f_salesEntity`= 1 AND NOT isnull(`f_salesIsAppendix`))),2,(SELECT MAX(f_salesNumber) FROM `f_sales` WHERE `f_salesClient`=" . $sale['f_salesClient'] . " AND NOT isnull(`f_salesIsAppendix`))+1)) as `tmp`), "
			. " `f_salesCreditManager` = " . sqlVON($sale['f_salesCreditManager'] ?? $_USER['id']) . ", "
			. " `f_salesClient` = " . $sale['f_salesClient'] . ","
			. " `f_salesSumm` = " . $f_salesSumm . ","
			. " `f_salesComment` = " . sqlVON(($sale['f_salesComment'] . '. Перенос абонемента из Инфинити-СТОМ')) . ","
			. " `f_salesDate` = CURDATE(),"
			. " `f_salesType` = " . $sale['f_salesType'] . ", "
			. " `f_salesEntity` = 1,"
			. " `f_salesIsAppendix` = 1,"
			. " `f_salesAdvancePayment` = " . sqlVON($sale['f_salesAdvancePayment']) . "");

	$newsaleid = mysqli_insert_id($link);

	foreach ($_POST['oldservice'] as $key => $serviceid) {
		$f_salesSumm = $_POST['qty'][$key] * $_POST['newprice'][$key];
		if ($_POST['qty'][$key]) {
			mysqlQuery("INSERT INTO `f_subscriptions` SET"
					. " `f_subscriptionsContract` = '" . $newsaleid . "',"
					. " `f_salesContentService`='$serviceid',"
					. " `f_salesContentPrice` = " . intval($_POST['newprice'][$key]) . ","
					. " `f_salesContentQty` = " . intval($_POST['qty'][$key]) . ","
					. " `f_subscriptionsDate` = CURDATE(),"
					. " `f_subscriptionsUser`= " . $_USER['id'] . " ");
		}
	}

	mysqlQuery("INSERT INTO `f_salesReplacementComments` SET "
			. " `f_salesReplacementCommentsContract`= '" . $sale['idf_sales'] . "',"
			. " `f_salesReplacementCommentsDate` = CURDATE(),"
			. " `f_salesReplacementCommentsText` = 'перенос абонемента на Инфинити. Новый номер ($newsaleid)'");
	mysqlQuery("INSERT INTO `f_salesReplacementComments` SET "
			. " `f_salesReplacementCommentsContract`= '" . $newsaleid . "',"
			. " `f_salesReplacementCommentsDate` = CURDATE(),"
			. " `f_salesReplacementCommentsText` = 'перенос абонемента из Инфинити-Стом. Старый номер (" . $sale['idf_sales'] . ")'");
	mysqlQuery("INSERT INTO `f_salesToPersonal` (`f_salesToPersonalSalesID`, `f_salesToPersonalUser`) "
			. " SELECT $newsaleid as `f_salesToPersonalSalesID`, `f_salesToPersonalUser` FROM `f_salesToPersonal` WHERE `f_salesToPersonalSalesID` =" . $sale['idf_sales'] . ";  "
			. "");
	mysqlQuery("INSERT INTO `f_salesRoles`  (`f_salesRolesSale`,`f_salesRolesUser`, `f_salesRolesRole`) "
			. " SELECT $newsaleid as `f_salesRolesSale`,`f_salesRolesUser`, `f_salesRolesRole` FROM "
			. " `f_salesRoles` WHERE `f_salesRolesSale` =" . $sale['idf_sales'] . " ");
	mysqlQuery("INSERT INTO `f_balance` SET"
			. " `f_balanceSalesID` =" . $sale['idf_sales'] . ","
			. " `f_balanceAmount`=-" . floatval($_POST['balance']) . ","
			. " `f_balanceTime` = NOW(),"
			. " `f_balanceUser` = " . $_USER['id'] . ","
			. " `f_balanceClient` = " . $sale['f_salesClient'] . "");
	mysqlQuery("INSERT INTO `f_balance` SET"
			. " `f_balanceSalesID` =" . $newsaleid . ","
			. " `f_balanceAmount`=" . floatval($_POST['balance']) . ","
			. " `f_balanceTime` = NOW(),"
			. " `f_balanceUser` = " . $_USER['id'] . ","
			. " `f_balanceClient` = " . $sale['f_salesClient'] . "");

	//idf_subscriptions,  f_subscriptionsExpDate, f_subscriptionsImport
	header("Location: " . GR());
	exit('ok');
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>

<div class="box neutral">
	<div class="box-body">
		<form action="?" method="get">
			<input type="text" style="display: inline-block; width: auto;" placeholder="Номер абона" name="sale" value="<?= ($_GET['sale'] ?? ''); ?>"><input type="submit" value="загрузить">
		</form>
		<?
		if (($_GET['sale'] ?? false) && ($sale = mfa(mysqlQuery("SELECT * FROM `f_sales` WHERE `idf_sales` = " . sqlVON($_GET['sale'], 1) . "")))) {
			$remains = getRemainsBySale($sale['idf_sales']);
			?>
			<form action="<?= GR(); ?>" method="POST" id="remains" oninput="calc(this);">
				<table border="1" style="margin: 30px; border-collapse: collapse;">
					<tr>
						<th style="width: 70px;">id</th>
						<th>Процедура</th>
						<th>старая цена</th>
						<th style="width: 40px;">К-во</th>
						<th>новая цена</th>
						<th>Сумма</th>
					</tr>

					<?
					$n = 0;
					$total = 0;
					foreach ($remains as $remain) {
						if (!$remain['f_salesContentQty']) {
							continue;
						}
//						printr($remain);
						?>
						<tr>
							<td><input autocomplete="off" type="text" readonly name="oldservice[<?= $n; ?>]" value="<?= $remain['f_salesContentService']; ?>"></td>
							<td><?= $remain['servicesName']; ?></td>
							<td><input autocomplete="off" type="text" name="oldprice[<?= $n; ?>]" readonly value="<?= $remain['f_salesContentPrice']; ?>"></td>
							<td><input autocomplete="off" type="text" name="qty[<?= $n; ?>]" style="width: 40px; text-align: center;" value="<?= $remain['f_salesContentQty']; ?>" max="<?= $remain['f_salesContentQty']; ?>" min="0"></td>
							<td><input autocomplete="off" type="text" name="newprice[<?= $n; ?>]" value="<?= $remain['f_salesContentPrice']; ?>"></td>
							<td><input autocomplete="off" type="text" readonly value="<?= $remain['f_salesContentQty'] * $remain['f_salesContentPrice']; ?>"></td>
						</tr>
						<?
						$total += $remain['f_salesContentQty'] * $remain['f_salesContentPrice'];
						$n++;
					}
					?>
					<tr>
						<td colspan="5"></td>
						<td><input type="text" id="summary" autocomplete="off" value="<?= $total; ?>"></td>
					</tr>
				</table>
				Сумма к переносу <input type="text" required name="balance" style="display: inline-block; width: auto;">
				<?
			}
			?>
			<input type="submit" value="Перенести">
		</form>
		<script>
			function calc(form) {
				let prices = document.querySelectorAll('input[name*=newprice]');
				let qty = document.querySelectorAll('input[name*=qty]');
				let total = 0;
				for (var i = 0; i < prices.length; i++) {
					total += prices[i].value * qty[i].value;
				}
				document.querySelector(`#summary`).value = total;
			}
		</script>




	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
