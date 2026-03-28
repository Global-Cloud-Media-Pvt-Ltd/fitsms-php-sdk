<?php

require __DIR__ . '/vendor/autoload.php';

use GlobalCloudMedia\FitSMS\FitSMS;

// Replace with your actual credentials
$apiToken = 'YOUR_BEARER_TOKEN_HERE';
$senderId = 'YOUR_SENDER_ID_HERE';

$phone = '947XXXXXXXX';

$sms = new FitSMS($apiToken, $senderId);

try {

    echo "Testing FitSMS PHP SDK...\n";

    // 1. Check Balance
    $balance = $sms->getBalance();
    echo "Balance: " . ($balance['data'] ? json_encode($balance['data']) : 'Error') . "\n";

    // 2. Send Test SMS
    $response = $sms->send($phone, 'Hello from Composer Package!');
    echo "Send Status: " . ($response['status'] ?? 'Failed') . "\n";

    // Wait for the gateway to process (DLRs are not instant)
    echo "Waiting 10 seconds for DLR... \n";

    sleep(10);

    /** * NOTE ON v4 ARCHITECTURE:
     * In v4, the Delivery Receipt (DLR) is automatically pushed to the 
     * Webhook URL you provided when requesting your Sender ID.
     * * Use getStatus() only for manual reconciliation
     */

    echo "Checking Status (Manual Reconciliation)... \n";

    // 3. Check Status
    $status = $sms->getStatus($response['data']['ruid'], $phone);
    echo "Status: " . json_encode($status) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}