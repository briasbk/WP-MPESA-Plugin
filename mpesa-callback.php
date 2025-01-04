<?php
// Load WordPress environment to access WooCommerce functions.
define('WP_USE_THEMES', false);
require_once(dirname(__FILE__) . '/wp-load.php');

header("Content-Type: application/json");

// Step 1: Capture the incoming JSON payload.
$callback_data = json_decode(file_get_contents('php://input'), true);

// Step 2: Validate payload structure.
if (!isset($callback_data['Body']['stkCallback'])) {
    error_log('[M-Pesa Callback] Invalid payload structure.');
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid Payload']);
    exit;
}

// Step 3: Extract relevant data from the callback.
$stk_callback = $callback_data['Body']['stkCallback'];
$merchant_request_id = $stk_callback['MerchantRequestID']; // Order ID or custom identifier.
$checkout_request_id = $stk_callback['CheckoutRequestID'];
$result_code = $stk_callback['ResultCode']; // 0 = success, others = failure.
$result_desc = $stk_callback['ResultDesc']; // Description of the result.
$callback_metadata = $stk_callback['CallbackMetadata'] ?? [];

// Default values if not set in the callback metadata.
$amount = 0;
$phone_number = '';
$transaction_date = '';
$mpesa_receipt_number = '';

if (isset($callback_metadata['Item'])) {
    foreach ($callback_metadata['Item'] as $item) {
        if ($item['Name'] === 'Amount') {
            $amount = $item['Value'];
        } elseif ($item['Name'] === 'PhoneNumber') {
            $phone_number = $item['Value'];
        } elseif ($item['Name'] === 'MpesaReceiptNumber') {
            $mpesa_receipt_number = $item['Value'];
        } elseif ($item['Name'] === 'TransactionDate') {
            $transaction_date = $item['Value'];
        }
    }
}

// Step 4: Log the callback data for debugging (optional).
error_log('[M-Pesa Callback] Payload: ' . json_encode($callback_data));

// Step 5: Handle success or failure responses.
if ($result_code == 0) {
    // Payment successful.
    $order_id = intval($merchant_request_id); // Assuming you used MerchantRequestID as the WooCommerce Order ID.

    // Fetch the WooCommerce order.
    $order = wc_get_order($order_id);

    if ($order) {
        // Add a note to the order with M-Pesa transaction details.
        $order->add_order_note("M-Pesa payment successful. Amount: KES $amount. Phone: $phone_number. Receipt: $mpesa_receipt_number. Transaction Date: $transaction_date.");
        $order->payment_complete(); // Mark order as completed.
        error_log("[M-Pesa Callback] Order #$order_id marked as completed.");
    } else {
        // Log error if the order is not found.
        error_log("[M-Pesa Callback] Order not found for MerchantRequestID: $merchant_request_id");
    }
} else {
    // Payment failed.
    $order_id = intval($merchant_request_id); // Assuming MerchantRequestID matches WooCommerce Order ID.

    // Fetch the WooCommerce order.
    $order = wc_get_order($order_id);

    if ($order) {
        // Add a failure note to the order.
        $order->add_order_note("M-Pesa payment failed. Reason: $result_desc.");
        $order->update_status('failed'); // Mark order as failed.
        error_log("[M-Pesa Callback] Order #$order_id marked as failed.");
    } else {
        // Log error if the order is not found.
        error_log("[M-Pesa Callback] Order not found for failed MerchantRequestID: $merchant_request_id");
    }
}

// Step 6: Respond to Safaricom with success.
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Acknowledged successfully']);
exit;
