<!DOCTYPE html>
<html lang="ru-RU">
    <head>
        <title>warehouse</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="/css/common.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
		<form action="<?= $_SERVER['DOCUMENT_URI'] . '?' . http_build_query($_GET); ?>" method="post" style="text-align: center;">
			<div class="box" style="display: inline-block; text-align: center; margin: 100px auto;">
				<h2>Авторизация</h2>
				<div class="box-body">
					<?php
					if (!empty($_SESSION['error'])) {
						print '<div class="error" style="padding: 10px;">';
						foreach ($_SESSION['error'] as $key => $error) {
							print '<div style="text-align: center;">' . $error . '</div>';
							unset($_SESSION['error'][$key]);
						}
						print '</div>';
					}
					if (!empty($errors)) {
						print '<div class="error" style="padding: 10px;">';
						foreach ($errors as $key => $error) {
							print '<div style="text-align: center;">' . $error . '</div>';
							unset($errors[$key]);
						}
						print '</div>';
					}
					?>
					<table style="max-width: 180px; margin: 0 auto;">
						<tr><td><input type="text" name="login" style="display: inline; width: auto; max-width: 100%;" value="<?= ($_POST['login'] ?? ''); ?>" placeholder="Логин" required/></td></tr>
						<tr><td><input type="password" name="password" style="display: inline; width: auto; max-width: 100%;" placeholder="Пароль"/></td></tr>
						<tr><td><button type="submit" class="login-button">Войти</button></td></tr>
					</table>
				</div>
			</div>
		</form>
	</body>
</html>