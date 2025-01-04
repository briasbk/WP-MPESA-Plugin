# M-Pesa STK Push WooCommerce Integration

This project provides a complete integration of M-Pesa STK Push payments with WooCommerce. It includes a callback handling mechanism to update WooCommerce orders based on M-Pesa payment status.

## Features

- Handles M-Pesa STK Push payment results (success or failure).
- Updates WooCommerce orders automatically based on payment status.
- Logs callback payloads and errors for debugging.
- Secure endpoint for callback handling.

## Requirements

- **WordPress** with **WooCommerce** installed.
- M-Pesa credentials (shortcode, consumer key, consumer secret, and passkey).
- Publicly accessible URL for the callback endpoint.

## Setup Instructions

### 1. Clone or Copy the Files

Place the provided `mpesa-callback.php` file in the root of your WordPress installation or inside a custom plugin folder.

### 2. Set Up the Callback URL

1. Register a publicly accessible URL as your callback endpoint in the M-Pesa API.
   - Example URL: `https://yourwebsite.com/mpesa-callback.php`
2. Include this URL in your STK Push API request under the `CallBackURL` parameter.

### 3. Ensure WordPress Access

The callback script requires access to WordPress and WooCommerce functions. Ensure that:

- `wp-load.php` is accessible from the file location.
- WooCommerce is active and functional.

### 4. Testing the Callback

#### Using Postman:

Send a POST request to the callback URL with a sample payload:

```json
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "12345",
      "CheckoutRequestID": "ws_CO_12345",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          {"Name": "Amount", "Value": 1000},
          {"Name": "MpesaReceiptNumber", "Value": "ABC123XYZ"},
          {"Name": "TransactionDate", "Value": "20250101"},
          {"Name": "PhoneNumber", "Value": "254712345678"}
        ]
      }
    }
  }
}
```

#### Verify:

- The WooCommerce order matching the `MerchantRequestID` is updated as:
  - **Completed** if `ResultCode` is `0`.
  - **Failed** if `ResultCode` is non-zero.
- Logs for debugging are generated in the WordPress error log.

### 5. Security Recommendations

- Restrict access to the callback endpoint using server-level configurations or Safaricom IP whitelisting.
- Validate incoming requests using Safaricom’s headers or tokens (if available).
- Keep your M-Pesa credentials secure.

### 6. Logging

- Logs are stored in the server’s PHP error log. Use these logs to debug callback issues.
- Example log location: `/var/log/apache2/error.log` or `/wp-content/debug.log` (if `WP_DEBUG` is enabled).

## Callback Script Overview

The callback script processes the following:

1. **Success Case:**
   - Updates the WooCommerce order status to `Completed`.
   - Logs the payment details (amount, phone number, receipt number, transaction date).

2. **Failure Case:**
   - Updates the WooCommerce order status to `Failed`.
   - Logs the failure reason.

## Example STK Push API Request

```json
{
  "BusinessShortCode": "174379",
  "Password": "Base64EncodedPassword",
  "Timestamp": "20250101120000",
  "TransactionType": "CustomerPayBillOnline",
  "Amount": "100",
  "PartyA": "254712345678",
  "PartyB": "174379",
  "PhoneNumber": "254712345678",
  "CallBackURL": "https://yourwebsite.com/mpesa-callback.php",
  "AccountReference": "Order12345",
  "TransactionDesc": "Payment for Order #12345"
}
```

## Troubleshooting

- **Callback Not Triggered:**
  - Verify the callback URL is publicly accessible.
  - Check Safaricom sandbox or production logs for delivery errors.

- **Order Not Updated:**
  - Ensure `MerchantRequestID` matches your WooCommerce order ID.
  - Confirm that the WooCommerce database is accessible from the callback script.

## License

This project is open-source and can be freely modified and distributed.

## Support

For further assistance, contact the developer or refer to the [Safaricom API Documentation](https://developer.safaricom.co.ke/).
