<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Barcode</title>
		<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
		<style>
			* {
				position: relative;
				box-sizing: border-box;
				padding: 0px;
				margin: 0px;
				font-family: Arial;
			}

			.badge {
				display: inline-block;
				border: 1px dashed gray;
				width: 82mm;
				height: 80mm;
				margin: 2.5mm;
			}
			.upperhalf {
				background-image: url('/css/images/badgesBG1.png'), url('/css/images/badgesBG2.png');
				background-position: bottom right, top left;
				background-size: 40.0%;
				background-repeat: no-repeat;
				height: 40mm;
			}
			.lowerhalf {
				height: 40mm;
				transform: rotate(180deg);
			}

			.barcode {
				position: absolute;
				bottom: 10mm;
				left: 20mm;
				right: 20mm;
				/*z-index: -1;*/
				width: 38mm;
				/*padding: 6px;*/
				/*border: 1px solid red;*/
			}
			.barcode2 {
				position: absolute;
				bottom: 10mm;
				left: 20mm;
				right: 20mm;
				line-height: 1.3em;
				/*z-index: -1;*/
				width: 40mm;
				/*padding: 6px;*/
				/*border: 1px solid red;*/
			}
			.text-wrapper {
				/*border: 1px solid red;*/
				position: absolute;
				width: 100%;
				left: 0%;
				top: 35%;
				text-align: center;
			}

			.name {
				font-weight: bolder;
				font-size: 1.2em;
			}
			.position {
				font-weight: normal;
				font-size: 0.7em;
			}


		</style>
    </head>
    <body>




		<?
		if (!empty($_GET['print'])) {
			$print = json_decode($_GET['print']);
			$employees = mysqlQuery("SELECT * "
					. "FROM `users` "
					. "LEFT JOIN `usersPositions` ON (`usersPositionsUser` = `idusers`) "
					. "LEFT JOIN `positions` ON (`idpositions` = `usersPositionsPosition`) "
					. "WHERE `idusers` IN (" . implode(", ", $print) . ") AND NOT isnull(`usersBarcode`)");

			$employeesArr = query2array($employees);
			$employeesArr2 = [];

			foreach ($employeesArr as $employee) {
				//		printr($employee);
				if (!isset($employeesArr2[$employee['idusers']])) {
					$employeesArr2[$employee['idusers']] = [
						'usersLastName' => $employee['usersLastName'],
						'usersFirstName' => $employee['usersFirstName'],
						'usersMiddleName' => $employee['usersMiddleName'],
						'usersBarcode' => $employee['usersBarcode'],
					];
				}
				if (isset($employee['positionsName']) && $employee['positionsName']) {
					if (!isset($employeesArr2[$employee['idusers']]['positions'])) {
						$employeesArr2[$employee['idusers']]['positions'] = [];
					}
					$employeesArr2[$employee['idusers']]['positions'][] = $employee['positionsName'];
				}
			}


			foreach ($employeesArr2 as $employee) {
				//printr($employee);
				?>
				<div class="badge">
					<div class="upperhalf">
						<div class="text-wrapper">
							<div class="name">
								<?= $employee['usersLastName'] ?><br>
								<?= $employee['usersFirstName'] ?> <?= $employee['usersMiddleName'] ?>
							</div>
							<div class="position">
								<?= implode(',<br>', $employee ['positions'] ?? []); ?>
							</div>
						</div>
					</div>
					<div class="lowerhalf">
						<? if ($employee['usersBarcode']) { ?>
							<svg class="barcode"
								 jsbarcode-value="<?= $employee['usersBarcode']; ?>"
								 jsbarcode-height="60"
								 jsbarcode-displayValue="true"
								 jsbarcode-margin="0"
								 jsbarcode-background="none"
								 >
							</svg>
						<? } else { ?>
							<div class="barcode2" style="text-align: center; transform: rotate(180deg);">
								<?= $_USER['fname']; ?>, у этого сотрудника<br>нет штрих-кода!
							</div>


						<? } ?>

						</svg>
					</div>


				</div>
				<?
			}
		}
		?>
		<script>
			JsBarcode(".barcode").init();
		</script>

    </body>
</html>
