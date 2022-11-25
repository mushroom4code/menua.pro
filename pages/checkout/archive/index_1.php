<?php
$load['title'] = $pageTitle = 'Оформление договора';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (R(26)) {
	
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (!R(26)) {
	?>E403R26<?
} else {
	?>
	<style>
		.suggestions {
			position: absolute;
			width: auto;
			background-color: white;
			border: 1px solid silver;
			box-shadow: 0px 0px 10px hsla(0,0%,0%,0.3);
			border-radius: 4px;
			z-index: 10;
			list-style: none;
			white-space: nowrap;
			left: 95%;
			top: 0px;
		}
		.suggestions .red {
			color: red;
		}
		.suggestions span {
			color: gray;
		}
		.suggestions li {
			font-size: 0.8em;
			padding: 2px 10px;
			cursor: pointer;
		}
		.suggestions li .mask{
			position: absolute;
			top: 0px;
			left: 0px;
			width: 100%;
			height: 100%;
			z-index: 10;
		}

		.suggestions li .mask:hover{
			background-color:  hsla(0,0%,0%,0.1);
		}

		.suggestions li .pointed{
			background-color:  hsla(0,0%,0%,0.1);
		}

		.displayContents{
			display: contents;
		}
		.salesWindows {
			vertical-align: top;
			width: 100%;
			margin: 20px auto;
			text-align: left;
		}
		#subscriptions {
			white-space: nowrap;
		}
		.box-body {
			/*display: inline-block;*/
		}
	</style>
	<? include 'menu.php'; ?>
	
<? }
?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
