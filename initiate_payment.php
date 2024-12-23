<?php
require_once 'config.php';
require_once 'database.php';
require_once 'token_generator.php';

function initiatePayment($amount, $phone_number, $channel_id, $external_reference)
{
    $basicAuthToken = generateBasicAuthToken();

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
            "callback_url" => "https://1d74-41-90-176-209.ngrok-free.app/projects/payhero-mpesa-stk/callback.php"
        ]),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: ' . $basicAuthToken
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        error_log("cURL Error: $error");
        return ['success' => false, 'message' => "cURL Error: $error"];
    }

    error_log("HTTP Code: $httpCode");
    error_log("Payhero API Response: " . $response);

    if ($httpCode != 200 && $httpCode != 201) {
        return ['success' => false, 'message' => "HTTP Error: $httpCode", 'response' => $response];
    }

    $decodedResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Invalid JSON response', 'response' => $response];
    }

    return ['success' => true, 'data' => $decodedResponse];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Set response format to JSON

    $amount = $_POST['amount'];
    $phone_number = $_POST['phone_number'];
    $channel_id = 1240;
    $external_reference = 'INV-' . time();

    $response = initiatePayment($amount, $phone_number, $channel_id, $external_reference);

    if ($response['success']) {
        $db = new Database();
        $sql = "INSERT INTO payments (amount, phone_number, external_reference, checkout_request_id, status) 
                VALUES ('" . $db->escapeString($amount) . "', 
                        '" . $db->escapeString($phone_number) . "', 
                        '" . $db->escapeString($external_reference) . "', 
                        '" . $db->escapeString($response['data']['CheckoutRequestID']) . "', 
                        'PENDING')";
        if ($db->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Payment initiated',
                'data' => [
                    'external_reference' => $external_reference,
                    'CheckoutRequestID' => $response['data']['CheckoutRequestID'],
                    'status' => $response['data']['status']
                ]
            ]);
        } else {
            error_log("Database Error: Failed to save payment details");
            echo json_encode(['success' => false, 'message' => 'Failed to save payment details']);
        }
    } else {
        echo json_encode($response);
    }
}
