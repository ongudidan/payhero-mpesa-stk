<?php

// Display all errors for debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'database.php';
require_once 'token_generator.php';


$basicAuthToken = generateBasicAuthToken();
$amount = 1;
$phone_number = '0768540720';
$external_reference = 'test';
$channel_id = 1240;

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://backend.payhero.co.ke/api/v2/payments',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        "amount" => floatval($amount), // Ensure amount is a numeric value
        "phone_number" => $phone_number,
        "channel_id" => $channel_id,
        "provider" => "m-pesa",
        "external_reference" => $external_reference,
        "callback_url" => "https://7ea8-41-90-176-209.ngrok-free.app/projects/payhero-mpesa-stk/callback.php"
    ]),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: ' . $basicAuthToken
    ),
));

$response = (curl_exec($curl));
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo $response;

// Decode the response from JSON string to PHP array
$response = json_decode($response, true);

// Check if decoding was successful
if (is_array($response) && isset($response['success']) && $response['success']) {
    $db = new Database();
    $sql = "INSERT INTO payments (amount, phone_number, external_reference, checkout_request_id, status) 
            VALUES ('" . $db->escapeString($amount) . "', 
                    '" . $db->escapeString($phone_number) . "', 
                    '" . $db->escapeString($external_reference) . "', 
                    '" . $db->escapeString($response['CheckoutRequestID']) . "', 
                    'PENDING')";
    if ($db->query($sql)) {
        echo $external_reference . " Payment initiated " . $response['CheckoutRequestID'];
    } else {
        echo 'Failed to save payment details';
    }
} else {
    echo 'Payment initiation failed: ' . json_encode($response);
}

