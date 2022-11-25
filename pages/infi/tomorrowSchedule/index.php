<?php
$load['title'] = $pageTitle = 'Инфи - ассистент';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(77)) {
	if (!empty($_POST['text'])) {
		if (!empty($_POST['user'])) {
			$usericq = mfa(mysqlQuery("SELECT * FROM `users` WHERE NOT isnull(`usersICQ`) AND isnull(`usersDeleted`) AND `idusers` = '" . FSI($_POST['user']) . "'"));
			$text = preg_replace("/name/", $usericq['usersFirstName'], $_POST['text']);
//			ICQ_messagesSend_SYNC($usericq['usersICQ'], $text);
			ICQMSDelay(7000, $usericq['usersICQ'], $text);
		} elseif (!empty($_POST['all']) && $_POST['all'] === '1') {
			$uwers = query2array(mysqlQuery("SELECT * FROM `users` WHERE NOT isnull(`usersICQ`) AND isnull(`usersDeleted`)"));
			foreach ($uwers as $uwer) {
				$text = preg_replace("/name/", $uwer['usersFirstName'], $_POST['text']);
//				ICQ_messagesSend_SYNC($uwer['usersICQ'], $text);
				ICQMSDelay(7000, $uwer['usersICQ'], $text);
			}
		}
		header("Location: " . GR());
		die();
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(77)) {
	?>E403R77<?
} else {

	include $_SERVER['DOCUMENT_ROOT'] . '/pages/infi/menu.php';
	$_date = $_GET['date'] ?? date("Y-m-d");
	?>
	<div class="box neutral">
		<div class="box-body">
			<h2>
				<input type="date" onchange="GETreloc('date', this.value);" value="<?= $_date; ?>">
			</h2>

			<?
//				printr($_WEEKDAYS);
			$dayname = '';
			$was = '';
			if (strtotime($_date) == strtotime(date("Y-m-d"))) {
				$dayname = 'Сегодня ';
			}
			if (strtotime($_date) == 60 * 60 * 24 + strtotime(date("Y-m-d"))) {
				$dayname = 'Завтра ';
			}
			if (strtotime($_date) == 60 * 60 * 24 * 2 + strtotime(date("Y-m-d"))) {
				$dayname = 'Послезавтра ';
			}
			if (strtotime($_date) > 60 * 60 * 24 * 2 + strtotime(date("Y-m-d"))) {

				$dayname = $_WEEKDAYS['full']['nom'][date("N", strtotime($_date))] . ' ';
			}
			if (strtotime($_date) < strtotime(date("Y-m-d"))) {
				$was = ' должны были быть';
			}

			$text = '✅ ' . $dayname . '' . date("d.m", strtotime($_date)) . ' в смене' . $was . "\r\n";

			$usersSchedule = query2array(mysqlQuery("SELECT * FROM"
							. " `usersSchedule`"
							. " LEFT JOIN `users` ON (`idusers` = `usersScheduleUser`)"
							. " LEFT JOIN `usersGroups` ON (`idusersGroups`=`usersGroup`)"
							. " WHERE `usersScheduleDate`='" . $_date . "' "
					. "AND NOT isnull(`usersScheduleFrom`) "
					. "AND NOT isnull(`usersScheduleTo`) "
					. ""));

			$all = array_filter($usersSchedule, function($el) {
				return in_array($el['usersGroup'], [2, 3, 4, 5, 6, 11]);
			});

			uasort($all, function($a, $b) {
				return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
			});
			foreach ($all as $user) {
				$start = '';
				if (date("H:i", strtotime($user['usersScheduleFrom'])) != '10:00') {
					$start = ' c ' . date("H:i", strtotime($user['usersScheduleFrom']));
				}
				$text .= "\r\n" . $user['usersLastName'] . ' ' . $user['usersFirstName'] . $start;
			}




			$stoma = array_filter($usersSchedule, function($el) {
				return in_array($el['usersGroup'], [7]);
			});
			uasort($stoma, function($a, $b) {
				return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
			});
			$text .= "\r\n\r\n" . '✅ Стома' . "\r\n";
			foreach ($stoma as $user) {
				$start = '';
				if (date("H:i", strtotime($user['usersScheduleFrom'])) != '10:00') {
					$start = ' c ' . date("H:i", strtotime($user['usersScheduleFrom']));
				}
				$text .= "\r\n" . $user['usersLastName'] . ' ' . $user['usersFirstName'] . $start;
			}


			$diag = array_filter($usersSchedule, function($el) {
				return in_array($el['usersGroup'], [1]);
			});
			uasort($diag, function($a, $b) {
				return mb_strtolower($a['usersLastName']) <=> mb_strtolower($b['usersLastName']);
			});
			$text .= "\r\n\r\n" . '✅ ПМП' . "\r\n";
			foreach ($diag as $user) {
				$start = '';
				if (date("H:i", strtotime($user['usersScheduleFrom'])) != '10:00') {
					$start = ' c ' . date("H:i", strtotime($user['usersScheduleFrom']));
				}
				$text .= "\r\n" . $user['usersLastName'] . ' ' . $user['usersFirstName'] . $start;
			}
			?>




			<div style="text-align: center;">
				<div style="text-align: left; display: inline-block; background-color: white; border: 1px solid gray; padding: 10px; border-radius: 5px;"><?= nl2br($text, false); ?></div>	
			</div>
			<? $users = query2array(mysqlQuery("SELECT * FROM `users` WHERE isnull(`usersDeleted`) AND NOT isnull(`usersICQ`) ORDER BY `usersLastName`")); ?>
			<form action="<?= GR(); ?>" method="POST">
				<input type="hidden" name="text" value="<?= urlencode($text); ?>">
				<div style="padding: 10px;">
					<select name="user">
						<option></option>
						<? foreach ($users as $user) {
							?><option value="<?= $user['idusers'] ?>"><?= $user['usersLastName'] ?> <?= $user['usersFirstName'] ?> <?= $user['usersMiddleName'] ?></option><? }
						?>
					</select>
				</div>
				<div style="padding: 10px; text-align: center;">
					<input type="submit" value="Отправить">
				</div>
			</form>
		</div>
	</div>





<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
