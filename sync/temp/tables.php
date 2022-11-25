<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

//printr(array_column(query2array(mysqlQuery("SHOW TABLES")), 'Tables_in_warehouse'));

foreach (array_column(query2array(mysqlQuery("SHOW TABLES")), 'Tables_in_warehouse') as $table) {
	$columns = query2array(mysqlQuery("SHOW COLUMNS FROM $table"));
	//AND REFERENCED_COLUMN_NAME = '<column>'
	?>
	<div style="border: 2px solid silver; display: inline-block; margin: 3px;">
		<div style=" padding: 5px; background: silver;"><?= $table; ?></div>
		<? foreach ($columns as $column) { ?>
			<div style=" padding: 2px 5px;"><?= $column['Field']; ?></div>
			<?
		}
//		printr($keys);
		?>
	</div>
	<?
//	if (count($keys)) {
//		if ($n > 5) {
//			die();
//		}
//		$n++;
//	}
}
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

