<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';


$json = json_decode(file_get_contents("remains.json"), true);
$remains = $json['remains'];
$DBservices = query2array(mysqlQuery("SELECT * FROM `services` LEFT JOIN `servicesGUIDs` ON (`servicesGUIDsService` = `idservices`)"));


?>

<div class="box neutral">
	<div class="box-body">
		<?
//		printr($remains[0]); 
		?>

		<?
		

//		printr($DBservices[0]);
		$services = [];
		foreach ($remains as $remain) {
			$services[$remain['GUIDПроцедуры']] = $remain['Процедура'];
		}
		?><table border="1"><?
			foreach ($services as $GUID => $service) {
				$matches = array_filter($DBservices, function($element) {
					global $service;
					return trim($element['servicesName']) == trim($service);
				});

				$GUIDmatch = array_filter($DBservices, function($element) {
					global $GUID;
					return $element['servicesGUIDsGUID'] == $GUID;
				});
				if (count($GUIDmatch)) {
					continue;
				}
				?>
				<tr>
					<td><?= $GUID; ?></td>
					<td><?= $service; ?></td>
					<td><?
						if (count($matches)) {
							foreach ($matches as $match) {
								printr($match);
//								mysqlQuery("UPDATE `services`  SET `servicesGUID` = '" . $GUID . "' WHERE `idservices` = '" . $match['idservices'] . "'");
							}
						}
						?></td>
				</tr>

				<?
			}
			?>
		</table>

	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
