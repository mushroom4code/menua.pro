<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
	<div class="box-body">
		<?
		$clients = query2array(mysqlQuery("SELECT * FROM `clients`"), 'idclients');


		$contracts = query2array(mysqlQuery("SELECT *, UNIX_TIMESTAMP(`f_salesDate`) AS `f_salesDateTS`  FROM `f_sales`"));
		printr($contracts[0]);
		foreach ($contracts as $contract) {
			$clients[$contract['f_salesClient']]['contracts'][] = $contract;
		}

		printr($clients[18]);
		$clients = obj2array($clients);
		?>

		<table>
			<tr><td>#</td></tr>

			<?
			$n = 0;
			foreach ($clients as $client) {
				$n++;

				if (count($client['contracts'] ?? [])) {
					$mindate = min(array_column($client['contracts'], 'f_salesDateTS'));

					mysqlQuery("UPDATE `clients` SET `clientsOldSince` = '" . date("Y-m-d", $mindate) . "' WHERE `idclients` = '" . $client['idclients'] . "'");
				} else {
					$mindate = 'null';
				}
				?><tr>
					<td><?= $n; ?></td>
					<td><?= $client['clientsLName'] ?? '???'; ?></td>
					<td><?= $client['clientsFName'] ?? '???'; ?></td>
					<td><?= $client['clientsMName'] ?? '???'; ?></td>
					<td><?= count($client['contracts'] ?? []); ?></td>

					<td><?= $mindate; ?>(<?= implode(',', array_column($client['contracts'] ?? [], 'f_salesDateTS')); ?>)</td>
				</tr><?
			}
			?>

		</table>


	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
