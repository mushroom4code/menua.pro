<?php
$load['title'] = $pageTitle = 'Импорт процедур';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$_GET['parent'] = $_GET['parent'] ?? 1;

function getBranch($branch_id, $arrayToWalk, $id_name, $leafsColumn = 'content') {
	foreach ($arrayToWalk as $arrayElement) {
		if ($arrayElement[$id_name] == $branch_id) {
			return $arrayElement;
		} else {
			if (is_array($arrayElement[$leafsColumn] ?? false)) {
				$result = getBranch($branch_id, $arrayElement[$leafsColumn], $id_name, $leafsColumn);
				if ($result) {
					return $result;
				}
			}
		}
	}
}

/*            "idservices": 2029,
  "servicesParent": 1925,
  "servicesCode": 1,
  "servicesName": "Ультразвуковое исследование надпочечников",
  "serviceNameShort": null,
  "servicesBasePrice2": null,
  "servicesCost2": null,
  "servicesType": null,
  "servicesDeleted": null,
  "servicesEquipment": null,
  "servicesDuration": 30,
  "servicesURL": "",
  "servicesAdded": "2021-06-12 17:37:51",
  "servicesEquipped": null,
  "servicescolN804": "A04.22.002 ",
  "servicesSupplierCode": null,
  "minPrice": 1200 */
$n = 0;

function flatter($array) {
	global $n;
	$outArray = [];
	//${rownumber}	${n804}	${name}	${price} 
//	printr($array);
	foreach ($array as $arrayElement) {
		$n++;
		if (is_array($arrayElement['content'] ?? false)) {
			$outArray[] = [
				'rownumber' => $n,
				'n804' => '',
				'name' => $arrayElement['servicesName'],
				'price' => ''
			];
			$outArray = array_merge($outArray, flatter($arrayElement['content']));
		} else {
//			printr($arrayElement);

			$outArray[] = [
				'rownumber' => $n,
				'n804' => $arrayElement['servicescolN804'],
				'name' => $arrayElement['servicesName'],
				'price' => $arrayElement['minPrice']
			];
		}
	}


	return $outArray;
}

if (($_GET['save'] ?? false)) {
	require $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/vendor/phpoffice/phpword/bootstrap.php';
	$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/templates/pricetemplate.docx');
//	printr($_SESSION);
	$data = [
		"entityName" => "Инфинити",
		"entityDirName" => "Горшкова Р.Р.",
		"date" => date("d.m.Y"),
	];

	foreach ($data as $variable => $value) {
		$templateProcessor->setValue($variable, $value);
	}
	$services = adjArr2obj(query2array(mysqlQuery("SELECT *"
							. " ,(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `minPrice`"
							. " FROM `services`"
							. " WHERE isnull(`servicesDeleted`)")), 'idservices', 'servicesParent');
	$branch = getBranch($_GET['parent'], $services, 'idservices', 'content');
//	printr($branch);
	$rows = flatter([$branch]);
	$templateProcessor->cloneRowAndSetValues('rownumber', $rows);

	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename="' . date("Y.m.d") . '.docx"');
	header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
	header('Content-Transfer-Encoding: binary');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Expires: 0');

	$templateProcessor->saveAs('php://output');
	die();
}

$up = mfa(mysqlQuery("SELECT `servicesParent` FROM `services` WHERE `idservices`='" . mres($_GET['parent']) . "'"))['servicesParent'] ?? 1;
$prnt = $_GET['parent'];
if ($_POST['textdata'] ?? false) {
	$rows = explode("\r\n", $_POST['textdata']);
	foreach ($rows as &$row2) {
		$row2 = explode("\t", $row2);
		if (($row2[0] ?? '') !== '' && ($row2[1] ?? '') !== '') {
			$query = "INSERT INTO `services` SET "
					. "`servicescolN804`='" . mres($row2[0]) . "',"
					. "`servicesName` = '" . mres($row2[1]) . "',"
					. "`servicesParent` = '" . mres($_GET['parent']) . "'";
			mysqlQuery($query);
		}
	}
	header("Location: " . GR());
	die();
}

function getServicesCode($service) {
	$code = $service['servicesCode'] ?? '00';
	$prnt = $service['servicesParent'];
	while ($prnt && !($prnt == 1)) {
		$bc = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices`='" . mres($prnt) . "'"));
		$prnt = $bc['servicesParent'];
		$code = ($bc['servicesCode'] ?? '00') . $code;
	}
	return $code;
}

$services = query2array(mysqlQuery("SELECT *"
				. " ,(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='1') AND `servicesPricesType`='1'  AND `servicesPricesService` = `idservices`)) as `minPrice`"
				. ",(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='2') AND `servicesPricesType`='2'  AND `servicesPricesService` = `idservices`)) as `maxPrice`"
				. ",(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='3') AND `servicesPricesType`='3'  AND `servicesPricesService` = `idservices`)) as `minCost`"
				. ",(SELECT `servicesPricesPrice` FROM `servicesPrices` WHERE `idservicesPrices` = (SELECT `idservicesPrices` FROM `servicesPrices` WHERE `servicesPricesDate` = (SELECT MAX(`servicesPricesDate`) FROM  `servicesPrices` WHERE `servicesPricesService` = `idservices` AND `servicesPricesType`='4') AND `servicesPricesType`='4'  AND `servicesPricesService` = `idservices`)) as `maxCost`"
				. " FROM `services`"
				. " WHERE `servicesParent`='" . mres($_GET['parent']) . "'"));

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
	<div class="box-body">
		<!--<a href="<?= GR2(['parent' => $up]); ?>">Назад</a>-->
		<div style="padding: 15px; font-size: 1.4em;">
			<?
			$bctext = '';
			while ($prnt) {
				$bc = mfa(mysqlQuery("SELECT * FROM `services` WHERE `idservices`='" . mres($prnt) . "'"));
				$prnt = $bc['servicesParent'];
				$bctext = '<a href="' . GR2(['parent' => $bc['idservices']]) . '">' . $bc['servicesName'] . '</a> | ' . $bctext;
			}
			print $bctext;
			?>
			<a href="<?= GR2(['save' => true]); ?>" style="float: right;"><i class="fas fa-save"></i></a>
		</div>
		<hr style="display: block;"><!-- comment -->
		<div class="lightGrid" style="display: grid; grid-template-columns: repeat(5,auto); margin: 20px;">
			<div style="display: contents;">
				<div></div>
				<div class="B C">N804</div>
				<div class="B C">Внутренний<br>Код</div>
				<div class="B C">Наименование</div>
				<div class="B C">Цена</div>
			</div>

			<?
			foreach ($services as $service) {
				?>

				<div style="display: contents;">
					<div  class="C"><?
						if (!$service['servicesCode']) {
							print '&gt;';
							$new = sprintf('%02d', intval(mfa(mysqlQuery("SELECT max(`servicesCode`) as `mx` FROM `services` WHERE `servicesParent`='" . mres($_GET['parent']) . "'"))['mx']) + 1);
							mysqlQuery("UPDATE `services` SET `servicesCode`='" . $new . "' WHERE `idservices` = '" . $service['idservices'] . "'");
							$service['servicesCode'] = $new;
						}
						?></div>
					<div><?= $service['servicescolN804']; ?></div>
					<div  class="C"><a target="_blank" href="/pages/services/index.php?service=<?= $service['idservices']; ?>"><?= getServicesCode($service); ?></a></div>
					<div>
						<? if (!$service['servicescolN804']) { ?>
							<a href="<?= GR2(['parent' => $service['idservices']]); ?>">
							<? } else { ?>
								<a target="_blank" href="/pages/services/index.php?service=<?= $service['idservices']; ?>">
								<? } ?>


								<?= $service['servicesName']; ?>
							</a>
					</div>
					<div class="R"><?= nf($service['minPrice']); ?></div>
				</div>
				<?
			}
			?>

		</div>
		<form action="<?= GR(); ?>" method="post">
			<textarea name="textdata" style="margin: 0 auto; display: block; width: 400px; height: 200px; resize: none;"></textarea>
			<br>
			<center>
				<input type="submit" value="Сохранить">	
			</center>

		</form>
	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
