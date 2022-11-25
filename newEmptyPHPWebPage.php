<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
		<?php
		print iconv("CP1251//TRANSLIT//IGNORE","UTF8",  file_get_contents('http://rzhunemogu.ru/RandJSON.aspx?CType=1&Teg=Врачи'));
		?>
    </body>
</html>
