<?php

if (R(121)
) {

	if (
			($_POST['action'] ?? '') == 'saveUserPayment' &&
			($_POST['user'] ?? false) &&
			($_POST['from'] ?? false) &&
			($_POST['to'] ?? false) &&
			($_POST['paymentValue'] ?? false)
	) {

		if (mysqlQuery("INSERT INTO `usersPayments` SET "
						. " `usersPaymentsUser`='" . mres($_POST['user']) . "',"
						. " `usersPaymentsFrom`='" . mres($_POST['from']) . "',"
						. " `usersPaymentsTo`='" . mres($_POST['to']) . "',"
						. " `usersPaymentsAmount`='" . mres($_POST['paymentValue']) . "',"
						. " `usersPaymentsBy` = '" . $_USER['id'] . "'")) {
			header("Location: " . GR());
			die();
		} else {
			print '<H1>Ошибка</H1>';
			die();
		}
	}
}
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

