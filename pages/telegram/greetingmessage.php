<?php
$pageTitle = $load['title'] = 'Телеграм бот';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
if (!R(196)) {
	die('E403R196');
}
if (isset($_POST['greetingMessage'])) {
	//, , , infinitimedbotTemplatesAddedTime, infinitimedbotTemplatesAddedBy, infinitimedbotTemplatesDeletedTime, infinitimedbotTemplatesDeletedBy
	mysqlQuery("INSERT INTO `infinitimedbotTemplates`"
			. " SET "
			. "`infinitimedbotTemplatesType`=1,"
			. "`infinitimedbotTemplatesAddedBy`=" . $_USER['id'] . ","
			. " `infinitimedbotTemplatesText` = " . sqlVON($_POST['greetingMessage']) . " ");
	header("Location: " . GR());
	exit('ok');
}

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>

	.varbtn {
		border: 1px solid silver;
		cursor: pointer;
		display: inline-block;
		border-radius: 4px;
		line-height: 1em;
		padding: 3px 6px;
		background-color: #f0f0f0;
	}
</style>
<? include 'menu.php'; ?>
<?
$client = mfa(mysqlQuery("SELECT * FROM `clients` WHERE `idclients`=112"));
$greetingmessage = mfa(mysqlQuery("SELECT * FROM `infinitimedbotTemplates` WHERE `idinfinitimedbotTemplates` = (SELECT MAX(`idinfinitimedbotTemplates`) FROM `infinitimedbotTemplates` WHERE `infinitimedbotTemplatesType` = 1)"))['infinitimedbotTemplatesText'];

//		r
?>
<div class="box neutral">
	<div class="box-body">
		<table>
			<tr>
				<td style="vertical-align: top; text-align: center;">Шаблон сообщения:<br>
					<form action="<?= GR(); ?>" method="POST">
						<textarea name="greetingMessage" id="greetingMessage" style="width: 400px; height: 200px; border-radius: 0px; border: 1px solid silver; resize: none; padding: 10px;"><?= $greetingmessage; ?></textarea>
						<br>
						<button type="submit" style=" margin: 10px;">Сохранить</button>
					</form>
					<H4>Предпросмотр:</H4>
					<div style="width: 400px; text-align: left; background-color: white;  border-radius: 0px; border: 1px solid silver; resize: none; padding: 10px;">
						<?= str_replace("\n", "<br>", replaceInTemplate($greetingmessage, $client)); ?>

					</div>
				</td>
				<td style="vertical-align: top;">
					Доступные переменные:<br>
					<div style=" line-height: 2em;">
						<span class="varbtn">[clientsLName]</span> - фамилия клиента<br>
						<span class="varbtn">[clientsFName]</span> - Имя клиента<br>
						<span class="varbtn">[clientsMName]</span> - отчество клиента<br>
					</div>

				</td>
			</tr>
		</table>

		<script>
			function setCaretPosition(elem, caretPos) {
				if (elem !== null) {
					if (elem.createTextRange) {
						var range = elem.createTextRange();
						range.move('character', caretPos);
						range.select();
					} else {
						if (elem.selectionStart) {
							elem.focus();
							elem.setSelectionRange(caretPos, caretPos);
						} else
							elem.focus();
					}
				}
			}
			function insertAtCursor(myField, myValue) {
				//IE support
				if (document.selection) {
					console.log('case 1');
					myField.focus();
					sel = document.selection.createRange();
					sel.text = myValue;
				}
				//MOZILLA and others
				else if (myField.selectionStart || myField.selectionStart == '0') {
					console.log('case 2');
					var startPos = myField.selectionStart;
					var endPos = myField.selectionEnd;
					myField.value = myField.value.substring(0, startPos)
							+ myValue
							+ myField.value.substring(endPos, myField.value.length);
				} else {
					console.log('case 3');
					myField.value += myValue;
				}
				myField.focus();
				console.log(endPos);
				setCaretPosition(myField, endPos + myValue.length);
			}

			document.querySelectorAll(`.varbtn`).forEach(btn => {
				btn.addEventListener('click', function () {
					console.log(btn.innerHTML);
					insertAtCursor(document.querySelector(`#greetingMessage`), btn.innerHTML);
				});
			});
		</script>
	</div>
</div>
<?
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
