<?php
/**
 * Plugin Name: M-Pesa STK Woo-Gateway
 * Plugin URI: https://soliddigital.co.ke/
 * Description: A WooCommerce payment gateway for Safaricom M-Pesa STK Push.
 * Author: Brias
 * Author URI: https://soliddigital.co.ke/
 * Version: 1.0
 * License: GPLv2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Initialize the gateway on plugin load.
add_action('plugins_loaded', 'mpesa_stk_gateway_init', 0);

function mpesa_stk_gateway_init() {
    if (!class_exists('WC_Payment_Gateway')) return; // Exit if WooCommerce is not active.

    class WC_Gateway_Mpesa_Stk extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'mpesa_stk'; // Unique ID for the gateway.
            $this->icon = ''; // Icon URL (optional).
            $this->has_fields = true; // Show fields for phone number input.
            $this->method_title = 'M-Pesa STK Push';
            $this->method_description = 'Accept payments via Safaricom M-Pesa STK Push.';
            
            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            
            // Save admin options.
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        // Admin settings fields.
        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable M-Pesa STK Push',
                    'default' => 'yes',
                ],
                'title' => [
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title for the payment method displayed to customers.',
                    'default' => 'M-Pesa',
                ],
                'description' => [
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'Payment method description shown to customers.',
                    'default' => 'Pay via M-Pesa STK Push.',
                ],
                'consumer_key' => [
                    'title' => 'Consumer Key',
                    'type' => 'text',
                    'description' => 'Your Safaricom M-Pesa API Consumer Key.',
                ],
                'consumer_secret' => [
                    'title' => 'Consumer Secret',
                    'type' => 'text',
                    'description' => 'Your Safaricom M-Pesa API Consumer Secret.',
                ],
                'shortcode' => [
                    'title' => 'Shortcode',
                    'type' => 'text',
                    'description' => 'Your M-Pesa Business Shortcode.',
                ],
                'passkey' => [
                    'title' => 'Passkey',
                    'type' => 'text',
                    'description' => 'The Passkey provided by Safaricom.',
                ],
                'callback_url' => [
                    'title' => 'Callback URL',
                    'type' => 'text',
                    'description' => 'Your endpoint to handle payment status notifications.',
                ],
            ];
        }

        // Display payment fields during checkout.
        public function payment_fields() {
            echo '<p>Enter your M-Pesa registered phone number to complete payment.</p>';
            echo '<label for="mpesa_phone">Phone Number:</label>';
            echo '<input type="text" id="mpesa_phone" name="mpesa_phone" required>';
        }

        // Process payment.
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $phone_number = isset($_POST['mpesa_phone']) ? sanitize_text_field($_POST['mpesa_phone']) : '';

            // Validate phone number.
            if (empty($phone_number)) {
                wc_add_notice('Please provide a phone number.', 'error');
                return;
            }

            // Initiate M-Pesa STK Push.
            $response = $this->mpesa_stk_push($order->get_total(), $phone_number, $order_id);

            if ($response && $response['ResponseCode'] === '0') {
                $order->add_order_note('M-Pesa STK Push initiated. Awaiting confirmation.');
                return [
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                ];
            } else {
                wc_add_notice('M-Pesa payment failed. Please try again.', 'error');
                return ['result' => 'failure'];
            }
        }

        // Send M-Pesa STK Push request.
        private function mpesa_stk_push($amount, $phone_number, $order_id) {
            $consumer_key = $this->get_option('consumer_key');
            $consumer_secret = $this->get_option('consumer_secret');
            $shortcode = $this->get_option('shortcode');
            $passkey = $this->get_option('passkey');
            $callback_url = $this->get_option('callback_url');

            // Get access token.
            $auth_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
            $auth_credentials = base64_encode("$consumer_key:$consumer_secret");

            $auth_response = wp_remote_post($auth_url, [
                'headers' => ['Authorization' => "Basic $auth_credentials"],
            ]);

            $access_token = json_decode(wp_remote_retrieve_body($auth_response), true)['access_token'];

            // Prepare STK Push request.
            $stk_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $timestamp = date('YmdHis');
            $password = base64_encode("$shortcode$passkey$timestamp");

            $stk_request = [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phone_number,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phone_number,
                'CallBackURL' => $callback_url,
                'AccountReference' => $order_id,
                'TransactionDesc' => 'Order Payment',
            ];

            $stk_response = wp_remote_post($stk_url, [
                'headers' => [
                    'Authorization' => "Bearer $access_token",
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($stk_request),
            ]);

            return json_decode(wp_remote_retrieve_body($stk_response), true);
        }
    }

    // Add the gateway to WooCommerce.
    add_filter('woocommerce_payment_gateways', 'add_mpesa_stk_gateway');

    function add_mpesa_stk_gateway($gateways) {
        $gateways[] = 'WC_Gateway_Mpesa_Stk';
        return $gateways;
    }
}
