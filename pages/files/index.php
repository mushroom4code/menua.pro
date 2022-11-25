<?php
$pageTitle = 'Мои документы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

if (R(25)) {
	$file = isset($_GET['file']) ?
			FSI($_GET['file']) :
			FALSE;

	if ($file) {
		$fileData = mfa(mysqlQuery("SELECT * FROM `files` WHERE `filesUser` = '" . $_USER['id'] . "' AND `idfiles` = '" . $file . "'"));
		$file = $_SERVER['DOCUMENT_ROOT'] . '/pages/files/uploads/' . $_USER['id'] . '/' . $fileData['filesPath'];
		if (file_exists($file)) {
			header('Content-Type: ' . mime_content_type($file));
			header("Content-Transfer-Encoding: Binary");
			header("Content-disposition: attachment; filename=\"" . basename($fileData['filesName']) . "\"");
			readfile($file);
			die();
		} else {
			header('HTTP/1.1 403 Forbidden');
			echo 'You\'re not authenticated!';
			die();
		}
	}
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
if (R(25)) {
	?>

	<ul class="horisontalMenu">
		<li><a href="#" onclick="addFolder(<?= FSI($_GET['dir'] ?? null) ? $_GET['dir'] : ''; ?>);">Создать папку</a></li>
		<li><a href="#" onclick="fileUpload(<?= FSI($_GET['dir'] ?? null) ? $_GET['dir'] : ''; ?>);">Загрузить файл</a></li>
	</ul>

	<?
	if (!empty($_GET['dir'])) {
		$parent = mfa(mysqlQuery("SELECT * FROM `files`"
								. "WHERE `filesUser`='" . $_USER['id'] . "' AND "
								. "`idfiles` = '" . FSI($_GET['dir']) . "' "
								. ""))['filesParent'];
		?>
		<div>
			<a href="/pages/files/index.php<?= ($parent ? ('?parent=' . $parent) : '') ?>">...Назад</a>
		</div>
		<?
	}
	?>
	<div class="box neutral">
		<div class="box-body">
			<div style="padding: 10px 0px;">
				<div class="fileTable">
					<?
					$files = query2array(mysqlQuery("SELECT * "
									. "FROM `files` "
									. "WHERE `filesUser`='" . $_USER['id'] . "' "
									. (!empty($_GET['dir']) ? " AND `filesParent` = '" . FSI($_GET['dir']) . "' " : ' AND isnull(`filesParent`)')
									. ""));


					usort($files, function($a, $b) {
						if (empty($a['filesPath']) === empty($b['filesPath'])) {
							return mb_strtolower($a['filesName']) <=> mb_strtolower($b['filesName']);
						} else {
							return intval(empty($b['filesPath'])) <=> intval(empty($a['filesPath']));
						}
					});


					foreach ($files as $file) {

						if ($file['filesPath']) {
							?>
							<div>
								<a href="/pages/files/?file=<?= $file['idfiles']; ?>"><i class="far fa-file-alt"></i></a>
							</div>
							<div>
								<a href="/pages/files/?file=<?= $file['idfiles']; ?>"><?= $file['filesName']; ?></a>
							</div>
							<div>
								<a href="/pages/files/?file=<?= $file['idfiles']; ?>"><?= date("d.m.Y", $file['filesPath']); ?></a></div>
							<?
						} else {
							?>
							<div>
								<a href="/pages/files/?dir=<?= $file['idfiles']; ?>"><i class="fas fa-folder"></i></a>
							</div>
							<div>
								<a href="/pages/files/?dir=<?= $file['idfiles']; ?>"><?= $file['filesName']; ?></a>
							</div>
							<div></div>
							<?
						}
						?>

						<div><button style="color: red;" data-function="deleteFile" data-file="<?= $file['idfiles']; ?>">X</button></div><?
					}
					?>

				</div>
			</div>

		</div>
	</div>

	<?
} else {
	?>E403R25<? } ?>

<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
