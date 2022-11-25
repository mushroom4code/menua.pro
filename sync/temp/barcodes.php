<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Barcode</title>
		<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
    </head>
    <body>
		<div>
			<?
			foreach ([
		'000114',
		'000115',
		'000117',
		'000118',
		'000119',
		'000120',
		'000121',
		'000122',
		'000123',
		'000124',
		'000125',
		'000126',
		'000127'] as $barcode) {
				?><svg class="barcode" style="border: 1px solid black; display: inline-block;"
					 jsbarcode-text="<?= $barcode; ?>"
					 jsbarcode-value="<?= $barcode; ?>"
					 jsbarcode-width="3"
					 jsbarcode-height="30" 
					 jsbarcode-fontSize="12" 
					 jsbarcode-font="Arial" 
					 ></svg><br><br><?
				 }
				 ?>
			<script>
				JsBarcode(".barcode").init();
			</script>
		</div>
    </body>
</html>
