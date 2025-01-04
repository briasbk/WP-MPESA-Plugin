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
