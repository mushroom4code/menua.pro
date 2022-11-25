<?php

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
mb_internal_encoding("UTF-8");
header("Content-type: application/json; charset=utf8");

function checkRS_correspondent($name, $account = false) {
	global $link;
	$correspondent = mfa(mysqlQuery("SELECT * FROM `RS_correspondents` WHERE"
					. " `RS_correspondentsName` = '" . FSS($name) . "'"
					. ($account ? (" AND `RS_correspondentsAccount`='" . FSS($account) . "'") : ""))
	);
	if (!$correspondent) {
		if (mysqlQuery("INSERT INTO `RS_correspondents` SET"
						. " `RS_correspondentsName` = '" . FSS($name) . "'"
						. ($account ? (", `RS_correspondentsAccount`='" . FSS($account) . "'") : ""))) {
			$correspondent = mfa(mysqlQuery("SELECT * FROM `RS_correspondents` WHERE"
							. " `idRS_correspondents` = '" . mysqli_insert_id($link) . "'")
			);
		}
	}
	return $correspondent;
}

function checkRS_bank($name) {
	global $link;
	$bank = null;
	$bank = mfa(mysqlQuery("SELECT * FROM `RS_banks` WHERE"
					. " `RS_banksName` = '" . FSS($name) . "'"
			)
	);
	if (!$bank) {
		if (mysqlQuery("INSERT INTO `RS_banks` SET"
						. " `RS_banksName` = '" . FSS($name) . "'"
				)) {
			$bank = mfa(mysqlQuery("SELECT * FROM `RS_banks` WHERE"
							. " `idRS_banks` = '" . mysqli_insert_id($link) . "'")
			);
		}
	}
	return $bank;
}

if (!empty($_FILES)) {
	$content = file($_FILES['file']['tmp_name']);
	$output = [];
	if (count($content) > 1) {
		foreach ($content as $numrow => $fileRow) {
			foreach (explode("\t", $fileRow) as $idcolumn => $column) {
				$output[$numrow][$idcolumn] = mb_convert_encoding($column, "utf-8", "windows-1251");
			}
		}
		//	printr($output);
	}
}

$data = [];
//            [8] => Номер документа
//            [9] => Дата документа


foreach ($output as $idrow => $row) {
	if ($idrow > 0) {
		$data[] = [
			'rs' => $row[0],
			'correspondent' => checkRS_correspondent($row[7], $row[6]),
			'bank' => checkRS_bank($row[5]),
			'operationDate' => date("Y-m-d", strtotime($row[1])),
			'comment' => $row[13],
			'documentDate' => date("Y-m-d", strtotime($row[9])),
			'documentNumber' => FSS($row[8]),
			'deb' => floatval($row[10]),
			'cred' => floatval($row[11]),
		];
	}
}
$success = true;
foreach ($data as $idrow => $row) {
	if (mysqlQuery("INSERT INTO `RS_entries`
					SET
				`RS_entriesRS` = '" . $row['rs'] . "',
				`RS_entriesCorrespondent` = '" . $row['correspondent']['idRS_correspondents'] . "',
				`RS_entriesBank` = '" . $row['bank']['idRS_banks'] . "',
				`RS_entriesOperationDate` = '" . $row['operationDate'] . "',
				`RS_entriesDocumentDate` = '" . $row['documentDate'] . "',
				`RS_entriesDocumentNumber` = '" . $row['documentNumber'] . "',
				`RS_entriesDebet` = '" . $row['deb'] . "',
				`RS_entriesCredit` = '" . $row['cred'] . "'
					ON DUPLICATE KEY UPDATE 
				`RS_entriesCorrespondent` = '" . $row['correspondent']['idRS_correspondents'] . "',
				`RS_entriesBank` = '" . $row['bank']['idRS_banks'] . "',
				`RS_entriesOperationDate` = '" . $row['operationDate'] . "',
				`RS_entriesDebet` = '" . $row['deb'] . "',
				`RS_entriesCredit` = '" . $row['cred'] . "'
					

				") && ($idRS_entries = mysqli_insert_id($link)) && mysqlQuery("INSERT INTO `RS_comments` SET `RS_commentsRS_entry` = '" . $idRS_entries . "',`RS_commentsComment`='" . $row['comment'] . "'")) {
		
	} else {
		
	}
}
print json_encode(['success' => $success], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

die();

//printr();

