<?php
ini_set('memory_limit', '-1');
$pageTitle = 'Импорт пройденных процедур';
header('Content-Encoding: none;');

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
//die('7');
if ($_USER['id'] == 176) {

}
$totalToInsert = 0;
die('выключено'); 
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if ($_USER['id'] != 176) {
	?>!176<?
} else {
//	die('not now');
	$servicesapplied = [];
//	die();
	$servicesapplied = array_merge(
			json_decode(file_get_contents("free.json"), true)['Бесплатные процедуры'],
			json_decode(file_get_contents("servisesApplied.json"), true)['Прохождения процедур']
	);

	$BANKGUIDS = [];
	$start = microtime(1);
	mysqlQuery("DELETE FROM servicesApplied;");
	mysqlQuery("ALTER TABLE servicesApplied AUTO_INCREMENT = 1;");
	?>
	<style>
		H3 {
			line-height: 2em;
		}
		.sq {
			display: inline-block;
			width: 6px;
			height: 6px;
			margin: 1px;
		}
		.ok {
			background-color: green;
		}
		.err {
			background-color: red;
		}

		.warn {
			background-color: orange;
		}
		.wrapper {
			line-height: 8px;
		}
		.box {
			margin: 0.0em 1.2em 1.0em 1.2em;
		}
	</style>


	<div class="box neutral">
		<div class="box-body">
			<h2>Импорт Прохождения процедур</h2>
			<? $start = microtime(1); ?>
			<div class="wrapper">
				<?
				$clients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'GUID');
				$services = query2array(mysqlQuery("SELECT * FROM `warehouse`.`services` left join `warehouse`.`servicesGUIDs` on (`servicesGUIDsService`=`idservices`) where not isnull(`servicesGUIDsGUID`);"), 'servicesGUIDsGUID');
				$f_sales = query2array(mysqlQuery("SELECT `idf_sales`,`f_salesGUID` FROM `f_sales`"), 'f_salesGUID');
				$users = query2array(mysqlQuery("SELECT * FROM `users`"), 'usersGUID');
//				printr($users);

				$noguids = 0;
				$withguids = 0;
				$head = "INSERT INTO `servicesApplied` ("
						. "`servicesAppliedService`,"
						. "`servicesAppliedQty`,"
						. "`servicesAppliedClient`,"
						. "`servicesAppliedBy`,"
						. "`servicesAppliedDate`,"
						. "`servicesAppliedTimeBegin`,"
						. "`servicesAppliedStarted`,"
						. "`servicesAppliedStartedBy`,"
						. "`servicesAppliedTimeEnd`,"
						. "`servicesAppliedFineshed`,"
						. "`servicesAppliedFinishedBy`,"
						. "`servicesAppliedContract`,"
						. "`servicesAppliedPrice`,"
						. "`servicesAppliedPersonal`,"
						. "`servicesAppliedComment`"
						. ") VALUES ";

				$insert = [];
				foreach ($servicesapplied as $serviceapplied) {
//				printr($client);$serviceapplied["GUIDОператора"]

					if (!($serviceapplied["GUIDКосметолога"] ?? false) || !($serviceapplied["GUIDОператора"] ?? false) || in_array(($serviceapplied["GUIDКосметолога"] ?? false),
									[
										'810618a8-1265-11e5-a76b-002590649803',
										'88ab7353-fa31-11e5-b339-e840f20ab9f2'
							])) {
						continue;
					}

					$personnel = ($users[$serviceapplied["GUIDКосметолога"]]['idusers'] ?? false);
					if (!$personnel) {
//						print "<H3>'" . $serviceapplied["GUIDКосметолога"] . "' -> ";
						$nameArr = explode(' ', trim(preg_replace('!\s+!', ' ', $serviceapplied["Косметолог"])));
//						printr($nameArr);
						$user = query2array(mysqlQuery("SELECT * FROM `users` WHERE "
										. " `usersLastName` = '" . trim($nameArr[0]) . "'"
										. " AND `usersFirstName` = '" . trim($nameArr[1] ?? '') . "'"));
//						print "'" . $user[0]['usersGUID'] . "'</H3>";
						if (!count($user)) {
							mysqlQuery("INSERT INTO `users` SET"
									. "`usersLastName` = '" . trim($nameArr[0] ?? '') . "', "
									. "`usersFirstName` = '" . trim($nameArr[1] ?? '') . "', "
									. "`usersMiddleName` = '" . trim($nameArr[2] ?? '') . "',"
									. " `usersGUID` = '" . $serviceapplied["GUIDКосметолога"] . "'"
									. "");
						} elseif (count($user) === 1) {
							mysqlQuery("UPDATE `users` SET "
									. " `usersGUID` = '" . $serviceapplied["GUIDКосметолога"] . "'"
									. " WHERE "
									. "`idusers` = '" . $user[0]['idusers'] . "'"
									. "");
						} else {
							printr($user);
							die($serviceapplied["GUIDКосметолога"] . "<br>" . $serviceapplied["Косметолог"]);
						}
//						print '1' . $serviceapplied["Косметолог"] . '<br><br>';
						$users = query2array(mysqlQuery("SELECT * FROM `users`"), 'usersGUID');
						$personnel = ($users[$serviceapplied["GUIDКосметолога"]]['idusers'] ?? false);
//						die($serviceapplied["GUIDКосметолога"] . "\t" . $serviceapplied["Косметолог"]);
					}

					$operator = ($users[$serviceapplied["GUIDОператора"]]['idusers'] ?? false);
					if (!$operator) {
//						print "<H3>'" . $serviceapplied["GUIDОператора"] . "' -> ";
						$nameArr = explode(' ', trim(preg_replace('!\s+!', ' ', $serviceapplied["Оператор"])));
//						printr($nameArr);
						$user = query2array(mysqlQuery("SELECT * FROM `users` WHERE `usersLastName` = '" . $nameArr[0] . "'"
										. " AND `usersFirstName` = '" . trim($nameArr[1] ?? '') . "'"
										. ""));
//						print "'" . $user[0]['usersGUID'] . "'</H3>";
						if (!count($user)) {
							mysqlQuery("INSERT INTO `users` SET"
									. "`usersLastName` = '" . ($nameArr[0] ?? '') . "', "
									. "`usersFirstName` = '" . ($nameArr[1] ?? '') . "', "
									. "`usersMiddleName` = '" . ($nameArr[2] ?? '') . "',"
									. " `usersGUID` = '" . $serviceapplied["GUIDОператора"] . "'"
									. "");
						} elseif (count($user) === 1) {
							mysqlQuery("UPDATE `users` SET "
									. " `usersGUID` = '" . $serviceapplied["GUIDОператора"] . "'"
									. " WHERE "
									. "`idusers` = '" . $user[0]['idusers'] . "'"
									. "");
						} else {
							printr($user);
							die($serviceapplied["GUIDОператора"] . "<br>156;" . $serviceapplied["Оператор"]);
						}
//						print '2' . $serviceapplied["Оператор"] . '(' . $serviceapplied["GUIDОператора"] . ')' . '<br><br>';
						$users = query2array(mysqlQuery("SELECT * FROM `users`"), 'usersGUID');
						$operator = ($users[$serviceapplied["GUIDОператора"]]['idusers'] ?? false);
					}



					if ($serviceapplied['GUIDКлиента']) {

						if (!($serviceapplied['GUIDДоговора'] ?? false)) {
							$noguids++;
//							print '<div class="sq warn" title="нет GUID договора"></div>';
							$isf_sales = null;
						} else {
							$isf_sales = ($f_sales[$serviceapplied['GUIDДоговора']]['idf_sales'] ?? false);
							if (!$isf_sales) {
								print '<div class="sq err" title="нет idf_sales" onclick="alert(\'' . $serviceapplied['GUIDДоговора'] . '\');"></div>';
								continue;
							}
						}


						if (!($serviceapplied['GUIDКлиента'] ?? false)) {
							print '<div class="sq err" title="нет GUID клиента"></div>';
							continue;
						}
						$idclients = $clients[$serviceapplied['GUIDКлиента']]['idclients'] ?? false;
						if (!$idclients) {
							print '<div class="sq err" title="нет ID клиента"></div>';
							continue;
						}


						if (!($serviceapplied['GUIDПроцедуры'] ?? false)) {
							print '<div class="sq err" title="нет GUIDПроцедуры"></div>';
							continue;
						}
						$idservices = ($services[$serviceapplied['GUIDПроцедуры']]['idservices'] ?? false);
						if (!$idservices) {
							mysqlQuery("INSERT INTO `services` SET `servicesName`='" . mres(($serviceapplied['Процедура'] ?? false) ? $serviceapplied['Процедура'] : 'ПУСТАЯ СТРОКА') . "'");
							$insertid = mysqli_insert_id($link);
							mysqlQuery("INSERT INTO `warehouse`.`servicesGUIDs` SET `servicesGUIDsService`='" . $insertid . "', `servicesGUIDsGUID`='" . $serviceapplied['GUIDПроцедуры'] . "'");
							$idservices = $insertid;
							$services = query2array(mysqlQuery("SELECT * FROM `warehouse`.`services` left join `warehouse`.`servicesGUIDs` on (`servicesGUIDsService`=`idservices`) where not isnull(`servicesGUIDsGUID`);"), 'servicesGUIDsGUID');
						}

						$qty = ($serviceapplied['Выполнено'] ?? false);
						if (!$qty) {
							print '<div class="sq warn" title="нет количество =0" onclick="alert(\'' . $serviceapplied['GUIDДокументаПрохождения'] . '\');"></div>';
							continue;
						}
//							printr($replacement);
//								print '<div class="sq ok"></div>';

						$insert[] = "("
								. "'" . $idservices . "',"
								. "'" . $qty . "',"
								. "'" . $idclients . "',"
								. "'" . $operator . "',"
								. "'" . date("Y-m-d", strtotime($serviceapplied['Дата прохождения'])) . "',"
								. "'" . date("Y-m-d H:i:s", strtotime($serviceapplied['Дата прохождения'])) . "',"
								. "'" . date("Y-m-d H:i:s", strtotime($serviceapplied['Дата прохождения'])) . "',"
								. "176,"
								. "'" . date("Y-m-d H:i:s", 60 * ($services[$serviceapplied['GUIDПроцедуры']]['servicesDuration'] ?? 60) + strtotime($serviceapplied['Дата прохождения'])) . "',"
								. "'" . date("Y-m-d H:i:s", 60 * ($services[$serviceapplied['GUIDПроцедуры']]['servicesDuration'] ?? 60) + strtotime($serviceapplied['Дата прохождения'])) . "',"
								. "176,"
								. ($isf_sales ? ("'" . $isf_sales . "',") : 'null,')
								. "'" . ($serviceapplied['Цена списания'] ?? '0') . "',"
								. "'" . $personnel . "',"
								. "" . sqlVON($serviceapplied["КомментарийПроц"]) . ""
								. ")";

//							[GUIDПроцедуры] => 6ae14b6c-ecb1-11e4-aba5-002590649803

						if (count($insert) >= 150) {
							$totalToInsert += count($insert);
//							print $head . implode(',', $insert) . '<br><br><br><br>';
							if (mysqlQuery($head . implode(',', $insert))) {
								print '<div class="sq ok" title="150"></div>';
							} else {
								print '<div class="sq warn" title="MYSQL ERROR"></div>';
								die(mysqli_error($link));
							}
							$insert = [];
						}
						for ($n = 0; $n <= 10; $n++) {
							print '<!--                                                                                                    -->';
						}
						flush();
					} else {
						print '<div class="sq err" title="NO GUIDКлиента"></div>';
					}
				}
				if (count($insert)) {
					$totalToInsert += count($insert);
					if (mysqlQuery($head . implode(',', $insert))) {
						print '<div class="sq ok" title="???"></div>';
					} else {
						print '<div class="sq warn" title="MYSQL ERROR"></div>';
					}
				}
				mysqlQuery("SELECT `idservicesApplied` AS `servicesAppliedCommentsSA`,`servicesAppliedComment` as `servicesAppliedCommentText` FROM `servicesApplied` WHERE NOT isnull(`servicesAppliedComment`) AND `servicesAppliedComment`<>'';");
				?>

			</div>
			<h4>Завершено за: <?= round((microtime(1) - $start), 2); ?> Должно быть <?= $totalToInsert; ?> процедур</h4>
		</div>
	</div>

	<?
}
print 'PGT:' . (microtime(1) - $start);
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
