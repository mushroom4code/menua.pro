<?php

$URL = 'https://bookingtest.sberhealth.ru/api/2.0';
$bearer = 'd0f89f0f82f343ef7b795f163d99e6671875917d534f01976e672aaa3de23552';
$alias = 'infinity';

function jwt_request($url, $token, $payliad) {
	header('Content-Type: application/json'); // Specify the type of data
	$ch = curl_init($url); // Initialise cURL
	$post = json_encode($payliad); // Encode the data array into a JSON string
	$authorization = "Authorization: Bearer " . $token; // Prepare the authorisation token
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
	$result = curl_exec($ch); // Execute the cURL statement
	curl_close($ch); // Close the cURL connection
	return json_decode($result); // Return the received data
}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

