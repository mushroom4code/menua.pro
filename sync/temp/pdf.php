<?php
 
require_once $_SERVER['DOCUMENT_ROOT'] . '/sync/3rdparty/dompdf/autoload.inc.php';
 
use Dompdf\Dompdf;
 
// instantiate and use the dompdf class
$dompdf = new Dompdf();
$dompdf->set_option('isHtml5ParserEnabled', true);
$dompdf->loadHtml('hello world');

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

// Output the ge nerated PDF to Browser
$dompdf->stream();
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

