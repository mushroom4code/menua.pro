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
				width: 94mm;
				height: 118mm;
				margin: 2.5mm;
			}
			.upperhalf {
				background-image: url('/css/images/badgesBG1.png');
				background-position: bottom right;
				background-size: 54.9%;
				background-repeat: no-repeat;
				height: 59mm;
			}
			.lowerhalf {
				background-image: url('/css/images/badgesBG1.png');
				background-position: bottom right;
				background-size: 50%;
				background-repeat: no-repeat;
				height: 57mm;
				transform: rotate(180deg);
			}

			.barcode {
				position: absolute;
				bottom: 3mm;
				left: 3mm;
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
			.logo {
				position: absolute;
				background-image: url('/css/images/infinitiMC.png');
				width: 37mm;
				height: 8mm;
				background-size: contain;
				top: 8mm;
				left: 3mm;
				background-repeat: no-repeat;
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
						<div class="logo"></div>
						<div class="text-wrapper">
							<div class="name">
								<?= $employee['usersLastName'] ?><br>
								<?= $employee['usersFirstName'] ?> <?= $employee['usersMiddleName'] ?>
							</div>			
							<div class="position">
								<?= implode(', ', $employee ['positions'] ?? []); ?>
							</div>
						</div>
						<svg class="barcode"
							 jsbarcode-value="<?= $employee['usersBarcode']; ?>"
							 jsbarcode-height="50" 
							 jsbarcode-displayValue="false"
							 jsbarcode-margin="1"
							 jsbarcode-background="none"
							 >
						</svg>
					</div>
					<div class="lowerhalf">
						<div class="logo"></div>
						<div class="text-wrapper">
							<div class="name">
								<?= $employee['usersLastName'] ?><br>
								<?= $employee['usersFirstName'] ?> <?= $employee['usersMiddleName'] ?>
							</div>			
							<div class="position">
								<?= implode(', ', $employee ['positions'] ?? []); ?>
							</div>
						</div>
						<svg class="barcode"
							 jsbarcode-value="<?= $employee['usersBarcode']; ?>"
							 jsbarcode-height="50" 
							 jsbarcode-displayValue="false"
							 jsbarcode-margin="1"
							 jsbarcode-background="none"
							 >
						</svg>
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
