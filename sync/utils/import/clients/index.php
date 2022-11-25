<?php
$pageTitle = 'Импорт клиентов';
die();
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if ($_USER['id'] == 176) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {
	die();

	mysqlQuery("delete FROM vita.clients;");
	mysqlQuery("ALTER TABLE vita.clients AUTO_INCREMENT = 1;");
	$json = json_decode(file_get_contents("clients.json"), true);
	$clients = $json['clients'];
	printr($clients[17]);
	$oldClients = query2array(mysqlQuery("SELECT * FROM `clients` WHERE isnull(`GUID`);"));
	printr($oldClients[0]);
	?>
	<div class="box neutral">
		<div class="box-body">
			<table style="font-family: courier;">
				<tr>
					<td></td>
					<td>GUID</td>
					<td>Фамилия</td>
					<td>Имя</td>
					<td>Отчество</td>
					<td>Дата рождения</td>
					<td>Номер телефона</td>
				</tr>
				<?
				foreach ($clients as $client) {

					$name = explode(' ', trim(preg_replace('/\s+/', ' ', $client['ФИО'])));
					$bTime = strtotime($client['Дата рождения']);
					$bdate = date("Y-m-d", $bTime);
					///
					$lname = mb_ucfirst($name[0] ?? '');
					$fname = mb_ucfirst($name[1] ?? '');
					$mname = mb_ucfirst($name[2] ?? '');

					$phone = preg_replace("/[^0-9]/", "", trim($client['Номер телефона']) ?? '');
					$phones = [];
					$invalidphones = [];
					if (strlen($phone) == 11) {
						$phone[0] = '8';
						$phones[] = $phone;
					} elseif (strlen($phone) == 10) {
						$phone = '8' . $phone;
						$phones[] = $phone;
					} elseif (strlen($phone) == 7) {
						$phones[] = $phone;
					} elseif (strlen($phone) == 22) {
						$phones[] = '8' . substr($phone, 1, 10);
						$phones[] = '8' . substr($phone, 12, 10);
					} elseif ($phone) {
						$invalidphones[] = $phone;
					}

					$phone = preg_replace("/[^0-9]/", "", trim($client['Мобильный']) ?? '');

					if (strlen($phone) == 11) {
						$phone[0] = '8';
						$phones[] = $phone;
					} elseif (strlen($phone) == 10) {
						$phone = '8' . $phone;
						$phones[] = $phone;
					} elseif (strlen($phone) == 7) {
						$phones[] = $phone;
					} elseif (strlen($phone) == 22) {
						$phones[] = '8' . substr($phone, 1, 10);
						$phones[] = '8' . substr($phone, 12, 10);
					} elseif ($phone) {
						$invalidphones[] = $phone;
					}


					///
					?>
					<tr>

						<td>

							<?
							$update = false;
							foreach ($oldClients as $oldClient) {

								if (
										mb_strtolower(trim($oldClient['clientsLName'])) == mb_strtolower(trim($lname)) &&
										mb_strtolower(trim($oldClient['clientsFName'])) == mb_strtolower(trim($fname)) &&
										mb_strtolower(trim($oldClient['clientsMName'])) == mb_strtolower(trim($mname)) &&
										$oldClient['clientsBDay'] == $bdate
								) {
									$update = true;
									if (1) {
										mysqlQuery("UPDATE `clients` SET"
												. " `GUID` = '" . $client['GUID'] . "',"
												. " `clientsAKNum` = '" . $client['Номер амбулаторной карты'] . "'"
												. ""
												. " WHERE `idclients` = '" . $oldClient['idclients'] . "'");
									}
									?>#F#F<?
								}
							}
							if (1 && !$update) {

								mysqlQuery("INSERT INTO `clients` SET "
										. "`GUID` = '" . $client['GUID'] . "',"
										. " `clientsLName` = " . ($lname ? ("'" . $lname . "'") : "null") . ","
										. " `clientsFName` = " . ($fname ? ("'" . $fname . "'") : "null") . ","
										. "`clientsMName` = " . ($mname ? ("'" . $mname . "'") : "null") . ","
										. "`clientsBDay` = " . ($bdate ? ("'" . $bdate . "'") : "null") . ","
										. "`clientsAKNum` = " . ($client['Номер амбулаторной карты'] ? ("'" . $client['Номер амбулаторной карты'] . "'") : "null") . ","
										. "`clientsAddedBy` = '" . $_USER['id'] . "', "
										. "`clientsIsNew` = '1'"
										. " ");
								$insertedUserId = mysqli_insert_id($link);

								foreach ($phones as $phone) {
									mysqlQuery("INSERT INTO `clientsPhones` SET "
											. "`clientsPhonesClient` = '" . $insertedUserId . "', "
											. "`clientsPhonesPhone` = '" . $phone . "'"
											. "");
								}
								foreach ($invalidphones as $phone) {
									mysqlQuery("INSERT INTO `clientsPhones` SET "
											. "`clientsPhonesClient` = '" . $insertedUserId . "', "
											. "`clientsPhonesPhone` = '" . $phone . "', "
											. "`clientsPhonesInvalid` = '1'"
											. "");
								}

								$passport = $client['Паспортные данные'];
								$passportSQL = [];
								if ($passport['Серия'] . ' ' . $passport['Номер'] != ' ') {
									$passportSQL = "`clientsPassportNumber`='" . $passport['Серия'] . ' ' . $passport['Номер'] . "'";
								}

								if ($client['Адрес проживания'] ?? '') {
									$passportSQL = "`clientsPassportsResidence`='" . mysqli_real_escape_string($link, $client['Адрес проживания']) . "'";
								}

								if ($passport['Кем выдан'] ?? '') {
									$passportSQL = "`clientsPassportsDepartment`='" . mysqli_real_escape_string($link, $client['Кем выдан']) . "'";
								}
								$paspDate = strtotime($passport['Дата выдачи']);

								if ($paspDate > strtotime("1900-01-01 00:00:00")) {
									$passportSQL = "`clientsPassportsDate`='" . date("Y-m-d", $paspDate) . "'";
								}
								if (count($passportSQL)) {
									mysqlQuery("INSERT INTO `clientsPassports` SET `clientsPassportsClient`='" . $insertedUserId . "'," . implode(",", $passportSQL));
								}
							}
							?>



						</td>
						<td>


							<?= $client['GUID']; ?>


						</td>
						<td><?= $lname ? $lname : '<i class="fas fa-exclamation-triangle" style="color: red;"></i>'; ?></td>
						<td><?= $fname ? $fname : '<i class="fas fa-exclamation-triangle" style="color: red;"></i>'; ?></td>
						<td><?= $mname ? $mname : '<i class="fas fa-exclamation-triangle" style="color: red;"></i>'; ?></td>


						<td><? if ($bTime > strtotime("2000-01-01 00:00:00")) { ?>
								<i class="fas fa-exclamation-triangle" style="color: orange;"></i>
							<? }
							?><? if ($bTime > strtotime("1900-01-01 00:00:00")) { ?>
								<?= date("Y.m.d", $bTime); ?>
							<? } else { ?>
								<i class="fas fa-exclamation-triangle" style="color: red;"></i>
							<? }
							?> </td>


						<td>
							<?
							if (count($invalidphones)) {
//								var_dump($invalidphones);
								print '<i class="fas fa-exclamation-triangle" style="color: red;"></i>';
							}
							?>
						</td>
					</tr>

					<?
				}
				?>


			</table>
		</div>
	</div>

<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
