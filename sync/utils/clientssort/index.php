<?php
$pageTitle = 'Приложения';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<div class="box neutral">
	<div class="box-body">
		<?
		if (isset($_GET['client']) && isset($_GET['source'])) {
			mysqlQuery("UPDATE `clients` SET `clientsSource` = '" . FSI($_GET['source']) . "' WHERE `idclients` = '" . FSI($_GET['client']) . "'");
		}


		$clients = query2array(mysqlQuery("SELECT 
    *
FROM
    warehouse.clients
        LEFT JOIN usersPositions ON (usersPositionsUser = clientsAddedBy)
		LEFT JOIN positions on (idpositions = usersPositionsPosition)
        LEFT JOIN users on (idusers = clientsAddedBy) WHERE
    ISNULL(clientsSource) ORDER BY `positionsName`,`usersLastName`;"));


		if (count($clients)) {

			$client = $clients[rand(0, count($clients) - 1)];
//			printr($client);
			$phones = query2array(mysqlQuery("SELECT * FROM `clientsPhones` WHERE `clientsPhonesClient` = '" . $client['idclients'] . "' "));
			?>
			<table>
				<tr>
					<td>
						Ф.И.О.
					</td>
					<td>
						<?= $client['clientsLName']; ?>
						<?= $client['clientsFName']; ?>
						<?= $client['clientsMName']; ?>
					</td>
				</tr>
				<tr>
					<td>Телефон(ы)</td>
					<td>
						<?
						if (count($phones)) {
							foreach ($phones as $phone) {
//								printr($phone);
								?><div><?= $phone['clientsPhonesPhone'] ?></div><?
							}
						} else {
							?>
							Не указан
							<?
						}
						?>
					</td>
				</tr>
				<tr>
					<td>дата рождения</td>
					<td><?= $client['clientsBDay'] ?? 'не знаю'; ?></td>
				</tr>

				<tr>
					<td>№ Карты</td>
					<td><?= $client['clientsAKNum'] ?? 'Нету'; ?></td>
				</tr>

				<tr>
					<td>Кем добавлена: </td>
					<td><?= $client['usersLastName'] ?? 'Непомню'; ?> <?= $client['usersFirstName']; ?><?= $client['positionsName'] ? (' (' . $client['positionsName'] . ')') : ''; ?></td>
				</tr>

				<tr>
					<td>Когда добавлена</td>
					<td><?= $client['clientsAddedAt'] ?? 'Забыла'; ?></td>
				</tr>

				<tr>
					<td>Отмечен как первичный</td>
					<td><?= $client['clientsIsNew'] ? 'Да' : 'Нет'; ?></td>
				</tr>
			</table>
			<div style="text-align: center;">
				<div style="display: inline-block;">
					<div style="padding: 10px; margin: 10px; font-weight: bold;">Итак, вопрос:<br>Откуда пришёл клиент?</div>
					<div style="display: grid; grid-template-columns:30px auto 30px 20px 30px auto 30px 20px; grid-gap: 20px 0px;">
						<?
						$clientsSources = query2array(mysqlQuery("SELECT * FROM `clientsSources`"));
						foreach ($clientsSources as $clientsSource) {
							?>
							<div style="border-left:  2px solid darkblue; border-top:  2px solid darkblue; transform: rotate(-45deg) translate(11px, 17px); background-color: lightskyblue; width: 21px; height: 21px;"></div>
							<div style=" background-color: lightskyblue; align-items: center; line-height: 30px;  z-index: 2; border-top: 2px solid darkblue;  border-bottom: 2px solid darkblue; height: 30px;"><a href="/sync/utils/clientssort/?client=<?= $client['idclients']; ?>&source=<?= $clientsSource['idclientsSources']; ?>"><?= $clientsSource['clientsSourcesName']; ?></a></div>
							<div style="border-left:  2px solid darkblue; border-top:  2px solid darkblue; z-index: 0; transform: translate(-10px, 4px) rotate(135deg); background-color: lightskyblue; width: 21px; height: 21px;"></div>
							<div></div>
							<?
						}
						?>
					</div>
					<a style="margin: 20px; display: block;" href="/sync/utils/clientssort/">Понятия не имею....</a>
				</div>
			</div>
			А ещё в базе таких <?= rt(['безродных', 'замечательных', 'интересных', 'случаев']); ?> <?= human_plural_form(count($clients), ['человек', 'человека', 'человек'], true); ?>.<br>
			<?= rt(['Так что не останавливаемся', 'Так что продолжаем', 'Так что едем дальше', 'Так что жмём на кнопки', 'Так что улыбаемся и машем']); ?>.
			<?
		} else {
			?> 
			УРА! ЗАКОНЧИЛИ!!!!!<?
		}
		?>


	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
