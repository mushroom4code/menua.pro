<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

$row = 1;
$DATA = [];
if (($handle = fopen('helixcheck.csv', "r")) !== FALSE) {
	while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
		foreach ($data as &$column) {
			$column = mb_convert_encoding($column, "utf-8", "windows-1251");
		}
		$DATA[] = $data;
	}
	fclose($handle);

	$clients = [];

	$clientRow = 0;
	$dateRow = 0;

//	printr($DATA, 1);

	foreach ($DATA as $row) {
		if ($clientRow ?? false) {
			$clientRow++;
		}
		if (
				preg_match("/^\d{2}\.\d{2}\.\d{4}/", $row[1])
		) {
			$date = date("Y-m-d", strtotime($row[1]));
//			print $row[1] . '<br>';
		}
		if (!$row[0] && $row[1] && $row[6] && $row[12] && $row[14] && $row[20]) {
			$clients[] = [
				'name' => array_map('mb_ucfirst', explode(' ', preg_replace('/\s+/', ' ', trim($row[6])))),
				'date' => $date
			];
			$clientRow = 1;
		}

		$idclients = count($clients) - 1;
		if ($idclients > -1) {
			if ($clientRow > 1) {
				$clients[$idclients]['services'][] = [
					'code' => $row[2],
					'name' => $row[4],
					'summ' => str_replace(',', '.', str_replace(' ', '', $row[20])),
				];
			}
		}
	}




	foreach ($clients as $index => $client) {
		$clients[$index]['total'] = array_sum(array_column($client['services'], 'summ'));
		$dbClients = query2array(mysqlQuery("SELECT * FROM `clients` WHERE "
						. " `clientsLName`='" . $client['name'][0] . "'"
						. " AND `clientsFName`='" . $client['name'][1] . "'"
						. " AND `clientsMName`='" . $client['name'][2] . "'"
		));
		$clients[$index]['clones'] = $dbClients;
		$clients[$index]['valid'] = count($dbClients) == 1;

		if ($clients[$index]['valid']) {
			$dbClients[0];
			$clients[$index]['f_sales'] = query2array(mysqlQuery("SELECT * FROM `f_sales` WHERE `f_salesClient` = '" . $dbClients[0]['idclients'] . "' AND `f_salesDate`='" . $client['date'] . "'"));
			$clients[$index]['f_payments'] = query2array(mysqlQuery("SELECT * FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE `f_salesClient` = '" . $dbClients[0]['idclients'] . "' AND `f_paymentsDate`='" . $client['date'] . "'"));
			$clients[$index]['f_credits'] = query2array(mysqlQuery("SELECT * FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`) WHERE `f_salesClient` = '" . $dbClients[0]['idclients'] . "' AND `f_salesDate`='" . $client['date'] . "'"));
			$clients[$index]['servicesApplied'] = query2array(mysqlQuery("SELECT *,"
							. " ("
							. "ifnull((SELECT SUM(`f_creditsSumm`) FROM `f_credits` LEFT JOIN `f_sales` ON (`idf_sales` = `f_creditsSalesID`) WHERE  `idf_sales` = `servicesAppliedContract`),0)+"
							. "ifnull((SELECT SUM(`f_paymentsAmount`) FROM `f_payments` LEFT JOIN `f_sales` ON (`idf_sales` = `f_paymentsSalesID`) WHERE  `idf_sales` = `servicesAppliedContract`),0)"
							. ") as `payed` "
							. " FROM `servicesApplied`"
							. " LEFT JOIN `f_sales` ON (`idf_sales` = `servicesAppliedContract`)"
							. " LEFT JOIN `services` ON (`idservices` = `servicesAppliedService`)"
							. " WHERE  `servicesAppliedClient` = '" . $dbClients[0]['idclients'] . "' AND  `servicesAppliedDate`='" . $client['date'] . "' AND isnull(`servicesAppliedDeleted`)"));
		}
	}
//	printr($clients);
//	printr($DATA, 1);
	?>

	<table border="1">
		<tr>
			<td>Клиент</td>
			<td>Дата</td>
			<td>Сумма</td>
			<td>В базе</td>
			<td>Куплено абонементов<br>(Совершшено платежей)</td>
			<td>Процедуры</td>
		</tr>
		<? foreach ($clients as $client) { ?>
			<tr>
				<td>
					<? if ($client['valid']) { ?>
						<a target="_blank" href="/pages/offlinecall/schedule.php?client=<?= $client['clones'][0]['idclients']; ?>&date=<?= $client['date']; ?>">
						<? } ?>
						<?= implode(' ', $client['name']); ?>
						<? if ($client['valid']) { ?></a><? } ?>
				</td>
				<td><?= $client['date']; ?></td>
				<td style="text-align: right;"><?= $client['total']; ?></td>
				<td style="text-align: center;"><?= $client['valid'] ? 'да' : 'нет'; ?></td>
				<td><?= array_sum(array_column(($client['f_sales'] ?? []), 'f_salesSumm')); ?><br>(<?=
					array_sum(array_column(($client['f_payments'] ?? []), 'f_paymentsAmount')) + array_sum(array_column(($client['f_credits'] ?? []), 'f_creditsSumm'))
					?>)</td>
				<td><? foreach (($client['servicesApplied'] ?? []) as $serviceApplied) {
						?>
						<?= $serviceApplied['servicesName'] ?> (<?= $serviceApplied['servicesAppliedPrice']; ?>p.)
						<?
						if (round($serviceApplied['servicesAppliedPrice'])) {
							if (round($serviceApplied['payed'])) {
								if (round($serviceApplied['payed']) == round($serviceApplied['f_salesSumm'])) {
									?><span style="color: green;">ОПЛАЧЕНО ПОЛНОСТЬЮ</span><?
								} else {
									?><span style="color: orange;">ОПЛАЧЕНО ЧАСТИЧНО (<?= round($serviceApplied['payed']); ?> из <?= round($serviceApplied['f_salesSumm']); ?>)</span><?
								}
							} else {
								?><span style="color: red;">НЕ ОПЛАЧЕНО</span><?
							}
						} else {
							?><span style="color: red;">Бесплатно</span><?
						}
						?>
						<br>
					<? }
					?></td>
			</tr>
		<? }
		?>
	</table>

	<?
}



