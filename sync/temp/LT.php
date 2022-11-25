<?php
$pageTitle = 'сетка';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';

//idLT, LTtype, LTid, LTuser, LTdate, LTfrom, LTto, LTresult, LTset
$LTdata = query2array(mysqlQuery("SELECT * FROM `LT` WHERE `LTuser`='176' AND `LTid`='1'"));

foreach ($LTdata as $LTdataRow) {
	$ltgrids[$LTdataRow['LTdate']]['type'] = $LTdataRow['LTtype'] ?? '-';
	$ltgrids[$LTdataRow['LTdate']]['data'][] = [
		'from' => $LTdataRow['LTfrom'],
		'to' => $LTdataRow['LTto'],
		'result' => $LTdataRow['LTresult'],
	];
}





printr($ltgrids);


//function LTF($ltgrids, $LTvalue, $LTdate) {// $ltgrids <- только нужные сетки  Ключ - дата.
//	$grids = array_filter($ltgrids, function ($griddate) use ($LTdate) {
//		return $griddate <= $LTdate;
//	}, ARRAY_FILTER_USE_KEY);
//	krsort($grids);
//	$grids = array_values($grids);
//	if ($grids) {
////		printr($grids);
//		$grid = $grids[0];
////		printr($grid);
//		$row = array_filter($grid, function ($gridRow)use ($LTvalue) {
//			if ($gridRow['from'] === null && $LTvalue <= $gridRow['to']) {
//				return true;
//			}
//			if ($gridRow['from'] !== null && $gridRow['to'] !== null && $gridRow['from'] <= $LTvalue && $LTvalue <= $gridRow['to']) {
//				return true;
//			}
//			if ($gridRow['from'] <= $LTvalue && $gridRow['to'] === null) {
//				return true;
//			}
//			return false;
//		});
//		return array_values($row)[0]['result'] ?? null;
//	}
//Сортируем сетки по дате от самой новой, к старой
//	$LTfilteredByDate = array_filter($data, function ($LTentry) use ($LTdate, $user) {
//		return $LTentry['LTdate'] <= $LTdate && $LTentry['LTuser'] == $user;
//	});
//	$LTfiltered = array_filter($LTfilteredByDate, function ($LTentry) use ($LTfilteredByDate) {
//		return max(array_column($LTfilteredByDate, 'LTdate')) == $LTentry['LTdate'];
//	});
//
//	foreach ($LTfiltered as $LTval) {
//		if ($LTval['LTvalue'] >= $LTvalue) {
//			return $LTval['LTreward'];
//		}
//	}
//	return '-';
//}

$dates = ['2020-08-01', '2021-07-01', '2021-08-01', '2021-10-10'];
?>

<div class="box neutral">
	<div class="box-body">
		<?
		printr($LTdata);
		?>

		<div class="lightGrid" style="display: grid; grid-template-columns: repeat(3, auto);">
			<div style="display: contents;">
				<div>Дата</div>
				<div>Вход</div>
				<div>Выход</div>
			</div>

			<?
			foreach ($dates as $date) {
				for ($val = 300; $val < 1000; $val += 71) {
					?>
					<div style="display: contents;">
						<div><?= $date; ?></div>
						<div class="C"><?= $val; ?></div>
						<div><?= LT($ltgrids, $val, $date); ?></div>
					</div>
					<?
				}
			}
			?>
		</div>

	</div>
</div>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
?>
