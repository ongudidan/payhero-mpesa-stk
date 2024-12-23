<?php

// Display all errors for debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// callback.php
require_once 'config.php';
require_once 'database.php';


// header("Content-Type: application/json");

// // Acknowledge receipt of the M-Pesa response
// $response = json_encode([
//     "ResultCode" => 0,
//     "ResultDesc" => "Confirmation Received Successfully"
// ]);

header("Content-Type: application/json");


$callbackData = file_get_contents('php://input');
// Log the response for debugging and record-keeping
$logFile = "M_PESAConfirmationResponse.json";
file_put_contents($logFile, $callbackData . PHP_EOL, FILE_APPEND);

// Send acknowledgment response
$callbackData = json_decode($callbackData);

if (!empty($callbackData)) {
    $response = $callbackData->response;
    
    $db = new Database();
    $sql = "UPDATE payments SET 
            status = '" . $db->escapeString($response->Status) . "',
            mpesa_receipt_number = '" . $db->escapeString($response->MpesaReceiptNumber) . "',
            result_code = '" . $db->escapeString($response->ResultCode) . "',
            result_desc = '" . $db->escapeString($response->ResultDesc) . "'
            WHERE checkout_request_id = '" . $db->escapeString($response->CheckoutRequestID) . "'";
    
    if ($db->query($sql)) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Payment updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update payment']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid callback data']);
}